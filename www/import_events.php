<?php
// import_events.php
// 외부 API에서 시술정보를 가져와 DB에 삽입하는 스크립트
require_once __DIR__ . '/config/db_connect.php';

if (!$db_connected || $pdo === null) {
    die("Database connection failed.\n");
}

$apiUrl = "https://mediicon03.mycafe24.com/api.php?action=event_reports&page=1&pretty=1";
echo "Fetching data from: " . $apiUrl . "\n";

$jsonStr = file_get_contents($apiUrl);
if ($jsonStr === false) {
    die("Failed to fetch API data.\n");
}

$data = json_decode($jsonStr, true);
if (!isset($data['data']) || !is_array($data['data'])) {
    die("Invalid JSON format or empty data.\n");
}

$eventsData = $data['data'];
$count = 0;

echo "Found " . count($eventsData) . " events. Starting import...\n";

foreach ($eventsData as $item) {
    // 1. 병원 정보 확인 및 삽입
    $hospitalName = trim($item['competitor_name'] ?? '알수없는병원');
    
    // DB에 해당 병원이 있는지 확인
    $stmt = $pdo->prepare("SELECT id FROM hospitals WHERE name = :name LIMIT 1");
    $stmt->execute([':name' => $hospitalName]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($hospital) {
        $hospitalId = $hospital['id'];
    } else {
        // 없으면 새로 삽입
        $stmtInsertHosp = $pdo->prepare("INSERT INTO hospitals (name, address, contact, description) VALUES (:name, :address, :contact, :description)");
        $stmtInsertHosp->execute([
            ':name' => $hospitalName,
            ':address' => '주소 미상', // API에 상세 주소가 없으므로 기본값 처리
            ':contact' => '연락처 미상',
            ':description' => 'API 자동 연동 병원'
        ]);
        $hospitalId = $pdo->lastInsertId();
    }

    // 2. 여우언니 단독 특가 데이터 파싱 및 매핑
    $title = trim($item['event_name'] ?? '');
    if (empty($title)) continue; // 제목이 없으면 건너뜀

    $apiCategory = trim($item['category'] ?? '');
    
    // 카테고리 매핑 (ENUM: 성형, 피부, 쁘띠)
    $dbCategory = '피부'; // 기본값
    if (in_array($apiCategory, ['필러', '보톡스'])) {
        $dbCategory = '쁘띠';
    } elseif (in_array($apiCategory, ['성형'])) {
        $dbCategory = '성형';
    } else {
        // 리프팅, 레이저, 스킨케어, 바디, 기타 -> 피부
        $dbCategory = '피부';
    }

    // 부위 (target_part)
    $targetPart = trim($item['details'] ?? '');
    if (empty($targetPart)) {
        $targetPart = '얼굴전체';
    }
    // 길이가 100을 넘을 수 있으므로 자르기
    $targetPart = mb_substr($targetPart, 0, 100, 'UTF-8');

    // 가격 파싱 (숫자만 추출)
    $discountPriceStr = preg_replace('/[^0-9]/', '', $item['price'] ?? '0');
    $discountPrice = $discountPriceStr !== '' ? (float)$discountPriceStr : 0;

    $originalPriceStr = preg_replace('/[^0-9]/', '', $item['original_price'] ?? '');
    $originalPrice = $originalPriceStr !== '' ? (float)$originalPriceStr : $discountPrice; // 할인가와 동일하게 처리

    // 이미지 및 설명
    $imageUrl = trim($item['event_image_url'] ?? '');
    // URL 형태가 아니면 기본 이미지로 처리 (선택적)
    if (strpos($imageUrl, 'http') !== 0) {
        $imageUrl = '/static/images/skin.jpg'; // 임시 이미지
    }

    $description = trim($item['catchphrase'] ?? '');

    // 중복 방지: 동일 병원, 동일 여우언니 단독 특가명이 있는지 체크
    $stmtCheck = $pdo->prepare("SELECT id FROM events WHERE hospital_id = :hospital_id AND title = :title LIMIT 1");
    $stmtCheck->execute([
        ':hospital_id' => $hospitalId,
        ':title' => $title
    ]);

    if (!$stmtCheck->fetch()) {
        // 3. 여우언니 단독 특가 DB에 삽입
        $stmtInsertEvent = $pdo->prepare("INSERT INTO events (hospital_id, title, category, target_part, original_price, discount_price, image_url, description) 
                                          VALUES (:hospital_id, :title, :category, :target_part, :original_price, :discount_price, :image_url, :description)");
        $stmtInsertEvent->execute([
            ':hospital_id' => $hospitalId,
            ':title' => $title,
            ':category' => $dbCategory,
            ':target_part' => $targetPart,
            ':original_price' => $originalPrice,
            ':discount_price' => $discountPrice,
            ':image_url' => $imageUrl,
            ':description' => $description
        ]);
        $count++;
    }
}

echo "Import complete! Successfully imported " . $count . " new events.\n";
?>
