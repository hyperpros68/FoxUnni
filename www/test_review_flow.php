<?php
session_start();
require_once 'config/db_connect.php';

// Create a test user
$pdo->exec("INSERT IGNORE INTO users (id, email, password, name, gender, points) VALUES (10, 'test@test.com', '1234', '테스트유저', 'F', 0)");

// Simulate save_review.php (mocking the POST request and session)
$_SESSION['user_id'] = 10;
$_SESSION['user_name'] = '테스트유저';
$_POST['hospital_name'] = '테스트 성형외과';
$_POST['event_id'] = 1;
$_POST['event_title'] = '코성형 특가';
$_POST['rating'] = 5;
$_POST['content'] = '정말 친절하고 좋았습니다. 붓기도 금방 빠졌어요!';
$_SERVER['REQUEST_METHOD'] = 'POST';
// include it to trigger the logic
ob_start();
require_once 'api/save_review.php';
$response = ob_get_clean();

echo "Response from API: " . $response . "\n";

// Check points
$stmt = $pdo->query("SELECT points FROM users WHERE id = 10");
$user = $stmt->fetch();
echo "User points after review: " . $user['points'] . "\n";
