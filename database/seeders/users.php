<?php
    function createUsersTable($conn) {
        $dbname = "eRAS";

        $sql = "USE $dbname";
        if (!$conn->query($sql)) {
            die("Error creating database: " . $conn->error);
        }
        echo "Database created successfully.";
        echo LINE_BREAK;

        $conn->select_db($dbname);
        
        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `uuid` VARCHAR(50) NOT NULL PRIMARY KEY,
            `hashed_secret` VARCHAR(255) NOT NULL,
            `role` ENUM('student', 'teacher', 'admin') NOT NULL
        )";
        if (!$conn->query($sql)) {
            die("Error creating users table: " . $conn->error);
        }
        echo "'users' table created successfully.";
        echo LINE_BREAK;
    }

    function insertUsers($conn) {
        $user = [
            'uuid' => '2025-ADMIN-0',
            'password' => 'superuser',
            'role' => 'admin'
        ];
         
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT IGNORE INTO `users` (`uuid`, `hashed_secret`, `role`) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $user['uuid'], $hashedPassword, $user['role']);
        if (!$stmt->execute()) {
            echo "Error initializing admin credentials.";
            echo LINE_BREAK;
        } else {
            echo "Admin credentials initialization complete.";
            echo LINE_BREAK;
        }
        
        $stmt->close();
    }
