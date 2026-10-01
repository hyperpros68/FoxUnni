<?php
require_once 'config/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $author_name = $_SESSION['user_name'] ?? '익명' . rand(100, 999);
    $category = $_POST['category'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $is_receipt_verified = isset($_POST['is_receipt_verified']) && $_POST['is_receipt_verified'] === '1' ? 1 : 0;
    $timeline_period = $_POST['timeline_period'] ?? null;
    
    if (empty($category) || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'error' => '필수 항목을 모두 입력해주세요.']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO community_posts (author_name, category, title, content, is_receipt_verified, timeline_period) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$author_name, $category, $title, $content, $is_receipt_verified, $timeline_period])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'DB 저장 실패']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>글쓰기 - 뷰티 수다방</title>
    <style>
        :root {
            --primary-color: #f5576c;
            --bg-color: #f4f5f7;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Pretendard', -apple-system, sans-serif; }
        body { background-color: #fff; color: #333; max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        
        /* Header */
        .header-top { display: flex; align-items: center; justify-content: space-between; padding: 0 15px; height: 56px; border-bottom: 1px solid #eaeaea; }
        .header-top a { text-decoration: none; color: #333; font-size: 20px; }
        .header-title { font-size: 17px; font-weight: 700; }
        .submit-btn { background: none; border: none; color: var(--primary-color); font-size: 15px; font-weight: 700; cursor: pointer; }
        .submit-btn:disabled { color: #ccc; cursor: not-allowed; }
        
        /* Form */
        .form-wrap { padding: 20px; }
        select, input, textarea { width: 100%; border: none; outline: none; font-size: 15px; padding: 15px 0; }
        select { border-bottom: 1px solid #eaeaea; color: #333; appearance: none; background: url('data:image/svg+xml;utf8,<svg fill="%23aaa" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right center; background-size: 20px; padding-right: 30px; border-radius: 0; }
        input { border-bottom: 1px solid #eaeaea; font-weight: 700; }
        input::placeholder { font-weight: 400; color: #aaa; }
        textarea { height: 300px; resize: none; margin-top: 10px; line-height: 1.5; }
        textarea::placeholder { color: #aaa; }
        
        .anonymous-notice { background: #fdf5f6; padding: 12px; border-radius: 8px; font-size: 12px; color: #d81b60; margin-top: 20px; line-height: 1.4; border: 1px solid #ffe4e1; }
        
        /* New Features UI */
        .option-group { margin-top: 15px; display: none; }
        .option-group.active { display: block; }
        .option-label { font-size: 13px; font-weight: 700; color: #555; margin-bottom: 8px; display: block; }
        
        .receipt-upload-btn {
            display: flex; align-items: center; justify-content: center; width: 100%; padding: 12px; 
            border: 1px dashed var(--primary-color); border-radius: 8px; background: #fff0f5; 
            color: var(--primary-color); font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s;
        }
        .receipt-upload-btn.verified {
            background: var(--primary-color); color: #fff; border-style: solid;
        }
        
        .timeline-select {
            width: 100%; padding: 12px; border: 1px solid #eaeaea; border-radius: 8px; font-size: 14px;
            background: url('data:image/svg+xml;utf8,<svg fill="%23aaa" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center;
            background-size: 20px; appearance: none; color: #333; outline: none;
        }
    </style>
</head>
<body>
    <header class="header-top">
        <a href="/community.php">✕</a>
        <div class="header-title">글쓰기</div>
        <button class="submit-btn" id="submitBtn" disabled>등록</button>
    </header>

    <div class="form-wrap">
        <select id="category">
            <option value="">게시판 선택</option>
            <option value="성형후기">성형후기</option>
            <option value="피부고민">피부고민</option>
            <option value="병원정보">병원정보</option>
            <option value="자유수다">자유수다</option>
        </select>
        
        <!-- 경과 시점 (성형후기 선택 시 활성화) -->
        <div class="option-group" id="timelineGroup">
            <label class="option-label">수술 경과 시점</label>
            <select id="timelinePeriod" class="timeline-select">
                <option value="">선택 안함</option>
                <option value="당일">당일</option>
                <option value="1주일 차">1주일 차</option>
                <option value="1개월 차">1개월 차</option>
                <option value="3개월 차">3개월 차</option>
                <option value="6개월 차 이상">6개월 차 이상</option>
            </select>
        </div>
        
        <!-- 영수증 인증 (성형후기 선택 시 활성화) -->
        <div class="option-group" id="receiptGroup">
            <label class="option-label">영수증 인증</label>
            <div id="receiptBtn" class="receipt-upload-btn">📸 영수증 사진 업로드하여 인증하기</div>
            <input type="file" id="receiptFile" accept="image/*" style="display:none;">
            <input type="hidden" id="isReceiptVerified" value="0">
        </div>
        
        <input type="text" id="title" placeholder="제목을 입력하세요">
        <textarea id="content" placeholder="여우언니는 누구나 기분 좋게 참여할 수 있는 시크릿 라운지를 만듭니다. 매너 있는 글로 소통해주세요!"></textarea>
        
        <div class="anonymous-notice">
            🔒 <b>안심하세요!</b> 여우언니 뷰티 수다방은 철저한 익명 시스템으로 운영되며, 게시글 작성자의 정보는 절대 노출되지 않습니다. (의료법 위반 소지가 있는 특정 병원 비방글은 통보 없이 삭제될 수 있습니다.)
        </div>
    </div>

    <script>
        const cat = document.getElementById('category');
        const tit = document.getElementById('title');
        const con = document.getElementById('content');
        const btn = document.getElementById('submitBtn');
        
        const timelineGroup = document.getElementById('timelineGroup');
        const receiptGroup = document.getElementById('receiptGroup');
        const timelinePeriod = document.getElementById('timelinePeriod');
        const isReceiptVerified = document.getElementById('isReceiptVerified');
        const receiptBtn = document.getElementById('receiptBtn');
        const receiptFile = document.getElementById('receiptFile');
        
        function checkVal() {
            if (cat.value && tit.value.trim() && con.value.trim().length > 5) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
            
            // 성형후기 일 때만 경과시점/영수증 활성화
            if (cat.value === '성형후기') {
                timelineGroup.classList.add('active');
                receiptGroup.classList.add('active');
            } else {
                timelineGroup.classList.remove('active');
                receiptGroup.classList.remove('active');
                timelinePeriod.value = '';
            }
        }
        
        cat.addEventListener('change', checkVal);
        tit.addEventListener('input', checkVal);
        con.addEventListener('input', checkVal);
        
        // 영수증 인증 Mocking
        receiptBtn.addEventListener('click', () => {
            if (isReceiptVerified.value === '1') {
                if (confirm('인증을 취소하시겠습니까?')) {
                    isReceiptVerified.value = '0';
                    receiptBtn.classList.remove('verified');
                    receiptBtn.innerText = '📸 영수증 사진 업로드하여 인증하기';
                }
                return;
            }
            // 원래는 receiptFile.click() 호출 후 파일 선택 시 API 요청
            // 여기서는 UI Mocking으로 바로 처리
            alert('사진이 업로드되었습니다. AI가 영수증 정보를 판독합니다...');
            setTimeout(() => {
                isReceiptVerified.value = '1';
                receiptBtn.classList.add('verified');
                receiptBtn.innerText = '✅ 영수증 인증 완료';
            }, 800);
        });
        
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            btn.textContent = '등록 중...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('category', cat.value);
            formData.append('title', tit.value);
            formData.append('content', con.value);
            formData.append('is_receipt_verified', isReceiptVerified.value);
            if (cat.value === '성형후기') {
                formData.append('timeline_period', timelinePeriod.value);
            }
            
            fetch('/community_write.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.href = '/community.php';
                } else {
                    alert('오류: ' + data.error);
                    btn.textContent = '등록';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alert('네트워크 오류가 발생했습니다.');
                btn.textContent = '등록';
                btn.disabled = false;
            });
        });
    </script>
</body>
</html>


