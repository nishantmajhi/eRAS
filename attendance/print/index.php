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

    $createdOn = (string) ($_GET['createdDate'] ?? '');
    $courseCode = (string) ($_GET['courseCode'] ?? '');
    $semester = (string) ($_GET['semester'] ?? '');
    $section = (string) ($_GET['section'] ?? '');

    // A register is a list of named students and who was absent. Nothing here
    // checked that the teacher asking for it teaches the class, so any teacher
    // could print any class's register by editing the query string.
    if ($courseCode === '' || $section === '') {
        Status::error('No class was selected.');
    }

    if (!Attendance::teaches($conn, (string) $_SESSION['uuid'], $courseCode, $section)) {
        http_response_code(403);
        Status::error('You do not teach this class.');
    }

    if (!Attendance::isValidDate($createdOn)) {
        Status::error('That is not a valid date.');
    }

    $stmt = $conn->prepare("SELECT `program_name`, `course_title` FROM `courses` WHERE `course_code` = ?");
    $stmt->bind_param("s", $courseCode);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // A course code with no matching row left $row null and every read of it
    // raised a warning before rendering a page with empty headings.
    if ($row === null) {
        Status::error('That course does not exist.');
    }

    $programName = $row['program_name'];
    $courseTitle = $row['course_title'];

    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    try {
        $checkStmt = $conn->prepare("SELECT 1 FROM `attendance` WHERE `course_code` = ? AND `section` = ? AND `created_date` = ? LIMIT 1");
        $checkStmt->bind_param('sss', $courseCode, $section, $createdOn);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows == 0) {
            $updateJS = str_replace("update", "print", file_get_contents(updatePath('update.js')));
            $updateCSS = file_get_contents(updatePath('update.css'));
            $updateHTML = str_replace(
                ['<script src="./update.js"></script>', "</head>", "</html>"], 
                ["", "<style>\n".$updateCSS."</style>\n</head>", "<script>\n".$updateJS."</script>\n</html>"], 
                file_get_contents(updatePath('update.html')));

            $error = '<h1 style="margin-bottom: 0">⇱ Select Another Date⚠️</h1>';
            $description = '<p style="margin-top: 0">Attendance of <code>'
                . htmlspecialchars($createdOn, ENT_QUOTES) . '</code> was not saved!</p>';
            $html = preg_replace(
                ['/<h3\b[^>]*>.*?<\/h3>/is', '/<table\b[^>]*>.*?<\/table>/is'], 
                ["", $error . "\n" . $description], 
                $updateHTML
            );

            $placeholders = ['{{FIRST_DAY}}', '{{CSRF_TOKEN}}', '{{CREATED_DATE}}', '{{COURSE_CODE}}', '{{SEMESTER_NO}}', '{{SECTION}}'];
            $replacements = escapeAll([$sixMonthsAgo, $_SESSION['csrf_token'], $createdOn, $courseCode, $semester, $section]);

            echo str_replace($placeholders, $replacements, $html);
            exit;
        }
        
        $checkStmt->close();
    } catch (Exception $e) {
        echo str_replace(['{{STATUS}}', '{{MESSAGE}}'], ['ERROR ❌', 'Internal server error! Please contact your administrator.'], file_get_contents(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'status.html'));
        exit;
    }

    $stmt = $conn->prepare("SELECT MIN(created_date) as firstDay FROM attendance WHERE course_code = ? AND section = ? AND created_date >= ?");
    $stmt->bind_param("sss", $courseCode, $section, $sixMonthsAgo);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $firstDay = $row['firstDay'] ? $row['firstDay'] : $sixMonthsAgo;
    $stmt->close();

    $today = date('Y-m-d');

    $stmt = $conn->prepare("SELECT DISTINCT students.student_id, students.student_name, attendance.status 
                            FROM `attendance` 
                            JOIN `students` ON attendance.student_id = students.student_id 
                            WHERE attendance.created_date = ? AND attendance.course_code = ? AND attendance.section = ? 
                            ORDER BY students.student_name ASC");
    $stmt->bind_param("sss", $createdOn, $courseCode, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    $attendanceTbody = '';
    $count = 1;
    while ($row = $result->fetch_assoc()) {
        $attendanceTbody .= '<tr>';
        $attendanceTbody .= '<td>' . $count . '</td>';
        $attendanceTbody .= '<td>' . htmlspecialchars((string) $row['student_name'], ENT_QUOTES) . '</td>';
        $attendanceTbody .= '<td>' . ($row['status'] == 'P' ? '✓' : '∙') . '</td>';
        $attendanceTbody .= '</tr>';
        $count++;
    }

    $stmt->close();
    $conn->close();

    $placeholders = ['{{FIRST_DAY}}', '{{TODAY}}', '{{CREATED_ON}}', '{{COURSE_CODE}}', '{{PROGRAM}}', '{{COURSE_TITLE}}', '{{SEMESTER_NO}}', '{{SECTION}}', '{{ATTENDANCE_TBODY}}'];

    // Every value except the table body is text going into HTML; the table body
    // is markup this file built, and its student names were escaped as they
    // were added.
    $replacements = escapeAll([$firstDay, $today, $createdOn, $courseCode, $programName, $courseTitle, $semester, $section]);
    $replacements[] = $attendanceTbody;

    echo str_replace($placeholders, $replacements, file_get_contents('print.html'));

    function updatePath($file): string {
        return implode(DIRECTORY_SEPARATOR, ['..', 'update', $file]);
    }

    /** Escape a list of values that are about to be substituted into HTML. */
    function escapeAll(array $values): array {
        return array_map(
            static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES),
            $values
        );
    }
