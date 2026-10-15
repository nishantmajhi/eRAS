<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Attendance.php';

/*
 * What a submitted attendance sheet is allowed to contain.
 *
 * The handlers used to read every unrecognised POST field as a student ID and
 * its value as the mark, with no reference to who is in the class or to what a
 * mark can be. These are the rules that replaced that.
 */

/** The roster the tests work against. */
function roster(): array
{
    return ['2026-BCA-SDNT-1', '2026-BCA-SDNT-2', '2026-BCA-SDNT-3'];
}

test('only P and A are marks', function (): void {
    assertTrue(Attendance::isValidStatus('P'), 'present');
    assertTrue(Attendance::isValidStatus('A'), 'absent');

    assertFalse(Attendance::isValidStatus('p'), 'lowercase is not a mark');
    assertFalse(Attendance::isValidStatus('X'), 'an unrelated letter');
    assertFalse(Attendance::isValidStatus(''), 'nothing');
    assertFalse(Attendance::isValidStatus('PA'), 'two letters — the column is CHAR(1) and would have kept the first');
    assertFalse(Attendance::isValidStatus(null), 'a missing value');
    assertFalse(Attendance::isValidStatus(['P']), 'an array, which PHP accepts as a POST value');
});

test('a complete honest submission is accepted whole', function (): void {
    $post = [
        'csrf_token' => 'abc',
        'courseCode' => 'CSC109',
        'semester'   => '1',
        'section'    => 'A',
        '2026-BCA-SDNT-1' => 'P',
        '2026-BCA-SDNT-2' => 'A',
        '2026-BCA-SDNT-3' => 'P',
    ];

    $collected = Attendance::collectMarks($post, roster());

    assertSame(3, count($collected['marks']), 'all three students');
    assertSame('A', $collected['marks']['2026-BCA-SDNT-2'], 'the absent one is recorded absent');
    assertSame([], $collected['unknown'], 'nothing unknown');
    assertSame([], $collected['invalid'], 'nothing invalid');
    assertSame([], $collected['missing'], 'nobody missing');
});

test('a student who is not in the class is rejected', function (): void {
    // The original defect: a hand-written POST could add rows for anyone.
    $post = [
        'courseCode'      => 'CSC109',
        'section'         => 'A',
        '2026-BCA-SDNT-1' => 'P',
        '2026-BCA-SDNT-2' => 'P',
        '2026-BCA-SDNT-3' => 'P',
        '2026-BCA-SDNT-9' => 'A',
    ];

    $collected = Attendance::collectMarks($post, roster());

    assertSame(['2026-BCA-SDNT-9'], $collected['unknown'], 'the outsider is named');
    assertFalse(isset($collected['marks']['2026-BCA-SDNT-9']), 'and is not recorded');
    assertSame(3, count($collected['marks']), 'the genuine marks survive');
});

test('a mark that is not P or A is rejected', function (): void {
    $post = [
        '2026-BCA-SDNT-1' => 'P',
        '2026-BCA-SDNT-2' => 'Z',
        '2026-BCA-SDNT-3' => 'present',
    ];

    $collected = Attendance::collectMarks($post, roster());

    assertSame(['2026-BCA-SDNT-2', '2026-BCA-SDNT-3'], $collected['invalid'], 'both bad marks are named');
    assertSame(1, count($collected['marks']), 'only the good one is kept');
});

test('every reserved form field is skipped', function (): void {
    // createdDate was missing from the correction page's exclusion list, so the
    // date was processed as though it were a student.
    $post = [
        'csrf_token'  => 'abc',
        'courseCode'  => 'CSC109',
        'semester'    => '1',
        'section'     => 'A',
        'createdDate' => '2026-09-05',
    ];

    $collected = Attendance::collectMarks($post, roster());

    assertSame([], $collected['marks'], 'no marks were read out of the form fields');
    assertSame([], $collected['unknown'], 'and none of them was mistaken for a student');
});

test('students left unmarked are reported', function (): void {
    $post = ['2026-BCA-SDNT-1' => 'P'];

    $collected = Attendance::collectMarks($post, roster());

    assertSame(
        ['2026-BCA-SDNT-2', '2026-BCA-SDNT-3'],
        $collected['missing'],
        'the two who were not marked'
    );
});

