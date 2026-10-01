import os
import re
import json
import urllib.request
import threading
from typing import List, Dict, Any, Optional

# ESG 4090 GPU 바카라 시스템 보호용 글로벌 번역 락 (동시 요청 1개 엄격 제한)
_GPU_TRANSLATION_LOCK = threading.Lock()

class DeepSeekTranslator:
    """
    [Pictro Enterprise Dual-LLM Translation Engine]
    1순위 (Primary): RTX 4090 초고속 GPU 서버 (125.129.99.198:11434, Qwen2.5-14B) - 0.5초 응답
    2순위 (Fallback): Mika 로컬 서버 (127.0.0.1:39990, DeepSeek-R1-8B) - 무중단 안전망
    """
    def __init__(
        self,
        primary_endpoint: str = "http://125.129.99.198:11434/api/generate",
        primary_model: str = "qwen2.5:14b",
        fallback_endpoint: str = "http://127.0.0.1:39990/v1/chat/completions",
        fallback_api_key: str = "tr-llm-key-2026-thrillrig",
        fallback_model: str = "deepseek-r1:8b"
    ):
        self.primary_endpoint = primary_endpoint
        self.primary_model = primary_model
        self.fallback_endpoint = fallback_endpoint
        self.fallback_api_key = fallback_api_key
        self.fallback_model = fallback_model


    def _clean_think_tags(self, text: str) -> str:
        """DeepSeek-R1의 <think>...</think> 추론 태그 제거"""
        cleaned = re.sub(r"<think>.*?</think>", "", text, flags=re.DOTALL)
        return cleaned.strip()

    def translate_texts(
        self,
        texts: List[str],
        target_lang: str = "en",
        category: str = "aesthetic_surgery",
        glossary_rules: Optional[List[Dict[str, Any]]] = None
    ) -> List[str]:
        """
        입력된 다국어 텍스트 리스트를 지정된 목표 언어로 초고속 번역
        - glossary_rules: 사용자 교정 용어사전 및 문맥 벡터 매칭 규칙 리스트 (동적 Few-Shot 주입)
        """
        if not texts:
            return []

        lang_map = {
            "en": "English",
            "ja": "Japanese",
            "zh": "Simplified Chinese",
            "vi": "Vietnamese",
            "ko": "Korean"
        }
        lang_name = lang_map.get(target_lang.lower(), "English")

        input_dict = {str(i): t for i, t in enumerate(texts)}
        dict_json_str = json.dumps(input_dict, ensure_ascii=False, indent=2)

        # 사용자 교정 문맥 용어사전 프롬프트 블록 구성
        glossary_prompt_section = ""
        if glossary_rules:
            rule_lines = []
            for r in glossary_rules[:8]:  # 상위 8개 가장 관련성 높은 문맥만 안전 주입
                src = r.get("source_term", "")
                corr = r.get("corrected_term", "")
                if src and corr:
                    rule_lines.append(f'   - "{src}" -> "{corr}" (STRICT)')
            if rule_lines:
                glossary_prompt_section = "\nUSER-VERIFIED CUSTOM GLOSSARY (HIGHEST PRIORITY - YOU MUST USE THESE EXACT TERMS):\n" + "\n".join(rule_lines) + "\n"

        def post_process_translation(orig_t: str, trans_t: str) -> str:
            t = re.sub(r"\(.*?\)", "", trans_t).strip()
            t = re.sub(r"\[.*?\]", "", t).strip()
            orig_pure_num = re.sub(r"[^0-9]", "", orig_t)
            if orig_pure_num and len(orig_t.strip()) <= 6:
                return orig_t.strip()
            return t if t else orig_t

        # 1. RTX 4090 초고속 Qwen2.5-14B 엔진 시도
        try:
            with _GPU_TRANSLATION_LOCK:
                prompt_4090 = f"""You are an expert beauty & aesthetic advertising localization copywriter.
Translate each text item in the following JSON dictionary into concise, punchy, premium {lang_name} banner copies.
{glossary_prompt_section}
STRICT RULES:
1. Translate item by item. Keep the EXACT same keys ("0", "1", "2", ...).
2. Keep translations SHORT and PUNCHY to fit tight banner buttons (1~2 words max per item):
   - "추가비용" -> "No Extra"
   - "없는!" -> "Fee!"
   - "VAT포함" -> "VAT Incl."
   - "코성형" -> "Rhinoplasty"
   - "라이즈" -> "RISE"
3. NEVER add notes, comments, or parentheses like (Brand/Product Name).
4. NEVER alter or invent currency symbols. If original is a number (e.g. "125"), KEEP IT AS "125".
5. Output ONLY a valid JSON dictionary: {{"0": "...", "1": "...", ...}}

Input:
{dict_json_str}
"""
                payload_4090 = {
                    "model": self.primary_model,
                    "prompt": prompt_4090,
                    "stream": False,
                    "options": {
                        "num_ctx": 1024,
                        "temperature": 0.05
                    }
                }
                req_data = json.dumps(payload_4090).encode("utf-8")
                req = urllib.request.Request(
                    self.primary_endpoint,
                    data=req_data,
                    headers={"Content-Type": "application/json"}
                )
                with urllib.request.urlopen(req, timeout=15.0) as resp:
                    res_json = json.loads(resp.read().decode("utf-8"))
                    raw_out = res_json.get("response", "").strip()
                    raw_out = self._clean_think_tags(raw_out)

                    match = re.search(r"\{.*\}", raw_out, flags=re.DOTALL)
                    if match:
                        parsed = json.loads(match.group(0))
                        res_list = []
                        for i in range(len(texts)):
                            val = str(parsed.get(str(i), texts[i])).strip().strip('"').strip("'")
                            res_list.append(post_process_translation(texts[i], val))
                        return res_list
        except Exception as e:
            print(f"[Pictro LLM] Notice: Primary AI LLM fallback triggered: {e}")

        # 2. Mika DeepSeek Fallback 시도
        try:
            fallback_prompt = f"""You are a professional medical & aesthetic advertising translator.
Translate each item in the JSON map into short, punchy {lang_name} banner copies (1~2 words max per item, e.g. "No Extra", "Fee!", "VAT Incl.", "Rhinoplasty").
{glossary_prompt_section}
Output ONLY a JSON map matching the exact keys. Do NOT add parentheses or notes.

Input:
{dict_json_str}

Output JSON:"""
            payload_fallback = {
                "model": self.fallback_model,
                "messages": [{"role": "user", "content": fallback_prompt}],
                "temperature": 0.05,
                "max_tokens": 512
            }
            req_data = json.dumps(payload_fallback).encode("utf-8")
            req = urllib.request.Request(
                self.fallback_endpoint,
                data=req_data,
                headers={
                    "Authorization": f"Bearer {self.fallback_api_key}",
                    "Content-Type": "application/json"
                }
            )
            with urllib.request.urlopen(req, timeout=35.0) as resp:
                res_json = json.loads(resp.read().decode("utf-8"))
                content = res_json["choices"][0]["message"]["content"]
                content = self._clean_think_tags(content)
                match = re.search(r"\{.*\}", content, flags=re.DOTALL)
                if match:
                    parsed = json.loads(match.group(0))
                    res_list = []
                    for i in range(len(texts)):
                        val = str(parsed.get(str(i), texts[i])).strip().strip('"').strip("'")
                        res_list.append(post_process_translation(texts[i], val))
                    return res_list
        except Exception as e:
            print(f"[Pictro LLM] Warning: Fallback LLM error: {e}")

        # 실패 시 원본 리스트 안전 반환
        return texts


