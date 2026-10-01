<?php
$host = 'localhost';
$dbname = 'shinsa_db';
$user = 'root'; // 로컬 환경의 MySQL 기본 계정
$pass = ''; // 로컬 환경 비밀번호 (필요 시 수정)
$pass2 = 'root'; // APMSETUP 등 다른 기본 비번
$pass3 = '1234';

$pdo = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    echo "DB Connection Success with empty password.\n";
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass2);
        echo "DB Connection Success with password 'root'.\n";
    } catch (PDOException $e2) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass3);
            echo "DB Connection Success with password '1234'.\n";
        } catch (PDOException $e3) {
            try {
                $pass4 = 'apmsetup';
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass4);
                echo "DB Connection Success with password 'apmsetup'.\n";
            } catch (PDOException $e4) {
                echo "DB Connection Failed: " . $e4->getMessage() . "\n";
                exit;
            }
        }
    }
}

// Check users table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if($stmt->rowCount() > 0) {
        echo "Table 'users' exists.\n";
    } else {
        echo "Table 'users' DOES NOT exist.\n";
        
        // Create table
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(50) NOT NULL,
            gender CHAR(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "Table 'users' created successfully.\n";
    }
} catch(PDOException $e) {
    echo "Error checking/creating table: " . $e->getMessage() . "\n";
}
