# 픽트로 (Pictro) 상용 엔터프라이즈 REST API 연동 가이드

**버전**: v1.0.0 Commercial  
**최종 업데이트**: 2026-09-29  
**기본 엔드포인트 Base URL**: `http://thrillrig.com:9990/pictro-api`  
**대화형 테스트 Swagger UI**: `http://thrillrig.com:9990/pictro-api/docs`  
**웹 가이드 URL**: `http://thrillrig.com:9990/pictro-api/guide`  

---

## 1. 인증 및 보안 (Authentication)

모든 API 호출 시 HTTP 요청 헤더에 발급받은 API 키를 전달해야 합니다.  
허가되지 않은 키 또는 비활성화된 계정의 요청은 **HTTP 403 Forbidden**으로 즉시 차단됩니다.

```http
X-API-Key: FOXUNNI-PARTNER-MASTER-KEY-2026
```

* **테스트용 마스터 키**: `FOXUNNI-PARTNER-MASTER-KEY-2026`
* **플랜 규격**: Enterprise (월 1TB 변환 트래픽, 분당 600회 호출 허용)
* **자동 미터링**: 호출마다 요청 바이트(Input)와 응답 바이트(Output), 소요 시간이 DB에 실시간 감사 로깅되며 월간 트래픽이 자동 누적됩니다.

---

## 2. 엔진 3대 동작 모드 (`mode`) 옵션 상세 안내

`POST /api/v1/pictro/process` 호출 시 업무 목적에 맞게 `mode` 옵션을 선택할 수 있습니다.

| 모드 옵션 (`mode`) | 수행 단계 | 소요 시간 | 주 사용 시나리오 및 산출물 |
| :--- | :--- | :---: | :--- |
| **`detect`**<br>(OCR 탐색) | **[1단계]** PaddleOCR 텍스트 영역 및 문구 검출 | **약 1.0초** | • 원본 배너에서 시술명, 가격, 조건, 바운딩 박스(좌표)만 빠르게 JSON으로 추출할 때<br>• 산출물: `ocr_vis` (검출 박스 표시 이미지), `items` (JSON) |
| **`clear`**<br>(배경 복원) | **[1+3단계]** 글자 영역 마스킹 후 AI LaMa 배경 복원 | **약 4.0초** | • 배너의 글자만 깨끗이 지우고 배경 템플릿 이미지만 확보하여 재활용할 때<br>• 산출물: `clean_bg` (글자 흔적 없이 복원된 배경 이미지) |
| **`trans`** *(기본값)*<br>(풀코스 치환) | **[1~4단계]** 검출 ➔ AI 번역 ➔ 배경 복원 ➔ 1:1 폰트 치환 합성 | **약 6.0초** | • 원본 배너를 영어/일본어/중국어 등의 현지 언어로 1:1 완벽 치환한 완성형 배너를 얻을 때<br>• 산출물: `clean_bg` + `translated_image` (완성본) + 상세 메타데이터 |

---

## 3. 지원 언어 코드 (`target_lang`)

| 언어 코드 | 언어명 | 기본 적용 시스템 폰트 | 비고 |
| :---: | :---: | :---: | :--- |
| **`en`** *(기본값)* | 영어 (English) | DejaVu Sans / Malgun Gothic | 영문 장평(Horizontal Scale) 자동 조절 적용 |
| **`ja`** | 일본어 (Japanese) | Noto Sans CJK JP / TakaoGothic | 일본 엔화(¥) 및 가나 표기 최적화 |
| **`zh-CN`** | 중국어 간체 (Simplified) | Noto Sans CJK SC | 위안화(¥) 및 간체자 타이포 적용 |
| **`zh-TW`** | 중국어 번체 (Traditional) | Noto Sans CJK TC | 번체자 및 대만 달러(NT$) 표기 |
| **`vi`** | 베트남어 (Vietnamese) | DejaVu Sans / Noto Sans | 동(VND) 및 성조 부호 완벽 지원 |
| **`th`** | 태국어 (Thai) | Garuda / Loma / Noto Sans Thai | 바트(฿) 및 상/하 모음 결합 처리 |

---

## 4. 핵심 엔드포인트 명세 및 호출 예제 (Examples)

### 4.1 배너 OCR 텍스트 정밀 분석 (`POST /api/v1/ocr/analyze`)
배너 이미지에서 텍스트 문구, 바운딩 박스 좌표, 인식 신뢰도(0.0~1.0)를 JSON으로 추출합니다.

* **Content-Type**: `multipart/form-data`
* **요청 파라미터 (둘 중 하나 필수)**:
  - `file`: 분석할 이미지 파일 (JPG, PNG, WebP)
  - `image_url`: 분석할 원격 웹 이미지 URL (예: `https://example.com/banner.jpg`)

#### cURL 호출 예제:
```bash
curl -X POST "http://thrillrig.com:9990/pictro-api/api/v1/ocr/analyze" \
  -H "X-API-Key: FOXUNNI-PARTNER-MASTER-KEY-2026" \
  -F "file=@banner_sample.jpg"
```

#### 응답 예제 (JSON):
```json
{
  "success": true,
  "request_id": "ec5a6da7",
  "processing_time_ms": 1073,
  "image_spec": {
    "width": 800,
    "height": 800,
    "total_pixels": 640000
  },
  "detected_count": 2,
  "items": [
    {
      "id": 0,
      "text": "슈링크 유니버스 300샷",
      "score": 0.9955,
      "box": [290, 51, 323, 259],
      "polygon": [[51.0, 290.0], [259.0, 290.0], [259.0, 324.0], [51.0, 324.0]]
    },
    {
      "id": 1,
      "text": "99,000원",
      "score": 0.9998,
      "box": [330, 50, 370, 180],
      "polygon": [[50.0, 330.0], [180.0, 330.0], [180.0, 370.0], [50.0, 370.0]]
    }
  ]
}
```

