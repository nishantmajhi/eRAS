<?php

/**
 * Rate limiting for sign-in attempts.
 *
 * There was none. The login form accepted unlimited guesses against accounts
 * whose passwords were, until today, the account ID itself — so an attacker who
 * knew the ID format (sequential, "2026-TCHR-1" upward) could enumerate staff
 * and try each ID as its own password with nothing slowing them down.
 *
 * State is kept in files rather than in the database, for three reasons: it
 * needs no schema change, it keeps working when the database is the thing that
 * is struggling, and it can be tested without a MySQL server.
 *
 * Both the clock and the storage directory are injectable, which is what makes
 * tests/LoginThrottleTest.php able to check the lockout windows without
 * actually waiting for them.
 */
final class LoginThrottle
{
    /** Failures allowed before the first delay. */
    public const FREE_ATTEMPTS = 5;

    /** How long a lockout lasts, by how many failures have accumulated. */
    private const LOCKOUT_STEPS = [
        5  => 60,      // 1 minute
        8  => 300,     // 5 minutes
        12 => 1800,    // 30 minutes
        20 => 3600,    // 1 hour
    ];

    /** Failures older than this are forgotten, so an honest user recovers. */
    public const WINDOW_SECONDS = 86400;

    private string $storageDir;

    /** @var callable():int */
    private $clock;

    public function __construct(?string $storageDir = null, ?callable $clock = null)
    {
        $this->storageDir = $storageDir ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eras-login-throttle');
        $this->clock = $clock ?? static fn (): int => time();
    }

    /**
     * Seconds remaining before this key may try again, or 0 if it may now.
     *
     * The key should identify the account being attacked, not the attacker: an
     * attacker can change IP address far more easily than a defender can track
     * one. Callers pass the submitted username.
     */
    public function retryAfter(string $key): int
    {
        $state = $this->read($key);
        $now = ($this->clock)();

        if ($state['lockedUntil'] > $now) {
            return $state['lockedUntil'] - $now;
        }

        return 0;
    }

    /** Whether this key is currently locked out. */
    public function isLocked(string $key): bool
    {
        return $this->retryAfter($key) > 0;
    }

    /**
     * Record a failed attempt and return the seconds the caller must now wait.
     *
     * Returns 0 while the account is still within its free attempts.
     */
    public function recordFailure(string $key): int
    {
        $state = $this->read($key);
        $now = ($this->clock)();

        // Forget failures that have aged out, so someone who mistyped their
        // password twice last week starts today with a clean slate.
        if ($state['firstFailureAt'] > 0 && ($now - $state['firstFailureAt']) > self::WINDOW_SECONDS) {
            $state = ['failures' => 0, 'lockedUntil' => 0, 'firstFailureAt' => 0];
        }

        $state['failures']++;

        if ($state['firstFailureAt'] === 0) {
            $state['firstFailureAt'] = $now;
        }

        $lockoutSeconds = self::lockoutFor($state['failures']);

        if ($lockoutSeconds > 0) {
            $state['lockedUntil'] = $now + $lockoutSeconds;
        }

        $this->write($key, $state);

        return $lockoutSeconds;
    }

    /** Clear the record for a key after a successful sign-in. */
    public function clear(string $key): void
    {
        $path = $this->pathFor($key);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * How long a lockout should last at a given number of failures.
     *
     * Public and static so the schedule can be asserted on directly.
     */
    public static function lockoutFor(int $failures): int
    {
        $lockout = 0;

        // The steps are ascending, so the last threshold reached wins.
        foreach (self::LOCKOUT_STEPS as $threshold => $seconds) {
            if ($failures >= $threshold) {
                $lockout = $seconds;
            }
        }

        return $lockout;
    }

    /** Human-readable form of a wait, for the message shown to the user. */
    public static function describeWait(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' second' . ($seconds === 1 ? '' : 's');
        }

        $minutes = (int) ceil($seconds / 60);

        return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
    }

    /** @return array{failures: int, lockedUntil: int, firstFailureAt: int} */
    private function read(string $key): array
    {
        $path = $this->pathFor($key);

        if (!is_file($path)) {
            return ['failures' => 0, 'lockedUntil' => 0, 'firstFailureAt' => 0];
        }

        $raw = @file_get_contents($path);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($decoded)) {
            return ['failures' => 0, 'lockedUntil' => 0, 'firstFailureAt' => 0];
        }

        return [
            'failures'       => (int) ($decoded['failures'] ?? 0),
            'lockedUntil'    => (int) ($decoded['lockedUntil'] ?? 0),
            'firstFailureAt' => (int) ($decoded['firstFailureAt'] ?? 0),
        ];
    }

    /** @param array{failures: int, lockedUntil: int, firstFailureAt: int} $state */
    private function write(string $key, array $state): void
    {
        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0700, true) && !is_dir($this->storageDir)) {
            return;
        }

        @file_put_contents($this->pathFor($key), json_encode($state), LOCK_EX);
    }

    /**
     * Where one key's record lives.
     *
     * The key is hashed rather than used as a filename: a username may contain
     * characters that are not valid in one, and hashing means a crafted
     * username cannot decide where the file is written.
     */
    private function pathFor(string $key): string
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . hash('sha256', strtolower(trim($key))) . '.json';
    }
}
