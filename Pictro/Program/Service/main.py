# -*- coding: utf-8 -*-
"""
Pictro Enterprise Commercial REST API Service (FastAPI)
- Port: 49991
- Standard RESTful Endpoints for External Services (FoxUnni, Mobile App, Crawlers, Partners)
- Fully Integrated with Database Quota Metering, API Key Auth & Visual Inspection
"""

import os
import sys
import time
import shutil
import uuid
import base64
import json
import hashlib
import urllib.request
from typing import Optional, Dict, Any, List
from pydantic import BaseModel
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Header, Request, Depends
from fastapi.responses import JSONResponse, FileResponse, HTMLResponse
from fastapi.middleware.cors import CORSMiddleware

# 로컬 모듈 경로 설정
SERVICE_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.dirname(SERVICE_DIR)
if PARENT_DIR not in sys.path:
    sys.path.insert(0, PARENT_DIR)
if SERVICE_DIR not in sys.path:
    sys.path.insert(0, SERVICE_DIR)

from pictro_engine import PictroEngine
from auth.api_key_auth import APIKeyValidator, log_api_usage
from core.database import query_one

API_DESCRIPTION = """
### 🚀 픽트로 (Pictro) 상용 다국어 배너 AI 비전 & 1:1 치환 REST API
- **[📖 Pictro API 연동 가이드 및 3대 모드 상세 매뉴얼](/pictro-api/guide)** (클릭 시 전용 웹 매뉴얼로 이동)
- **[💳 Pictro 구독 플랜 및 B2B 과금 체계 정책 안내](/pictro-api/billing)** (3단계 플랜, I/O 트래픽 미터링, 유예 정책)
- **[🎨 픽트로 스튜디오 웹 콘솔](/pictro/)** (화면 어디서든 Ctrl+V 클립보드 붙여넣기 및 시각적 테스트)

---
#### 🔑 인증 헤더 안내:
- 모든 API 호출 시 헤더에 `X-API-Key: FOXUNNI-PARTNER-MASTER-KEY-2026` 전달 필수 (우측 상단 **Authorize** 버튼에 입력)
- 테스트 키(`FOXUNNI-PARTNER-MASTER-KEY-2026`)는 **일일 10회 호출 제한**이 적용됩니다.

---
### 🐍 [Python 예제 1] `trans` 풀코스 1:1 다국어 치환 모드 (기본값, 약 6초)
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/pictro/process"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

# 원격 웹 이미지 URL을 일본어로 1:1 치환하는 예제
data = {
    "image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg",
    "mode": "trans",       # 풀코스 치환 (탐색 -> 번역 -> 배경복원 -> 폰트합성)
    "target_lang": "ja"    # 언어 선택: en, ja, zh-CN, zh-TW, vi, th
}

res = requests.post(url, headers=headers, data=data)
data = res.json()
print("치환 완료 배너 URL:", data.get("translated_image_url"))
print("복원된 순수 배경 URL:", data.get("clean_bg_image_url"))
```

---
### 🐍 [Python 예제 2] `clear` AI 배경 복원 모드 (글자 지우기, 약 4초)
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/pictro/process"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

# 배너 글자를 깨끗이 지우고 배경만 추출하는 예제
data = {
    "image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg",
    "mode": "clear"        # AI Big-LaMa 배경 복원
}

res = requests.post(url, headers=headers, data=data)
print("글자 제거된 깨끗한 배경 URL:", res.json().get("clean_bg_image_url"))
```

---
### 🐍 [Python 예제 3] `detect` OCR 탐색 모드 (초고속 영역/텍스트 추출, 약 1초)
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/pictro/process"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

# 배너 텍스트 영역 및 바운딩 박스를 검출하는 예제
data = {
    "image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg",
    "mode": "detect"       # 텍스트 영역 및 사각 바운딩 박스 검출
}

res = requests.post(url, headers=headers, data=data)
print("검출 박스 시각화 이미지 URL:", res.json().get("visualized_image_url"))
```
"""

