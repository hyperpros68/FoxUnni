<?php
$title = "실시간 AI 안면 스캐너";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    
    <!-- MediaPipe 라이브러리 로드 -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/control_utils/control_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>

    <style>
        body { margin: 0; padding: 0; background-color: #000; overflow: hidden; font-family: 'Inter', sans-serif; }
        
        .header-sub { 
            position: absolute; top: 0; width: 100%; z-index: 100;
            display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; box-sizing: border-box;
            background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, transparent 100%);
        }
        .back-btn { font-size: 24px; cursor: pointer; text-decoration: none; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
        .page-title { font-size: 16px; font-weight: 700; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
        .placeholder-btn { width: 24px; } /* header 정렬용 */

        .container { position: relative; width: 100vw; height: 100vh; display: flex; justify-content: center; align-items: center; }
        
        /* 비디오 (화면 꽉 차게) */
        .input_video {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scaleX(-1); /* 거울모드 */
            width: 100%; height: 100%; object-fit: cover; z-index: 1;
        }
        
        /* 캔버스 (비디오 위에 덧그림) */
        .output_canvas {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scaleX(-1); /* 거울모드 동기화 */
            width: 100%; height: 100%; object-fit: cover; z-index: 2; pointer-events: none;
        }

        /* 스캐너 가이드라인 UI */
        .guide-box {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 280px; height: 360px;
            border: 2px dashed rgba(255,255,255,0.4); border-radius: 50% 50% 40% 40% / 40% 40% 60% 60%; /* 얼굴모양 */
            z-index: 3; pointer-events: none;
            box-shadow: 0 0 50px rgba(245,87,108,0.2) inset;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .guide-box.scanning {
            border: 2px solid #00ffcc;
            box-shadow: 0 0 30px rgba(0,255,204,0.4) inset, 0 0 30px rgba(0,255,204,0.2);
            animation: pulse 1s infinite alternate;
        }
        @keyframes pulse { 0% { transform: translate(-50%, -50%) scale(1); } 100% { transform: translate(-50%, -50%) scale(1.02); } }

        /* 하단 안내 UI */
        .bottom-ui {
            position: absolute; bottom: 40px; width: 100%; z-index: 10;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .status-msg {
            background: rgba(0,0,0,0.6); color: #fff; padding: 10px 20px; border-radius: 20px;
            font-size: 14px; font-weight: 700; margin-bottom: 20px; backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        /* 진행바 */
        .progress-container { width: 80%; max-width: 320px; background: rgba(255,255,255,0.2); border-radius: 10px; height: 8px; overflow: hidden; display: none; }
        .progress-bar { width: 0%; height: 100%; background: linear-gradient(90deg, #f093fb, #f5576c); transition: width 0.1s linear; }

        /* 로딩 스피너 */
        .loading { display: flex; justify-content: center; align-items: center; height: 100vh; color: white; position: absolute; z-index: 99; background: #000; width: 100%; font-weight: bold; }
        
        .result-btn {
            display: none; background: linear-gradient(90deg, #f093fb, #f5576c); color: #fff; text-decoration: none;
            padding: 15px 40px; border-radius: 30px; font-weight: 800; font-size: 16px;
            box-shadow: 0 4px 15px rgba(245,87,108,0.4); cursor: pointer; border: none;
            animation: pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes pop { 0% { transform: scale(0.5); } 100% { transform: scale(1); } }
    </style>
</head>
<body>

    <div id="loading" class="loading">AI 스캐너 엔진을 초기화 중입니다...</div>

    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title">실시간 안면 스캔</div>
        <div class="placeholder-btn"></div>
    </header>

    <div class="container">
        <!-- 카메라 영상 -->
        <video class="input_video" autoplay playsinline></video>
        <!-- AR 메쉬 그리는 캔버스 -->
        <canvas class="output_canvas"></canvas>
        
        <!-- 가이드 영역 -->
        <div class="guide-box" id="guideBox"></div>

        <!-- 하단 UI -->
        <div class="bottom-ui">
            <div class="status-msg" id="statusMsg">점선 안에 얼굴을 맞춰주세요.</div>
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            <button class="result-btn" id="resultBtn" onclick="location.href='ai_face_analysis.php?mode=scanned'">스캔 완료! 분석 결과 보기</button>
        </div>
    </div>

    <script>
        const videoElement = document.getElementsByClassName('input_video')[0];
        const canvasElement = document.getElementsByClassName('output_canvas')[0];
        const canvasCtx = canvasElement.getContext('2d');
        
        const loading = document.getElementById('loading');
        const guideBox = document.getElementById('guideBox');
        const statusMsg = document.getElementById('statusMsg');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const resultBtn = document.getElementById('resultBtn');

        let isScanning = false;
        let scanProgress = 0;
        let scanComplete = false;
        
        // 분석 결과를 담을 변수
        let faceMetrics = { jaw: 'vline', eyes: 'normal', lips: 'normal' };

        // 두 점 사이의 거리 계산 함수
        function getDistance(pt1, pt2) {
            return Math.sqrt(Math.pow(pt1.x - pt2.x, 2) + Math.pow(pt1.y - pt2.y, 2));
        }

        function onResults(results) {
            // 엔진 로딩 텍스트 숨김
            if (loading.style.display !== 'none') {
                loading.style.display = 'none';
                resizeCanvas();
            }

            canvasCtx.save();
            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
            
            // 만약 얼굴을 발견했다면
            if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
                // 한 명의 얼굴만 분석 (0번째)
                const landmarks = results.multiFaceLandmarks[0];

                // AR 드로잉 (메쉬 연결선)
                // 기본 구조 (초록색 톤)
                drawConnectors(canvasCtx, landmarks, FACEMESH_TESSELATION, {color: 'rgba(0, 255, 204, 0.15)', lineWidth: 1});
                // 눈 윤곽 (핑크톤)
                drawConnectors(canvasCtx, landmarks, FACEMESH_RIGHT_EYE, {color: '#f5576c', lineWidth: 2});
                drawConnectors(canvasCtx, landmarks, FACEMESH_LEFT_EYE, {color: '#f5576c', lineWidth: 2});
                drawConnectors(canvasCtx, landmarks, FACEMESH_RIGHT_EYEBROW, {color: '#f5576c', lineWidth: 2});
                drawConnectors(canvasCtx, landmarks, FACEMESH_LEFT_EYEBROW, {color: '#f5576c', lineWidth: 2});
                // 얼굴 윤곽선 (사각턱, 브이라인 체크용)
                drawConnectors(canvasCtx, landmarks, FACEMESH_FACE_OVAL, {color: '#e0e0e0', lineWidth: 2});
                // 입술
                drawConnectors(canvasCtx, landmarks, FACEMESH_LIPS, {color: '#ff9a9e', lineWidth: 2});

                // 얼굴이 인식되었고, 스캔이 완료되지 않았다면 스캔 게이지 채우기 시작
                if (!scanComplete) {
                    if (!isScanning) {
                        isScanning = true;
                        guideBox.classList.add('scanning');
                        progressContainer.style.display = 'block';
                        statusMsg.innerText = "얼굴 윤곽 및 비례 분석 중...";
                    }
                    
                    scanProgress += 1.5; // 진행 속도
                    progressBar.style.width = scanProgress + '%';

                    if (scanProgress > 30 && scanProgress <= 60) {
                        statusMsg.innerText = "피부 굴곡 및 비대칭 확인 중...";
                    } else if (scanProgress > 60 && scanProgress < 100) {
                        statusMsg.innerText = "최적의 뷰티 플랜 계산 중...";
                    } else if (scanProgress >= 100) {
                        // 스캔 완료
                        scanComplete = true;
                        isScanning = false;
                        
                        // --- 수학적 분석 로직 실행 ---
                        // 1. 얼굴 너비 (좌우 끝점)
                        const faceWidth = getDistance(landmarks[234], landmarks[454]);
                        // 2. 미간 거리 (왼쪽 눈 안쪽 133, 오른쪽 눈 안쪽 362)
                        const eyeDistance = getDistance(landmarks[133], landmarks[362]);
                        // 3. 턱선 비율 (턱끝 152와 하악각 부근 132/361 거리합 대비 얼굴너비)
                        const jawDistance = getDistance(landmarks[132], landmarks[152]) + getDistance(landmarks[361], landmarks[152]);
                        // 4. 입술 두께 (위 13, 아래 14)
                        const lipThickness = getDistance(landmarks[13], landmarks[14]);

                        // 분석 판별
                        if (eyeDistance / faceWidth > 0.28) faceMetrics.eyes = 'wide'; // 미간 멂
                        else if (eyeDistance / faceWidth < 0.22) faceMetrics.eyes = 'narrow'; // 미간 좁음
                        
                        if (jawDistance / faceWidth < 0.9) faceMetrics.jaw = 'square'; // 사각턱/U라인
                        else faceMetrics.jaw = 'vline'; // V라인

                        if (lipThickness / faceWidth < 0.05) faceMetrics.lips = 'thin'; // 얇은 입술
                        else faceMetrics.lips = 'thick'; // 도톰한 입술
                        
                        guideBox.classList.remove('scanning');
                        guideBox.style.borderColor = "#f5576c"; // 완료 색상
                        statusMsg.style.display = 'none';
                        progressContainer.style.display = 'none';
                        
                        // 결과보기 버튼 URL 갱신
                        resultBtn.onclick = function() {
                            location.href = `ai_face_analysis.php?mode=scanned&jaw=${faceMetrics.jaw}&eyes=${faceMetrics.eyes}&lips=${faceMetrics.lips}`;
                        };
                        resultBtn.style.display = 'block';
                    }
                }
            } else {
                // 얼굴을 못 찾은 경우
                if (!scanComplete) {
                    isScanning = false;
                    guideBox.classList.remove('scanning');
                    statusMsg.innerText = "점선 안에 얼굴을 맞춰주세요.";
                }
            }
            canvasCtx.restore();
        }

        const faceMesh = new FaceMesh({locateFile: (file) => {
            return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
        }});
        
        faceMesh.setOptions({
            maxNumFaces: 1,
            refineLandmarks: true, // 홍채 등 더 정밀한 위치 파악
            minDetectionConfidence: 0.5,
            minTrackingConfidence: 0.5
        });
        faceMesh.onResults(onResults);

        // 카메라 설정
        const camera = new Camera(videoElement, {
            onFrame: async () => {
                await faceMesh.send({image: videoElement});
            },
            width: 720,
            height: 1280,
            facingMode: 'user' // 전면 카메라 우선
        });
        
        camera.start();

        // 창 크기에 맞게 캔버스 해상도 조절 (화면 꽉 차게)
        function resizeCanvas() {
            canvasElement.width = videoElement.videoWidth || window.innerWidth;
            canvasElement.height = videoElement.videoHeight || window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
    </script>
</body>
</html>
