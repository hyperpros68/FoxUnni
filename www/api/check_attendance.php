<?php
require_once "../config/db_connect.php";
session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["is_logged_in"])) {
    echo json_encode(["ok" => false, "msg" => "로그인이 필요합니다."]);
    exit;
}

$user_id = $_SESSION["user_id"];
$today = date("Y-m-d");
$points_to_give = 100;

try {
    // Check if already checked in today
    $stmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id = ? AND checked_date = ?");
    $stmt->execute([$user_id, $today]);
    
    if ($stmt->fetch()) {
        echo json_encode(["ok" => false, "msg" => "오늘은 이미 출석체크를 완료했습니다!"]);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Insert attendance log
    $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, checked_date) VALUES (?, ?)");
    $stmt->execute([$user_id, $today]);

    // 2. Insert point log
    $stmt = $pdo->prepare("INSERT INTO point_logs (user_id, amount, reason) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $points_to_give, "출석체크 보상"]);

    // 3. Update user total points
    $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->execute([$points_to_give, $user_id]);

    $pdo->commit();

    echo json_encode(["ok" => true, "msg" => "출석체크 완료! {$points_to_give}P가 지급되었습니다.", "amount" => $points_to_give]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["ok" => false, "msg" => "오류가 발생했습니다: " . $e->getMessage()]);
}

