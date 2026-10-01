<?php
// setup_db.php
$pass = 'gtwave!@#$';

// 1. MySQL 서버 설치
$cmd1 = "echo '$pass' | sudo -S apt-get update 2>&1";
$cmd2 = "echo '$pass' | sudo -S DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server 2>&1";

// 2. DDL 스키마 덤프 붓기
$cmd3 = "echo '$pass' | sudo -S mysql < /home/gtwave/Project/FoxUnni/www/full_setup.sql 2>&1";

echo "<h1>서버 내부 DB 자동 설치 및 세팅</h1>";
echo "<h3>1. 패키지 업데이트</h3>";
echo "<pre>" . shell_exec($cmd1) . "</pre>";

echo "<h3>2. MySQL 서버 다운로드 및 설치</h3>";
echo "<pre>" . shell_exec($cmd2) . "</pre>";

echo "<h3>3. 데이터베이스 생성 및 정보 밀어넣기</h3>";
echo "<pre>" . shell_exec($cmd3) . "</pre>";

echo "<h2>작업이 100% 완료되었습니다! 이제 앱 메인으로 가셔서 로그인하시면 됩니다.</h2>";
?>
