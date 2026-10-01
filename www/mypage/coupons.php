<?php
session_start();
$title = "내 쿠폰";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 35px;}
        
        .coupon-list { padding: 20px 15px; display: flex; flex-direction: column; gap: 15px; }
        
        .coupon-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; overflow: hidden; position: relative; }
        .coupon-left { background: var(--primary-color); color: #fff; width: 30%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px 10px; border-right: 2px dashed rgba(255,255,255,0.5); }
        .coupon-amount { font-size: 24px; font-weight: 900; line-height: 1.1; }
        .coupon-unit { font-size: 14px; font-weight: 600; margin-top: 2px; }
        
        .coupon-right { padding: 20px 15px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .coupon-title { font-size: 15px; font-weight: 800; color: #333; margin-bottom: 5px; }
        .coupon-desc { font-size: 12px; color: #666; margin-bottom: 15px; }
        .coupon-date { font-size: 11px; color: #999; }
        
        .use-btn { position: absolute; right: 15px; bottom: 15px; padding: 6px 12px; background: #fff0f5; color: var(--primary-color); font-size: 12px; font-weight: 700; border-radius: 20px; text-decoration: none; border: 1px solid #ffb6c1; }
        
        /* 반원 효과 */
        .coupon-card::before, .coupon-card::after { content: ''; position: absolute; width: 20px; height: 20px; background: #f8f9fa; border-radius: 50%; left: calc(30% - 10px); z-index: 10; }
        .coupon-card::before { top: -10px; }
        .coupon-card::after { bottom: -10px; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/mypage.php" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <main class="coupon-list">
        <!-- 쿠폰 1 -->
        <div class="coupon-card">
            <div class="coupon-left">
                <div class="coupon-amount">1만</div>
                <div class="coupon-unit">원 할인</div>
            </div>
            <div class="coupon-right">
                <div>
                    <div class="coupon-title">첫 가입 환영 쿠폰</div>
                    <div class="coupon-desc">5만원 이상 결제 시 사용 가능</div>
                </div>
                <div class="coupon-date">2026.07.16 까지</div>
                <a href="/events/list.php" class="use-btn">사용하기</a>
            </div>
        </div>

        <!-- 쿠폰 2 -->
        <div class="coupon-card" style="opacity: 0.9;">
            <div class="coupon-left" style="background: linear-gradient(135deg, #f5576c, #f093fb);">
                <div class="coupon-amount">5%</div>
                <div class="coupon-unit">추가 할인</div>
            </div>
            <div class="coupon-right">
                <div>
                    <div class="coupon-title">여름맞이 VIP 특별 쿠폰</div>
                    <div class="coupon-desc">피부/레이저 시술 전용 (최대 3만원)</div>
                </div>
                <div class="coupon-date">2026.06.30 까지</div>
                <a href="/events/list.php" class="use-btn">사용하기</a>
            </div>
        </div>
    </main>
</body>
</html>


