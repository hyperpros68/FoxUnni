import os
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
from typing import Tuple, Optional, List, Union

class SmartFontRenderer:
    """
    바운딩 박스 크기에 맞춰 폰트 크기를 자동 스케일링하고,
    세련된 외곽선(Stroke) 및 소프트 드롭 섀도우(Shadow) 효과를 주어
    전문 디자인 배너 수준으로 영문/다국어를 렌더링하는 고품질 엔진
    """
    def __init__(self, font_dir: Optional[str] = None):
        self.font_dir = font_dir
        self.cached_fonts = {}

    def find_system_font(self, bold: bool = True) -> Optional[str]:
        # 1. 패키지 내 resources/fonts 디렉토리 최우선 탐색
        res_font_candidates = [
            os.path.join(os.path.dirname(__file__), "../../resources/fonts/malgunbd.ttf" if bold else "../../resources/fonts/malgun.ttf"),
            os.path.join(os.path.dirname(__file__), "../../resources/fonts/arialbd.ttf" if bold else "../../resources/fonts/arial.ttf"),
            "/home/mika/Project/ThrillRig/Program/Pictro/resources/fonts/malgunbd.ttf",
            "/home/mika/Project/ThrillRig/Program/Pictro/resources/fonts/arialbd.ttf"
        ]
        for p in res_font_candidates:
            if os.path.exists(p):
                return p

        # 2. OS 시스템 폰트 탐색
        candidates = []
        if os.name == "nt": # Windows
            candidates = [
                "C:/Windows/Fonts/malgunbd.ttf" if bold else "C:/Windows/Fonts/malgun.ttf",
                "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
                "C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
            ]
        else: # Linux
            candidates = [
                "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf",
                "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
                "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
                "/usr/share/fonts/truetype/nanum/NanumBarunGothicBold.ttf" if bold else "/usr/share/fonts/truetype/nanum/NanumBarunGothic.ttf",
            ]

        for p in candidates:
            if os.path.exists(p):
                return p
        return None

    def get_font(self, font_path: Optional[str], size: int) -> ImageFont.FreeTypeFont:
        key = (font_path, size)
        if key in self.cached_fonts:
            return self.cached_fonts[key]

        font = None
        if font_path and os.path.exists(font_path):
            try:
                font = ImageFont.truetype(font_path, size=size)
            except Exception:
                pass

        if font is None:
            sys_font = self.find_system_font(bold=True)
            if sys_font and os.path.exists(sys_font):
                try:
                    font = ImageFont.truetype(sys_font, size=size)
                except Exception:
                    pass

        if font is None:
            font = ImageFont.load_default()

        self.cached_fonts[key] = font
        return font

    def fit_text_size(self, text: str, target_w: int, target_h: int, font_path: Optional[str] = None) -> Tuple[ImageFont.FreeTypeFont, int]:
        target_w = max(10, int(target_w * 0.98))
        target_h = max(10, int(target_h * 0.95))

        low = 10
        high = max(14, int(target_h * 1.1))
        best_size = low
        dummy_draw = ImageDraw.Draw(Image.new("RGB", (1, 1)))

        while low <= high:
            mid = (low + high) // 2
            font = self.get_font(font_path, mid)
            bbox = dummy_draw.textbbox((0, 0), text, font=font)
            text_w = bbox[2] - bbox[0]
            text_h = bbox[3] - bbox[1]

            if text_w <= target_w and text_h <= target_h:
                best_size = mid
                low = mid + 1
            else:
                high = mid - 1

        return self.get_font(font_path, best_size), best_size

    def render_text(
        self,
        base_image: Union[np.ndarray, Image.Image],
        text: str,
        box: Tuple[int, int, int, int],
        rgb_color: Tuple[int, int, int],
        font_path: Optional[str] = None,
        add_shadow: bool = True
    ) -> Image.Image:
        if isinstance(base_image, np.ndarray):
            pil_img = Image.fromarray(base_image[:, :, ::-1]).convert("RGBA")
        else:
            pil_img = base_image.copy().convert("RGBA")

        ymin, xmin, ymax, xmax = box
        target_w = xmax - xmin
        target_h = ymax - ymin

        font, font_size = self.fit_text_size(text, target_w, target_h, font_path)
        draw = ImageDraw.Draw(pil_img)

        bbox = draw.textbbox((0, 0), text, font=font)
        text_w = bbox[2] - bbox[0]
        text_h = bbox[3] - bbox[1]

        # 정렬 좌표 (좌측 정렬 또는 중앙 정렬)
        pos_x = xmin + max(0, (target_w - text_w) // 2) - bbox[0]
        pos_y = ymin + max(0, (target_h - text_h) // 2) - bbox[1]

        # 밝은 글자(흰색 계열)일 경우 부드러운 드롭 섀도우 추가로 시인성 대폭 향상
        is_light = np.mean(rgb_color) > 160
        if add_shadow and is_light:
            shadow_color = (0, 0, 0, 90)
            draw.text((pos_x + 1, pos_y + 2), text, fill=shadow_color, font=font)

        # 본문 텍스트 합성
        draw.text((pos_x, pos_y), text, fill=rgb_color, font=font)

        return pil_img.convert("RGB")

    def render_exact_bbox_fit(
        self,
        base_image: Union[np.ndarray, Image.Image],
        text: str,
        box: Tuple[int, int, int, int],
        rgb_color: Tuple[int, int, int],
        font_path: Optional[str] = None,
        stroke_width: int = 0,
        stroke_fill: Optional[Tuple[int, int, int]] = None,
        glow: bool = False,
        shadow_offset: Optional[Tuple[int, int]] = None,
        shadow_blur: int = 2,
        # 장평 허용 범위 (0.6 = 60% 압축, 1.5 = 150% 확장)
        condense_min: float = 0.60,
        condense_max: float = 1.50
    ) -> Image.Image:
        """
        [Pictro Enterprise Natural Typography Engine v3 - 장평(Horizontal Scale)]
        - 폰트 크기는 세로(높이) 기준으로 이진 탐색하여 최대한 키움
        - 텍스트를 별도 RGBA 레이어에 렌더링 후, 가로 방향만 PIL resize()로 스트레치(장평)
        - 장평 범위: condense_min ~ condense_max (기본 60%~150%, 과도한 왜곡 방지)
        - 소프트 Halo + 정밀 드롭 섀도우로 가독성 극대화
        - stroke_fill 미지정 시 배경 대비 자동 선택
        - 2줄 분리 없음, 항상 단일 행 유지
        """
        if isinstance(base_image, np.ndarray):
            base_img = Image.fromarray(base_image[:, :, ::-1]).convert("RGBA")
        else:
            base_img = base_image.copy().convert("RGBA")

        ymin, xmin, ymax, xmax = [int(v) for v in box]
        target_w = max(1, xmax - xmin)
        target_h = max(1, ymax - ymin)

        # 세로 기준 안전 여백 82%로 폰트 크기 이진 탐색 (가로 제약 없음)
        max_allowed_h = max(8, int(target_h * 0.82))
        dummy = Image.new("RGBA", (1, 1), (0, 0, 0, 0))
        d_draw = ImageDraw.Draw(dummy)

        low, high, best_size = 8, max(12, int(max_allowed_h * 1.05)), 8
        while low <= high:
            mid = (low + high) // 2
            f = self.get_font(font_path, mid)
            b = d_draw.textbbox((0, 0), text, font=f, stroke_width=stroke_width)
            if (b[3] - b[1]) <= max_allowed_h:
                best_size = mid
                low = mid + 1
            else:
                high = mid - 1

        font = self.get_font(font_path, best_size)
        bbox = d_draw.textbbox((0, 0), text, font=font, stroke_width=stroke_width)
        text_w = bbox[2] - bbox[0]
        text_h = bbox[3] - bbox[1]

        # --- 외곽선(stroke) 두께 자동 결정 ---
        s_w = stroke_width if stroke_width > 0 else (2 if best_size >= 32 else (1 if best_size >= 16 else 0))

        # --- stroke_fill: 배경 대비 자동 선택 ---
        is_light = np.mean(rgb_color) > 150
        auto_stroke_fill = (0, 0, 0) if is_light else (255, 255, 255)
        s_fill = stroke_fill if stroke_fill is not None else auto_stroke_fill

        # --- 장평(Horizontal Scale) 계산 ---
        # 목표: 가로 여백 90% 채우기
        target_fill_w = max(8, int(target_w * 0.90))
        raw_scale = target_fill_w / text_w if text_w > 0 else 1.0
        # 허용 범위 클램프
        h_scale = max(condense_min, min(condense_max, raw_scale))
        scaled_w = max(1, int(text_w * h_scale))
        # 장평 적용 후 세로는 원본 text_h 유지
        scaled_h = max(1, text_h)

        # --- 텍스트를 별도 레이어에 렌더링 ---
        # 원본 크기로 그린 뒤 resize로 장평 적용
        pad = s_w + 2  # stroke/shadow 잘림 방지 여유
        layer_w = text_w + pad * 2
        layer_h = text_h + pad * 2
        text_layer = Image.new("RGBA", (max(1, layer_w), max(1, layer_h)), (0, 0, 0, 0))
        tl_draw = ImageDraw.Draw(text_layer)
        tl_x = pad - bbox[0]
        tl_y = pad - bbox[1]

        # 소프트 Halo(글로우) - 텍스트 레이어에 먼저 그림
        if glow or is_light:
            halo_radius = max(2, best_size // 10)
            halo_col = (0, 0, 0, 70) if is_light else (255, 255, 255, 50)
            for dx in range(-halo_radius, halo_radius + 1):
                for dy in range(-halo_radius, halo_radius + 1):
                    if dx == 0 and dy == 0:
                        continue
                    dist = (dx ** 2 + dy ** 2) ** 0.5
                    if dist <= halo_radius:
                        alpha_factor = max(0, 1.0 - dist / halo_radius)
                        col = halo_col[:3] + (int(halo_col[3] * alpha_factor),)
                        tl_draw.text((tl_x + dx, tl_y + dy), text, font=font, fill=col)
            text_layer = text_layer.filter(ImageFilter.GaussianBlur(radius=max(1, halo_radius // 2)))
            tl_draw = ImageDraw.Draw(text_layer)

        # 드롭 섀도우
        s_offset = shadow_offset if shadow_offset else (max(1, best_size // 20), max(1, best_size // 14))
        shadow_col = (0, 0, 0, 130) if is_light else (0, 0, 0, 80)
        tl_draw.text(
            (tl_x + s_offset[0], tl_y + s_offset[1]),
            text, font=font, fill=shadow_col,
            stroke_width=s_w, stroke_fill=shadow_col
        )

        # 본문 텍스트
        tl_draw.text(
            (tl_x, tl_y),
            text, font=font, fill=rgb_color,
            stroke_width=s_w, stroke_fill=s_fill
        )

        # --- 장평 적용: 가로만 resize ---
        final_layer = text_layer.resize(
            (max(1, int(layer_w * h_scale)), layer_h),
            Image.LANCZOS
        )

        # --- base_img 정중앙에 합성 ---
        paste_x = xmin + (target_w - final_layer.width) // 2
        paste_y = ymin + (target_h - final_layer.height) // 2
        # 경계 클램프
        paste_x = max(0, min(paste_x, base_img.width - 1))
        paste_y = max(0, min(paste_y, base_img.height - 1))

        base_img.paste(final_layer, (paste_x, paste_y), final_layer)

        return base_img.convert("RGB")

