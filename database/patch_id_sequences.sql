-- Chạy MỘT LẦN sau khi tạo schema. Bảng này giúp sinh ID VARCHAR(12) an toàn khi nhiều request đồng thời.
USE petshop_db;

CREATE TABLE IF NOT EXISTS id_sequences (
    prefix     VARCHAR(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    last_value INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (prefix)
) ENGINE=InnoDB;