test('a student with an invalid mark counts as unmarked', function (): void {
    // Otherwise a rejected mark would leave a gap that nothing reported.
    $post = [
        '2026-BCA-SDNT-1' => 'P',
        '2026-BCA-SDNT-2' => 'Z',
        '2026-BCA-SDNT-3' => 'A',
    ];

    $collected = Attendance::collectMarks($post, roster());

    assertSame(['2026-BCA-SDNT-2'], $collected['missing'], 'rejecting a mark does not silently drop the student');
});

test('an empty submission for a real class marks nobody', function (): void {
    $collected = Attendance::collectMarks([], roster());

    assertSame([], $collected['marks'], 'nothing recorded');
    assertSame(roster(), $collected['missing'], 'everyone is outstanding');
});

test('a real date in the expected format is accepted', function (): void {
    assertTrue(Attendance::isValidDate('2026-09-05'), 'an ordinary date');
    assertTrue(Attendance::isValidDate('2024-02-29'), 'a leap day in a leap year');
});

test('a date that does not exist is refused', function (): void {
    // strtotime turns 2026-02-31 into the third of March without complaint.
    assertFalse(Attendance::isValidDate('2026-02-31'), 'the thirty-first of February');
    assertFalse(Attendance::isValidDate('2025-02-29'), 'a leap day in a common year');
    assertFalse(Attendance::isValidDate('2026-13-01'), 'a thirteenth month');
    assertFalse(Attendance::isValidDate('2026-00-10'), 'a zeroth month');
});

test('a date in another shape is refused', function (): void {
    assertFalse(Attendance::isValidDate('05-09-2026'), 'day first');
    assertFalse(Attendance::isValidDate('2026-9-5'), 'unpadded');
    assertFalse(Attendance::isValidDate('yesterday'), 'a phrase strtotime would accept');
    assertFalse(Attendance::isValidDate(''), 'nothing');
    assertFalse(Attendance::isValidDate(null), 'a missing field');
    assertFalse(Attendance::isValidDate("2026-09-05\n"), 'a trailing newline');
});

test('the first correction starts the history', function (): void {
    assertSame(
        '[2026-09-05 10:00:00]',
        Attendance::appendModification('', '2026-09-05 10:00:00'),
        'one entry'
    );
});

test('later corrections are appended', function (): void {
    $history = Attendance::appendModification('[2026-09-05 10:00:00]', '2026-09-06 11:30:00');

    assertSame('[2026-09-05 10:00:00], [2026-09-06 11:30:00]', $history, 'both entries, oldest first');
});

test('the history never outgrows its column', function (): void {
    // The defect: the old code appended without limit to a VARCHAR(500). Past
    // about twenty corrections the value no longer fitted, and in MySQL's
    // strict mode that error rolled back the whole submission — so a class
    // could reach a state where no correction could be saved at all.
    $history = '';

    for ($correction = 1; $correction <= 60; $correction++) {
        $history = Attendance::appendModification($history, '2026-09-05 10:00:00');

        assertTrue(
            strlen($history) <= Attendance::MODIFIED_DATES_LIMIT,
            'still fits after correction ' . $correction
        );
    }
});

test('the newest entries are the ones kept', function (): void {
    $history = '';

    for ($correction = 1; $correction <= 40; $correction++) {
        $stamp = sprintf('2026-09-05 10:%02d:00', $correction);
        $history = Attendance::appendModification($history, $stamp);
    }

    assertTrue(str_contains($history, '10:40:00'), 'the most recent correction is there');
    assertFalse(str_contains($history, '10:01:00'), 'the oldest has been dropped');
});

test('an entry is never cut in half', function (): void {
    // A half-written timestamp still looks like a record, which is worse than
    // no record.
    $history = '';

    for ($correction = 1; $correction <= 40; $correction++) {
        $history = Attendance::appendModification($history, '2026-09-05 10:00:00');
    }

    foreach (explode(', ', $history) as $entry) {
        assertMatches('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]$/', $entry, 'every entry is whole');
    }
});

test('a history already at the limit still accepts a correction', function (): void {
    $overlong = implode(', ', array_fill(0, 40, '[2026-09-05 10:00:00]'));

    assertTrue(strlen($overlong) > Attendance::MODIFIED_DATES_LIMIT, 'the starting value is too long');

    $history = Attendance::appendModification($overlong, '2026-09-06 09:00:00');

    assertTrue(strlen($history) <= Attendance::MODIFIED_DATES_LIMIT, 'the result fits');
    assertTrue(str_contains($history, '2026-09-06 09:00:00'), 'and the new correction is recorded');
});
