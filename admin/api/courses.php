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

    // section filter is applied via teachers/students linked to the course
    if ($section !== '') {
      $sql    = "SELECT DISTINCT c.course_code, c.course_title, c.program_name, c.semester_no
                 FROM courses c
                 WHERE (c.course_code LIKE ? OR c.course_title LIKE ?)
                   AND (
                     EXISTS (SELECT 1 FROM teachers t WHERE t.course_code = c.course_code AND t.section = ?)
                     OR EXISTS (SELECT 1 FROM students s WHERE s.course_code = c.course_code AND s.section = ?)
                   )";
      $params = [$search, $search, $section, $section];
      $types  = 'ssss';
    } else {
      $sql    = "SELECT course_code, course_title, program_name, semester_no FROM courses WHERE (course_code LIKE ? OR course_title LIKE ?)";
      $params = [$search, $search];
      $types  = 'ss';
    }

    if ($program) {
      $sql    .= " AND program_name = ?";
      $params[] = $program;
      $types   .= 's';
    }
    if ($sem) {
      $sql    .= " AND semester_no = ?";
      $params[] = (int)$sem;
      $types   .= 'i';
    }

    $sql .= " ORDER BY program_name, semester_no, course_code";

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
    $code         = strtoupper(trim($body['course_code']    ?? ''));
    $title        = trim($body['course_title']              ?? '');
    $program      = $body['program_name']                    ?? '';
    $sem          = (int)($body['semester_no']              ?? 0);
    $originalCode = $body['original_code']                   ?? null;

    if (!$code || !$title || !$program || !$sem) {
      http_response_code(400);
      echo json_encode(['error' => 'All fields are required.']);
      exit;
    }

    if ($originalCode) {
      // update — course_code is PK so only update title, program, semester
      $stmt = $conn->prepare("UPDATE courses SET course_title = ?, program_name = ?, semester_no = ? WHERE course_code = ?");
      $stmt->bind_param('ssis', $title, $program, $sem, $originalCode);
      $stmt->execute();
      $stmt->close();
    } else {
      // insert — check for duplicate code
      $check = $conn->prepare("SELECT course_code FROM courses WHERE course_code = ?");
      $check->bind_param('s', $code);
      $check->execute();
      if ($check->get_result()->num_rows) {
        http_response_code(400);
        echo json_encode(['error' => "Course code $code already exists."]);
        $check->close();
        $conn->close();
        exit;
      }
      $check->close();

      $stmt = $conn->prepare("INSERT INTO courses (course_code, course_title, program_name, semester_no) VALUES (?, ?, ?, ?)");
      $stmt->bind_param('sssi', $code, $title, $program, $sem);
      $stmt->execute();
      $stmt->close();
    }

    $conn->close();
    echo json_encode(['success' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
?>
