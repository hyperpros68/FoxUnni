<?php
require_once __DIR__ . '/config/db_connect.php';

if (!$db_connected) {
    die("DB Connection failed");
}

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_room_id VARCHAR(50) NOT NULL,
        sender_type VARCHAR(20) NOT NULL, -- 'user' or 'hospital'
        sender_id INT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (chat_room_id),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "chat_messages table created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
