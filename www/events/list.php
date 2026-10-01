<?php
// www/events/list.php
require_once '../config/db_connect.php';

$category = $_GET['category'] ?? '전체';
$sort = $_GET['sort'] ?? '인기순';
$type = $_GET['type'] ?? '여우언니 단독 특가'; // '여우언니 단독 특가', '일반시술'
$region_query = $_GET['region'] ?? $_COOKIE['user_region'] ?? '전체지역';
$keyword = $_GET['keyword'] ?? '';
// 세부 카테고리 파라미터 (홈화면 탭에서 넘어오는 세부 시술 카테고리)
$sub_category = $_GET['sub_cat'] ?? '';

// 카테고리 맵핑 표 (세부 → 대분류)
$cat_map = [
    '눈' => '성형',
    '코' => '성형',
    '성형' => '성형',
    '쁘띠' => '쁘띠',
    '리프팅' => '피부',
    '피부' => '피부',
    '바디' => '피부',
    '두피/탈모' => '피부',
    '반영구' => '쁘띠',
];
// sub_category가 있으면 대분류로 변환
if (!empty($sub_category) && $category === '전체') {
    $category = $cat_map[$sub_category] ?? '전체';
}

// 콤마로 구분된 여러 지역 파싱
$selected_regions = array_map('trim', explode(',', $region_query));
if (count($selected_regions) == 1 && $selected_regions[0] === '') {
    $selected_regions = ['전체지역'];
}

// 외부 파일(events.json)에서 시술정보 파싱하여 동적으로 할당
$all_events = [];
$jsonPath = __DIR__ . '/../events.json';
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