---

### 4.2 배너 OCR 박스 시각화 이미지 생성 (`POST /api/v1/ocr/visualize`)
검출된 글자 위치를 직관적인 컬러 사각형 박스로 표시한 이미지를 반환합니다. (외부 유료 서비스 제공용)

* **Content-Type**: `multipart/form-data`
* **요청 파라미터**:
  - `file` or `image_url`: 이미지 파일 또는 웹 URL
  - `return_type`: 반환 형태 (`url`: 이미지 다운로드 URL / `base64`: Data URI Base64 스트림)

#### Python 호출 예제:
```python
import requests

url = "http://thrillrig.com:9990/pictro-api/api/v1/ocr/visualize"
headers = {"X-API-Key": "FOXUNNI-PARTNER-MASTER-KEY-2026"}
files = {"file": open("my_banner.jpg", "rb")}
data = {"return_type": "url"}

response = requests.post(url, headers=headers, files=files, data=data)
print(response.json())
```

#### 응답 예제 (JSON):
```json
{
  "success": true,
  "request_id": "f9afdbdb",
  "processing_time_ms": 715,
  "detected_count": 13,
  "return_type": "url",
  "visualized_image": "/files/f9afdbdb/input_origin_ocr_detected.jpg"
}
```

---

### 4.3 다국어 배너 변환 종합 파이프라인 (`POST /api/v1/pictro/process`)
탐색(detect), 배경 복원(clear), 다국어 폰트 치환(trans) 3대 모드를 종합 지원하는 최상위 엔드포인트입니다.

* **Content-Type**: `multipart/form-data`
* **요청 파라미터**:
  - `file` (UploadFile, 선택): 직접 업로드 파일
  - `image_url` (String, 선택): 원격 웹 이미지 URL
  - `mode` (String, 기본값: `trans`): `detect` | `clear` | `trans`
  - `target_lang` (String, 기본값: `en`): `en` | `ja` | `zh-CN` | `zh-TW` | `vi` | `th`

#### Node.js / JavaScript (Fetch) 호출 예제:
```javascript
const formData = new FormData();
formData.append('image_url', 'http://thrillrig.com:9990/pictro/banner5_orig.jpg');
formData.append('mode', 'trans');
formData.append('target_lang', 'ja'); // 일본어로 치환

const res = await fetch('http://thrillrig.com:9990/pictro-api/api/v1/pictro/process', {
  method: 'POST',
  headers: {
    'X-API-Key': 'FOXUNNI-PARTNER-MASTER-KEY-2026'
  },
  body: formData
});
const data = await res.json();
console.log('치환 완료 이미지:', data.translated_image_url);
console.log('순수 배경 복원 이미지:', data.clean_bg_image_url);
```

#### 응답 예제 (JSON):
```json
{
  "success": true,
  "request_id": "194dbca0",
  "mode": "trans",
  "target_lang": "ja",
  "processing_time_ms": 5830,
  "detected_count": 13,
  "original_image_url": "/files/194dbca0/input_origin.jpg",
  "visualized_image_url": "/files/194dbca0/input_origin_ocr_detected.jpg",
  "clean_bg_image_url": "/files/194dbca0/input_origin_clean_bg.jpg",
  "translated_image_url": "/files/194dbca0/input_origin_translated_ja.jpg"
}
```

---

### 4.4 고객사 사용량 및 잔여 쿼터 실시간 조회 (`GET /api/v1/client/usage`)
현재 API Key 소유 고객사의 당월 트래픽 사용량, 기본 제공 용량, 하드 리밋, 스토리지 사용량을 실시간 조회합니다.

#### cURL 호출 예제:
```bash
curl -X GET "http://thrillrig.com:9990/pictro-api/api/v1/client/usage" \
  -H "X-API-Key: FOXUNNI-PARTNER-MASTER-KEY-2026"
```

#### 응답 예제 (JSON):
```json
{
  "success": true,
  "client_id": "client_foxunni_master",
  "company_name": "여우언니 (FoxUnni Master)",
  "plan_id": "ENTERPRISE",
  "plan_name": "Enterprise (100GB) - 대형 에이전시",
  "storage": {
    "used_bytes": 0,
    "max_bytes": 107374182400,
    "usage_percent": 0.0
  },
  "monthly_traffic": {
    "used_bytes": 684062,
    "included_bytes": 1073741824000,
    "hard_limit_bytes": 1099511627776,
    "usage_percent": 0.06
  }
}
```

---

## 5. HTTP 상태 코드 및 오류 정의

| HTTP Code | 에러 코드 | 설명 및 대응 방법 |
| :---: | :--- | :--- |
| **`200`** | `SUCCESS` | 요청 성공 및 결과 데이터 정상 반환 |
| **`400`** | `BAD_REQUEST` | `file` 또는 `image_url` 파라미터가 누락되었거나 이미지 다운로드 실패 |
| **`401`** | `AUTH_REQUIRED` | 요청 헤더에 `X-API-Key`가 포함되지 않음 |
| **`403`** | `INVALID_KEY` | 존재하지 않거나 비활성화(정지/만료)된 API 키 |
| **`403`** | `IP_NOT_ALLOWED` | API 키의 IP 화이트리스트에 등록되지 않은 IP에서의 접근 |
| **`429`** | `QUOTA_EXCEEDED` | 당월 하드 리밋(트래픽 상한선) 초과 또는 분당 요청 건수 초과 |
| **`500`** | `INTERNAL_ERROR` | OCR 엔진 또는 GPU 번역 파이프라인 처리 중 예외 발생 |
