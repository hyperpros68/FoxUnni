<?php
// www/api/recommend_events.php
if (function_exists('opcache_reset')) opcache_reset();
header('Content-Type: application/json; charset=utf-8');

$tags_param = $_GET['tags'] ?? '';
$tags = array_filter(array_map('trim', explode(',', $tags_param)));

if (empty($tags)) {
    echo json_encode(['success' => false, 'error' => '태그가 전달되지 않았습니다.', 'events' => []]);
    exit;
}

$all_events = [];
$jsonPath = __DIR__ . '/../events.json';

// list.php 와 동일한 방식으로 events.json 파싱
if (file_exists($jsonPath)) {
    $jsonData = json_decode(file_get_contents($jsonPath), true);
    if (isset($jsonData['data'])) {
        foreach ($jsonData['data'] as $idx => $item) {
            $apiCat = trim($item['category'] ?? '');
            $dbCat = '피부';
            if (in_array($apiCat, ['필러', '보톡스'])) $dbCat = '쁘띠';
            elseif (in_array($apiCat, ['성형'])) $dbCat = '성형';
            
            $discountPrice = (float)preg_replace('/[^0-9]/', '', $item['price'] ?? '0');
            $originalPrice = (float)preg_replace('/[^0-9]/', '', $item['original_price'] ?? '');
            if (!$originalPrice) $originalPrice = $discountPrice;
            
            $imgUrl = trim($item['event_image_url'] ?? '');
            if (strpos($imgUrl, 'http') !== 0) {
                if (strpos($imgUrl, '/') === 0) {
                    $imgUrl = 'https://mediicon03.mycafe24.com' . $imgUrl;
                } else {
                    $imgUrl = '/static/images/skin.jpg';
                }
            }
            
            $region = trim($item['branch_name'] ?? '');
            if (empty($region)) $region = '강남';
            
            $all_events[] = [
                'id' => $item['id'] ?? ($idx + 100),
                'type' => '여우언니 단독 특가',
                'category' => $dbCat,
                'region' => $region,
                'hospital_name' => $item['competitor_name'] ?? '알수없는병원',
                'title' => $item['event_name'] ?? '',
                'discount_price' => $discountPrice,
                'original_price' => $originalPrice,
                'image_url' => $imgUrl,
                'rating' => 4.5 + (rand(-5, 4) / 10),
                'reviews' => rand(10, 500),
                'popularity' => rand(50, 100)
            ];
        }
    }
}

// 태그와 매칭되는 이벤트 찾기
$matched_events = [];
foreach ($all_events as $ev) {
    $title = mb_strtolower($ev['title'], 'UTF-8');
    $match_score = 0;
    
    foreach ($tags as $tag) {
        $tag = mb_strtolower($tag, 'UTF-8');
        // 태그에 따라 키워드 매칭 범위 넓히기
        $keywords = [$tag];
        if (strpos($tag, '여드름') !== false) $keywords = array_merge($keywords, ['아쿠아필', '스케일링', '여드름', '피지', '염증']);
        if (strpos($tag, '다크서클') !== false) $keywords = array_merge($keywords, ['눈밑', '눈밑지방', '필러']);
        if (strpos($tag, '기미') !== false || strpos($tag, '잡티') !== false || strpos($tag, '색소') !== false) $keywords = array_merge($keywords, ['토닝', '레이저', '피코', '색소']);
        if (strpos($tag, '모공') !== false) $keywords = array_merge($keywords, ['스킨보톡스', '모공', '프락셀', '포텐자']);
        if (strpos($tag, '주름') !== false) $keywords = array_merge($keywords, ['보톡스', '리쥬란', '주름', '탄력']);
        if (strpos($tag, '안티에이징') !== false || strpos($tag, '리프팅') !== false) $keywords = array_merge($keywords, ['울쎄라', '슈링크', '써마지', '인모드', '리프팅']);

        foreach ($keywords as $kw) {
            if (mb_strpos($title, $kw) !== false) {
                $match_score += 10;
                break; // 이 태그에 대해서는 점수 획득 완료
            }
        }
    }

    if ($match_score > 0) {
        $ev['match_score'] = $match_score;
        // 평점, 리뷰 수 등에 가산점
        $ev['match_score'] += ($ev['rating'] * 2) + ($ev['popularity'] / 10);
        $matched_events[] = $ev;
    }
}

// 점수 순으로 정렬 후 상위 4개 추출
usort($matched_events, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

$result_events = array_slice($matched_events, 0, 4);

// 매칭된 이벤트가 부족하면 기본 더미 추가
if (count($result_events) < 2) {
    $fallback_kw = htmlspecialchars($tags[0]);
    $result_events[] = [
        'id' => rand(9000, 9999),
        'type' => '여우언니 단독 특가',
        'category' => '피부',
        'region' => '강남/신논현',
        'hospital_name' => '여우언니 프라이빗의원',
        'title' => 'AI 추천 스페셜: ' . $fallback_kw . ' 집중 케어 특가',
        'discount_price' => rand(3, 15) * 10000,
        'original_price' => rand(5, 20) * 10000,
        'image_url' => '/static/images/banner_skin.png',
        'rating' => 4.9,
        'reviews' => rand(100, 500)
    ];
}

echo json_encode([
    'success' => true,
    'events' => $result_events
], JSON_UNESCAPED_UNICODE);