app = FastAPI(
    title="Pictro Enterprise AI Banner Engine API",
    description=API_DESCRIPTION,
    version="1.0.0",
    root_path="/pictro-api"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

UPLOAD_DIR = "/home/mika/Project/ThrillRig/Program/Pictro/datas/uploads"
os.makedirs(UPLOAD_DIR, exist_ok=True)

# 엔진 싱글톤 로딩
engine = None

@app.on_event("startup")
def startup_event():
    global engine
    print("[API] Initializing Pictro Engine for External Services...")
    try:
        engine = PictroEngine()
        print("[API] Pictro Commercial API Service Ready on port 49991!")
    except Exception as e:
        print(f"[API ERROR] Engine startup error: {e}")

# ================================================================================
# 0. 공식 API 가이드 및 과금 정책 웹페이지 서빙
# ================================================================================
DOC_HTML_PATH = "/home/mika/Project/ThrillRig/Program/Pictro/Doc/pictro_api_guide.html"
BILLING_HTML_PATH = "/home/mika/Project/ThrillRig/Program/Pictro/Doc/pictro_billing_policy.html"

@app.get("/guide", response_class=HTMLResponse, summary="API 공식 연동 가이드 웹 매뉴얼", tags=["3. 공식 가이드 & 과금 정책"])
@app.get("/", response_class=HTMLResponse, summary="API 메인 포털 가이드", tags=["3. 공식 가이드 & 과금 정책"])
def get_api_guide():
    """브라우저에서 직접 열람 가능한 모던 다크 테마 API 가이드 웹페이지 서빙"""
    if os.path.exists(DOC_HTML_PATH):
        with open(DOC_HTML_PATH, "r", encoding="utf-8") as f:
            return f.read()
    local_path = os.path.join(os.path.dirname(SERVICE_DIR), "Doc", "pictro_api_guide.html")
    if os.path.exists(local_path):
        with open(local_path, "r", encoding="utf-8") as f:
            return f.read()
    return "<h1>Pictro API Guide</h1><p><a href='/pictro-api/docs'>Swagger UI 바로가기</a></p>"

@app.get("/billing", response_class=HTMLResponse, summary="상용 구독 플랜 및 과금 체계 안내 웹 매뉴얼", tags=["3. 공식 가이드 & 과금 정책"])
def get_billing_policy():
    """브라우저에서 직접 열람 가능한 3단계 구독 플랜 및 과금 정책 웹페이지 서빙"""
    if os.path.exists(BILLING_HTML_PATH):
        with open(BILLING_HTML_PATH, "r", encoding="utf-8") as f:
            return f.read()
    local_path = os.path.join(os.path.dirname(SERVICE_DIR), "Doc", "pictro_billing_policy.html")
    if os.path.exists(local_path):
        with open(local_path, "r", encoding="utf-8") as f:
            return f.read()
    return "<h1>Pictro Billing Policy</h1><p><a href='/pictro-api/docs'>Swagger UI 바로가기</a></p>"

# ================================================================================
# 0-1. 사용자 및 슈퍼 관리자 인증 체계 (Auth Router)
# ================================================================================
class LoginRequest(BaseModel):
    email: str
    password: str

class RegisterRequest(BaseModel):
    company_name: str
    manager_name: str
    manager_email: str
    manager_phone: Optional[str] = ""
    password: str

@app.post("/api/v1/auth/login", summary="일반 고객사 로그인", tags=["0. 통합 계정 & 인증"])
def client_login(req: LoginRequest):
    """일반 고객사(병원/에이전시) 로그인"""
    from core.database import query_one, query_all
    pw_hash = hashlib.sha256(req.password.strip().encode('utf-8')).hexdigest()
    sql = """
        SELECT client_id, company_name, manager_name, manager_email, plan_id, status, role
        FROM api_client_user
        WHERE manager_email = %s AND password_hash = %s
        LIMIT 1
    """
    user = query_one(sql, (req.email.strip(), pw_hash))
    if not user:
        raise HTTPException(status_code=401, detail="이메일 또는 비밀번호가 일치하지 않습니다.")
    if user["status"] != "ACTIVE":
        raise HTTPException(status_code=403, detail=f"계정이 비활성화 상태입니다. ({user['status']})")

    # 기본 API Key 조회
    key_row = query_one("SELECT api_key_hash FROM api_key_master WHERE client_id = %s AND is_active = 1 LIMIT 1", (user["client_id"],))
    api_key = key_row["api_key_hash"] if key_row else ""

    return {
        "success": True,
        "token": f"token_{user['client_id']}",
        "user": user,
        "api_key": api_key,
        "is_admin": user["role"] == "ADMIN"
    }

@app.post("/api/v1/auth/admin-login", summary="슈퍼 관리자 전용 보안 로그인", tags=["0. 통합 계정 & 인증"])
def admin_login(req: LoginRequest):
    """슈퍼 관리자(Super Admin) 전용 보안 로그인 (role='ADMIN' 필수)"""
    from core.database import query_one
    pw_hash = hashlib.sha256(req.password.strip().encode('utf-8')).hexdigest()
    # 이메일 또는 client_id 로 로그인 지원
    sql = """
        SELECT client_id, company_name, manager_name, manager_email, plan_id, status, role
        FROM api_client_user
        WHERE (manager_email = %s OR client_id = %s) AND password_hash = %s AND role = 'ADMIN'
        LIMIT 1
    """
    admin = query_one(sql, (req.email.strip(), req.email.strip(), pw_hash))
    if not admin:
        raise HTTPException(status_code=401, detail="슈퍼 관리자 권한이 없거나 자격 증명이 올바르지 않습니다.")

    key_row = query_one("SELECT api_key_hash FROM api_key_master WHERE client_id = %s AND is_active = 1 LIMIT 1", (admin["client_id"],))
    api_key = key_row["api_key_hash"] if key_row else ""

    return {
        "success": True,
        "token": f"admin_token_{admin['client_id']}",
        "user": admin,
        "api_key": api_key,
        "is_admin": True
    }

@app.post("/api/v1/auth/register", summary="일반 고객사 신규 회원가입 (Starter 플랜 기본 배정)", tags=["0. 통합 계정 & 인증"])
def client_register(req: RegisterRequest):
    """일반 고객사 회원가입 및 기본 API Key 자동 발급"""
    from core.database import query_one, execute
    # 이메일 중복 체크
    exist = query_one("SELECT client_id FROM api_client_user WHERE manager_email = %s", (req.manager_email.strip(),))
    if exist:
        raise HTTPException(status_code=400, detail="이미 등록된 이메일 주소입니다.")

    new_cid = f"client_{uuid.uuid4().hex[:8]}"
    pw_hash = hashlib.sha256(req.password.strip().encode('utf-8')).hexdigest()

    # 계정 삽입 (기본 BASIC 플랜)
    ins_sql = """
        INSERT INTO api_client_user 
        (client_id, company_name, manager_name, manager_email, manager_phone, password_hash, plan_id, status, role)
        VALUES (%s, %s, %s, %s, %s, %s, 'BASIC', 'ACTIVE', 'CLIENT')
    """
    execute(ins_sql, (new_cid, req.company_name, req.manager_name, req.manager_email.strip(), req.manager_phone, pw_hash))

    # 기본 API Key 자동 발급
    new_api_key = f"PICTRO-LIVE-{uuid.uuid4().hex[:16].upper()}"
    key_sql = """
        INSERT INTO api_key_master (client_id, key_name, api_key_hash, is_active, rate_limit_per_min)
        VALUES (%s, '기본 발급 API 키', %s, 1, 60)
    """
    execute(key_sql, (new_cid, new_api_key))

    return {
        "success": True,
        "message": "회원가입이 완료되었습니다. 기본 10GB Starter 플랜이 배정되었습니다.",
        "client_id": new_cid,
        "api_key": new_api_key
    }

# ================================================================================
# 0-2. 슈퍼 관리자(Super Admin) 운영 관제 API
# ================================================================================
class UpdatePlanRequest(BaseModel):
    client_id: str
    plan_id: str

class ToggleStatusRequest(BaseModel):
    client_id: str
    status: str

@app.get("/api/v1/admin/overview", summary="슈퍼 관리자 종합 대시보드 지표", tags=["0. 통합 계정 & 인증"])
def admin_overview(auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)):
    """전체 고객사 수, 오늘 변환 건수, 누적 매출, 스토리지 총 사용량 요약"""
    if auth_user.get("role") != "ADMIN":
        raise HTTPException(status_code=403, detail="슈퍼 관리자 권한이 필요합니다.")

    from core.database import query_one, query_all

    # 1. 전체 고객사 수
    clients_cnt = query_one("SELECT COUNT(*) AS cnt FROM api_client_user")["cnt"]

    # 2. 오늘 총 OCR 변환 장수
    today_cnt = query_one("""
        SELECT COUNT(*) AS cnt 
        FROM user_ocr_history 
        WHERE DATE(created_at) = CURDATE()
    """)["cnt"]

    # 3. 플랜별 월간 예상 매출 (MRR)
    plan_mrr_rows = query_all("""
        SELECT u.plan_id, COUNT(*) AS client_count, COALESCE(p.monthly_fee, 0) AS monthly_fee
        FROM api_client_user u
        LEFT JOIN api_plan_tier p ON u.plan_id = p.plan_id
        WHERE u.status = 'ACTIVE'
        GROUP BY u.plan_id, p.monthly_fee
    """)
    total_mrr = sum(int(r["client_count"]) * int(r["monthly_fee"]) for r in plan_mrr_rows)

    # 4. 전체 스토리지 실사용량
    storage_sum = query_one("SELECT COALESCE(SUM(current_storage_bytes), 0) AS total_bytes FROM api_client_user")["total_bytes"]
    storage_gb = round(storage_sum / (1024 ** 3), 2)

    return {
        "success": True,
        "metrics": {
            "total_clients": clients_cnt,
            "today_conversions": today_cnt,
            "monthly_mrr": total_mrr,
            "total_storage_gb": storage_gb
        }
    }

