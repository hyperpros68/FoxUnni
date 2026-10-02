# -*- coding: utf-8 -*-
"""
Pictro DB Schema Patch: user_ocr_history에 is_saved 컬럼 추가
- 1:1 변환 스튜디오에서 변환된 내역(테스트 포함)은 스토리지 용량은 즉시 차감하되,
  사용자가 '보관함에 저장' 버튼을 누른 건만 is_saved = 1 로 갤러리에 노출.
"""
import pymysql
from core.database import DB_CONFIG

def run_patch():
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as c:
            # 1. is_saved 컬럼 존재 여부 확인
            c.execute("SHOW COLUMNS FROM user_ocr_history LIKE 'is_saved'")
            col = c.fetchone()
            if not col:
                print("[Patch] Adding is_saved column to user_ocr_history...")
                c.execute("""
                    ALTER TABLE user_ocr_history 
                    ADD COLUMN is_saved TINYINT(1) NOT NULL DEFAULT 1 
                    COMMENT '보관함 등록 여부 (1: 보관함 정식 등록, 0: 임시/미저장)'
                    AFTER target_lang
                """)
                print("[Patch] is_saved column added successfully.")
            else:
                print("[Patch] is_saved column already exists.")
            
            # 인덱스 추가 (조회 성능 최적화)
            c.execute("SHOW INDEX FROM user_ocr_history WHERE Key_name = 'idx_history_saved'")
            idx = c.fetchone()
            if not idx:
                print("[Patch] Adding index idx_history_saved...")
                c.execute("ALTER TABLE user_ocr_history ADD INDEX idx_history_saved (client_id, is_saved, is_deleted)")
                print("[Patch] Index added successfully.")
    finally:
        conn.close()

if __name__ == '__main__':
    run_patch()
