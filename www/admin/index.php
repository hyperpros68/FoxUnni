<?php
// admin/index.php
session_start();
require_once '../config/db_connect.php';

// 임시 접근 제어 (추후 admin 계정 권한 체크 로직 추가 필요)
// if (!isset($_SESSION['is_admin'])) { header("Location: /"); exit; }

$stats = [
    'users' => 0,
    'reservations' => 0,
    'reports' => 0,
    'revenue' => 0,
    'hospitals' => 0,
    'events' => 0
];

if ($db_connected) {
    try {
        $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['reservations'] = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
        $stats['reports'] = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $stats['revenue'] = $pdo->query("SELECT SUM(amount) FROM reservations WHERE status IN ('paid', 'completed')")->fetchColumn() ?: 0;
        $stats['hospitals'] = $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
        $stats['events'] = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    } catch(PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>여우언니 관리자 대시보드</title>
    <style>
        :root { --primary: #f5576c; --dark: #333; --bg: #f5f6f8; }
        body { font-family: 'Inter', sans-serif; margin: 0; background: var(--bg); color: var(--dark); }
        .sidebar { width: 250px; height: 100vh; background: #fff; position: fixed; border-right: 1px solid #eee; }
        .logo { padding: 20px; font-size: 24px; font-weight: 900; color: var(--primary); border-bottom: 1px solid #eee; }
        .nav-link { display: block; padding: 15px 20px; color: #555; text-decoration: none; font-weight: 600; border-bottom: 1px solid #fafafa; }
        .nav-link:hover, .nav-link.active { background: #fff0f5; color: var(--primary); }
        .main-content { margin-left: 250px; padding: 30px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: 800; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .stat-title { font-size: 14px; color: #888; margin-bottom: 10px; font-weight: 600; }
        .stat-value { font-size: 28px; font-weight: 900; color: var(--dark); }
        .stat-value.highlight { color: var(--primary); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🦊 여우언니 Admin</div>
        <a href="index.php" class="nav-link active">📊 대시보드</a>
        <a href="hospitals.php" class="nav-link">🏥 입점 병원 관리</a>
        <a href="events.php" class="nav-link">🎁 시술 이벤트 관리</a>
        <a href="reservations.php" class="nav-link">💳 결제/정산 관리</a>
        <a href="reports.php" class="nav-link">🚨 유저 신고 관리</a>
        <a href="/" class="nav-link" style="margin-top:50px; color:#999;">앱으로 돌아가기</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="title">대시보드 요약표</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">총 가입 유저</div>
                <div class="stat-value"><?= number_format($stats['users']) ?>명</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">총 예약(결제) 건수</div>
                <div class="stat-value highlight"><?= number_format($stats['reservations']) ?>건</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">입점 병원 / 진행 이벤트</div>
                <div class="stat-value"><?= number_format($stats['hospitals']) ?> <span style="font-size:16px; font-weight:400; color:#888;">/ <?= number_format($stats['events']) ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">누적 예약금 (매출)</div>
                <div class="stat-value highlight"><?= number_format($stats['revenue']) ?>원</div>
                <div style="font-size:11px; color:#888; margin-top:5px;">수수료(10% 가정) 수익: <?= number_format($stats['revenue'] * 0.1) ?>원</div>
            </div>
            <div class="stat-card">
                <div class="stat-title" style="color:#d9534f;">미처리 신고 접수</div>
                <div class="stat-value" style="color:#d9534f;"><?= number_format($stats['reports']) ?>건</div>
            </div>
        </div>
        
        <div style="background:#fff; padding:25px; border-radius:12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
            <h3>환영합니다, 대표님!</h3>
            <p style="color:#666; line-height:1.6;">현재 앱에 결제된 내역과 유저들의 신고 내역을 왼쪽 메뉴에서 확인할 수 있습니다.<br>
            추후 병원 원장님들이 직접 들어와서 이벤트를 올릴 수 있는 '병원 파트너 센터' 기능도 이곳에 연동될 예정입니다.</p>
        </div>
    </div>
</body>
</html>
