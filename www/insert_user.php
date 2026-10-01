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
    
    $email = 'kari0817@naver.com';
    $password = 'kari0817!!';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "ALREADY_EXISTS";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, gender) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hashed, '카리', 'F']);
        echo "SUCCESS";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
