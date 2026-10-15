<?php
    function createCoursesTable($conn) {
        $dbname = "eRAS";

        $sql = "USE $dbname";
        if (!$conn->query($sql)) {
            die("Error creating database: " . $conn->error);
        }
        echo "Database created successfully.";
        echo LINE_BREAK;

        $conn->select_db($dbname);

        $sql = "CREATE TABLE IF NOT EXISTS courses (
            course_code VARCHAR(10) PRIMARY KEY,
            course_title VARCHAR(100) NOT NULL,
            program_name VARCHAR(50) NOT NULL,
            semester_no INT NOT NULL
        )";
        if (!$conn->query($sql)) {
            die("Error creating courses table: " . $conn->error);
        }
        echo "'courses' table created successfully.";
        echo LINE_BREAK;
    }

    function insertCourses($conn) {
        require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'courses.php';
        $academics = getAcademicsArray();

        $conn->begin_transaction();
        try {
            foreach ($academics as $degree) {
                $degreeName = $degree["program"];

                foreach ($degree["semesters"] as $semester) {
                    $semesterNo = $semester["no"];

                    foreach ($semester["courses"] as $course) {
                        $courseCode = $course["code"];
                        $courseTitle = $course["title"];

                        $stmt = $conn->prepare("INSERT INTO courses (course_code, course_title, program_name, semester_no) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("sssi", $courseCode, $courseTitle, $degreeName, $semesterNo);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $conn->commit();
            echo "Courses initialization complete.";
            echo LINE_BREAK;
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error: " . $e->getMessage(); 
            echo LINE_BREAK;
        }
    }
