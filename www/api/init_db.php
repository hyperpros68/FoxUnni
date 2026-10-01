<?php
require_once __DIR__ . '/../config/db_connect.php';

if ($db_connected) {
    echo "DB Connected.\n";

    // 비밀번호 해시
    $default_pw = password_hash('1234', PASSWORD_DEFAULT);

    // 이전 로컬스토리지 기반 테스트 계정 '카리나' 등 더미 데이터 삽입 (있다면 중복 방지)
    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, gender) VALUES (:email, :password, :name, :gender)");
        $stmt->execute(['email' => 'test@shinsa.com', 'password' => $default_pw, 'name' => '카리나', 'gender' => 'F']);
        echo "Inserted dummy user '카리나' (pw: 1234)\n";
    } catch (PDOException $e) {
        echo "'카리나' 이미 존재함.\n";
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, gender) VALUES (:email, :password, :name, :gender)");
        $stmt->execute(['email' => 'admin@shinsa.com', 'password' => $default_pw, 'name' => '신규회원', 'gender' => 'F']);
        echo "Inserted dummy user '신규회원' (pw: 1234)\n";
    } catch (PDOException $e) {
        echo "'신규회원' 이미 존재함.\n";
    }

} else {
    echo "DB Error.\n";
}
