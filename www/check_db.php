<?php
require_once 'config/db_connect.php';
$stmt = $pdo->query("DESCRIBE hospitals");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
