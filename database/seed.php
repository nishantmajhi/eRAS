<?php
  require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'utils' .  DIRECTORY_SEPARATOR. 'Database.php';

  $isCLI = (php_sapi_name() === 'cli' || defined('STDIN'));
  define("LINE_BREAK", $isCLI ? "\n" : "<br />");

  $conn = Database::establishConnection();
  if ($conn->connect_error) {
    echo str_replace(['{{STATUS}}', '{{MESSAGE}}'], ['ERROR ❌', 'Connection to database failed!'], file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'status.html'));
    exit;
  }
  echo "Database connection opened.";
  echo LINE_BREAK;

  $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
  echo "Foreign key checks disabled.";
  echo LINE_BREAK;

  require_once __DIR__ . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'courses.php';
  echo LINE_BREAK;
  createCoursesTable($conn);
  insertCourses($conn);

  require_once __DIR__ . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'users.php';
  echo LINE_BREAK;
  createUsersTable($conn);
  insertUsers($conn);

  require_once __DIR__ . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'students.php';
  echo LINE_BREAK;
  createStudentsTable($conn);
  insertStudents($conn);

  require_once __DIR__ . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'teachers.php';
  echo LINE_BREAK;
  createTeachersTable($conn);
  insertTeachers($conn);

  require_once __DIR__ . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'attendance.php';
  echo LINE_BREAK;
  createAttendanceTable($conn);
  addAttendanceUniqueKey($conn);

  echo LINE_BREAK;
  $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
  echo "Foreign key checks enabled.";  
  echo LINE_BREAK;

  $conn->close();
  echo "Database connection closed.";
  echo LINE_BREAK;
