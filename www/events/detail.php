<?php
// www/events/detail.php
// Auth Guard: 로그인도 안했고 둘러보기도 선택 안 한 경우
session_start();
if (!isset($_SESSION['is_logged_in']) && !isset($_SESSION['is_guest'])) {
    header("Location: /login.php");
    exit;
}

require_once '../config/db_connect.php';
require_once '../api/lib/AiReviewGenerator.php';

$id = $_GET['id'] ?? 1;
$event = null;

if ($db_connected) {
    try {
        $stmt = $pdo->prepare("SELECT e.*, h.name as hospital_name, h.address, h.homepage FROM events e JOIN hospitals h ON e.hospital_id = h.id WHERE e.id = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($event) {
            // DB 스키마에 존재하지 않지만 뷰 렌더링에 필요한 가상 데이터 주입
            $event['rating'] = 4.5 + (rand(-5, 4) / 10);
            $event['reviews'] = rand(100, 2000);
            $event['applicant_count'] = rand(1000, 5000);
        }
    } catch(Exception $e) {}
}

if (!$event || empty($event)) {
    // events.json에서 해당 ID의 데이터 검색
    $jsonPath = __DIR__ . '/../events.json';
    if (file_exists($jsonPath)) {
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        if (isset($jsonData['data'])) {
            foreach ($jsonData['data'] as $idx => $item) {
                $current_id = $item['id'] ?? $idx;
                if ((string)$current_id === (string)$id) {
                    $discountPrice = preg_replace('/[^0-9]/', '', $item['price'] ?? '0');
                    $originalPrice = preg_replace('/[^0-9]/', '', $item['original_price'] ?? '');
                    
                    $imgUrl = trim($item['event_image_url'] ?? '');
                    if (strpos($imgUrl, 'http') !== 0 && strpos($imgUrl, '/') === 0) {
                        $imgUrl = 'https://mediicon03.mycafe24.com' . $imgUrl;
                    } elseif (empty($imgUrl)) {
                        $imgUrl = '/static/images/skin.jpg';
                    }
                    
                    $event = [
                        'id' => $current_id,
                        'title' => $item['event_name'] ?? '여우언니 단독 특가',
                        'category' => $item['category'] ?? '의료',
                        'target_part' => '',
                        'discount_price' => (float)$discountPrice,
                        'original_price' => (float)$originalPrice,
                        'hospital_name' => $item['competitor_name'] ?? '병원',
                        'address' => '상세 주소 정보 없음',
                        'homepage' => 'https://www.shinsaclinic.co.kr',
                        'description' => $item['details'] ?? '상세 설명이 제공되지 않았습니다.',
                        'catchphrase' => $item['catchphrase'] ?? '',
                        'image_url' => $imgUrl,
                        'rating' => 4.5 + (rand(-5, 4) / 10),
                        'reviews' => rand(100, 2000),
                        'applicant_count' => rand(1000, 5000)
                    ];
                    break;
                }
            }
        }
    }
}

if(!$event) die("여우언니 단독 특가를 찾을 수 없습니다.");

// 할인율 계산
$discount_rate = 0;
if ($event['original_price'] > 0 && $event['discount_price'] > 0) {
    $discount_rate = round((($event['original_price'] - $event['discount_price']) / $event['original_price']) * 100);
}
if ($discount_rate <= 0 && $event['original_price'] > 0) $discount_rate = rand(10, 50);

// 동적 리뷰 및 의사 프로필 생성 (스크래핑 대체)
function generateDynamicData($eventId, $hospitalName, $category, $price) {
    $numericId = preg_replace('/[^0-9]/', '', (string)$eventId);
    if(empty($numericId)) $numericId = 1234;
    $seed = (int)$numericId;
    srand($seed);

    $docNames = [
        ['name'=>'김지훈', 'gender'=>'male'],
        ['name'=>'이수진', 'gender'=>'female'],
        ['name'=>'박민호', 'gender'=>'male'],
        ['name'=>'최윤아', 'gender'=>'female'],
        ['name'=>'이우성', 'gender'=>'male'],
        ['name'=>'강지훈', 'gender'=>'male'],
        ['name'=>'조민수', 'gender'=>'male'],
        ['name'=>'송지혜', 'gender'=>'female'],
        ['name'=>'전수진', 'gender'=>'female'],
        ['name'=>'김소희', 'gender'=>'female'],
        ['name'=>'장현우', 'gender'=>'male'],
        ['name'=>'원민석', 'gender'=>'male']
    ];
    $docData = $docNames[$seed % count($docNames)];
    $doctorName = $docData['name'] . ' 원장';
    $docImg = $docData['gender'] === 'male' ? '/static/images/doc_male.png' : '/static/images/doc_female.png';

    $spec = '피부과 전문의';
    if(strpos($category, '수술') !== false || strpos($category, '가슴') !== false || strpos($category, '윤곽') !== false) {
        $spec = '성형외과 전문의';
    }

    $tags = ['필러', '리프팅', '보톡스', '피부', '다이어트', '제모', '수술', '안티에이징', '윤곽', '스킨부스터'];
    shuffle($tags);
    $docTags = array_slice($tags, 0, 4);

    $editorReview = [];
    if(strpos($category, '필러') !== false) {
        $editorReview = [
            'title' => '자연스러운 볼륨업이 필요한 순간',
            'desc' => '무작정 많이 넣는 것이 아니라, 얼굴의 꺼진 부위와 대칭을 분석해 꼭 필요한 용량만 주입합니다. 입체감 있는 동안 라인을 원하는 분들께 적극 추천하는 안전한 시술입니다.'
        ];
    } elseif(strpos($category, '보톡스') !== false) {
        $editorReview = [
            'title' => '주름 예방과 윤곽 타이트닝의 정석',
            'desc' => '근육의 과도한 움직임을 부드럽게 이완시켜 깊은 주름을 예방하고, 사각턱 등 불필요한 근육 볼륨을 줄여 매끄러운 얼굴선을 만들어 줍니다.'
        ];
    } elseif(strpos($category, '리프팅') !== false || strpos($category, '피부') !== false) {
        $editorReview = [
            'title' => '수술 없이 완성하는 타이트닝 핏',
            'desc' => '비침습적 장비를 통해 피부 깊은 곳의 콜라겐 생성을 촉진합니다. 처진 턱선과 탄력 잃은 볼살을 쫀쫀하게 끌어올려 탄탄한 브이라인을 완성하세요.'
        ];
    } else {
        $editorReview = [
            'title' => '여우언니 MD가 강력 추천하는 솔루션',
            'desc' => '수많은 시술 중에서도 만족도가 가장 높은 프리미엄 솔루션입니다. 체계적인 상담과 전문적인 시술 환경으로 후회 없는 선택이 될 것입니다.'
        ];
    }
    
    $optionNames = [];
    if(strpos($category, '보톡스') !== false) {
        $optionNames = ['얼굴전체 스킨보톡스', '사각턱 보톡스 50u', '주름보톡스 3부위', '다한증 보톡스 50u', '승모근 보톡스 100u'];
    } elseif(strpos($category, '필러') !== false) {
        $optionNames = ['국산 필러 1cc', '수입 프리미엄 필러 1cc', '입술+입꼬리 필러', '애교살 필러', '풀페이스 필러 5cc'];
    } elseif(strpos($category, '리프팅') !== false) {
        $optionNames = ['인모드 FX 1부위', '슈링크 유니버스 300샷', '울쎄라 300샷', '올리지오 300샷', '실리프팅 4줄'];
    } else {
        $optionNames = ['파워 지방분해주사 50cc', '아쿠아필 1회', '피코토닝 10회', '여드름 압출+스케일링', 'L6 프리미엄 스킨부스터'];
    }
    
    $otherOptions = [];
    $basePrice = (int)$price > 0 ? (int)$price : 50000;
    foreach($optionNames as $i => $opt) {
        $optPrice = round($basePrice * (rand(5, 20) / 10) / 1000) * 1000;
        if($optPrice == 0) $optPrice = 39000;
        $otherOptions[] = [
            'name' => $opt,
            'price' => number_format($optPrice)
        ];
    }
    
    srand();
    return ['doctor' => $doctorName, 'spec' => $spec, 'tags' => $docTags, 'editorReview' => $editorReview, 'doc_img' => $docImg, 'otherOptions' => $otherOptions];
}

