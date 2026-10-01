# -*- coding: utf-8 -*-
"""
Pictro Automatic Trash Purge Daemon
30일 유예 기간이 만료된(purge_due_date <= NOW()) 휴지통 배너를
물리 디스크 및 DB에서 영구 삭제하고 고객사 스토리지를 자동 환원하는 데몬
"""
import os
import sys
import datetime
import pymysql

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "pictro",
    "password": "pictro!@#$",
    "database": "Pictro",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
    "autocommit": True
}

BASE_DIR = "/home/mika/Project/ThrillRig/Program/Pictro"

def run_trash_purge():
    now_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{now_str}] Starting Pictro 30-Day Trash Purge Job...")

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # 1. 만료 대상 조회
            sql = """
                SELECT history_id, client_id, original_webp_path, visual_webp_path, 
                       clean_bg_webp_path, translated_webp_path, thumb_webp_path,
                       image_size_bytes, purge_due_date
                FROM user_ocr_history
                WHERE is_deleted = 1 AND purge_due_date IS NOT NULL AND purge_due_date <= NOW()
                LIMIT 500
            """
            cursor.execute(sql)
            expired_items = cursor.fetchall()

            if not expired_items:
                print(f"[{now_str}] No expired trash items found. Done.")
                return 0

            print(f"[{now_str}] Found {len(expired_items)} expired trash items to purge.")

            # 고객사별 반환할 용량 집계
            client_freed_bytes = {}
            deleted_ids = []

            for item in expired_items:
                hid = item["history_id"]
                cid = item["client_id"]
                deleted_ids.append(hid)
                client_freed_bytes[cid] = client_freed_bytes.get(cid, 0) + (item["image_size_bytes"] or 0)

                # 물리 파일 삭제
                file_keys = [
                    "original_webp_path", "visual_webp_path",
                    "clean_bg_webp_path", "translated_webp_path", "thumb_webp_path"
                ]
                for fk in file_keys:
                    rel_path = item.get(fk)
                    if rel_path:
                        # /files/... 경로 변환
                        if rel_path.startswith("/files/"):
                            local_fpath = os.path.join(BASE_DIR, "datas", "uploads", rel_path.replace("/files/", ""))
                        elif rel_path.startswith("files/"):
                            local_fpath = os.path.join(BASE_DIR, "datas", "uploads", rel_path.replace("files/", ""))
                        else:
                            local_fpath = os.path.join(BASE_DIR, rel_path.lstrip("/"))

                        if os.path.exists(local_fpath):
                            try:
                                os.remove(local_fpath)
                            except Exception as fe:
                                print(f"  [Warning] Failed to delete file {local_fpath}: {fe}")

            # 2. DB 레코드 영구 삭제
            ids_str = ",".join(map(str, deleted_ids))
            cursor.execute(f"DELETE FROM user_ocr_history WHERE history_id IN ({ids_str})")
            print(f"[{now_str}] Permanently deleted {len(deleted_ids)} records from user_ocr_history.")

            # 3. 고객사별 스토리지 차감 반환
            for cid, freed in client_freed_bytes.items():
                if freed > 0:
                    cursor.execute("""
                        UPDATE api_client_user
                        SET current_storage_bytes = GREATEST(0, CAST(current_storage_bytes AS SIGNED) - %s)
                        WHERE client_id = %s
                    """, (freed, cid))
                    print(f"[{now_str}] Restored {round(freed/1024, 1)} KB storage to client '{cid}'.")

            print(f"[{now_str}] Purge job successfully completed.")
            return len(deleted_ids)
    finally:
        conn.close()

if __name__ == "__main__":
    run_trash_purge()
