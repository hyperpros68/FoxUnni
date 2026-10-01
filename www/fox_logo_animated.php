<?php
// fox_logo_animated.php
// 실버 테마의 애니메이션 윙크 여우 로고 SVG
$size = isset($fox_logo_size) ? $fox_logo_size : '60px';
?>
<style>
    .fox-logo-svg {
        display: inline-block;
        vertical-align: middle;
        overflow: visible;
    }
    
    /* 꼬리를 훨씬 크고 역동적으로 흔들게 */
    .fox-tail {
        transform-origin: 30px 80px; 
        animation: tailWag 1.5s ease-in-out infinite alternate;
    }
    @keyframes tailWag {
        0% { transform: rotate(-15deg); }
        100% { transform: rotate(25deg); }
    }

    /* 윙크 애니메이션 */
    .fox-eye-right {
        transform-origin: 65px 45px;
        animation: foxWink 3.5s infinite;
    }
    @keyframes foxWink {
        0%, 40%, 60%, 100% { transform: scaleY(1); opacity: 1; }
        50% { transform: scaleY(0.1); opacity: 0; }
    }
    
    .fox-wink-curve {
        opacity: 0;
        animation: foxWinkCurve 3.5s infinite;
    }
    @keyframes foxWinkCurve {
        0%, 40%, 60%, 100% { opacity: 0; }
        50% { opacity: 1; }
    }
</style>

<!-- viewBox를 크게 늘려 꼬리가 잘리지 않게 함 -->
<svg class="fox-logo-svg" style="width: <?= htmlspecialchars($size) ?>; height: <?= htmlspecialchars($size) ?>;" viewBox="-40 -10 160 120" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="silverGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#ffffff" />
            <stop offset="50%" stop-color="#dce3eb" />
            <stop offset="100%" stop-color="#b0bec5" />
        </linearGradient>
        <linearGradient id="iceBlueGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#e0f7fa" />
            <stop offset="100%" stop-color="#80deea" />
        </linearGradient>
        <filter id="drop-shadow" x="-30%" y="-30%" width="160%" height="160%">
            <feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#000000" flood-opacity="0.15"/>
        </filter>
    </defs>
    
    <!-- 풍성하고 큰 꼬리 -->
    <g class="fox-tail" filter="url(#drop-shadow)">
        <path d="M 30 80 Q -40 80, -20 20 Q -5 -10, 15 25 Q 35 60, 30 80 Z" fill="url(#silverGrad)" />
        <path d="M -10 10 Q -5 0, 15 25 Q 10 35, -10 10 Z" fill="#ffffff" />
    </g>

    <!-- 얼굴 -->
    <g filter="url(#drop-shadow)">
        <polygon points="25,40 10,5 40,25" fill="url(#silverGrad)" />
        <polygon points="75,40 90,5 60,25" fill="url(#silverGrad)" />
        <polygon points="23,35 15,12 35,25" fill="url(#iceBlueGrad)" />
        <polygon points="77,35 85,12 65,25" fill="url(#iceBlueGrad)" />
        
        <path d="M 20 40 Q 50 20, 80 40 Q 90 70, 50 90 Q 10 70, 20 40 Z" fill="url(#silverGrad)" />
        <path d="M 25 45 Q 50 35, 75 45 Q 85 70, 50 85 Q 15 70, 25 45 Z" fill="#ffffff" />
    </g>
    
    <!-- 코 -->
    <circle cx="50" cy="70" r="4.5" fill="#37474f" />
    
    <!-- 왼쪽 눈 (째지고 매혹적인 눈매) -->
    <path d="M 22 41 Q 32 37, 40 46" stroke="#37474f" stroke-width="3.5" stroke-linecap="round" fill="none" />
    <circle cx="34" cy="46" r="3.5" fill="#37474f" />
    
    <!-- 오른쪽 눈 (윙크 담당) -->
    <g class="fox-eye-right">
        <path d="M 78 41 Q 68 37, 60 46" stroke="#37474f" stroke-width="3.5" stroke-linecap="round" fill="none" />
        <circle cx="66" cy="46" r="3.5" fill="#37474f" />
    </g>
    <!-- 윙크할 때 나타나는 찡긋 눈 (째진 형태 유지) -->
    <path class="fox-wink-curve" d="M 78 45 Q 68 38, 60 45" stroke="#37474f" stroke-width="3.5" stroke-linecap="round" fill="none" />
    
    <!-- 볼터치 -->
    <ellipse cx="30" cy="55" rx="7" ry="4" fill="#ff4081" opacity="0.4" />
    <ellipse cx="70" cy="55" rx="7" ry="4" fill="#ff4081" opacity="0.4" />
    
    <!-- 입 -->
    <path d="M 46 78 Q 50 83, 54 78" stroke="#37474f" stroke-width="2.5" stroke-linecap="round" fill="none" />
</svg>
