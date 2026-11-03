<?php
declare(strict_types=1);

/**
 * Sinh ID dạng VARCHAR(12): PREFIX + số tuần tự, đệm 0 cho đủ 12 ký tự.
 * Ví dụ: USR000000001, PET000000042.
 *
 * An toàn khi nhiều request đồng thời: dùng bảng id_sequences và
 * LAST_INSERT_ID(expr) nên mỗi lần gọi nhận một số duy nhất.
 * (Cần chạy database/patch_id_sequences.sql một lần.)
 *
 * Dùng chung cho cả nhóm: IdGenerator::next(IdGenerator::ORDER)
 */
final class IdGenerator
{
    public const USER             = 'USR';
    public const RESET_TOKEN      = 'PRT';
    public const PET_CATEGORY     = 'PCT';
    public const PET              = 'PET';
    public const PRODUCT_CATEGORY = 'PDC';
    public const PRODUCT          = 'PRD';
    public const CART             = 'CRT';
    public const CART_ITEM        = 'CIT';
    public const COUPON           = 'CPN';
    public const COUPON_USAGE     = 'CPU';
    public const ORDER            = 'ORD';
    public const ORDER_ITEM       = 'OIT';
    public const PAYMENT          = 'PAY';
    public const REVIEW           = 'REV';
    public const REVIEW_IMAGE     = 'RVI';
    public const POST_CATEGORY    = 'PCA';
    public const POST             = 'PST';

    public static function next(string $prefix): string
    {
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO id_sequences (prefix, last_value) VALUES (:p, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_value = LAST_INSERT_ID(last_value + 1)'
        )->execute(['p' => $prefix]);

        $n = (int) $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        $digits = 12 - strlen($prefix);
        if (strlen((string) $n) > $digits) {
            throw new RuntimeException("Hết dải ID cho tiền tố $prefix");
        }
        return $prefix . str_pad((string) $n, $digits, '0', STR_PAD_LEFT);
    }
}
