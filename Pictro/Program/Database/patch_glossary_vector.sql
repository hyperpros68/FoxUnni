-- ================================================================================
-- Pictro [translation_glossary_db] 문맥(Context) 및 임베딩 벡터 컬럼 추가 패치
-- ================================================================================

-- 1. context_text: 사용자가 교정했을 당시의 배너 문장/주변 문맥 (최대 512자)
-- 2. context_vector: 문맥의 임베딩 벡터 JSON 배열 (예: [0.012, -0.045, ...])
-- 3. context_hash: 동일 문맥 중복 방지용 SHA-256 해시 (CHAR 64)

ALTER TABLE `translation_glossary_db`
    ADD COLUMN `context_text` VARCHAR(512) DEFAULT NULL COMMENT '교정 당시 배너 주변 문맥 문장' AFTER `corrected_term`,
    ADD COLUMN `context_vector` JSON DEFAULT NULL COMMENT '문맥 임베딩 벡터 (Float Array JSON)' AFTER `context_text`,
    ADD COLUMN `context_hash` CHAR(64) DEFAULT NULL COMMENT '문맥 SHA256 해시' AFTER `context_vector`,
    ADD KEY `idx_glossary_vector_search` (`target_lang`, `category`, `context_hash`);
