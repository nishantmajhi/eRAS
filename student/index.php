<?php
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] != 'student') {
    Redirect::requireSignIn(__DIR__);
  }

  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Status.php';

  $conn = Database::establishConnection();
  if ($conn->connect_error) {
    Status::error('Connection to database failed!');
  }

  if ($_SESSION['user_role'] == 'student') {
    $studentUUID = $_SESSION['uuid'];

    try {
      $sql = "SELECT students.student_id as ID, students.student_name as name,
                    courses.program_name as program, courses.semester_no as semester, students.section as section
              FROM students
              JOIN courses ON students.course_code = courses.course_code
              WHERE students.student_id = ? LIMIT 1";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param('s', $studentUUID);
      $stmt->execute();
      $result = $stmt->get_result();
      $studentData = $result->fetch_assoc();
      $stmt->close();

      // strtotime returns false for anything it cannot read, and date() then
      // treats that as the epoch: ?month=nonsense silently showed January 1970
      // rather than saying the month was not understood.
      $selectedMonth = date('Y-m');

      if (isset($_GET['month']) && is_string($_GET['month'])) {
        $requested = strtotime($_GET['month']);

        if ($requested === false) {
          Status::error('That is not a month.');
        }

        $selectedMonth = date('Y-m', $requested);
      }

      $attendanceSQL = "SELECT attendance.created_date as date, attendance.created_time as time,
                              attendance.course_code as course, attendance.status
                        FROM attendance
                        WHERE attendance.student_id = ? AND attendance.created_date LIKE CONCAT(?, '%')";

      $stmt = $conn->prepare($attendanceSQL);
      $stmt->bind_param('ss', $studentUUID, $selectedMonth);
      $stmt->execute();
      $result = $stmt->get_result();

      $attendanceRecords = [];
      while ($row = $result->fetch_assoc()) {
        $attendanceRecords[] = $row;
      }
      $stmt->close();
      $conn->close();

      $finalData = [
        'info' => $studentData,
        'attendance' => $attendanceRecords,
        'month' => $selectedMonth
      ];

      // Escaped for embedding in a <script> block. A student name containing
      // "</script>" would otherwise close the element early and everything
      // after it would be parsed as HTML.
      $JSON = json_encode(
        $finalData,
        JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
      );

      $html = str_replace('{{FULL_DATE}}', $selectedMonth, file_get_contents('dashboard.html'));
      $script = <<<HEREDOC
                  <script>
                    const attendanceData = $JSON;
                  </script>
                HEREDOC;

      echo str_replace('</body>', '</body>' . $script, $html);
    } catch (Throwable $e) {
      // rollback() was called here without a transaction ever having been
      // opened; nothing in this page writes. Reading only, there is nothing to
      // undo.
      Status::error('Attendance not found!');
    }
  }
