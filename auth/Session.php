<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Redirect.php';

/**
 * The signed-in session.
 *
 * What changed and why:
 *
 * The cookie carried no flags at all, so it was readable by any script on the
 * page and was sent on cross-site requests. It now sets HttpOnly, SameSite=Lax
 * and — when the request arrived over HTTPS — Secure.
 *
 * There was only an idle timeout. A session that is used continuously never
 * ended, so a signed-in browser left on a shared machine stayed signed in
 * indefinitely. There is now an absolute lifetime as well.
 *
 * Expiry ended with header('Location: index.php'), a *relative* path. From
 * /admin/api/teachers.php that resolves to /admin/api/index.php, which does not
 * exist; and an expired session on an API endpoint returned a redirect where
 * the caller's fetch() expected JSON, so the front end saw HTML and reported
 * nothing useful. Expiry now answers 401 with JSON on API paths and sends a
 * browser page to the sign-in form by a path that actually reaches it.
 *
 * The expiry rule itself is expiryReason(), a pure function of the session
 * array and a timestamp, so tests/SessionTest.php can check hour-long windows
 * without waiting for them.
 */
class Session {
    /** How long a session may sit unused. */
    public const IDLE_TIMEOUT = 1800;

    /** How long a session may live, however active it is. */
    public const ABSOLUTE_LIFETIME = 28800;

    public static function init(): void {
        date_default_timezone_set('Asia/Kathmandu');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params(self::cookieParams());
            session_start();
        }

        $reason = self::expiryReason($_SESSION, time());

        if ($reason !== null) {
            self::terminate($reason);
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Why this session should end, or null if it should not.
     *
     * A session with no marks on it is not expired: that is a visitor who has
     * not signed in yet, and index.php needs to be able to render the form.
     */
    public static function expiryReason(array $session, int $now): ?string {
        if (isset($session['last_activity']) && $now - (int) $session['last_activity'] > self::IDLE_TIMEOUT) {
            return 'idle';
        }

        if (isset($session['started_at']) && $now - (int) $session['started_at'] > self::ABSOLUTE_LIFETIME) {
            return 'expired';
        }

        return null;
    }

    /**
     * Cookie settings for the session.
     *
     * Secure is conditional because the project is developed over plain HTTP;
     * setting it unconditionally would drop the cookie there and make sign-in
     * appear to silently fail.
     */
    private static function cookieParams(): array {
        return [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    public static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /** Is the current script one that answers with JSON rather than a page? */
    private static function isApiRequest(): bool {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return str_contains($script, '/api/');
    }

    private static function terminate(string $reason): never {
        $_SESSION = [];

        // session_destroy() leaves the cookie in the browser, which then keeps
        // presenting a session ID the server has thrown away.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();

        if (self::isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            exit(json_encode(['error' => 'Session expired', 'reason' => $reason]));
        }

        header('Location: ' . Redirect::relativePath(dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')), Redirect::applicationRoot()));
        exit();
    }
}
