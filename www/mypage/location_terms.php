<?php
session_start();
$title = "위치기반서비스 이용약관";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; }
        
        .policy-container { padding: 25px 20px 50px; font-size: 13px; color: #555; line-height: 1.6; background-color: #fff; }
        .policy-intro { font-weight: 600; color: #333; margin-bottom: 25px; font-size: 14px; }
        .policy-title { font-size: 15px; font-weight: 800; color: #111; margin-top: 35px; margin-bottom: 12px; }
        .policy-title:first-child { margin-top: 0; }
        .policy-text { margin-bottom: 15px; word-break: keep-all; text-align: justify; }
        .policy-text ul { padding-left: 20px; margin-top: 8px; margin-bottom: 15px; }
        .policy-text li { margin-bottom: 6px; }
        .highlight { font-weight: 700; color: var(--primary-color); }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <div class="policy-container">
        <div class="policy-intro">
            주식회사 여우언니(이하 "회사")는 「위치정보의 보호 및 이용 등에 관한 법률」에 따라 이용자의 개인위치정보를 안전하게 관리하고 있습니다.
        </div>

        <div class="policy-title">제 1 조 (목적)</div>
        <div class="policy-text">
            본 약관은 회사가 제공하는 위치기반서비스(이하 "서비스")와 관련하여 회사와 개인위치정보주체(이하 "이용자") 간의 권리, 의무 및 책임사항, 기타 필요한 사항을 규정함을 목적으로 합니다.
        </div>

        <div class="policy-title">제 2 조 (이용약관의 효력 및 변경)</div>
        <div class="policy-text">
            <ul>
                <li>본 약관은 이용자가 본 약관에 동의하고 회사가 정한 소정의 절차에 따라 서비스의 이용자로 등록함으로써 효력이 발생합니다.</li>
                <li>이용자가 온라인에서 본 약관의 "동의하기" 버튼을 클릭하였을 경우, 본 약관의 내용을 모두 읽고 이를 충분히 이해하였으며 그 적용에 동의한 것으로 봅니다.</li>
                <li>회사는 관련 법령을 위배하지 않는 범위 내에서 본 약관을 개정할 수 있으며, 개정 시에는 적용일자 및 개정사유를 명시하여 적용일 7일 전부터 플랫폼 내에 공지합니다.</li>
            </ul>
        </div>

        <div class="policy-title">제 3 조 (서비스의 내용 및 요금)</div>
        <div class="policy-text">
            회사가 제공하는 위치기반서비스의 내용은 다음과 같습니다.
            <ul>
                <li><strong>내 주변 제휴병원 검색:</strong> 이용자의 현재 위치를 기반으로 인근에 있는 피부과, 성형외과 등 제휴 병원의 위치 및 여우언니 단독 특가 정보를 제공합니다.</li>
                <li><strong>맞춤형 광고 제공:</strong> 이용자의 위치정보를 활용하여 해당 지역 내 병원의 프로모션 혜택, 맞춤형 시술 정보를 푸시 알림 등으로 안내합니다.</li>
            </ul>
            회사가 제공하는 위치기반서비스는 원칙적으로 무료입니다. 단, 무선 인터넷(Wi-Fi, 3G, LTE, 5G 등)을 이용한 접속 시 통신사 정책에 따른 데이터 통화료가 발생할 수 있습니다.
        </div>

        <div class="policy-title">제 4 조 (위치정보 수집방법)</div>
        <div class="policy-text">
            회사는 다음과 같은 방식으로 개인위치정보를 수집합니다.
            <ul>
                <li>이용자의 스마트폰 등 단말기에 내장된 GPS, Wi-Fi, 기지국 기반 위치정보 측정 기능을 활용하여 수집합니다.</li>
                <li>이용자가 직접 입력한 주소 및 지역 정보를 기반으로 수집합니다.</li>
            </ul>
        </div>

        <div class="policy-title">제 5 조 (위치정보 이용·제공사실 확인자료의 보유근거 및 보유기간)</div>
        <div class="policy-text">
            회사는 「위치정보의 보호 및 이용 등에 관한 법률」 제16조 제2항에 근거하여 이용자의 위치정보 이용·제공사실 확인자료를 위치정보시스템에 자동으로 기록하며, 해당 자료는 <strong>6개월 이상 보관</strong>합니다.
        </div>

        <div class="policy-title">제 6 조 (개인위치정보의 제3자 제공 및 통보)</div>
        <div class="policy-text">
            <ul>
                <li>회사는 이용자의 사전 동의 없이 개인위치정보를 제3자에게 제공하지 않습니다. 단, 관련 법령에 의거하여 수사기관 등의 적법한 요구가 있는 경우에는 예외로 합니다.</li>
                <li>회사가 이용자가 지정한 제3자에게 개인위치정보를 제공할 경우, 매회 이용자에게 제공받는 자, 제공 일시 및 제공 목적을 즉시 스마트폰 푸시 알림 또는 문자메시지 등으로 통보합니다.</li>
            </ul>
        </div>

        <div class="policy-title">제 7 조 (개인위치정보주체의 권리)</div>
        <div class="policy-text">
            <ul>
                <li>이용자는 회사에 대하여 언제든지 개인위치정보를 이용한 위치기반서비스 제공 및 개인위치정보의 제3자 제공에 대한 동의의 전부 또는 일부를 철회할 수 있습니다. 이 경우 회사는 수집한 개인위치정보 및 위치정보 이용, 제공사실 확인자료를 파기합니다.</li>
                <li>이용자는 언제든지 개인위치정보의 수집, 이용 또는 제공의 일시적인 중지를 요구할 수 있으며, 회사는 이를 거절할 수 없고 이를 위한 기술적 수단을 갖추고 있습니다.</li>
                <li>이용자는 회사를 상대로 본인의 위치정보 이용·제공사실 확인자료 등의 열람 또는 고지를 요구할 수 있으며, 해당 자료에 오류가 있는 경우 정정을 요구할 수 있습니다.</li>
            </ul>
        </div>

        <div class="policy-title">제 8 조 (법정대리인의 권리)</div>
        <div class="policy-text">
            회사는 만 14세 미만 아동의 개인위치정보를 수집·이용·제공하고자 하는 경우, 법정대리인의 동의를 받아야 합니다. 법정대리인은 만 14세 미만 아동의 개인위치정보에 대하여 제7조에 따른 동의 철회, 일시 중지 및 열람·고지 요구의 권리를 행사할 수 있습니다.
        </div>

        <div class="policy-title">제 9 조 (위치정보관리책임자의 정보)</div>
        <div class="policy-text">
            회사는 개인위치정보를 적절히 관리·보호하고 이용자의 불만을 원활히 처리하기 위하여 위치정보관리책임자를 지정하고 있습니다.
            <ul>
                <li>성명: 김신사 (위치정보관리책임자)</li>
                <li>소속: 정보보안본부</li>
                <li>연락처: 1588-0000</li>
                <li>이메일: lbs_privacy@shinsa.co.kr</li>
            </ul>
        </div>

        <div style="margin-top: 35px; font-size: 12px; color: #888;">
            - 공고일자 : 2026년 06월 16일<br>
            - 시행일자 : 2026년 06월 16일
        </div>
    </div>
</body>
</html>


