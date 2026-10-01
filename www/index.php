<?php
// www/index.php
session_start();

// Auth Guard: 로그인도 안했고 둘러보기도 선택 안 한 경우
if (!isset($_SESSION['is_logged_in']) && !isset($_SESSION['is_guest'])) {
    header("Location: /login.php");
    exit;
}

require_once 'config/db_connect.php';

// events.json에서 여우언니 단독 특가 데이터 가져오기
$all_events = [];
$jsonPath = __DIR__ . '/events.json';
if (file_exists($jsonPath)) {
    // 13MB 파일 로딩으로 인한 PHP 메모리 초과(Fatal error) 방지 임시 처리
    // $jsonData = json_decode(file_get_contents($jsonPath), true);
    $jsonData = ['data' => []];
    if (isset($jsonData['data'])) {
        // 중복 방지를 위해 전체 데이터를 섞고, 병원명 기준으로 중복되지 않는 10개 추출
        shuffle($jsonData['data']);
        $seen_hospitals = [];
        foreach ($jsonData['data'] as $item) {
            $hospital_name = $item['competitor_name'] ?? '병원';
            // 같은 병원 여우언니 단독 특가는 1개만 표시 (다양성 확보)
            if (in_array($hospital_name, $seen_hospitals))
                continue;
            $seen_hospitals[] = $hospital_name;

            $discountPrice = preg_replace('/[^0-9]/', '', $item['price'] ?? '0');
            $originalPrice = preg_replace('/[^0-9]/', '', $item['original_price'] ?? '');

            $imgUrl = trim($item['event_image_url'] ?? '');
            if (strpos($imgUrl, 'http') !== 0 && strpos($imgUrl, '/') === 0) {
                $imgUrl = 'https://mediicon03.mycafe24.com' . $imgUrl;
            } elseif (empty($imgUrl)) {
                $imgUrl = '/static/images/skin.jpg';
            }

            $all_events[] = [
                'id' => $item['id'] ?? count($all_events),
                'hospital_name' => $hospital_name,
                'title' => $item['event_name'] ?? '여우언니 단독 특가',
                'discount_price' => $discountPrice,
                'original_price' => $originalPrice,
                'image_url' => $imgUrl,
                'rating' => 4.5 + (rand(-5, 4) / 10),
                'reviews' => rand(10, 500)
            ];

            if (count($all_events) >= 10)
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>여우언니 - 미용의료 1등 플랫폼</title>
    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f5576c">
    <link rel="apple-touch-icon" href="/static/images/icons/icon-192x192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="여우언니">
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        /* ===== 홈 카테고리 탭 (2줄 격자형) ===== */
        .home-cat-tabs {
            display: flex;
            flex-wrap: wrap;
            padding: 15px 5px 10px;
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
        }
        .home-cat-tab {
            width: 20%; /* 한 줄에 5개씩 (총 10개) */
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 8px 0 10px;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .home-cat-tab .cat-tab-icon {
            font-size: 24px;
            line-height: 1;
            margin-bottom: 2px;
        }
        .home-cat-tab.active {
            color: var(--primary-color);
            font-weight: 700;
        }
        .home-cat-tab.active .cat-tab-icon {
            transform: translateY(-2px);
            transition: transform 0.2s;
        }
        .home-cat-tab:hover { color: var(--primary-color); }
        /* ===== END 홈 카테고리 탭 ===== */

        /* ===== 검색 자동완성 드롭다운 ===== */
        .search-wrapper {
            position: relative;
        }
        .search-autocomplete {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            z-index: 500;
            overflow: hidden;
            border: 1px solid #f0f0f0;
            animation: dropIn 0.18s ease;
        }
        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .search-autocomplete.visible { display: block; }
        .ac-section-title {
            font-size: 11px; font-weight: 700; color: #aaa;
            padding: 12px 16px 6px;
            letter-spacing: 0.5px;
        }
        .ac-item {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px;
            cursor: pointer;
            transition: background 0.15s;
            font-size: 14px; color: #333;
        }
        .ac-item:hover, .ac-item.focused { background: #fff5f7; }
        .ac-item-icon { font-size: 16px; width: 22px; text-align: center; flex-shrink: 0; }
        .ac-item-text { flex: 1; }
        .ac-item-text mark {
            background: none;
            color: var(--primary-color);
            font-weight: 700;
        }
        .ac-item-category {
            font-size: 11px; color: #bbb;
            background: #f5f5f5;
            border-radius: 8px;
            padding: 2px 8px;
        }
        .ac-popular-tags {
            display: flex; flex-wrap: wrap; gap: 7px;
            padding: 8px 16px 14px;
        }
        .ac-pop-tag {
            display: inline-flex; align-items: center; gap: 4px;
            background: #fff0f5;
            color: var(--primary-color);
            border: 1px solid #ffe0ea;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 13px; font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .ac-pop-tag:hover { background: var(--primary-color); color: #fff; }
        .ac-divider { height: 1px; background: #f5f5f5; margin: 0 16px; }
        /* ===== END 검색 자동완성 ===== */

        /* Secret Style Region Modal */
        .region-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .region-modal-overlay.active {
            display: block;
        }

        .region-bottom-sheet {
            display: flex;
            flex-direction: column;
            position: fixed;
            bottom: -100%;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            height: 85vh;
            background: #fff;
            border-radius: 20px 20px 0 0;
            z-index: 2001;
            transition: bottom 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-sizing: border-box;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .region-bottom-sheet.active {
            bottom: 0;
        }

        .r-header {
            padding: 15px 20px 10px;
            display: flex;
            flex-direction: column;
            border-bottom: 1px solid #f0f0f0;
        }

        .r-drag-handle {
            width: 40px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin: 0 auto 15px;
        }

        .r-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .r-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .r-close {
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }

        .r-sub-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .r-reset-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            color: #666;
            cursor: pointer;
            white-space: nowrap;
        }

        .r-tags-scroll {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding-bottom: 5px;
        }

        .r-tags-scroll::-webkit-scrollbar {
            display: none;
        }

        .r-tag {
            display: inline-flex;
            align-items: center;
            background: #f5f5f5;
            border-radius: 16px;
            padding: 6px 12px;
            font-size: 13px;
            color: #333;
            white-space: nowrap;
        }

        .r-tag-close {
            margin-left: 6px;
            color: #999;
            cursor: pointer;
            font-size: 14px;
        }

        .r-body {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .r-sidebar {
            width: 100px;
            background: #fcfcfc;
            overflow-y: auto;
            border-right: 1px solid #f0f0f0;
        }

        .r-side-item {
            padding: 16px 0;
            text-align: center;
            font-size: 15px;
            color: #666;
            cursor: pointer;
            position: relative;
        }

        .r-side-item.active {
            background: rgba(245, 87, 108, 0.08);
            color: var(--primary-color);
            font-weight: 700;
        }

        .r-side-item .dot {
            display: none;
            position: absolute;
            top: 10px;
            right: 15px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--primary-color);
        }

        .r-side-item.has-selection .dot {
            display: block;
        }

        .r-content {
            flex: 1;
            overflow-y: auto;
            padding: 10px 20px;
            background: #fff;
        }

        .r-check-item {
            display: flex;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f9f9f9;
            cursor: pointer;
        }

        .r-check-box {
            width: 20px;
            height: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .r-check-item.active .r-check-box {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .r-check-item.active .r-check-box::after {
            content: '✓';
            color: #fff;
            font-size: 14px;
        }

        .r-check-text {
            font-size: 15px;
            color: #333;
        }

        .r-footer {
            padding: 15px 20px calc(15px + env(safe-area-inset-bottom, 0));
            background: #fff;
            border-top: 1px solid #eee;
        }

        .r-submit-btn {
            width: 100%;
            padding: 16px;
            background: #f5f5f5;
            color: #aaa;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .r-submit-btn.active {
            background: var(--primary-color);
            color: #fff;
        }

        .ai-face-banner {
            margin: 0 20px 20px 20px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: white;
            box-shadow: 0 4px 10px rgba(245, 87, 108, 0.3);
            position: relative;
            overflow: hidden;
        }

        .ai-face-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(to bottom right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 100%);
            transform: rotate(45deg);
            animation: shimmer 3s infinite linear;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .ai-banner-text {
            font-weight: 700;
            font-size: 15px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .ai-banner-sub {
            font-size: 12px;
            font-weight: 400;
            opacity: 0.9;
            margin-top: 2px;
        }

        .ai-banner-icon {
            font-size: 24px;
        }

        /* Wishlist Button */
        .event-card-home {
            position: relative;
        }

        .wish-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ccc;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 10;
            transition: transform 0.2s;
        }

        .wish-btn:active {
            transform: scale(0.9);
        }

        .wish-btn.active {
            color: #f5576c;
        }

        /* --- Beauty Secret Exclusive Splash Screen --- */
        #secret-splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #fff0f5 0%, #ffffff 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .splash-logo-container {
            position: relative;
            width: 90px;
            height: 90px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 40%;
            /* 약간 각진 부드러운 형태 */
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(245, 87, 108, 0.15), inset 0 0 0 1px rgba(255, 255, 255, 0.7);
            animation: floatHeart 2.5s ease-in-out infinite alternate;
        }

        .splash-logo-container::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 40%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            z-index: -1;
            filter: blur(20px);
            opacity: 0.4;
            animation: pulseGlow 3s infinite alternate;
        }

        .splash-heart {
            font-size: 45px;
            color: var(--primary-color);
            text-shadow: 0 4px 10px rgba(245, 87, 108, 0.3);
        }

        .splash-text {
            margin-top: 30px;
            font-size: 26px;
            font-weight: 900;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1.5px;
            opacity: 0;
            transform: translateY(15px);
            animation: slideUpFade 0.8s 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .splash-sub {
            margin-top: 8px;
            font-size: 11px;
            color: #888;
            font-weight: 600;
            letter-spacing: 4px;
            opacity: 0;
            transform: translateY(10px);
            animation: slideUpFade 0.8s 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes floatHeart {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-12px);
            }
        }

        @keyframes pulseGlow {
            0% {
                opacity: 0.2;
                transform: scale(0.95);
            }

            100% {
                opacity: 0.5;
                transform: scale(1.05);
            }
        }

        @keyframes slideUpFade {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        body.splash-active {
            overflow: hidden;
        }

        #secret-splash.hide {
            opacity: 0;
            transform: scale(1.03);
            pointer-events: none;
        }
    </style>
</head>

<body style="background-color: #f5f5f5;" class="splash-active">
    <!-- Premium Splash Screen -->
    <div id="secret-splash">
        <div class="splash-logo-container">
            <?php $fox_logo_size='140px'; include 'fox_logo_animated.php'; ?>
        </div>
        <div class="splash-text">여우언니</div>
        <div class="splash-sub">PREMIUM BEAUTY</div>
    </div>

    <!-- Header -->
    <header class="home-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:nowrap; overflow:hidden;">
        <div class="logo" onclick="location.href='/index.php'" style="cursor:pointer; white-space:nowrap; flex-shrink:0; display:flex; align-items:center; height: 35px;">
            <div style="margin-right: 5px; margin-top: 5px; margin-left: -10px; display: flex; align-items: center; justify-content: center; width: 45px;">
                <?php $fox_logo_size='80px'; include 'fox_logo_animated.php'; ?>
            </div>
            <span style="font-weight: 800; font-size: 20px;">여우언니</span>
        </div>
        <div class="header-right" style="display:flex; align-items:center; gap:10px; flex-wrap:nowrap; flex-shrink:1; overflow:hidden;">
            <a href="/ai_chat.php" class="ai-ask-btn" style="white-space:nowrap; padding:6px 10px; font-size:13px; flex-shrink:0;">✨ AI 상담</a>
            
            <?php 
                $display_name = $_SESSION['user_name'] ?? '카리';
                // 이메일 형식이 들어가 있으면 무조건 '카리'로 고정
                if(strpos($display_name, '@') !== false) {
                    $display_name = '카리';
                }
            ?>
            <?php if(isset($_SESSION['is_logged_in'])): ?>
                <!-- 로그아웃 기능은 텍스트 숨김 처리하고 환영 문구에 포함시킴 -->
                <a href="/login.php?action=logout" style="font-size:14px; font-weight:800; color:#333; margin-left:4px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden; flex-shrink:1; text-decoration:none; cursor:pointer;" title="클릭 시 로그아웃">
                    <?= htmlspecialchars($display_name) ?>님 환영해요
                </a>
            <?php else: ?>
                <a href="/login.php" style="font-size:14px; font-weight:700; color:#555; text-decoration:none; padding:6px 12px; background:#f0f0f0; border-radius:15px; white-space:nowrap; flex-shrink:0;">로그인</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="home-main" style="padding-top: 15px;">
        <!-- Banners Moved Below Quick Links -->

        <!-- AI Face Analysis Banner (Moved to top priority) -->
        <a href="/ai_face_analysis.php" class="ai-face-banner" style="margin-top: 15px;">
            <div>
                <div class="ai-banner-text">✨ 여우언니 AI가 내 얼굴을 스캔한다면?</div>
                <div class="ai-banner-sub">정면 사진 한 장으로 맞춤 시술 추천받기</div>
            </div>
            <div class="ai-banner-icon">📸</div>
        </a>

        <!-- Promo Banner Carousel -->
        <div class="promo-banner-carousel" id="promoCarousel" style="padding: 0 15px 10px; overflow: visible;">
            <div class="carousel-inner" id="carouselInner" style="gap: 12px; padding-bottom: 15px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; border-radius: 0;">
                <?php
                // 상위 5개를 배너로 사용
                $banner_events = array_slice($all_events, 0, 5);
                foreach ($banner_events as $idx => $bevent):
                    // 할인율 계산 (없으면 임의 부여)
                    $discount_rate = 0;
                    if ($bevent['original_price'] > 0 && $bevent['discount_price'] > 0) {
                        $discount_rate = round((($bevent['original_price'] - $bevent['discount_price']) / $bevent['original_price']) * 100);
                    }
                    if ($discount_rate <= 0)
                        $discount_rate = rand(10, 50);
                    ?>
                    <a href="/events/detail.php?id=<?= $bevent['id'] ?>" class="premium-carousel-card" style="scroll-snap-align: center; flex: 0 0 calc(100% - 20px); border-radius: 20px; overflow: hidden; background: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.06); text-decoration: none; position: relative; display: flex; flex-direction: column; border: 1px solid rgba(0,0,0,0.03);">
                        <!-- 이미지 영역 (가로형 크롭) -->
                        <div style="width: 100%; aspect-ratio: 16/10; position: relative; overflow: hidden; background: #f0f0f5;">
                            <img src="<?= $bevent['image_url'] ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; object-position: center top;" onerror="this.src='/static/images/skin.jpg';">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.3) 0%, transparent 40%);"></div>
                            
                            <div style="position: absolute; top: 15px; right: 15px; background: #FF2A75; color: #fff; font-weight: 800; font-size: 14px; padding: 6px 12px; border-radius: 20px; box-shadow: 0 4px 10px rgba(255,42,117,0.3);">
                                <?= $discount_rate ?>% OFF
                            </div>
                        </div>
                        
                        <!-- 텍스트 정보 영역 -->
                        <div style="padding: 18px 20px;">
                            <div style="font-size: 13px; color: #888; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFF0F5; color: #FF2A75; padding: 2px 8px; border-radius: 4px; font-size: 11px;">단독특가</span> 
                                <?= htmlspecialchars($bevent['hospital_name']) ?>
                            </div>
                            <div style="font-size: 18px; font-weight: 800; color: #111; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($bevent['title']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="banner-pagination" id="carouselPagination">1 / <?= count($banner_events) ?></div>
        </div>

        <!-- Quick Links -->
        <div class="quick-menu premium-menu">
            <a href="/ai_face_analysis.php" class="quick-item">
                <div class="quick-icon premium-icon ai-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)"
                        stroke-width="1.5">
                        <path
                            d="M12 2a2 2 0 0 1 2 2c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2zM4 10h16v12H4zM4 10l4-6M20 10l-4-6M10 14h4v4h-4z" />
                    </svg>
                </div>
                <span>AI 여우 스캔</span>
            </a>
            <a href="/events/list.php?keyword=VIP" class="quick-item">
                <div class="quick-icon premium-icon vip-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)"
                        stroke-width="1.5">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                        </polygon>
                    </svg>
                </div>
                <span>여우들의 시크릿</span>
            </a>
            <a href="/my_skin_custom.php" class="quick-item">
                <div class="quick-icon premium-icon care-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)"
                        stroke-width="1.5">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                        </path>
                    </svg>
                </div>
                <span>여우결 맞춤피부</span>
            </a>
            <a href="/virtual_consult.php" class="quick-item">
                <div class="quick-icon premium-icon talk-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)"
                        stroke-width="1.5">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <span>비대면 견적</span>
            </a>
            <a href="/community.php" class="quick-item">
                <div class="quick-icon premium-icon trend-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)"
                        stroke-width="1.5">
                        <path d="M23 6l-9.5 9.5-5-5L1 18"></path>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
                <span>여우들의 픽</span>
            </a>
        </div>

        <!-- Search Box with Autocomplete -->
        <div class="search-container search-wrapper" style="margin-top: 0;" id="searchWrapper">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div class="region-selector" onclick="openRegionModal()" style="cursor: pointer;">
                    <span style="color: var(--primary-color);">📍</span> <span id="currentRegionText">강남역/신논현역/양재 외 1개</span>
                    <span class="arrow-down">⌵</span>
                </div>
                <a href="/hospital_map.php" style="font-size: 12px; color: var(--primary-color); font-weight: 700; text-decoration: none; background: #fff0f5; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 4px;">🗺️ 내 주변 지도</a>
            </div>
            <form action="/events/list.php" method="GET" class="search-input-box" id="mainSearchForm" autocomplete="off">
                <input type="text" name="keyword" id="mainSearchInput" placeholder="시술명을 입력해보세요 (예: 보톡스)" class="search-input"
                    style="flex:1; border:none; outline:none; background:transparent;" autocomplete="off">
                <span style="color: var(--primary-color); margin-left: 8px; cursor: pointer;"
                    onclick="document.getElementById('mainSearchForm').submit();">🔍</span>
            </form>

            <!-- 자동완성 드롭다운 -->
            <div class="search-autocomplete" id="searchAutocomplete">
                <!-- 인기 검색어 (기본 노출) -->
                <div id="acPopularSection">
                    <div class="ac-section-title">🔥 인기 시술 검색어</div>
                    <div class="ac-popular-tags">
                        <span class="ac-pop-tag" onclick="doSearch('보톡스')">💉 보톡스</span>
                        <span class="ac-pop-tag" onclick="doSearch('필러')">✨ 필러</span>
                        <span class="ac-pop-tag" onclick="doSearch('리프팅')">🔊 리프팅</span>
                        <span class="ac-pop-tag" onclick="doSearch('쌍꺼풀')">👁️ 쌍꺼풀</span>
                        <span class="ac-pop-tag" onclick="doSearch('아쿠아필')">💧 아쿠아필</span>
                        <span class="ac-pop-tag" onclick="doSearch('코성형')">👃 코성형</span>
                        <span class="ac-pop-tag" onclick="doSearch('피부관리')">🌸 피부관리</span>
                        <span class="ac-pop-tag" onclick="doSearch('제모')">✂️ 제모</span>
                        <span class="ac-pop-tag" onclick="doSearch('탈모')">💇 탈모</span>
                        <span class="ac-pop-tag" onclick="doSearch('다이어트')">🏃 다이어트</span>
                    </div>
                </div>
                <!-- 추천 키워드 결과 (타이핑 시 노출) -->
                <div id="acResultSection" style="display:none;">
                    <div class="ac-section-title">🔍 추천 검색어</div>
                    <div id="acResultList"></div>
                </div>
            </div>
        </div>

        <!-- 세부 카테고리 탭 (홈 필터) -->
        <div class="home-cat-tabs" id="homeCatTabs">
            <div class="home-cat-tab active" data-cat="전체" onclick="filterHomeCategory(this,'전체')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                </span>전체
            </div>
            <div class="home-cat-tab" data-cat="눈" onclick="filterHomeCategory(this,'눈')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>눈
            </div>
            <div class="home-cat-tab" data-cat="코" onclick="filterHomeCategory(this,'코')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3 C11 7 11 10 16 15 C17 16 16 18 14 18 C11 18 9 20 9 22" /></svg>
                </span>코
            </div>
            <div class="home-cat-tab" data-cat="쁘띠" onclick="filterHomeCategory(this,'쁘띠')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2l4 4-4 4-4-4z"></path><path d="M14 6l-8 8-2 6 6-2 8-8z"></path><path d="M8 12l4 4"></path></svg>
                </span>쁘띠
            </div>
            <div class="home-cat-tab" data-cat="리프팅" onclick="filterHomeCategory(this,'리프팅')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10c0 5 3 9 7 11 4-2 7-6 7-11"></path><path d="M9 5l-3-3-3 3"></path><path d="M15 5l3-3 3 3"></path></svg>
                </span>리프팅
            </div>
            <div class="home-cat-tab" data-cat="피부" onclick="filterHomeCategory(this,'피부')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
                </span>피부
            </div>
            <div class="home-cat-tab" data-cat="바디" onclick="filterHomeCategory(this,'바디')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l-2 7 3 11H7l3-11z"></path></svg>
                </span>바디
            </div>
            <div class="home-cat-tab" data-cat="성형" onclick="filterHomeCategory(this,'성형')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </span>성형
            </div>
            <div class="home-cat-tab" data-cat="두피/탈모" onclick="filterHomeCategory(this,'두피/탈모')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </span>두피/탈모
            </div>
            <div class="home-cat-tab" data-cat="반영구" onclick="filterHomeCategory(this,'반영구')">
                <span class="cat-tab-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="url(#pink-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                </span>반영구
            </div>
        </div>

        <!-- Real Reviews Banner -->
        <a href="/reviews.php" class="ai-face-banner" style="background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%); margin-bottom: 15px;">
            <div>
                <div class="ai-banner-text" style="color: #c2185b;">📸 예뻐진 그녀들의 리얼리뷰</div>
                <div class="ai-banner-sub" style="color: #880e4f; font-weight: 600;">광고 없는 진짜 전후 사진 보러가기</div>
            </div>
            <div class="ai-banner-icon">✨</div>
        </a>

        <!-- SVG Definitions for Gradients -->
        <svg style="width:0;height:0;position:absolute;" aria-hidden="true" focusable="false">
            <linearGradient id="pink-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="var(--primary-color)" />
                <stop offset="100%" stop-color="var(--secondary-color)" />
            </linearGradient>
        </svg>

        <!-- 최근 본 시술 (로컬 보관소 기반) -->
        <div class="recommend-section" id="recentEventsSection" style="display: none; margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <h3 class="section-title" style="margin-bottom:15px; font-size: 17px; font-weight:700;">🕒 최근 본 시술</h3>
            <div id="recentEventsContainer" style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; -ms-overflow-style: none;">
                <!-- JS Dynamic Load -->
            </div>
        </div>

        <!-- 여우언니 단독 특가 카드 섹션 (카테고리 탭 필터와 연동) -->
        <div class="recommend-section">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                <h3 class="section-title" style="margin-bottom:0;"><span style="color:var(--primary-color);"><?= htmlspecialchars($display_name) ?></span>님을 위한 여우언니 단독 특가</h3>
                <span id="homeCatResultCount" style="font-size:12px; color:#bbb;"></span>
            </div>

            <div class="event-list-home" id="homeEventList">
                <?php foreach ($all_events as $event):
                    // 카테고리 키워드 매핑 (제목 기반 자동 분류)
                    $title_lower = mb_strtolower($event['title'], 'UTF-8');
                    $home_cat = '기타';
                    if (preg_match('/쌍꺼풀|눈매|트임|눈밑|다크서클|안검/', $title_lower)) $home_cat = '눈';
                    elseif (preg_match('/코성형|코필러|코끝|매부리|복코|하이코/', $title_lower)) $home_cat = '코';
                    elseif (preg_match('/보톡스|필러|리쥬란|스킨부스터|쥬베룩|스컬트라|지방분해|윤곽주사/', $title_lower)) $home_cat = '쁘띠';
                    elseif (preg_match('/리프팅|울쎄라|써마지|슈링크|실리프팅|고주파|초음파|텐테라/', $title_lower)) $home_cat = '리프팅';
                    elseif (preg_match('/피부|여드름|기미|모공|토닝|레이저|아쿠아필|스케일링|프락셀|홍조/', $title_lower)) $home_cat = '피부';
                    elseif (preg_match('/지방흡입|다이어트|바디|종아리|승모근|가슴|힙업|제모/', $title_lower)) $home_cat = '바디';
                    elseif (preg_match('/탈모|두피|모발이식/', $title_lower)) $home_cat = '두피/탈모';
                    elseif (preg_match('/반영구|눈썹문신|입술문신|아이라인/', $title_lower)) $home_cat = '반영구';
                    elseif (preg_match('/성형|윤곽|양악|광대|사각턱|안면/', $title_lower)) $home_cat = '성형';
                ?>
                    <a href="/events/detail.php?id=<?= $event['id'] ?>" class="event-card-home" data-home-cat="<?= htmlspecialchars($home_cat) ?>">
                        <!-- 찜하기 버튼 -->
                        <div class="wish-btn"
                            onclick="toggleWishlist(event, <?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= htmlspecialchars(addslashes($event['hospital_name'])) ?>', '<?= addslashes($event['image_url']) ?>', <?= $event['discount_price'] ?>, <?= $event['original_price'] ?: 0 ?>, <?= $event['rating'] ?>, <?= $event['reviews'] ?>)"
                            data-id="<?= $event['id'] ?>">🤍</div>

                        <img src="<?= $event['image_url'] ?>" alt="<?= htmlspecialchars($event['title']) ?>"
                            onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2280%22%20height%3D%2280%22%20viewBox%3D%220%200%2080%2080%22%3E%3Crect%20fill%3D%22%23eee%22%20width%3D%2280%22%20height%3D%2280%22%2F%3E%3C%2Fsvg%3E'">
                        <div class="event-info">
                            <div class="badge"><?= htmlspecialchars($home_cat) ?></div>
                            <div class="hospital-name"><?= htmlspecialchars($event['hospital_name']) ?></div>
                            <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                            <div class="price">
                                <?= number_format((float) $event['discount_price']) ?>원
                                <?php if ($event['original_price'] > $event['discount_price']): ?>
                                    <span style="font-size:12px; color:#aaa; text-decoration:line-through; font-weight:400;"><?= number_format((float) $event['original_price']) ?>원</span>
                                <?php endif; ?>
                            </div>
                            <div class="meta-info">
                                <span><span class="star">★</span> <?= $event['rating'] ?></span>
                                <span>리뷰 <?= number_format($event['reviews']) ?>개</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if (empty($all_events)): ?>
                    <div style="padding: 20px; text-align: center; color: #888;">데이터를 불러오지 못했습니다.</div>
                <?php endif; ?>
            </div>

            <!-- 카테고리 필터 결과 없음 -->
            <div id="homeCatNoResult" style="display:none; text-align:center; padding:40px 20px; color:#999;">
                <div style="font-size:40px; margin-bottom:12px;">🔍</div>
                <div style="font-size:14px; font-weight:600; color:#666;" id="homeCatNoResultText"></div>
                <div style="font-size:13px; margin-top:6px;">다른 카테고리를 선택해보세요</div>
            </div>
        </div>
    </main>

    <!-- 법적 필수 고지 및 사업자 정보 (푸터) -->
    <footer
        style="background-color: #f8f9fa; padding: 25px 20px; font-size: 11px; color: #888; line-height: 1.6; padding-bottom: 90px; border-top: 1px solid #eaeaea;">
        <div style="font-weight: 700; color: #555; margin-bottom: 8px; font-size: 12px;">(주)여우언니</div>
        대표이사: 김신사 | 사업자등록번호: 123-45-67890<br>
        통신판매업신고: 제2026-서울강남-0000호<br>
        주소: 서울특별시 강남구 테헤란로 123, 신사타워 4층<br>
        고객센터: 1588-0000 | 이메일: cs@shinsa.co.kr<br><br>
        <span style="font-weight: 600;">(주)여우언니는 통신판매중개자로서 통신판매의 당사자가 아니며, 입점 병원이 등록한 의료정보, 예약, 시술 결과 및 이와 관련한 일체의 법적
            책임은 해당 병원에게 있습니다.</span><br><br>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
            <a href="/mypage/terms.php" style="color: #666; text-decoration: none; font-weight: 600;">이용약관</a> |
            <a href="/mypage/privacy.php" style="color: #666; text-decoration: none; font-weight: 600;">개인정보처리방침</a> |
            <a href="/mypage/location_terms.php"
                style="color: #666; text-decoration: none; font-weight: 600;">위치기반약관</a> |
            <a href="/mypage/partnership.php"
                style="color: var(--primary-color); text-decoration: none; font-weight: 700;">입점문의</a>
        </div>
    </footer>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/index.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span style="margin-top: 4px;">홈</span>
        </a>
        <a href="/events/list.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span style="margin-top: 4px;">검색</span>
        </a>
        <a href="/mypage/reviews.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span style="margin-top: 4px;">리뷰</span>
        </a>
        <a href="/mypage.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span style="margin-top: 4px;">마이 여우</span>
        </a>
    </nav>

    <!-- Region Selection Modal -->
    <div class="region-modal-overlay" id="regionOverlay" onclick="closeRegionModal()"></div>
    <div class="region-bottom-sheet" id="regionSheet">
        <!-- Header -->
        <div class="r-header">
            <div class="r-drag-handle"></div>
            <div class="r-title-row">
                <div class="r-title">지역</div>
                <div class="r-close" onclick="closeRegionModal()">&times;</div>
            </div>
            <div class="r-sub-header">
                <div class="r-reset-btn" onclick="resetRegions()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                        <path d="M3 3v5h5" />
                    </svg> 초기화
                </div>
                <div class="r-tags-scroll" id="rTagsContainer">
                    <!-- Selected Tags will be injected here -->
                </div>
            </div>
        </div>

        <!-- Split Body -->
        <div class="r-body">
            <!-- Sidebar (Major Regions) -->
            <div class="r-sidebar" id="rSidebar">
                <!-- Generated via JS -->
            </div>
            <!-- Content (Sub Regions) -->
            <div class="r-content" id="rContent">
                <!-- Generated via JS -->
            </div>
        </div>

        <!-- Footer -->
        <div class="r-footer">
            <button class="r-submit-btn" id="rSubmitBtn" onclick="applyRegions()">필터 선택 완료</button>
        </div>
    </div>

    <script>
        // ===== 검색 자동완성 기능 =====
        (function() {
            // 추천 키워드 사전 (카테고리별)
            const acKeywords = [
                // 눈
                { text: '쌍꺼풀', cat: '눈성형', icon: '👁️' },
                { text: '눈매교정', cat: '눈성형', icon: '👁️' },
                { text: '앞트임', cat: '눈성형', icon: '👁️' },
                { text: '뒤트임', cat: '눈성형', icon: '👁️' },
                { text: '다크서클', cat: '눈성형', icon: '👁️' },
                { text: '눈밑지방재배치', cat: '눈성형', icon: '👁️' },
                // 코
                { text: '코성형', cat: '코성형', icon: '👃' },
                { text: '코필러', cat: '코성형', icon: '👃' },
                { text: '매부리코', cat: '코성형', icon: '👃' },
                { text: '복코교정', cat: '코성형', icon: '👃' },
                // 쁘띠
                { text: '보톡스', cat: '쁘띠', icon: '💉' },
                { text: '필러', cat: '쁘띠', icon: '💉' },
                { text: '입술필러', cat: '쁘띠', icon: '💉' },
                { text: '눈밑필러', cat: '쁘띠', icon: '💉' },
                { text: '리쥬란', cat: '쁘띠', icon: '💉' },
                { text: '스킨부스터', cat: '쁘띠', icon: '💉' },
                { text: '쥬베룩', cat: '쁘띠', icon: '💉' },
                { text: '지방분해주사', cat: '쁘띠', icon: '💉' },
                // 리프팅
                { text: '리프팅', cat: '리프팅', icon: '🔊' },
                { text: '울쎄라', cat: '리프팅', icon: '🔊' },
                { text: '써마지', cat: '리프팅', icon: '🔊' },
                { text: '슈링크', cat: '리프팅', icon: '🔊' },
                { text: '실리프팅', cat: '리프팅', icon: '🔊' },
                // 피부
                { text: '피부관리', cat: '피부', icon: '🌸' },
                { text: '아쿠아필', cat: '피부', icon: '💧' },
                { text: '기미치료', cat: '피부', icon: '🌸' },
                { text: '여드름치료', cat: '피부', icon: '🌸' },
                { text: '레이저토닝', cat: '피부', icon: '🌸' },
                { text: '프락셀', cat: '피부', icon: '🌸' },
                { text: '모공관리', cat: '피부', icon: '🌸' },
                { text: '피부과', cat: '피부', icon: '🌸' },
                // 바디
                { text: '지방흡입', cat: '바디', icon: '🏃' },
                { text: '다이어트', cat: '바디', icon: '🏃' },
                { text: '바디라인', cat: '바디', icon: '🏃' },
                { text: '종아리보톡스', cat: '바디', icon: '🏃' },
                { text: '승모근보톡스', cat: '바디', icon: '🏃' },
                // 기타
                { text: '제모', cat: '기타', icon: '✂️' },
                { text: '반영구화장', cat: '기타', icon: '🖋️' },
                { text: '탈모치료', cat: '기타', icon: '💇' },
                { text: '눈썹문신', cat: '기타', icon: '🖋️' },
            ];

            const input  = document.getElementById('mainSearchInput');
            const acBox  = document.getElementById('searchAutocomplete');
            const popularSec = document.getElementById('acPopularSection');
            const resultSec  = document.getElementById('acResultSection');
            const resultList = document.getElementById('acResultList');
            let focusIdx = -1;

            function escapeHtml(t) {
                return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            }
            function highlightText(text, query) {
                if (!query) return escapeHtml(text);
                const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return escapeHtml(text).replace(re, '<mark>$1</mark>');
            }
            function showAC() { acBox.classList.add('visible'); }
            function hideAC() { acBox.classList.remove('visible'); focusIdx = -1; }

            function renderResults(query) {
                const q = query.trim();
                if (!q) {
                    popularSec.style.display = '';
                    resultSec.style.display = 'none';
                    return;
                }
                const matched = acKeywords.filter(k =>
                    k.text.includes(q) || k.cat.includes(q)
                ).slice(0, 7);

                popularSec.style.display = 'none';
                resultSec.style.display = '';

                if (matched.length === 0) {
                    resultList.innerHTML = `<div class="ac-item"><span class="ac-item-icon">🔍</span><span class="ac-item-text">"${escapeHtml(q)}" 검색하기</span></div>`;
                    resultList.querySelector('.ac-item').onclick = () => doSearch(q);
                    return;
                }

                resultList.innerHTML = matched.map((k, i) => `
                    <div class="ac-item" data-idx="${i}" onclick="doSearch('${escapeHtml(k.text)}')">
                        <span class="ac-item-icon">${k.icon}</span>
                        <span class="ac-item-text">${highlightText(k.text, q)}</span>
                        <span class="ac-item-category">${escapeHtml(k.cat)}</span>
                    </div>
                `).join('');
            }

            // 인풋 포커스 → 드롭다운 열기
            input.addEventListener('focus', () => {
                renderResults(input.value);
                showAC();
            });

            // 타이핑 시 실시간 추천
            input.addEventListener('input', () => {
                focusIdx = -1;
                renderResults(input.value);
            });

            // 키보드 방향키·엔터·ESC 처리
            input.addEventListener('keydown', (e) => {
                const items = resultList.querySelectorAll('.ac-item');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    focusIdx = Math.min(focusIdx + 1, items.length - 1);
                    items.forEach((el, i) => el.classList.toggle('focused', i === focusIdx));
                    if (items[focusIdx]) input.value = items[focusIdx].querySelector('.ac-item-text').textContent.trim();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    focusIdx = Math.max(focusIdx - 1, -1);
                    items.forEach((el, i) => el.classList.toggle('focused', i === focusIdx));
                    if (focusIdx === -1) input.value = '';
                } else if (e.key === 'Escape') {
                    hideAC();
                } else if (e.key === 'Enter') {
                    hideAC();
                }
            });

            // 바깥 클릭 시 닫기
            document.addEventListener('click', (e) => {
                if (!document.getElementById('searchWrapper').contains(e.target)) hideAC();
            });
        })();

        // 검색 실행 헬퍼
        function doSearch(keyword) {
            const form = document.getElementById('mainSearchForm');
            document.getElementById('mainSearchInput').value = keyword;
            form.submit();
        }
        // ===== END 검색 자동완성 기능 =====

        // ===== 홈 카테고리 탭 필터 =====
        function filterHomeCategory(tabEl, cat) {
            // 탭 활성화
            document.querySelectorAll('.home-cat-tab').forEach(t => t.classList.remove('active'));
            tabEl.classList.add('active');

            const cards   = document.querySelectorAll('.event-card-home[data-home-cat]');
            const noRes   = document.getElementById('homeCatNoResult');
            const noText  = document.getElementById('homeCatNoResultText');
            const cntEl   = document.getElementById('homeCatResultCount');
            let visible   = 0;

            cards.forEach(card => {
                const cardCat = card.getAttribute('data-home-cat') || '';
                const show = (cat === '전체') || (cardCat === cat);
                if (show) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            // 결과 건수 표시
            if (cat === '전체') {
                cntEl.textContent = '';
            } else {
                cntEl.textContent = visible + '건';
            }

            // 결과 없음 메시지
            if (visible === 0) {
                noRes.style.display = 'block';
                noText.textContent = '"' + cat + '" 카테고리의 여우언니 단독 특가가 없습니다.';
            } else {
                noRes.style.display = 'none';
            }

            // 탭 클릭 시 부드럽게 해당 섹션으로 스크롤
            const section = document.getElementById('homeEventList');
            if (section && cat !== '전체') {
                section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        // ===== END 홈 카테고리 탭 필터 =====

        // --- Splash Screen Logic ---
        window.addEventListener('load', () => {
            setTimeout(() => {
                const splash = document.getElementById('secret-splash');
                splash.classList.add('hide');
                setTimeout(() => {
                    document.body.classList.remove('splash-active');
                    splash.style.display = 'none';
                }, 600); // fade transition time
            }, 1400); // 1.4초 감상 시간
        });

        // --- Region Data ---
        const regionData = {
            '서울': ['서울 전체', '강남역/신논현역/양재', '청담/압구정/신사', '선릉/삼성', '논현/반포/학동', '서초/교대/방배', '대치/도곡/한티', '홍대/합정/신촌', '잠실/송파'],
            '경기': ['경기 전체', '분당/판교', '수원/인계', '일산/파주', '안양/평촌', '부천/상동'],
            '인천': ['인천 전체', '구월/부평', '송도/청라'],
            '부산': ['부산 전체', '서면/전포', '해운대/센텀', '남포/광복'],
            '대구': ['대구 전체', '동성로/반월당', '수성구'],
            '대전': ['대전 전체', '둔산/월평', '유성'],
            '광주': ['광주 전체', '상무지구', '충장로'],
            '울산': ['울산 전체', '삼산동']
        };

        let currentMajorRegion = '서울';
        let selectedSubRegions = new Set(['강남역/신논현역/양재']); // Default selection

        // --- Render Functions ---
        function renderSidebar() {
            const sidebar = document.getElementById('rSidebar');
            sidebar.innerHTML = '';
            Object.keys(regionData).forEach(major => {
                // Check if this major region has any selected sub-regions
                const hasSelection = regionData[major].some(sub => selectedSubRegions.has(sub));

                const item = document.createElement('div');
                item.className = 'r-side-item' + (major === currentMajorRegion ? ' active' : '') + (hasSelection ? ' has-selection' : '');
                item.innerHTML = `${major}<div class="dot"></div>`;
                item.onclick = () => {
                    currentMajorRegion = major;
                    renderSidebar(); // Update active class
                    renderContent(); // Update right list
                };
                sidebar.appendChild(item);
            });
        }

        function renderContent() {
            const content = document.getElementById('rContent');
            content.innerHTML = '';
            const subs = regionData[currentMajorRegion];

            subs.forEach(sub => {
                const isActive = selectedSubRegions.has(sub);
                const item = document.createElement('div');
                item.className = 'r-check-item' + (isActive ? ' active' : '');
                item.innerHTML = `
                    <div class="r-check-box"></div>
                    <div class="r-check-text">${sub}</div>
                `;
                item.onclick = () => {
                    toggleRegion(sub);
                };
                content.appendChild(item);
            });
        }

        function renderTags() {
            const container = document.getElementById('rTagsContainer');
            container.innerHTML = '';

            selectedSubRegions.forEach(sub => {
                const tag = document.createElement('div');
                tag.className = 'r-tag';
                tag.innerHTML = `${sub} <span class="r-tag-close" onclick="toggleRegion('${sub}')">&times;</span>`;
                container.appendChild(tag);
            });

            // Update submit button state
            const btn = document.getElementById('rSubmitBtn');
            if (selectedSubRegions.size > 0) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }

        function toggleRegion(sub) {
            if (selectedSubRegions.has(sub)) {
                selectedSubRegions.delete(sub);
            } else {
                selectedSubRegions.add(sub);
            }
            renderContent();
            renderSidebar(); // Update dots
            renderTags();
        }

        function resetRegions() {
            selectedSubRegions.clear();
            renderContent();
            renderSidebar();
            renderTags();
        }

        // --- Modal Actions ---
        function openRegionModal() {
            renderSidebar();
            renderContent();
            renderTags();
            document.getElementById('regionOverlay').classList.add('active');
            setTimeout(() => {
                document.getElementById('regionSheet').classList.add('active');
            }, 10);
        }

        function closeRegionModal() {
            document.getElementById('regionSheet').classList.remove('active');
            setTimeout(() => {
                document.getElementById('regionOverlay').classList.remove('active');
            }, 300);
        }

        function applyRegions() {
            if (selectedSubRegions.size === 0) return; // Prevent empty submission

            const arr = Array.from(selectedSubRegions);
            let displayText = arr[0];
            if (arr.length > 1) {
                displayText += ` 외 ${arr.length - 1}개`;
            }

            document.getElementById('currentRegionText').innerText = displayText;

            // Update category links with selected region
            const regionString = arr.join(',');
            document.querySelectorAll('.cat-item').forEach(link => {
                let url = new URL(link.href, window.location.origin);
                url.searchParams.set('region', regionString);
                link.href = url.toString();
            });

            // Update search form with hidden region input
            let regionInput = document.getElementById('searchRegionInput');
            if (!regionInput) {
                regionInput = document.createElement('input');
                regionInput.type = 'hidden';
                regionInput.name = 'region';
                regionInput.id = 'searchRegionInput';
                document.querySelector('form.search-input-box').appendChild(regionInput);
            }
            regionInput.value = regionString;

            // Save region to cookie for global persistence across the site
            document.cookie = "user_region=" + encodeURIComponent(regionString) + "; path=/; max-age=31536000";

            closeRegionModal();
        }

        // --- Carousel Logic ---
        document.addEventListener('DOMContentLoaded', function () {
            const carouselInner = document.getElementById('carouselInner');
            const pagination = document.getElementById('carouselPagination');
            const slides = document.querySelectorAll('.carousel-slide');
            const totalSlides = slides.length;
            let currentSlide = 0;

            if (totalSlides > 1) {
                // 자동 슬라이드
                setInterval(() => {
                    currentSlide = (currentSlide + 1) % totalSlides;
                    carouselInner.scrollTo({
                        left: currentSlide * carouselInner.offsetWidth,
                        behavior: 'smooth'
                    });
                }, 3500);

                // 스크롤 시 페이지네이션 업데이트
                carouselInner.addEventListener('scroll', () => {
                    const index = Math.round(carouselInner.scrollLeft / carouselInner.offsetWidth);
                    if (index !== currentSlide) {
                        currentSlide = index;
                        pagination.innerText = (currentSlide + 1) + ' / ' + totalSlides;
                    }
                });
            }
        });

        // --- Wishlist Logic ---
        function toggleWishlist(e, id, title, hospital, img, d_price, o_price, rating, reviews) {
            e.preventDefault(); // 링크 이동 방지
            e.stopPropagation(); // 상위 여우언니 단독 특가 전달 방지

            let btn = e.currentTarget;
            let wishlist = JSON.parse(localStorage.getItem('shinsa_wishlist')) || [];

            let idx = wishlist.findIndex(item => item.id == id);
            if (idx > -1) {
                // 이미 있으면 삭제
                wishlist.splice(idx, 1);
                btn.classList.remove('active');
                btn.innerText = '🤍';
            } else {
                // 없으면 추가
                wishlist.push({ id, title, hospital, img, d_price, o_price, rating, reviews });
                btn.classList.add('active');
                btn.innerText = '❤️';
            }
            localStorage.setItem('shinsa_wishlist', JSON.stringify(wishlist));
        }

        // 최근 본 시술 렌더링 로직
        function renderRecentEvents() {
            const container = document.getElementById('recentEventsContainer');
            const section = document.getElementById('recentEventsSection');
            if (!container || !section) return;

            let recentList = JSON.parse(localStorage.getItem('shinsa_recent_events') || '[]');
            if (recentList.length === 0) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';
            let html = '';
            recentList.forEach(item => {
                const discountPrice = Number(item.discount_price).toLocaleString();
                
                html += `
                    <a href="/events/detail.php?id=${item.id}" style="min-width: 140px; max-width: 140px; display: block; text-decoration: none; color: inherit; flex-shrink: 0;">
                        <div style="position: relative; width: 140px; height: 100px; border-radius: 8px; overflow: hidden; background: #eee;">
                            <img src="${item.image_url}" alt="${item.title}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2280%22%20height%3D%2280%22%20viewBox%3D%220%200%2080%2080%22%3E%3Crect%20fill%3D%22%23eee%22%20width%3D%2280%22%20height%3D%2280%22%2F%3E%3C%2Fsvg%3E'">
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.hospital_name}</div>
                        <div style="font-size: 13px; font-weight: 600; color: #333; margin-top: 2px; height: 36px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${item.title}</div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--primary-color); margin-top: 4px;">${discountPrice}원</div>
                    </a>
                `;
            });
            container.innerHTML = html;
        }

        // 페이지 로드 시 찜 상태 렌더링 및 최근 본 시술 렌더링
        document.addEventListener('DOMContentLoaded', () => {
            // 스플래시 화면 페이드아웃 (앱 실행 효과)
            setTimeout(() => {
                const splash = document.getElementById('secret-splash');
                if (splash) {
                    splash.classList.add('hide');
                    document.body.classList.remove('splash-active');
                    setTimeout(() => splash.remove(), 700);
                }
            }, 1800);

            let wishlist = JSON.parse(localStorage.getItem('shinsa_wishlist')) || [];
            let wishIds = wishlist.map(item => parseInt(item.id));
            document.querySelectorAll('.wish-btn').forEach(btn => {
                let id = parseInt(btn.getAttribute('data-id'));
                if (wishIds.includes(id)) {
                    btn.classList.add('active');
                    btn.innerText = '❤️';
                }
            });
            renderRecentEvents();
        });
    </script>
    <script>
        // PWA Service Worker 등록
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(error => {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>
</body>

</html>
