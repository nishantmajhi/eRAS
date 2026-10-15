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

  // Return distinct programs and for each program the distinct semesters that
  // actually have at least one course.  The UI uses this to populate dropdowns
  // so the admin never sees an empty semester.
  $result = $conn->query(
    "SELECT program_name, semester_no
     FROM courses
     GROUP BY program_name, semester_no
     ORDER BY program_name, semester_no"
  );

  $programs = [];
  while ($row = $result->fetch_assoc()) {
    $p = $row['program_name'];
    $s = (int)$row['semester_no'];
    if (!isset($programs[$p])) {
      $programs[$p] = [];
    }
    $programs[$p][] = $s;
  }
  $conn->close();

  // Return as array of { name, semesters[] }
  $out = [];
  foreach ($programs as $name => $sems) {
    $out[] = ['name' => $name, 'semesters' => $sems];
  }

  echo json_encode($out);
?>
