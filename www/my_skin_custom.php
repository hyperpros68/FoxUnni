<?php
// my_skin_custom.php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>내 피부 맞춤 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #fafafa; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .header { display: flex; align-items: center; padding: 15px 20px; background: #fff; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid #eee; }
        .back-btn { font-size: 20px; cursor: pointer; color: #333; text-decoration: none; margin-right: 15px; }
        .title { font-size: 18px; font-weight: 700; color: #333; flex: 1; text-align: center; margin-right: 35px; }
        
        .container { padding: 30px 20px; }
        
        /* Premium Hero Section */
        .hero { text-align: center; margin-bottom: 40px; }
        .hero-icon { font-size: 60px; margin-bottom: 20px; display: inline-block; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        .hero-title { font-size: 24px; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.4; }
        .hero-title span { color: var(--primary-color); }
        .hero-desc { font-size: 15px; color: #666; line-height: 1.6; word-break: keep-all; }
        
        /* Options Card */
        .option-card { background: #fff; border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-title { font-size: 18px; font-weight: 700; color: #333; display: flex; align-items: center; gap: 8px; }
        .badge { background: #ffe4ed; color: var(--primary-color); font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 12px; }
        
        .card-desc { font-size: 14px; color: #777; line-height: 1.5; margin-bottom: 20px; }
        
        /* Buttons */
        .premium-btn { display: block; width: 100%; text-align: center; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: #fff; font-size: 16px; font-weight: 700; padding: 18px; border-radius: 14px; text-decoration: none; box-shadow: 0 5px 15px rgba(245,87,108,0.3); transition: all 0.3s ease; }
        .premium-btn:active { transform: scale(0.98); }
        
        .outline-btn { display: block; width: 100%; text-align: center; background: #fff; color: var(--primary-color); border: 2px solid var(--primary-color); font-size: 16px; font-weight: 700; padding: 16px; border-radius: 14px; text-decoration: none; transition: all 0.3s ease; box-sizing: border-box; }
        .outline-btn:active { background: #fdf5f6; transform: scale(0.98); }
        
        /* Border accents */
        .card-primary { border: 1px solid rgba(255,182,193,0.3); }
        .card-primary::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--primary-color); }
        
        .card-secondary { border: 1px solid #eee; }
        .card-secondary::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #ccc; }
    </style>
</head>
<body>
    <header class="header">
        <a href="/index.php" class="back-btn">←</a>
        <div class="title">내 피부 맞춤</div>
    </header>

    <div class="container">
        <div class="hero">
            <div class="hero-icon">✨</div>
            <h1 class="hero-title">당신만을 위한<br><span>초개인화 뷰티 큐레이션</span></h1>
            <p class="hero-desc">여우언니의 첨단 AI가 분석한 데이터로<br>고객님의 현재 피부 상태에 가장 최적화된<br>프리미엄 시술 솔루션을 제안합니다.</p>
        </div>

        <div class="option-card card-primary">
            <div class="card-header" style="align-items: flex-start;">
                <div class="card-title" style="flex-direction: column; align-items: flex-start; gap: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        💾 스마트 솔루션 불러오기
                        <div class="badge">추천</div>
                    </div>
                    <a href="#" onclick="showSavedSummary(); return false;" style="font-size: 12px; color: var(--primary-color); text-decoration: none; font-weight: 500; background: #fff0f5; padding: 4px 10px; border-radius: 12px; border: 1px solid #ffb6c1; display: inline-flex; align-items: center;">이전 AI 분석결과 상세보기 〉</a>
                </div>
            </div>

            <div id="savedSummaryBox" style="display: none; background: #fff0f5; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px dashed #ffb6c1; position: relative;">
                <div onclick="document.getElementById('savedSummaryBox').style.display='none';" style="position: absolute; top: 12px; right: 15px; cursor: pointer; color: #ffb6c1; font-size: 20px; line-height: 1; font-weight: bold;">×</div>
                <div style="font-weight: 700; color: var(--primary-color); margin-bottom: 8px; font-size: 14px; padding-right: 20px;">💕 나의 뷰티 요약 리포트</div>
                <div style="font-size: 13px; color: #555; line-height: 1.5; margin-bottom: 12px;">
                    <span style="font-weight: 600; color: #333;">피부 나이:</span> <span id="sumAge" style="color: var(--primary-color); font-weight: 700;"></span><br>
                    <span style="font-weight: 600; color: #333;">진단 요약:</span> <span id="sumDesc"></span>
                </div>
                <div style="font-weight: 700; color: #333; margin-bottom: 5px; font-size: 13px;">✨ 추천 시술</div>
                <div style="font-size: 13px; color: #555; line-height: 1.6; background: #fff; padding: 10px; border-radius: 8px;">
                    <div style="margin-bottom: 8px;">
                        <span style="color: var(--primary-color); font-weight: 700;">✔</span> <span id="sumPetit" style="font-weight: 600; color:#333;"></span>
                        <div id="sumPetitDesc" style="font-size: 11px; color: #888; padding-left: 14px; margin-top: 2px;"></div>
                    </div>
                    <div>
                        <span style="color: var(--primary-color); font-weight: 700;">✔</span> <span id="sumSurgery" style="font-weight: 600; color:#333;"></span>
                        <div id="sumSurgeryDesc" style="font-size: 11px; color: #888; padding-left: 14px; margin-top: 2px;"></div>
                    </div>
                </div>
            </div>

            <p class="card-desc">이전에 진행했던 AI 안면 및 피부 스캔 기록을 바탕으로 즉각적인 맞춤형 시술 추천 리스트를 확인합니다.</p>
            <a href="#" onclick="goToRecommend(); return false;" class="premium-btn">기존 AI 분석 결과로 시술 추천받기</a>
        </div>

        <div class="option-card card-secondary">
            <div class="card-header">
                <div class="card-title">🔍 AI 정밀 재진단</div>
            </div>
            <p class="card-desc">피부 컨디션이 바뀌었거나 최근 스캔 기록이 없다면, AI 스캐너를 통해 현재 상태를 새롭게 분석합니다.</p>
            <a href="/ai_face_analysis.php" class="outline-btn">AI 스캐너로 다시 진단하기</a>
        </div>
    </div>

    <script>
        function showSavedSummary() {
            const savedStr = localStorage.getItem('saved_ai_analysis');
            if (savedStr) {
                try {
                    let data = JSON.parse(savedStr);

                    // 강제 예외 처리: 과거에 분석하여 '성형 수술'이 저장되어 있는 경우 피부 시술로 자동 변환
                    const legacySurgeries = ["자연유착 쌍꺼풀", "직반버선 코성형", "미니 안면윤곽", "눈밑지방재배치", "풀페이스 필러"];
                    if (legacySurgeries.includes(data.surgeryTitle) || legacySurgeries.includes(data.petitTitle)) {
                        let ageStr = String(data.age).replace(/[^0-9]/g, '');
                        let age = parseInt(ageStr) || 30;
                        if (age < 25) {
                            data.petitTitle = "아쿠아필 + 크라이오";
                            data.petitDesc = "모공 속 피지와 노폐물을 자극 없이 비워내고 피부 온도를 낮춰 홍조와 트러블을 예방합니다.";
                            data.surgeryTitle = "엑셀V 레이저";
                            data.surgeryDesc = "초기 색소 병변과 미세한 안면 홍조를 맑고 깨끗하게 지워 투명한 피부톤을 완성합니다.";
                        } else if (age < 35) {
                            data.petitTitle = "리쥬란 힐러";
                            data.petitDesc = "진피층부터 수분과 콜라겐을 꽉 채워 건조함을 잡고 쫀쫀한 광채 피부를 완성합니다.";
                            data.surgeryTitle = "인모드 리프팅";
                            data.surgeryDesc = "볼살과 이중턱의 불필요한 지방을 정리하고 피부 표면의 탄력을 끌어올려 세련된 윤곽을 만듭니다.";
                        } else {
                            data.petitTitle = "스킨보톡스";
                            data.petitDesc = "얼굴 전체의 미세 주름을 팽팽하게 펴고 모공을 조여 즉각적인 타이트닝 효과를 부여합니다.";
                            data.surgeryTitle = "울쎄라 리프팅";
                            data.surgeryDesc = "깊은 근막층까지 강력한 초음파 에너지를 전달해 처진 피부를 확실하게 끌어올립니다.";
                        }
                        localStorage.setItem('saved_ai_analysis', JSON.stringify(data)); // 변경된 데이터 덮어쓰기
                    }

                    document.getElementById('sumAge').innerText = data.age;
                    document.getElementById('sumDesc').innerText = data.descText;
                    document.getElementById('sumPetit').innerText = data.petitTitle;
                    document.getElementById('sumPetitDesc').innerText = data.petitDesc;
                    document.getElementById('sumSurgery').innerText = data.surgeryTitle;
                    document.getElementById('sumSurgeryDesc').innerText = data.surgeryDesc;
                    
                    const box = document.getElementById('savedSummaryBox');
                    // Toggle visibility
                    if (box.style.display === 'none') {
                        box.style.display = 'block';
                    } else {
                        box.style.display = 'none';
                    }
                } catch(e) {
                    alert("분석 기록을 불러오는데 실패했습니다.");
                }
            } else {
                alert("저장된 AI 분석 기록이 없습니다. 먼저 하단의 [AI 스캐너로 다시 진단하기]를 진행해 주세요.");
            }
        }
        function goToRecommend() {
            const savedStr = localStorage.getItem('saved_ai_analysis');
            if (!savedStr) {
                alert('저장된 AI 분석 기록이 없습니다.\n먼저 하단의 [AI 스캐너로 다시 진단하기]를 진행해 주세요.');
                return;
            }
            try {
                const data = JSON.parse(savedStr);

                // petitTitle에서 첫 번째 키워드 추출 (예: "아쿠아필 + 크라이오" → "아쿠아필")
                const petitKeyword = (data.petitTitle || '').split(/[\s+\+]+/)[0].trim();
                const surgeryKeyword = (data.surgeryTitle || '').split(/[\s+\+]+/)[0].trim();

                // 두 키워드를 붙여서 검색 (첫 번째 시술 기준으로 이동)
                const keyword = petitKeyword || surgeryKeyword || '피부';
                window.location.href = `/events/list.php?keyword=${encodeURIComponent(keyword)}`;
            } catch(e) {
                window.location.href = '/events/list.php?category=피부';
            }
        }
    </script>
</body>
</html>


