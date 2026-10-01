# -*- coding: utf-8 -*-
"""
Pictro API Key Authentication & Quota Metering
- Validates X-API-Key against `api_key_master` & `api_client_user`
- Enforces Rate Limits & Traffic Hard Limits
- Logs request metrics to `api_usage_log`
"""

import sys
import os
from datetime import datetime
from typing import Optional, Dict, Any
from fastapi import Header, HTTPException, Request

sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from core.database import query_one, execute

class APIKeyValidator:
    @staticmethod
    def validate_key(
        request: Request,
        x_api_key: Optional[str] = Header(None, alias="X-API-Key")
    ) -> Dict[str, Any]:
        """X-API-Key 헤더 검증 및 클라이언트 권한/쿼터 확인"""
        if not x_api_key:
            raise HTTPException(
                status_code=401,
                detail={"error": "AUTH_REQUIRED", "message": "X-API-Key header is missing"}
            )

        client_ip = request.client.host if request.client else "127.0.0.1"

        # 1. API Key 마스터, 클라이언트 및 플랜(일일 한도) 상태 조회
        sql = """
            SELECT 
                k.key_id, k.client_id, k.key_name, k.api_key_hash, k.is_active,
                k.rate_limit_per_min, k.allowed_ips, k.expires_at,
                u.company_name, u.plan_id, u.status, u.role,
                u.current_storage_bytes, u.monthly_used_traffic_bytes, u.hard_limit_bytes,
                COALESCE(p.daily_quota, 10) AS daily_quota
            FROM `api_key_master` k
            INNER JOIN `api_client_user` u ON k.client_id = u.client_id
            LEFT JOIN `api_plan_tier` p ON u.plan_id = p.plan_id
            WHERE k.api_key_hash = %s
            LIMIT 1;
        """
        row = query_one(sql, (x_api_key,))

        if not row:
            raise HTTPException(
                status_code=403,
                detail={"error": "INVALID_KEY", "message": "Provided API Key does not exist"}
            )

        if not row["is_active"]:
            raise HTTPException(
                status_code=403,
                detail={"error": "KEY_DEACTIVATED", "message": "This API Key has been deactivated"}
            )

        if row["expires_at"] and row["expires_at"] < datetime.now():
            raise HTTPException(
                status_code=403,
                detail={"error": "KEY_EXPIRED", "message": "This API Key has expired"}
            )

        if row["status"] != "ACTIVE":
            raise HTTPException(
                status_code=403,
                detail={"error": "ACCOUNT_SUSPENDED", "message": f"Client account is {row['status']}"}
            )

        # 2. IP 화이트리스트 체크 (설정된 경우)
        allowed_ips = (row["allowed_ips"] or "").strip()
        if allowed_ips and allowed_ips != "*":
            ip_list = [ip.strip() for ip in allowed_ips.split(",") if ip.strip()]
            if client_ip not in ip_list and client_ip not in ["127.0.0.1", "localhost"]:
                raise HTTPException(
                    status_code=403,
                    detail={"error": "IP_NOT_ALLOWED", "message": f"Client IP {client_ip} is not whitelisted"}
                )

        # 3. 일일 API 호출 한도(daily_quota) 체크 (단, /client/usage 조회는 예외 허용하여 잔여량 파악 가능)
        daily_quota = int(row.get("daily_quota") or 10)
        count_sql = """
            SELECT COUNT(*) AS today_count 
            FROM `api_usage_log` 
            WHERE `client_id` = %s AND DATE(`created_at`) = CURDATE() AND `status_code` = 200;
        """
        count_row = query_one(count_sql, (row["client_id"],))
        today_calls = int(count_row["today_count"]) if count_row else 0

        req_path = request.url.path.rstrip("/")
        # /gallery/, /admin/ 조회 및 /client/usage 잔여량 조회는 한도 체크 제외
        is_exempt = "/gallery" in req_path or "/admin" in req_path or req_path.endswith("/client/usage")
        if not is_exempt and today_calls >= daily_quota:
            raise HTTPException(
                status_code=429,
                detail={
                    "error": "DAILY_QUOTA_EXCEEDED",
                    "message": f"일일 API 호출 한도({daily_quota}회)를 모두 소진하였습니다. (오늘 사용: {today_calls}/{daily_quota}회). 매일 자정(00:00 KST)에 리셋됩니다.",
                    "daily_quota": daily_quota,
                    "today_calls": today_calls
                }
            )

        row["today_calls"] = today_calls

        # 4. 월간 트래픽 하드 리밋(차단선) 체크 (0이 아닌 경우)
        if row["hard_limit_bytes"] > 0 and row["monthly_used_traffic_bytes"] >= row["hard_limit_bytes"]:
            raise HTTPException(
                status_code=429,
                detail={"error": "QUOTA_EXCEEDED", "message": "Monthly hard traffic quota exceeded. Upgrade plan."}
            )

        return row

def log_api_usage(
    client_id: str,
    key_id: int,
    endpoint: str,
    http_status: int,
    ip_address: str,
    user_agent: str,
    input_bytes: int,
    output_bytes: int,
    duration_ms: int,
    img_w: int = 0,
    img_h: int = 0,
    megapixel_count: float = 0.0,
    calculated_cost: int = 0
):
    """API 호출 및 미터링 내역을 DB에 기록 (트리거 trg_after_insert_usage_log 가 월간 트래픽 자동 누적 가산)"""
    total_bytes = input_bytes + output_bytes
    sql = """
        INSERT INTO `api_usage_log` (
            `client_id`, `key_id`, `endpoint`, `input_bytes`, `output_bytes`,
            `total_traffic_bytes`, `image_width`, `image_height`, `megapixel_count`,
            `duration_ms`, `status_code`, `calculated_cost`, `request_ip`,
            `user_agent`, `created_at`
        ) VALUES (
            %s, %s, %s, %s, %s,
            %s, %s, %s, %s,
            %s, %s, %s, %s,
            %s, NOW()
        );
    """
    try:
        execute(sql, (
            client_id, key_id, endpoint, input_bytes, output_bytes,
            total_bytes, img_w, img_h, megapixel_count,
            duration_ms, http_status, calculated_cost,
            ip_address[:45], (user_agent or "")[:255]
        ))
    except Exception as e:
        print(f"[AUTH_LOG_ERROR] Failed to record api_usage_log: {e}")
