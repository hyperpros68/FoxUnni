<?php
require_once "config/db_connect.php";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NULL,
        author_name VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "community_comments table created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