@app.get("/api/v1/admin/clients", summary="슈퍼 관리자 고객사 목록 조회", tags=["0. 통합 계정 & 인증"])
def admin_get_clients(auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)):
    """전체 등록된 고객사 목록 및 사용량 상세 조회"""
    if auth_user.get("role") != "ADMIN":
        raise HTTPException(status_code=403, detail="슈퍼 관리자 권한이 필요합니다.")

    from core.database import query_all
    sql = """
        SELECT u.client_id, u.company_name, u.manager_name, u.manager_email, u.manager_phone,
               u.plan_id, u.status, u.role, u.current_storage_bytes, u.created_at,
               COALESCE(p.plan_name, u.plan_id) AS plan_title,
               COALESCE(p.max_storage_bytes, 10737418240) AS max_storage_bytes,
               (SELECT COUNT(*) FROM user_ocr_history h WHERE h.client_id = u.client_id AND h.is_deleted = 0) AS active_banners
        FROM api_client_user u
        LEFT JOIN api_plan_tier p ON u.plan_id = p.plan_id
        ORDER BY u.created_at DESC
    """
    rows = query_all(sql)
    for r in rows:
        r["used_mb"] = round((r.get("current_storage_bytes") or 0) / (1024 * 1024), 2)
        r["max_gb"] = round((r.get("max_storage_bytes") or 0) / (1024 ** 3), 1)

    return {
        "success": True,
        "count": len(rows),
        "clients": rows
    }

@app.post("/api/v1/admin/clients/update-plan", summary="고객사 플랜 강제 변경", tags=["0. 통합 계정 & 인증"])
def admin_update_plan(req: UpdatePlanRequest, auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)):
    """관리자 권한으로 특정 고객사의 플랜 즉시 업그레이드/다운그레이드"""
    if auth_user.get("role") != "ADMIN":
        raise HTTPException(status_code=403, detail="슈퍼 관리자 권한이 필요합니다.")

    from core.database import execute
    sql = "UPDATE api_client_user SET plan_id = %s WHERE client_id = %s"
    affected = execute(sql, (req.plan_id.upper(), req.client_id))
    return {"success": True, "message": f"플랜이 {req.plan_id.upper()}로 변경되었습니다.", "affected": affected}

@app.post("/api/v1/admin/clients/toggle-status", summary="고객사 계정 상태 변경 (차단/해제)", tags=["0. 통합 계정 & 인증"])
def admin_toggle_status(req: ToggleStatusRequest, auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)):
    """관리자 권한으로 고객사 계정 상태 변경 (ACTIVE ↔ PAUSED)"""
    if auth_user.get("role") != "ADMIN":
        raise HTTPException(status_code=403, detail="슈퍼 관리자 권한이 필요합니다.")

    from core.database import execute
    sql = "UPDATE api_client_user SET status = %s WHERE client_id = %s"
    affected = execute(sql, (req.status.upper(), req.client_id))
    return {"success": True, "message": f"계정 상태가 {req.status.upper()}로 변경되었습니다.", "affected": affected}

# ================================================================================
# 1. 헬스 체크
# ================================================================================
@app.get("/health", summary="시스템 상태 점검", tags=["2. 고객사 쿼터 & 시스템 상태"])
def health_check():
    db_status = "OK"
    try:
        query_one("SELECT 1")
    except Exception as e:
        db_status = f"FAIL ({str(e)})"
        
    return {
        "status": "UP",
        "service": "pictro-api-service",
        "port": 49991,
        "engine_ready": engine is not None,
        "database_status": db_status
    }

# ================================================================================
# 헬퍼 함수: 입력 이미지 확보 (파일 업로드 or URL 다운로드)
# ================================================================================
def _resolve_input_image(work_dir: str, file: Optional[UploadFile] = None, image_url: Optional[str] = None) -> tuple:
    """업로드 파일 또는 이미지 URL을 로컬 워크스페이스에 저장하고 (경로, 바이트크기) 반환"""
    input_path = os.path.join(work_dir, "input_origin.jpg")
    file_bytes = 0

    if file and file.filename:
        with open(input_path, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)
        file_bytes = os.path.getsize(input_path)
    elif image_url and image_url.strip():
        req = urllib.request.Request(
            image_url.strip(),
            headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"}
        )
        with urllib.request.urlopen(req, timeout=10) as response, open(input_path, "wb") as out_file:
            shutil.copyfileobj(response, out_file)
        file_bytes = os.path.getsize(input_path)
    else:
        raise HTTPException(status_code=400, detail="Either 'file' or 'image_url' must be provided.")

    return input_path, file_bytes