// DB(MySQL)에 등록된 실제 병원(어드민) 이벤트 불러오기
try {
    $stmt = $pdo->query("
        SELECT e.*, h.name as hospital_name, h.address as region 
        FROM events e 
        JOIN hospitals h ON e.hospital_id = h.id
        ORDER BY e.created_at DESC
    ");
    $db_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($db_events as $dbe) {
        $all_events[] = [
            'id' => $dbe['id'],
            'type' => '여우언니 단독 특가',
            'category' => $dbe['category'],
            'region' => explode(' ', $dbe['region'])[0] ?? '강남', // 간단히 지역 추출
            'hospital_name' => $dbe['hospital_name'],
            'title' => $dbe['title'],
            'discount_price' => (float)$dbe['discount_price'],
            'original_price' => (float)$dbe['original_price'],
            'image_url' => $dbe['image_url'] ?: '/static/images/skin.jpg',
            'rating' => 5.0,
            'reviews' => 0,
            'popularity' => 100 // 새로 등록된 이벤트이므로 인기순 상위 노출
        ];
    }
} catch (Exception $e) {
    // DB 조회 실패시 무시
}

// 만약 events.json 이 없거나 비어있으면 기본값
if (empty($all_events)) {
    $all_events = [
        ['id'=>1, 'type'=>'여우언니 단독 특가', 'category'=>'성형', 'region'=>'신사', 'hospital_name'=>'신사드림성형외과', 'title'=>'데이터를 불러오지 못했습니다', 'discount_price'=>0, 'original_price'=>0, 'image_url'=>'/static/images/nose.jpg', 'rating'=>0, 'reviews'=>0, 'popularity'=>0],
    ];
}

// 1. 필터링 로직
$events = [];
foreach($all_events as $ev) {
    $match_category = ($category === '전체' || $ev['category'] === $category);
    // 다중 지역 체크
    $match_region = false;
    if (in_array('전체지역', $selected_regions) || in_array('서울 전체', $selected_regions) || in_array('경기 전체', $selected_regions)) {
        $match_region = true;
    } else {
        foreach($selected_regions as $r) {
            // '강남/신논현' 같이 슬래시로 묶인 지역들을 분리해서 각각 검사
            $sub_regions = explode('/', $r);
            foreach($sub_regions as $sub_r) {
                $sub_r = trim($sub_r);
                if (mb_strpos($ev['region'], $sub_r) !== false || $ev['region'] === $sub_r) {
                    $match_region = true;
                    break 2; // 찾았으면 완전히 루프 탈출
                }
            }
        }
    }
    
    $match_type = ($type === '전체' || $ev['type'] === $type);
    
    $match_keyword = empty($keyword) || (mb_strpos($ev['title'], $keyword) !== false || mb_strpos($ev['hospital_name'], $keyword) !== false);
    
    if ($match_category && $match_region && $match_type && $match_keyword) {
        $events[] = $ev;
    }
}

// AI 추천 시술 등 키워드 검색 시 결과가 없으면 가상의 여우언니 단독 특가 제공
if (empty($events) && !empty($keyword)) {
    $events[] = [
        'id' => rand(9000, 9999),
        'type' => '여우언니 단독 특가',
        'category' => $category !== '전체' ? $category : '성형',
        'region' => strpos($region_query, '전체') !== false ? '강남/신논현' : explode(',', $region_query)[0],
        'hospital_name' => '여우언니 프라이빗의원',
        'title' => '프리미엄 ' . htmlspecialchars($keyword) . ' 특가 여우언니 단독 특가',
        'discount_price' => rand(10, 100) * 10000,
        'original_price' => rand(15, 150) * 10000,
        'image_url' => '/static/images/banner_skin.png',
        'rating' => 4.9,
        'reviews' => rand(100, 500),
        'popularity' => 99
    ];
    $events[] = [
        'id' => rand(9000, 9999),
        'type' => '여우언니 단독 특가',
        'category' => $category !== '전체' ? $category : '피부',
        'region' => strpos($region_query, '전체') !== false ? '압구정/신사' : explode(',', $region_query)[0],
        'hospital_name' => '청담 더셀클리닉',
        'title' => '대표원장 1:1 전담 ' . htmlspecialchars($keyword),
        'discount_price' => rand(20, 150) * 10000,
        'original_price' => rand(30, 200) * 10000,
        'image_url' => '/static/images/banner_botox.png',
        'rating' => 4.8,
        'reviews' => rand(50, 300),
        'popularity' => 95
    ];
}

// 2. 정렬 로직
usort($events, function($a, $b) use ($sort) {
    if ($sort === '가격순') return $a['discount_price'] <=> $b['discount_price'];
    if ($sort === '할인율순') {
        $rateA = $a['original_price'] > 0 ? (($a['original_price'] - $a['discount_price']) / $a['original_price']) : 0;
        $rateB = $b['original_price'] > 0 ? (($b['original_price'] - $b['discount_price']) / $b['original_price']) : 0;
        return $rateB <=> $rateA;
    }
    if ($sort === '평점순') return $b['rating'] <=> $a['rating'];
    if ($sort === '후기순') return $b['reviews'] <=> $a['reviews'];
    if ($sort === '최신순') return $b['id'] <=> $a['id'];
    // 기본값: 인기순
    return $b['popularity'] <=> $a['popularity'];
});

// 브라우저 렌더링 성능을 위해 상위 100개만 자르기 (DOM 1만개 생성 방지)
$events = array_slice($events, 0, 100);

// URL 헬퍼 함수
function getUrl($params) {
    global $category, $sort, $region_query, $type;
    $query = [
        'category' => $params['category'] ?? $category,
        'sort' => $params['sort'] ?? $sort,
        'region' => $params['region'] ?? $region_query,
        'type' => $params['type'] ?? $type
    ];
    return '?' . http_build_query($query);
}

// 화면 상단에 보여줄 지역 텍스트 정리
$display_region = implode(', ', $selected_regions);
if (mb_strlen($display_region) > 10) {
    $display_region = mb_substr($display_region, 0, 10) . '...';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>여우언니 단독 특가 및 시술 찾기 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        .header-top { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid var(--border-color); background-color: var(--white); position: sticky; top: 0; z-index: 100;}
        .region-btn { font-size: 16px; font-weight: 700; background: transparent; border: none; cursor: pointer; outline: none; }
        
        /* 토글 탭 (여우언니 단독 특가 vs 일반시술) */
        .type-toggle { display: flex; padding: 10px 20px; background: var(--white); border-bottom: 1px solid var(--border-color); }
        .type-toggle a { flex: 1; text-align: center; padding: 10px; font-weight: 500; color: var(--text-light); border-bottom: 2px solid transparent; }
        .type-toggle a.active { color: var(--text-dark); border-bottom: 2px solid var(--text-dark); font-weight: 700; }
        
        .filter-btn { padding: 6px 15px; background: #f0f0f0; color: #333; border-radius: 20px; font-size: 13px; font-weight: 500; display: inline-block; }
        .filter-btn.active { background: var(--primary-color); color: white; }
        .sort-btn { font-size: 13px; color: var(--text-light); margin-right: 15px; cursor: pointer; }
        .sort-btn.active { color: var(--text-dark); font-weight: 700; }
        .meta-info { font-size: 11px; color: var(--text-light); margin-top: 5px; }
        .meta-info span { margin-right: 8px; }
        .star { color: #FFD700; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 10px; border-radius: 4px; background: #ffe4ed; color: var(--primary-color); font-weight: 700; margin-bottom: 5px; }

        /* ===== 세부 카테고리 탭 (list.php) ===== */
        .sub-cat-tabs {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding: 12px 15px;
            background: #fff;
            scrollbar-width: none;
            -ms-overflow-style: none;
            border-bottom: 1px solid var(--border-color);
        }
        .sub-cat-tabs::-webkit-scrollbar { display: none; }
        .sub-cat-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #f5f5f5;
            color: #666;
            cursor: pointer;
            white-space: nowrap;
            border: 1.5px solid transparent;
            transition: all 0.2s;
            flex-shrink: 0;
            text-decoration: none;
        }
        .sub-cat-btn:hover { background: #fff0f5; color: var(--primary-color); border-color: #ffb6c1; }
        .sub-cat-btn.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        /* ===== END 세부 카테고리 탭 ===== */

        /* ===== 실시간 검색 관련 스타일 ===== */
        .search-result-banner {
            display: none;
            background: linear-gradient(135deg, #fff0f5 0%, #fff8fb 100%);
            padding: 12px 20px;
            border-bottom: 1px solid #ffe0ea;
            align-items: center;
            gap: 10px;
        }
        .search-result-banner.visible { display: flex; }
        .search-result-kw {
            font-size: 15px; font-weight: 800; color: var(--primary-color);
        }
        .search-result-count {
            font-size: 13px; color: #888;
        }
        .search-clear-btn {
            margin-left: auto;
            font-size: 12px; font-weight: 600;
            color: #999; background: #f0f0f0;
            border: none; border-radius: 20px;
            padding: 4px 12px; cursor: pointer;
            text-decoration: none; white-space: nowrap;
        }
        /* 검색어 하이라이트 */
        .event-title mark, .hospital-name mark {
            background: none;
            color: var(--primary-color);
            font-weight: 800;
        }
        /* 필터링 시 숨김 */
        .event-card.list-hidden { display: none !important; }
        /* 검색도중 로딩 애니메이션 */
        @keyframes searchPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        /* 노결과 상태 */
        #noResultMsg {
            display: none;
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        #noResultMsg .no-res-icon { font-size: 48px; margin-bottom: 15px; }
        #noResultMsg .no-res-text { font-size: 15px; font-weight: 600; margin-bottom: 8px; color: #555; }
        #noResultMsg .no-res-sub { font-size: 13px; }
        /* ===== END 실시간 검색 ===== */
    </style>
</head>
<body>
    
    <!-- 상단 헤더 (뒤로가기, 검색창, 카메라) -->
    <header style="display:flex; align-items:center; padding: 15px 20px; background: #fff; gap: 15px;">
        <div style="cursor:pointer; font-size:22px; color: #333;" onclick="history.back()">‹</div>
        <div style="flex: 1; position: relative;">
            <?php
            $trending_phrases = [
                '요즘 대세 인모드 리프팅',
                '지금 뜨는 울쎄라 특가',
                '가장 많이 찾는 스킨부스터',
                '인기 만점 입술 필러',
                '자연스러운 쌍꺼풀 병원',
                '후기 좋은 주름 보톡스',
                'V라인 완성 슈링크'
            ];
            $random_phrase = $trending_phrases[array_rand($trending_phrases)];
            ?>
            <input type="text" id="listKeywordInput" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="<?= $random_phrase ?>" style="width:100%; border:none; background:#f0f2f5; border-radius:20px; padding:10px 35px 10px 15px; font-size:15px; outline:none;" autocomplete="off">
            <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#888; pointer-events:none;">🔍</span>
        </div>
        <div style="cursor:pointer; font-size:20px; color:#555;">📷</div>
    </header>

    <!-- Top Tabs (통합 검색 탭) -->
    <?php
    $kw_param = !empty($keyword) ? '?keyword=' . urlencode($keyword) : '';
    ?>
    <div class="list-top-tabs">
        <div class="list-top-tab active" onclick="location.href='/events/list.php<?= $kw_param ?>'" style="cursor:pointer;">이벤트</div>
        <div class="list-top-tab" onclick="location.href='/community.php<?= $kw_param ?>'" style="cursor:pointer;">커뮤니티</div>
        <div class="list-top-tab" onclick="location.href='/reviews.php<?= $kw_param ?>'" style="cursor:pointer;">시술후기</div>
        <div class="list-top-tab" onclick="location.href='/hospital_map.php<?= $kw_param ?>'" style="cursor:pointer;">병원</div>
        <div class="list-top-tab" onclick="alert('의사 상세 검색 기능은 준비 중입니다!');" style="cursor:pointer;">의사</div>
    </div>

    <!-- Filter Bar (지역, 시술, 가격) -->
    <div class="list-filter-bar">
        <button class="list-filter-btn active" onclick="openAdvModal('region')">지역 21 ⌵</button>
        <button class="list-filter-btn" onclick="openAdvModal('category')">시술 ⌵</button>
        <button class="list-filter-btn" onclick="openAdvModal('price')">가격 ⌵</button>
    </div>

    <!-- Chip Bar (앱결제, CCTV, 정렬) -->
    <div class="list-chip-bar">
        <div class="chip-group">
            <div class="list-chip">앱결제</div>
            <div class="list-chip">CCTV</div>
        </div>
        <div class="list-sort-btn" onclick="openSortModal()">추천순 ⇅</div>
    </div>

    <!-- 실시간 검색 결과 배너 -->
    <div class="search-result-banner" id="searchResultBanner" style="display:none;">
        <span>🔍</span>
        <span class="search-result-kw" id="searchResultKw"></span>
        <span class="search-result-count" id="searchResultCount"></span>
        <button class="search-clear-btn" onclick="clearListSearch()">✕ 검색 초기화</button>
    </div>

    <main style="padding-bottom: 80px; background: #fff;">

        <!-- 여우언니 안심케어 안내 배너 -->
        <div style="background: linear-gradient(90deg, #fdfbfb 0%, #ebedee 100%); padding: 12px 20px; border-bottom: 1px solid #eaeaea; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="alert('여우언니를 통해 예약 시, 부작용 발생에 대비한 제휴 법률 상담 및 사후 관리 케어(포인트 지원 등)를 제공합니다. 자세한 사항은 고객센터를 확인해주세요.');">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="font-size: 24px;">🛡️</div>
                <div>
                    <div style="font-size: 13px; font-weight: 700; color: #111;">부작용 걱정 없는 여우언니 안심케어</div>
                    <div style="font-size: 11px; color: #666; margin-top: 2px;">안심 뱃지 병원에서 사후 관리까지 확실하게!</div>
                </div>
            </div>
            <div style="color: #999; font-size: 16px;">›</div>
        </div>

        <!-- 뼈대 로딩 (Skeleton) UI -->
        <div id="skeletonContainer">
            <?php for($i=0; $i<6; $i++): ?>
            <div class="event-card">
                <div class="skeleton" style="width:80px; height:80px; border-radius:8px; margin-right:15px; flex-shrink:0;"></div>
                <div style="flex:1;">
                    <div class="skeleton" style="width:60px; height:18px; margin-bottom:6px;"></div>
                    <div class="skeleton" style="width:120px; height:14px; margin-bottom:6px;"></div>
                    <div class="skeleton" style="width:100%; height:18px; margin-bottom:10px;"></div>
                    <div class="skeleton" style="width:80px; height:20px;"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- 실제 리스트 (초기 숨김) -->
        <div id="eventListContainer" style="display:none;">
            <?php foreach($events as $event):
                // 세부 카테고리 자동 분류
                $t = mb_strtolower($event['title'], 'UTF-8');
                $sc = '기타';
                if (preg_match('/쌍꺼풀|눈매|트임|눈밑|다크서클|안검/', $t)) $sc = '눈';
                elseif (preg_match('/코성형|코필러|코끝|매부리|복코|하이코/', $t)) $sc = '코';
                elseif (preg_match('/보톡스|필러|리쥬란|스킨부스터|쥬베룩|스컬트라|지방분해|윤곽주사/', $t)) $sc = '쁘띠';
                elseif (preg_match('/리프팅|울쎄라|써마지|슈링크|실리프팅|고주파|초음파|텐테라/', $t)) $sc = '리프팅';
                elseif (preg_match('/피부|여드름|기미|모공|토닝|레이저|아쿠아필|스케일링|프락셀|홍조/', $t)) $sc = '피부';
                elseif (preg_match('/지방흥입|다이어트|바디|종아리|승모근|가슴|힙업|제모/', $t)) $sc = '바디';
                elseif (preg_match('/탈모|두피|모발이식/', $t)) $sc = '두피/탈모';
                elseif (preg_match('/반영구|눈썹문신|입술문신|아이라인/', $t)) $sc = '반영구';
                elseif (preg_match('/성형|윤곽|양악|광대|사각턱|안면/', $t)) $sc = '성형';
                
                $has_app_pay = (crc32($event['id'] . 'app') % 2 === 0) ? 'true' : 'false';
                $has_cctv = (crc32($event['id'] . 'cctv') % 2 === 0) ? 'true' : 'false';
            ?>
            <a href="/events/detail.php?id=<?= $event['id'] ?>" class="event-card infinite-item"
               data-title="<?= htmlspecialchars($event['title']) ?>"
               data-hospital="<?= htmlspecialchars($event['hospital_name']) ?>"
               data-category="<?= htmlspecialchars($event['category']) ?>"
               data-sub-cat="<?= htmlspecialchars($sc) ?>"
               data-app-pay="<?= $has_app_pay ?>"
               data-cctv="<?= $has_cctv ?>">
                <img src="<?= $event['image_url'] ?>" alt="<?= htmlspecialchars($event['title']) ?>" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2280%22%20height%3D%2280%22%20viewBox%3D%220%200%2080%2080%22%3E%3Crect%20fill%3D%22%23eee%22%20width%3D%2280%22%20height%3D%2280%22%2F%3E%3C%2Fsvg%3E'">
                <div class="event-info">
                    <?php if($event['type'] === '일반시술'): ?>
                    <div class="badge">일반시술</div>
                    <?php else: ?>
                    <div class="badge">여우언니 단독 특가</div>
                    <div class="badge" style="background:#f4f4f4; color:#333; margin-left:4px;"><span style="color:#00a8ff;">🛡️</span> 안심케어</div>
                    <?php endif; ?>
                    <div class="hospital-name" data-orig="<?= htmlspecialchars($event['hospital_name']) ?>"><?= htmlspecialchars($event['hospital_name']) ?> <span style="color:#ddd;">|</span> <?= $event['region'] ?></div>
                    <div class="event-title" data-orig="<?= htmlspecialchars($event['title']) ?>"><?= htmlspecialchars($event['title']) ?></div>
                    <div class="price">
                        <?= number_format($event['discount_price']) ?>원 
                        <?php if($event['original_price'] > $event['discount_price']): ?>
                        <span style="font-size:12px; color:#aaa; text-decoration:line-through; font-weight:400;"><?= number_format($event['original_price']) ?>원</span>
                        <?php endif; ?>
                    </div>
                    <div class="meta-info">
                        <span><span class="star">★</span> <?= isset($event['rating']) ? $event['rating'] : '4.5' ?></span>
                        <span>리뷰 <?= isset($event['reviews']) ? number_format($event['reviews']) : '0' ?>개</span>
                        <div style="float:right; font-size:16px; color:#ccc;" class="heart-animate" onclick="event.preventDefault(); this.classList.toggle('liked');">❤️</div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- 무한 스크롤 스피너 & 앵커 -->
        <div class="infinite-spinner" id="infiniteSpinner">
            <div class="spinner-ring"></div>
        </div>
        <div id="infiniteScrollAnchor" style="height:20px;"></div>
        
        <?php if(empty($events)): ?>
        <div style="padding: 40px 20px; text-align: center; color: var(--text-light);">
            해당 조건의 시술/수술이 없습니다.
        </div>
        <?php endif; ?>

        <!-- 실시간 필터링 결과 없음 상태 -->
        <div id="noResultMsg">
            <div class="no-res-icon">🔍</div>
            <div class="no-res-text" id="noResultKw"></div>
            <div class="no-res-sub">다른 키워드로 검색해 보세요</div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/index.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span style="margin-top: 4px;">홈</span>
        </a>
        <a href="/events/list.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <span style="margin-top: 4px;">검색</span>
        </a>
        <a href="/mypage/reviews.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <span style="margin-top: 4px;">리뷰</span>
        </a>
        <a href="/mypage.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span style="margin-top: 4px;">마이 여우</span>
        </a>
    </nav>

    
    <!-- Bottom Sheet Modal (Adv Modal) -->
    <div class="bottom-sheet-overlay" id="advModalOverlay" onclick="closeAdvModal()"></div>
    <div class="bottom-sheet" id="advModal">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <div class="sheet-tabs">
                <div class="sheet-tab" id="tab-region" onclick="switchAdvTab('region')">지역<span style="color:#f25530;font-size:18px;line-height:0;vertical-align:top;margin-left:2px;">.</span></div>
                <div class="sheet-tab" id="tab-category" onclick="switchAdvTab('category')">시술</div>
                <div class="sheet-tab" id="tab-price" onclick="switchAdvTab('price')">가격</div>
            </div>
        </div>

        <!-- 1. 지역 탭 내용 -->
        <div class="two-pane" id="pane-region" style="display: none;">
            <div class="pane-left">
                <div class="pane-left-item active" onclick="selectRegionLeft('seoul', this)">서울<span style="color:#f25530;font-size:18px;line-height:0;vertical-align:top;margin-left:2px;">.</span></div>
                <div class="pane-left-item" onclick="selectRegionLeft('gyeonggi', this)">경기</div>
                <div class="pane-left-item" onclick="selectRegionLeft('incheon', this)">인천</div>
                <div class="pane-left-item" onclick="selectRegionLeft('busan', this)">부산</div>
                <div class="pane-left-item" onclick="selectRegionLeft('daegu', this)">대구</div>
            </div>
            <div class="pane-right" id="regionRightContent">
                <div class="region-right-group" id="rrg-seoul">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="서울 전체" onchange="updateSelectedTags()"> <span>서울 전체</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="강남역/신논현역/양재" onchange="updateSelectedTags()"> <span>강남역/신논현역/양재</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="청담/압구정/신사" onchange="updateSelectedTags()"> <span>청담/압구정/신사</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="선릉/삼성" onchange="updateSelectedTags()"> <span>선릉/삼성</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="논현/반포/학동" onchange="updateSelectedTags()"> <span>논현/반포/학동</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="서초/교대/방배" onchange="updateSelectedTags()"> <span>서초/교대/방배</span></label>
                </div>
                <div class="region-right-group" id="rrg-gyeonggi" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="경기 전체" onchange="updateSelectedTags()"> <span>경기 전체</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="분당/판교" onchange="updateSelectedTags()"> <span>분당/판교</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="수원/화성" onchange="updateSelectedTags()"> <span>수원/화성</span></label>
                </div>
                <div class="region-right-group" id="rrg-incheon" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="인천 전체" onchange="updateSelectedTags()"> <span>인천 전체</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="부평/구월" onchange="updateSelectedTags()"> <span>부평/구월</span></label>
                </div>
                <div class="region-right-group" id="rrg-busan" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="부산 전체" onchange="updateSelectedTags()"> <span>부산 전체</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="서면/전포" onchange="updateSelectedTags()"> <span>서면/전포</span></label>
                </div>
                <div class="region-right-group" id="rrg-daegu" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="대구 전체" onchange="updateSelectedTags()"> <span>대구 전체</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="동성로" onchange="updateSelectedTags()"> <span>동성로</span></label>
                </div>
            </div>
        </div>

        <!-- 2. 시술 탭 내용 -->
        <div class="two-pane" id="pane-category" style="display: none;">
            <div class="pane-left">
                <div class="pane-left-item active" onclick="selectCatLeft('lifting', this)">리프팅</div>
                <div class="pane-left-item" onclick="selectCatLeft('skin', this)">피부</div>
                <div class="pane-left-item" onclick="selectCatLeft('botox', this)">보톡스</div>
                <div class="pane-left-item" onclick="selectCatLeft('filler', this)">필러</div>
            </div>
            <div class="pane-right" id="catRightContent">
                <div class="cat-right-group" id="crg-lifting">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="울쎄라·슈링크" onchange="updateSelectedTags()"> <span>울쎄라·슈링크 류<br><small style="color:#888;">(초음파리프팅)</small></span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="써마지·인모드" onchange="updateSelectedTags()"> <span>써마지·인모드 류<br><small style="color:#888;">(고주파리프팅)</small></span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="실리프팅" onchange="updateSelectedTags()"> <span>실리프팅</span></label>
                </div>
                <div class="cat-right-group" id="crg-skin" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="스킨부스터" onchange="updateSelectedTags()"> <span>스킨부스터</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="여드름관리" onchange="updateSelectedTags()"> <span>여드름관리</span></label>
                </div>
                <div class="cat-right-group" id="crg-botox" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="주름보톡스" onchange="updateSelectedTags()"> <span>주름보톡스</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="사각턱보톡스" onchange="updateSelectedTags()"> <span>사각턱보톡스</span></label>
                </div>
                <div class="cat-right-group" id="crg-filler" style="display:none;">
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="코필러" onchange="updateSelectedTags()"> <span>코필러</span></label>
                    <label class="pane-right-item"><input type="checkbox" class="custom-cb custom-cb-round" value="입술필러" onchange="updateSelectedTags()"> <span>입술필러</span></label>
                </div>
            </div>
        </div>

        <!-- 3. 가격 탭 내용 -->
        <div id="pane-price" style="display: none; flex:1; overflow-y:auto;">
            <div style="padding:20px; font-size:14px; color:#333; font-weight:700;">검색한 이벤트의 평균가 <span style="float:right; font-size:16px;">919,903원</span></div>
            <div class="price-input-row">
                <div style="flex:1;">
                    <div style="font-size:13px; color:#666; margin-bottom:5px;">최소 가격</div>
                    <input type="text" class="price-input-box" placeholder="1,100 원">
                </div>
                <div style="flex:1;">
                    <div style="font-size:13px; color:#666; margin-bottom:5px;">최대 가격</div>
                    <input type="text" class="price-input-box" placeholder="23,700,000 원">
                </div>
            </div>
            <div class="price-chip-grid">
                <div class="price-chip" onclick="this.classList.toggle('active')">130,000원 이하</div>
                <div class="price-chip" onclick="this.classList.toggle('active')">130,000원 ~ 310,000원</div>
                <div class="price-chip" onclick="this.classList.toggle('active')">310,000원 ~ 600,000원</div>
                <div class="price-chip" onclick="this.classList.toggle('active')">600,000원 ~ 1,290,000원</div>
                <div class="price-chip" onclick="this.classList.toggle('active')">1,290,000원 이상</div>
            </div>
        </div>

        <!-- 하단 공통 푸터 -->
        <div>
            <div class="sheet-selected-tags" id="selectedTagsContainer">
                <!-- Javascript로 동적 추가 -->
            </div>
            <div class="sheet-footer">
                <button class="btn-reset" onclick="resetFilters()">초기화</button>
                <button class="btn-apply" onclick="applyFilters()">적용하기</button>
            </div>
        </div>
    </div>

    <!-- Sort Modal -->
    <div class="bottom-sheet-overlay" id="sortModalOverlay" onclick="closeSortModal()"></div>
    <div class="bottom-sheet" id="sortModal" style="border-radius:24px 24px 0 0; max-height: 70vh;">
        <div style="padding:20px; font-size:18px; font-weight:700; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
            정렬 <span style="cursor:pointer; color:#888;" onclick="closeSortModal()">✕</span>
        </div>
        <div style="overflow-y:auto; padding-bottom:30px;">
            <div class="sort-item active" onclick="selectSort(this)">추천순</div>
            <div class="sort-item" onclick="selectSort(this)">최근 등록순</div>
            <div class="sort-item" onclick="selectSort(this)">가격 낮은순</div>
            <div class="sort-item" onclick="selectSort(this)">할인율 높은순</div>
            <div class="sort-item" onclick="selectSort(this)">가격 높은순</div>
            <div class="sort-item" onclick="selectSort(this)">후기 많은순</div>
            <div class="sort-item" onclick="selectSort(this)">마감 임박순</div>
            <div class="sort-item" onclick="selectSort(this)">인기순</div>
            <div class="sort-item" onclick="selectSort(this)">평점순</div>
        </div>
    </div>

    <script>
        /* 필터 바 칩 토글 및 필터링 */
        document.querySelectorAll('.list-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                chip.classList.toggle('active');
                applyChipFilters();
            });
        });

        function applyChipFilters() {
            const isAppPayActive = document.querySelectorAll('.list-chip')[0].classList.contains('active');
            const isCctvActive = document.querySelectorAll('.list-chip')[1].classList.contains('active');
            
            document.querySelectorAll('.event-card').forEach(card => {
                const hasAppPay = card.dataset.appPay === 'true';
                const hasCctv = card.dataset.cctv === 'true';
                
                let show = true;
                if (isAppPayActive && !hasAppPay) show = false;
                if (isCctvActive && !hasCctv) show = false;
                
                if (show) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        /* Bottom Sheet JS */
        const advModal = document.getElementById('advModal');
        const advOverlay = document.getElementById('advModalOverlay');
        const sortModal = document.getElementById('sortModal');
        const sortOverlay = document.getElementById('sortModalOverlay');

        function openAdvModal(tabName) {
            advModal.classList.add('show');
            advOverlay.classList.add('show');
            switchAdvTab(tabName);
        }
        function closeAdvModal() {
            advModal.classList.remove('show');
            advOverlay.classList.remove('show');
        }

        function switchAdvTab(tabName) {
            document.querySelectorAll('.sheet-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');

            document.getElementById('pane-region').style.display = 'none';
            document.getElementById('pane-category').style.display = 'none';
            document.getElementById('pane-price').style.display = 'none';

            document.getElementById('pane-' + tabName).style.display = 'flex';
        }

        /* Left Sidebar Nav */
        function selectRegionLeft(id, el) {
            document.getElementById('pane-region').querySelectorAll('.pane-left-item').forEach(e => e.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.region-right-group').forEach(g => g.style.display = 'none');
            document.getElementById('rrg-' + id).style.display = 'block';
        }
        function selectCatLeft(id, el) {
            document.getElementById('pane-category').querySelectorAll('.pane-left-item').forEach(e => e.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.cat-right-group').forEach(g => g.style.display = 'none');
            document.getElementById('crg-' + id).style.display = 'block';
        }

        /* Dynamic Tags */
        function updateSelectedTags() {
            const container = document.getElementById('selectedTagsContainer');
            container.innerHTML = '';
            const checked = document.querySelectorAll('.custom-cb:checked');
            checked.forEach(cb => {
                const tag = document.createElement('div');
                tag.className = 'sheet-selected-tag';
                tag.innerHTML = `${cb.value} <i onclick="uncheckVal('${cb.value}')">✕</i>`;
                container.appendChild(tag);
            });
        }
        function uncheckVal(val) {
            const cb = document.querySelector(`.custom-cb[value="${val}"]`);
            if(cb) {
                cb.checked = false;
                updateSelectedTags();
            }
        }
        function resetFilters() {
            document.querySelectorAll('.custom-cb:checked').forEach(cb => cb.checked = false);
            document.querySelectorAll('.price-chip').forEach(c => c.classList.remove('active'));
            updateSelectedTags();
        }
        function applyFilters() {
            const checkedRegions = [];
            document.getElementById('pane-region').querySelectorAll('.custom-cb:checked').forEach(cb => {
                checkedRegions.push(cb.value);
            });
            
            const url = new URL(window.location.href);
            
            if (checkedRegions.length > 0) {
                url.searchParams.set('region', checkedRegions.join(','));
            } else {
                url.searchParams.set('region', '전체지역');
            }
            
            window.location.href = url.toString();
        }

        // 페이지 로드 시 선택된 지역 체크박스 활성화
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const regions = urlParams.get('region');
            if (regions && regions !== '전체지역') {
                const regionArr = regions.split(',');
                document.getElementById('pane-region').querySelectorAll('.custom-cb').forEach(cb => {
                    if (regionArr.includes(cb.value)) {
                        cb.checked = true;
                    }
                });
                updateSelectedTags();
            }
        });

        /* Sort Modal JS */
        function openSortModal() {
            sortModal.classList.add('show');
            sortOverlay.classList.add('show');
        }
        function closeSortModal() {
            sortModal.classList.remove('show');
            sortOverlay.classList.remove('show');
        }
        function selectSort(el) {
            document.querySelectorAll('.sort-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            // update sorting logic here
            document.querySelector('.list-sort-btn').innerHTML = el.textContent.trim() + ' ⇅';
            closeSortModal();
        }

        /* Premium UX: Skeleton & Infinite Scroll */
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const skel = document.getElementById('skeletonContainer');
                const list = document.getElementById('eventListContainer');
                if(skel) skel.style.display = 'none';
                if(list) {
                    list.style.display = 'block';
                    initInfiniteScroll();
                }
            }, 600); // 0.6s 뼈대 애니메이션 유지 (고급스러운 느낌)
        });

        function initInfiniteScroll() {
            const cards = document.querySelectorAll('#eventListContainer .infinite-item');
            if(cards.length === 0) return;
            
            let currentIndex = 0;
            const batchSize = 10;
            
            // 처음 10개만 보이고 나머지는 숨김
            cards.forEach((card, idx) => {
                if(idx >= batchSize) card.style.display = 'none';
            });
            currentIndex = batchSize;

            const spinner = document.getElementById('infiniteSpinner');
            if(currentIndex >= cards.length && spinner) spinner.style.display = 'none';

            const observer = new IntersectionObserver((entries) => {
                if(entries[0].isIntersecting) {
                    if(currentIndex >= cards.length) {
                        if(spinner) spinner.style.display = 'none';
                        return;
                    }
                    if(spinner) spinner.classList.add('active');
                    
                    setTimeout(() => {
                        let end = currentIndex + batchSize;
                        for(let i=currentIndex; i<end && i<cards.length; i++) {
                            cards[i].style.display = 'flex';
                            cards[i].style.animation = 'pageFadeIn 0.4s ease-out forwards';
                        }
                        currentIndex = end;
                        if(currentIndex >= cards.length && spinner) {
                            spinner.style.display = 'none';
                            spinner.classList.remove('active');
                        }
                    }, 400);
                }
            }, { rootMargin: "0px 0px 200px 0px", threshold: 0 });

            const anchor = document.getElementById('infiniteScrollAnchor');
            if(anchor) observer.observe(anchor);
        }
    </script>
</body>
</html>
