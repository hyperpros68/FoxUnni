<?php
session_start();
$user_id = $_GET['user_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>포인트 내역 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; font-family: inherit; margin: 0; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background: #fff; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; text-decoration: none; color: #333; margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; }

        /* 총 포인트 요약 카드 */
        .points-summary { background: linear-gradient(135deg, #f5576c, #f093fb); color: #fff; padding: 25px 20px; text-align: center; }
        .points-summary .label { font-size: 13px; opacity: 0.85; margin-bottom: 8px; }
        .points-summary .total { font-size: 36px; font-weight: 800; letter-spacing: -1px; }
        .points-summary .unit { font-size: 18px; font-weight: 400; margin-left: 4px; opacity: 0.9; }

        /* 내역 리스트 */
        .history-list { padding: 0 20px 80px 20px; }
        .history-date-label { font-size: 12px; color: #aaa; font-weight: 600; padding: 20px 0 8px; }
        .history-item { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .history-item .info .title { font-size: 14px; font-weight: 700; color: #333; margin-bottom: 4px; }
        .history-item .info .date { font-size: 12px; color: #aaa; }
        .history-item .amount { font-size: 18px; font-weight: 800; color: var(--primary-color); }
        .history-item .amount.minus { color: #aaa; }

        /* 빈 상태 */
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 50vh; }
        .empty-icon { font-size: 50px; margin-bottom: 15px; color: #ddd; }
        .empty-text { font-size: 15px; color: #aaa; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title">포인트 내역</div>
    </header>

    <!-- 총 포인트 요약 -->
    <div class="points-summary">
        <div class="label">현재 보유 포인트</div>
        <div class="total"><span id="totalPointsView">--</span><span class="unit">P</span></div>
    </div>

    <!-- 내역 리스트 -->
    <div class="history-list" id="historyList">
        <div class="empty-state">
            <div class="empty-icon">??</div>
            <div class="empty-text">적립/사용된 포인트 내역이 없습니다.</div>
        </div>
    </div>

    <script>
        async function loadPointsPage() {
            try {
                const res = await fetch('/api/get_points.php');
                const data = await res.json();
                
                if (data.ok) {
                    document.getElementById('totalPointsView').innerText = data.total.toLocaleString();
                    
                    const listEl = document.getElementById('historyList');
                    if (!data.history || data.history.length === 0) {
                        listEl.innerHTML = 
                            <div class="empty-state">
                                <div class="empty-icon">??</div>
                                <div class="empty-text">적립/사용된 포인트 내역이 없습니다.</div>
                            </div>
                        ;
                        return;
                    }
                    
                    let html = '';
                    let lastDate = '';
                    data.history.forEach(item => {
                        const d = new Date(item.datetime.replace(' ', 'T'));
                        const dateStr = ${d.getFullYear()}..;
                        const timeStr = ${String(d.getHours()).padStart(2,'0')}:;

                        if (dateStr !== lastDate) {
                            html += <div class="history-date-label"></div>;
                            lastDate = dateStr;
                        }

                        const amountClass = item.amount > 0 ? '' : 'minus';
                        const amountPrefix = item.amount > 0 ? '+' : '';
                        html += 
                            <div class="history-item">
                                <div class="info">
                                    <div class="title"></div>
                                    <div class="date"></div>
                                </div>
                                <div class="amount "> P</div>
                            </div>
                        ;
                    });
                    listEl.innerHTML = html;
                } else {
                    alert(data.msg);
                    if(data.msg.includes('로그인')) location.href = '/login.php';
                }
            } catch (e) {
                console.error(e);
                alert('포인트 내역을 불러오는 중 오류가 발생했습니다.');
            }
        }

        window.addEventListener('DOMContentLoaded', loadPointsPage);
    </script>
</body>
</html>


