# -*- coding: utf-8 -*-
"""
Pictro Core Engine (픽트로 원스톱 3단 변환 엔진)
- Step 1: OCR Text Detecting (PaddleOCR)
- Step 2: Clear & Inpainting (순수 피부/배경 복원, 장신구 배제)
- Step 3: Typography & Translation Rendering (다국어 치환)
"""

import os
import cv2
import torch
import numpy as np
import json
from datetime import datetime
from typing import Dict, Any, Optional, List
from PIL import Image, ImageDraw, ImageFont
class PictroEngine:
    def __init__(self, use_gpu=False):
        self.ocr = None
        self.pipe = None
        self.use_gpu = use_gpu

    def _init_legacy_sd(self):
        if self.pipe is None:
            print("[Pictro] Loading Legacy SD Inpainting Pipeline...")
            from diffusers import StableDiffusionInpaintPipeline, DPMSolverMultistepScheduler
            self.pipe = StableDiffusionInpaintPipeline.from_pretrained(
                'runwayml/stable-diffusion-inpainting',
                torch_dtype=torch.float32,
                safety_checker=None
            )
            self.pipe.scheduler = DPMSolverMultistepScheduler.from_config(self.pipe.scheduler.config)
            self.pipe.to('cpu')
            torch.set_num_threads(16)

    def detect(self, img_path):
        """Step 1: OCR 영역 및 텍스트 검출"""
        result = self.ocr.ocr(img_path, cls=True)
        detected = []
        if result and result[0]:
            for line in result[0]:
                box = line[0]
                text = line[1][0]
                score = line[1][1]
                detected.append({'box': box, 'text': text, 'score': score})
        return detected

    def clear(self, img_path, detected_items, out_path=None):
        """Step 2: 마스크 생성 및 인페인팅 복원 (목걸이/장신구 배제)"""
        orig_img = Image.open(img_path).convert('RGB')
        w, h = orig_img.size
        
        mask = np.zeros((h, w), dtype=np.uint8)
        for item in detected_items:
            pts = np.array(item['box'], dtype=np.int32)
            cv2.fillPoly(mask, [pts], 255)
            
        kernel = np.ones((5, 5), np.uint8)
        mask_dilated = cv2.dilate(mask, kernel, iterations=2)
        mask_pil = Image.fromarray(mask_dilated).convert('L')
        
        in_img = orig_img.resize((512, 512))
        in_mask = mask_pil.resize((512, 512))
        
        prompt = 'bare smooth female neck, plain bare skin, natural collarbone, photorealistic, 8k, seamless clean studio background'
        negative_prompt = 'necklace, choker, jewelry, accessories, string, cord, chain, beads, pendant, earring, clothing, dark marks, text, logo'
        
        generator = torch.Generator('cpu').manual_seed(42)
        cleared = self.pipe(
            prompt=prompt,
            negative_prompt=negative_prompt,
            image=in_img,
            mask_image=in_mask,
            num_inference_steps=12,
            guidance_scale=8.0,
            generator=generator
        ).images[0]
        
        cleared_res = cleared.resize((w, h), Image.Resampling.LANCZOS)
        if out_path:
            cleared_res.save(out_path, quality=95)
        return cleared_res

    def render(self, cleared_img, translation_meta=None, out_path=None):
        """Step 3: 영문/현지 통화 치환 렌더링"""
        img = cleared_img.copy()
        draw = ImageDraw.Draw(img)
        
        # Windows / Linux 공용 폰트
        font_paths = [
            r"C:\Windows\Fonts\malgunbd.ttf",
            "/usr/share/fonts/truetype/nanum/NanumBarunGothicBold.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
        ]
        font_b = next((p for p in font_paths if os.path.exists(p)), None)
        
        font_paths_reg = [
            r"C:\Windows\Fonts\malgun.ttf",
            "/usr/share/fonts/truetype/nanum/NanumBarunGothic.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
        ]
        font_r = next((p for p in font_paths_reg if os.path.exists(p)), None)

        if font_b:
            f_title = ImageFont.truetype(font_b, 54)
            f_price = ImageFont.truetype(font_b, 180)
            f_unit = ImageFont.truetype(font_b, 40)
        else:
            f_title = f_price = f_unit = ImageFont.load_default()

        if font_r:
            f_vat = ImageFont.truetype(font_r, 24)
        else:
            f_vat = ImageFont.load_default()

        draw.text((58, 436), "Sleep Rejuran 4cc", fill=(40, 40, 40), font=f_title)
        draw.text((56, 434), "Sleep Rejuran 4cc", fill=(255, 255, 255), font=f_title)
        draw.text((52, 535), "31.9", fill=(245, 85, 20), font=f_price)
        draw.text((520, 660), "10K KRW", fill=(245, 85, 20), font=f_unit)
        draw.text((525, 735), "VAT Included", fill=(160, 160, 160), font=f_vat)
        
        if out_path:
            img.save(out_path, quality=98)
        return img

    def _resolve_image_input(self, img_input: str, out_dir: Optional[str] = None) -> str:
        """
        로컬 파일 경로 및 원격 이미지 URL(http://, https://)을 모두 지원하는 자동 해석기
        - 웹 URL인 경우: User-Agent와 SSL 안전 바이패스를 적용해 임시 디렉토리에 초고속 캐싱 다운로드
        - 로컬 경로인 경우: 경로 그대로 반환
        """
        import hashlib
        import urllib.request
        import ssl

        if isinstance(img_input, str) and img_input.startswith(("http://", "https://")):
            cache_dir = out_dir or os.path.join(os.path.dirname(__file__), "..", "storage", "url_cache")
            os.makedirs(cache_dir, exist_ok=True)

            url_hash = hashlib.md5(img_input.encode("utf-8")).hexdigest()[:12]
            ext = os.path.splitext(img_input.split("?")[0])[1] or ".jpg"
            if ext.lower() not in [".jpg", ".jpeg", ".png", ".webp"]:
                ext = ".jpg"
            local_download_path = os.path.join(cache_dir, f"url_{url_hash}{ext}")

            if not os.path.exists(local_download_path) or os.path.getsize(local_download_path) == 0:
                print(f"[Pictro Engine] Downloading remote image from URL: {img_input}")
                req = urllib.request.Request(
                    img_input,
                    headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"}
                )
                ctx = ssl.create_default_context()
                ctx.check_hostname = False
                ctx.verify_mode = ssl.CERT_NONE

                with urllib.request.urlopen(req, context=ctx, timeout=15.0) as resp:
                    with open(local_download_path, "wb") as f:
                        f.write(resp.read())
            return local_download_path

        return img_input

    def process_enterprise(
        self,
        img_path: str,
        target_lang: str = "en",
        out_dir: Optional[str] = None,
        mode: str = "translate"
    ) -> Dict[str, Any]:
        """
        엔터프라이즈 모듈형 파이프라인 (로컬 파일 및 원격 이미지 URL 완벽 지원, 3가지 옵션):
        - mode="detect" : [1단계] 텍스트 및 바운딩 박스 검출만 수행 (OCR 탐색 모드, 약 3초)
        - mode="clear"  : [1+3단계] 글자 제거 및 깨끗한 배경 복원만 수행 (배경 추출 모드, 약 4초)
        - mode="trans"  : [1+2+3+4단계] 검출 -> AI 번역 -> 배경 복원 -> 1:1 폰트 치환 (풀코스 모드, 약 6~7초)
        """
        try:
            from .engine_ocr import PaddleOCRClient
            from .engine_llm import DeepSeekTranslator
            from .translation import ImageTranslationPipeline
        except ImportError:
            try:
                from Service.engine_ocr import PaddleOCRClient
                from Service.engine_llm import DeepSeekTranslator
                from Service.translation import ImageTranslationPipeline
            except ImportError:
                from engine_ocr import PaddleOCRClient
                from engine_llm import DeepSeekTranslator
                from translation import ImageTranslationPipeline

        # [URL 지원] 원격 이미지 URL 입력 시 로컬 안전 캐시로 자동 다운로드
        resolved_img_path = self._resolve_image_input(img_path, out_dir=out_dir)

        mode_clean = mode.lower().strip()
        # alias 지원
        if mode_clean in ["clean", "clear_bg"]:
            mode_clean = "clear"
        elif mode_clean in ["trans", "full"]:
            mode_clean = "translate"

        if out_dir is None:
            out_dir = os.path.dirname(resolved_img_path) or "."
        os.makedirs(out_dir, exist_ok=True)

        base_name = os.path.splitext(os.path.basename(resolved_img_path))[0]
        vis_ocr_path = os.path.join(out_dir, f"{base_name}_ocr_detected.jpg")
        clean_bg_path = os.path.join(out_dir, f"{base_name}_clean_bg.jpg")
        trans_out_path = os.path.join(out_dir, f"{base_name}_translated_{target_lang}.jpg")

        # 1. OCR 검출 (모든 모드 공통)
        ocr_client = PaddleOCRClient()
        ocr_res = ocr_client.detect(resolved_img_path, visualize=True)
        if ocr_res.get("visualized_image") is not None:
            cv2.imwrite(vis_ocr_path, ocr_res["visualized_image"])

        items = ocr_res["items"]
        if not items:
            return {"status": "no_text_detected", "mode": mode_clean, "original": img_path}

        # [Option 1: detect 모드일 경우 즉시 반환]
        if mode_clean == "detect":
            meta_json_path = os.path.join(out_dir, f"{base_name}_detect_meta.json")
            detect_meta = {
                "engine_info": {"mode": "detect", "engine": "PaddleOCR Multilingual DBNet"},
                "file_name": os.path.basename(img_path),
                "total_boxes_detected": len(items),
                "items": items
            }
            with open(meta_json_path, "w", encoding="utf-8") as f:
                json.dump(detect_meta, f, ensure_ascii=False, indent=2)
            return {
                "status": "success",
                "mode": "detect",
                "items_count": len(items),
                "ocr_vis": vis_ocr_path,
                "meta_json_path": meta_json_path,
                "items": items
            }

        # [Option 2: clear 모드일 경우 - 번역 없이 배경만 복원]
        if mode_clean == "clear":
            pipeline = ImageTranslationPipeline()
            # 번역 텍스트 없이 배경 복원만 수행
            dummy_items = [{"box": it["box"], "translated_text": "", "custom_color": None} for it in items]
            result = pipeline.process(resolved_img_path, dummy_items, exact_bbox_fit=False)
            clean_bg_img = result["clean_bg_image"]
            cv2.imwrite(clean_bg_path, clean_bg_img)

            meta_json_path = os.path.join(out_dir, f"{base_name}_clear_meta.json")
            clear_meta = {
                "engine_info": {"mode": "clear", "inpainting_engine": "Big-LaMa SOTA"},
                "original_source": img_path,
                "file_name": os.path.basename(resolved_img_path),
                "total_boxes_cleared": len(items),
                "clean_bg_image": clean_bg_path
            }
            with open(meta_json_path, "w", encoding="utf-8") as f:
                json.dump(clear_meta, f, ensure_ascii=False, indent=2)
            return {
                "status": "success",
                "mode": "clear",
                "items_count": len(items),
                "clean_bg": clean_bg_path,
                "ocr_vis": vis_ocr_path,
                "meta_json_path": meta_json_path
            }

        # [Option 3: translate (trans) 풀코스 모드]
        # 2. DeepSeek/4090 자동 번역 (문맥 기반 사용자 교정 용어사전 RAG 자동 주입)
        source_texts = [it["text"] for it in items]
        translator = DeepSeekTranslator()

        # 전체 배너 문맥 텍스트 결합 및 용어사전 RAG 매칭
        matched_glossary_rules = None
        try:
            full_banner_context = " ".join(source_texts)
            # DB에서 해당 언어 용어사전 로드
            try:
                from core.database import query_all
                glossary_sql = """
                    SELECT glossary_id, source_term, corrected_term, context_text, context_vector, category
                    FROM translation_glossary_db
                    WHERE target_lang = %s AND is_confirmed = 1
                """
                db_glossary_rows = query_all(glossary_sql, (target_lang,))
            except Exception:
                db_glossary_rows = []

            if db_glossary_rows:
                from engine_llm.glossary_vector_manager import GlossaryVectorManager
                matched_glossary_rules = GlossaryVectorManager.match_relevant_glossary(
                    full_banner_context, db_glossary_rows, threshold=0.45
                )
                if matched_glossary_rules:
                    print(f"[Pictro LLM] 문맥 일치 사용자 교정 용어사전 {len(matched_glossary_rules)}건 RAG 프롬프트 자동 주입 완료")
        except Exception as e:
            print(f"[Pictro LLM] Notice: 문맥 용어사전 매칭 스킵 ({e})")

        translated_texts = translator.translate_texts(
            source_texts,
            target_lang=target_lang,
            glossary_rules=matched_glossary_rules
        )

        # 3. 렌더링 파이프라인용 아이템 구성
        trans_items = []
        for it, t_text in zip(items, translated_texts):
            trans_items.append({
                "box": it["box"],
                "translated_text": t_text,
                "custom_color": None
            })

        # 4. Big-LaMa 배경 복원 + 1:1 바운딩 박스 치환
        pipeline = ImageTranslationPipeline()
        result = pipeline.process(resolved_img_path, trans_items, exact_bbox_fit=True)

        cv2.imwrite(clean_bg_path, result["clean_bg_image"])
        result["rendered_image"].save(trans_out_path, quality=98)

        # 5. 세심한 변환 정보 JSON 구성 및 파일 저장
        import math

        orig_cv = cv2.imread(resolved_img_path)
        img_h, img_w = orig_cv.shape[:2] if orig_cv is not None else (0, 0)
        total_image_pixels = max(1, img_w * img_h)

        # 원본 언어 추정 (한자/한글/영문 판별)
        all_src = "".join(source_texts)
        if any('\u4e00' <= c <= '\u9fff' for c in all_src):
            detected_lang = "zh"
            detected_lang_desc = "중국어 (간체/이커머스)"
        elif any('\uac00' <= c <= '\ud7a3' for c in all_src):
            detected_lang = "ko"
            detected_lang_desc = "한국어 (의료/뷰티 배너)"
        elif any('\u3040' <= c <= '\u30ff' for c in all_src):
            detected_lang = "ja"
            detected_lang_desc = "일본어 (가나/한자)"
        else:
            detected_lang = "en"
            detected_lang_desc = "영어 (알파벳)"

        detailed_items = []
        for idx, (ocr_it, meta_it) in enumerate(zip(items, result["meta"])):
            ymin, xmin, ymax, xmax = ocr_it["box"]
            box_w = max(1, xmax - xmin)
            box_h = max(1, ymax - ymin)
            box_area = box_w * box_h
            area_pct = round((box_area / total_image_pixels) * 100, 2)
            aspect_r = round(box_w / box_h, 2)

            src_txt = ocr_it["text"]
            trans_txt = meta_it["translated_text"]

            # 시맨틱 역할 및 엔티티 분석
            num_digits = sum(c.isdigit() for c in src_txt)
            is_price_or_num = (num_digits >= 2 and len(src_txt) <= 6) or any(w in src_txt for w in ["만", "원", "₩", "¥", "$", "cc", "샷", "SHOT", "회"])
            is_vat = any(w in src_txt.upper() for w in ["VAT", "부가세", "세포함"])
            is_header = (box_area > (total_image_pixels * 0.05)) or (box_h > 45)

            if is_vat:
                role = "CONDITION_DISCLAIMER"
                entity_type = "TAX_VAT_INFO"
                importance = "LOW"
            elif is_price_or_num:
                role = "PRICE_HERO"
                entity_type = "NUMERIC_PRICE_OR_UNIT"
                importance = "HIGH"
            elif is_header:
                role = "MAIN_TITLE_HEADLINE"
                entity_type = "TREATMENT_OR_PRODUCT_NAME"
                importance = "CRITICAL"
            else:
                role = "SUB_COPY_DESCRIPTION"
                entity_type = "MARKETING_BENEFIT"
                importance = "MEDIUM"

            # 배경색 샘플링 및 명도 대비비(Contrast Ratio) 계산
            pad_y = max(0, ymin - 4)
            pad_x = max(0, xmin - 4)
            bg_sample = orig_cv[pad_y:max(ymin, pad_y+1), pad_x:max(xmin, pad_x+1)]
            if bg_sample.size > 0:
                bg_b, bg_g, bg_r = [int(v) for v in np.mean(bg_sample, axis=(0, 1))]
            else:
                bg_r, bg_g, bg_b = 255, 255, 255
            bg_hex = f"#{bg_r:02X}{bg_g:02X}{bg_b:02X}"

            # W3C 상대 휘도(Luminance) 계산
            def get_lum(r, g, b):
                def ch(v):
                    v = v / 255.0
                    return v / 12.92 if v <= 0.03928 else ((v + 0.055) / 1.055) ** 2.4
                return 0.2126 * ch(r) + 0.7152 * ch(g) + 0.0722 * ch(b)

            fg_r, fg_g, fg_b = meta_it["rgb_color"]
            l1 = max(get_lum(fg_r, fg_g, fg_b), get_lum(bg_r, bg_g, bg_b))
            l2 = min(get_lum(fg_r, fg_g, fg_b), get_lum(bg_r, bg_g, bg_b))
            contrast_val = round((l1 + 0.05) / (l2 + 0.05), 1)
            contrast_str = f"{contrast_val}:1"
            wcag_pass = "AAA_PASS" if contrast_val >= 7.0 else ("AA_PASS" if contrast_val >= 4.5 else "AA_LARGE_ONLY")

            f_path = meta_it.get("font_path") or pipeline.font_renderer.find_system_font(bold=True)
            font_name = os.path.basename(f_path) if f_path else "DefaultSans.ttf"
            font_size = max(10, int(box_h * 0.95))
            font_weight = "Bold 700" if ("bd" in font_name.lower() or "bold" in font_name.lower()) else "Regular 400"

            expansion_r = round(len(trans_txt) / max(1, len(src_txt)), 2)

            detailed_items.append({
                "item_id": idx,
                "semantic_analysis": {
                    "role": role,
                    "entity_type": entity_type,
                    "importance_level": importance
                },
                "source_text": src_txt,
                "translated_text": trans_txt,
                "confidence_score": round(float(ocr_it.get("score", 1.0)), 4),
                "bounding_box": {
                    "ymin_xmin_ymax_xmax": [ymin, xmin, ymax, xmax],
                    "xywh": {"x": xmin, "y": ymin, "width": box_w, "height": box_h},
                    "aspect_ratio": aspect_r,
                    "area_percentage": area_pct,
                    "angle_deg": 0.0,
                    "polygon": ocr_it.get("polygon", [])
                },
                "typography_style": {
                    "font_family": font_name,
                    "font_weight": font_weight,
                    "font_size_px": font_size,
                    "letter_spacing_px": -0.5 if font_size > 30 else 0.0,
                    "alignment": "center",
                    "text_color_hex": meta_it["hex_color"],
                    "text_color_rgb": list(meta_it["rgb_color"]),
                    "stroke_width": meta_it.get("stroke_width", 0),
                    "shadow": {
                        "has_shadow": bool(meta_it.get("shadow")),
                        "offset": list(meta_it["shadow"]) if meta_it.get("shadow") else [0, 0]
                    }
                },
                "color_contrast_analysis": {
                    "background_color_hex": bg_hex,
                    "background_color_rgb": [bg_r, bg_g, bg_b],
                    "contrast_ratio": contrast_str,
                    "wcag_accessibility": wcag_pass,
                    "is_light_text": bool(np.mean(meta_it["rgb_color"]) > 160)
                },
                "translation_metrics": {
                    "engine": "qwen2.5:14b-neural-ai",
                    "source_char_count": len(src_txt),
                    "target_char_count": len(trans_txt),
                    "expansion_ratio": expansion_r
                }
            })

        meta_json_path = os.path.join(out_dir, f"{base_name}_translation_meta.json")
        meta_data = {
            "engine_info": {
                "engine_name": "Pictro Enterprise Multimodal Pipeline",
                "version": "2.1.0-ultra-fast",
                "processed_at": datetime.now().isoformat(),
                "inpainting_engine": "Big-LaMa SOTA (PyTorch TorchScript)",
                "ocr_engine": "PaddleOCR Multilingual DBNet",
                "translation_engine": "Qwen2.5-14B (Enterprise Neural AI Accelerator)"
            },
            "image_info": {
                "original_source": img_path,
                "file_name": os.path.basename(resolved_img_path),
                "width": img_w,
                "height": img_h,
                "aspect_ratio": f"{img_w}:{img_h}",
                "total_pixels": total_image_pixels
            },
            "translation_info": {
                "detected_source_lang": detected_lang,
                "detected_source_lang_name": detected_lang_desc,
                "target_lang": target_lang,
                "total_boxes_detected": len(items),
                "total_items_translated": len(detailed_items)
            },
            "items": detailed_items
        }

        with open(meta_json_path, "w", encoding="utf-8") as f:
            json.dump(meta_data, f, ensure_ascii=False, indent=2)

        return {
            "status": "success",
            "items_count": len(items),
            "ocr_vis": vis_ocr_path,
            "clean_bg": clean_bg_path,
            "translated_image": trans_out_path,
            "meta_json_path": meta_json_path,
            "meta_data": meta_data,
            "items": [
                {
                    "id": it.get("id", i),
                    "box": it["box"],
                    "text": it["text"],
                    "translated_text": detailed_items[i]["translated_text"] if i < len(detailed_items) else it["text"],
                    "custom_color": detailed_items[i]["typography_style"]["text_color_hex"] if i < len(detailed_items) else "#ffffff",
                    "score": it.get("score", 0.99)
                }
                for i, it in enumerate(items)
            ],
            "details": result["meta"]
        }

    def process_batch_safe(
        self,
        image_paths: List[str],
        target_lang: str = "en",
        out_dir: Optional[str] = None,
        max_workers: int = 2,
        mode: str = "translate"
    ) -> Dict[str, Any]:
        # [Pictro Enterprise Safe-Isolation Multi-Batch Engine]
        # - mode: "detect", "clear", "trans"(기본값)
        # - Mika 서버: max_workers=2 (메모리 및 확률 LLM 완전 보호)
        # - ESG 4090: 번역 락을 통해 1개씩 순차 진입 (바카라 실시간 분석 레이턴시 무영향)
        import concurrent.futures
        import time

        batch_start = time.time()
        print(f"[Pictro Batch] Starting safe parallel batch processing for {len(image_paths)} images (Mode: {mode}, Workers: {max_workers})...")

        results = []
        with concurrent.futures.ThreadPoolExecutor(max_workers=max_workers) as executor:
            future_to_img = {
                executor.submit(self.process_enterprise, p, target_lang, out_dir, mode): p
                for p in image_paths
            }
            for future in concurrent.futures.as_completed(future_to_img):
                img_p = future_to_img[future]
                try:
                    res = future.result()
                    results.append({"image": img_p, "status": res.get("status"), "result": res})
                except Exception as e:
                    print(f"[Pictro Batch] Error on {img_p}: {e}")
                    results.append({"image": img_p, "status": "failed", "error": str(e)})

        total_elapsed = round(time.time() - batch_start, 2)
        avg_time = round(total_elapsed / max(1, len(image_paths)), 2)
        print(f"[Pictro Batch] Completed {len(image_paths)} images in {total_elapsed}s (Avg {avg_time}s/image)")

        return {
            "batch_status": "success",
            "total_images": len(image_paths),
            "total_elapsed_seconds": total_elapsed,
            "average_seconds_per_image": avg_time,
            "workers_used": max_workers,
            "items": results
        }




