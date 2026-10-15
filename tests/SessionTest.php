<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';

/*
 * When a session ends.
 *
 * The rule is expressed as a pure function of the session array and a
 * timestamp precisely so it can be tested: the windows are half an hour and
 * eight hours long, and a test that waited for them would never be run.
 */

test('a fresh session is not expired', function (): void {
    $now = 1_757_000_000;

    assertNull(
        Session::expiryReason(['last_activity' => $now, 'started_at' => $now], $now),
        'just signed in'
    );
});

test('a session with no marks on it is not expired', function (): void {
    // This is a visitor who has not signed in yet. Treating it as expired would
    // redirect the sign-in page to itself.
    assertNull(Session::expiryReason([], 1_757_000_000), 'an empty session is simply not signed in');
    assertNull(Session::expiryReason(['csrf_token' => 'abc'], 1_757_000_000), 'a CSRF token alone is not a sign-in');
});

test('a session that has been idle too long expires', function (): void {
    $start = 1_757_000_000;
    $session = ['last_activity' => $start, 'started_at' => $start];

    assertNull(
        Session::expiryReason($session, $start + Session::IDLE_TIMEOUT),
        'still alive at exactly the timeout'
    );

    assertSame(
        'idle',
        Session::expiryReason($session, $start + Session::IDLE_TIMEOUT + 1),
        'expired one second later'
    );
});

test('a session that is used continuously still expires eventually', function (): void {
    // The defect: there was only an idle timeout, so a browser left signed in
    // on a shared machine and touched now and then never had to sign in again.
    $start = 1_757_000_000;
    $muchLater = $start + Session::ABSOLUTE_LIFETIME + 1;

    $session = [
        'started_at'    => $start,
        'last_activity' => $muchLater - 5,
    ];

    assertSame('expired', Session::expiryReason($session, $muchLater), 'the absolute lifetime is reached');
});

test('the absolute lifetime is not reached a moment early', function (): void {
    $start = 1_757_000_000;

    $session = [
        'started_at'    => $start,
        'last_activity' => $start + Session::ABSOLUTE_LIFETIME,
    ];

    assertNull(
        Session::expiryReason($session, $start + Session::ABSOLUTE_LIFETIME),
        'alive at exactly the lifetime'
    );
});

test('idle expiry is reported before absolute expiry', function (): void {
    // Both are true here; the idle reason is the more accurate description and
    // is what the API response says.
    $start = 1_757_000_000;

    $session = ['started_at' => $start, 'last_activity' => $start];

    assertSame(
        'idle',
        Session::expiryReason($session, $start + Session::ABSOLUTE_LIFETIME + 1),
        'idle wins'
    );
});

test('timestamps stored as strings are still compared as numbers', function (): void {
    // Values that have been through JSON or a form come back as strings.
    $start = 1_757_000_000;

    assertSame(
        'idle',
        Session::expiryReason(['last_activity' => (string) $start], $start + Session::IDLE_TIMEOUT + 1),
        'a string timestamp expires the same way'
    );
});

test('the timeouts are the documented lengths', function (): void {
    assertSame(1800, Session::IDLE_TIMEOUT, 'thirty minutes idle');
    assertSame(28800, Session::ABSOLUTE_LIFETIME, 'eight hours in total');
});

test('HTTPS is detected from the server environment', function (): void {
    $https = $_SERVER['HTTPS'] ?? null;
    $port  = $_SERVER['SERVER_PORT'] ?? null;

    // The Secure cookie flag has to be conditional: setting it on a plain HTTP
    // development server would drop the session cookie and make sign-in appear
    // to fail silently.
    unset($_SERVER['HTTPS']);
    $_SERVER['SERVER_PORT'] = '80';
    assertFalse(Session::isHttps(), 'plain HTTP');

    $_SERVER['HTTPS'] = 'off';
    assertFalse(Session::isHttps(), 'the literal "off" that IIS sends');

    $_SERVER['HTTPS'] = 'on';
    assertTrue(Session::isHttps(), 'HTTPS on');

    unset($_SERVER['HTTPS']);
    $_SERVER['SERVER_PORT'] = '443';
    assertTrue(Session::isHttps(), 'port 443 with no HTTPS variable');

    if ($https === null) { unset($_SERVER['HTTPS']); } else { $_SERVER['HTTPS'] = $https; }
    if ($port === null) { unset($_SERVER['SERVER_PORT']); } else { $_SERVER['SERVER_PORT'] = $port; }
});