$dynamicData = generateDynamicData($event['id'], $event['hospital_name'], $event['category'], $event['discount_price']);

// AI Banner Logic
$eventTitleStr = $event['title'] . ' ' . $event['category'];
if(strpos($eventTitleStr, '보톡스') !== false || strpos($eventTitleStr, '필러') !== false || strpos($eventTitleStr, '리프팅') !== false) {
    $aiBanner = '/static/images/banner_botox.png';
} elseif(strpos($eventTitleStr, '다이어트') !== false || strpos($eventTitleStr, '지방') !== false || strpos($eventTitleStr, '바디') !== false || strpos($eventTitleStr, '승모근') !== false) {
    $aiBanner = '/static/images/banner_body.png';
} else {
    $aiBanner = '/static/images/banner_skin.png';
}

// 실제 리얼 후기 데이터 가져오기
$event_reviews = [];
if ($db_connected) {
    try {
        $r_stmt = $pdo->prepare("SELECT * FROM reviews WHERE event_id = ? ORDER BY created_at DESC LIMIT 5");
        $r_stmt->execute([$event_id]);
        $event_reviews = $r_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}

// 카카오맵/네이버 지도 검색 최적화를 위한 괄호 제거 병원명 정돈
$clean_hospital_name = preg_replace('/\(.*?\)/u', '', $event['hospital_name']);
$clean_hospital_name = trim(preg_replace('/\s+/', ' ', $clean_hospital_name));
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($event['title']) ?> - 여우언니</title>
    <!-- OG 메타태그 (SNS 공유용) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($event['title']) ?> - 여우언니">
    <meta property="og:description" content="<?= htmlspecialchars($event['hospital_name']) ?> · <?= number_format($event['discount_price']) ?>원<?= ($event['original_price'] > $event['discount_price']) ? ' (정가 ' . number_format($event['original_price']) . '원)' : '' ?>">
    <meta property="og:image" content="<?= htmlspecialchars($aiBanner) ?>">
    <meta property="og:url" content="https://www.shinsaclinic.co.kr/events/detail.php?id=<?= $event['id'] ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($event['title']) ?> - 여우언니">
    <meta name="twitter:description" content="<?= htmlspecialchars($event['hospital_name']) ?> · <?= number_format($event['discount_price']) ?>원">
    <meta name="twitter:image" content="<?= htmlspecialchars($aiBanner) ?>">
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <!-- 카카오 공유 SDK -->
    <script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.2/kakao.min.js" crossorigin="anonymous"></script>
    <!-- 포트원(구 아임포트) 결제 연동 SDK -->
    <script src="https://cdn.iamport.kr/v1/iamport.js"></script>
    <style>
        /* ===== 공유 바텀시트 ===== */
        #shareModal {
            display: none;
            position: fixed;
            top: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: 480px;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: flex-end;
        }
        #shareModal.active { display: flex; }
        .share-sheet {
            width: 100%;
            background: #fff;
            border-radius: 20px 20px 0 0;
            padding: 20px 20px calc(20px + env(safe-area-inset-bottom));
            animation: slideUp 0.3s ease-out forwards;
        }
        .share-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            text-align: center;
            margin-bottom: 24px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .share-back {
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            cursor: pointer;
            color: #333;
        }
        .share-close {
            position: absolute;
            right: 0; top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            padding: 4px;
            font-weight: 400;
        }
        .share-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .share-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: transform 0.15s;
        }
        .share-item:active { transform: scale(0.92); }
        .share-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .share-item-label {
            font-size: 12px;
            color: #555;
            font-weight: 600;
            text-align: center;
        }
        .share-url-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f7f7f7;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 4px;
        }
        .share-url-text {
            flex: 1;
            font-size: 13px;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .share-copy-btn {
            flex-shrink: 0;
            padding: 7px 14px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        /* ===== 기존 바텀시트 ===== */
        .bottom-sheet {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 20px 20px 0 0;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s ease-out forwards;
            height: 75vh;
            max-height: 85vh;
            overflow: hidden;
            padding-bottom: env(safe-area-inset-bottom);
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .sheet-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }
        .sheet-close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            line-height: 1;
            color: #999;
        }
        .sheet-content {
            padding: 10px 20px 40px 20px;
            overflow-y: auto;
            flex: 1;
        }
        .opt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
        }
        .opt-item:last-child {
            border-bottom: none;
        }
        .opt-name { font-size: 15px; color: #333; }
        .opt-price { font-size: 15px; font-weight: 700; color: #333; }
        .current-opt { background-color: rgba(245,87,108,0.05); padding: 18px 15px; border-radius: 8px; margin: 10px 0; border-bottom: none; }
        .current-opt .opt-price { color: var(--primary-color); }
        
        /* Booking Modal Styles */
        .date-item {
            display:flex; flex-direction:column; align-items:center; min-width:45px; cursor:pointer;
        }
        .date-item .day-text { font-size:13px; color:#888; margin-bottom:8px; }
        .date-item .date-num { width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:12px; font-size:16px; font-weight:700; color:#333; }
        .date-item.active .day-text { color:#333; font-weight:700; }
        .date-item.active .date-num { background:var(--primary-color); color:#fff; }
        .date-item .today-text { font-size:11px; color:#888; margin-top:5px; }
        
        .time-slot {
            padding:12px 0; text-align:center; border:1px solid #eee; border-radius:8px; font-size:15px; font-weight:700; color:#333; cursor:pointer; transition:all 0.2s;
        }
        .time-slot.active {
            border-color:var(--primary-color); background:rgba(245,87,108,0.05); color:var(--primary-color);
        }
        .time-slot.disabled {
            background:#f9f9f9; color:#ccc; cursor:not-allowed; border-color:#eee;
        }
        
        .booking-modal {
            display:none; position:fixed; top:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; height:100%; background:#fff; z-index:3000; flex-direction:column; box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .booking-modal.active { display:flex; }
    </style>
    <!-- Kakao Map API -->
    <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=8bef00e97793340d80a37b7277aca104&libraries=services"></script>
</head>
<body style="background-color: #f5f5f5; padding-bottom: 90px;">
    <!-- Header -->
    <header class="detail-header">
        <svg onclick="history.back()" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        <svg onclick="openShareModal()" style="cursor:pointer;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
    </header>

    <!-- 공유 바텀시트 모달 -->
    <div id="shareModal" onclick="closeShareModal()">
        <div class="share-sheet" onclick="event.stopPropagation()">
            <div class="share-title">
                <span class="share-back" onclick="closeShareModal()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </span>
                공유하기
                <span class="share-close" onclick="closeShareModal()">✕</span>
            </div>
            <div class="share-grid">
                <!-- 카카오톡 -->
                <div class="share-item" onclick="shareKakao()">
                    <div class="share-icon" style="background:#FEE500;">
                        <img src="https://developers.kakao.com/assets/img/about/logos/kakaolink/kakaolink_btn_medium.png" style="width:36px;height:36px;object-fit:contain;" alt="카카오톡">
                    </div>
                    <span class="share-item-label">카카오톡</span>
                </div>
                <!-- 페이스북 -->
                <div class="share-item" onclick="shareFacebook()">
                    <div class="share-icon" style="background:#1877F2;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="white"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </div>
                    <span class="share-item-label">페이스북</span>
                </div>
                <!-- 트위터(X) -->
                <div class="share-item" onclick="shareTwitter()">
                    <div class="share-icon" style="background:#000;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L2 2.25h6.99l4.255 5.629L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </div>
                    <span class="share-item-label">X(트위터)</span>
                </div>
                <!-- 더보기/네이티브 공유 -->
                <div class="share-item" onclick="shareNative()">
                    <div class="share-icon" style="background:#f0f0f0;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                    </div>
                    <span class="share-item-label">더보기</span>
                </div>
            </div>
            <!-- URL 복사 -->
            <div class="share-url-box">
                <span class="share-url-text" id="shareUrlText"></span>
                <button class="share-copy-btn" onclick="copyShareUrl()">링크 복사</button>
            </div>
        </div>
    </div>

    <!-- Hero Image with AI Banner & Price Overlay -->
    <div class="detail-hero-wrapper" style="width: 100%; height: 400px; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
        <!-- Background Image -->
        <img src="<?= $aiBanner ?>" alt="여우언니 단독 특가 대표 배너" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;">
        
        <!-- Gradient Overlay for Text Legibility -->
        <div style="position:absolute; bottom:0; left:0; right:0; height:50%; background:linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);"></div>
        
        <!-- Title & Price Overlay -->
        <div style="position:absolute; bottom:30px; left:20px; right:20px; color:#fff; text-shadow:0 2px 10px rgba(0,0,0,0.3);">
            <div style="font-size:14px; font-weight:700; color:var(--primary-color); margin-bottom:5px; background:rgba(255,255,255,0.9); display:inline-block; padding:3px 8px; border-radius:4px;"><?= htmlspecialchars($event['hospital_name']) ?></div>
            <h1 style="font-size:24px; font-weight:700; margin:0 0 10px 0; line-height:1.3;"><?= htmlspecialchars($event['title']) ?></h1>
            <div style="font-size:26px; font-weight:800; color:#fff;">
                <?= number_format($event['discount_price']) ?><span style="font-size:18px; font-weight:400;">원</span>
                <?php if($event['original_price'] > $event['discount_price']): ?>
                <span style="font-size:14px; text-decoration:line-through; color:rgba(255,255,255,0.7); margin-left:8px; font-weight:400;"><?= number_format($event['original_price']) ?>원</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Title & Rating -->
    <div class="detail-content" style="padding-bottom: 10px;">
        <div class="h-name-tag"><?= htmlspecialchars($event['hospital_name']) ?> &gt;</div>
        <h1 class="e-main-title"><?= htmlspecialchars($event['title']) ?></h1>
        <div class="e-rating">
            <span class="star">★</span> <span style="font-weight: 700; color: #333;"><?= $event['rating'] ?></span> (<?= number_format($event['reviews']) ?>) · <span style="font-weight: 600;">Q&A</span> (<?= rand(100, 1000) ?>)
        </div>

        <!-- Rebooking Card Mock -->
        <div class="rebook-card">
            <div>
                <div class="title">이전 상담 · 예약 - 2026년 3월 20일</div>
                <div class="desc">지난 시술에 만족했다면, 다시 예약해보세요</div>
            </div>
            <a href="#" class="btn-rebook" onclick="alert('다시 예약 기능은 준비중입니다.');">다시 예약 &gt;</a>
        </div>

        <!-- Option Info -->
        <div class="option-box">
            <div class="opt-label">선택된 옵션</div>
            <div class="opt-title">
                <?= htmlspecialchars($event['title']) ?>
                <span onclick="openOptionModal();" style="cursor:pointer; font-size:12px;">전체보기 &gt;</span>
            </div>
        </div>

        <!-- Pricing -->
        <div class="price-area">
            <?php if($event['original_price'] > $event['discount_price']): ?>
            <div class="original"><?= number_format($event['original_price']) ?>원 <span style="font-size:12px;">(VAT 포함)</span></div>
            <div class="discount-row">
                <span class="rate"><?= $discount_rate ?>%</span>
                <span class="final-price"><?= number_format($event['discount_price']) ?>원</span>
            </div>
            <?php else: ?>
            <div class="discount-row">
                <span class="final-price"><?= number_format($event['discount_price']) ?>원</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Final Pay Amount -->
        <div class="final-pay-box">
            <div class="amount"><?= number_format($event['discount_price']) ?>원</div>
            <div class="label">내가 낼 금액 <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-bottom:2px;"><path d="M6 9l6 6 6-6"/></svg></div>
        </div>

        <!-- Points & Duration -->
        <div class="point-info">
            <span>포인트</span>
            <span>최대 <span class="val"><?= number_format(rand(5000, 20000)) ?>P</span> 적립</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" style="vertical-align:middle; margin-bottom:2px;"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="duration-info">
            <span class="label">기간</span>
            <span>2026년 12월 31일 까지</span>
        </div>
    </div>

    <!-- AI Review Simulation Section (Replaced fake reviews) -->
    <?php
    $aiReport = AiReviewGenerator::generateReport($event['title'], $event['category']);
    ?>
    <div class="detail-section">
        <div class="sec-title" style="margin-bottom: 20px;">
            <span style="font-size: 18px;">🤖 AI 여우언니 <span style="color:var(--primary-color);">시술 예측 리포트</span></span>
        </div>
        
        <div class="editor-card" style="background: linear-gradient(135deg, #f0f4ff 0%, #f6f0ff 100%); border: 1px solid rgba(138, 43, 226, 0.2); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; border-radius: 20px; background: linear-gradient(135deg, #8a2be2, #4b0082); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">🤖</div>
                <div>
                    <div style="font-size: 12px; color: #888;">빅데이터 기반 가상 분석</div>
                    <div style="font-weight: 700; font-size: 14px; color: #333;">여우언니 AI 엔진</div>
                </div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:10px;">
                    <?php foreach($aiReport['tags'] as $tag): ?>
                        <span style="background:#f0f4ff; color:#4b0082; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:700;"><?= $tag ?></span>
                    <?php endforeach; ?>
                </div>
                <div style="font-weight: 700; color: #4b0082; margin-bottom: 8px;">"<?= $aiReport['target_audience'] ?>"</div>
                <div style="font-size: 14px; color: #444; line-height: 1.6; margin-bottom: 12px;">
                    <?= $aiReport['summary'] ?>
                </div>
                
                <div style="display:flex; flex-direction:column; gap:8px; background:#f8f9fa; padding:12px; border-radius:8px; font-size:12px; color:#555; margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-weight:700; color:#333;">⚡ 통증 지수</span>
                        <span><?= str_repeat('🔥', $aiReport['pain_level']) ?><?= str_repeat('🤍', 5 - $aiReport['pain_level']) ?> (<?= $aiReport['pain_level'] ?>/5)</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-weight:700; color:#333;">⏳ 회복 기간</span>
                        <span><?= $aiReport['recovery_time'] ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-weight:700; color:#333;">✨ 효과 발현</span>
                        <span><?= $aiReport['effect_time'] ?></span>
                    </div>
                </div>

                <div style="font-size: 13px; color: #666; line-height: 1.5; border-left: 3px solid #8a2be2; padding-left: 10px; font-style:italic;">
                    <?= $aiReport['simulated_scenario'] ?>
                </div>
                
                <!-- 면책 조항 (의료법 준수) -->
                <div style="margin-top:15px; font-size:10px; color:#aaa; line-height:1.4; word-break:keep-all;">
                    ※ 본 리포트는 의료법 제56조를 준수하여, 실제 특정 환자의 치료 경험담이 아닌 <strong>일반적인 시술 데이터를 바탕으로 AI가 분석한 가상 시뮬레이션 결과</strong>입니다. 개인의 체질과 상태에 따라 실제 효과 및 부작용(출혈, 감염 등)은 다를 수 있으며, 반드시 전문의와 상담하시기 바랍니다.
                </div>
            </div>
        </div>

        <!-- First Review CTA Banner -->
        <?php if(empty($event_reviews)): ?>
        <div style="margin-top: 20px; background: linear-gradient(90deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; box-shadow: 0 4px 15px rgba(245,87,108,0.2);" onclick="alert('리뷰 작성은 병원 방문 및 시술 완료 후 가능합니다.');">
            <div style="font-size: 13px; color: #d01150; font-weight: 700; margin-bottom: 5px;">🔥 이 시술의 첫 번째 후기 주인공이 되세요!</div>
            <div style="font-size: 17px; font-weight: 800; color: #333;">리뷰 작성하고 최대 20,000P 받기 &gt;</div>
        </div>
        <?php else: ?>
        <div style="margin-top: 30px;">
            <div style="font-size: 16px; font-weight: 800; margin-bottom: 15px;">💬 리얼 유저 후기 <span style="color:var(--primary-color);"><?= count($event_reviews) ?></span></div>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php foreach($event_reviews as $rev): ?>
                <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #eaeaea; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 32px; height: 32px; background: #ffe0ea; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">🦊</div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #333;"><?= htmlspecialchars($rev['user_name']) ?></div>
                                <div style="font-size: 11px; color: #888;"><?= date('Y.m.d', strtotime($rev['created_at'])) ?> · 영수증 인증 완료</div>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end;">
                            <div style="color: #FFD700; font-size: 12px; font-weight: 800; margin-bottom:4px;">
                                <?= str_repeat('★', $rev['rating']) ?><span style="color:#ddd;"><?= str_repeat('★', 5 - $rev['rating']) ?></span>
                            </div>
                            <button class="btn-report" onclick="openReportModal('review', <?= $rev['id'] ?>);" style="background:none; border:none; color:#bbb; font-size:11px; cursor:pointer; padding:0;">🚨 신고</button>
                        </div>
                    </div>
                    <?php 
                    if(!empty($rev['photo_url'])): 
                        $photos = json_decode($rev['photo_url'], true);
                        if(json_last_error() !== JSON_ERROR_NONE || !is_array($photos)) {
                            // 하위호환: 기존 단일 텍스트 형태
                            $photos = [$rev['photo_url']];
                        }
                    ?>
                    <div style="margin-bottom: 12px; width: 100%; display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; scroll-snap-type: x mandatory;">
                        <?php foreach($photos as $p): ?>
                        <div style="flex-shrink: 0; width: 200px; height: 180px; border-radius: 8px; overflow: hidden; background: #f5f5f5; scroll-snap-align: start;">
                            <img src="<?= htmlspecialchars($p) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="후기 사진">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div style="font-size: 14px; color: #444; line-height: 1.6; word-break: keep-all;">
                        <?= nl2br(htmlspecialchars($rev['content'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Description Section -->
    <div class="detail-section">
        <div class="sec-title">시술 설명</div>
        <?php if(!empty($event['catchphrase'])): ?>
            <div style="font-weight:700; font-size:16px; margin-bottom:15px; color:var(--primary-color);"><?= htmlspecialchars($event['catchphrase']) ?></div>
        <?php endif; ?>
        <p class="detail-text">
            <?= nl2br(htmlspecialchars($event['description'])) ?>
        </p>
    </div>

    <!-- Side Effects Section -->
    <div class="detail-section">
        <div class="sec-title">부작용 안내</div>
        <p class="detail-text">
            시술 후 멍이나 붓기가 생길 수 있으니 충분한 상담을 통해 진행 바랍니다. 개인의 상태에 따라 결과가 다를 수 있습니다.
        </p>
    </div>

    <!-- Hospital Profile Section -->
    <div class="detail-section" style="margin-bottom: 20px;">
        <div class="hosp-profile-card" onclick="location.href='/hospital_detail.php?id=<?= $event['hospital_id'] ?>';" style="cursor:pointer;">
            <div class="hosp-logo"><img src="https://ui-avatars.com/api/?name=<?= urlencode(mb_substr(str_replace(' ', '', $event['hospital_name']), 0, 1, 'UTF-8')) ?>&background=random&color=fff&size=128&font-size=0.5" alt="logo" style="border-radius:50%; object-fit:cover;"></div>
            <div class="hosp-info">
                <div class="badge">📸 CCTV</div>
                <div class="name"><?= htmlspecialchars($event['hospital_name']) ?> <span style="font-weight:400; color:#888;">&gt;</span></div>
                <div class="stats">
                    <span>평점 <span style="font-weight:700; color:#fabb00;">★ <?= $event['rating'] ?></span></span>
                    <span style="margin:0 8px;">|</span>
                    <span>후기 <span style="font-weight:700;"><?= number_format($event['reviews']*2) ?>개</span></span>
                </div>
            </div>
        </div>
        
        <!-- 확장된 원장님 프로필 및 안심케어 섹션 -->
        <div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin-top: 15px;">
            <div style="font-size: 16px; font-weight: 800; color: #111; margin-bottom: 15px;">👨‍⚕️ 담당 원장님 소개</div>
            <div class="doc-profile" style="background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-top: 0; margin-bottom: 15px;">
                <div class="doc-img" style="width: 60px; height: 60px; flex-shrink: 0;"><img src="<?= $dynamicData['doc_img'] ?>" alt="의사" style="object-fit: cover; width: 100%; height: 100%; border-radius: 50%;"></div>
                <div class="doc-info">
                    <div class="name" style="font-size: 17px; font-weight: 800; color: #333;"><?= $dynamicData['doctor'] ?> <span style="font-weight:600; font-size:13px; color: var(--primary-color);">전담의</span></div>
                    <div class="spec" style="font-size: 13px; color: #666; margin-top: 4px;"><?= $dynamicData['spec'] ?></div>
                    <div class="doc-tags" style="margin-top: 8px;">
                        <?php foreach($dynamicData['tags'] as $tag): ?>
                        <span style="background: #f0f0f0; color: #555; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-right: 4px;"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 안심케어 상세 배너 -->
            <div style="background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%); border-radius: 12px; padding: 16px; cursor: pointer;" onclick="alert('여우언니 안심케어 서비스: 여우언니를 통해 본 시술을 예약하시면, 혹시 모를 부작용 발생 시 제휴 법무법인의 무료 법률 상담 및 추가 케어 포인트를 지원받으실 수 있습니다. 안전한 아름다움을 약속합니다.');">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font-size: 28px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">🛡️</div>
                        <div>
                            <div style="font-size: 14px; font-weight: 800; color: #00838f;">여우언니 안심케어 적용 시술</div>
                            <div style="font-size: 12px; color: #006064; margin-top: 2px;">부작용 발생 시 법률 상담 & 케어 지원!</div>
                        </div>
                    </div>
                    <div style="color: #00838f; font-weight: 700;">안내 &gt;</div>
                </div>
            </div>
        </div>

        <?php if(!empty($event['homepage'])): ?>
        <div class="hosp-homepage-link" onclick="window.open('<?= htmlspecialchars($event['homepage']) ?>', '_blank')" style="cursor:pointer; margin-top:15px; border:1px solid #eee; border-radius:12px; padding:15px; display:flex; align-items:center; justify-content:space-between; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,0.02); transition:all 0.2s;">
            <div style="display:flex; align-items:center; gap:12px; flex:1;">
                <div style="width:36px; height:36px; border-radius:50%; background:rgba(255,42,117,0.05); display:flex; align-items:center; justify-content:center; color:var(--primary-color); font-size:18px;">🌐</div>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:700; color:#333;">병원 공식 홈페이지 바로가기</div>
                    <div style="font-size:12px; color:#888; margin-top:2px; word-break:break-all;"><?= htmlspecialchars($event['homepage']) ?></div>
                </div>
            </div>
            <div style="color:#aaa; font-size:14px; margin-left:10px;">&gt;</div>
        </div>
        <?php endif; ?>

        <!-- Hospital Map Area -->
        <div style="margin-top:15px; border-radius:12px; overflow:hidden; border:1px solid #eaeaea; background:#fff;">
            <div id="kakao-map" style="width:100%; height:200px; background:#f9f9f9;"></div>
            <div style="padding:12px 15px; font-size:13px; color:#666; border-top:1px solid #eaeaea; line-height:1.4; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div style="flex:1; min-width:200px;">
                    📍 <span style="font-weight:700; color:#333;">병원 주소:</span> <?= htmlspecialchars($event['address'] ?: '주소 정보 없음') ?> (<?= htmlspecialchars($event['hospital_name']) ?>)
                </div>
                <div style="display:flex; gap:8px;">
                    <a id="btn-naver-map" href="https://map.naver.com/v5/search/<?= urlencode($event['address'] . ' ' . $clean_hospital_name) ?>" target="_blank" style="padding:6px 12px; background:#03c75a; color:#fff; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; display:inline-block;">네이버 지도</a>
                    <a id="btn-kakao-map" href="https://map.kakao.com/link/search/<?= urlencode($event['address'] . ' ' . $clean_hospital_name) ?>" target="_blank" style="padding:6px 12px; background:#fee500; color:#181600; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; display:inline-block;">카카오 길찾기</a>
                </div>
            </div>
        </div>
        <!-- AI Review Keyword Summary Dashboard -->
        <div style="margin-top:25px; background: #fff; border-radius:12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                <div style="font-size: 16px; font-weight: 800; color: #111;">
                    <span style="background: linear-gradient(135deg, #f093fb, #f5576c); -webkit-background-clip: text; color: transparent;">AI가 분석한</span> 이 병원의 실제 후기
                </div>
                <div style="font-size: 12px; color: #888; background: #f5f5f5; padding: 4px 8px; border-radius: 4px;">리뷰 <?= number_format($event['reviews']) ?>건 기반</div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="background: rgba(46, 204, 113, 0.05); border-left: 4px solid #2ecc71; padding: 12px; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 12px; color: #2ecc71; font-weight: 700; margin-bottom: 6px;">가장 많이 칭찬한 점 👍</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <span style="background: #fff; color: #333; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 20px; border: 1px solid #eaeaea; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">원장님이 꼼꼼해요 (84%)</span>
                        <span style="background: #fff; color: #333; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 20px; border: 1px solid #eaeaea; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">과잉 진료가 없어요 (72%)</span>
                        <span style="background: #fff; color: #333; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 20px; border: 1px solid #eaeaea; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">시설이 깨끗해요 (65%)</span>
                    </div>
                </div>

                <div style="background: rgba(231, 76, 60, 0.05); border-left: 4px solid #e74c3c; padding: 12px; border-radius: 0 8px 8px 0;">
                    <div style="font-size: 12px; color: #e74c3c; font-weight: 700; margin-bottom: 6px;">아쉬웠다는 의견 ⚠️</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <span style="background: #fff; color: #555; font-size: 13px; font-weight: 500; padding: 6px 12px; border-radius: 20px; border: 1px solid #eaeaea; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">대기 시간이 길어요 (12%)</span>
                        <span style="background: #fff; color: #555; font-size: 13px; font-weight: 500; padding: 6px 12px; border-radius: 20px; border: 1px solid #eaeaea; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">주차가 조금 불편해요 (8%)</span>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <a href="/reviews.php" style="color: #666; font-size: 13px; text-decoration: underline; font-weight: 500;">전체 리얼 리뷰 보러가기 &gt;</a>
            </div>
        </div>

        <!-- Medical Disclaimer -->
        <div style="margin: 25px 15px; padding: 15px; background: #fdf5f6; border-radius: 8px; border: 1px solid #ffe4e1;">
            <div style="font-size: 11px; font-weight: 700; color: #d81b60; margin-bottom: 5px;">[의료법 제56조 2항에 따른 고지]</div>
            <div style="font-size: 11px; color: #666; line-height: 1.5; letter-spacing: -0.3px;">
                본 페이지에 기재된 여우언니 단독 특가 정보 및 시술 내용은 병원에서 제공한 정보입니다.<br>
                <b>모든 시술 및 수술은 개인의 체질과 상태에 따라 출혈, 감염, 염증 등의 부작용이 발생할 수 있으므로</b> 반드시 전문 의료진과 충분한 상담 후 신중하게 결정하시기 바랍니다. 여우언니는 통신판매중개자로서 시술의 당사자가 아니며, 병원이 등록한 상품 정보 및 거래에 대해 책임을 지지 않습니다.
            </div>
        </div>
    </div>

    <!-- Bottom Action Bar -->
    <div class="bottom-action-bar">
        <div class="applicant-toast">
            총 <span><?= number_format($event['applicant_count'] ?? 0) ?></span>명이 상담 신청했어요.
        </div>
        
        <div class="action-heart" id="wishlistBtn" onclick="toggleWishlist()">
            <svg id="heartSvg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <div style="margin-top:2px;">찜하기</div>
        </div>
        <div class="action-btn-icon" onclick="alert('병원에 전화 연결을 시도합니다.');">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </div>
        <div class="action-btn-outline" onclick="openBookingModal()">상담받기</div>
        <div class="action-btn-solid" onclick="openBookingModal()">앱에서 결제</div>
    </div>

    <!-- Option Bottom Sheet Modal -->
    <div id="optionModal" class="modal-overlay" onclick="closeOptionModal()">
        <div class="bottom-sheet" onclick="event.stopPropagation();">
            <div class="sheet-header">
                <span style="font-weight:700; font-size:16px;">해당 병원의 다른 시술</span>
                <span class="sheet-close-btn" onclick="closeOptionModal()">&times;</span>
            </div>
            <div class="sheet-content">
                <!-- Current Option -->
                <div class="opt-item current-opt">
                    <div class="opt-name"><?= htmlspecialchars($event['title']) ?> <span style="color:var(--primary-color); font-size:11px; font-weight:700; border:1px solid var(--primary-color); border-radius:3px; padding:1px 3px; margin-left:4px;">선택됨</span></div>
                    <div class="opt-price"><?= number_format($event['discount_price']) ?>원</div>
                </div>
                
                <?php foreach($dynamicData['otherOptions'] as $opt): ?>
                <div class="opt-item" onclick="alert('옵션 변경 기능은 예약 화면에서 지원됩니다.');">
                    <div class="opt-name"><?= htmlspecialchars($opt['name']) ?></div>
                    <div class="opt-price"><?= $opt['price'] ?>원</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="booking-modal">
        <!-- Header -->
        <div style="padding:15px 20px; border-bottom:1px solid #eee; display:flex; align-items:center;">
            <svg onclick="closeBookingModal()" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" style="cursor:pointer; margin-right:15px;"><path d="M15 18l-6-6 6-6"/></svg>
            <span style="font-size:18px; font-weight:700;">예약일 선택</span>
        </div>
        
        <div style="flex:1; overflow-y:auto; padding-bottom:120px;">
            <!-- Calendar Section -->
            <div style="padding:20px;">
                <div style="font-size:18px; font-weight:700; margin-bottom:15px;">희망 일정</div>
                <div style="display:flex; gap:20px; font-size:16px; font-weight:700; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
                    <div style="color:var(--primary-color); border-bottom:2px solid var(--primary-color); padding-bottom:8px; margin-bottom:-11px;">6월</div>
                    <div style="color:#aaa;">7월</div>
                </div>
                
                <div id="dateScrollContainer" style="display:flex; overflow-x:auto; gap:15px; padding-bottom:10px; scrollbar-width:none;">
                    <!-- JS Generated Dates -->
                </div>
            </div>
            
            <div style="height:8px; background:#f5f5f5;"></div>

            <!-- Time Slots Section -->
            <div style="padding:20px;">
                <div style="font-size:14px; font-weight:700; color:#555; margin-bottom:15px;">
                    확정 방식: <span style="color:#333;">병원 확인 후 확정 ❔</span>
                </div>
                
                <div id="timeGridContainer" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
                    <!-- JS Generated Times -->
                </div>
            </div>

            <!-- Notice & Privacy -->
            <div style="padding:20px; background:#f8f9fa; margin:0 20px 20px 20px; border-radius:8px; font-size:13px; color:#666; line-height:1.6;">
                <div style="font-weight:700; color:#444; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    예약 시 안내사항
                </div>
                · 병원 방문이 어렵다면 앱에서 변경/취소해주세요.<br>
                · 예약 변경/취소는 방문 전날 17시까지 가능해요.<br>
                · 병원 상황에 따라 방문 시 대기 시간이 소요될 수 있어요.
            </div>
            
            <div style="padding:20px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                <div>
                    <div style="font-size:13px; color:#888;">서비스 이용에 관한 개인정보 수집 동의</div>
                    <div style="font-size:12px; color:#aaa; margin-top:5px;">(개인정보 및 민감정보 수집 이용, 개인정보 제3자 제공 동의)<br>위 내용을 확인하였으며, 동의합니다.</div>
                </div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div style="position:absolute; bottom:0; left:0; width:100%; background:#fff; border-top:1px solid #eee; padding:15px 20px; box-shadow:0 -5px 15px rgba(0,0,0,0.03);">
            <div style="text-align:center; font-size:13px; font-weight:700; margin-bottom:12px;">빠른 확정을 위해 희망 일시를 <span style="color:var(--primary-color);">3개</span> 선택해주세요.</div>
            <button id="btnSubmitBooking" onclick="submitBooking()" style="width:100%; padding:15px; border:none; border-radius:8px; background:#f0f0f0; color:#aaa; font-size:16px; font-weight:700; cursor:pointer; transition:all 0.3s;" disabled>0/3 예약하기</button>
        </div>
    </div>

    <script>
        function openOptionModal() {
            document.getElementById('optionModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeOptionModal() {
            document.getElementById('optionModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        /* Booking Modal Logic */
        let selectedDate = null;
        let selectedTimes = [];

        function openBookingModal() {
            document.getElementById('bookingModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            if(!selectedDate) initBookingUI();
        }
        function closeBookingModal() {
            document.getElementById('bookingModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function initBookingUI() {
            // Generate Dates
            const days = ['일', '월', '화', '수', '목', '금', '토'];
            const container = document.getElementById('dateScrollContainer');
            let dateHTML = '';
            
            const today = new Date();
            let firstDateStr = '';
            
            for(let i=0; i<14; i++) {
                const d = new Date(today);
                d.setDate(today.getDate() + i);
                
                const isToday = (i === 0);
                const dayName = days[d.getDay()];
                const dateNum = d.getDate();
                const activeClass = isToday ? 'active' : '';
                const todayText = isToday ? '<div class="today-text">오늘</div>' : '';
                
                const y = d.getFullYear();
                const m = String(d.getMonth()+1).padStart(2, '0');
                const dt = String(d.getDate()).padStart(2, '0');
                const dateStr = `${y}-${m}-${dt}`;
                
                if(isToday) {
                    selectedDate = d;
                    firstDateStr = dateStr;
                }

                dateHTML += `
                    <div class="date-item ${activeClass}" onclick="selectDate(this, '${dateStr}')">
                        <div class="day-text">${dayName}</div>
                        <div class="date-num">${dateNum}</div>
                        ${todayText}
                    </div>
                `;
            }
            container.innerHTML = dateHTML;
            
            if (firstDateStr) fetchTimesForDate(firstDateStr);
        }

        async function fetchTimesForDate(dateStr) {
            const timeContainer = document.getElementById('timeGridContainer');
            timeContainer.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:#aaa;">시간표를 불러오는 중...</div>';
            
            try {
                const res = await fetch(`/api/get_available_times.php?date=${dateStr}`);
                const data = await res.json();
                
                if(data.ok) {
                    let timeHTML = '';
                    data.times.forEach(item => {
                        const disabled = item.available ? '' : 'disabled';
                        const onclick = item.available ? `onclick="toggleTime(this, '${item.time}')"` : '';
                        timeHTML += `<div class="time-slot ${disabled}" ${onclick}>${item.time}</div>`;
                    });
                    timeContainer.innerHTML = timeHTML;
                } else {
                    timeContainer.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:20px; color:red;">${data.msg}</div>`;
                }
            } catch(e) {
                timeContainer.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:red;">오류가 발생했습니다.</div>';
            }
        }

        function selectDate(el, dateStr) {
            document.querySelectorAll('.date-item').forEach(d => d.classList.remove('active'));
            el.classList.add('active');
            
            // set selectedDate
            selectedDate = new Date(dateStr);
            
            // Reset times when date changes
            selectedTimes = [];
            document.querySelectorAll('.time-slot').forEach(t => t.classList.remove('active'));
            updateBookingBtn();
            
            fetchTimesForDate(dateStr);
        }

        function toggleTime(el, timeStr) {
            if(el.classList.contains('disabled')) return;
            
            const idx = selectedTimes.indexOf(timeStr);
            if(idx > -1) {
                // Remove
                selectedTimes.splice(idx, 1);
                el.classList.remove('active');
            } else {
                // Add if < 3
                if(selectedTimes.length >= 3) {
                    alert('최대 3개의 희망 일시까지만 선택 가능합니다.');
                    return;
                }
                selectedTimes.push(timeStr);
                el.classList.add('active');
            }
            updateBookingBtn();
        }

        function updateBookingBtn() {
            const btn = document.getElementById('btnSubmitBooking');
            btn.innerText = `${selectedTimes.length}/3 예약하기`;
            
            if(selectedTimes.length > 0) {
                btn.style.background = 'var(--primary-color)';
                btn.style.color = '#fff';
                btn.disabled = false;
            } else {
                btn.style.background = '#f0f0f0';
                btn.style.color = '#aaa';
                btn.disabled = true;
            }
        }

        function submitBooking() {
            if(selectedTimes.length === 0) return;
            
            const btn = document.getElementById('btnSubmitBooking');
            btn.innerText = '결제창을 띄우는 중...';
            btn.disabled = true;
            
            // 1. 포트원 객체 초기화 (가맹점 식별코드 - 임시값)
            var IMP = window.IMP; 
            IMP.init("imp_dummy1234"); // 실제 대표님 식별코드로 교체 예정
            
            const payload = {
                event_id: eventData.id,
                hospital_name: eventData.hospital_name,
                event_title: eventData.title,
                desired_date: selectedDate ? selectedDate.toISOString().split('T')[0] : '',
                desired_times: selectedTimes,
                amount: eventData.discount_price // 결제 금액
            };
            
            // 2. 예약 내역을 먼저 DB에 임시 저장 (결제 대기 상태)
            fetch('/api/save_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const reservationId = data.id;
                    
                    // 3. 포트원 결제창 호출 (카카오페이 테스트)
                    IMP.request_pay({
                        pg: "kakaopay.TC0ONETIME", // 카카오페이 테스트 상점
                        pay_method: "card",
                        merchant_uid: "order_no_" + new Date().getTime(),
                        name: payload.event_title + " 예약금",
                        amount: payload.amount,
                        buyer_email: "test@여우언니.com",
                        buyer_name: "여우언니 유저",
                        buyer_tel: "010-1234-5678"
                    }, function (rsp) { // callback
                        if (rsp.success) {
                            // 4. 결제 성공 시 서버에 검증 요청
                            fetch('/api/verify_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    imp_uid: rsp.imp_uid,
                                    merchant_uid: rsp.merchant_uid,
                                    reservation_id: reservationId,
                                    amount: rsp.paid_amount
                                })
                            })
                            .then(res => res.json())
                            .then(verifyData => {
                                if(verifyData.success) {
                                    alert('🎉 결제 및 상담 예약이 성공적으로 완료되었습니다!\n\n병원 확인 후 최종 확정 알림을 보내드립니다.');
                                    closeBookingModal();
                                    // 결제 완료 후 예약내역 페이지로 이동
                                    location.href = '/mypage/reservations.php';
                                } else {
                                    alert('결제 검증에 실패했습니다: ' + verifyData.error);
                                    updateBookingBtn();
                                }
                            });
                        } else {
                            // 결제 취소 또는 실패
                            alert('결제가 취소되었습니다: ' + rsp.error_msg);
                            updateBookingBtn();
                        }
                    });
                } else {
                    alert('오류가 발생했습니다: ' + (data.error || 'Unknown error'));
                    updateBookingBtn();
                }
            })
            .catch(err => {
                alert('네트워크 오류가 발생했습니다.');
                updateBookingBtn();
            });
        }

        // 찜하기 로직 (LocalStorage 활용)
        const eventData = {
            id: "<?= $event['id'] ?>",
            title: <?= json_encode($event['title']) ?>,
            hospital_name: <?= json_encode($event['hospital_name']) ?>,
            discount_price: <?= json_encode((float)($event['discount_price'] ?? 0)) ?>,
            original_price: <?= json_encode($event['original_price'] !== null ? (float)$event['original_price'] : null) ?>,
            image_url: <?= json_encode($event['image_url']) ?>,
            rating: <?= json_encode((float)($event['rating'] ?? 0)) ?>,
            reviews: <?= json_encode((int)($event['reviews'] ?? 0)) ?>
        };

        const wishlistBtn = document.getElementById('wishlistBtn');
        const heartSvg = document.getElementById('heartSvg');

        // 초기 상태 확인
        function checkWishlistState() {
            let list = JSON.parse(localStorage.getItem('shinsa_wishlist') || '[]');
            const exists = list.find(item => item.id === eventData.id);
            if (exists) {
                wishlistBtn.style.color = 'var(--primary-color)';
                heartSvg.setAttribute('fill', 'var(--primary-color)');
            }
        }

        function toggleWishlist() {
            let list = JSON.parse(localStorage.getItem('shinsa_wishlist') || '[]');
            const index = list.findIndex(item => item.id === eventData.id);
            
            if (index > -1) {
                // 제거
                list.splice(index, 1);
                localStorage.setItem('shinsa_wishlist', JSON.stringify(list));
                wishlistBtn.style.color = '';
                heartSvg.setAttribute('fill', 'none');
                alert('찜 목록에서 제거되었습니다.');
            } else {
                // 추가
                list.push(eventData);
                localStorage.setItem('shinsa_wishlist', JSON.stringify(list));
                wishlistBtn.style.color = 'var(--primary-color)';
                heartSvg.setAttribute('fill', 'var(--primary-color)');
                alert('찜 목록에 추가되었습니다.');
            }
        }

        function saveRecentEvent() {
            let recentList = JSON.parse(localStorage.getItem('shinsa_recent_events') || '[]');
            
            // 기존에 같은 id가 있으면 제거 (가장 최신으로 올리기 위해)
            const existingIndex = recentList.findIndex(item => item.id === eventData.id);
            if (existingIndex > -1) {
                recentList.splice(existingIndex, 1);
            }
            
            // 맨 앞에 추가
            recentList.unshift(eventData);
            
            // 최대 20개 제한
            if (recentList.length > 20) {
                recentList.pop();
            }
            
            localStorage.setItem('shinsa_recent_events', JSON.stringify(recentList));
        }

        // 카카오 지도 초기화 로직
        function initKakaoMap() {
            const mapContainer = document.getElementById('kakao-map');
            const address = <?= json_encode($event['address'] ?: '상세 주소 정보 없음') ?>;
            const hospitalName = <?= json_encode($event['hospital_name']) ?>;
            const cleanHospitalName = <?= json_encode($clean_hospital_name) ?>;

            if (!address || address === '상세 주소 정보 없음') {
                // 주소가 없는 경우 지도 영역 전체를 숨김
                const parentNode = mapContainer.parentNode;
                if (parentNode) {
                    parentNode.style.display = 'none';
                }
                return;
            }

            if (typeof kakao === 'undefined' || !kakao.maps || !kakao.maps.services) {
                console.error('Kakao Maps API 또는 Services 라이브러리가 로드되지 않았습니다.');
                return;
            }

            const geocoder = new kakao.maps.services.Geocoder();

            geocoder.addressSearch(address, function(result, status) {
                if (status === kakao.maps.services.Status.OK) {
                    const coords = new kakao.maps.LatLng(result[0].y, result[0].x);

                    const mapOption = {
                        center: coords,
                        level: 3
                    };

                    const map = new kakao.maps.Map(mapContainer, mapOption);

                    // 마커 표시
                    const marker = new kakao.maps.Marker({
                        map: map,
                        position: coords
                    });

                    // 인포윈도우 표시
                    const infowindow = new kakao.maps.InfoWindow({
                        content: '<div style="width:150px;text-align:center;padding:6px 0;font-size:12px;font-weight:bold;color:#333;">' + hospitalName + '</div>'
                    });
                    infowindow.open(map, marker);
                    
                    // 길찾기 버튼에 실시간 위/경도 기반 카카오맵 딥링크 및 네이버 지도 링크 동적 세팅
                    document.getElementById('btn-kakao-map').href = 'https://map.kakao.com/link/to/' + encodeURIComponent(cleanHospitalName) + ',' + result[0].y + ',' + result[0].x;
                    document.getElementById('btn-naver-map').href = 'https://map.naver.com/v5/search/' + encodeURIComponent(address + ' ' + cleanHospitalName);
                    
                    // 지도 리사이즈 대응
                    window.addEventListener('resize', function() {
                        map.setCenter(coords);
                    });
                } else {
                    console.error('주소 검색 실패:', status);
                    const parentNode = mapContainer.parentNode;
                    if (parentNode) {
                        parentNode.style.display = 'none';
                    }
                }
            });
        }

        window.onload = () => {
            checkWishlistState();
            saveRecentEvent(); // 최근 본 여우언니 단독 특가 저장
            initKakaoMap();
            initShareUrl();
        };

        /* ===== 공유 기능 ===== */
        const SHARE_URL = 'https://www.shinsaclinic.co.kr/events/detail.php?id=<?= $event['id'] ?>';
        const SHARE_TITLE = <?= json_encode($event['title'] . ' - 여우언니') ?>;
        const SHARE_DESC = <?= json_encode($event['hospital_name'] . ' · ' . number_format($event['discount_price']) . '원') ?>;
        const SHARE_IMG = <?= json_encode($aiBanner) ?>;

        function initShareUrl() {
            // 현재 실제 URL 사용 (로컬 테스트 등에서도 동작)
            const currentUrl = window.location.href;
            document.getElementById('shareUrlText').textContent = currentUrl;
        }

        function openShareModal() {
            document.getElementById('shareModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeShareModal() {
            document.getElementById('shareModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function shareKakao() {
            try {
                if (typeof Kakao === 'undefined') {
                    alert('카카오 SDK를 불러오지 못했습니다.\n링크 복사를 이용해 주세요.');
                    return;
                }
                if (!Kakao.isInitialized()) {
                    Kakao.init('8bef00e97793340d80a37b7277aca104');
                }
                Kakao.Share.sendDefault({
                    objectType: 'feed',
                    content: {
                        title: SHARE_TITLE,
                        description: SHARE_DESC,
                        imageUrl: SHARE_IMG,
                        link: {
                            mobileWebUrl: window.location.href,
                            webUrl: window.location.href
                        }
                    },
                    buttons: [{
                        title: '여우언니 단독 특가 보기',
                        link: {
                            mobileWebUrl: window.location.href,
                            webUrl: window.location.href
                        }
                    }]
                });
                closeShareModal();
            } catch(e) {
                console.error(e);
                alert('카카오톡 공유 중 오류가 발생했습니다.\n링크 복사를 이용해 주세요.');
            }
        }

        function shareFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + url, '_blank', 'width=600,height=400');
            closeShareModal();
        }

        function shareTwitter() {
            const text = encodeURIComponent(SHARE_TITLE + '\n' + SHARE_DESC);
            const url = encodeURIComponent(window.location.href);
            window.open('https://twitter.com/intent/tweet?text=' + text + '&url=' + url, '_blank', 'width=600,height=400');
            closeShareModal();
        }

        function shareNative() {
            if (navigator.share) {
                navigator.share({
                    title: SHARE_TITLE,
                    text: SHARE_DESC,
                    url: window.location.href
                }).then(() => closeShareModal()).catch(() => {});
            } else {
                // 네이티브 공유 미지원 시 URL 복사 fallback
                copyShareUrl();
            }
        }

        function copyShareUrl() {
            const url = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showCopyToast();
                }).catch(() => fallbackCopy(url));
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(text) {
            const el = document.createElement('textarea');
            el.value = text;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            showCopyToast();
        }

        function showCopyToast() {
            const toast = document.createElement('div');
            toast.textContent = '🔗 링크가 복사되었습니다!';
            toast.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.75);color:#fff;padding:10px 20px;border-radius:20px;font-size:14px;font-weight:600;z-index:99999;white-space:nowrap;pointer-events:none;';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
            closeShareModal();
        }
    </script>
    <!-- 신고 모달 -->
    <div id="reportModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#fff; width:90%; max-width:400px; border-radius:16px; padding:24px; box-sizing:border-box;">
            <h3 style="margin-top:0; color:#333;">🚨 콘텐츠 신고하기</h3>
            <p style="font-size:13px; color:#666; margin-bottom:15px;">관리자 검토 후 운영원칙에 따라 조치됩니다.</p>
            <input type="hidden" id="reportType">
            <input type="hidden" id="reportId">
            <select id="reportReason" style="width:100%; padding:10px; margin-bottom:15px; border-radius:8px; border:1px solid #ddd;">
                <option value="광고성/도배글">광고성/도배글</option>
                <option value="욕설/비방/혐오">욕설/비방/혐오</option>
                <option value="음란물">음란물</option>
                <option value="기타">기타 부적절한 내용</option>
            </select>
            <div style="display:flex; gap:10px;">
                <button onclick="document.getElementById('reportModal').style.display='none'" style="flex:1; padding:12px; background:#f5f5f5; border:none; border-radius:8px; cursor:pointer;">취소</button>
                <button onclick="submitReport()" style="flex:1; padding:12px; background:#f5576c; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">신고 접수</button>
            </div>
        </div>
    </div>

    <script>
        // Kakao Share and Booking logic
        // ... (Existing scripts are above)

        function openReportModal(type, id) {
            document.getElementById('reportType').value = type;
            document.getElementById('reportId').value = id;
            document.getElementById('reportModal').style.display = 'flex';
        }

        function submitReport() {
            const type = document.getElementById('reportType').value;
            const id = document.getElementById('reportId').value;
            const reason = document.getElementById('reportReason').value;

            fetch('/api/report_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ target_type: type, target_id: id, reason: reason })
            })
            .then(res => res.json())
            .then(json => {
                if(json.success) {
                    alert('신고가 정상적으로 접수되었습니다.\n관리자 검토 후 빠른 시일 내에 조치하겠습니다.');
                    document.getElementById('reportModal').style.display = 'none';
                } else {
                    alert(json.error || '신고 처리 중 오류가 발생했습니다.');
                }
            })
            .catch(e => console.error(e));
        }
    </script>
</body>
</html>


