<?php
  require_once __DIR__ . DIRECTORY_SEPARATOR .'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();
  
  if (isset($_SESSION['uuid'])) {
    gotoDashboard($_SESSION['user_role']);
  }
  
  if ($_SERVER['REQUEST_METHOD'] === 'GET'){
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    echo str_replace('{{CSRF_TOKEN}}', $_SESSION['csrf_token'], file_get_contents('home.html'));
    exit;
  }
  
  require_once __DIR__ . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Status.php';
  require_once __DIR__ . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Redirect.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // hash_equals compares in constant time, and both operands are forced to
    // strings first: `!=` on a missing token compared null with a string, and
    // a loose comparison of two numeric-looking strings compares them as
    // numbers, so "0e123" and "0e456" would have matched.
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $expectedToken  = is_string($_SESSION['csrf_token'] ?? null) ? $_SESSION['csrf_token'] : '';

    if ($expectedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
      Status::error('CSRF token validation failed!');
    }

    require_once __DIR__ . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
    $conn = Database::establishConnection();
    if ($conn->connect_error) {
      Status::error('Connection to database failed!');
    }

    $uuid = $_POST['username'] ?: '';

    // Refuse further guesses at an account that has already had several wrong
    // ones. There was no limit at all before, which mattered a great deal while
    // teacher accounts had the teacher's own ID as their password: the IDs are
    // sequential, so an attacker could walk the staff list and try each ID as
    // its own password without anything slowing them down.
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'LoginThrottle.php';
    $throttle = new LoginThrottle();

    if ($uuid !== '' && $throttle->isLocked($uuid)) {
      showLoginError(
        'Too many failed attempts. Try again in '
        . LoginThrottle::describeWait($throttle->retryAfter($uuid)) . '.'
      );
    }

    if (stripos($uuid, 'SDNT') !== false) {
      $stmt = $conn->prepare("SELECT `student_id` FROM `students` WHERE `student_id` = ?");
      $stmt->bind_param('s', $uuid);
      $stmt->execute();
      $result = $stmt->get_result();

      // NOTE: students sign in with an ID and no password. The students table
      // has no password column, so this is a design decision rather than a
      // missing check — but it means anyone who knows or guesses a student ID
      // can read that student's attendance record, and the IDs are sequential.
      //
      // Changing it needs a schema change and a screen, so it is written up in
      // README.md and review.md rather than altered here. An ID that is not
      // found falls through to the users lookup below, where the throttle
      // records the failure, so at least guessing is slowed.
      if ($result->num_rows > 0) {
        $throttle->clear($uuid);

        session_regenerate_id(true);
        $_SESSION['uuid'] = $uuid;
        $_SESSION['user_role'] = 'student';
        $_SESSION['started_at'] = time();

        checkForRedirection();
        gotoDashboard('student');
      }
    }

    $pass = isset($_POST['password']) ? $_POST['password']: '';

    $stmt = $conn->prepare("SELECT `uuid`, `hashed_secret`, `role` FROM `users` WHERE `uuid` = ?");
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $result = $stmt->get_result();

    // Both failures below give the same message and take the same path. Saying
    // "Invalid username!" for one and "Invalid password!" for the other turned
    // this form into a way to discover which accounts exist, which is exactly
    // the first step of the attack the throttle above is defending against.
    if ($result->num_rows == 0) {
      $throttle->recordFailure($uuid);
      showLoginError('Invalid username or password.');
    }

    $row = $result->fetch_assoc();
    if (!password_verify($pass, $row['hashed_secret'])) {
      $throttle->recordFailure($uuid);
      showLoginError('Invalid username or password.');
    }

    $throttle->clear($uuid);

    session_regenerate_id(true);
    $_SESSION['uuid'] = $row['uuid'];
    $_SESSION['user_role'] = $row['role'];
    $_SESSION['started_at'] = time();

    checkForRedirection();
    gotoDashboard($row['role']);
  }

  /**
   * Re-render the sign-in page carrying a message, then stop.
   *
   * One function for every failure, so a new branch cannot accidentally
   * reintroduce a message that distinguishes a bad username from a bad
   * password.
   */
  function showLoginError(string $message): never {
    $errorDiv = '<div id="error_message">' . htmlspecialchars($message, ENT_QUOTES) . '</div>';
    $html = str_replace('{{CSRF_TOKEN}}', htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES), file_get_contents('home.html'));
    echo str_replace('</form>', $errorDiv . '</form>', $html);
    exit;
  }

  function gotoDashboard($role): void {
    switch ($role) {
      case 'admin':
        header('Location: ./admin/');
        exit();
      case 'teacher':
        header('Location: ./teacher/');
        exit();
      case 'student':
        header('Location: ./student/');
        exit();
      default:
        echo "ERROR 403: Unauthorized Access";
        exit();
    }
  }
