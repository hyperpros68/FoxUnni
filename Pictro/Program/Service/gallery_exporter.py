# -*- coding: utf-8 -*-
"""
Pictro Gallery Exporter
배너 OCR 분석 및 번역 결과 데이터를 엑셀(XLSX/CSV)로 변환 내보내기 전담 모듈
"""
import io
import json
import csv
from typing import List, Dict, Any

def export_gallery_to_csv_bytes(items: List[Dict[str, Any]]) -> bytes:
    """배너 갤러리 메타데이터를 UTF-8-BOM CSV 바이너리로 변환 (Excel에서 한글 완벽 호환)"""
    output = io.StringIO()
    # 엑셀 한글 깨짐 방지 BOM
    output.write('\ufeff')
    
    writer = csv.writer(output, quoting=csv.QUOTE_MINIMAL)
    # 헤더
    writer.writerow([
        "번호", "히스토리ID", "업로드일시", "타겟언어", "텍스트요약", 
        "검출텍스트목록", "번역텍스트목록", "이미지용량(KB)", "대표글자색", "상태"
    ])

    for i, it in enumerate(items, 1):
        parsed = it.get("parsed_json")
        if isinstance(parsed, str):
            try:
                parsed = json.loads(parsed)
            except Exception:
                parsed = []
        parsed_texts = " | ".join([p.get("text", "") for p in parsed if isinstance(p, dict)]) if isinstance(parsed, list) else ""

        trans = it.get("translated_json")
        if isinstance(trans, str):
            try:
                trans = json.loads(trans)
            except Exception:
                trans = []
        trans_texts = " | ".join([t.get("translated_text", "") for t in trans if isinstance(t, dict)]) if isinstance(trans, list) else ""

        kb_size = round((it.get("image_size_bytes") or 0) / 1024, 1)
        status_str = "휴지통" if it.get("is_deleted") else "정상"

        writer.writerow([
            i,
            it.get("history_id", ""),
            str(it.get("created_at", "")),
            it.get("target_lang", ""),
            it.get("parsed_text_summary", ""),
            parsed_texts,
            trans_texts,
            kb_size,
            it.get("detected_font_color", ""),
            status_str
        ])

    return output.getvalue().encode('utf-8-sig')
