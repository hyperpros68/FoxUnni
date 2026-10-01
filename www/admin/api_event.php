<?php
// www/admin/api_event.php
require_once '../config/db_connect.php';

if (!$db_connected) {
    die('DB Not Connected');
}

$action = $_POST['action'] ?? '';

if ($action === 'insert') {
    $hospital_id = $_POST['hospital_id'] ?? 1;
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $target_part = $_POST['target_part'] ?? '';
    $original_price = (float)($_POST['original_price'] ?? 0);
    $discount_price = (float)($_POST['discount_price'] ?? 0);
    $image_url = $_POST['image_url'] ?? '';
    $description = $_POST['description'] ?? '';

    if(empty($title) || empty($category) || empty($discount_price)) {
        die('필수 값이 누락되었습니다.');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO events 
            (hospital_id, title, category, target_part, original_price, discount_price, image_url, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $hospital_id, $title, $category, $target_part, 
            $original_price, $discount_price, $image_url, $description
        ]);
        echo 'success';
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} 
elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        echo 'success';
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    echo 'Invalid action';
}
?>
