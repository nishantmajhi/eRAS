<?php
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
  }

  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Passwords.php';
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'TeacherIds.php';
  $conn = Database::establishConnection();

  header('Content-Type: application/json');
  $method = $_SERVER['REQUEST_METHOD'];

  if ($method === 'GET') {
    $search  = '%' . ($_GET['search']  ?? '') . '%';
    $program = $_GET['program'] ?? '';
    $sem     = $_GET['sem']     ?? '';
    $section = $_GET['section'] ?? '';

    $sql = "SELECT t.id, t.teacher_id, t.teacher_name, t.course_code, t.section,
                   c.program_name, c.semester_no
            FROM teachers t
            JOIN courses c ON t.course_code = c.course_code
            WHERE (t.teacher_name LIKE ? OR t.teacher_id LIKE ?)";

    $params = [$search, $search];
    $types  = 'ss';

    if ($program) {
      $sql     .= " AND c.program_name = ?";
      $params[] = $program;
      $types   .= 's';
    }
    if ($sem) {
      $sql     .= " AND c.semester_no = ?";
      $params[] = (int)$sem;
      $types   .= 'i';
    }
    if ($section) {
      $sql     .= " AND t.section = ?";
      $params[] = $section;
      $types   .= 's';
    }

    $sql .= " ORDER BY t.teacher_id, t.course_code";

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
    $name    = trim($body['teacher_name'] ?? '');
    $course  = strtoupper(trim($body['course_code'] ?? ''));
    $section = $body['section'] ?? 'A';
    $id      = $body['id'] ?? null;

    // Filled in when a new account is created; returned once so the password
    // can be passed on. Initialised here so the response shape is the same
    // whether a row was created or updated.
    $createdAccounts = [];

    if (!$name || !$course) {
      http_response_code(400);
      echo json_encode(['error' => 'Name and course code are required.']);
      exit;
    }

    // verify course exists
    $check = $conn->prepare("SELECT course_code FROM courses WHERE course_code = ?");
    $check->bind_param('s', $course);
    $check->execute();
    if (!$check->get_result()->num_rows) {
      http_response_code(400);
      echo json_encode(['error' => "Course code $course does not exist."]);
      $check->close();
      $conn->close();
      exit;
    }
    $check->close();

    if ($id) {
      // update existing row
      $stmt = $conn->prepare("UPDATE teachers SET teacher_name = ?, course_code = ?, section = ? WHERE id = ?");
      $stmt->bind_param('sssi', $name, $course, $section, $id);
      $stmt->execute();
      $stmt->close();
    } else {
      // Allocate the next teacher_id. This previously selected teacher_id from
      // `users`, a table with no such column, so it threw and adding a teacher
      // never worked; and it ordered textually, which would have handed out a
      // duplicate once ten teachers existed. See utils/TeacherIds.php.
      $teacherId = TeacherIds::allocate($conn);

      // insert teacher row
      $stmt = $conn->prepare("INSERT INTO teachers (teacher_id, teacher_name, course_code, section) VALUES (?, ?, ?, ?)");
      $stmt->bind_param('ssss', $teacherId, $name, $course, $section);
      $stmt->execute();
      $stmt->close();

      // Create the user account with a random temporary password.
      //
      // This used to be password_hash($teacherId), making the account's
      // password its own ID — a value printed on staff lists, visible in URLs,
      // and sequential, so every teacher account was open to anyone who could
      // count. The generated password is returned once, below, for the
      // administrator to pass on.
      $temporaryPassword = Passwords::generateTemporary();
      $tempHash = Passwords::hash($temporaryPassword);
      $role = 'teacher';
      $stmt = $conn->prepare("INSERT INTO users (uuid, hashed_secret, role) VALUES (?, ?, ?)");
      $stmt->bind_param('sss', $teacherId, $tempHash, $role);
      $stmt->execute();
      $stmt->close();

      $createdAccounts[] = [
        'teacherId' => $teacherId,
        'temporaryPassword' => $temporaryPassword,
      ];
    }

    $conn->close();

    // The password is returned exactly once and is not stored anywhere in
    // recoverable form; only the hash is kept. If it is lost the account has to
    // be reset rather than looked up.
    echo json_encode(['success' => true, 'accounts' => $createdAccounts]);
    exit;
  }

  if ($method === 'DELETE') {
    $id = $body['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'ID required.']);
      exit;
    }

    // get teacher_id before deletion so we can remove the user account
    $res = $conn->prepare("SELECT teacher_id FROM teachers WHERE id = ?");
    $res->bind_param('i', $id);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    $res->close();

    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    // remove user account only if no more teacher rows for this teacher_id
    if ($row) {
      $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM teachers WHERE teacher_id = ?");
      $check->bind_param('s', $row['teacher_id']);
      $check->execute();
      $cnt = $check->get_result()->fetch_assoc()['cnt'];
      $check->close();

      // mysqli returns column values as strings, so a strict comparison against
      // the integer 0 was never true and the orphaned user account was never
      // removed.
      if ((int) $cnt === 0) {
        $del = $conn->prepare("DELETE FROM users WHERE uuid = ?");
        $del->bind_param('s', $row['teacher_id']);
        $del->execute();
        $del->close();
      }
    }

    $conn->close();
    echo json_encode(['success' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
?>
