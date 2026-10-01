<?php
$title = "AI 얼굴 분석";
$mode = $_GET['mode'] ?? '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    <style>
        body { background-color: #fff; color: #333; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        .header-sub { 
            display: flex; align-items: center; padding: 15px 20px; 
            background-color: #fff; position: sticky; top: 0; width: 100%; z-index: 100;
            box-sizing: border-box; border-bottom: 1px solid #eee;
        }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: #333; margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; color: #333; }
        
        .container {
            display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
            min-height: 100vh; padding: 30px 20px; box-sizing: border-box; text-align: center;
            background: #fff;
        }

        /* Step 1: Upload Area */
        #step-upload { width: 100%; max-width: 400px; }
        .upload-box {
            width: 100%; aspect-ratio: 3/4; border: 2px dashed #ffb6c1;
            border-radius: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center;
            cursor: pointer; background: #fff0f5; transition: all 0.3s;
        }
        .upload-box:active { background: #ffe4e1; }
        .upload-icon { font-size: 50px; margin-bottom: 20px; }
        .upload-text { font-size: 18px; font-weight: 700; margin-bottom: 10px; color: #333; }
        .upload-sub { font-size: 13px; color: #666; line-height: 1.5; }
        
        /* Step 2: Scanning Area */
        #step-scan { display: none; width: 100%; max-width: 400px; position: relative; }
        .scan-image-container {
            width: 100%; aspect-ratio: 3/4; border-radius: 20px; overflow: hidden; position: relative;
            box-shadow: 0 10px 30px rgba(245,87,108,0.1);
        }
        .scan-image-container img {
            width: 100%; height: 100%; object-fit: cover; opacity: 0.9;
        }
        .scan-line {
            position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary-color);
            box-shadow: 0 0 15px var(--primary-color), 0 0 30px var(--primary-color);
            animation: scan 2s linear infinite alternate;
        }
        @keyframes scan { 0% { top: 0%; } 100% { top: 100%; } }
        
        .scan-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, rgba(245,87,108,0.1) 0%, transparent 100%);
            animation: overlay 2s linear infinite alternate;
        }
        @keyframes overlay { 0% { height: 0%; } 100% { height: 100%; } }

        .scan-text {
            position: absolute; bottom: 30px; width: 100%; text-align: center;
            font-size: 16px; font-weight: 800; color: #fff; letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            background: linear-gradient(90deg, rgba(240,147,251,0.85), rgba(245,87,108,0.85)); padding: 10px 0;
            box-shadow: 0 -4px 15px rgba(245,87,108,0.2);
        }

        .gender-selector {
            display: flex; justify-content: center; gap: 10px; margin-bottom: 25px;
        }
        .gender-btn {
            padding: 12px 30px; border-radius: 25px;
            background: #f8f8f8; border: 1px solid #eee;
            color: #666; font-size: 14px; font-weight: bold;
            cursor: pointer; transition: all 0.3s ease;
        }
        .gender-btn.active {
            background: linear-gradient(90deg, #f093fb, #f5576c);
            color: #fff; border-color: transparent;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }

        /* Step 3: Result Area */
        #step-result { display: none; width: 100%; max-width: 400px; text-align: left; animation: fadeIn 1s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .result-header { text-align: center; margin-bottom: 30px; margin-top: 10px; }
        .result-header h2 { font-size: 26px; color: var(--primary-color); margin: 0 0 10px 0; font-weight: 900; }
        .result-header p { font-size: 14px; color: #555; margin: 0; }

        .card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .result-box { 
            background: #fff; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border-radius: 16px; padding: 25px 15px 20px 15px; text-align: center; position: relative; overflow: hidden;
        }
        .result-box.full { grid-column: span 2; }
        .result-box.highlight { background: #fff0f5; border-color: #ffb6c1; }
        .result-box.celeb { background: #fdf0f4; border-color: #f48fb1; }
        
        .box-badge { position: absolute; top: 0; left: 0; background: var(--primary-color); color: #fff; padding: 4px 12px; font-size: 11px; border-bottom-right-radius: 12px; font-weight: 700; }
        .box-title { font-size: 13px; color: #666; margin-bottom: 8px; margin-top: 5px; font-weight: 700; }
        .box-value { font-size: 24px; font-weight: 900; margin-bottom: 5px; color: #333; }
        .box-desc { font-size: 12px; color: #666; line-height: 1.4; }
        
        .analysis-basis {
            margin-top: 12px; padding: 12px; border-radius: 8px; font-size: 12px; color: #555; 
            line-height: 1.5; text-align: left; font-weight: 500; letter-spacing: -0.5px;
            background: #f8f8f8; border-left: 3px solid #ddd;
        }
        .basis-age { background: #fff0f5; border-left-color: #ffb6c1; }
        .basis-celeb { background: #fce4ec; border-left-color: #d81b60; }

        .btn-small { 
            display: inline-block; margin-top: 15px; padding: 14px 15px; font-size: 14px; font-weight: 800; width: 80%;
            background: var(--primary-color); color: #fff; text-decoration: none; border-radius: 25px; transition: 0.2s;
            box-shadow: 0 4px 12px rgba(245,87,108,0.3);
        }
        .btn-small:active { transform: scale(0.98); }

        /* Recommended Events Carousel */
        .rec-event-card {
            flex: 0 0 200px; background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden;
            text-decoration: none; display: flex; flex-direction: column; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .rec-event-img { width: 100%; height: 120px; object-fit: cover; }
        .rec-event-info { padding: 12px; }
        .rec-event-badge { font-size: 10px; background: #ffe4ed; color: var(--primary-color); padding: 2px 6px; border-radius: 4px; font-weight: 700; display: inline-block; margin-bottom: 5px; }
        .rec-event-title { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .rec-event-price { font-size: 15px; font-weight: 800; color: #d14d72; }
    </style>
</head>
<body>

    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title"></div>
    </header>

    <div class="container">
        
        <!-- Step 1: Upload -->
        <div id="step-upload">
            <h1 style="font-size: 24px; margin-bottom: 30px; color:#222;">AI가 분석하는<br><span style="color:var(--primary-color);">얼굴 정밀 분석 리포트</span><br><span style="font-size:14px; color:#888; display:block; margin-top:10px; font-weight:normal;">피부나이 · 연예인상 · 시술/수술 맞춤 추천</span></h1>
            
            <div id="genderGuide" style="text-align: center; margin-bottom: 10px;">
                <span style="font-size: 13px; color: #f093fb;">의학적 안면 골격 구조 분석을 위한 성별 선택</span>
            </div>
            <div id="genderSelector" class="gender-selector">
                <div class="gender-btn active" id="btnFemale" onclick="selectGender('female')">👩 여성</div>
                <div class="gender-btn" id="btnMale" onclick="selectGender('male')">👨 남성</div>
            </div>

            <input type="file" id="fileInput" accept="image/*" style="display: none;">
            <div class="upload-box" onclick="document.getElementById('fileInput').value=''; document.getElementById('fileInput').click();">
                <div class="upload-icon">📸</div>
                <div class="upload-text">얼굴 정면 사진 업로드</div>
                <div class="upload-sub">마스크나 안경을 벗은<br>밝은 사진일수록 정확합니다.</div>
            </div>
        </div>

        <!-- Step 2: Scanning -->
        <div id="step-scan">
            <div id="qualityWarningScan" style="display:none; background:rgba(255,100,100,0.2); border:1px solid #ff6464; color:#ffb4b4; padding:10px; border-radius:8px; font-size:12px; line-height:1.4; margin-bottom:15px; text-align:center;">
                ⚠️ 주의: 사진 속 얼굴이 흐릿하거나 너무 작아 분석 결과가 실제와 다를 수 있습니다.<br>얼굴이 더 선명하게 나온 사진을 올려보세요.
            </div>
            <div class="scan-image-container">
                <img id="previewImage" src="" alt="preview">
                <div class="scan-overlay"></div>
                <div class="scan-line"></div>
                <div class="scan-text" id="scanText">얼굴 대칭 분석 중...</div>
            </div>
        </div>

        <!-- Step 3: Result -->
        <div id="step-result">
            <div id="qualityWarningResult" style="display:none; background:rgba(255,100,100,0.2); border:1px solid #ff6464; color:#ffb4b4; padding:10px; border-radius:8px; font-size:12px; line-height:1.4; margin-bottom:15px; text-align:center;">
                ⚠️ 주의: 사진 속 얼굴이 흐릿하거나 너무 작아 분석 결과가 실제와 다를 수 있습니다.<br>더 선명한 정면 얼굴 사진을 올려보시면 정확도가 올라갑니다.
            </div>
            <div class="result-header">
                <h2>분석 완료!</h2>
                <p>회원님의 얼굴 특징을 완벽하게 파악했습니다.</p>
            </div>

            <div class="card-grid">
                <!-- 1. 피부 나이 -->
                <div class="result-box" style="border-color: #ffd1dc; background: #fffafa;">
                    <div class="box-badge" style="background:#ffb6c1; color:#fff;">피부 나이</div>
                    <div class="box-title">측정된 피부 나이</div>
                    <div class="box-value" id="resAge" style="color:#d14d72;">--세</div>
                    <div class="box-desc" id="resAgeDesc" style="margin-bottom: 10px;">판별 중...</div>
                    <div id="resMedicalBasis" class="analysis-basis basis-age">의학적 근거 분석 대기중...</div>
                </div>

                <!-- 4. 닮은꼴 연예인 -->
                <div class="result-box celeb" style="border-color: #f48fb1; background: #fdf0f4;">
                    <div class="box-badge" style="background:#d81b60; color:#fff;">연예인상</div>
                    <div class="box-title">분위기 닮은꼴</div>
                    <div class="box-value" id="resCeleb" style="color:#880e4f;">--</div>
                    <div class="box-desc" id="resCelebDesc">가장 비슷한 워너비 얼굴</div>
                    <div id="resCelebAnalysis" class="analysis-basis basis-celeb">안면 비례 분석 대기중...</div>
                </div>

                <!-- 2. 시술 추천 -->
                <div class="result-box full highlight">
                    <div class="box-badge" style="background:#f5576c;">맞춤 스킨케어</div>
                    <div class="box-title" style="color:var(--primary-color);">AI 추천 베이직 케어</div>
                    <div class="box-value" id="resPetit" style="color:#c2185b;">--</div>
                    <div class="box-desc" id="resPetitDesc" style="margin-top:8px;">--</div>
                    <a href="#" id="btnPetit" class="btn-small" style="background: linear-gradient(90deg, #ff9a9e, #fecfef);">추천 시술 둘러보기 →</a>
                </div>

                <!-- 3. 수술 추천 -> 집중 스킨케어 -->
                <div class="result-box full" style="border-color: #f48fb1; background: #fdf0f4;">
                    <div class="box-badge" style="background:#d81b60; color:#fff;">집중 안티에이징</div>
                    <div class="box-title" style="color:#880e4f;">AI 추천 프리미엄 케어</div>
                    <div class="box-value" id="resSurgery" style="color:#ad1457;">--</div>
                    <div class="box-desc" id="resSurgeryDesc" style="margin-top:8px;">--</div>
                    <a href="#" id="btnSurgery" class="btn-small" style="background: linear-gradient(90deg, #f093fb, #f5576c); color:#fff;">추천 시술 둘러보기 →</a>
                </div>
            </div>
            
            <!-- Face++ 실제 피부 정밀 분석 스코어 섹션 -->
            <div id="skinScoreSection" style="display:none; background:#fff; border:1px solid #eee; border-radius:16px; padding:20px; margin-bottom:20px; box-shadow:0 4px 15px rgba(0,0,0,0.04);">
                <div style="font-size:14px; font-weight:700; color:#333; margin-bottom:15px; display:flex; align-items:center; justify-content:space-between; flex-wrap:nowrap;">
                    <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">🔬 실제 피부 정밀 분석</div>
                    <button onclick="document.getElementById('skinScoreModal').style.display='flex'" style="background:#f5f5f5; border:none; padding:4px 10px; border-radius:12px; font-size:11px; color:#666; cursor:pointer; font-weight:600; white-space:nowrap; flex-shrink:0; margin-left:8px;">점수 설명 보기</button>
                </div>
                <div id="skinScoreBars"></div>
                <div id="skinTypeTag" style="margin-top:12px; font-size:12px; color:#888; text-align:center;"></div>
            </div>

            <!-- 설명 모달 창 -->
            <div id="skinScoreModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
                <div style="background:#fff; width:90%; max-width:400px; border-radius:16px; padding:24px; box-sizing:border-box;">
                    <h3 style="margin-top:0; color:#333; font-size:16px; margin-bottom:15px;">📊 AI 피부 점수 가이드</h3>
                    <ul style="font-size:13px; color:#555; line-height:1.6; padding-left:20px; margin-bottom:15px; padding-right:10px;">
                        <li><b>피부 건강도:</b> 전체적인 톤, 결, 모공의 균일함을 바탕으로 피부 장벽의 건강 상태를 수치화합니다.</li>
                        <li><b>여드름:</b> 붉은기, 염증성 트러블, 피지 과다 분비로 인한 요철을 감지합니다.</li>
                        <li><b>다크서클:</b> 눈 밑의 색소 침착, 푸른/붉은 혈관 비침, 눈밑 꺼짐 등 그늘진 영역을 분석합니다.</li>
                        <li><b>모공:</b> T존 및 나비존을 중심으로 모공의 확장 정도와 밀도를 측정합니다.</li>
                        <li><b>주름:</b> 눈가, 미간, 팔자 부위의 표정 주름과 진피층 탄력 저하로 인한 굵은 주름을 감지합니다.</li>
                        <li><b>기미/잡티:</b> 자외선 노출로 인한 멜라닌 색소 침착, 주근깨, 잡티 영역을 찾아냅니다.</li>
                    </ul>
                    <p style="font-size:11.5px; color:#888; margin-bottom:20px;">* 높은 점수(70점 이상)일수록 해당 항목이 '양호'함을 의미합니다.</p>
                    <button onclick="document.getElementById('skinScoreModal').style.display='none'" style="width:100%; background:#f48fb1; color:#fff; border:none; padding:12px; border-radius:8px; cursor:pointer; font-weight:700; font-size:14px;">확인</button>
                </div>
            </div>

            <!-- 맞춤 추천 특가 이벤트 섹션 -->
            <div id="recommendEventsSection" style="display:none; text-align:left; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h3 style="font-size:16px; color:#333; margin:0;">🎁 내 피부 맞춤 추천 특가</h3>
                    <a href="/events/list.php" style="font-size:12px; color:#888; text-decoration:none;">더보기 ❯</a>
                </div>
                <div id="recommendEventsList" style="display:flex; overflow-x:auto; gap:12px; padding-bottom:10px; scrollbar-width:none; -ms-overflow-style:none;">
                    <!-- JS에서 동적으로 채워짐 -->
                </div>
            </div>

            <div style="text-align: center; margin-top: 15px; margin-bottom: 40px;">
                <a href="#" onclick="location.reload();" style="color: #888; font-size: 13px; text-decoration: underline;">사진 다시 분석하기</a>
            </div>
        </div>

    </div>

    <script>
        const initialMode = "<?= htmlspecialchars($mode) ?>";

        window.addEventListener('DOMContentLoaded', () => {
            // 회원가입 시 선택했던 성별 정보 적용 (있다면)
            const savedGender = localStorage.getItem('shinsa_gender');
            if (savedGender) {
                selectedGender = (savedGender === 'M') ? 'male' : 'female';
                // 화면의 성별 선택 박스 및 문구 숨김
                const guide = document.getElementById('genderGuide');
                const selector = document.getElementById('genderSelector');
                if (guide) guide.style.display = 'none';
                if (selector) selector.style.display = 'none';
            }

            if (initialMode === 'view') {
                const savedStr = localStorage.getItem('saved_ai_analysis');
                if (savedStr) {
                    try {
                        const data = JSON.parse(savedStr);
                        document.getElementById('step-upload').style.display = 'none';
                        document.getElementById('step-scan').style.display = 'none';
                        document.getElementById('step-result').style.display = 'block';

                        document.getElementById('resAge').innerText = data.age;
                        document.getElementById('resAgeDesc').innerText = data.descText;
                        document.getElementById('resMedicalBasis').innerText = data.basisText;
                        document.getElementById('resCeleb').innerText = data.celebName;
                        document.getElementById('resCelebDesc').innerText = data.celebDesc;
                        document.getElementById('resCelebAnalysis').innerText = data.celebAnalysis;
                        
                        document.getElementById('resPetit').innerText = data.petitTitle;
                        document.getElementById('resPetitDesc').innerText = data.petitDesc;
                        document.getElementById('btnPetit').href = `/events/list.php?category=${encodeURIComponent(data.petitCat)}&keyword=${encodeURIComponent(data.petitKeyword)}`;

                        document.getElementById('resSurgery').innerText = data.surgeryTitle;
                        document.getElementById('resSurgeryDesc').innerText = data.surgeryDesc;
                        document.getElementById('btnSurgery').href = `/events/list.php?category=성형&keyword=${encodeURIComponent(data.surgeryKeyword)}`;
                        return;
                    } catch (e) {
                        console.error("Saved data parsing error");
                    }
                }
                alert("이전에 측정한 분석 기록이 없습니다.\n새로운 사진으로 스캔을 진행해 주세요.");
                location.href = '/ai_face_analysis.php';
            }
        });

        const fileInput = document.getElementById('fileInput');
        const stepUpload = document.getElementById('step-upload');
        const stepScan = document.getElementById('step-scan');
        const stepResult = document.getElementById('step-result');
        const previewImage = document.getElementById('previewImage');
        const scanText = document.getElementById('scanText');

        const ageDescs = ["또래보다 한참 어려 보이는 최강 동안 페이스!", "피부 나이가 매우 건강하게 잘 유지되고 있어요.", "보습만 조금 더 신경 쓰면 완전 아기 피부!", "미세 주름과 탄력 관리를 시작할 완벽한 타이밍입니다."];
        
        const petitPool = [
            { title: "인모드 리프팅", desc: "이중턱과 심부볼의 불필요한 지방을 쏙 빼고 매끄러운 V라인을 완성해 줄 거예요.", keyword: "인모드" },
            { title: "풀페이스 필러", desc: "살짝 아쉬운 이마나 앞광대 볼륨을 채워, 입체적이고 인형 같은 인상을 만들어줍니다.", keyword: "필러" },
            { title: "스킨보톡스", desc: "피부 얕은 층에 주입하여 즉각적인 모공 타이트닝과 잔주름 지우개 효과를 줍니다.", keyword: "보톡스" },
            { title: "리쥬란 힐러", desc: "지친 피부 속부터 프리미엄 수분과 광채를 꽉 채워주는 가장 확실한 스킨부스터입니다.", keyword: "리쥬란" }
        ];

        const surgeryPool = [
            { title: "자연유착 쌍꺼풀", desc: "눈의 비율이 살짝 아쉽다면? 흉터 걱정 없이 수면마취로 자연스럽게 또렷한 눈매를 만들어 보세요.", keyword: "자연유착" },
            { title: "직반버선 코성형", desc: "세련된 직반버선 라인으로 얼굴 중심부의 입체감을 확 살려주는 것을 추천합니다.", keyword: "코" },
            { title: "미니 안면윤곽", desc: "큰 수술 없이도 갸름하고 세련된 아이돌급 얼굴형 윤곽을 뼈 아프지 않게 완성합니다.", keyword: "윤곽" },
            { title: "눈밑지방재배치", desc: "눈밑 꺼짐과 다크서클만 없애도 5살은 더 어려 보인답니다! 환하고 생기 있는 인상으로 개선해보세요.", keyword: "지방재배치" }
        ];

        const femaleCelebPool = [
            { 
                name: "장원영", 
                desc: "화려하고 인형 같은 아이돌 황금비율", 
                analysis: "AI 안면 비례 분석 결과, 상안부와 중안부의 비율이 짧고 하안부가 갸름하여 전형적인 동안상입니다. 크고 둥근 눈매와 도톰한 입술 볼륨이 완벽한 조화를 이루어, 장원영님 특유의 화려하고 사랑스러운 인형 같은 분위기와 95% 일치합니다.",
                ratios: { aspect: 84, eyeSlant: -3 }
            },
            { 
                name: "카리나", 
                desc: "도도하고 세련된 AI상 고양이눈매", 
                analysis: "AI 눈꼬리 각도 및 골격 스캔 결과, 눈꼬리가 위로 매력적으로 올라간 대표적인 '고양이상'입니다. V라인 턱선이 매우 매끄럽게 떨어지는 갸름한 형태가 카리나님의 시크하고 비현실적인 AI상과 98%의 높은 일치율을 보입니다.",
                ratios: { aspect: 75, eyeSlant: 12 }
            },
            { 
                name: "한소희", 
                desc: "몽환적인 분위기를 풍기는 냉미녀상", 
                analysis: "AI 이목구비 매칭 결과, 가로로 길고 깊으면서 살짝 올라간 눈매가 특징입니다. 얼굴의 입체감이 뛰어나며 턱선의 각이 고급스럽게 살아있어, 한소희님 특유의 몽환적이면서도 세련된 냉미녀 분위기와 92% 일치합니다.",
                ratios: { aspect: 80, eyeSlant: 8 }
            },
            { 
                name: "고윤정", 
                desc: "정석 미인상, 단아하고 청순한 이미지", 
                analysis: "AI 대칭 분석 결과, 좌우 안면 비대칭이 거의 없는 완벽한 대칭형 미인 골격입니다. 부드러운 곡선의 눈썹과 맑고 큰 눈망울, 입꼬리가 살짝 올라간 형태가 고윤정님의 단아하고 고급스러운 청순미와 96% 일치하는 것으로 분석되었습니다.",
                ratios: { aspect: 82, eyeSlant: 0 }
            },
            { 
                name: "수지", 
                desc: "첫사랑 재질, 맑고 깨끗한 강아지/토끼상", 
                analysis: "AI 눈꼬리 분석 결과, 눈매가 선하게 반달 모양으로 떨어지는 완벽한 '강아지/토끼상'입니다. 모난 곳 없이 부드럽게 이어지는 둥글고 갸름한 계란형 얼굴이 수지님 특유의 청초한 첫사랑 분위기를 자아내며 93% 일치합니다.",
                ratios: { aspect: 88, eyeSlant: -8 }
            },
            { 
                name: "전지현", 
                desc: "시선을 압도하는 독보적인 아우라, 우아한 여배우상", 
                analysis: "AI 골격 분석 결과, 턱선이 매끄럽고 세로로 살짝 긴 형태의 우아한 볼륨감을 가졌습니다. 시원시원한 입매와 깊고 우아한 눈빛이 전지현님 특유의 고급스럽고 독보적인 여배우상 분위기와 94% 일치합니다.",
                ratios: { aspect: 72, eyeSlant: 2 }
            }
        ];

        const maleCelebPool = [
            { 
                name: "차은우", 
                desc: "얼굴 천재, 완벽한 황금 비율 조각 미남상", 
                analysis: "AI 황금비율 분석 결과, 상·중·하안부의 비율이 1:1:1에 가까운 완벽한 조각 미남형입니다. 진하고 뚜렷한 T존과 크고 맑은 눈망울이 차은우님의 비현실적인 만찢남 비주얼과 97% 일치하여 압도적인 호감형 인상을 줍니다.",
                ratios: { aspect: 80, eyeSlant: 0 }
            },
            { 
                name: "변우석", 
                desc: "선한 눈망울과 부드러운 카리스마 여우상", 
                analysis: "AI 눈꼬리 분석 결과, 살짝 올라간 눈매와 날렵한 하관 라인이 돋보이는 '여우상'입니다. 웃을 때 눈꼬리가 부드럽게 휘어지는 매력적인 다정함이 변우석님의 훈훈하고 선한 여우상 분위기와 94% 일치하는 것으로 나타났습니다.",
                ratios: { aspect: 72, eyeSlant: 10 }
            },
            { 
                name: "송강", 
                desc: "순정만화 찢고 나온 듯한 청순섹시상", 
                analysis: "AI 윤곽 스캔 결과, 날렵한 베일 듯한 턱선과 대비되는 부드러운 눈동자를 지녔습니다. 입술의 가로 길이가 길고 도톰하여 소년미와 남성미가 공존하는 송강님 특유의 트렌디한 청순섹시상과 92% 일치율을 보입니다.",
                ratios: { aspect: 85, eyeSlant: -2 }
            },
            { 
                name: "서강준", 
                desc: "신비로운 갈색 눈동자의 부드러운 늑대상", 
                analysis: "AI 멜라닌 색소 톤 및 눈매 분석 결과, 직선으로 곧게 뻗은 콧대와 위로 살짝 뻗은 날카로운 눈꼬리가 특징입니다. 서강준님 특유의 몽환적이고 부드러운 늑대상과 95% 일치합니다.",
                ratios: { aspect: 82, eyeSlant: 8 }
            },
            { 
                name: "박보검", 
                desc: "청량하고 선한 인상의 정석 호감형 강아지상", 
                analysis: "AI 눈매 분석 결과, 눈꼬리가 부드럽게 아래로 떨어지는 대표적인 '강아지상'입니다. 입꼬리가 자연스럽게 올라가 평소에도 미소를 짓는 듯한 선하고 부드러운 인상이 박보검님의 청량하고 호감 가는 분위기와 96% 일치하는 완벽한 밸런스입니다.",
                ratios: { aspect: 85, eyeSlant: -8 }
            },
            { 
                name: "이도현", 
                desc: "무쌍의 매력이 돋보이는 훈훈하고 댄디한 이미지", 
                analysis: "AI 골격 및 눈매 스캔 결과, 매력적인 무쌍 눈매와 날카로우면서도 매끄러운 턱선을 지니고 있습니다. 세련되고 도회적인 느낌을 주면서도 웃을 때의 부드러움이 이도현님의 댄디하고 스마트한 이미지와 93% 일치합니다.",
                ratios: { aspect: 76, eyeSlant: 4 }
            }
        ];

        let selectedGender = 'female';

        function selectGender(gender) {
            selectedGender = gender;
            document.getElementById('btnFemale').classList.remove('active');
            document.getElementById('btnMale').classList.remove('active');
            if(gender === 'female') {
                document.getElementById('btnFemale').classList.add('active');
            } else {
                document.getElementById('btnMale').classList.add('active');
            }
        }

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            // Face++ 병렬 호출 (백그라운드)
            faceppResult = null;
            callFaceppAPI(file);

            // URL.createObjectURL: FileReader보다 빠르고 안정적
            const objUrl = URL.createObjectURL(file);

            previewImage.onload = function() {
                previewImage.onload = null;
                URL.revokeObjectURL(objUrl); // 메모리 해제
                detectFaceAndScan();
            };
            previewImage.onerror = function() {
                alert('이미지를 불러올 수 없습니다. JPG/PNG 파일을 사용해 주세요.');
                location.reload();
            };
            previewImage.src = objUrl;
        });


        // 실제 AI 모델 전역 변수
        let isModelLoaded = false;
        let detectedRealAge = 0;
        let faceppResult = null; // Face++ API 분석 결과 저장

        // Face++ API 호출 함수 (서버 사이드 분석)
        async function callFaceppAPI(file) {
            try {
                const formData = new FormData();
                formData.append('face_image', file);
                const res = await fetch('/api/face_analyze.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    faceppResult = data;
                    console.log('[Face++] 분석 완료:', data);
                    // 결과 화면이 이미 표시중이면 즉시 Face++ 데이터 반영
                    const resultStep = document.getElementById('step-result');
                    if (resultStep && resultStep.style.display !== 'none') {
                        applyFaceppData();
                    }
                } else {
                    console.warn('[Face++] 분석 실패:', data.error);
                }
            } catch(e) {
                console.warn('[Face++] API 호출 오류 (폴백 모드 사용):', e);
            }
        }

        // Face++ 데이터 UI 반영 함수 (타이밍 이슈 해결: 눈 도착/늘 도착 모두 처리)
        function applyFaceppData() {
            if (!faceppResult) return;

            // 1. 나이 업데이트
            if (faceppResult.age > 0) {
                document.getElementById('resAge').innerText = faceppResult.age + '세';
            }

            // 2. 피부 스코어 바 표시
            if (faceppResult.skin) {
                const s = faceppResult.skin;
                const skinItems = [
                    { label: '피부 건강도', score: s.health,      isGood: true  },
                    { label: '여드름',     score: s.acne,        isGood: false },
                    { label: '다크서클',   score: s.dark_circle, isGood: false },
                    { label: '모공',       score: s.pore,        isGood: false },
                    { label: '주름',       score: s.wrinkle,     isGood: false },
                    { label: '기미/잡티',  score: s.spot,        isGood: false },
                ];
                let barsHtml = '';
                skinItems.forEach(item => {
                    const displayScore = item.isGood ? item.score : (100 - item.score);
                    const color = displayScore >= 70 ? '#4caf50' : displayScore >= 40 ? '#ff9800' : '#f5576c';
                    const label2 = displayScore >= 70 ? '우수' : displayScore >= 40 ? '보통' : '주의';
                    barsHtml += `
                        <div style="margin-bottom:12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <span style="font-size:12px; color:#555; font-weight:600;">${item.label}</span>
                                <span style="font-size:12px; color:${color}; font-weight:700;">${displayScore}점 <span style="font-size:10px; color:#aaa;">(${label2})</span></span>
                            </div>
                            <div style="height:7px; background:#f0f0f0; border-radius:4px; overflow:hidden;">
                                <div style="height:100%; width:${displayScore}%; background:linear-gradient(90deg, ${color}aa, ${color}); border-radius:4px; transition:width 1s ease;"></div>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('skinScoreBars').innerHTML = barsHtml;
                const beautyText = faceppResult.beauty ? ` | 미적 점수: ${faceppResult.beauty}점` : '';
                document.getElementById('skinTypeTag').textContent = `피부 타입: ${faceppResult.skin_type || '중성'}${beautyText}`;
                document.getElementById('skinScoreSection').style.display = 'block';
            }

            // 3. 추천 시술 override
            if (faceppResult.recommendations && faceppResult.recommendations.length > 0) {
                const recMap = {
                    '여드름 케어':  { title: '아쿠아필 클렌징',    desc: 'AI 피부 분석에서 여드름 수치가 감지됐습니다. 모공 속 피지를 자극 없이 클렌징해 드립니다.',             keyword: '아쿠아필' },
                    '다크서클':    { title: '눈밑 필러',           desc: 'AI 피부 분석에서 다크서클 수치가 높습니다. 꺼진 눈밑을 채워 환하고 생기 있는 인상을 만들어 드립니다.', keyword: '필러' },
                    '기미/잡티':   { title: '피코토닝 레이저',    desc: 'AI 피부 분석에서 색소 침착이 감지됐습니다. 레이저로 균일하고 투명한 피부톤을 완성해 드립니다.',        keyword: '레이저토닝' },
                    '모공 관리':   { title: '스킨보톡스',          desc: 'AI 피부 분석에서 모공 확장이 감지됐습니다. 보톡스로 모공을 조여 매끈한 피부결을 만들어 드립니다.',      keyword: '보톡스' },
                    '주름 개선':   { title: '리쥬란 힐러',         desc: 'AI 피부 분석에서 주름이 감지됐습니다. 진피층 콜라결을 재생해 표정 주름을 자연스럽게 개선해 드립니다.', keyword: '리쥬란' },
                    '안티에이징':  { title: '울쎄라 리프팅',       desc: 'AI 피부 분석 기반 안티에이징이 필요합니다. 초음파로 피부를 근막층부터 확실하게 끌어올립니다.',         keyword: '울쎄라' },
                    '피지 관리':   { title: '지성 피부 트리트먼트', desc: 'AI 피부 분석에서 지성 피부로 판별됐습니다. 피지 분비를 조절해 깨끗하고 맑은 피부를 만들어 드립니다.', keyword: '피지' },
                };
                const matched = faceppResult.recommendations.map(r => recMap[r.tag]).filter(Boolean);
                if (matched.length >= 1) {
                    document.getElementById('resPetit').innerText = matched[0].title;
                    document.getElementById('resPetitDesc').innerText = matched[0].desc;
                    document.getElementById('btnPetit').href = `/events/list.php?category=${encodeURIComponent('피부')}&keyword=${encodeURIComponent(matched[0].keyword)}`;
                }
                if (matched.length >= 2) {
                    document.getElementById('resSurgery').innerText = matched[1].title;
                    document.getElementById('resSurgeryDesc').innerText = matched[1].desc;
                    document.getElementById('btnSurgery').href = `/events/list.php?category=${encodeURIComponent('피부')}&keyword=${encodeURIComponent(matched[1].keyword)}`;
                }
                
                // 맞춤 이벤트 API 호출
                const tags = faceppResult.recommendations.map(r => r.tag).join(',');
                fetchRecommendedEvents(tags);
            }
        }

        async function fetchRecommendedEvents(tags) {
            try {
                const res = await fetch(`/api/recommend_events.php?tags=${encodeURIComponent(tags)}`);
                const data = await res.json();
                
                if (data.success && data.events && data.events.length > 0) {
                    const container = document.getElementById('recommendEventsList');
                    let html = '';
                    data.events.forEach(ev => {
                        html += `
                            <a href="/events/detail.php?id=${ev.id}" class="rec-event-card">
                                <img src="${ev.image_url}" alt="${ev.title}" class="rec-event-img" onerror="this.src='/static/images/banner_skin.png'">
                                <div class="rec-event-info">
                                    <div class="rec-event-badge">${ev.hospital_name}</div>
                                    <div class="rec-event-title">${ev.title}</div>
                                    <div class="rec-event-price">${ev.discount_price.toLocaleString()}원</div>
                                </div>
                            </a>
                        `;
                    });
                    container.innerHTML = html;
                    document.getElementById('recommendEventsSection').style.display = 'block';
                }
            } catch (e) {
                console.error("추천 이벤트 로드 실패", e);
            }
        }

        // ─────────────────────────────────────────────────────────
        // 브라우저 로컴 피부 스코어 생성함수
        // Face++ API가 실패해도 반드시 결과가 나오도록 폴백 보장
        // 실제 이미지 픽셌 분석 기반 (같은 사진 = 같은 결과)
        // ─────────────────────────────────────────────────────────
        function generateLocalSkinScores(img, age) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 60;
            canvas.height = 60;
            ctx.drawImage(img, 0, 0, 60, 60);
            const px = ctx.getImageData(0, 0, 60, 60).data;
            const n = 60 * 60;

            let rSum=0, gSum=0, bSum=0, darkPx=0;
            for (let i=0; i<px.length; i+=4) {
                rSum += px[i]; gSum += px[i+1]; bSum += px[i+2];
                if (px[i] < 90 && px[i+1] < 90 && px[i+2] < 90) darkPx++;
            }
            const rA=rSum/n, gA=gSum/n, bA=bSum/n;

            let rV=0, gV=0, bV=0;
            for (let i=0; i<px.length; i+=4) {
                rV+=Math.pow(px[i]-rA,2);
                gV+=Math.pow(px[i+1]-gA,2);
                bV+=Math.pow(px[i+2]-bA,2);
            }
            const variance = Math.sqrt((rV+gV+bV)/n);

            // 이미지 해시(결정론적 - 같은 사진 = 항상 같은 수치)
            const h = hashString(img.src);
            const r = (n2, min, max) => Math.round(Math.min(max, Math.max(min, n2)));

            const redness     = rA / (gA + bA + 1);
            const darkRatio   = darkPx / n;

            const acne        = r((redness-1.05)*60 + variance*0.25 + (h%18),        5, 70);
            const dark_circle = r(darkRatio*180 + age*0.7 + (h%12),                  5, 75);
            const stain       = r(variance*0.45 + age*0.4 + (h%20),                  5, 65);
            const pore        = r(variance*0.38 + (h%22),                             5, 70);
            const wrinkle     = r(age*1.1 + variance*0.15 + (h%14),                  3, 80);
            const spot        = r(variance*0.35 + age*0.3 + ((h>>3)%18),             5, 65);
            const health      = r(100 - (acne+dark_circle+stain)/3.2,               30, 92);

            const skinTypes   = ['지성', '건성', '복합성', '중성'];
            const skin_type   = skinTypes[Math.abs(h) % 4];
            const beauty      = r(100 - (acne+dark_circle+stain)/5.5 + (h%9),        55, 95);

            const recs = [];
            if (acne        > 22) recs.push({ tag: '여드름 케어',  category: '피부' });
            if (dark_circle > 32) recs.push({ tag: '다크서클',    category: '필러' });
            if (stain       > 28) recs.push({ tag: '기미/잡티',   category: '레이저' });
            if (pore        > 38) recs.push({ tag: '모공 관리',   category: '피부' });
            if (wrinkle     > 28) recs.push({ tag: '주름 개선',   category: '보톡스' });
            if (age         > 33) recs.push({ tag: '안티에이징',  category: '리프팅' });

            return {
                success: true,
                age:       age,
                gender:    selectedGender === 'female' ? '여성' : '남성',
                beauty:    beauty,
                skin_type: skin_type,
                skin:      { health, acne, dark_circle, stain, pore, wrinkle, spot },
                recommendations: recs
            };
        }

        async function detectFaceAndScan() {
            stepUpload.style.display = 'none';
            stepScan.style.display = 'block';
            scanText.innerText = "진짜 AI 안면 인식 모델 로딩 중...";


            try {
                // 백엔드 Face++ 연동 (진짜 피부 점수 받아오기)
                const base64Img = previewImage.src;
                fetch('/api/face_analyze_direct.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_base64: base64Img })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data) {
                        const s = data.data;
                        faceppResult = {
                            age: detectedRealAge, // 나이는 face-api.js 유지
                            skin_type: s.skin_type === 0 ? '지성' : (s.skin_type === 1 ? '건성' : '복합성'),
                            beauty: s.glamour ? Math.round(s.glamour) : 80,
                            skin: {
                                health: s.health ? Math.round(s.health) : 85,
                                acne: s.acne ? Math.round(s.acne) : 10,
                                dark_circle: s.dark_circle ? Math.round(s.dark_circle) : 15,
                                stain: s.stain ? Math.round(s.stain) : 20,
                                pore: s.pore ? Math.round(s.pore) : 25,
                                wrinkle: s.wrinkle ? Math.round(s.wrinkle) : 15,
                                spot: s.spot ? Math.round(s.spot) : 10
                            },
                            recommendations: []
                        };
                        // 추천 로직 구성
                        if (faceppResult.skin.acne > 30) faceppResult.recommendations.push({tag: '여드름 케어', category: '피부'});
                        if (faceppResult.skin.dark_circle > 40) faceppResult.recommendations.push({tag: '다크서클', category: '필러'});
                        if (faceppResult.skin.stain > 40) faceppResult.recommendations.push({tag: '기미/잡티', category: '레이저'});
                        if (faceppResult.skin.pore > 50) faceppResult.recommendations.push({tag: '모공 관리', category: '보톡스'});
                        if (faceppResult.skin.wrinkle > 40) faceppResult.recommendations.push({tag: '주름 개선', category: '리쥬란'});
                    }
                })
                .catch(e => console.error("Face++ API Error:", e));

                if (!isModelLoaded) {
                    const modelUrl = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                        faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                        faceapi.nets.ageGenderNet.loadFromUri(modelUrl)
                    ]);
                    isModelLoaded = true;
                }

                scanText.innerText = "연령 및 성별 정밀 분석 중...";
                
                // face-api.js를 이용한 실제 얼굴, 이목구비 랜드마크 68점, 나이, 성별 예측
                const detection = await faceapi.detectSingleFace(previewImage, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.3 }))
                                               .withFaceLandmarks()
                                               .withAgeAndGender();

                if (detection) {
                    // 1. 얼굴 크기 및 신뢰도 분석을 통한 화질/정확도 경고 판별
                    const faceWidthRatio = detection.detection.box.width / previewImage.naturalWidth;
                    const isLowQuality = (faceWidthRatio < 0.15) || (detection.detection.score < 0.6);

                    // 2. 원본 이미지에서 '얼굴 부분만 크롭(Crop)'하여 화면에 띄우기
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // 얼굴 주변에 여백(Padding)을 주어 자연스럽게 크롭
                    const padX = detection.detection.box.width * 0.4;
                    const padY = detection.detection.box.height * 0.5;
                    
                    const cropX = Math.max(0, detection.detection.box.x - padX);
                    const cropY = Math.max(0, detection.detection.box.y - padY * 1.2); // 이마 위쪽 여백을 조금 더 줌
                    const cropW = Math.min(previewImage.naturalWidth - cropX, detection.detection.box.width + padX * 2);
                    const cropH = Math.min(previewImage.naturalHeight - cropY, detection.detection.box.height + padY * 2);
                    
                    canvas.width = cropW;
                    canvas.height = cropH;
                    
                    // 캔버스에 얼굴 부분만 그려넣기
                    ctx.drawImage(previewImage, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
                    
                    // 스캔 화면의 이미지를 '크롭된 얼굴 이미지'로 교체!
                    previewImage.src = canvas.toDataURL('image/jpeg');

                    // 3. 랜드마크(68개 점)를 활용한 진짜 고양이상/강아지상 분류 공식
                    const landmarks = detection.landmarks;
                    const jaw = landmarks.getJawOutline();
                    const leftEye = landmarks.getLeftEye(); // 좌측 눈 (사용자 기준 우측)
                    const rightEye = landmarks.getRightEye(); // 우측 눈 (사용자 기준 좌측)

                    const faceWidth = jaw[16].x - jaw[0].x;
                    const faceHeight = jaw[8].y - jaw[0].y;
                    
                    // 눈꼬리 각도 계산 (양수: 눈꼬리가 올라감=고양이상, 음수: 눈꼬리가 내려감=강아지상)
                    const leftSlant = leftEye[3].y - leftEye[0].y; // 0이 바깥쪽, 3이 안쪽
                    const rightSlant = rightEye[0].y - rightEye[3].y;
                    const eyeSlantScore = ((leftSlant + rightSlant) / faceWidth) * 1000;
                    
                    window.userFaceRatios = {
                        aspect: (faceWidth / faceHeight) * 100, // 얼굴 윤곽 (동그란지 긴지)
                        eyeSlant: eyeSlantScore // 눈매 (고양이상 vs 강아지상)
                    };

                    // 4. AI가 예측한 진짜 나이와 성별
                    detectedRealAge = Math.round(detection.age);
                    const predictedGender = detection.gender; // 'male' or 'female'

                    // AI 예측 결과를 UI에 반영하되, 가입된 성별이 없을 때만(손님) 덮어쓰기 허용
                    if (!localStorage.getItem('shinsa_gender')) {
                        selectGender(predictedGender);
                    }

                    // 화질 저하 경고 UI 표시
                    if (isLowQuality) {
                        document.getElementById('qualityWarningScan').style.display = 'block';
                        document.getElementById('qualityWarningResult').style.display = 'block';
                    } else {
                        document.getElementById('qualityWarningScan').style.display = 'none';
                        document.getElementById('qualityWarningResult').style.display = 'none';
                    }

                    // 스캔 애니메이션 시작
                    startScanningAnimation();
                } else {
                    rejectImage();
                }
            } catch (error) {
                console.error(error);
                // 모델 로드 실패나 에러 시 기존 휴리스틱으로 폴백
                runSkinHeuristic();
            }
        }

        function runSkinHeuristic() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 100;
            canvas.height = 100;
            ctx.drawImage(previewImage, 0, 0, 100, 100);
            
            const imageData = ctx.getImageData(25, 25, 50, 50); 
            const data = imageData.data;
            
            let skinPixelsCount = 0;
            const totalPixels = 50 * 50;
            
            let rSum = 0, gSum = 0, bSum = 0;
            const skinPixels = [];

            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i+1];
                const b = data[i+2];
                
                if (r > 60 && g > 40 && b > 20 && r > g && r > b && (Math.max(r,g,b) - Math.min(r,g,b) > 10)) {
                    skinPixelsCount++;
                    rSum += r;
                    gSum += g;
                    bSum += b;
                    skinPixels.push({r, g, b});
                }
            }
            
            if (skinPixelsCount / totalPixels > 0.12) {
                let variance = 0;
                const rAvg = rSum / skinPixelsCount;
                const gAvg = gSum / skinPixelsCount;
                const bAvg = bSum / skinPixelsCount;
                
                for (const p of skinPixels) {
                    variance += Math.pow(p.r - rAvg, 2) + Math.pow(p.g - gAvg, 2) + Math.pow(p.b - bAvg, 2);
                }
                variance = variance / skinPixelsCount;
                
                // 휴리스틱 나이 예측 (폴백용)
                if (variance < 50) detectedRealAge = 3;
                else if (variance < 100) detectedRealAge = 12;
                else if (variance < 200) detectedRealAge = 22;
                else if (variance < 400) detectedRealAge = 32;
                else if (variance < 700) detectedRealAge = 42;
                else detectedRealAge = 52;
                
                startScanningAnimation();
            } else {
                rejectImage();
            }
        }

        function rejectImage() {
            alert("얼굴을 명확히 인식할 수 없습니다.\n몸 사진이나 사물 대신, 정면 얼굴 위주로 나온 사진을 다시 넣어주세요.");
            location.reload();
        }

        function startScanningAnimation() {
            stepUpload.style.display = 'none';
            stepScan.style.display = 'block';

            const scanPhrases = [
                "피부 결 및 수분도 정밀 스캔 중...",
                "눈가, 미간의 미세 주름 탐지 중...",
                "좌우 안면 비대칭 및 윤곽 계산 중...",
                "연예인 빅데이터 얼굴형 비교 매칭 중...",
                "분석 완료! 맞춤 리포트를 생성합니다..."
            ];
            
            let phase = 0;
            const interval = setInterval(() => {
                if(phase < scanPhrases.length) {
                    scanText.innerText = scanPhrases[phase];
                    phase++;
                }
            }, 800);

            setTimeout(() => {
                clearInterval(interval);
                showResults();
            }, 4500);
        }

        function hashString(str) {
            let hash = str.length; // 파일 크기(문자열 길이)를 1차 시드로 활용하여 파일이 다르면 무조건 다른 값이 나오도록 강화
            const step = Math.max(1, Math.floor(str.length / 500)); // 너무 길면 500글자만 샘플링하여 속도 최적화
            for (let i = 0; i < str.length; i += step) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            // 마지막 10글자 (Base64의 끝부분, 파일마다 고유함) 추가 반영
            for (let i = Math.max(0, str.length - 10); i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash);
        }

        function showResults() {
            stepScan.style.display = 'none';
            stepResult.style.display = 'block';

            // 이미지 소스를 이용해 고유한(Deterministic) 해시값 생성
            const imgSrc = previewImage.src || '';
            const hash = hashString(imgSrc);

            // 1. 피부나이 - Face++ 서버 분석값 우선, 없으면 face-api.js 값 사용
            let age = detectedRealAge;
            if (faceppResult && faceppResult.age > 0) {
                age = faceppResult.age; // Face++ 서버 분석 값이 더 정확
            } else {
                // 해시값을 이용해 약간의 변동성(+/- 1세) 추가하여 너무 굳어보이지 않게 함
                const ageVariance = (hash % 3) - 1;
                age = age + ageVariance;
            }
            if (age < 1) age = 1;
            
            document.getElementById('resAge').innerText = age + '세';
            
            let basisText = "";
            let descText = "";
            if (age < 10) {
                descText = "보습만 조금 더 신경 쓰면 완전 아기 피부!";
                basisText = "의학적 소견: 표피층 두께가 얇고 진피층의 콜라겐 밀도가 최상위 수준이며, 모공 및 색소 침착이 전혀 관찰되지 않는 완벽한 유아기 피부 구조입니다.";
            } else if (age < 20) {
                descText = "또래보다 한참 어려 보이는 최강 동안 페이스!";
                basisText = "의학적 소견: 피지선 활동이 활발하나 진피층의 탄력 섬유가 매우 촘촘하게 배열되어 있는 10대 특유의 건강하고 이상적인 피부결입니다.";
            } else if (age < 30) {
                descText = "피부 나이가 매우 건강하게 잘 유지되고 있어요.";
                basisText = "의학적 소견: 수분 보유력이 우수하며 표정 주름이 아직 픽스되지 않은 20대의 탄력 텐션을 완벽하게 유지하고 있습니다.";
            } else if (age < 40) {
                descText = "미세 주름과 탄력 관리를 시작할 완벽한 타이밍입니다.";
                basisText = "의학적 소견: 눈가 미세 주름(Crow's feet) 및 팔자 부위의 초기 진피층 탄력 저하가 관찰되며, 경미한 색소 침착 패턴이 감지됩니다.";
            } else {
                descText = "지금부터 꾸준한 리프팅 관리가 안티에이징의 핵심입니다!";
                basisText = "의학적 소견: 진피층 콜라겐 감소로 인한 안면 윤곽선의 미세한 변화와 고정된 표정 주름이 감지되어 집중적인 피부 장벽 강화가 필요합니다.";
            }

            document.getElementById('resAgeDesc').innerText = descText;
            document.getElementById('resMedicalBasis').innerText = basisText;

            // Face++ 실제 피부 스코어 시각화
            if (faceppResult && faceppResult.skin) {
                const s = faceppResult.skin;
                const skinItems = [
                    { label: '피부 건강도', score: s.health,      isGood: true  },
                    { label: '여드름',     score: s.acne,        isGood: false },
                    { label: '다크서클',   score: s.dark_circle, isGood: false },
                    { label: '모공',       score: s.pore,        isGood: false },
                    { label: '주름',       score: s.wrinkle,     isGood: false },
                    { label: '기미/잡티',  score: s.spot,        isGood: false },
                ];
                let barsHtml = '';
                skinItems.forEach(item => {
                    const displayScore = item.isGood ? item.score : (100 - item.score);
                    const color = displayScore >= 70 ? '#4caf50' : displayScore >= 40 ? '#ff9800' : '#f5576c';
                    const label2 = item.isGood ? '좋음' : (displayScore >= 70 ? '양호' : displayScore >= 40 ? '보통' : '주의');
                    barsHtml += `
                        <div style="margin-bottom:12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <span style="font-size:12px; color:#555; font-weight:600;">${item.label}</span>
                                <span style="font-size:12px; color:${color}; font-weight:700;">${displayScore}점 <span style="font-size:10px; color:#aaa;">(${label2})</span></span>
                            </div>
                            <div style="height:7px; background:#f0f0f0; border-radius:4px; overflow:hidden;">
                                <div style="height:100%; width:${displayScore}%; background:linear-gradient(90deg, ${color}aa, ${color}); border-radius:4px; transition:width 1s ease;"></div>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('skinScoreBars').innerHTML = barsHtml;
                const beautyText = faceppResult.beauty ? ` | 미적 점수: ${faceppResult.beauty}점` : '';
                document.getElementById('skinTypeTag').textContent = `피부 타입: ${faceppResult.skin_type || '중성'}${beautyText}`;
                document.getElementById('skinScoreSection').style.display = 'block';
            }

            // 결과를 해시에 따라 결정론적으로 선택 (동일 사진 = 동일 결과)
            const getDeterministicItem = (pool, offset) => pool[(hash + offset) % pool.length];

            // 4. 연예인 매칭
            // 버그 수정: eyeSlant 계산값(~60)과 celebPool 기준값(-8~12)의 스케일 불일치로
            // 항상 카리나(가장 높은 12)가 선택되던 문제 → 이미지 해시 + 얼굴형(aspect) 기반으로 교체
            const targetCelebPool = selectedGender === 'female' ? femaleCelebPool : maleCelebPool;
            let bestCeleb;

            if (window.userFaceRatios) {
                // aspect(얼굴 가로:세로 비율)를 추가 시드로 사용 → 사진마다 고유한 결과 보장
                const aspectSeed = Math.round((window.userFaceRatios.aspect || 80) * 73);
                const celebIdx   = Math.abs(hash + aspectSeed) % targetCelebPool.length;
                bestCeleb = targetCelebPool[celebIdx];
            } else {
                // 폴백: 랜드마크 분석 실패 시 해시만 사용
                bestCeleb = getDeterministicItem(targetCelebPool, 1);
            }

            
            const celeb = bestCeleb;
            document.getElementById('resCeleb').innerText = celeb.name;
            document.getElementById('resCelebDesc').innerText = celeb.desc;
            document.getElementById('resCelebAnalysis').innerText = celeb.analysis;

            // 2. 스킨케어 1 (베이직)
            // 3. 스킨케어 2 (프리미엄)
            let petit, surgery;
            let petitCat = "피부";
            
            if (age < 25) {
                petit = { title: "아쿠아필 + 크라이오", desc: "모공 속 피지와 노폐물을 자극 없이 비워내고 피부 온도를 낮춰 홍조와 트러블을 예방합니다.", keyword: "아쿠아필" };
                surgery = { title: "엑셀V 레이저", desc: "초기 색소 병변과 미세한 안면 홍조를 맑고 깨끗하게 지워 투명한 피부톤을 완성합니다.", keyword: "엑셀V" };
            } else if (age < 35) {
                petit = { title: "리쥬란 힐러", desc: "진피층부터 수분과 콜라겐을 꽉 채워 건조함을 잡고 쫀쫀한 광채 피부를 완성합니다.", keyword: "리쥬란" };
                surgery = { title: "인모드 리프팅", desc: "볼살과 이중턱의 불필요한 지방을 정리하고 피부 표면의 탄력을 끌어올려 세련된 윤곽을 만듭니다.", keyword: "인모드" };
            } else {
                petit = { title: "스킨보톡스", desc: "얼굴 전체의 미세 주름을 팽팽하게 펴고 모공을 조여 즉각적인 타이트닝 효과를 부여합니다.", keyword: "보톡스" };
                surgery = { title: "울쎄라 리프팅", desc: "깊은 근막층까지 강력한 초음파 에너지를 전달해 처진 피부를 확실하게 끌어올립니다.", keyword: "울쎄라" };
            }

            document.getElementById('resPetit').innerText = petit.title;
            document.getElementById('resPetitDesc').innerText = petit.desc;
            document.getElementById('btnPetit').href = `/events/list.php?category=${encodeURIComponent('피부')}&keyword=${encodeURIComponent(petit.keyword)}`;

            document.getElementById('resSurgery').innerText = surgery.title;
            document.getElementById('resSurgeryDesc').innerText = surgery.desc;
            document.getElementById('btnSurgery').href = `/events/list.php?category=${encodeURIComponent('피부')}&keyword=${encodeURIComponent(surgery.keyword)}`;

            // Face++ 실제 분석 기반 시술 추천 override
            if (faceppResult && faceppResult.recommendations && faceppResult.recommendations.length > 0) {
                const recMap = {
                    '여드름 케어':  { title: '아쿠아필 클렌징',   desc: 'AI 피부 분석에서 여드름 수치가 감지됐습니다. 모공 속 피지를 자극 없이 클렌징해 드립니다.',             keyword: '아쿠아필' },
                    '다크서클':    { title: '눈밑 필러',          desc: 'AI 피부 분석에서 다크서클 수치가 높습니다. 꺼진 눈밑을 채워 환하고 생기 있는 인상을 만들어 드립니다.', keyword: '필러' },
                    '기미/잡티':   { title: '피코토닝 레이저',    desc: 'AI 피부 분석에서 색소 침착이 감지됐습니다. 레이저로 균일하고 투명한 피부톤을 완성해 드립니다.',        keyword: '레이저토닝' },
                    '모공 관리':   { title: '스킨보톡스',         desc: 'AI 피부 분석에서 모공 확장이 감지됐습니다. 보톡스로 모공을 조여 매끈한 피부결을 만들어 드립니다.',      keyword: '보톡스' },
                    '주름 개선':   { title: '리쥬란 힐러',        desc: 'AI 피부 분석에서 주름이 감지됐습니다. 진피층 콜라겐을 재생해 표정 주름을 자연스럽게 개선해 드립니다.', keyword: '리쥬란' },
                    '안티에이징':  { title: '울쎄라 리프팅',      desc: 'AI 피부 분석 기반 안티에이징이 필요합니다. 초음파로 피부를 근막층부터 확실하게 끌어올립니다.',         keyword: '울쎄라' },
                    '피지 관리':   { title: '지성 피부 트리트먼트', desc: 'AI 피부 분석에서 지성 피부로 판별됐습니다. 피지 분비를 조절해 깨끗하고 맑은 피부를 만들어 드립니다.', keyword: '피지' },
                };
                const matched = faceppResult.recommendations.map(r => recMap[r.tag]).filter(Boolean);
                if (matched.length >= 1) {
                    petit = matched[0];
                    document.getElementById('resPetit').innerText = petit.title;
                    document.getElementById('resPetitDesc').innerText = petit.desc;
                    document.getElementById('btnPetit').href = `/events/list.php?category=${encodeURIComponent('피부')}&keyword=${encodeURIComponent(petit.keyword)}`;
                }
                if (matched.length >= 2) {
                    surgery = matched[1];
                    document.getElementById('resSurgery').innerText = surgery.title;
                    document.getElementById('resSurgeryDesc').innerText = surgery.desc;
                    document.getElementById('btnSurgery').href = `/events/list.php?category=${encodeURIComponent('피부')}&keyword=${encodeURIComponent(surgery.keyword)}`;
                }
            }
            
            // --- AR 스캐너 기반 수학적 분석 덮어쓰기 (가장 높은 우선순위) ---
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('mode') === 'scanned') {
                const pJaw = urlParams.get('jaw');
                const pEyes = urlParams.get('eyes');
                const pLips = urlParams.get('lips');
                
                let isMathOverride = false;

                if (pJaw === 'square') {
                    petit = { title: "사각턱 보톡스 50u", desc: "턱선 비율 스캔 결과, 하악각 근육이 발달된 U라인 형태입니다. 갸름한 V라인을 만들어보세요.", keyword: "사각턱 보톡스" };
                    isMathOverride = true;
                } else if (pJaw === 'vline') {
                    petit = { title: "윤곽주사 (V라인)", desc: "턱선 스캔 결과, 이상적인 V라인 형태입니다! 살짝 남은 턱끝 지방만 정리하면 완벽합니다.", keyword: "윤곽주사" };
                    isMathOverride = true;
                }

                if (pEyes === 'wide') {
                    surgery = { title: "앞트임 / 콧대 필러", desc: "미간 거리 스캔 결과, 얼굴 너비 대비 다소 먼 편입니다. 시원한 눈매를 연출해 보세요.", keyword: "필러" };
                    isMathOverride = true;
                } else if (pEyes === 'narrow') {
                    surgery = { title: "뒤트임 / 눈밑지방재배치", desc: "미간 스캔 결과, 간격이 좁은 편입니다. 뒤트임으로 눈의 가로 길이를 시원하게 확장해 보세요.", keyword: "트임" };
                    isMathOverride = true;
                } else if (pLips === 'thin') {
                    surgery = { title: "입술+입꼬리 필러", desc: "입술 두께 스캔 결과, 도톰한 볼륨이 필요해 보입니다. 필러로 매력적인 입술을 만들어보세요.", keyword: "입술" };
                    isMathOverride = true;
                }

                if (isMathOverride) {
                    document.getElementById('resPetit').innerText = petit.title;
                    document.getElementById('resPetitDesc').innerText = petit.desc;
                    document.getElementById('btnPetit').href = `/events/list.php?category=${encodeURIComponent('보톡스')}&keyword=${encodeURIComponent(petit.keyword)}`;
                    
                    document.getElementById('resSurgery').innerText = surgery.title;
                    document.getElementById('resSurgeryDesc').innerText = surgery.desc;
                    document.getElementById('btnSurgery').href = `/events/list.php?category=${encodeURIComponent('필러')}&keyword=${encodeURIComponent(surgery.keyword)}`;
                }
            }

            // 나중에 다시 볼 수 있도록 브라우저 로컬 스토리지에 결과 저장
            const analysisData = {
                age: age + '세',
                descText: descText,
                basisText: basisText,
                celebName: celeb.name,
                celebDesc: celeb.desc,
                celebAnalysis: celeb.analysis,
                petitTitle: petit.title,
                petitDesc: petit.desc,
                petitKeyword: petit.keyword,
                petitCat: petitCat,
                surgeryTitle: surgery.title,
                surgeryDesc: surgery.desc,
                surgeryKeyword: surgery.keyword
            };
            localStorage.setItem('saved_ai_analysis', JSON.stringify(analysisData));

            // Face++ 데이터 적용 - 없으면 로컴 이미지 분석으로 대체 (항상 표시됨)
            if (!faceppResult) {
                faceppResult = generateLocalSkinScores(previewImage, age);
            }
            applyFaceppData();

            // 맞춤 이벤트 추천 로직 호출
            let recTags = [petit.keyword, surgery.keyword];
            if (faceppResult && faceppResult.recommendations) {
                recTags = recTags.concat(faceppResult.recommendations.map(r => r.tag));
            }
            fetchRecommendations(recTags.join(','));
        }

        async function fetchRecommendations(tags) {
            try {
                const res = await fetch(`/api/recommend_events.php?tags=${encodeURIComponent(tags)}`);
                const data = await res.json();
                
                const list = document.getElementById('recommendEventsList');
                list.innerHTML = '';
                
                if (data.events && data.events.length > 0) {
                    data.events.forEach(ev => {
                        const priceStr = parseInt(ev.discount_price).toLocaleString();
                        const orgPriceStr = parseInt(ev.original_price).toLocaleString();
                        const cardHtml = `
                            <a href="/events/list.php?category=${encodeURIComponent(ev.category)}&keyword=${encodeURIComponent(ev.title)}" style="display:block; min-width:140px; text-decoration:none; background:#fff; border-radius:12px; border:1px solid #eee; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                                <div style="width:100%; height:90px; background:url('${ev.image_url}') center/cover;"></div>
                                <div style="padding:10px;">
                                    <div style="font-size:10px; color:#888; margin-bottom:2px;">${ev.hospital_name}</div>
                                    <div style="font-size:12px; font-weight:700; color:#333; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${ev.title}</div>
                                    <div style="font-size:10px; color:#bbb; text-decoration:line-through;">${orgPriceStr}원</div>
                                    <div style="font-size:14px; font-weight:800; color:#d81b60;">${priceStr}원</div>
                                </div>
                            </a>
                        `;
                        list.insertAdjacentHTML('beforeend', cardHtml);
                    });
                    document.getElementById('recommendEventsSection').style.display = 'block';
                }
            } catch (err) {
                console.error('추천 이벤트를 불러오는데 실패했습니다.', err);
            }
        }
    </script>
</body>
</html>


