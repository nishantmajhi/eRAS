<?php
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';

    // Start the session the same way every other page does, so the cookie that
    // gets cleared below carries the same path, domain and flags it was set
    // with. A bare session_start() here used the default parameters, and a
    // cookie cleared with different parameters is a different cookie: the
    // browser kept the original and carried on presenting the old session ID.
    Session::init();

    $_SESSION = [];

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

    header('Location: ./');
    exit();
