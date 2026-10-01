# -*- coding: utf-8 -*-
"""
Pictro Database Connection Manager
- Target: MySQL 8.4+ (Database: Pictro)
- User: pictro / PW: pictro!@#$
"""

import os
import pymysql
from pymysql.cursors import DictCursor
from typing import Optional, Dict, Any, List

DB_CONFIG = {
    "host": os.getenv("PICTRO_DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("PICTRO_DB_PORT", "3306")),
    "user": os.getenv("PICTRO_DB_USER", "pictro"),
    "password": os.getenv("PICTRO_DB_PASSWORD", "pictro!@#$"),
    "database": os.getenv("PICTRO_DB_NAME", "Pictro"),
    "charset": "utf8mb4",
    "cursorclass": DictCursor,
    "autocommit": True,
    "connect_timeout": 5
}

def get_db_connection():
    """MySQL 데이터베이스 연결 인스턴스 반환"""
    return pymysql.connect(**DB_CONFIG)

def query_one(sql: str, params: Optional[tuple] = None) -> Optional[Dict[str, Any]]:
    """단건 조회 헬퍼"""
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(sql, params or ())
            return cursor.fetchone()
    finally:
        conn.close()

def query_all(sql: str, params: Optional[tuple] = None) -> List[Dict[str, Any]]:
    """다건 조회 헬퍼"""
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(sql, params or ())
            return cursor.fetchall()
    finally:
        conn.close()

def execute(sql: str, params: Optional[tuple] = None) -> int:
    """INSERT / UPDATE / DELETE 실행 헬퍼 (영향받은 row 수 반환)"""
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            affected = cursor.execute(sql, params or ())
            return affected
    finally:
        conn.close()
