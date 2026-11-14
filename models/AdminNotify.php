<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: THONG BAO TRONG CHUONG
   File    : models/AdminNotify.php
   Bang doc / ghi: coupons, orders, payments, posts, products
   Duoc dung boi: views/admin/layout/header.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Thong bao "viec can xu ly" cho chuong tren thanh dau trang (tinh truc tiep tu DB, khong luu trang thai da doc). */
final class AdminNotify
{
    /** @return array<int,array{0:string,1:int,2:string,3:string}> [khoa lang, so luong, duong dan, mau] */
    public static function items(): array
    {
        try {
            $pdo = Database::pdo();
            $one = fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();
            $rows = [
                ['orders_confirmed', $one("SELECT COUNT(*) FROM orders WHERE status = 'CONFIRMED'"),
                    url('admin/orders', ['status' => 'CONFIRMED']), 'bl'],
                ['payments_failed', $one("SELECT COUNT(*) FROM payments WHERE status = 'FAILED' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
                    url('admin/orders', ['pay' => 'FAILED']), 'rd'],
                ['stock_low', $one("SELECT COUNT(*) FROM products WHERE status = 'active' AND stock <= 5"),
                    url('admin/manage/products'), 'y'],
                ['coupons_expiring', $one("SELECT COUNT(*) FROM coupons WHERE status = 'active' AND end_at >= NOW() AND end_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)"),
                    url('admin/coupons'), 'pu'],
                ['posts_draft', $one("SELECT COUNT(*) FROM posts WHERE status = 'draft'"),
                    url('admin/posts', ['status' => 'draft']), 'gy'],
            ];
            return array_values(array_filter($rows, fn(array $r): bool => $r[1] > 0));
        } catch (Throwable $e) {
            return [];
        }
    }
}
