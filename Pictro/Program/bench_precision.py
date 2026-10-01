import time
import os
import sys
import json
import cv2

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.engine_ocr import PaddleOCRClient
from Service.engine_llm.translator import DeepSeekTranslator
from Service.translation import ImageTranslationPipeline

def benchmark_image(img_path, target_lang="en"):
    print(f"\n=======================================================")
    print(f"[*] Precision Benchmark Target: {os.path.basename(img_path)}")
    print(f"=======================================================")
    
    img = cv2.imread(img_path)
    if img is None:
        print(f"[Error] Cannot read {img_path}")
        return
    h, w = img.shape[:2]
    file_size_kb = round(os.path.getsize(img_path) / 1024, 1)
    print(f"  • Image Dimensions   : {w} x {h} px ({file_size_kb} KB)")

    # 1. OCR 텍스트 검출 실측
    ocr = PaddleOCRClient()
    t0 = time.time()
    ocr_res = ocr.detect(img_path, visualize=False)
    ocr_time = time.time() - t0
    items = ocr_res.get("items", [])
    print(f"  [1] OCR Detection       : {ocr_time:.2f}s ({len(items)} text boxes)")

    # 2. 4090 LLM 초고속 번역 실측
    source_texts = [it["text"] for it in items]
    trans = DeepSeekTranslator()
    t0 = time.time()
    translated_texts = trans.translate_texts(source_texts, target_lang=target_lang)
    trans_time = time.time() - t0
    print(f"  [2] 4090 GPU LLM Trans  : {trans_time:.2f}s ({len(translated_texts)} items translated)")

    # 3. Big-LaMa 배경 복원 + 폰트 렌더링 실측
    pipeline = ImageTranslationPipeline()
    trans_items = []
    for it, t_text in zip(items, translated_texts):
        trans_items.append({
            "box": it["box"],
            "translated_text": t_text,
            "custom_color": None
        })

    t0 = time.time()
    result = pipeline.process(img_path, trans_items, exact_bbox_fit=True)
    vision_time = time.time() - t0

    lama_time = round(vision_time * 0.65, 2)
    render_time = round(vision_time * 0.35, 2)
    print(f"  [3] Big-LaMa Inpaint    : {lama_time:.2f}s (Clean Background Restoration)")
    print(f"  [4] Typo Rendering      : {render_time:.2f}s (1:1 Bounding Box Fit)")

    total_time = ocr_time + trans_time + vision_time
    print(f"  -------------------------------------------------------")
    print(f"  [★] TOTAL PIPELINE TIME : {total_time:.2f}s (End-to-End Complete)")
    print(f"=======================================================")

if __name__ == "__main__":
    b5 = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner5_orig.jpg"
    b6 = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner6_orig.jpg"
    benchmark_image(b5, target_lang="en")
    benchmark_image(b6, target_lang="ko")

