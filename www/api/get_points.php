<?php
require_once "../config/db_connect.php";
session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["is_logged_in"])) {
    echo json_encode(["ok" => false, "msg" => "로그인이 필요합니다."]);
    exit;
}

$user_id = $_SESSION["user_id"];

try {
    // 1. Get total points
    $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["ok" => false, "msg" => "사용자를 찾을 수 없습니다."]);
        exit;
    }
    
    $total_points = (int)$user["points"];

    // 2. Get point history
    $stmt = $pdo->prepare("SELECT amount, reason as title, created_at as datetime FROM point_logs WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "total" => $total_points, "history" => $history]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "msg" => "오류가 발생했습니다: " . $e->getMessage()]);
}

