import cv2
import numpy as np
from typing import Tuple, Optional
from sklearn.cluster import KMeans

class TextColorExtractor:
    """
    원본 글자 영역의 픽셀을 분석하여 글자 본체 색상(RGB/Hex) 및 외곽선 색상을 자동 추출하는 엔진
    """
    def __init__(self, n_clusters: int = 2):
        self.n_clusters = n_clusters

    def rgb_to_hex(self, rgb: Tuple[int, int, int]) -> str:
        return "#{:02x}{:02x}{:02x}".format(int(rgb[0]), int(rgb[1]), int(rgb[2]))

    def extract_color(self, bgr_image: np.ndarray, box: Tuple[int, int, int, int]) -> Tuple[Tuple[int, int, int], str]:
        """
        bgr_image: 원본 BGR 이미지
        box: (ymin, xmin, ymax, xmax)
        반환: ((R, G, B), "#RRGGBB")
        """
        h, w = bgr_image.shape[:2]
        ymin, xmin, ymax, xmax = box
        y1, x1, y2, x2 = max(0, int(ymin)), max(0, int(xmin)), min(h, int(ymax)), min(w, int(xmax))
        
        if y2 <= y1 or x2 <= x1:
            return (0, 0, 0), "#000000"

        roi = bgr_image[y1:y2, x1:x2]
        if roi.size == 0:
            return (0, 0, 0), "#000000"

        # BGR to RGB 변환
        roi_rgb = cv2.cvtColor(roi, cv2.COLOR_BGR2RGB)
        gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
        
        # Otsu 이진화로 글자(전경)와 배경 분리
        # 배경이 밝고 글자가 어두운 경우 vs 배경이 어둡고 글자가 밝은 경우 판별
        _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        
        # 가장자리 테두리 픽셀을 샘플링하여 배경의 평균 밝기 추정
        edge_pixels = np.concatenate([thresh[0, :], thresh[-1, :], thresh[:, 0], thresh[:, -1]])
        bg_is_white = np.mean(edge_pixels) > 127
        
        # 글자 픽셀 마스크
        if bg_is_white:
            text_mask = (thresh == 0)
        else:
            text_mask = (thresh == 255)
            
        text_pixels = roi_rgb[text_mask]
        
        if len(text_pixels) < 10:
            # 픽셀이 부족하면 중앙 영역 샘플링
            text_pixels = roi_rgb.reshape(-1, 3)

        # K-Means 클러스터링으로 대표 글자색 추출
        try:
            kmeans = KMeans(n_clusters=min(self.n_clusters, len(text_pixels)), n_init=3, random_state=42)
            kmeans.fit(text_pixels)
            # 가장 빈도가 높은 클러스터가 메인 글자색
            labels, counts = np.unique(kmeans.labels_, return_counts=True)
            dominant_cluster = labels[np.argmax(counts)]
            dominant_rgb = tuple(map(int, kmeans.cluster_centers_[dominant_cluster]))
        except Exception:
            dominant_rgb = tuple(map(int, np.median(text_pixels, axis=0)))

        # 경계값 클램핑 (0~255)
        r = max(0, min(255, dominant_rgb[0]))
        g = max(0, min(255, dominant_rgb[1]))
        b = max(0, min(255, dominant_rgb[2]))
        
        hex_code = self.rgb_to_hex((r, g, b))
        return (r, g, b), hex_code
