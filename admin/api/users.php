<?php
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
  }

  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Passwords.php';
  $conn = Database::establishConnection();

  header('Content-Type: application/json');
  $method = $_SERVER['REQUEST_METHOD'];

  if ($method === 'GET') {
    $result = $conn->query("SELECT uuid, role FROM users ORDER BY role DESC, uuid ASC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    $conn->close();
    echo json_encode($rows);
    exit;
  }

  if ($method === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true);
    $uuid     = $body['uuid']     ?? '';
    $password = $body['password'] ?? '';

    if (!is_string($uuid) || $uuid === '' || !is_string($password)) {
      http_response_code(400);
      echo json_encode(['error' => 'A user and a password are required.']);
      $conn->close();
      exit;
    }

    // The same rules the account creation path uses, rather than a bare length
    // check. In particular this refuses a password equal to the account's own
    // ID — the exact value teacher accounts used to be created with, and the
    // obvious thing to retype when resetting one by hand.
    $verdict = Passwords::check($password, $uuid);

    if (!$verdict['ok']) {
      http_response_code(400);
      echo json_encode(['error' => $verdict['error']]);
      $conn->close();
      exit;
    }

    $hash = Passwords::hash($password);
    $stmt = $conn->prepare("UPDATE users SET hashed_secret = ? WHERE uuid = ?");
    $stmt->bind_param('ss', $hash, $uuid);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();

    // An UPDATE that matches nothing is not an error in SQL, so a typo in the
    // user ID used to report success while changing no password at all. The
    // administrator would then hand out a password that does not work.
    if ($changed === 0) {
      $exists = $conn->prepare("SELECT 1 FROM users WHERE uuid = ?");
      $exists->bind_param('s', $uuid);
      $exists->execute();
      $found = $exists->get_result()->num_rows > 0;
      $exists->close();

      if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => "No account exists for $uuid."]);
        $conn->close();
        exit;
      }
    }

    $conn->close();
    echo json_encode(['success' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
?>
