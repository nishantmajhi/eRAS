<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Passwords.php';

/*
 * These cover the rule that new teacher accounts must not be guessable. The
 * account creation endpoint used the teacher's own ID as the password, and
 * teacher IDs are sequential and publicly known.
 */

test('a generated password is not predictable', function (): void {
    $first  = Passwords::generateTemporary();
    $second = Passwords::generateTemporary();

    assertSame(Passwords::TEMPORARY_LENGTH, strlen($first), 'the requested length is produced');
    assertNotSame($first, $second, 'two calls do not produce the same password');
});

test('generated passwords avoid characters that are misread', function (): void {
    // A temporary password is always transcribed by hand at least once.
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $password = Passwords::generateTemporary(32);

        assertSame(false, str_contains($password, '0'), 'no zero');
        assertSame(false, str_contains($password, 'O'), 'no capital O');
        assertSame(false, str_contains($password, '1'), 'no one');
        assertSame(false, str_contains($password, 'l'), 'no lowercase L');
        assertSame(false, str_contains($password, 'I'), 'no capital I');
    }
});

test('generated passwords use the full alphabet over many draws', function (): void {
    // A generator stuck on a subset of its alphabet would still pass the tests
    // above; this catches that.
    $seen = [];

    for ($attempt = 0; $attempt < 200; $attempt++) {
        foreach (str_split(Passwords::generateTemporary()) as $character) {
            $seen[$character] = true;
        }
    }

    assertTrue(count($seen) > 40, 'a wide range of characters appears across many draws');
});

test('a password length of zero or less is refused', function (): void {
    Harness::assertThrows(
        static fn () => Passwords::generateTemporary(0),
        'a zero-length password is refused'
    );
});

test('a password equal to the account id is refused', function (): void {
    // The exact defect: password_hash($teacherId) as the account's password.
    $verdict = Passwords::check('2026-TCHR-4', '2026-TCHR-4');

    assertFalse($verdict['ok'], 'the account ID is not an acceptable password');
    assertMatches('/same as the account ID/', $verdict['error'], 'the reason says why');
});

test('the account id check ignores case and surrounding space', function (): void {
    assertFalse(Passwords::check('2026-tchr-4  ', '2026-TCHR-4')['ok'], 'case and space do not evade the check');
});

test('a short password is refused', function (): void {
    $verdict = Passwords::check('short');

    assertFalse($verdict['ok'], 'below the minimum length');
    assertMatches('/at least/', $verdict['error'], 'the reason names the minimum');
});

test('a long enough password unrelated to the id is accepted', function (): void {
    assertTrue(Passwords::check('a-reasonable-passphrase', '2026-TCHR-4')['ok'], 'accepted');
});

test('generated temporary passwords pass their own rules', function (): void {
    for ($attempt = 0; $attempt < 20; $attempt++) {
        $password = Passwords::generateTemporary();

        assertTrue(Passwords::check($password, '2026-TCHR-4')['ok'], 'a generated password is acceptable');
    }
});

test('hashing and verifying round trip', function (): void {
    $password = Passwords::generateTemporary();
    $hash = Passwords::hash($password);

    assertTrue(Passwords::verify($password, $hash), 'the correct password verifies');
    assertFalse(Passwords::verify($password . 'x', $hash), 'a wrong password does not');
    assertFalse(Passwords::verify($password, ''), 'an empty hash never verifies');
});

test('the stored hash is not the password', function (): void {
    $password = Passwords::generateTemporary();
    $hash = Passwords::hash($password);

    assertNotSame($password, $hash, 'the hash differs from the password');
    assertTrue(strlen($hash) > 50, 'the hash has the shape of a bcrypt digest');
});
