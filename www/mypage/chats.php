<?php
session_start();
$title = "채팅상담";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; flex: 1; }
        
        .chat-list { list-style: none; padding: 0; margin: 0; }
        .chat-item { display: flex; align-items: center; padding: 18px 20px; border-bottom: 1px solid #f2f2f2; cursor: pointer; text-decoration: none; color: inherit; transition: background 0.2s; }
        .chat-item:active { background-color: #f8f9fa; }
        
        .hospital-thumb { width: 50px; height: 50px; border-radius: 50%; background-color: #f0f0f0; background-image: url('/static/images/skin.jpg'); background-size: cover; background-position: center; margin-right: 15px; flex-shrink: 0; border: 1px solid #eaeaea; }
        .chat-info { flex: 1; overflow: hidden; }
        
        .chat-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 5px; }
        .hospital-name { font-size: 15px; font-weight: 700; color: #222; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .last-time { font-size: 12px; color: #999; white-space: nowrap; margin-left: 10px; }
        
        .chat-preview { display: flex; justify-content: space-between; align-items: center; }
        .last-msg { font-size: 14px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 15px; }
        .unread-badge { background-color: var(--primary-color); color: white; font-size: 11px; font-weight: 700; padding: 3px 6px; border-radius: 10px; min-width: 18px; text-align: center; }
        
        .empty-state { display: none; flex-direction: column; align-items: center; justify-content: center; height: 60vh; text-align: center; }
        .empty-icon { font-size: 50px; color: #ddd; margin-bottom: 20px; }
        .empty-text { font-size: 15px; color: #999; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/mypage.php" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <main>
        <div class="empty-state" id="emptyState">
            <div class="empty-icon">💬</div>
            <div class="empty-text">진행 중인 채팅상담이 없습니다.</div>
        </div>
        
        <ul class="chat-list" id="chatList">
            <!-- 자바스크립트로 렌더링 됨 -->
        </ul>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 초기 더미 데이터가 없으면 로컬스토리지에 세팅
            if (!localStorage.getItem('shinsa_chats')) {
                const initialChats = [
                    {
                        id: 'chat_1',
                        hospital_name: '신사 뷰티업 성형외과',
                        last_msg: '원장님, 내일 오후 3시 예약 확정되었습니다.',
                        last_time: '오후 2:30',
                        unread: 1,
                        image: '/static/images/skin.jpg'
                    },
                    {
                        id: 'chat_2',
                        hospital_name: '강남 에스테틱 의원',
                        last_msg: '사진을 보내주시면 더 정확한 상담이 가능합니다!',
                        last_time: '어제',
                        unread: 0,
                        image: '/static/images/skin.jpg'
                    }
                ];
                localStorage.setItem('shinsa_chats', JSON.stringify(initialChats));
            }

            const chatData = JSON.parse(localStorage.getItem('shinsa_chats')) || [];
            const chatList = document.getElementById('chatList');
            const emptyState = document.getElementById('emptyState');

            if (chatData.length === 0) {
                emptyState.style.display = 'flex';
            } else {
                let html = '';
                chatData.forEach(chat => {
                    html += `
                        <a href="/mypage/chat_detail.php?id=${chat.id}" class="chat-item">
                            <div class="hospital-thumb" style="background-image: url('${chat.image}')"></div>
                            <div class="chat-info">
                                <div class="chat-header">
                                    <div class="hospital-name">${chat.hospital_name}</div>
                                    <div class="last-time">${chat.last_time}</div>
                                </div>
                                <div class="chat-preview">
                                    <div class="last-msg">${chat.last_msg}</div>
                                    ${chat.unread > 0 ? `<div class="unread-badge">${chat.unread}</div>` : ''}
                                </div>
                            </div>
                        </a>
                    `;
                });
                chatList.innerHTML = html;
            }
        });
    </script>
</body>
</html>


