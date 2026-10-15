<?php

/**
 * Sending the browser somewhere else, safely.
 *
 * Two separate problems live here, and both were real.
 *
 * The first is where an unauthenticated visitor gets sent. Every guarded page
 * did this:
 *
 *     header('Location:' . $_SERVER['HTTP_HOST']);
 *
 * There is no space after the colon and no scheme, so the browser reads the
 * value as a *relative* path. From /admin/index.php that resolves to
 * /admin/example.com — a page that does not exist. Sign-in redirection has
 * therefore been landing on a 404 rather than on the sign-in form. Worse,
 * HTTP_HOST is whatever the client put in the Host header; on a server that
 * does not pin a canonical name, it is attacker-controlled, and student/
 * index.php prefixed it with "http://" and so would follow it off-site.
 *
 * The fix does not consult the request at all. The sign-in page is at a known
 * place on disk, so the number of directories between the current page and it
 * is a fact the code already knows; a relative "../../" the browser resolves
 * itself needs neither a host nor a scheme.
 *
 * The second is where a visitor gets sent *after* signing in.
 * $_SESSION['redirect_target'] is set from $_SERVER['REQUEST_URI'], which a
 * client can make into "//evil.example/path" simply by requesting it: that is
 * a protocol-relative URL, and the browser treats it as another origin. The
 * value is now checked before it is used.
 */
final class Redirect
{
    /**
     * Is this a path that stays on this site?
     *
     * A safe target is an absolute path on this host and nothing else. That
     * rules out, in order: a scheme ("https://..."), a protocol-relative URL
     * ("//host"), the backslash form some browsers normalise to it ("/\host"),
     * and control characters — a carriage return or newline in a Location
     * header lets the rest of the response be forged.
     */
    public static function isSafePath(string $target): bool
    {
        if ($target === '' || $target[0] !== '/') {
            return false;
        }

        if (str_starts_with($target, '//') || str_starts_with($target, '/\\')) {
            return false;
        }

        return preg_match('/[\x00-\x1F\x7F]/', $target) !== 1;
    }

    /**
     * How to get from one directory to another, as a relative URL path.
     *
     * Both arguments are filesystem paths. The result is what the browser
     * should be given to move from a page in $fromDir to $toDir: "../", "../../"
     * and so on, or "./" when they are the same directory.
     *
     * Kept as a pure function of two strings so tests/RedirectTest.php can check
     * the depth arithmetic without a web server.
     */
    public static function relativePath(string $fromDir, string $toDir): string
    {
        $from = self::segments($fromDir);
        $to   = self::segments($toDir);

        // Drop the shared prefix.
        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        $up = str_repeat('../', count($from));
        $down = $to === [] ? '' : implode('/', $to) . '/';
        $path = $up . $down;

        return $path === '' ? './' : $path;
    }

    /**
     * Split a filesystem path into comparable segments.
     *
     * Both separators are accepted because the same code runs on a Windows
     * development machine and a Linux host, and case is folded for the same
     * reason.
     */
    private static function segments(string $path): array
    {
        $normalised = str_replace('\\', '/', $path);
        $parts = array_values(array_filter(explode('/', $normalised), static fn (string $p): bool => $p !== ''));

        return array_map(static fn (string $p): string => strtolower($p), $parts);
    }

    /** The directory holding index.php, the sign-in page. */
    public static function applicationRoot(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Send an unauthenticated visitor to the sign-in page and stop.
     *
     * $currentDir is the calling page's own directory, normally __DIR__.
     */
    public static function toSignIn(string $currentDir): never
    {
        header('Location: ' . self::relativePath($currentDir, self::applicationRoot()));
        exit();
    }

    /**
     * Remember where the visitor was trying to go, if it is somewhere on this
     * site, and send them to sign in.
     */
    public static function requireSignIn(string $currentDir): never
    {
        $target = $_SERVER['REQUEST_URI'] ?? '';

        if (is_string($target) && self::isSafePath($target)) {
            $_SESSION['redirect_target'] = $target;
        } else {
            unset($_SESSION['redirect_target']);
        }

        self::toSignIn($currentDir);
    }
}

/**
 * Resume an interrupted request after a successful sign-in.
 *
 * Left as a free function because that is how index.php has always called it.
 * The stored value is re-checked here rather than trusted from when it was
 * written: the session may predate the check above.
 */
function checkForRedirection(): void {
    if (!isset($_SESSION['redirect_target'])) {
        return;
    }

    $target = $_SESSION['redirect_target'];
    unset($_SESSION['redirect_target']);

    if (is_string($target) && Redirect::isSafePath($target)) {
        header('Location: ' . $target);
        exit();
    }
}
