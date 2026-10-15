<?php
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'Session.php';
  Session::init();

  if (!isset($_SESSION['uuid']) || $_SESSION['user_role'] != 'teacher') {
    Redirect::requireSignIn(__DIR__);
  }
  
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Status.php';
  require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'Database.php';
  $conn = Database::establishConnection();
  if ($conn->connect_error) {
    Status::error('Connection to database failed!');
  }

  if ($_SESSION['user_role'] == 'teacher') {
    $teacherUUID = $_SESSION['uuid'];
    
    $sql = "SELECT teachers.course_code, courses.program_name, teachers.section, courses.semester_no
            FROM teachers
            JOIN courses ON teachers.course_code = courses.course_code
            WHERE teachers.teacher_id = ?";
      
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $teacherUUID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $conn->close();
      
    $periods = [];
    while ($row = $result->fetch_assoc()) {
      $periods[] = $row;
    }
    // Escaped for embedding in a <script> block. A program name containing
    // "</script>" would otherwise close the element early and everything after
    // it would be parsed as HTML; JSON_HEX_TAG and its companions encode the
    // characters that can do that.
    $JSON = json_encode(
      $periods,
      JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    
    $html = file_get_contents('dashboard.html');
    $script = <<<HEREDOC
                <script>\n
                  const periods = $JSON;\n
                </script>\n
              HEREDOC;

    echo str_replace('</body>', '</body>' . $script, $html);
  }
