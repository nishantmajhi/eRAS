<?php

/**
 * Rendering the shared status page.
 *
 * Every page built this by hand:
 *
 *     echo str_replace(['{{STATUS}}', '{{MESSAGE}}'], ['ERROR ❌', '...'],
 *         file_get_contents(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'template' . ...));
 *
 * — twenty-odd copies, each carrying its own directory arithmetic, and none of
 * them escaping the message. Where the message included something the visitor
 * supplied, such as a date from the query string, that was an injection point
 * into the page.
 */
final class Status
{
    /** Show a status page and stop. Both arguments are escaped. */
    public static function show(string $heading, string $message): never
    {
        $template = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'status.html';

        echo str_replace(
            ['{{STATUS}}', '{{MESSAGE}}'],
            [htmlspecialchars($heading, ENT_QUOTES), htmlspecialchars($message, ENT_QUOTES)],
            (string) file_get_contents($template)
        );

        exit;
    }

    public static function error(string $message): never
    {
        self::show('ERROR ❌', $message);
    }

    public static function success(string $message): never
    {
        self::show('Success ✅', $message);
    }

    public static function warning(string $heading, string $message): never
    {
        self::show($heading . '⚠️', $message);
    }
}
