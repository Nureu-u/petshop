-- TV4: chạy SAU schema.sql, patch_id_sequences.sql, seed.sql.
USE petshop_db;

-- 1) Cho phép thanh toán COD và SEPAY (chuyển khoản QR). Giữ 'VNPAY' trong danh sách để các dòng cũ (đơn test trước đây) không vi phạm ràng buộc.
ALTER TABLE payments DROP CONSTRAINT chk_payments_method;
ALTER TABLE payments ADD CONSTRAINT chk_payments_method CHECK (payment_method IN ('VNPAY', 'COD', 'SEPAY'));

-- 2) Nhật ký giao dịch (Transaction): mỗi lần tạo / return / IPN / đổi trạng thái đều ghi lại để đối soát
CREATE TABLE IF NOT EXISTS payment_transactions (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    payment_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    event VARCHAR(30) NOT NULL,          -- CREATED | RETURN | IPN | PAID | FAILED | CANCELLED | REFUNDED
    raw_data TEXT NULL,                  -- JSON dữ liệu nhận/gửi (không chứa secret)
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_ptx_payment (payment_id),
    CONSTRAINT fk_ptx_payment
        FOREIGN KEY (payment_id) REFERENCES payments(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
