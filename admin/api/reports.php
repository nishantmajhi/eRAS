<?php
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
  }

  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Attendance.php';
  $conn = Database::establishConnection();

  header('Content-Type: application/json');

  if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
  }

  $program = $_GET['program'] ?? '';
  $sem     = $_GET['sem']     ?? '';
  $section = $_GET['section'] ?? '';
  $from    = $_GET['from']    ?? '';
  $to      = $_GET['to']      ?? '';

  // A date the database cannot parse compares as NULL, which quietly excludes
  // every row: the report came back empty and looked like an answer.
  foreach (['from' => $from, 'to' => $to] as $field => $value) {
    if ($value !== '' && !Attendance::isValidDate($value)) {
      http_response_code(400);
      echo json_encode(['error' => "The $field date must be written as YYYY-MM-DD."]);
      $conn->close();
      exit;
    }
  }

  /*
   * This report has never produced a correct number.
   *
   * It was written against a guessed schema, and said so:
   *
   *     Expected attendance table schema (inferred from project):
   *       attendance(id, student_id, course_code, section, date,
   *                  status ENUM('present','absent'))
   *     Adjust column names if yours differ.
   *
   * They do differ. The real table — see database/seeders/attendance.php —
   * stores the date in `created_date`, not `date`, and the mark as CHAR(1)
   * holding 'P' or 'A', not the words 'present' and 'absent'. So
   * SUM(a.status = 'present') counted nothing and every student was reported at
   * zero per cent, and any request that set a date filter referred to a column
   * that does not exist, which since PHP 8.1 is an uncaught mysqli exception
   * and a 500.
   */
  $sql = "SELECT s.student_id, s.student_name, s.course_code, s.section,
                 SUM(a.status = 'P') AS present,
                 COUNT(a.id)         AS total
          FROM students s
          JOIN attendance a ON a.student_id = s.student_id
                           AND a.course_code = s.course_code
                           AND a.section     = s.section
          JOIN courses c ON c.course_code = s.course_code
          WHERE 1=1";

  $params = [];
  $types  = '';

  if ($program) {
    $sql    .= " AND c.program_name = ?";
    $params[] = $program;
    $types   .= 's';
  }
  if ($sem) {
    $sql    .= " AND c.semester_no = ?";
    $params[] = (int)$sem;
    $types   .= 'i';
  }
  if ($section) {
    $sql    .= " AND s.section = ?";
    $params[] = $section;
    $types   .= 's';
  }
  if ($from) {
    $sql    .= " AND a.created_date >= ?";
    $params[] = $from;
    $types   .= 's';
  }
  if ($to) {
    $sql    .= " AND a.created_date <= ?";
    $params[] = $to;
    $types   .= 's';
  }

  $sql .= " GROUP BY s.student_id, s.course_code, s.section
            ORDER BY s.student_id, s.course_code";

  $stmt = $conn->prepare($sql);
  if ($types) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  $rows = [];
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
  }
  $stmt->close();
  $conn->close();
  echo json_encode($rows);
?>
