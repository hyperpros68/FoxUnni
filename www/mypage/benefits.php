<?php
// www/mypage/benefits.php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>여우언니 단독 특가 및 혜택 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f7f7f7; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .page-header { display: flex; align-items: center; padding: 15px 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #eaeaea; background: #fff; position: sticky; top: 0; z-index: 10; }
        .back-btn { margin-right: 15px; font-size: 24px; text-decoration: none; color: #333; }
        
        .benefits-container { padding: 20px; }
        .benefit-card { background: #fff; border-radius: 16px; overflow: hidden; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-decoration: none; display: block; color: inherit; transition: transform 0.2s; }
        .benefit-card:active { transform: scale(0.98); }
        .benefit-img { width: 100%; height: 160px; background-size: cover; background-position: center; position: relative; display: flex; align-items: center; justify-content: center; font-size: 50px;}
        .benefit-badge { position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6); color: #fff; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .benefit-content { padding: 20px; }
        .benefit-title { font-size: 17px; font-weight: 800; color: #333; margin-bottom: 8px; line-height: 1.4; }
        .benefit-desc { font-size: 14px; color: #666; margin-bottom: 15px; line-height: 1.5; }
        .benefit-date { font-size: 12px; color: #aaa; }
        
        .grad-bg-1 { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); }
        .grad-bg-2 { background: linear-gradient(120deg, #a1c4fd 0%, #c2e9fb 100%); }
        .grad-bg-3 { background: linear-gradient(to top, #cfd9df 0%, #e2ebf0 100%); }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="/mypage.php" class="back-btn">&larr;</a>
        여우언니 혜택
    </div>
    
    <div class="benefits-container">
        <!-- 여우언니 단독 특가 1 -->
        <a href="/mypage/write_review.php" class="benefit-card">
            <div class="benefit-img grad-bg-1">
                📝
                <div class="benefit-badge">진행중</div>
            </div>
            <div class="benefit-content">
                <div class="benefit-title">첫 리얼 후기 작성하고<br>1,000P 100% 받기!</div>
                <div class="benefit-desc">영수증 인증하고 시술 후기를 남기면 무조건 1,000 포인트를 드립니다.</div>
                <div class="benefit-date">상시 진행</div>
            </div>
        </a>

        <!-- 여우언니 단독 특가 2 -->
        <a href="#" class="benefit-card">
            <div class="benefit-img grad-bg-2">
                👯‍♀️
                <div class="benefit-badge">진행중</div>
            </div>
            <div class="benefit-content">
                <div class="benefit-title">친구 초대하고<br>서로 5,000P 사이좋게 받기</div>
                <div class="benefit-desc">초대한 친구가 첫 예약을 완료하면 두 분 모두에게 5,000P를 쏩니다.</div>
                <div class="benefit-date">2026.06.01 ~ 2026.12.31</div>
            </div>
        </a>

        <!-- 여우언니 단독 특가 3 -->
        <a href="#" class="benefit-card">
            <div class="benefit-img grad-bg-3" style="filter: grayscale(100%);">
                🎁
                <div class="benefit-badge" style="background: rgba(0,0,0,0.4);">종료</div>
            </div>
            <div class="benefit-content">
                <div class="benefit-title" style="color: #999;">가정의 달 기념<br>효도 쁘띠 시술 할인전</div>
                <div class="benefit-desc" style="color: #bbb;">부모님 모시고 오면 10% 추가 할인 쿠폰 증정!</div>
                <div class="benefit-date">여우언니 단독 특가가 종료되었습니다.</div>
            </div>
        </a>
    </div>
</body>
</html>


