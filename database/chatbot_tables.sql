-- ==============================================================================
-- CHATBOT (TV5): 2 bang rieng, khong sua bang nao cua TV3.
-- Dan vao cuoi database/schema.sql (hoac chay rieng trong phpMyAdmin, chay lai nhieu lan khong loi).
-- ==============================================================================

-- Kho tri thuc: thu cung, san pham, danh muc, bai viet (tu dong) va cau hoi thuong gap (admin viet)
CREATE TABLE IF NOT EXISTS chatbot_knowledge (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_type VARCHAR(20) NOT NULL,          -- pet | product | category | post | faq
    source_key VARCHAR(40) NULL,               -- ma ben bang goc (faq thi de trong)
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    link VARCHAR(255) NULL,
    meta TEXT NULL,                            -- du lieu dang bang (JSON) de hien thi thu cung / san pham
    embedding LONGTEXT NULL,                   -- vector Gemini dang JSON
    content_hash CHAR(32) NOT NULL,            -- doi noi dung thi moi nhung vector lai
    status VARCHAR(10) NOT NULL DEFAULT 'active',   -- active | hidden
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_chatbot_source (source_type, source_key),
    KEY idx_chatbot_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nhat ky hoi dap (admin xem lai o trang Chatbot tu van)
CREATE TABLE IF NOT EXISTS chatbot_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_key CHAR(32) NOT NULL,
    user_id VARCHAR(12) NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    mode VARCHAR(10) NOT NULL,                 -- ai | fallback
    top_titles VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chatbot_msg_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
