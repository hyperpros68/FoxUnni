<?php
require_once __DIR__ . '/config/db_connect.php';

if ($db_connected) {
    try {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN payment_uid VARCHAR(100) NULL AFTER status");
        $pdo->exec("ALTER TABLE reservations ADD COLUMN amount DECIMAL(12,2) DEFAULT 0 AFTER payment_uid");
        $pdo->exec("ALTER TABLE reservations ADD COLUMN payment_status VARCHAR(50) NULL AFTER amount");
        echo "Table altered successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Columns already exist.\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "DB not connected.\n";
}
?>