# ================================================================================
# 2. OCR 단건 배너 정밀 분석 API (텍스트/시술명/바운딩박스 JSON 반환)
# ================================================================================
OCR_ANALYZE_DESC = """
### 🔍 배너 내 텍스트, 시술명, 가격, 사각 바운딩 박스 정밀 추출 API

---
#### 🐍 Python 호출 예제:
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/ocr/analyze"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

# 원격 이미지 분석 예제
data = {"image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg"}
res = requests.post(url, headers=headers, data=data)

result = res.json()
print(f"검출 개수: {result.get('detected_count')}개, 소요시간: {result.get('processing_time_ms')}ms")
for item in result.get("items", []):
    print(f"[{item['text']}] 신뢰도: {item['score']} 좌표: {item['box']}")
```
"""

@app.post("/api/v1/ocr/analyze", summary="배너 OCR 텍스트 및 시술 정보 정밀 분석 (JSON)", description=OCR_ANALYZE_DESC, tags=["1. 배너 변환 & OCR 파이프라인"])
async def ocr_analyze(
    request: Request,
    file: Optional[UploadFile] = File(None, description="직접 업로드할 배너 이미지 파일 (JPG, PNG, WebP)"),
    image_url: Optional[str] = Form(None, description="원격 배너 이미지 웹 URL (file 미지정 시 사용)", example="http://thrillrig.com:9990/pictro/banner5_orig.jpg"),
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    if not engine:
        raise HTTPException(status_code=503, detail="Pictro Engine not ready")

    start_time = time.time()
    req_id = str(uuid.uuid4())[:8]
    work_dir = os.path.join(UPLOAD_DIR, req_id)
    os.makedirs(work_dir, exist_ok=True)

    try:
        input_path, input_bytes = _resolve_input_image(work_dir, file, image_url)
        res = engine.process_enterprise(input_path, out_dir=work_dir, mode="detect")

        items = res.get("items", [])
        meta_file = res.get("meta_json_path") or os.path.join(work_dir, "input_origin_detect_meta.json")
        img_info = {}
        if os.path.exists(input_path):
            from PIL import Image as PILImage
            with PILImage.open(input_path) as pimg:
                img_info = {
                    "width": pimg.width,
                    "height": pimg.height,
                    "total_pixels": pimg.width * pimg.height
                }

        duration_ms = int((time.time() - start_time) * 1000)
        output_bytes = os.path.getsize(meta_file) if os.path.exists(meta_file) else 1024

        log_api_usage(
            client_id=auth_user["client_id"],
            key_id=auth_user["key_id"],
            endpoint="/api/v1/ocr/analyze",
            http_status=200,
            ip_address=request.client.host if request.client else "127.0.0.1",
            user_agent=request.headers.get("user-agent", ""),
            input_bytes=input_bytes,
            output_bytes=output_bytes,
            duration_ms=duration_ms,
            img_w=img_info.get("width", 0),
            img_h=img_info.get("height", 0),
            megapixel_count=round(img_info.get("total_pixels", 0) / 1000000.0, 2)
        )

        return {
            "success": True,
            "request_id": req_id,
            "processing_time_ms": duration_ms,
            "image_spec": img_info,
            "detected_count": len(items),
            "items": items
        }

    except HTTPException:
        raise
    except Exception as e:
        duration_ms = int((time.time() - start_time) * 1000)
        log_api_usage(
            client_id=auth_user["client_id"],
            key_id=auth_user["key_id"],
            endpoint="/api/v1/ocr/analyze",
            http_status=500,
            ip_address=request.client.host if request.client else "127.0.0.1",
            user_agent=request.headers.get("user-agent", ""),
            input_bytes=0,
            output_bytes=0,
            duration_ms=duration_ms
        )
        raise HTTPException(status_code=500, detail=str(e))

# ================================================================================
# 3. OCR 시각화 박스 이미지 생성 API (URL or Base64 반환)
# ================================================================================
OCR_VISUALIZE_DESC = """
### 🎨 검출된 텍스트 위치에 시각화 박스를 합성한 이미지 반환 API

---
#### 🐍 Python 호출 예제:
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/ocr/visualize"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}
data = {
    "image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg",
    "return_type": "url"  # url (다운로드 URL) 또는 base64 (Data URI)
}
res = requests.post(url, headers=headers, data=data)
print("시각화 이미지 URL:", res.json().get("visualized_image"))
```
"""

@app.post("/api/v1/ocr/visualize", summary="배너 OCR 검출 박스 시각화 이미지 생성 (URL / Base64)", description=OCR_VISUALIZE_DESC, tags=["1. 배너 변환 & OCR 파이프라인"])
async def ocr_visualize(
    request: Request,
    file: Optional[UploadFile] = File(None, description="직접 업로드할 배너 이미지 파일"),
    image_url: Optional[str] = Form(None, description="원격 배너 이미지 웹 URL (선택)", example="http://thrillrig.com:9990/pictro/banner5_orig.jpg"),
    return_type: str = Form("url", description="반환 포맷: url (이미지 다운로드 URL) 또는 base64 (Data URI 스트림)", example="url"),
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    if not engine:
        raise HTTPException(status_code=503, detail="Pictro Engine not ready")

    start_time = time.time()
    req_id = str(uuid.uuid4())[:8]
    work_dir = os.path.join(UPLOAD_DIR, req_id)
    os.makedirs(work_dir, exist_ok=True)

    try:
        input_path, input_bytes = _resolve_input_image(work_dir, file, image_url)
        res = engine.process_enterprise(input_path, out_dir=work_dir, mode="detect")

        vis_img_path = res.get("ocr_vis") or os.path.join(work_dir, "input_origin_ocr_detected.jpg")
        output_bytes = os.path.getsize(vis_img_path) if os.path.exists(vis_img_path) else 0

        duration_ms = int((time.time() - start_time) * 1000)

        vis_payload = f"/files/{req_id}/{os.path.basename(vis_img_path)}"
        if return_type.lower() == "base64" and os.path.exists(vis_img_path):
            with open(vis_img_path, "rb") as img_f:
                b64 = base64.b64encode(img_f.read()).decode("utf-8")
                vis_payload = f"data:image/jpeg;base64,{b64}"

        log_api_usage(
            client_id=auth_user["client_id"],
            key_id=auth_user["key_id"],
            endpoint="/api/v1/ocr/visualize",
            http_status=200,
            ip_address=request.client.host if request.client else "127.0.0.1",
            user_agent=request.headers.get("user-agent", ""),
            input_bytes=input_bytes,
            output_bytes=output_bytes,
            duration_ms=duration_ms
        )

        return {
            "success": True,
            "request_id": req_id,
            "processing_time_ms": duration_ms,
            "detected_count": res.get("items_count", 0),
            "return_type": return_type.lower(),
            "visualized_image": vis_payload
        }

    except HTTPException:
        raise
    except Exception as e:
        duration_ms = int((time.time() - start_time) * 1000)
        log_api_usage(
            client_id=auth_user["client_id"],
            key_id=auth_user["key_id"],
            endpoint="/api/v1/ocr/visualize",
            http_status=500,
            ip_address=request.client.host if request.client else "127.0.0.1",
            user_agent=request.headers.get("user-agent", ""),
            input_bytes=0,
            output_bytes=0,
            duration_ms=duration_ms
        )
        raise HTTPException(status_code=500, detail=str(e))

# ================================================================================
# 4. 픽트로 종합 파이프라인 API (detect / clear / trans 종합 모드)
# ================================================================================
PICTRO_PROCESS_DESC = """
### 🌐 엔진 3대 모드(`mode`) & 언어 옵션(`target_lang`) 종합 배너 변환 API

---
#### 🐍 Python 호출 예제 1: `trans` 풀코스 1:1 다국어 치환 모드 (기본값, 약 6초)
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/pictro/process"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

# 원격 웹 이미지 URL을 일본어로 치환하는 예제
data = {
    "image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg",
    "mode": "trans",       # 풀코스 치환 (탐색 -> 번역 -> 배경복원 -> 폰트합성)
    "target_lang": "ja"    # 언어 선택: en, ja, zh-CN, zh-TW, vi, th
}

res = requests.post(url, headers=headers, data=data)
data = res.json()
print("치환 완료 배너 URL:", data.get("translated_image_url"))
print("복원된 순수 배경 URL:", data.get("clean_bg_image_url"))
```

---
#### 🐍 Python 호출 예제 2: `clear` AI 배경 복원 모드 (글자 지우기, 약 4초)
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/pictro/process"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

# 로컬 이미지 파일 업로드하여 글자 지우기 예제
files = {"file": open("my_banner.jpg", "rb")}
data = {"mode": "clear"}  # AI Big-LaMa 배경 복원

res = requests.post(url, headers=headers, files=files, data=data)
print("글자 제거된 깨끗한 배경 URL:", res.json().get("clean_bg_image_url"))
```

---
#### 🐍 Python 호출 예제 3: `detect` OCR 탐색 모드 (초고속 영역/텍스트 추출, 약 1초)
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/pictro/process"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}
data = {
    "image_url": "http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg",
    "mode": "detect"  # 텍스트 영역 및 사각 바운딩 박스 검출
}

res = requests.post(url, headers=headers, data=data)
print("검출 박스 시각화 이미지 URL:", res.json().get("visualized_image_url"))
```
"""

@app.post("/api/v1/pictro/process", summary="다국어 배너 변환 종합 파이프라인 (detect/clear/trans)", description=PICTRO_PROCESS_DESC, tags=["1. 배너 변환 & OCR 파이프라인"])
async def pictro_process(
    request: Request,
    file: Optional[UploadFile] = File(None, description="직접 업로드할 배너 이미지 파일 (선택)"),
    image_url: Optional[str] = Form(None, description="원격 배너 이미지 웹 URL (선택)", example="http://thrillrig.com:9990/pictro/examples/banner5_orig.jpg"),
    mode: str = Form("trans", description="3대 동작 모드: detect (OCR 탐색, 약 1초) | clear (AI 배경 복원, 약 4초) | trans (풀코스 치환, 약 6초)", example="trans"),
    target_lang: str = Form("en", description="목표 언어 코드: en (영어) | ja (일본어) | zh-CN (중국어 간체) | zh-TW (중국어 번체) | vi (베트남어) | th (태국어)", example="ja"),
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    if not engine:
        raise HTTPException(status_code=503, detail="Pictro Engine not ready")

    start_time = time.time()
    req_id = str(uuid.uuid4())[:8]
    work_dir = os.path.join(UPLOAD_DIR, req_id)
    os.makedirs(work_dir, exist_ok=True)

    try:
        input_path, input_bytes = _resolve_input_image(work_dir, file, image_url)
        res = engine.process_enterprise(input_path, target_lang=target_lang, out_dir=work_dir, mode=mode)

        duration_ms = int((time.time() - start_time) * 1000)

        clean_bg_url = None
        trans_url = None
        vis_url = None

        if res.get("clean_bg") and os.path.exists(res["clean_bg"]):
            clean_bg_url = f"/files/{req_id}/{os.path.basename(res['clean_bg'])}"
        if res.get("translated_image") and os.path.exists(res["translated_image"]):
            trans_url = f"/files/{req_id}/{os.path.basename(res['translated_image'])}"
        if res.get("ocr_vis") and os.path.exists(res["ocr_vis"]):
            vis_url = f"/files/{req_id}/{os.path.basename(res['ocr_vis'])}"

        total_out_bytes = sum(
            os.path.getsize(res[k]) for k in ["clean_bg", "translated_image", "ocr_vis"]
            if res.get(k) and os.path.exists(res[k])
        )
        log_api_usage(
            client_id=auth_user["client_id"],
            key_id=auth_user["key_id"],
            endpoint="/api/v1/pictro/process",
            http_status=200,
            ip_address=request.client.host if request.client else "127.0.0.1",
            user_agent=request.headers.get("user-agent", ""),
            input_bytes=input_bytes,
            output_bytes=total_out_bytes,
            duration_ms=duration_ms
        )

        # 구독자 보관함(user_ocr_history) 자동 적재 및 스토리지 용량 동기화
        try:
            from core.database import execute
            orig_url = f"/files/{req_id}/{os.path.basename(input_path)}"
            items_list = res.get("items", [])
            text_summary = " ".join([it.get("text", "") for it in items_list[:5]])
            parsed_json_str = json.dumps(items_list, ensure_ascii=False)
            
            visual_url = vis_url or orig_url
            insert_history_sql = """
                INSERT INTO user_ocr_history
                (client_id, original_webp_path, thumb_webp_path, visual_webp_path, clean_bg_webp_path, 
                 translated_webp_path, image_size_bytes, target_lang, parsed_text_summary, 
                 parsed_json, translated_json, is_deleted)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 0)
            """
            execute(insert_history_sql, (
                auth_user["client_id"],
                orig_url,
                trans_url or orig_url,
                visual_url,
                clean_bg_url,
                trans_url,
                input_bytes + total_out_bytes,
                target_lang,
                text_summary[:1000] if text_summary else "배너 이미지",
                parsed_json_str,
                json.dumps([{"translated_text": it.get("translated_text", "")} for it in items_list], ensure_ascii=False)
            ))

            # 스토리지 누적 용량 갱신
            update_storage_sql = """
                UPDATE api_client_user
                SET current_storage_bytes = current_storage_bytes + %s
                WHERE client_id = %s
            """
            execute(update_storage_sql, (input_bytes + total_out_bytes, auth_user["client_id"]))
        except Exception as he:
            print(f"[Pictro Gallery] History auto-save notice: {he}")

        return {
            "success": True,
            "request_id": req_id,
            "mode": mode,
            "target_lang": target_lang,
            "processing_time_ms": duration_ms,
            "detected_count": res.get("items_count", 0),
            "original_image_url": f"/files/{req_id}/{os.path.basename(input_path)}",
            "visualized_image_url": vis_url,
            "clean_bg_image_url": clean_bg_url,
            "translated_image_url": trans_url
        }

    except HTTPException:
        raise
    except Exception as e:
        duration_ms = int((time.time() - start_time) * 1000)
        log_api_usage(
            client_id=auth_user["client_id"],
            key_id=auth_user["key_id"],
            endpoint="/api/v1/pictro/process",
            http_status=500,
            ip_address=request.client.host if request.client else "127.0.0.1",
            user_agent=request.headers.get("user-agent", ""),
            input_bytes=0,
            output_bytes=0,
            duration_ms=duration_ms
        )
        raise HTTPException(status_code=500, detail=str(e))

# ================================================================================
# 5. 고객사 사용량 및 잔여 쿼터 조회 API
# ================================================================================
CLIENT_USAGE_DESC = """
### 📊 고객사 당월 트래픽, 스토리지 용량, 일일 잔여 호출 쿼터 실시간 조회 API

---
#### 🐍 Python 호출 예제:
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/client/usage"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}

res = requests.get(url, headers=headers)
data = res.json()
print("고객사:", data.get("company_name"))
print("적용 플랜:", data.get("plan_name"))
print(f"오늘 호출: {data['daily_calls']['today_calls']} / {data['daily_calls']['daily_quota']}회 (잔여: {data['daily_calls']['remaining_calls']}회)")
print(f"당월 트래픽: {data['monthly_traffic']['used_bytes']} 바이트 사용 중")
```
"""

@app.get("/api/v1/client/usage", summary="고객사 당월 트래픽 및 사용량 잔여 쿼터 조회", description=CLIENT_USAGE_DESC, tags=["2. 고객사 쿼터 & 시스템 상태"])
def get_client_usage(auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)):
    client_id = auth_user["client_id"]
    sql = """
        SELECT 
            u.client_id, u.company_name, u.plan_id, p.plan_name,
            u.current_storage_bytes, p.max_storage_bytes,
            u.monthly_used_traffic_bytes, p.monthly_included_volume_bytes,
            u.hard_limit_bytes, u.status, COALESCE(p.daily_quota, 10) AS daily_quota
        FROM `api_client_user` u
        INNER JOIN `api_plan_tier` p ON u.plan_id = p.plan_id
        WHERE u.client_id = %s;
    """
    user_info = query_one(sql, (client_id,))
    if not user_info:
        raise HTTPException(status_code=404, detail="Client not found")

    storage_pct = round((user_info["current_storage_bytes"] / max(1, user_info["max_storage_bytes"])) * 100, 2)
    traffic_pct = round((user_info["monthly_used_traffic_bytes"] / max(1, user_info["monthly_included_volume_bytes"])) * 100, 2)
    daily_quota = user_info["daily_quota"]
    today_calls = auth_user.get("today_calls", 0)
    remaining_calls = max(0, daily_quota - today_calls)

    return {
        "success": True,
        "client_id": user_info["client_id"],
        "company_name": user_info["company_name"],
        "plan_id": user_info["plan_id"],
        "plan_name": user_info["plan_name"],
        "daily_calls": {
            "today_calls": today_calls,
            "daily_quota": daily_quota,
            "remaining_calls": remaining_calls
        },
        "storage": {
            "used_bytes": user_info["current_storage_bytes"],
            "max_bytes": user_info["max_storage_bytes"],
            "usage_percent": storage_pct
        },
        "monthly_traffic": {
            "used_bytes": user_info["monthly_used_traffic_bytes"],
            "included_bytes": user_info["monthly_included_volume_bytes"],
            "hard_limit_bytes": user_info["hard_limit_bytes"],
            "usage_percent": traffic_pct
        }
    }

# ================================================================================
# 6. 사용자 교정 용어사전(Glossary) 문맥 벡터 RAG API
# ================================================================================
@app.post("/api/v1/glossary/save", summary="사용자 교정 용어사전 및 문맥 벡터 자동 저장", tags=["4. 사용자 용어사전 & RAG"])
def save_glossary_term(
    source_term: str = Form(..., description="원문 한국어 단어/문구"),
    corrected_term: str = Form(..., description="사용자가 수정한 번역어"),
    target_lang: str = Form("en", description="타겟 언어 코드 (en, ja, zh_CN, vi)"),
    category: str = Form("COMMON", description="시술/의료 분야"),
    context_text: Optional[str] = Form(None, description="해당 배너의 주변 문맥 전체 문장"),
    client_id: Optional[str] = Form(None, description="고객사 ID (NULL일 경우 전체 공용)")
):
    from core.database import execute
    from engine_llm.glossary_vector_manager import GlossaryVectorManager

    ctx_text = context_text or source_term
    ctx_hash = GlossaryVectorManager.calculate_hash(ctx_text)
    ctx_vector = GlossaryVectorManager.get_embedding(ctx_text)
    ctx_vector_json = json.dumps(ctx_vector)

    sql = """
        INSERT INTO translation_glossary_db
        (target_lang, client_id, category, source_term, corrected_term, context_text, context_vector, context_hash, use_count, is_confirmed)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1, 1)
        ON DUPLICATE KEY UPDATE
            corrected_term = VALUES(corrected_term),
            context_text = VALUES(context_text),
            context_vector = VALUES(context_vector),
            context_hash = VALUES(context_hash),
            use_count = use_count + 1,
            updated_at = NOW();
    """
    try:
        execute(sql, (target_lang, client_id, category, source_term, corrected_term, ctx_text, ctx_vector_json, ctx_hash))
        return {
            "success": True,
            "message": "사용자 교정 단어 및 문맥 벡터가 성공적으로 등록되었습니다.",
            "data": {
                "source_term": source_term,
                "corrected_term": corrected_term,
                "target_lang": target_lang,
                "context_hash": ctx_hash
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"용어사전 저장 실패: {str(e)}")


@app.get("/api/v1/glossary/list", summary="등록된 사용자 교정 용어사전 조회", tags=["4. 사용자 용어사전 & RAG"])
def list_glossary_terms(target_lang: str = "en", limit: int = 50):
    from core.database import query_all
    sql = """
        SELECT glossary_id, target_lang, category, source_term, corrected_term, context_text, use_count, updated_at
        FROM translation_glossary_db
        WHERE target_lang = %s AND is_confirmed = 1
        ORDER BY use_count DESC, updated_at DESC
        LIMIT %s
    """
    try:
        rows = query_all(sql, (target_lang, limit))
        return {"success": True, "count": len(rows), "items": rows}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"용어사전 조회 실패: {str(e)}")


# ================================================================================
# 8. 구독자 포털 My Gallery & 스토리지 대시보드 API
# ================================================================================
@app.get("/api/v1/gallery/dashboard", summary="스토리지 사용량 및 배너 통계 대시보드 조회", tags=["5. 구독자 갤러리 & 대시보드"])
def get_gallery_dashboard(
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    """구독자의 스토리지 용량, 플랜 정보, 정상 보관 배너 수, 휴지통 배너 수 반환"""
    client_id = auth_user["client_id"]
    from core.database import query_one

    # 1. 고객 플랜 및 스토리지 정보
    client_sql = """
        SELECT u.client_id, u.company_name, u.current_storage_bytes,
               u.monthly_used_traffic_bytes,
               p.plan_id, p.plan_name, p.max_storage_bytes, p.monthly_included_volume_bytes
        FROM api_client_user u
        JOIN api_plan_tier p ON u.plan_id = p.plan_id
        WHERE u.client_id = %s
    """
    user_info = query_one(client_sql, (client_id,))
    if not user_info:
        raise HTTPException(status_code=404, detail="고객사 정보를 찾을 수 없습니다.")

    # 2. 배너 보관 수 통계
    count_sql = """
        SELECT 
            COUNT(CASE WHEN is_deleted = 0 THEN 1 END) AS active_count,
            COUNT(CASE WHEN is_deleted = 1 THEN 1 END) AS trash_count,
            COALESCE(SUM(CASE WHEN is_deleted = 0 THEN image_size_bytes ELSE 0 END), 0) AS active_bytes
        FROM user_ocr_history
        WHERE client_id = %s
    """
    stats = query_one(count_sql, (client_id,)) or {"active_count": 0, "trash_count": 0, "active_bytes": 0}

    used_bytes = user_info["current_storage_bytes"]
    max_bytes = max(1, user_info["max_storage_bytes"])
    storage_pct = min(100.0, round((used_bytes / max_bytes) * 100, 1))

    return {
        "success": True,
        "client_id": client_id,
        "company_name": user_info["company_name"],
        "plan_name": user_info["plan_name"],
        "storage": {
            "used_bytes": used_bytes,
            "max_bytes": max_bytes,
            "usage_percent": storage_pct,
            "used_human": f"{round(used_bytes / (1024*1024), 1)} MB" if used_bytes < 1024*1024*1024 else f"{round(used_bytes / (1024*1024*1024), 2)} GB",
            "max_human": f"{round(max_bytes / (1024*1024*1024), 1)} GB",
            "is_warning": storage_pct >= 85.0,
            "is_full": storage_pct >= 100.0
        },
        "stats": {
            "active_count": stats.get("active_count", 0),
            "trash_count": stats.get("trash_count", 0)
        }
    }


@app.get("/api/v1/gallery/items", summary="보관된 배너 목록 조회 (정상/휴지통/검색)", tags=["5. 구독자 갤러리 & 대시보드"])
def list_gallery_items(
    is_trash: bool = False,
    folder_id: Optional[int] = None,
    keyword: Optional[str] = None,
    limit: int = 60,
    offset: int = 0,
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    """보관함 또는 휴지통 배너 목록 페이징 조회"""
    client_id = auth_user["client_id"]
    from core.database import query_all

    where_clauses = ["client_id = %s", "is_deleted = %s"]
    params = [client_id, 1 if is_trash else 0]

    if folder_id is not None:
        where_clauses.append("folder_id = %s")
        params.append(folder_id)

    if keyword:
        where_clauses.append("(parsed_text_summary LIKE %s OR original_webp_path LIKE %s)")
        kw_like = f"%{keyword}%"
        params.extend([kw_like, kw_like])

    sql = f"""
        SELECT history_id, folder_id, original_webp_path, thumb_webp_path, 
               visual_webp_path, clean_bg_webp_path, translated_webp_path, image_size_bytes,
               width, height, detected_font_color, target_lang, parsed_json,
               parsed_text_summary, is_deleted, deleted_at, purge_due_date, created_at
        FROM user_ocr_history
        WHERE {" AND ".join(where_clauses)}
        ORDER BY created_at DESC
        LIMIT %s OFFSET %s
    """
    params.extend([limit, offset])

    try:
        items = query_all(sql, tuple(params))
        return {
            "success": True,
            "count": len(items),
            "is_trash": is_trash,
            "items": items
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"갤러리 목록 조회 실패: {str(e)}")


@app.post("/api/v1/gallery/trash", summary="배너 휴지통으로 이동 (30일 유예)", tags=["5. 구독자 갤러리 & 대시보드"])
def move_to_trash(
    history_ids: str = Form(..., description="휴지통으로 보낼 history_id 목록 (쉼표 구분)"),
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    """배너를 휴지통으로 이동 (purge_due_date = NOW() + 30일 설정)"""
    client_id = auth_user["client_id"]
    from core.database import execute
    raw_ids = [x.strip() for x in str(history_ids).replace("[", "").replace("]", "").split(",") if x.strip()]
    if not raw_ids:
        return {"success": False, "message": "선택된 배너가 없습니다."}

    fmt_ids = ",".join(raw_ids)
    sql = f"""
        UPDATE user_ocr_history
        SET is_deleted = 1,
            deleted_at = NOW(),
            purge_due_date = DATE_ADD(NOW(), INTERVAL 30 DAY)
        WHERE client_id = %s AND history_id IN ({fmt_ids})
    """
    try:
        execute(sql, (client_id,))
        return {"success": True, "message": f"{len(raw_ids)}개 배너가 휴지통으로 이동되었습니다. (30일간 보관)"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"휴지통 이동 실패: {str(e)}")


@app.post("/api/v1/gallery/restore", summary="휴지통에서 배너 원클릭 복구", tags=["5. 구독자 갤러리 & 대시보드"])
def restore_from_trash(
    history_ids: str = Form(..., description="복구할 history_id 목록 (쉼표 구분)"),
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    """휴지통의 배너를 정상 보관함으로 복원"""
    client_id = auth_user["client_id"]
    from core.database import execute
    raw_ids = [x.strip() for x in str(history_ids).replace("[", "").replace("]", "").split(",") if x.strip()]
    if not raw_ids:
        return {"success": False, "message": "선택된 배너가 없습니다."}

    fmt_ids = ",".join(raw_ids)
    sql = f"""
        UPDATE user_ocr_history
        SET is_deleted = 0,
            deleted_at = NULL,
            purge_due_date = NULL
        WHERE client_id = %s AND history_id IN ({fmt_ids})
    """
    try:
        execute(sql, (client_id,))
        return {"success": True, "message": f"{len(raw_ids)}개 배너가 성공적으로 복구되었습니다."}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"배너 복구 실패: {str(e)}")


@app.delete("/api/v1/gallery/purge", summary="휴지통 배너 영구 삭제 (스토리지 즉시 반환)", tags=["5. 구독자 갤러리 & 대시보드"])
def purge_trash_items(
    history_ids: str = Form(..., description="영구 삭제할 history_id 목록 (쉼표 구분)"),
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    """휴지통에서 선택한 배너를 DB 및 디스크에서 영구 삭제하고 스토리지 즉시 차감 반환"""
    client_id = auth_user["client_id"]
    from core.database import query_all, execute
    raw_ids = [x.strip() for x in str(history_ids).replace("[", "").replace("]", "").split(",") if x.strip()]
    if not raw_ids:
        return {"success": False, "message": "선택된 배너가 없습니다."}

    fmt_ids = ",".join(raw_ids)
    # 1. 삭제할 파일 경로 및 크기 합계 조회
    find_sql = f"""
        SELECT history_id, original_webp_path, clean_bg_webp_path, translated_webp_path, image_size_bytes
        FROM user_ocr_history
        WHERE client_id = %s AND is_deleted = 1 AND history_id IN ({fmt_ids})
    """
    targets = query_all(find_sql, (client_id,))
    total_freed_bytes = sum(t.get("image_size_bytes", 0) for t in targets)

    # 2. DB 레코드 삭제
    del_sql = f"DELETE FROM user_ocr_history WHERE client_id = %s AND history_id IN ({fmt_ids})"
    execute(del_sql, (client_id,))

    # 3. 스토리지 용량 즉시 복원 차감
    if total_freed_bytes > 0:
        quota_sql = """
            UPDATE api_client_user
            SET current_storage_bytes = GREATEST(0, CAST(current_storage_bytes AS SIGNED) - %s)
            WHERE client_id = %s
        """
        execute(quota_sql, (total_freed_bytes, client_id))

    return {
        "success": True,
        "message": f"{len(targets)}개 배너가 영구 삭제되었으며, {round(total_freed_bytes/1024, 1)} KB 용량이 복원되었습니다."
    }


@app.get("/api/v1/gallery/export", summary="배너 메타데이터 엑셀(CSV) 일괄 다운로드", tags=["5. 구독자 갤러리 & 대시보드"])
def export_gallery_data(
    is_trash: bool = False,
    auth_user: Dict[str, Any] = Depends(APIKeyValidator.validate_key)
):
    """현재 보관된 배너 분석 및 번역 메타데이터를 엑셀용 CSV 바이너리로 스트리밍 다운로드"""
    from fastapi.responses import Response
    from core.database import query_all
    from gallery_exporter import export_gallery_to_csv_bytes

    client_id = auth_user["client_id"]
    sql = """
        SELECT history_id, folder_id, original_webp_path, target_lang,
               detected_font_color, parsed_text_summary, parsed_json, translated_json,
               image_size_bytes, is_deleted, created_at
        FROM user_ocr_history
        WHERE client_id = %s AND is_deleted = %s
        ORDER BY created_at DESC
        LIMIT 500
    """
    rows = query_all(sql, (client_id, 1 if is_trash else 0))
    csv_bytes = export_gallery_to_csv_bytes(rows)

    filename = f"pictro_banners_{'trash' if is_trash else 'active'}_{int(time.time())}.csv"
    return Response(
        content=csv_bytes,
        media_type="text/csv",
        headers={"Content-Disposition": f"attachment; filename={filename}"}
    )


# ================================================================================
# 7. 정적 결과 파일 서빙
# ================================================================================
@app.get("/files/{req_id}/{filename}", summary="변환 완료 이미지 파일 다운로드", tags=["1. 배너 변환 & OCR 파이프라인"])
def get_file(req_id: str, filename: str):
    file_path = os.path.join(UPLOAD_DIR, req_id, filename)
    if os.path.exists(file_path):
        media_type = "image/webp" if filename.endswith(".webp") else "image/jpeg"
        return FileResponse(file_path, media_type=media_type)
    raise HTTPException(status_code=404, detail="File not found")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=49991, reload=False)
