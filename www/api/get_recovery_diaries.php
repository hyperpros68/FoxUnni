<?php
session_start();
require_once "../config/db_connect.php";
header("Content-Type: application/json; charset=utf-8");

$user_id = $_SESSION["user_id"] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "error" => "로그인이 필요합니다."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM recovery_diaries WHERE user_id = ? ORDER BY record_date DESC");
    $stmt->execute([$user_id]);
    $diaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $diaries]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

