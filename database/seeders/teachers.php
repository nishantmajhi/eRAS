<?php
    function createTeachersTable($conn) {
        $dbname = "eRAS";

        $sql = "USE $dbname";
        if (!$conn->query($sql)) {
            die("Error creating database: " . $conn->error);
        }
        echo "Database created successfully.";
        echo LINE_BREAK;
        
        $conn->select_db($dbname);
        
        $sql = "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id VARCHAR(50),
            teacher_name VARCHAR(100) NOT NULL,
            course_code VARCHAR(10) NOT NULL,
            section CHAR(1) NOT NULL,
            FOREIGN KEY (course_code) REFERENCES courses(course_code) ON UPDATE CASCADE
        )";
        if (!$conn->query($sql)) {
            die("Error creating teachers table: " . $conn->error);
        }
        echo "'teachers' table created successfully.";
        echo LINE_BREAK;
    }

    function insertTeachers($conn) {
        $teachersCourses = [
            "Ramesh Kumar Poudel" => ["CSC109", "CSC110"],
            "Naresh Bhattarai" => ["CSC111"],
            "Govinda Raj Shrestha" => ["MTH112", "PHY113"]
        ];

        $conn->begin_transaction();
        try {
            $teacherCount = 1;
            foreach ($teachersCourses as $name => $courses) {
                $teacherId = date('Y') . '-TCHR-' . $teacherCount;
                $hashedPassword = password_hash('teacher', PASSWORD_DEFAULT);

                $userSql = "INSERT INTO users (uuid, hashed_secret, role) 
                            VALUES ('$teacherId', '$hashedPassword', 'teacher')";
                if (!$conn->query($userSql)) {
                    echo "Error inserting teacher into users for $name: " . $conn->error;
                    echo LINE_BREAK;
                }

                foreach ($courses as $courseCode) {
                    foreach (['A', 'B'] as $section) {
                        $teacherSql = "INSERT INTO teachers (teacher_id, teacher_name, course_code, section) 
                                    VALUES ('$teacherId', '$name', '$courseCode', '$section')";
                        if (!$conn->query($teacherSql)) {
                            echo "Error inserting into teachers for $name - $courseCode section $section: " . $conn->error;
                            echo LINE_BREAK;
                        }
                    }
                }

                $teacherCount++;
            }

            $conn->commit();
            echo "Test teachers inserted successfully.";
            echo LINE_BREAK;
        } catch (Exception $e) {
            $conn->rollback();
            echo "Transaction failed: " . $e->getMessage();
            echo LINE_BREAK;
        }
    }
