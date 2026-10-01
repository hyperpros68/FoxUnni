<?php
$css = <<<'EOD'

/* =========================================
   Event Detail Page Styles
   ========================================= */
.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: var(--white);
    position: sticky;
    top: 0;
    z-index: 100;
    font-size: 24px;
}
.detail-header svg { cursor: pointer; }

.detail-hero-wrapper {
    width: 100%;
    background-color: #fff0f5;
    display: flex;
    justify-content: center;
}
.detail-hero-wrapper img {
    width: 100%;
    max-height: 400px;
    object-fit: contain;
    display: block;
}

.detail-content {
    padding: 20px;
    background: var(--white);
    margin-bottom: 8px;
}
.detail-section {
    background: var(--white);
    padding: 20px;
    margin-bottom: 8px;
    border-top: 10px solid #f5f5f5;
}

.h-name-tag { font-size: 14px; color: #666; margin-bottom: 8px; font-weight: 500; cursor: pointer; }
.e-main-title { font-size: 24px; font-weight: 800; line-height: 1.3; margin-bottom: 15px; }
.e-rating { font-size: 14px; color: #555; margin-bottom: 25px; }
.e-rating .star { color: #fabb00; margin-right: 4px; }

.rebook-card {
    background-color: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}
.rebook-card .title { font-size: 12px; color: #888; margin-bottom: 4px; }
.rebook-card .desc { font-size: 14px; font-weight: 500; }
.rebook-card .btn-rebook { color: #f25530; font-size: 13px; font-weight: 700; text-decoration: none; }

.option-box {
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
}
.option-box .opt-label { font-size: 12px; color: #888; margin-bottom: 8px; }
.option-box .opt-title { font-size: 14px; font-weight: 500; display: flex; justify-content: space-between; }
.option-box .opt-title span { color: #666; }

.price-area { margin-bottom: 15px; }
.price-area .original { text-decoration: line-through; color: #aaa; font-size: 14px; margin-bottom: 4px; }
.price-area .discount-row { display: flex; align-items: baseline; gap: 8px; }
.price-area .rate { color: #888; font-size: 18px; font-weight: 700; }
.price-area .final-price { font-size: 22px; font-weight: 800; }

.final-pay-box {
    background-color: #fafafa;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.final-pay-box .amount { font-size: 22px; font-weight: 800; color: #f25530; }
.final-pay-box .label { font-size: 14px; font-weight: 700; }

.point-info { display: flex; justify-content: space-between; font-size: 13px; color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.point-info .val { color: #0088cc; font-weight: 700; }
.duration-info { display: flex; font-size: 13px; color: #555; }
.duration-info .label { width: 50px; }

/* Section Title */
.sec-title { display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 800; margin-bottom: 20px; }
.sec-title .more { font-size: 13px; color: #888; font-weight: 500; }

/* Horizontal Review Cards */
.review-scroll {
    display: flex;
    overflow-x: auto;
    gap: 15px;
    padding-bottom: 10px;
    scrollbar-width: none;
}
.review-scroll::-webkit-scrollbar { display: none; }
.review-card {
    min-width: 260px;
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
}
.review-card .imgs { display: flex; }
.review-card .imgs div { flex: 1; height: 100px; background-size: cover; background-position: center; background-color: #ccc; }
.review-card .imgs .label { background: black; color: white; text-align: center; font-size: 12px; padding: 4px 0; font-weight: 700; }
.review-card .content { padding: 15px; }
.review-card .stars { color: #fabb00; font-size: 12px; margin-bottom: 8px; }
.review-card .text { font-size: 13px; color: #333; line-height: 1.5; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.review-card .meta { font-size: 12px; color: #888; display: flex; justify-content: space-between; }

/* Content Details */
.detail-text { font-size: 15px; line-height: 1.6; color: #333; }

/* Hospital Card */
.hosp-profile-card { border: 1px solid #eee; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.hosp-logo { width: 60px; height: 60px; border-radius: 50%; background: #f5f5f5; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; font-size: 10px; text-align: center; color: #aaa; overflow: hidden; }
.hosp-logo img { width: 100%; height: 100%; object-fit: cover; }
.hosp-info .badge { font-size: 10px; background: #eee; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px; display: inline-block; }
.hosp-info .name { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
.hosp-info .stats { font-size: 13px; color: #666; }

.doc-profile { border: 1px solid #eee; border-radius: 12px; padding: 20px; display: flex; align-items: flex-start; gap: 15px; }
.doc-img { width: 60px; height: 60px; border-radius: 50%; background: #eee; overflow: hidden; }
.doc-img img { width: 100%; height: 100%; object-fit: cover; }
.doc-info .name { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
.doc-info .spec { font-size: 11px; color: #0088cc; background: #e6f7ff; padding: 3px 6px; border-radius: 4px; display: inline-block; margin-top: 5px; margin-bottom: 5px; }
.doc-tags { display: flex; gap: 5px; flex-wrap: wrap; }
.doc-tags span { background: #f5f5f5; color: #666; font-size: 11px; padding: 4px 8px; border-radius: 4px; }

/* Bottom Action Bar */
.bottom-action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    background: var(--white);
    display: flex;
    align-items: center;
    padding: 10px 15px 25px;
    border-top: 1px solid #eee;
    z-index: 1000;
    gap: 10px;
}
.action-heart { display: flex; flex-direction: column; align-items: center; font-size: 11px; color: #888; margin-right: 5px; min-width: 45px; cursor: pointer; }
.action-heart svg { margin-bottom: 2px; }
.action-btn-outline { flex: 1; height: 48px; border: 1px solid #ddd; background: white; border-radius: 8px; font-size: 15px; font-weight: 700; display: flex; justify-content: center; align-items: center; cursor: pointer; color: #333; }
.action-btn-solid { flex: 1; height: 48px; background: #f25530; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; display: flex; justify-content: center; align-items: center; cursor: pointer; }
.action-btn-icon { width: 48px; height: 48px; border: 1px solid #ddd; background: white; border-radius: 8px; display: flex; justify-content: center; align-items: center; cursor: pointer; }

/* Applicant Toast */
.applicant-toast {
    position: absolute;
    top: -40px;
    left: 15px;
    right: 15px;
    background: white;
    border: 1px solid #eee;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 8px;
}
.applicant-toast span { color: #f25530; font-weight: 700; }
EOD;

file_put_contents('c:/Project/여우언니/Program/www/static/css/shinsa_style.css', $css, FILE_APPEND);
echo "CSS appended.";
?>


