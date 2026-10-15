<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'LoginThrottle.php';

/**
 * A throttle writing to a directory this test owns, with a clock it controls.
 *
 * The injectable clock is the point: the lockout windows are minutes and hours
 * long, and a test that actually waited for them would be useless.
 */
function makeThrottle(?array &$now = null): LoginThrottle
{
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eras-throttle-test-' . bin2hex(random_bytes(6));
    $now = ['t' => 1_757_000_000];

    return new LoginThrottle($directory, static function () use (&$now): int {
        return $now['t'];
    });
}

test('the lockout schedule escalates with repeated failures', function (): void {
    assertSame(0, LoginThrottle::lockoutFor(1), 'a single mistake costs nothing');
    assertSame(0, LoginThrottle::lockoutFor(4), 'still free at four');
    assertSame(60, LoginThrottle::lockoutFor(5), 'one minute at five');
    assertSame(60, LoginThrottle::lockoutFor(7), 'still one minute at seven');
    assertSame(300, LoginThrottle::lockoutFor(8), 'five minutes at eight');
    assertSame(1800, LoginThrottle::lockoutFor(12), 'thirty minutes at twelve');
    assertSame(3600, LoginThrottle::lockoutFor(20), 'an hour at twenty');
    assertSame(3600, LoginThrottle::lockoutFor(500), 'and stays there');
});

test('an account is not locked before the free attempts are used', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 1; $attempt < LoginThrottle::FREE_ATTEMPTS; $attempt++) {
        assertSame(0, $throttle->recordFailure('2026-TCHR-4'), 'attempt ' . $attempt . ' is free');
        assertFalse($throttle->isLocked('2026-TCHR-4'), 'not locked after attempt ' . $attempt);
    }
});

test('the fifth failure locks the account', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
    }

    assertTrue($throttle->isLocked('2026-TCHR-4'), 'locked after five failures');
    assertSame(60, $throttle->retryAfter('2026-TCHR-4'), 'for one minute');
});

test('the lock lifts once the clock moves past it', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
    }

    $now['t'] += 59;
    assertTrue($throttle->isLocked('2026-TCHR-4'), 'still locked one second before it expires');

    $now['t'] += 2;
    assertFalse($throttle->isLocked('2026-TCHR-4'), 'free once the window has passed');
});

test('accounts are throttled independently', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
    }

    assertTrue($throttle->isLocked('2026-TCHR-4'), 'the attacked account is locked');
    assertFalse($throttle->isLocked('2026-TCHR-9'), 'a different account is unaffected');
});

test('a successful sign-in clears the record', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
    }

    assertTrue($throttle->isLocked('2026-TCHR-4'), 'locked');

    $throttle->clear('2026-TCHR-4');

    assertFalse($throttle->isLocked('2026-TCHR-4'), 'cleared after a correct password');
    assertSame(0, $throttle->retryAfter('2026-TCHR-4'), 'and no wait remains');
});

test('old failures are forgotten so an honest user recovers', function (): void {
    $throttle = makeThrottle($now);

    // Four failures today, then a long gap.
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
    }

    $now['t'] += LoginThrottle::WINDOW_SECONDS + 1;

    // The count restarts, so this is a first failure rather than a fifth.
    assertSame(0, $throttle->recordFailure('2026-TCHR-4'), 'no lockout after the window expires');
    assertFalse($throttle->isLocked('2026-TCHR-4'), 'not locked');
});

test('failures inside the window keep accumulating', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
        $now['t'] += 60;
    }

    // Well inside the window, so this is the fifth failure.
    assertSame(60, $throttle->recordFailure('2026-TCHR-4'), 'the fifth failure still locks');
});

test('a username that is not a valid filename is handled', function (): void {
    $throttle = makeThrottle($now);

    // The key is hashed, so a crafted username cannot choose where the record
    // is written or break the write entirely.
    $awkward = '../../etc/passwd';

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $throttle->recordFailure($awkward);
    }

    assertTrue($throttle->isLocked($awkward), 'throttling still works for an awkward username');
});

test('the key is matched case-insensitively', function (): void {
    $throttle = makeThrottle($now);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $throttle->recordFailure('2026-TCHR-4');
    }

    // Otherwise changing the case of the username resets the counter.
    assertTrue($throttle->isLocked('2026-tchr-4'), 'a different case does not evade the lockout');
});

test('waits are described in a form a person can read', function (): void {
    assertSame('30 seconds', LoginThrottle::describeWait(30), 'seconds');
    assertSame('1 second', LoginThrottle::describeWait(1), 'singular second');
    assertSame('1 minute', LoginThrottle::describeWait(60), 'singular minute');
    assertSame('5 minutes', LoginThrottle::describeWait(300), 'plural minutes');
    assertSame('2 minutes', LoginThrottle::describeWait(61), 'rounded up rather than down');
});

test('a corrupt record is treated as no record', function (): void {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eras-throttle-corrupt-' . bin2hex(random_bytes(6));
    mkdir($directory, 0700, true);

    $throttle = new LoginThrottle($directory, static fn (): int => 1_757_000_000);
    $throttle->recordFailure('2026-TCHR-4');

    // Overwrite the record with rubbish.
    foreach (glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        file_put_contents($file, 'not json at all');
    }

    // Failing open here is deliberate: a damaged throttle file must not lock
    // a legitimate user out permanently.
    assertFalse($throttle->isLocked('2026-TCHR-4'), 'a damaged record does not lock anyone out');
    assertSame(0, $throttle->recordFailure('2026-TCHR-4'), 'and counting restarts');
});
