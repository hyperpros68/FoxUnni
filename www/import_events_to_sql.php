<?php
$jsonStr = file_get_contents(__DIR__ . '/events.json');
if ($jsonStr === false) {
    die("Failed to fetch API data.\n");
}

$data = json_decode($jsonStr, true);
if (!isset($data['data']) || !is_array($data['data'])) {
    die("Invalid JSON format or empty data.\n");
}

$eventsData = $data['data'];
echo "Found " . count($eventsData) . " events. Generating SQL...\n";

$sql = "-- 자동 연동 데이터 삽입 SQL\n";
$sql .= "USE shinsa_db;\n\n";

$hospitals = [];

foreach ($eventsData as $item) {
    $hospitalName = trim($item['competitor_name'] ?? '알수없는병원');
    if (!in_array($hospitalName, $hospitals)) {
        $hospitals[] = $hospitalName;
        // 병원 삽입 (중복 무시)
        $sql .= "INSERT IGNORE INTO hospitals (name, address, contact, description) VALUES ('" . addslashes($hospitalName) . "', '주소 미상', '연락처 미상', 'API 자동 연동 병원');\n";
    }
}
$sql .= "\n";

foreach ($eventsData as $item) {
    $title = trim($item['event_name'] ?? '');
    if (empty($title)) continue;

    $hospitalName = trim($item['competitor_name'] ?? '알수없는병원');
    $apiCategory = trim($item['category'] ?? '');
    
    $dbCategory = '피부';
    if (in_array($apiCategory, ['필러', '보톡스'])) {
        $dbCategory = '쁘띠';
    } elseif (in_array($apiCategory, ['성형'])) {
        $dbCategory = '성형';
    }

    $targetPart = trim($item['details'] ?? '');
    if (empty($targetPart)) $targetPart = '얼굴전체';
    $targetPart = mb_substr($targetPart, 0, 100, 'UTF-8');

    $discountPriceStr = preg_replace('/[^0-9]/', '', $item['price'] ?? '0');
    $discountPrice = $discountPriceStr !== '' ? (float)$discountPriceStr : 0;

    $originalPriceStr = preg_replace('/[^0-9]/', '', $item['original_price'] ?? '');
    $originalPrice = $originalPriceStr !== '' ? (float)$originalPriceStr : $discountPrice;

    $imageUrl = trim($item['event_image_url'] ?? '');
    if (strpos($imageUrl, 'http') !== 0) {
        $imageUrl = '/static/images/skin.jpg';
    }

    $description = trim($item['catchphrase'] ?? '');

    // 여우언니 단독 특가 삽입 (중복 확인을 위해 서브쿼리 사용 또는 IGNORE)
    $sql .= "INSERT INTO events (hospital_id, title, category, target_part, original_price, discount_price, image_url, description) \n";
    $sql .= "SELECT id, '" . addslashes($title) . "', '$dbCategory', '" . addslashes($targetPart) . "', $originalPrice, $discountPrice, '" . addslashes($imageUrl) . "', '" . addslashes($description) . "' \n";
    $sql .= "FROM hospitals WHERE name = '" . addslashes($hospitalName) . "' \n";
    $sql .= "AND NOT EXISTS (SELECT 1 FROM events e2 WHERE e2.hospital_id = hospitals.id AND e2.title = '" . addslashes($title) . "') LIMIT 1;\n";
}

file_put_contents(__DIR__ . '/events_insert.sql', $sql);
echo "SQL File generated successfully at: " . __DIR__ . "/events_insert.sql\n";
?>
