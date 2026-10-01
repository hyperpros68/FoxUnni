<?php
$css = <<<'EOD'

/* =========================================
   Full Screen Modal
   ========================================= */
.full-modal {
    position: fixed;
    top: 100vh;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 480px;
    height: 100%;
    background: #f5f5f5;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    transition: top 0.3s ease-in-out;
}
.full-modal.show {
    top: 0;
}
.full-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: var(--white);
    border-bottom: 1px solid #eee;
}
.full-modal .modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    padding-bottom: 100px;
}
.full-modal .review-card {
    min-width: auto;
    width: 100%;
    margin-bottom: 15px;
    background: var(--white);
}
EOD;
file_put_contents('c:/Project/여우언니/Program/www/static/css/shinsa_style.css', $css, FILE_APPEND);
echo "CSS added.";
?>


