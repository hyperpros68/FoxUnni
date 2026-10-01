<?php
// admin/events.php
session_start();
require_once '../config/db_connect.php';

$events = [];
if ($db_connected) {
    try {
        $stmt = $pdo->query("SELECT e.*, h.name as hospital_name FROM events e LEFT JOIN hospitals h ON e.hospital_id = h.id ORDER BY e.created_at DESC LIMIT 100");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>시술 이벤트 관리 - 여우언니 Admin</title>
    <style>
        :root { --primary: #f5576c; --dark: #333; --bg: #f5f6f8; }
        body { font-family: 'Inter', sans-serif; margin: 0; background: var(--bg); color: var(--dark); }
        .sidebar { width: 250px; height: 100vh; background: #fff; position: fixed; border-right: 1px solid #eee; }
        .logo { padding: 20px; font-size: 24px; font-weight: 900; color: var(--primary); border-bottom: 1px solid #eee; }
        .nav-link { display: block; padding: 15px 20px; color: #555; text-decoration: none; font-weight: 600; border-bottom: 1px solid #fafafa; }
        .nav-link:hover, .nav-link.active { background: #fff0f5; color: var(--primary); }
        .main-content { margin-left: 250px; padding: 30px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: 800; }
        .btn-add { background: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #fdfdfd; font-weight: 700; color: #555; }
        tr:hover { background: #fafafa; }
        .btn-action { background:#333; color:#fff; padding:6px 12px; border:none; border-radius:4px; font-size:12px; cursor:pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🦊 여우언니 Admin</div>
        <a href="index.php" class="nav-link">📊 대시보드</a>
        <a href="hospitals.php" class="nav-link">🏥 입점 병원 관리</a>
        <a href="events.php" class="nav-link active">🎁 시술 이벤트 관리</a>
        <a href="reservations.php" class="nav-link">💳 결제/정산 관리</a>
        <a href="reports.php" class="nav-link">🚨 유저 신고 관리</a>
        <a href="/" class="nav-link" style="margin-top:50px; color:#999;">앱으로 돌아가기</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="title">시술 이벤트 (특가) 리스트</div>
            <a href="#" class="btn-add" onclick="alert('이벤트 신규 등록 팝업 띄우기');">➕ 새 이벤트 등록</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>분류</th>
                    <th>시술/이벤트명</th>
                    <th>제공 병원</th>
                    <th>정가</th>
                    <th>특가가격</th>
                    <th>할인율</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($events)): ?>
                <tr><td colspan="8" style="text-align:center; padding:30px; color:#999;">등록된 시술 이벤트가 없습니다. (현재 events.json 파일로 임시 구동 중일 수 있습니다.)</td></tr>
                <?php else: ?>
                    <?php foreach($events as $e): 
                        $discount_rate = 0;
                        if($e['original_price'] > 0) {
                            $discount_rate = round((($e['original_price'] - $e['discount_price']) / $e['original_price']) * 100);
                        }
                    ?>
                    <tr>
                        <td>#<?= $e['id'] ?></td>
                        <td><span style="background:#f0f4ff; color:#4b0082; padding:2px 6px; border-radius:4px; font-size:11px;"><?= htmlspecialchars($e['category']) ?></span></td>
                        <td style="font-weight:700;"><?= htmlspecialchars($e['title']) ?></td>
                        <td><?= htmlspecialchars($e['hospital_name']) ?></td>
                        <td style="text-decoration:line-through; color:#999; font-size:12px;"><?= number_format($e['original_price']) ?>원</td>
                        <td style="color:var(--primary); font-weight:800;"><?= number_format($e['discount_price']) ?>원</td>
                        <td><span style="color:var(--primary); font-weight:700;"><?= $discount_rate ?>%</span></td>
                        <td>
                            <button class="btn-action" onclick="alert('품절(마감) 처리');">마감</button>
                            <button class="btn-action" style="background:#dc3545;" onclick="alert('삭제');">삭제</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
