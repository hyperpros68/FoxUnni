<?php
// ai_result.php
// 시뮬레이션용 추천 여우언니 단독 특가 데이터 (더미 데이터 재활용)
$recommended_events = [
    ['id'=>5, 'type'=>'여우언니 단독 특가', 'category'=>'피부', 'region'=>'마산', 'hospital_name'=>'마산맑은피부과', 'title'=>'슈링크 유니버스 300샷 (추천)', 'discount_price'=>99000, 'original_price'=>200000, 'image_url'=>'/static/images/lifting.jpg', 'rating'=>4.9, 'reviews'=>540, 'reason'=>'턱선 탄력 개선'],
    ['id'=>8, 'type'=>'일반시술', 'category'=>'쁘띠', 'region'=>'신사', 'hospital_name'=>'신사드림성형외과', 'title'=>'사각턱 보톡스 (추천)', 'discount_price'=>50000, 'original_price'=>50000, 'image_url'=>'/static/images/botox.jpg', 'rating'=>4.6, 'reviews'=>1024, 'reason'=>'안면 비대칭 보완'],
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI 분석 결과 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; }
        .header-sub { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--white); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border-color); }
        .close-btn { font-size: 24px; cursor: pointer; text-decoration: none; color: #333; font-weight: 300; }
        .page-title { font-size: 18px; font-weight: 700; }

        .report-section {
            background: var(--white);
            padding: 30px 20px;
            margin-bottom: 10px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .report-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .report-subtitle {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .analysis-card {
            background: #fff0f5;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .analysis-icon {
            font-size: 30px;
            margin-right: 15px;
            background: white;
            width: 50px; height: 50px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .analysis-text-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        .analysis-text-desc {
            font-size: 14px;
            color: var(--text-dark);
        }

        .recommend-section {
            background: var(--white);
            padding: 30px 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        /* 추천 여우언니 단독 특가 카드 약간 변형 */
        .rec-card {
            display: flex;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            text-decoration: none;
            color: inherit;
        }
        .rec-card img {
            width: 80px; height: 80px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
        }
        .rec-reason {
            display: inline-block;
            background: #ffe4ed;
            color: var(--primary-color);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <header class="header-sub">
        <div style="width: 24px;"></div>
        <div class="page-title">AI 분석 리포트</div>
        <a href="/index.php" class="close-btn">✕</a>
    </header>

    <main>
        <!-- 1. 분석 결과 요약 -->
        <section class="report-section">
            <div class="report-header">
                <div class="report-title">회원님의 얼굴 분석 결과</div>
                <div class="report-subtitle">AI가 측정한 윤곽 및 피부 밸런스입니다.</div>
            </div>

            <div class="analysis-card">
                <div class="analysis-icon">📐</div>
                <div>
                    <div class="analysis-text-title">안면 비대칭 감지</div>
                    <div class="analysis-text-desc">좌우 턱선 비율이 약 12% 비대칭입니다.</div>
                </div>
            </div>
            
            <div class="analysis-card">
                <div class="analysis-icon">💧</div>
                <div>
                    <div class="analysis-text-title">탄력 저하 의심</div>
                    <div class="analysis-text-desc">하관 주변의 탄력이 약간 저하된 상태입니다.</div>
                </div>
            </div>
        </section>

        <!-- 2. 맞춤 시술 추천 -->
        <section class="recommend-section">
            <div class="section-title">✨ AI 맞춤 추천 시술</div>
            
            <?php foreach($recommended_events as $event): ?>
            <a href="/events/detail.php?id=<?= $event['id'] ?>" class="rec-card">
                <img src="<?= $event['image_url'] ?>" alt="<?= htmlspecialchars($event['title']) ?>" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2280%22%20height%3D%2280%22%20viewBox%3D%220%200%2080%2080%22%3E%3Crect%20fill%3D%22%23eee%22%20width%3D%2280%22%20height%3D%2280%22%2F%3E%3C%2Fsvg%3E'">
                <div style="flex: 1;">
                    <div class="rec-reason">💡 <?= $event['reason'] ?></div>
                    <div style="font-size: 15px; font-weight: 700; margin-bottom: 4px;"><?= htmlspecialchars($event['title']) ?></div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 8px;"><?= htmlspecialchars($event['hospital_name']) ?></div>
                    <div style="font-size: 16px; font-weight: 800; color: #333;">
                        <?= number_format($event['discount_price']) ?>원
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            
            <div style="margin-top: 20px;">
                <a href="/events/list.php" style="display: block; text-align: center; padding: 15px; background: var(--primary-color); color: white; border-radius: 12px; font-weight: 700; text-decoration: none;">
                    추천 시술 더보기
                </a>
            </div>
        </section>
    </main>
</body>
</html>


