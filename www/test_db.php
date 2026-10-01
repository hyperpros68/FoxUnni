<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'sinsa';
$user = 'sinsa';
$pass = 'sinsa1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "SUCCESS";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
