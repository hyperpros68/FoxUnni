import os
import cv2
import numpy as np
from PIL import Image
from typing import List, Tuple, Union, Optional

class BackgroundInpainter:
    """
    이미지 내 텍스트 영역을 제거하고 배경을 복원하는 인페인팅 엔진
    """
    def __init__(self, inpaint_radius: int = 3, method: str = "telea", lama_model_path: Optional[str] = None):
        self.inpaint_radius = inpaint_radius
        self.flag = cv2.INPAINT_TELEA if method.lower() != "ns" else cv2.INPAINT_NS
        self.lama_model = None
        self.device = None

        # Big-LaMa TorchScript 모델 자동 탐색
        candidates = [
            lama_model_path,
            "/home/mika/Project/ThrillRig/Program/Pictro/models/big-lama.pt",
            "d:/Project/FoxUnni/Pictro/Program/models/big-lama.pt",
            os.path.join(os.path.dirname(__file__), "../../models/big-lama.pt")
        ]
        for p in candidates:
            if p and os.path.exists(p):
                try:
                    import torch
                    self.device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
                    self.lama_model = torch.jit.load(p, map_location=self.device)
                    self.lama_model.eval()
                    break
                except Exception:
                    pass

    def create_mask(self, cv_img: np.ndarray, boxes: List[Tuple[int, int, int, int]], padding: int = 4) -> np.ndarray:
        """
        [Pictro Big-LaMa High-Quality Clean Inpainting Mask]
        - 바운딩 박스를 완벽히 커버하고 5x5 커널 2회 팽창으로 폰트 그림자/잔상을 100% 제거
        """
        h, w = cv_img.shape[:2]
        mask = np.zeros((h, w), dtype=np.uint8)

        for box in boxes:
            if len(box) == 4:
                ymin, xmin, ymax, xmax = [int(v) for v in box]
                y1, y2 = max(0, ymin - padding), min(h, ymax + padding)
                x1, x2 = max(0, xmin - padding), min(w, xmax + padding)
                if y2 <= y1 or x2 <= x1:
                    continue
                mask[y1:y2, x1:x2] = 255

        # 글자 외곽 그림자 및 안티앨리어싱 잔상 완전 박멸 팽창
        kernel = np.ones((5, 5), np.uint8)
        mask = cv2.dilate(mask, kernel, iterations=2)
        return mask

    def inpaint(self, image: Union[np.ndarray, Image.Image], boxes: List[Tuple[int, int, int, int]], padding: int = 2) -> Tuple[np.ndarray, np.ndarray]:
        if isinstance(image, Image.Image):
            cv_img = cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)
        else:
            cv_img = image.copy()

        h, w = cv_img.shape[:2]
        mask = self.create_mask(cv_img, boxes, padding=padding)

        # 1. Big-LaMa 모델이 로드된 경우 SOTA 인페인팅 적용
        if self.lama_model is not None:
            try:
                import torch
                pad_h = (8 - h % 8) % 8
                pad_w = (8 - w % 8) % 8
                img_pad = cv2.copyMakeBorder(cv_img, 0, pad_h, 0, pad_w, cv2.BORDER_REFLECT)
                mask_pad = cv2.copyMakeBorder(mask, 0, pad_h, 0, pad_w, cv2.BORDER_CONSTANT, value=0)

                rgb_pad = cv2.cvtColor(img_pad, cv2.COLOR_BGR2RGB)
                img_tensor = torch.from_numpy(rgb_pad.astype(np.float32) / 255.0).permute(2, 0, 1).unsqueeze(0).to(self.device)
                mask_tensor = torch.from_numpy((mask_pad > 127).astype(np.float32)).unsqueeze(0).unsqueeze(0).to(self.device)

                with torch.no_grad():
                    out_tensor = self.lama_model(img_tensor, mask_tensor)

                raw_out = out_tensor[0].permute(1, 2, 0).detach().cpu().numpy()
                clean_rgb = np.clip(raw_out * 255.0, 0, 255).astype(np.uint8)[:h, :w]
                return cv2.cvtColor(clean_rgb, cv2.COLOR_RGB2BGR), mask
            except Exception:
                pass

        # 2. 모델이 없거나 에러 시 OpenCV 기본 인페인팅 안전 fallback
        inpainted_bgr = cv2.inpaint(cv_img, mask, inpaintRadius=self.inpaint_radius, flags=self.flag)
        return inpainted_bgr, mask

