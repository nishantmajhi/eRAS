<?php
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] != 'teacher') {
    Redirect::requireSignIn(__DIR__);
  }

  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Status.php';
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Attendance.php';

  $conn = Database::establishConnection();
  if ($conn->connect_error) {
    Status::error('Connection to database failed!');
  }

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $createdOn  = (string) ($_GET['createdDate'] ?? '');
    $courseCode = (string) ($_GET['courseCode']  ?? '');
    $semester   = (string) ($_GET['semester']    ?? '');
    $section    = (string) ($_GET['section']     ?? '');

    requireOwnership($conn, $courseCode, $section);

    // strtotime accepts "2026-02-31" and turns it into March; a date that is not
    // a real date has to be refused rather than quietly moved.
    if (!Attendance::isValidDate($createdOn)) {
      Status::error('That is not a valid date.');
    }

    $stmt = $conn->prepare("SELECT `course_title` FROM `courses` WHERE `course_code` = ?");
    $stmt->bind_param('s', $courseCode);
    $stmt->execute();
    $stmt->bind_result($courseTitle);
    $stmt->fetch();
    $stmt->close();

    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (!Attendance::recordedOn($conn, $courseCode, $section, $createdOn)) {
      // The date came from the query string and was written into this message
      // unescaped, so a crafted link put markup into the page.
      $error = '<h1 style="margin-bottom: 0">⇱ Select Another Date⚠️</h1>';
      $description = '<p style="margin-top: 0">Attendance of <code>'
        . htmlspecialchars($createdOn, ENT_QUOTES) . '</code> was not saved!</p>';
      $html = preg_replace(
        ['/<h3\b[^>]*>.*?<\/h3>/is', '/<table\b[^>]*>.*?<\/table>/is'],
        ["", $error . "\n" . $description],
        file_get_contents('update.html'));

      $placeholders = ['{{FIRST_DAY}}', '{{CSRF_TOKEN}}', '{{CREATED_DATE}}', '{{COURSE_CODE}}', '{{SEMESTER_NO}}', '{{SECTION}}'];
      $replacements = escapeAll([$sixMonthsAgo, $_SESSION['csrf_token'], $createdOn, $courseCode, $semester, $section]);
      echo str_replace($placeholders, $replacements, $html);
      exit;
    }

    $stmt = $conn->prepare("SELECT MIN(created_date) as firstDay FROM attendance WHERE course_code = ? AND section = ? AND created_date >= ?");
    $stmt->bind_param("sss", $courseCode, $section, $sixMonthsAgo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $firstDay = $row['firstDay'] ? $row['firstDay'] : $sixMonthsAgo;
    $stmt->close();

    $placeholders = ['{{FIRST_DAY}}', '{{CSRF_TOKEN}}', '{{CREATED_DATE}}', '{{COURSE_TITLE}}', '{{COURSE_CODE}}', '{{SEMESTER_NO}}', '{{SECTION}}'];
    $replacements = escapeAll([$firstDay, $_SESSION['csrf_token'], $createdOn, $courseTitle, $courseCode, $semester, $section]);
    $html = str_replace($placeholders, $replacements, file_get_contents('update.html'));

    generateOldAttendance($conn, $createdOn, $courseCode, $section, $html);
  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    updateAttendance($conn);
  }

  /** Escape a list of values that are about to be substituted into HTML. */
  function escapeAll(array $values): array {
    return array_map(
      static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES),
      $values
    );
  }

  /**
   * Stop unless the signed-in teacher teaches this class.
   *
   * Without this, any teacher could rewrite any class's attendance by editing
   * the course code in the address bar or in the submitted form.
   */
  function requireOwnership($conn, string $courseCode, string $section): void {
    if ($courseCode === '' || $section === '') {
      Status::error('No class was selected.');
    }

    if (!Attendance::teaches($conn, (string) $_SESSION['uuid'], $courseCode, $section)) {
      http_response_code(403);
      Status::error('You do not teach this class.');
    }
  }

  function generateOldAttendance($conn, $createdOn, $courseCode, $section, $html): void {
    $stmt = $conn->prepare("SELECT DISTINCT students.student_id, students.student_name, attendance.status
                            FROM `attendance`
                            JOIN `students` ON attendance.student_id = students.student_id
                            WHERE attendance.created_date = ? AND attendance.course_code = ? AND attendance.section = ?
                            ORDER BY students.student_name ASC");
    $stmt->bind_param("sss", $createdOn, $courseCode, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    $count = 1;
    $attendanceTbody = '';

    while ($row = $result->fetch_assoc()) {
      // Administrator-entered values written straight into the page before.
      $name = htmlspecialchars((string) $row['student_name'], ENT_QUOTES);
      $id   = htmlspecialchars((string) $row['student_id'], ENT_QUOTES);

      $attendanceTbody .= '<tr>';
      $attendanceTbody .= '<td>' . $count . '</td>';
      $attendanceTbody .= '<td>' . $name . '</td>';
      $attendanceTbody .= '<td>';
      $attendanceTbody .= '<label>';
      $attendanceTbody .= '<input type="radio" name="' . $id . '" value="P" ' . ($row['status'] == 'P' ? 'checked' : '') . ' /> Present';
      $attendanceTbody .= '</label>';
      $attendanceTbody .= '<label>';
      $attendanceTbody .= '<input type="radio" name="' . $id . '" value="A" ' . ($row['status'] == 'A' ? 'checked' : '') . ' /> Absent';
      $attendanceTbody .= '</label>';
      $attendanceTbody .= '</td>';
      $attendanceTbody .= '</tr>';
      $count++;
    }

    $stmt->close();
    $conn->close();
    echo str_replace('<tbody></tbody>', "<tbody>$attendanceTbody</tbody>", $html);
  }

  function updateAttendance($conn): void {
    $submitted = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $expected  = is_string($_SESSION['csrf_token'] ?? null) ? $_SESSION['csrf_token'] : '';

    if ($expected === '' || !hash_equals($expected, $submitted)) {
      Status::error('CSRF token validation failed!');
    }

    $courseCode = (string) ($_POST['courseCode'] ?? '');
    $section    = (string) ($_POST['section']    ?? '');
    $createdOn  = (string) ($_POST['createdDate'] ?? '');

    // The GET check only chose which page to render; the form body is a
    // separate request carrying its own course code.
    requireOwnership($conn, $courseCode, $section);

    if (!Attendance::isValidDate($createdOn)) {
      Status::error('That is not a valid date.');
    }

    $roster = Attendance::roster($conn, $courseCode, $section);

    // Only marks for students in this class, and only P or A. The old loop read
    // every unrecognised POST field as a student ID — including 'createdDate',
    // which was missing from its exclusion list.
    $collected = Attendance::collectMarks($_POST, $roster);

    if ($collected['unknown'] !== [] || $collected['invalid'] !== []) {
      Status::error('The submitted attendance did not match this class.');
    }

    $conn->begin_transaction();
    try {
      $selectStmt = $conn->prepare("SELECT status, modified_dates FROM attendance WHERE student_id = ? AND course_code = ? AND section = ? AND created_date = ?");
      $updateStmt = $conn->prepare("UPDATE attendance SET status = ?, modified_dates = ? WHERE student_id = ? AND course_code = ? AND section = ? AND created_date = ?");

      foreach ($collected['marks'] as $studentID => $status) {
        $selectStmt->bind_param('ssss', $studentID, $courseCode, $section, $createdOn);
        $selectStmt->execute();
        $existing = $selectStmt->get_result()->fetch_assoc();

        // Nothing was recorded for this student on this date, so there is
        // nothing to correct. Inserting here would create a row the "new"
        // page's duplicate check knows nothing about.
        if ($existing === null) {
          continue;
        }

        if ($status === ($existing['status'] ?? null)) {
          continue;
        }

        // modified_dates is VARCHAR(500); appending without limit eventually
        // overflowed it, and in strict mode that error rolled back the whole
        // correction.
        $newModifiedDates = Attendance::appendModification(
          (string) ($existing['modified_dates'] ?? ''),
          date('Y-m-d H:i:s')
        );

        $updateStmt->bind_param('ssssss', $status, $newModifiedDates, $studentID, $courseCode, $section, $createdOn);
        $updateStmt->execute();
      }

      $selectStmt->close();
      $updateStmt->close();

      $conn->commit();
      $conn->close();

      Status::success('Attendance updated successfully!');
    } catch (Throwable $e) {
      $conn->rollback();
      Status::error('Could not update attendance!');
    }
  }
