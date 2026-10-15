<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Redirect.php';

/*
 * Where the application is willing to send a browser.
 *
 * Two defects are covered. Guarded pages redirected to
 * header('Location:' . $_SERVER['HTTP_HOST']) — no space, no scheme, so the
 * browser read it as a relative path and landed on a page that does not exist;
 * and $_SESSION['redirect_target'] was taken from REQUEST_URI and followed
 * without a check, which a request for "//evil.example/" turns into an
 * off-site redirect.
 */

test('an ordinary path on this site is accepted', function (): void {
    assertTrue(Redirect::isSafePath('/admin/'), 'a directory');
    assertTrue(Redirect::isSafePath('/attendance/new/index.php'), 'a file');
    assertTrue(Redirect::isSafePath('/teacher/?course=CSC101&section=A'), 'a query string');
    assertTrue(Redirect::isSafePath('/'), 'the root');
});

test('an absolute URL to another site is refused', function (): void {
    assertFalse(Redirect::isSafePath('https://evil.example/'), 'https');
    assertFalse(Redirect::isSafePath('http://evil.example/'), 'http');
    assertFalse(Redirect::isSafePath('javascript:alert(1)'), 'a javascript URL');
    assertFalse(Redirect::isSafePath('data:text/html,x'), 'a data URL');
});

test('a protocol-relative URL is refused', function (): void {
    // The whole point: "//evil.example/" starts with a slash but is another
    // origin, and a client can put it in REQUEST_URI just by asking for it.
    assertFalse(Redirect::isSafePath('//evil.example/'), 'two slashes');
    assertFalse(Redirect::isSafePath('//evil.example/admin/'), 'with a path');
});

test('the backslash form of a protocol-relative URL is refused', function (): void {
    // Browsers normalise "/\host" to "//host".
    assertFalse(Redirect::isSafePath('/\\evil.example/'), 'slash backslash');
});

test('a relative path is refused', function (): void {
    assertFalse(Redirect::isSafePath('admin/'), 'no leading slash');
    assertFalse(Redirect::isSafePath('../admin/'), 'walking upwards');
    assertFalse(Redirect::isSafePath(''), 'nothing at all');
});

test('control characters are refused', function (): void {
    // A newline in a Location header lets the rest of the response be forged.
    assertFalse(Redirect::isSafePath("/admin/\r\nSet-Cookie: a=b"), 'carriage return and newline');
    assertFalse(Redirect::isSafePath("/admin/\n"), 'a bare newline');
    assertFalse(Redirect::isSafePath("/admin/\x00"), 'a null byte');
    assertFalse(Redirect::isSafePath("/admin/\x7F"), 'delete');
});

test('the way back to the sign-in page is counted correctly', function (): void {
    $root = 'C:/site';

    assertSame('./', Redirect::relativePath('C:/site', $root), 'index.php is already at the root');
    assertSame('../', Redirect::relativePath('C:/site/admin', $root), 'one level down');
    assertSame('../../', Redirect::relativePath('C:/site/attendance/new', $root), 'two levels down');
    assertSame('../../', Redirect::relativePath('C:/site/admin/api', $root), 'the API directory');
    assertSame('../../../', Redirect::relativePath('C:/site/a/b/c', $root), 'three levels down');
});

test('both path separators and either case work', function (): void {
    // The same code runs on a Windows development machine and a Linux host.
    assertSame('../', Redirect::relativePath('C:\\site\\admin', 'C:\\site'), 'backslashes');
    assertSame('../', Redirect::relativePath('C:\\Site\\Admin', 'c:/site'), 'mixed separators and case');
    assertSame('../../', Redirect::relativePath('/var/www/attendance/print', '/var/www'), 'a Linux path');
});

test('the redirect never depends on the Host header', function (): void {
    // relativePath takes only filesystem paths, so a forged Host header cannot
    // influence the result. This is the regression guard for the original bug.
    $before = $_SERVER['HTTP_HOST'] ?? null;
    $_SERVER['HTTP_HOST'] = 'evil.example';

    assertSame('../', Redirect::relativePath('/var/www/admin', '/var/www'), 'unchanged by the Host header');

    if ($before === null) {
        unset($_SERVER['HTTP_HOST']);
    } else {
        $_SERVER['HTTP_HOST'] = $before;
    }
});

test('the application root is the directory holding the sign-in page', function (): void {
    assertTrue(
        is_file(Redirect::applicationRoot() . DIRECTORY_SEPARATOR . 'index.php'),
        'index.php is where applicationRoot() says it is'
    );
});
