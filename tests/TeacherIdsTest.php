<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'TeacherIds.php';

/*
 * The numbering rule for teacher identifiers.
 *
 * The defect these exist for: allocation used ORDER BY uuid DESC LIMIT 1, a
 * textual sort. "2026-TCHR-9" sorts after "2026-TCHR-10", so once ten teachers
 * existed the highest ID by that ordering was 9 and the next allocated would
 * have been 10 — a duplicate.
 */

test('the trailing number is read out of an identifier', function (): void {
    assertSame(4, TeacherIds::sequenceOf('2026-TCHR-4'), 'a single digit');
    assertSame(10, TeacherIds::sequenceOf('2026-TCHR-10'), 'two digits');
    assertSame(137, TeacherIds::sequenceOf('2026-TCHR-137'), 'three digits');
    assertSame(4, TeacherIds::sequenceOf('  2026-TCHR-4  '), 'surrounding space is ignored');
});

test('an identifier without a trailing number has no sequence', function (): void {
    assertNull(TeacherIds::sequenceOf(''), 'an empty string');
    assertNull(TeacherIds::sequenceOf('2026-TCHR-'), 'a missing number');
    assertNull(TeacherIds::sequenceOf('2026-TCHR-abc'), 'a non-numeric suffix');
    assertNull(TeacherIds::sequenceOf('admin'), 'an unrelated identifier');
});

test('the year is not mistaken for the sequence', function (): void {
    // Only the last segment is read, so changing the year does not restart or
    // inflate the count.
    assertSame(3, TeacherIds::sequenceOf('2025-TCHR-3'), 'last year');
    assertSame(3, TeacherIds::sequenceOf('2026-TCHR-3'), 'this year');
});

test('the next sequence is one past the highest, compared numerically', function (): void {
    // This is the exact case that the textual sort got wrong.
    $existing = [
        '2026-TCHR-1', '2026-TCHR-2', '2026-TCHR-3', '2026-TCHR-4', '2026-TCHR-5',
        '2026-TCHR-6', '2026-TCHR-7', '2026-TCHR-8', '2026-TCHR-9', '2026-TCHR-10',
    ];

    assertSame(11, TeacherIds::nextSequence($existing), 'ten exists, so eleven is next');
});

test('order of the existing identifiers does not matter', function (): void {
    assertSame(
        11,
        TeacherIds::nextSequence(['2026-TCHR-10', '2026-TCHR-3', '2026-TCHR-9']),
        'the highest wins whatever order they arrive in'
    );
});

test('the first teacher gets sequence one', function (): void {
    assertSame(1, TeacherIds::nextSequence([]), 'nothing exists yet');
});

test('a malformed identifier does not reset the count', function (): void {
    // One bad row would otherwise be read as zero and hand out a duplicate.
    assertSame(
        8,
        TeacherIds::nextSequence(['2026-TCHR-7', 'legacy-account', '2026-TCHR-']),
        'unnumbered identifiers are skipped, not counted as zero'
    );
});

test('non-string entries are ignored rather than fatal', function (): void {
    assertSame(
        6,
        TeacherIds::nextSequence(['2026-TCHR-5', null, 42]),
        'a null or integer row does not break allocation'
    );
});

test('identifiers are built in the documented shape', function (): void {
    assertSame('2026-TCHR-1', TeacherIds::format(2026, 1), 'the first of the year');
    assertSame('2026-TCHR-11', TeacherIds::format(2026, 11), 'a two-digit sequence');
    assertSame('2027-TCHR-1', TeacherIds::format(2027, 1), 'a later year');
});

test('formatting and parsing are inverses', function (): void {
    for ($sequence = 1; $sequence <= 25; $sequence++) {
        assertSame(
            $sequence,
            TeacherIds::sequenceOf(TeacherIds::format(2026, $sequence)),
            'sequence ' . $sequence . ' survives a round trip'
        );
    }
});

test('allocating repeatedly never repeats an identifier', function (): void {
    // Simulates the database by keeping the allocated IDs in an array and
    // asking for the next one each time — the property that matters is that
    // no ID is handed out twice, especially across the 9 to 10 boundary.
    $issued = [];

    for ($teacher = 0; $teacher < 30; $teacher++) {
        $id = TeacherIds::format(2026, TeacherIds::nextSequence($issued));

        assertFalse(in_array($id, $issued, true), $id . ' has not been issued before');

        $issued[] = $id;
    }
});
