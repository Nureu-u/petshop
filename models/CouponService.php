<?php
declare(strict_types=1);

/**
 * Áp dụng mã giảm giá khi checkout (TV4). Chỉ ĐỌC coupons + GHI coupon_usages.
 * CRUD coupon trong admin thuộc TV5 (đặt ở model khác để tránh xung đột file).
 */
final class CouponService
{
    /**
     * @param bool $lock true khi gọi trong transaction tạo đơn (khóa dòng coupon, chống dùng vượt lượt)
     * @return array{0: ?array, 1: ?string, 2: int} [coupon|null, lỗi|null, số tiền giảm]
     *         Không nhập mã => [null, null, 0]
     */
    public static function evaluate(string $code, string $userId, int $subtotal, bool $lock = false): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return [null, null, 0];
        }

        $st = Database::pdo()->prepare('SELECT * FROM coupons WHERE code = :c' . ($lock ? ' FOR UPDATE' : ''));
        $st->execute(['c' => $code]);
        $cp = $st->fetch();

        if (!$cp) {
            return [null, 'Mã giảm giá không tồn tại.', 0];
        }
        $now = date('Y-m-d H:i:s');
        if ($cp['status'] !== 'active' || $now < $cp['start_at'] || $now > $cp['end_at']) {
            return [null, 'Mã giảm giá đã hết hạn hoặc chưa có hiệu lực.', 0];
        }
        if ($cp['usage_limit'] !== null && (int) $cp['used_count'] >= (int) $cp['usage_limit']) {
            return [null, 'Mã giảm giá đã hết lượt sử dụng.', 0];
        }
        if ($subtotal < (int) $cp['min_order_amount']) {
            return [null, 'Đơn hàng chưa đạt giá trị tối thiểu ' . format_price($cp['min_order_amount']) . '.', 0];
        }

        $st = Database::pdo()->prepare('SELECT 1 FROM coupon_usages WHERE coupon_id = :c AND user_id = :u');
        $st->execute(['c' => $cp['id'], 'u' => $userId]);
        if ($st->fetchColumn()) {
            return [null, 'Bạn đã sử dụng mã giảm giá này rồi.', 0];
        }

        if ($cp['discount_type'] === 'percent') {
            $discount = (int) floor($subtotal * (int) $cp['discount_value'] / 100);
            if ($cp['max_discount_amount'] !== null) {
                $discount = min($discount, (int) $cp['max_discount_amount']);
            }
        } else {
            $discount = (int) $cp['discount_value'];
        }
        $discount = max(0, min($discount, $subtotal)); // CHECK của orders: discount <= subtotal

        return [$cp, null, $discount];
    }

    /** Ghi nhận đã dùng (trong transaction tạo đơn). */
    public static function consume(array $coupon, string $userId, string $orderId, int $discount): void
    {
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO coupon_usages (id, coupon_id, user_id, order_id, discount_amount)
             VALUES (:id, :c, :u, :o, :d)'
        )->execute([
            'id' => IdGenerator::next(IdGenerator::COUPON_USAGE), 'c' => $coupon['id'],
            'u' => $userId, 'o' => $orderId, 'd' => $discount,
        ]);
        $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :c')->execute(['c' => $coupon['id']]);
    }

    /** Hoàn lượt dùng khi hủy đơn. */
    public static function release(string $orderId): void
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT coupon_id FROM coupon_usages WHERE order_id = :o');
        $st->execute(['o' => $orderId]);
        $couponId = $st->fetchColumn();
        if ($couponId) {
            $pdo->prepare('DELETE FROM coupon_usages WHERE order_id = :o')->execute(['o' => $orderId]);
            $pdo->prepare('UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE id = :c')
                ->execute(['c' => $couponId]);
        }
    }
}
