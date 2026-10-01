<?php
session_start();
$chat_id = $_GET['id'] ?? 'chat_1';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>채팅 상담 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f4f5f7; font-family: 'Inter', sans-serif; margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        
        .header-sub { 
            display: flex; align-items: center; padding: 15px 20px; 
            background-color: var(--white); border-bottom: 1px solid var(--border-color); 
            flex-shrink: 0;
        }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 16px; font-weight: 800; flex: 1; text-align: center; margin-right: 35px;}
        
        .chat-container { flex: 1; overflow-y: auto; padding: 20px 15px; display: flex; flex-direction: column; gap: 15px; }
        
        .date-divider { text-align: center; margin: 10px 0; }
        .date-divider span { background-color: rgba(0,0,0,0.1); color: #fff; font-size: 11px; padding: 4px 12px; border-radius: 20px; }
        
        .msg-row { display: flex; align-items: flex-end; max-width: 85%; }
        .msg-row.hospital { align-self: flex-start; }
        .msg-row.user { align-self: flex-end; flex-direction: row-reverse; }
        
        .hospital-thumb { width: 36px; height: 36px; border-radius: 50%; background-color: #ddd; background-size: cover; background-position: center; margin-right: 8px; flex-shrink: 0; }
        
        .msg-bubble { padding: 10px 14px; font-size: 14px; line-height: 1.5; word-break: break-word; }
        .hospital .msg-bubble { background-color: #fff; color: #333; border-radius: 4px 16px 16px 16px; border: 1px solid #eaeaea; }
        .user .msg-bubble { background-color: var(--primary-color); color: #fff; border-radius: 16px 4px 16px 16px; }
        
        .msg-time { font-size: 11px; color: #999; margin: 0 6px; margin-bottom: 2px; }
        
        .input-area { background-color: #fff; padding: 10px 15px; display: flex; align-items: center; border-top: 1px solid #eaeaea; flex-shrink: 0; padding-bottom: max(10px, env(safe-area-inset-bottom)); }
        .plus-btn { font-size: 24px; color: #999; margin-right: 10px; cursor: pointer; font-weight: 300;}
        .msg-input { flex: 1; background-color: #f4f5f7; border: none; border-radius: 20px; padding: 12px 15px; font-size: 14px; outline: none; }
        .send-btn { background-color: var(--primary-color); color: #fff; border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; justify-content: center; align-items: center; margin-left: 10px; cursor: pointer; font-weight: bold; font-size: 18px;}
        .send-btn:disabled { background-color: #ccc; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/mypage/chats.php" class="back-btn">←</a>
        <div class="page-title" id="hospitalTitle">채팅 상담</div>
    </header>

    <div class="chat-container" id="chatContainer">
        <div class="date-divider"><span>채팅이 시작되었습니다.</span></div>
        <!-- 메시지가 실시간으로 여기에 렌더링됩니다 -->
    </div>

    <div class="input-area">
        <div class="plus-btn">＋</div>
        <input type="text" class="msg-input" id="msgInput" placeholder="메시지를 입력하세요" autocomplete="off">
        <button class="send-btn" id="sendBtn" disabled>↑</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatId = "<?= htmlspecialchars($chat_id) ?>";
            const chatData = JSON.parse(localStorage.getItem('shinsa_chats')) || [];
            const currentChat = chatData.find(c => c.id === chatId);
            
            if (currentChat) {
                document.getElementById('hospitalTitle').innerText = currentChat.hospital_name;
                
                // 읽음 처리 (unread 초기화)
                if (currentChat.unread > 0) {
                    currentChat.unread = 0;
                    localStorage.setItem('shinsa_chats', JSON.stringify(chatData));
                }
            }
            
            const msgInput = document.getElementById('msgInput');
            const sendBtn = document.getElementById('sendBtn');
            const chatContainer = document.getElementById('chatContainer');
            
            // 맨 아래로 스크롤
            chatContainer.scrollTop = chatContainer.scrollHeight;
            
            let lastMsgId = 0;
            
            // 실시간 메시지 불러오기 (Short Polling)
            function loadMessages() {
                fetch(`/api/chat_api.php?action=load&room_id=${chatId}&last_id=${lastMsgId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.ok && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                const isUser = msg.sender_type === 'user';
                                const html = `
                                    <div class="msg-row ${isUser ? 'user' : 'hospital'}" style="margin-top: 10px;">
                                        ${!isUser ? `<div class="hospital-thumb" style="background-image: url('/static/images/skin.jpg')"></div>` : ''}
                                        <div class="msg-bubble">${msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</div>
                                        <div class="msg-time">${msg.time_str}</div>
                                    </div>
                                `;
                                chatContainer.insertAdjacentHTML('beforeend', html);
                                lastMsgId = msg.id; // 마지막 메시지 ID 갱신
                                
                                // 로컬스토리지에도 마지막 내용 저장 (목록 표시용)
                                if (currentChat) {
                                    currentChat.last_msg = msg.message;
                                    currentChat.last_time = msg.time_str;
                                    localStorage.setItem('shinsa_chats', JSON.stringify(chatData));
                                }
                            });
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        }
                    })
                    .catch(e => console.error(e));
            }

            // 3초마다 새 메시지 확인 (실시간 채팅 구현)
            setInterval(loadMessages, 3000);
            loadMessages(); // 최초 1회 실행
            
            msgInput.addEventListener('input', () => {
                sendBtn.disabled = msgInput.value.trim() === '';
            });
            
            function sendMessage() {
                const text = msgInput.value.trim();
                if (!text) return;
                
                sendBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('room_id', chatId);
                formData.append('sender_type', 'user');
                formData.append('message', text);
                
                fetch('/api/chat_api.php?action=send', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        msgInput.value = '';
                        loadMessages(); // 전송 성공 시 즉시 불러오기
                    } else {
                        alert(data.msg || "전송 실패");
                        sendBtn.disabled = false;
                    }
                });
            }
            
            sendBtn.addEventListener('click', sendMessage);
            msgInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
        });
    </script>
</body>
</html>


