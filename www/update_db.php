<?php
require_once 'config/db_connect.php';
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN is_receipt_verified TINYINT(1) DEFAULT 0");
    echo 'success';
} catch (Exception $e) {
    echo $e->getMessage();
}
