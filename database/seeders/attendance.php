<?php
    /**
     * The attendance table.
     *
     * This definition used to live inside attendance/new/index.php, which ran
     * CREATE TABLE IF NOT EXISTS on every submission. That put a schema change
     * in the request path — it needed DDL rights on the production account for
     * an operation that succeeds trivially every time after the first, and it
     * meant the table's shape was defined somewhere no one would look for it.
     * It belongs here with the other tables.
     */
    function createAttendanceTable($conn) {
        $dbname = "eRAS";

        $sql = "USE $dbname";
        if (!$conn->query($sql)) {
            die("Error selecting database: " . $conn->error);
        }

        $conn->select_db($dbname);

        $sql = "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(50) NOT NULL,
            course_code VARCHAR(20) NOT NULL,
            section CHAR(1) NOT NULL,
            created_date DATE NOT NULL,
            created_time TIME(6) NOT NULL,
            created_by VARCHAR(50) NOT NULL,
            status CHAR(1),
            modified_dates VARCHAR(500),
            UNIQUE KEY one_mark_per_student_per_day (student_id, course_code, section, created_date)
        )";
        if (!$conn->query($sql)) {
            die("Error creating attendance table: " . $conn->error);
        }
        echo "'attendance' table created successfully.";
        echo LINE_BREAK;
    }

    /**
     * Add the uniqueness constraint to a table that predates it.
     *
     * Nothing stopped the same student being marked twice for the same class on
     * the same day: the application checked first and then inserted, which two
     * simultaneous submissions both pass. The constraint makes the database
     * refuse the second one regardless.
     *
     * Adding it fails if duplicates already exist, which is the right outcome —
     * they have to be looked at rather than silently discarded — so the error is
     * reported rather than fatal.
     */
    function addAttendanceUniqueKey($conn) {
        $result = $conn->query(
            "SELECT COUNT(*) AS present FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'attendance'
               AND index_name = 'one_mark_per_student_per_day'"
        );

        if ($result && (int) $result->fetch_assoc()['present'] > 0) {
            echo "'attendance' already has the uniqueness constraint.";
            echo LINE_BREAK;
            return;
        }

        $sql = "ALTER TABLE attendance
                ADD UNIQUE KEY one_mark_per_student_per_day (student_id, course_code, section, created_date)";

        if (!$conn->query($sql)) {
            echo "Could not add the uniqueness constraint to 'attendance': " . $conn->error;
            echo LINE_BREAK;
            echo "Existing duplicate rows have to be resolved by hand first.";
            echo LINE_BREAK;
            return;
        }

        echo "Uniqueness constraint added to 'attendance'.";
        echo LINE_BREAK;
    }
