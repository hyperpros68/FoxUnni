<?php
// db_connect.php
// 데이터베이스 연결 설정 (MySQL)

// 환경(Environment) 분기 처리
// 실서버 배포 시 이 값을 'production'으로 변경하세요.
define('ENV', 'development'); 

if (ENV === 'production') {
    // [실서버 호스팅 DB 정보 기입란]
    $host = 'localhost';
    $dbname = '실서버_DB이름';
    $user = '실서버_DB아이디';
    $pass = '실서버_DB비밀번호';
} else {
    // [로컬 개발용 DB 정보]
    $host = 'localhost';
    $dbname = 'sinsa';
    $user = 'sinsa';
    $pass = 'sinsa1234';
}

$db_connected = false;
$pdo = null;

try {
    // MySQL 연결
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    // echo "DB Connection Failed: " . $e->getMessage() . "\n";
}
?>
