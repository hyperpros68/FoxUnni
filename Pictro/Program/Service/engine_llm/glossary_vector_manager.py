# -*- coding: utf-8 -*-
"""
Pictro [glossary_vector_manager.py]
사용자 교정 용어사전 문맥(Context) 임베딩 벡터화 및 코사인 유사도 매칭 모듈
- SRP: 문맥 임베딩 벡터 생성 및 코사인 유사도 계산 / DB 매칭 전담
"""
import hashlib
import json
import math
import re
from typing import List, Dict, Any, Optional

try:
    # sentence-transformers 설치 시 고정밀 Dense 임베딩 사용
    from sentence_transformers import SentenceTransformer
    _TRANSFORMER_MODEL = SentenceTransformer("paraphrase-multilingual-MiniLM-L12-v2")
except Exception:
    _TRANSFORMER_MODEL = None


class GlossaryVectorManager:
    """문맥 임베딩 생성 및 코사인 유사도 매칭 관리자"""

    @staticmethod
    def calculate_hash(text: str) -> str:
        """문맥 텍스트 고유 SHA256 해시 생성"""
        norm_text = re.sub(r"\s+", " ", (text or "").strip().lower())
        return hashlib.sha256(norm_text.encode("utf-8")).hexdigest()

    @classmethod
    def get_embedding(cls, text: str) -> List[float]:
        """
        문맥 문장의 임베딩 벡터 생성
        1) SentenceTransformer 모델 로드 가능 시 384차원 고품질 벡터 생성
        2) 폴백: 단어/음절 N-Gram 해시 기반 128차원 L2 정규화 벡터 생성 (외부 의존성 제로)
        """
        if not text:
            return []

        clean_text = re.sub(r"\s+", " ", text.strip())

        # 1. 딥러닝 임베딩
        if _TRANSFORMER_MODEL is not None:
            try:
                emb = _TRANSFORMER_MODEL.encode(clean_text)
                return [round(float(v), 5) for v in emb.tolist()]
            except Exception:
                pass

        # 2. 경량 하이브리드 N-Gram 정규화 임베딩 (Fallback: 128차원)
        dim = 128
        vec = [0.0] * dim
        # 단어 및 음절 단위 토큰 추출
        tokens = re.findall(r"[\w가-힣]+", clean_text.lower())
        for token in tokens:
            idx = int(hashlib.md5(token.encode("utf-8")).hexdigest(), 16) % dim
            vec[idx] += 1.0
            # 2-gram 음절
            for i in range(len(token) - 1):
                bi = token[i:i+2]
                bidx = int(hashlib.md5(bi.encode("utf-8")).hexdigest(), 16) % dim
                vec[bidx] += 0.5

        # L2 정규화 (Cosine Similarity 계산 최적화)
        norm = math.sqrt(sum(x * x for x in vec))
        if norm > 0:
            vec = [round(x / norm, 5) for x in vec]
        return vec

    @staticmethod
    def cosine_similarity(vec_a: List[float], vec_b: List[float]) -> float:
        """두 벡터 간의 코사인 유사도 (-1.0 ~ 1.0) 계산"""
        if not vec_a or not vec_b:
            return 0.0
        min_len = min(len(vec_a), len(vec_b))
        dot_product = sum(vec_a[i] * vec_b[i] for i in range(min_len))
        # 이미 L2 정규화되어 있는 경우 dot_product 자체가 cosine similarity
        norm_a = math.sqrt(sum(vec_a[i] * vec_a[i] for i in range(min_len)))
        norm_b = math.sqrt(sum(vec_b[i] * vec_b[i] for i in range(min_len)))
        if norm_a == 0 or norm_b == 0:
            return 0.0
        return round(dot_product / (norm_a * norm_b), 4)

    @classmethod
    def match_relevant_glossary(
        cls,
        target_context: str,
        glossary_items: List[Dict[str, Any]],
        threshold: float = 0.45
    ) -> List[Dict[str, Any]]:
        """
        현재 번역 대상 문맥과 저장된 교정 사전 항목들 간 유사도 매칭
        - threshold 이상의 가장 유사한 항목들을 내림차순 정렬 반환
        """
        if not target_context or not glossary_items:
            return []

        target_vec = cls.get_embedding(target_context)
        matched_items = []

        for item in glossary_items:
            # 1. 저장된 벡터 추출 (JSON string or list)
            item_vec = item.get("context_vector")
            if isinstance(item_vec, str):
                try:
                    item_vec = json.loads(item_vec)
                except Exception:
                    item_vec = None

            if not item_vec:
                # 벡터가 없으면 해당 항목 문맥 텍스트로 즉석 생성
                saved_ctx = item.get("context_text") or item.get("source_term", "")
                item_vec = cls.get_embedding(saved_ctx)

            similarity = cls.cosine_similarity(target_vec, item_vec)
            
            # 원문 키워드가 직접 문맥에 포함되어 있다면 가산점(+0.2)
            source_term = item.get("source_term", "")
            if source_term and source_term in target_context:
                similarity = min(1.0, round(similarity + 0.2, 4))

            if similarity >= threshold:
                matched = dict(item)
                matched["similarity"] = similarity
                matched_items.append(matched)

        # 유사도 높은 순 정렬
        matched_items.sort(key=lambda x: x["similarity"], reverse=True)
        return matched_items
