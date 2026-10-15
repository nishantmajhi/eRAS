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

  $stats = [];

  $res = $conn->query("SELECT COUNT(DISTINCT teacher_id) AS cnt FROM teachers");
  $stats['teachers'] = (int)$res->fetch_assoc()['cnt'];

  $res = $conn->query("SELECT COUNT(DISTINCT student_id) AS cnt FROM students");
  $stats['students'] = (int)$res->fetch_assoc()['cnt'];

  $res = $conn->query("SELECT COUNT(*) AS cnt FROM courses");
  $stats['courses'] = (int)$res->fetch_assoc()['cnt'];

  $res = $conn->query("SELECT COUNT(*) AS cnt FROM users");
  $stats['users'] = (int)$res->fetch_assoc()['cnt'];

  $conn->close();
  echo json_encode($stats);
?>
