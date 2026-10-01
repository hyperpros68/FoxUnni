import pymysql
from cron_purge_trash import run_trash_purge

conn = pymysql.connect(
    host="127.0.0.1", port=3306, user="pictro", password="pictro!@#$",
    database="Pictro", charset="utf8mb4", autocommit=True
)

with conn.cursor() as c:
    # 1. 30일 경과된 가상 휴지통 데이터 1건 삽입
    c.execute("""
        INSERT INTO user_ocr_history 
        (client_id, original_webp_path, thumb_webp_path, visual_webp_path, clean_bg_webp_path, translated_webp_path, parsed_json, target_lang, is_deleted, deleted_at, purge_due_date, image_size_bytes)
        VALUES 
        ('client_demo_hospital', '/files/dummy/test.webp', '/files/dummy/test_thumb.webp', '/files/dummy/test_vis.webp', '/files/dummy/test_clean.webp', '/files/dummy/test_trans.webp', '[]', 'en', 1, '2026-08-01', '2026-08-31 00:00:00', 500000)
    """)
    mock_id = c.lastrowid
    print(f"[1. 모의 만료 휴지통 데이터 생성]: history_id = {mock_id} (만료일: 2026-08-31)")

# 2. 배치 실행
print("[2. 배치 데몬 실행]")
purged_cnt = run_trash_purge()

# 3. 삭제 여부 확인
with conn.cursor() as c:
    c.execute("SELECT history_id FROM user_ocr_history WHERE history_id = %s", (mock_id,))
    remains = c.fetchone()
    if remains:
        print("[3. 검증 실패]: 데이터가 삭제되지 않았습니다.")
    else:
        print(f"[3. 검증 완료]: history_id = {mock_id} 가 정상 영구 삭제되었습니다! (총 {purged_cnt}건 처리)")

conn.close()
