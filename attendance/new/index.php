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
    $courseCode = (string) ($_GET['courseCode'] ?? '');
    $semester   = (string) ($_GET['semester']   ?? '');
    $section    = (string) ($_GET['section']    ?? '');

    requireOwnership($conn, $courseCode, $section);

    $date = date('Y-m-d');

    // A second submission for the same class on the same day would create a
    // duplicate set of rows rather than replace the first, so the page refuses
    // to open. The same check runs again inside the transaction below: two
    // browser tabs opened before either was submitted would both pass this one.
    if (Attendance::recordedOn($conn, $courseCode, $section, $date)) {
      Status::warning('Attendance Exists', 'Use attendance correction option to make changes!');
    }

    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $stmt = $conn->prepare("SELECT `course_title` FROM `courses` WHERE `course_code` = ?");
    $stmt->bind_param('s', $courseCode);
    $stmt->execute();
    $stmt->bind_result($courseTitle);
    $stmt->fetch();
    $stmt->close();

    // Everything below reaches the browser as HTML. courseCode, semester and
    // section come straight from the query string, and course_title from a
    // field an administrator types, so all of them are escaped: previously a
    // link with markup in ?section= put that markup into the page.
    $placeholders = ['{{CSRF_TOKEN}}', '{{COURSE_TITLE}}', '{{COURSE_CODE}}', '{{SEMESTER_NO}}', '{{SECTION}}'];
    $replacements = array_map(
      static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES),
      [$_SESSION['csrf_token'], $courseTitle, $courseCode, $semester, $section]
    );
    $html = str_replace($placeholders, $replacements, file_get_contents('new.html'));

    generateAttendancePage($conn, $courseCode, $section, $html);
  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processAttendance($conn);
  }

  /**
   * Stop unless the signed-in teacher teaches this class.
   *
   * Course and section arrive from the request, so without this any teacher
   * could record attendance for any class by editing the address bar.
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

  function generateAttendancePage($conn, $courseCode, $section, $html): void {
    $stmt = $conn->prepare("SELECT `student_id`, `student_name` FROM `students` WHERE `course_code` = ? AND `section` = ? ORDER BY `student_name` ASC");
    $stmt->bind_param('ss', $courseCode, $section);
    $stmt->execute();

    $result = $stmt->get_result();

    $count = 1;
    $tableRows = '';

    while ($row = $result->fetch_assoc()) {
      // Student names and IDs are administrator-entered and were written into
      // the page unescaped; a name containing a quote broke the radio buttons,
      // and one containing a tag ran as markup.
      $name = htmlspecialchars((string) $row['student_name'], ENT_QUOTES);
      $id   = htmlspecialchars((string) $row['student_id'], ENT_QUOTES);

      $tableRows .= <<<HEREDOC
          <tr>
            <td>{$count}</td>
            <td>{$name}</td>
            <td>
              <label>
                <input type="radio" name="{$id}" value="P" checked />
                  Present
                </label>
                <label>
                  <input type="radio" name="{$id}" value="A" />
                    Absent
                </label>
              </td>
          </tr>
        HEREDOC;
      $count++;
    }

    $stmt->close();
    $conn->close();
    echo str_replace('<tbody></tbody>', "<tbody>$tableRows</tbody>", $html);
  }

  function processAttendance($conn): void {
    // hash_equals compares in constant time and tolerates a missing field.
    $submitted = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $expected  = is_string($_SESSION['csrf_token'] ?? null) ? $_SESSION['csrf_token'] : '';

    if ($expected === '' || !hash_equals($expected, $submitted)) {
      Status::error('CSRF token validation failed!');
    }

    $courseCode = (string) ($_POST['courseCode'] ?? '');
    $section    = (string) ($_POST['section']    ?? '');
    $date       = date('Y-m-d');
    $time       = date('H:i:s');

    // The ownership check has to run again here. The GET check only decided
    // which page to render; the form body is a separate request and carries its
    // own course code, which the browser is free to change.
    requireOwnership($conn, $courseCode, $section);

    $roster = Attendance::roster($conn, $courseCode, $section);

    if ($roster === []) {
      Status::error('This class has no students enrolled.');
    }

    // Only fields naming a student in this class, marked P or A, are accepted.
    // The old loop took every unrecognised POST field as a student ID and its
    // value as the mark.
    $collected = Attendance::collectMarks($_POST, $roster);

    if ($collected['unknown'] !== [] || $collected['invalid'] !== []) {
      Status::error('The submitted attendance did not match this class.');
    }

    if ($collected['missing'] !== []) {
      Status::error('Attendance was not recorded for every student in the class.');
    }

    $conn->begin_transaction();
    try {
      // Checked inside the transaction so two tabs opened before either was
      // submitted cannot both insert a set of rows for the same day.
      if (Attendance::recordedOn($conn, $courseCode, $section, $date)) {
        $conn->rollback();
        Status::warning('Attendance Exists', 'Use attendance correction option to make changes!');
      }

      $stmt = $conn->prepare("INSERT INTO attendance (student_id, course_code, section, created_date, created_time, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $recordedBy = (string) $_SESSION['uuid'];

      foreach ($collected['marks'] as $studentID => $status) {
        $stmt->bind_param('sssssss', $studentID, $courseCode, $section, $date, $time, $recordedBy, $status);
        $stmt->execute();
      }

      $stmt->close();

      $conn->commit();
      $conn->close();

      Status::success('Attendance added successfully!');
    } catch (Throwable $e) {
      $conn->rollback();
      Status::error('Could not create attendance!');
    }
  }
