<?php
session_start();
require_once "../config/db_connect.php";
header("Content-Type: application/json; charset=utf-8");

$user_id = $_SESSION["user_id"] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "error" => "로그인이 필요합니다."]);
    exit;
}

$surgery_name = $_POST["surgery_name"] ?? "눈매교정 + 자연유착";
$surgery_date = $_POST["surgery_date"] ?? date("Y-m-d", strtotime("-4 days"));
$record_date = date("Y-m-d");
$d_day = floor((strtotime($record_date) - strtotime($surgery_date)) / 86400);
$swelling_score = (int)($_POST["swelling_score"] ?? 5);
$memo = $_POST["memo"] ?? "";

$photo_url = null;
if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
    $upload_dir = "../uploads/diaries/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "gif", "webp"];
    if (in_array($ext, $allowed)) {
        $filename = "diary_" . $user_id . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $filename)) {
            $photo_url = "/uploads/diaries/" . $filename;
        }
    }
}

try {
    // Insert or Update (ON DUPLICATE KEY UPDATE)
    $stmt = $pdo->prepare("
        INSERT INTO recovery_diaries (user_id, surgery_name, surgery_date, record_date, d_day, swelling_score, photo_url, memo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            swelling_score = VALUES(swelling_score),
            photo_url = COALESCE(VALUES(photo_url), photo_url),
            memo = VALUES(memo)
    ");
    $stmt->execute([
        $user_id, $surgery_name, $surgery_date, $record_date, $d_day, $swelling_score, $photo_url, $memo
    ]);
    
    echo json_encode(["success" => true, "msg" => "기록이 저장되었습니다.", "d_day" => $d_day]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

