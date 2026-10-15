<?php

/*
 * Every require in the project names a file that exists, spelled exactly.
 *
 * This is here because the project is written on Windows and deployed on Linux.
 * Windows does not care about the case of a filename, so require 'redirect.php'
 * kept working after the file was renamed to Redirect.php, and the mistake would
 * only have appeared on the host — as a blank page, since a failed require is
 * fatal. Comparing against the real directory listing catches it here instead.
 */

/** @return list<string> every PHP file in the project, tests excluded. */
function projectPhpFiles(): array
{
    $root = dirname(__DIR__);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $entry): bool {
                $name = $entry->getFilename();

                return !in_array($name, ['.git', 'tests', 'node_modules'], true);
            }
        )
    );

    $files = [];
    foreach ($iterator as $entry) {
        if ($entry->isFile() && strtolower($entry->getExtension()) === 'php') {
            $files[] = $entry->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * The names actually present in a directory, so a comparison can be
 * case-sensitive even on a filesystem that is not.
 *
 * @return list<string>
 */
function namesIn(string $directory): array
{
    $entries = @scandir($directory);

    return $entries === false ? [] : array_values(array_diff($entries, ['.', '..']));
}

test('the project has PHP files to check', function (): void {
    assertTrue(count(projectPhpFiles()) > 20, 'the file walk found the project');
});

test('every required utils or auth file exists with that exact name', function (): void {
    // Matches the project's one style of require:
    //   require_once <base> . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Name.php';
    $pattern = "/DIRECTORY_SEPARATOR\s*\.\s*'(utils|auth|seeders|data|template)'\s*\.\s*DIRECTORY_SEPARATOR\s*\.\s*'([^']+)'/";

    $root = dirname(__DIR__);
    $checked = 0;

    foreach (projectPhpFiles() as $file) {
        $source = (string) file_get_contents($file);

        if (preg_match_all($pattern, $source, $matches, PREG_SET_ORDER) === 0) {
            continue;
        }

        foreach ($matches as $match) {
            [, $directory, $name] = $match;

            // seeders and data are required relative to database/, the rest
            // relative to the project root.
            $base = in_array($directory, ['seeders', 'data'], true)
                ? $root . DIRECTORY_SEPARATOR . 'database'
                : $root;

            $checked++;

            assertTrue(
                in_array($name, namesIn($base . DIRECTORY_SEPARATOR . $directory), true),
                sprintf('%s requires %s/%s', basename(dirname($file)) . '/' . basename($file), $directory, $name)
            );
        }
    }

    assertTrue($checked > 15, 'a meaningful number of requires were checked, not zero');
});

test('the files that define the shared classes are where they are required from', function (): void {
    $utils = namesIn(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils');

    foreach (['Attendance.php', 'Database.php', 'LoginThrottle.php', 'Passwords.php', 'Redirect.php', 'Status.php', 'TeacherIds.php'] as $name) {
        assertTrue(in_array($name, $utils, true), $name . ' is in utils/');
    }

    assertFalse(in_array('redirect.php', $utils, true), 'the old lowercase redirect.php is gone');
});

test('no PHP file in the project has a syntax error', function (): void {
    // php -l on every file. The suite is the only thing that runs all of them;
    // a page with a parse error is a blank screen in production.
    foreach (projectPhpFiles() as $file) {
        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);

        assertSame(0, $status, basename(dirname($file)) . '/' . basename($file) . ' parses: ' . implode(' ', $output));
    }
});
