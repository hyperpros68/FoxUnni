import cv2
import numpy as np
from PIL import Image
from typing import List, Dict, Any, Tuple, Union, Optional

from .inpainter import BackgroundInpainter
from .color_extractor import TextColorExtractor
from .font_renderer import SmartFontRenderer

class ImageTranslationPipeline:
    """
    (1) 원본 글자색 정밀 추출 ➔ (2) 글자 지우기 및 배경 복원 ➔ (3) 다국어 폰트 치환 합성
    통합 AI 렌더링 파이프라인
    """
    def __init__(self, font_dir: Optional[str] = None):
        self.inpainter = BackgroundInpainter()
        self.color_extractor = TextColorExtractor()
        self.font_renderer = SmartFontRenderer(font_dir=font_dir)

    def process(
        self,
        image_input: Union[str, np.ndarray, Image.Image],
        items: List[Dict[str, Any]],
        font_path: Optional[str] = None,
        exact_bbox_fit: bool = True
    ) -> Dict[str, Any]:
        """
        items 예시:
        [
            {
                "box": (ymin, xmin, ymax, xmax),  # 픽셀 좌표
                "translated_text": "Ulthera 300 Shots",
                "custom_color": None,  # None이면 원본 글자색 자동 추출
                "stroke_width": 0,
                "stroke_fill": None,
                "glow": False,
                "shadow": None
            },
            ...
        ]
        """
        # 이미지 로드
        if isinstance(image_input, str):
            orig_bgr = cv2.imread(image_input)
            if orig_bgr is None:
                raise ValueError(f"Failed to load image from {image_input}")
        elif isinstance(image_input, Image.Image):
            orig_bgr = cv2.cvtColor(np.array(image_input), cv2.COLOR_RGB2BGR)
        else:
            orig_bgr = image_input.copy()

        h, w = orig_bgr.shape[:2]
        boxes = []
        item_meta = []

        # 1. 원본 글자색 정밀 추출
        for item in items:
            box = item["box"]
            boxes.append(box)
            
            if item.get("custom_color"):
                rgb = item["custom_color"]
                hex_c = self.color_extractor.rgb_to_hex(rgb)
            else:
                rgb, hex_c = self.color_extractor.extract_color(orig_bgr, box)

            item_meta.append({
                "box": box,
                "translated_text": item.get("translated_text", ""),
                "rgb_color": rgb,
                "hex_color": hex_c,
                "stroke_width": item.get("stroke_width", 0),
                "stroke_fill": item.get("stroke_fill", None),
                "glow": item.get("glow", False),
                "shadow": item.get("shadow", None),
                "font_path": item.get("font_path", font_path)
            })

        # 2. 배경 복원 (글자 지우기 - Big-LaMa / Inpaint)
        clean_bgr, mask = self.inpainter.inpaint(orig_bgr, boxes)

        # 3. 복원된 배경 위에 다국어 텍스트 치환 합성
        rendered_pil = Image.fromarray(clean_bgr[:, :, ::-1])
        
        for meta in item_meta:
            text = meta["translated_text"]
            if not text:
                continue
            box = meta["box"]
            rgb = meta["rgb_color"]
            f_path = meta.get("font_path") or font_path

            if exact_bbox_fit:
                rendered_pil = self.font_renderer.render_exact_bbox_fit(
                    base_image=rendered_pil,
                    text=text,
                    box=box,
                    rgb_color=rgb,
                    font_path=f_path,
                    stroke_width=meta.get("stroke_width", 0),
                    stroke_fill=meta.get("stroke_fill"),
                    glow=meta.get("glow", False),
                    shadow_offset=meta.get("shadow")
                )
            else:
                rendered_pil = self.font_renderer.render_text(
                    base_image=rendered_pil,
                    text=text,
                    box=box,
                    rgb_color=rgb,
                    font_path=f_path
                )

        return {
            "clean_bg_image": clean_bgr,       # OpenCV BGR 복원 배경
            "rendered_image": rendered_pil,    # PIL RGB 최종 치환 이미지
            "mask": mask,                      # 이진 마스크
            "meta": item_meta                  # 색상 및 처리 상세
        }
