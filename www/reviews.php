<?php
// www/reviews.php - 리얼 후기 피드 페이지
session_start();
require_once 'config/db_connect.php';

// 모든 리뷰 불러오기 (최신순)
$reviews = [];
if ($db_connected) {
    $stmt = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 더미 데이터 (리뷰가 없을 때를 대비)
if (empty($reviews)) {
    $reviews = [
        [
            'user_name' => '강남미인',
            'hospital_name' => '신사 눈성형외과',
            'event_title' => '자연유착 쌍꺼풀',
            'rating' => 5,
            'content' => '원장님이 너무 꼼꼼하게 상담해주셨어요! 붓기도 빨리 빠지고 라인이 완전 자연스럽게 잡혔어요. 대만족합니다! 완전 추천해요.',
            'photo_url' => 'https://picsum.photos/id/1011/400/400',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 days'))
        ],
        [
            'user_name' => '뷰티여신',
            'hospital_name' => '압구정 피부과',
            'event_title' => '올인원 모공 축소 레이저',
            'rating' => 4,
            'content' => '시술 받을 때 조금 따끔하긴 했는데, 1주일 지나니까 피부결이 확 달라진 게 느껴져요. 다음 달에 또 받으러 가려구요!',
            'photo_url' => 'https://picsum.photos/id/1027/400/400',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'user_name' => '익명',
            'hospital_name' => '청담 필러클리닉',
            'event_title' => '애교살 필러 1cc',
            'rating' => 5,
            'content' => '너무 과하지 않게 예쁘게 채워주셨어요. 거울 볼 때마다 기분이 좋네요 ㅎㅎㅎ 멍도 안 들고 바로 일상생활 가능했어요.',
            'photo_url' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>리얼 후기 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background: #f8f9fa; }
        .header-sub { background: #fff; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 100; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .header-sub .title { font-size: 18px; font-weight: 700; color: #333; }
        .header-sub .back { font-size: 20px; color: #333; text-decoration: none; font-weight: bold; }
        
        .feed-container { padding: 15px; }
        .review-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; }
        
        .review-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
        .user-details { display: flex; flex-direction: column; }
        .user-name { font-weight: 700; font-size: 15px; color: #333; }
        .review-date { font-size: 12px; color: #aaa; margin-top: 2px; }
        
        .hospital-badge { display: inline-block; background: #fff0f5; color: var(--primary-color); font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 12px; margin-bottom: 10px; border: 1px solid #ffe4e1; }
        .event-title { font-size: 16px; font-weight: 700; color: #222; margin-bottom: 8px; }
        
        .stars { color: #f48fb1; font-size: 16px; margin-bottom: 12px; }
        
        .review-photo { width: 100%; height: 250px; border-radius: 12px; object-fit: cover; margin-bottom: 15px; background: #eee; }
        .review-content { font-size: 14px; color: #444; line-height: 1.6; word-break: keep-all; margin-bottom: 15px; }
        
        .review-footer { border-top: 1px solid #f5f5f5; padding-top: 15px; display: flex; align-items: center; gap: 15px; }
        .action-btn { display: flex; align-items: center; gap: 5px; color: #888; font-size: 13px; font-weight: 500; cursor: pointer; }
        .action-btn i { font-size: 18px; font-style: normal; }
        .action-btn:hover { color: var(--primary-color); }
        
        .write-btn { position: fixed; bottom: 80px; right: 20px; background: var(--primary-color); color: #fff; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(245,87,108,0.4); text-decoration: none; font-size: 24px; transition: transform 0.2s; z-index: 1000; }
        .write-btn:active { transform: scale(0.9); }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="javascript:history.back()" class="back">←</a>
        <div class="title">리얼 후기</div>
        <a href="/mypage/write_review.php" style="background: var(--primary-color); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 18px; box-shadow: 0 2px 8px rgba(245,87,108,0.4); margin-right: 5px;">✎</a>
    </header>

    <div class="feed-container">
        <?php foreach ($reviews as $rev): ?>
            <?php 
                $date_str = date('y.m.d', strtotime($rev['created_at']));
                $first_char = mb_substr($rev['user_name'], 0, 1, 'UTF-8');
                $stars = str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']);
            ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="user-info">
                        <div class="user-avatar"><?= htmlspecialchars($first_char) ?></div>
                        <div class="user-details">
                            <span class="user-name"><?= htmlspecialchars($rev['user_name']) ?></span>
                            <span class="review-date"><?= $date_str ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="hospital-badge">🏥 <?= htmlspecialchars($rev['hospital_name']) ?></div>
                <?php if(!empty($rev['is_receipt_verified'])): ?>
                <div class="hospital-badge" style="background:#f4f9ff; color:#00a8ff; border-color:#cce9ff; margin-left:5px;">✅ 영수증 인증</div>
                <?php endif; ?>
                <div class="event-title"><?= htmlspecialchars($rev['event_title'] ?? '') ?></div>
                <div class="stars"><?= $stars ?></div>
                
                <?php if (!empty($rev['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($rev['photo_url']) ?>" class="review-photo" alt="리뷰 사진">
                <?php endif; ?>
                
                <div class="review-content">
                    <?= nl2br(htmlspecialchars($rev['content'])) ?>
                </div>
                
                <div class="review-footer">
                    <div class="action-btn"><i>🤍</i> 도움돼요</div>
                    <div class="action-btn"><i>💬</i> 댓글</div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align:center; padding:20px; color:#aaa; font-size:13px;">
            모든 후기를 확인했습니다.
        </div>

        <div style="margin: 20px 0; padding: 15px; background: #fdf5f6; border-radius: 8px; border: 1px solid #ffe4e1;">
            <div style="font-size: 11px; font-weight: 700; color: #d81b60; margin-bottom: 5px;">[의료법 제56조 2항에 따른 고지]</div>
            <div style="font-size: 11px; color: #666; line-height: 1.5; letter-spacing: -0.3px;">
                본 게시판의 모든 후기는 개인이 직접 작성한 주관적인 의견이며, 의료진의 의학적 판단을 대신할 수 없습니다.<br>
                <b>모든 시술 및 수술은 개인의 체질과 상태에 따라 출혈, 감염, 염증 등의 부작용이 발생할 수 있으므로</b> 반드시 전문 의료진과 충분한 상담 후 신중하게 결정하시기 바랍니다. 여우언니는 통신판매중개자로서 의료행위의 당사자가 아니며, 후기 내용의 사실 여부에 대한 책임을 지지 않습니다.
            </div>
        </div>
    </div>
    
    
</body>
</html>


