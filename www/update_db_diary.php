<?php
require_once "config/db_connect.php";
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS recovery_diaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        surgery_name VARCHAR(100) NOT NULL,
        surgery_date DATE NOT NULL,
        record_date DATE NOT NULL,
        d_day INT NOT NULL,
        swelling_score INT NOT NULL DEFAULT 5,
        photo_url VARCHAR(255) NULL,
        memo TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_record (user_id, record_date),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "recovery_diaries table created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

