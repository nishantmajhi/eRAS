<?php
    function createStudentsTable($conn) {
        $dbname = "eRAS";

        $sql = "USE $dbname";
        if (!$conn->query($sql)) {
            die("Error creating database: " . $conn->error);
        }
        echo "Database created successfully.";
        echo LINE_BREAK;
        
        $conn->select_db($dbname);
        
        $sql = "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(50),
            student_name VARCHAR(100) NOT NULL,
            course_code VARCHAR(10) NOT NULL,
            section CHAR(1) NOT NULL,
            FOREIGN KEY (course_code) REFERENCES courses(course_code) ON UPDATE CASCADE
        )";
        if (!$conn->query($sql)) {
            die("Error creating students table: " . $conn->error);
        }
        echo "'students' table created successfully.";
        echo LINE_BREAK;
    }

    function insertStudents($conn) {
        require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'students.php';
        $namesArray = getStudentsArray();

        $courseCodes = ["CSC109", "CSC110", "CSC111", "MTH112", "PHY113"];
        $programName = "CSIT";

        $halfPoint = floor(count($namesArray) / 2);

        $conn->begin_transaction();
        try {
            for ($i = 0; $i < count($namesArray); $i++) {
                $studentCount = $i + 1;
                $studentId = date('Y') . '-' . $programName . '-SDNT-' . $studentCount;
                $name = $namesArray[$i];
                $section = ($i < $halfPoint) ? 'A' : 'B';

                foreach ($courseCodes as $courseCode) {
                    $stmt = $conn->prepare("INSERT INTO students (student_id, student_name, course_code, section) 
                                            VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $studentId, $name, $courseCode, $section);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();
            echo "Test students initialization complete.";
            echo LINE_BREAK;
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error: " . $e->getMessage();
            echo LINE_BREAK;
        }
    }
