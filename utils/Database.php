<?php

/**
 * Opening the database connection.
 *
 * Two things were wrong here.
 *
 * The first was that a failed connection returned null:
 *
 *     } catch (mysqli_sql_exception $e) {
 *         error_log(...);
 *         return null;
 *     }
 *
 * Every caller then did `if ($conn->connect_error)`. On null that is a fatal
 * "Call to a member function on null", so the "Connection to database failed!"
 * page every one of them had ready was unreachable — a database outage showed a
 * blank screen instead. This class now always hands back a mysqli object, with
 * connect_error set when the connection failed, which is what the callers were
 * written to expect.
 *
 * The second was that choosing between the local and the hosted database meant
 * editing this file:
 *
 *     // return self::connectCloud();
 *     return self::connectLocalhost();
 *
 * Deploying therefore meant a code change that must not be committed. Which
 * connection to use is now a setting in .ENV — CONNECTION = 'cloud' — and
 * anything else, including no .ENV at all, means the local database. That
 * default is the behaviour the commented-out line gave, so nothing changes for
 * an existing checkout.
 */
class Database {
    public static function establishConnection(): mysqli {
        // mysqli reports errors as exceptions by default since PHP 8.1, and a
        // throw during construction leaves no object to inspect. Reporting is
        // turned off for the connection attempt only, then restored to the
        // default so that query errors elsewhere still surface.
        mysqli_report(MYSQLI_REPORT_OFF);

        try {
            $connection = self::useCloud() ? self::connectCloud() : self::connectLocalhost();
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        if ($connection->connect_error) {
            // The message can name the host and user, so it goes to the log and
            // not to the visitor.
            error_log('Database connection failed: ' . $connection->connect_error);
        }

        return $connection;
    }

    /** Settings from .ENV, or an empty array when there is no .ENV. */
    private static function settings(): array {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.ENV';

        if (!is_readable($path)) {
            return [];
        }

        $parsed = parse_ini_file($path);

        return is_array($parsed) ? $parsed : [];
    }

    private static function useCloud(): bool {
        $settings = self::settings();

        return strtolower(trim((string) ($settings['CONNECTION'] ?? ''))) === 'cloud';
    }

    private static function connectCloud(): mysqli {
        $credentials = self::settings();

        return @new mysqli(
            (string) ($credentials['HOSTNAME'] ?? ''),
            (string) ($credentials['USERNAME'] ?? ''),
            (string) ($credentials['PASSWORD'] ?? ''),
            (string) ($credentials['DBNAME'] ?? ''),
            isset($credentials['PORT']) ? (int) $credentials['PORT'] : null
        );
    }

    private static function connectLocalhost(): mysqli {
        return @new mysqli('localhost', 'root', '', 'eras');
    }
}
