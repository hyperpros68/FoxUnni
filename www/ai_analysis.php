<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI 얼굴 분석 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #fff; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border-color); }
        .back-btn { font-size: 24px; cursor: pointer; text-decoration: none; color: #333; margin-right: 15px; font-weight: 300; }
        .page-title { font-size: 18px; font-weight: 700; }

        .upload-section {
            padding: 40px 20px;
            text-align: center;
        }
        .upload-title {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        .upload-desc {
            font-size: 15px;
            color: var(--text-light);
            margin-bottom: 40px;
            line-height: 1.5;
        }
        
        .upload-btn-wrap {
            display: inline-block;
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            width: 100%;
            max-width: 300px;
        }
        .upload-btn {
            display: block;
            background: linear-gradient(135deg, var(--primary-color) 0%, #ff6b9d 100%);
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: 700;
            border: none;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 42, 117, 0.3);
        }
        .upload-input {
            position: absolute;
            top: 0; right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: pointer;
            display: block;
        }

        /* 스캐닝 화면 (처음엔 숨김) */
        #scanning-section {
            display: none;
            padding: 60px 20px;
        }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/index.php" class="back-btn">←</a>
        <div class="page-title">AI 얼굴 분석</div>
    </header>

    <!-- 업로드 전 화면 -->
    <main id="upload-section" class="upload-section">
        <div style="font-size: 60px; margin-bottom: 20px;">🤳</div>
        <div class="upload-title">내 얼굴에 맞는<br>최적의 시술은?</div>
        <div class="upload-desc">
            카메라로 정면 얼굴을 찍거나<br>
            앨범에서 정면 사진을 선택해 주세요.<br>
            AI가 윤곽과 피부 상태를 분석합니다.
        </div>
        
        <div class="upload-btn-wrap">
            <button class="upload-btn">📸 사진 촬영 / 선택</button>
            <input type="file" accept="image/*" class="upload-input" id="photoInput">
        </div>
        <div style="margin-top: 15px; font-size: 12px; color: #aaa;">
            * 업로드된 사진은 분석 후 즉시 삭제되며 저장되지 않습니다.
        </div>
    </main>

    <!-- 스캐닝 중 화면 -->
    <main id="scanning-section">
        <div class="scanner-container">
            <!-- 미리보기 이미지 표시 -->
            <img id="previewImg" src="data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22250%22%20height%3D%22300%22%20viewBox%3D%220%200%20250%20300%22%3E%3Crect%20fill%3D%22%23ddd%22%20width%3D%22250%22%20height%3D%22300%22%2F%3E%3Ctext%20fill%3D%22%23999%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%3EFace%20Image%3C%2Ftext%3E%3C%2Fsvg%3E" class="scanner-img">
            <!-- 위아래로 움직이는 스캔 라인 -->
            <div class="scan-line"></div>
        </div>
        
        <div class="ai-loading-text">AI가 얼굴을 분석 중입니다...</div>
        <div class="ai-loading-subtext">윤곽, 비율, 피부 탄력을 측정하고 있습니다.</div>
    </main>

    <script>
        const photoInput = document.getElementById('photoInput');
        const uploadSection = document.getElementById('upload-section');
        const scanningSection = document.getElementById('scanning-section');
        const previewImg = document.getElementById('previewImg');
        const loadingSubtext = document.querySelector('.ai-loading-subtext');

        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // 이미지 미리보기 설정
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                }
                reader.readAsDataURL(file);

                // 화면 전환 (업로드 -> 스캐닝)
                uploadSection.style.display = 'none';
                scanningSection.style.display = 'block';

                // 스캐닝 애니메이션 및 텍스트 변화 시뮬레이션
                setTimeout(() => { loadingSubtext.innerText = "안면 비대칭 분석 중..."; }, 1000);
                setTimeout(() => { loadingSubtext.innerText = "피부 처짐 및 주름 분석 중..."; }, 2000);
                setTimeout(() => { loadingSubtext.innerText = "최적의 시술 매칭 중..."; }, 3000);
                
                // 4초 후 결과 페이지로 이동 (분석 완료)
                setTimeout(() => {
                    window.location.href = '/ai_result.php';
                }, 4000);
            }
        });
    </script>
</body>
</html>


