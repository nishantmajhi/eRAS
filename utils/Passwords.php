<?php

/**
 * Password generation and strength rules.
 *
 * This exists because new teacher accounts were created with the teacher's own
 * ID as the password:
 *
 *     $tempHash = password_hash($teacherId, PASSWORD_DEFAULT);
 *
 * A teacher ID has the form "2026-TCHR-4". It is printed on lists, appears in
 * URLs, and is known to every student and colleague, so any account whose
 * password had not been changed was open to anyone holding a staff list — and
 * the IDs are sequential, so a list was not even necessary.
 */
final class Passwords
{
    /**
     * Alphabet for generated passwords.
     *
     * Omits the characters people mistake for one another when reading a
     * password off a screen and typing it somewhere else: 0/O, 1/l/I. A
     * temporary password is always transcribed by hand at least once, so this
     * matters more here than the handful of bits it costs.
     */
    private const ALPHABET = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** Length of a generated temporary password. */
    public const TEMPORARY_LENGTH = 12;

    /** Shortest password a person may choose. */
    public const MINIMUM_LENGTH = 8;

    /**
     * A random temporary password.
     *
     * Uses random_int, which draws from the system CSPRNG. rand() and mt_rand()
     * are predictable from a handful of outputs and must not be used for
     * anything anyone has to guess.
     */
    public static function generateTemporary(int $length = self::TEMPORARY_LENGTH): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Password length must be at least 1.');
        }

        $alphabetSize = strlen(self::ALPHABET);
        $password = '';

        for ($index = 0; $index < $length; $index++) {
            $password .= self::ALPHABET[random_int(0, $alphabetSize - 1)];
        }

        return $password;
    }

    /**
     * Whether a chosen password is acceptable.
     *
     * Deliberately a length floor rather than a composition rule. Requiring a
     * digit and a symbol reliably produces "Password1!" and little else; length
     * is what actually costs an attacker time.
     *
     * @return array{ok: bool, error: string}
     */
    public static function check(string $password, string $userIdentifier = ''): array
    {
        if (strlen($password) < self::MINIMUM_LENGTH) {
            return [
                'ok' => false,
                'error' => sprintf('The password must be at least %d characters.', self::MINIMUM_LENGTH),
            ];
        }

        // The specific failure this whole class exists to prevent.
        if ($userIdentifier !== '' && strcasecmp(trim($password), trim($userIdentifier)) === 0) {
            return ['ok' => false, 'error' => 'The password must not be the same as the account ID.'];
        }

        return ['ok' => true, 'error' => ''];
    }

    /** Hash a password for storage. */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** Verify a password against a stored hash. */
    public static function verify(string $password, string $hash): bool
    {
        return $hash !== '' && password_verify($password, $hash);
    }
}
