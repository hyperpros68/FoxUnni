<?php
require_once __DIR__ . '/config/db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT NOT NULL,
        target_type VARCHAR(50) NOT NULL COMMENT 'community or review',
        target_id INT NOT NULL,
        reason VARCHAR(50) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Reports table created successfully.<br>";
    
    // Add user status column if not exists
    $sql_user = "SHOW COLUMNS FROM users LIKE 'status'";
    $stmt = $pdo->query($sql_user);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' COMMENT 'active, deleted'");
        echo "Column 'status' added to 'users' table.<br>";
    }
    
    echo "DB Update Complete!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
