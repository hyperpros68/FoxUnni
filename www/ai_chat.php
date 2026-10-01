<?php
// www/ai_chat.php
require_once 'config/db_connect.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI 상담 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        .chat-main { display: flex; flex-direction: column; padding: 20px; height: calc(100vh - 140px); overflow-y: auto; overflow-x: hidden; background-color: #f8f9fa; }
        .chat-messages { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; padding-bottom: 20px; }
        .msg-row { display: flex; width: 100%; margin-bottom: 5px; }
        .msg-row.user { justify-content: flex-end; }
        .msg-row.ai { justify-content: flex-start; }
        
        .msg-bubble { max-width: 80%; padding: 12px 16px; border-radius: 20px; font-size: 14px; line-height: 1.5; word-break: break-word; }
        .user .msg-bubble { background-color: var(--primary-color); color: white; border-bottom-right-radius: 4px; }
        .ai .msg-bubble { background-color: white; border: 1px solid #eee; border-bottom-left-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); color: #333; }
        
        .ai-profile { width: 32px; height: 32px; border-radius: 50%; background-color: #ffe6ee; display: flex; justify-content: center; align-items: center; margin-right: 8px; font-size: 16px; flex-shrink: 0; }
        
        /* 마크다운 스타일 처리용 */
        .msg-bubble strong { font-weight: 800; color: #f25530; }
        .user .msg-bubble strong { color: #ffe6ee; }
    </style>
</head>
<body class="ai-chat-body">
    <!-- Header -->
    <div class="chat-header">
        <a href="javascript:history.back()" class="back-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div style="font-weight:700; font-size:16px;">AI 뷰티 컨설턴트</div>
        <div class="history-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="chat-main" id="chatMain">
        <div class="chat-intro" id="chatIntro">
            <div class="sparkle-icon">✨</div>
            <h2>무엇이든 편하게 물어보세요</h2>
            <p>시술 고민, 회복 기간, 비교까지 친절하게 쉽게 알려드릴게요</p>
        </div>

        <div class="suggestion-chips" id="suggestionChips">
            <button class="suggestion-btn" onclick="sendSuggestedMessage('볼살 때문에 고민인데 뭐 받으면 좋을까?')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                볼살 때문에 고민인데 뭐 받으면 좋을까?
            </button>
            <button class="suggestion-btn" onclick="sendSuggestedMessage('리쥬란힐러 맞으면 효과가 얼마나 갈까?')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                리쥬란힐러 맞으면 효과가 얼마나 갈까?
            </button>
            <button class="suggestion-btn" onclick="sendSuggestedMessage('써마지랑 올리지오는 뭐가 달라?')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                써마지랑 올리지오는 뭐가 달라?
            </button>
        </div>

        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be appended here -->
        </div>
    </div>

    <!-- Input Area -->
    <div class="chat-input-area">
        <div class="input-wrapper">
            <input type="text" placeholder="어떤 고민이든 편하게 물어보세요" class="chat-input" id="chatInput" onkeypress="handleKeyPress(event)">
            <button class="send-btn" onclick="sendMessage()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>

    <script>
        const chatMain = document.getElementById('chatMain');
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const chatIntro = document.getElementById('chatIntro');
        const suggestionChips = document.getElementById('suggestionChips');

        // 스크롤 맨 아래로 이동
        function scrollToBottom() {
            chatMain.scrollTop = chatMain.scrollHeight;
        }

        // 제안 질문 클릭 시
        function sendSuggestedMessage(text) {
            chatInput.value = text;
            sendMessage();
        }

        // 엔터키 입력 처리
        function handleKeyPress(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        }

        // 마크다운 볼드체 파싱 처리
        function parseMarkdown(text) {
            // **text** -> <strong>text</strong>
            let parsed = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // 줄바꿈 처리
            parsed = parsed.replace(/\n/g, '<br>');
            return parsed;
        }

        async function sendMessage() {
            const message = chatInput.value.trim();
            if (message === '') return;

            // 소개 영역 숨기기
            if(chatIntro.style.display !== 'none') {
                chatIntro.style.display = 'none';
                suggestionChips.style.display = 'none';
            }

            // 사용자 말풍선 추가
            appendMessage('user', message);
            chatInput.value = '';

            try {
                // API 연동 (Mock AI 백엔드)
                const response = await fetch('/api/chat_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    appendMessage('ai', data.reply);
                } else {
                    appendMessage('ai', '죄송합니다. 오류가 발생했어요. 다시 시도해 주세요.');
                }
            } catch (error) {
                appendMessage('ai', '네트워크 연결이 불안정합니다. 잠시 후 다시 시도해 주세요.');
            }
        }

        function appendMessage(sender, text) {
            const row = document.createElement('div');
            row.className = 'msg-row ' + sender;
            
            let html = '';
            if (sender === 'ai') {
                html += '<div class="ai-profile">🤖</div>';
            }
            
            html += '<div class="msg-bubble">' + parseMarkdown(text) + '</div>';
            row.innerHTML = html;
            
            chatMessages.appendChild(row);
            scrollToBottom();
        }

        function removeElement(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }
    </script>
</body>
</html>


