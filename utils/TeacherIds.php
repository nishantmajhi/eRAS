<?php

/**
 * Allocating teacher identifiers.
 *
 * Extracted from admin/api/teachers.php, where the numbering had two defects.
 *
 * The query read the wrong table:
 *
 *     SELECT teacher_id FROM users WHERE role = 'teacher' ORDER BY uuid DESC LIMIT 1
 *
 * `users` has three columns — uuid, hashed_secret, role — and no teacher_id.
 * Since PHP 8.1 mysqli reports errors as exceptions by default, so that line
 * threw "Unknown column 'teacher_id' in 'field list'" and nothing caught it:
 * adding a teacher failed every time.
 *
 * And the ordering was textual. Teacher IDs look like "2026-TCHR-9", so sorting
 * them as strings puts "2026-TCHR-9" *after* "2026-TCHR-10". Once ten teachers
 * existed, the highest by that ordering was 9, the next allocated would have
 * been 10, and 10 already existed.
 *
 * The numbering rule is a pure function of the existing IDs, which is what lets
 * tests/TeacherIdsTest.php check it without a database.
 */
final class TeacherIds
{
    /** The fixed middle segment of every teacher identifier. */
    public const MARKER = 'TCHR';

    /**
     * Pull the trailing number out of an identifier, or null if it has none.
     *
     * Only the last segment is read, so a year change does not affect it.
     */
    public static function sequenceOf(string $teacherId): ?int
    {
        if (preg_match('/-(\d+)$/', trim($teacherId), $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * The next sequence number, given every identifier already in use.
     *
     * Compares numerically. Identifiers that do not carry a number are ignored
     * rather than treated as zero, so one malformed row cannot reset the count.
     *
     * @param list<string> $existingIds
     */
    public static function nextSequence(array $existingIds): int
    {
        $highest = 0;

        foreach ($existingIds as $teacherId) {
            if (!is_string($teacherId)) {
                continue;
            }

            $sequence = self::sequenceOf($teacherId);

            if ($sequence !== null && $sequence > $highest) {
                $highest = $sequence;
            }
        }

        return $highest + 1;
    }

    /** Build an identifier from a year and a sequence number. */
    public static function format(int $year, int $sequence): string
    {
        return $year . '-' . self::MARKER . '-' . $sequence;
    }

    /**
     * Allocate the next identifier by asking the database what exists.
     *
     * Reads `teachers`, which is the table that actually holds teacher_id.
     * MAX over the cast suffix does the numeric comparison in SQL so the whole
     * list does not have to be fetched.
     */
    public static function allocate(mysqli $conn, ?int $year = null): string
    {
        $year ??= (int) date('Y');

        $statement = $conn->prepare(
            'SELECT MAX(CAST(SUBSTRING_INDEX(teacher_id, "-", -1) AS UNSIGNED)) AS highest
             FROM teachers
             WHERE teacher_id LIKE ?'
        );

        $pattern = $year . '-' . self::MARKER . '-%';
        $statement->bind_param('s', $pattern);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        // MAX returns null when nothing matches, and mysqli hands back strings.
        $highest = isset($row['highest']) ? (int) $row['highest'] : 0;

        return self::format($year, $highest + 1);
    }
}
