<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; }
        
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: calc(100vh - 120px); text-align: center; }
        .empty-icon { font-size: 50px; color: #ddd; margin-bottom: 20px; }
        .empty-text { font-size: 16px; color: var(--text-light); }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <!-- 상단 헤더 -->
    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <!-- 빈 화면 (Empty State) -->
    <main>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <div class="empty-text"><?= htmlspecialchars($empty_message) ?></div>
        </div>
    </main>

</body>
</html>


