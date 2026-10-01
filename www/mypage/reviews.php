<?php
session_start();
$title = "내 후기";
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
        
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: calc(100vh - 120px); text-align: center; }
        .empty-icon { font-size: 50px; color: #ddd; margin-bottom: 20px; }
        .empty-text { font-size: 16px; color: #999; margin-bottom: 30px; }
        
        .write-btn { padding: 15px 30px; background-color: var(--primary-color); color: #fff; text-decoration: none; border-radius: 12px; font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(255,105,180,0.3); transition: transform 0.2s; }
        .write-btn:active { transform: scale(0.95); }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/mypage.php" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <main>
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <div class="empty-text">아직 작성하신 후기가 없습니다.</div>
            
            <a href="/mypage/write_review.php" class="write-btn">
                <span>➕</span> 첫 후기 작성하고 1,000P 받기
            </a>
        </div>
    </main>
</body>
</html>


