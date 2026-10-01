<?php
require_once "../config/db_connect.php";
session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["is_logged_in"])) {
    echo json_encode(["ok" => false, "msg" => "로그인이 필요합니다."]);
    exit;
}

$post_id = $_POST["post_id"] ?? null;
$content = $_POST["content"] ?? "";
$user_id = $_SESSION["user_id"] ?? null;
$author_name = $_SESSION["user_name"] ?? "익명";

if (!$post_id || empty(trim($content))) {
    echo json_encode(["ok" => false, "msg" => "내용을 입력해주세요."]);
    exit;
}

try {
    // 1. Save comment
    $stmt = $pdo->prepare("INSERT INTO community_comments (post_id, user_id, author_name, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $author_name, $content]);

    // 2. Increase comment count in community_posts
    $stmt2 = $pdo->prepare("UPDATE community_posts SET comments = comments + 1 WHERE id = ?");
    $stmt2->execute([$post_id]);

    echo json_encode(["ok" => true, "msg" => "댓글이 등록되었습니다."]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "msg" => "오류가 발생했습니다: " . $e->getMessage()]);
}

