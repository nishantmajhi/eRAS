<?php

/**
 * The rules for recording and correcting attendance.
 *
 * The three attendance pages each grew their own copy of the same handling, and
 * each copy had the same three holes. They are gathered here so there is one
 * place to read and one place to test.
 *
 * 1. Nothing checked that the signed-in teacher actually teaches the class.
 *    Course code, semester and section arrive in the query string and the form
 *    body; changing them by hand let any teacher record or rewrite attendance
 *    for any class in the college. teaches() is the missing check.
 *
 * 2. The submit handlers walked $_POST and treated every field that was not a
 *    known form field as a student ID, with the submitted value as the mark:
 *
 *        foreach ($_POST as $key => $value) {
 *          if (in_array($key, ['csrf_token', 'courseCode', 'semester', 'section'])) continue;
 *          $studentID = $key; $status = $value;
 *
 *    So a hand-written POST could insert rows for students in other classes,
 *    and the status column — CHAR(1) — took whatever character the first byte
 *    of the value happened to be. collectMarks() checks both against the roster
 *    and against the two marks that exist.
 *
 *    The correction page had a further slip: 'createdDate' was missing from
 *    that exclusion list, so the date field itself was processed as a student.
 *    Nothing matched, so it silently did nothing, but it shows how little the
 *    loop knew about what it was reading.
 *
 * 3. modified_dates is VARCHAR(500) and the correction handler appended to it
 *    without limit. After roughly twenty corrections to one row the string no
 *    longer fits; in MySQL's strict mode that is an error that rolls back the
 *    whole submission, and otherwise the value is silently truncated mid-entry.
 *    appendModification() keeps the newest entries and drops the oldest.
 *
 * The parts that do not need a database are pure functions of their arguments,
 * which is what lets tests/AttendanceTest.php cover them.
 */
final class Attendance
{
    public const PRESENT = 'P';
    public const ABSENT  = 'A';

    /** Width of the attendance.modified_dates column. */
    public const MODIFIED_DATES_LIMIT = 500;

    /**
     * Form fields that carry something other than a student's mark.
     *
     * Anything in a submission that is not one of these is read as a student
     * ID, so this list has to be complete.
     */
    public const RESERVED_FIELDS = ['csrf_token', 'courseCode', 'semester', 'section', 'createdDate'];

    public static function isValidStatus(mixed $status): bool
    {
        return $status === self::PRESENT || $status === self::ABSENT;
    }

    /**
     * Is this a real calendar date written as YYYY-MM-DD?
     *
     * checkdate is the point: strtotime happily accepts "2026-02-31" and quietly
     * turns it into the third of March.
     *
     * The D modifier is not decoration. Without it PCRE lets "$" match just
     * before a trailing newline, so "2026-09-05\n" passed — and that string
     * would then have gone into a query parameter and a Location header.
     */
    public static function isValidDate(mixed $date): bool
    {
        if (!is_string($date) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    /**
     * Read the marks out of a submission, keeping only what belongs there.
     *
     * Returns the accepted marks along with everything that was rejected and
     * every student on the roster who was not marked, so the caller can decide
     * whether to save a partial submission or refuse it.
     *
     * @param array<string,mixed> $post
     * @param list<string> $roster student IDs in this class
     * @return array{marks: array<string,string>, unknown: list<string>, invalid: list<string>, missing: list<string>}
     */
    public static function collectMarks(array $post, array $roster): array
    {
        $onRoster = array_fill_keys($roster, true);

        $marks   = [];
        $unknown = [];
        $invalid = [];

        foreach ($post as $field => $value) {
            if (in_array($field, self::RESERVED_FIELDS, true)) {
                continue;
            }

            if (!isset($onRoster[$field])) {
                $unknown[] = (string) $field;
                continue;
            }

            if (!self::isValidStatus($value)) {
                $invalid[] = (string) $field;
                continue;
            }

            $marks[(string) $field] = $value;
        }

        $missing = array_values(array_filter(
            $roster,
            static fn (string $studentId): bool => !isset($marks[$studentId])
        ));

        return ['marks' => $marks, 'unknown' => $unknown, 'invalid' => $invalid, 'missing' => $missing];
    }

    /**
     * Add one timestamp to the modification history, keeping it inside the
     * column.
     *
     * The oldest entries are dropped rather than the newest, and an entry is
     * never cut in half: a truncated timestamp is worse than a missing one
     * because it still looks like a record.
     */
    public static function appendModification(
        string $existing,
        string $stamp,
        int $limit = self::MODIFIED_DATES_LIMIT
    ): string {
        $entry = '[' . $stamp . ']';

        $entries = $existing === '' ? [] : array_map('trim', explode(',', $existing));
        $entries = array_values(array_filter($entries, static fn (string $e): bool => $e !== ''));
        $entries[] = $entry;

        while (count($entries) > 1 && strlen(implode(', ', $entries)) > $limit) {
            array_shift($entries);
        }

        $history = implode(', ', $entries);

        // A single entry longer than the column is the only case left; cutting
        // it is still better than an error that loses the correction itself.
        return strlen($history) > $limit ? substr($history, 0, $limit) : $history;
    }

    /** Does this teacher teach this course and section? */
    public static function teaches(mysqli $conn, string $teacherId, string $courseCode, string $section): bool
    {
        $statement = $conn->prepare(
            'SELECT 1 FROM teachers WHERE teacher_id = ? AND course_code = ? AND section = ? LIMIT 1'
        );
        $statement->bind_param('sss', $teacherId, $courseCode, $section);
        $statement->execute();
        $found = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $found;
    }

    /**
     * The student IDs enrolled in a class.
     *
     * @return list<string>
     */
    public static function roster(mysqli $conn, string $courseCode, string $section): array
    {
        $statement = $conn->prepare(
            'SELECT student_id FROM students WHERE course_code = ? AND section = ?'
        );
        $statement->bind_param('ss', $courseCode, $section);
        $statement->execute();
        $result = $statement->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (string) $row['student_id'];
        }
        $statement->close();

        return $ids;
    }

    /** Has attendance already been recorded for this class on this date? */
    public static function recordedOn(mysqli $conn, string $courseCode, string $section, string $date): bool
    {
        $statement = $conn->prepare(
            'SELECT 1 FROM attendance WHERE course_code = ? AND section = ? AND created_date = ? LIMIT 1'
        );
        $statement->bind_param('sss', $courseCode, $section, $date);
        $statement->execute();
        $found = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $found;
    }
}
