<?php
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] != 'admin') {
    Redirect::requireSignIn(__DIR__);
  }

  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Status.php';
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  $conn = Database::establishConnection();
  if ($conn->connect_error) {
    Status::error('Connection to database failed!');
  }

  $stats = [];

  $res = $conn->query("SELECT COUNT(DISTINCT teacher_id) AS cnt FROM teachers");
  $stats['teachers'] = $res->fetch_assoc()['cnt'];

  $res = $conn->query("SELECT COUNT(DISTINCT student_id) AS cnt FROM students");
  $stats['students'] = $res->fetch_assoc()['cnt'];

  $res = $conn->query("SELECT COUNT(*) AS cnt FROM courses");
  $stats['courses'] = $res->fetch_assoc()['cnt'];

  $res = $conn->query("SELECT COUNT(*) AS cnt FROM users");
  $stats['users'] = $res->fetch_assoc()['cnt'];

  $conn->close();

  $JSON = json_encode($stats, JSON_PRETTY_PRINT);

  $html = file_get_contents('dashboard.html');
  $script = <<<HEREDOC
              <script>\n
                const adminStats = $JSON;\n
              </script>\n
            HEREDOC;

  echo str_replace('</body>', $script . '</body>', $html);
?>
