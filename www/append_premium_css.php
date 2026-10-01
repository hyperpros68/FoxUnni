<?php
$css = <<<'EOD'

/* =========================================
   Premium Quick Menu Styles
   ========================================= */
.premium-menu {
    display: flex;
    justify-content: space-between;
    padding: 10px 15px 30px;
}
.premium-menu .quick-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    font-size: 11px;
    text-align: center;
    font-weight: 700;
    color: #333;
    width: 60px;
}
.premium-menu .quick-item span {
    line-height: 1.2;
    letter-spacing: -0.3px;
}
.premium-icon {
    width: 50px;
    height: 50px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    box-shadow: 0 4px 12px rgba(255, 42, 117, 0.15); /* 은은한 핑크 그림자 */
    border: 1px solid rgba(255, 42, 117, 0.05);
    transition: transform 0.2s;
}
.premium-icon:active {
    transform: scale(0.95);
}
.ai-icon svg { stroke: url(#pink-grad); }
.vip-icon svg { stroke: url(#pink-grad); fill: rgba(255, 42, 117, 0.05); }
.care-icon svg { stroke: url(#pink-grad); }
.talk-icon svg { stroke: url(#pink-grad); fill: rgba(255, 42, 117, 0.05); }
.trend-icon svg { stroke: url(#pink-grad); }
EOD;
file_put_contents('c:/Project/여우언니/Program/www/static/css/shinsa_style.css', $css, FILE_APPEND);
echo "Premium CSS added.";
?>


