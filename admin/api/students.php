<?php
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
  }

  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  $conn = Database::establishConnection();

  header('Content-Type: application/json');
  $method = $_SERVER['REQUEST_METHOD'];

  if ($method === 'GET') {
    $search  = '%' . ($_GET['search']  ?? '') . '%';
    $program = $_GET['program'] ?? '';
    $sem     = $_GET['sem']     ?? '';
    $section = $_GET['section'] ?? '';

    $sql = "SELECT s.id, s.student_id, s.student_name, s.course_code, s.section,
                   c.program_name, c.semester_no
            FROM students s
            JOIN courses c ON s.course_code = c.course_code
            WHERE (s.student_name LIKE ? OR s.student_id LIKE ?)";

    $params = [$search, $search];
    $types  = 'ss';

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

    $sql .= " ORDER BY s.student_id, s.course_code";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    $stmt->close();
    $conn->close();
    echo json_encode($rows);
    exit;
  }

  $body = json_decode(file_get_contents('php://input'), true);

  if ($method === 'POST') {
    $name    = trim($body['student_name'] ?? '');
    $course  = strtoupper(trim($body['course_code'] ?? ''));
    $section = $body['section'] ?? 'A';
    $id      = $body['id'] ?? null;

    if (!$name || !$course) {
      http_response_code(400);
      echo json_encode(['error' => 'Name and course code are required.']);
      exit;
    }

    // verify course exists
    $check = $conn->prepare("SELECT course_code, program_name FROM courses WHERE course_code = ?");
    $check->bind_param('s', $course);
    $check->execute();
    $courseRow = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$courseRow) {
      http_response_code(400);
      echo json_encode(['error' => "Course code $course does not exist."]);
      $conn->close();
      exit;
    }

    if ($id) {
      $stmt = $conn->prepare("UPDATE students SET student_name = ?, course_code = ?, section = ? WHERE id = ?");
      $stmt->bind_param('sssi', $name, $course, $section, $id);
      $stmt->execute();
      $stmt->close();
    } else {
      // generate student_id: YYYY-PROG-SDNT-N
      $prog = strtoupper(str_replace(' ', '', $courseRow['program_name']));
      $year = date('Y');
      // Prepared rather than interpolated. $prog is derived from a course's
      // program_name, which is stored in the database and edited through
      // api/courses.php, so a quote or a percent sign in a program name reached
      // this query intact — a broken query at best and a second-order injection
      // at worst.
      $res = $conn->prepare(
        "SELECT MAX(CAST(SUBSTRING_INDEX(student_id, '-', -1) AS UNSIGNED)) AS mx
         FROM students WHERE student_id LIKE ?"
      );
      $pattern = "$year-$prog-SDNT-%";
      $res->bind_param('s', $pattern);
      $res->execute();
      $max = $res->get_result()->fetch_assoc()['mx'] ?? 0;
      $res->close();
      $num  = intval($max) + 1;
      $studentId = "$year-$prog-SDNT-$num";

      $stmt = $conn->prepare("INSERT INTO students (student_id, student_name, course_code, section) VALUES (?, ?, ?, ?)");
      $stmt->bind_param('ssss', $studentId, $name, $course, $section);
      $stmt->execute();
      $stmt->close();
    }

    $conn->close();
    echo json_encode(['success' => true]);
    exit;
  }

  if ($method === 'DELETE') {
    $id = $body['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'ID required.']);
      exit;
    }
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
?>
