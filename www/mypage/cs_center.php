<?php
// www/mypage/cs_center.php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>고객센터 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f7f7f7; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .page-header { display: flex; align-items: center; padding: 15px 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #eaeaea; background: #fff; position: sticky; top: 0; z-index: 10; }
        .back-btn { margin-right: 15px; font-size: 24px; text-decoration: none; color: #333; }
        
        .cs-top { background: #fff; padding: 30px 20px; text-align: center; border-bottom: 1px solid #eee; margin-bottom: 10px; }
        .cs-title { font-size: 22px; font-weight: 800; color: #333; margin-bottom: 10px; }
        .cs-desc { font-size: 14px; color: #666; line-height: 1.5; }
        
        .action-buttons { display: flex; gap: 10px; padding: 0 20px; margin-top: 20px; }
        .action-btn { flex: 1; padding: 15px 0; border-radius: 12px; font-size: 15px; font-weight: 700; text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
        .btn-call { background: #fff0f5; color: var(--primary-color); border: 1px solid #ffb6c1; }
        .btn-inquiry { background: var(--primary-color); color: #fff; }
        .btn-icon { font-size: 24px; }
        
        .faq-section { background: #fff; padding: 20px; }
        .section-title { font-size: 18px; font-weight: 800; color: #333; margin-bottom: 20px; }
        
        .faq-item { border-bottom: 1px solid #eee; }
        .faq-q { padding: 18px 0; font-size: 15px; font-weight: 600; color: #333; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .faq-a { padding: 0 0 18px 0; font-size: 14px; color: #666; line-height: 1.6; display: none; background: #fafafa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .faq-q.active { color: var(--primary-color); }
        .arrow { transition: transform 0.3s; color: #ccc; }
        .faq-q.active .arrow { transform: rotate(180deg); }
        
        .q-mark { color: var(--primary-color); margin-right: 8px; font-weight: 900; }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="/mypage.php" class="back-btn">&larr;</a>
        고객센터
    </div>
    
    <div class="cs-top">
        <div class="cs-title">무엇을 도와드릴까요?</div>
        <div class="cs-desc">여우언니 이용 중 불편하신 점이나<br>궁금한 점을 해결해 드립니다.</div>
        
        <div class="action-buttons">
            <a href="tel:1588-0000" class="action-btn btn-call">
                <div class="btn-icon">📞</div>
                전화 상담
            </a>
            <a href="#" onclick="alert('1:1 문의 폼이 곧 오픈됩니다!'); return false;" class="action-btn btn-inquiry">
                <div class="btn-icon">💬</div>
                1:1 문의하기
            </a>
        </div>
        <div style="font-size: 12px; color: #999; margin-top: 15px;">운영시간: 평일 10:00 ~ 18:00 (점심시간 13:00~14:00)</div>
    </div>

    <div class="faq-section">
        <div class="section-title">자주 묻는 질문 (FAQ)</div>
        
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">
                <span><span class="q-mark">Q.</span>예약금 결제 후 취소/환불은 어떻게 하나요?</span>
                <span class="arrow">▼</span>
            </div>
            <div class="faq-a">
                결제하신 예약금의 취소 및 환불은 [마이 여우 > 내 예약내역]에서 해당 예약을 선택하신 후 '예약 취소' 버튼을 누르시면 됩니다. 취소 수수료 정책은 병원마다 다를 수 있으니 유의해주세요.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">
                <span><span class="q-mark">Q.</span>포인트는 어떻게 모으고 사용하나요?</span>
                <span class="arrow">▼</span>
            </div>
            <div class="faq-a">
                포인트는 신규 가입, 친구 초대, 시술 후 영수증 리뷰 작성 등 다양한 활동을 통해 적립할 수 있습니다. 모인 포인트는 앱 내에서 시술 예약금을 결제할 때 현금처럼 1P=1원으로 사용 가능합니다.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">
                <span><span class="q-mark">Q.</span>리뷰 작성 시 사진이 안 올라가요.</span>
                <span class="arrow">▼</span>
            </div>
            <div class="faq-a">
                업로드 가능한 사진 용량은 최대 10MB입니다. 용량이 너무 크지 않은지 확인해 주시고, 일시적인 네트워크 오류일 수 있으니 잠시 후 다시 시도해 주시기 바랍니다. 계속 문제가 발생하면 1:1 문의를 남겨주세요.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">
                <span><span class="q-mark">Q.</span>병원에서 다른 시술을 강요해요.</span>
                <span class="arrow">▼</span>
            </div>
            <div class="faq-a">
                여우언니는 투명하고 건전한 의료 환경을 지향합니다. 과도한 추가 결제를 강요받거나 불친절한 응대를 겪으셨다면 고객센터 1:1 문의로 해당 병원을 신고해 주세요. 내부 검토 후 페널티가 부과될 수 있습니다.
            </div>
        </div>
    </div>

    <script>
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const isActive = element.classList.contains('active');
            
            // 모든 FAQ 닫기
            document.querySelectorAll('.faq-q').forEach(q => q.classList.remove('active'));
            document.querySelectorAll('.faq-a').forEach(a => a.style.display = 'none');
            
            // 클릭한 것만 열기 (이미 열려있던게 아니면)
            if (!isActive) {
                element.classList.add('active');
                answer.style.display = 'block';
            }
        }
    </script>
</body>
</html>


