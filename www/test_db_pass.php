<?php
$host = 'localhost';
$dbname = 'shinsa_db'; // 없을수도 있으니 제거하고 mysql로 테스트
$user = 'root'; 

$passwords = ['', 'root', '1234', 'apmsetup', 'autoset', '1111', '123456', 'admin', 'password'];

foreach ($passwords as $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        echo "SUCCESS with password: '$pass'\n";
        
        // Ensure shinsa_db exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS shinsa_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        $pdo->exec("USE shinsa_db;");
        
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(50) NOT NULL,
            gender CHAR(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "Table 'users' verified/created.\n";
        exit;
    } catch (PDOException $e) {
        // failed
    }
}
echo "ALL FAILED.\n";
