<?php
declare(strict_types=1);

/**
 * Đơn hàng (TV4). Bảng: orders, order_items (+ payments, coupon_usages qua Payment / CouponService).
 * Tạo đơn chạy trong 1 transaction: khóa hàng -> trừ kho -> ghi đơn -> dùng coupon -> xóa giỏ.
 * Mọi số tiền được TÍNH LẠI ở server từ giá trong DB; form (subtotal, discount, total) bị bỏ qua.
 */
final class Order
{
    public const STATUSES = ['PENDING_PAYMENT', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED'];
    /** Trạng thái khách còn được tự hủy. */
    private const CANCELLABLE = ['PENDING_PAYMENT', 'CONFIRMED'];

    public static function shippingFee(): int
    {
        return (int) config('shipping_fee', 50000);
    }

    /** @return array{0: array, 1: array} [errors, data] */
    public static function validate(array $in): array
    {
        $errors = [];
        $name    = trim((string) ($in['customer_name'] ?? ''));
        $phone   = trim((string) ($in['customer_phone'] ?? ''));
        $address = trim((string) ($in['shipping_address'] ?? ''));
        $note    = trim((string) ($in['note'] ?? ''));
        $method  = strtoupper(trim((string) ($in['payment_method'] ?? '')));
        $coupon  = strtoupper(trim((string) ($in['coupon_code'] ?? '')));

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['customer_name'] = 'Họ tên bắt buộc, tối đa 100 ký tự.';
        }
        if (!preg_match('/^(\+84|0)\d{9,10}$/', str_replace([' ', '.', '-'], '', $phone))) {
            $errors['customer_phone'] = 'Số điện thoại không hợp lệ.';
        }
        if ($address === '' || mb_strlen($address) > 255) {
            $errors['shipping_address'] = 'Địa chỉ nhận hàng bắt buộc, tối đa 255 ký tự.';
        }
        if (mb_strlen($note) > 500) {
            $errors['note'] = 'Ghi chú tối đa 500 ký tự.';
        }
        if (!in_array($method, Payment::METHODS, true)) {
            $errors['payment_method'] = 'Vui lòng chọn phương thức thanh toán.';
        }
        if (mb_strlen($coupon) > 50) {
            $errors['coupon_code'] = 'Mã giảm giá không hợp lệ.';
        }

        return [$errors, [
            'customer_name' => $name, 'customer_phone' => str_replace([' ', '.', '-'], '', $phone),
            'shipping_address' => $address, 'note' => $note !== '' ? $note : null,
            'payment_method' => $method, 'coupon_code' => $coupon,
        ]];
    }

    /**
     * Tạo đơn từ giỏ hàng của $userId.
     * @return array{order_id: ?string, error: ?string}
     */
    public static function createFromCart(string $userId, array $d): array
    {
        $items = Cart::items($userId);
        if (!$items) {
            return ['order_id' => null, 'error' => 'Giỏ hàng đang trống.'];
        }
        foreach ($items as $it) {
            if (!$it['available']) {
                return ['order_id' => null, 'error' => '"' . $it['name'] . '": ' . $it['warning'] . ' Vui lòng xóa khỏi giỏ.'];
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 1) Giữ hàng: trừ kho / đánh dấu đã bán (nguyên tử; thất bại => có người mua trước)
            foreach ($items as $it) {
                $ok = $it['item_type'] === 'pet'
                    ? Pet::markSold($it['item_id'])
                    : Product::decreaseStock($it['item_id'], $it['quantity']);
                if (!$ok) {
                    $pdo->rollBack();
                    return ['order_id' => null, 'error' => '"' . $it['name'] . '" vừa hết hàng hoặc có người đặt trước. Vui lòng kiểm tra lại giỏ hàng.'];
                }
            }

            // 2) Tính tiền ở server
            $subtotal = Cart::subtotal($items);
            [$coupon, $couponErr, $discount] = CouponService::evaluate($d['coupon_code'], $userId, $subtotal, true);
            if ($couponErr !== null) {
                $pdo->rollBack();
                return ['order_id' => null, 'error' => $couponErr];
            }
            $shipping = self::shippingFee();
            $total = $subtotal - $discount + $shipping;

            // 3) Ghi đơn
            $orderId = IdGenerator::next(IdGenerator::ORDER);
            $orderCode = 'PS-' . date('Ymd') . '-' . str_pad((string) ((int) substr($orderId, 3)), 3, '0', STR_PAD_LEFT);
            // SEPAY (chuyển khoản QR): chờ thanh toán. COD: xác nhận ngay (thu tiền khi giao).
            $status = $d['payment_method'] === 'SEPAY' ? 'PENDING_PAYMENT' : 'CONFIRMED';

            $pdo->prepare(
                'INSERT INTO orders (id, order_code, user_id, customer_name, customer_phone, shipping_address,
                                     subtotal, discount_amount, shipping_fee, total_amount, status, note)
                 VALUES (:id, :code, :u, :n, :ph, :ad, :sub, :dis, :ship, :tot, :st, :note)'
            )->execute([
                'id' => $orderId, 'code' => $orderCode, 'u' => $userId, 'n' => $d['customer_name'],
                'ph' => $d['customer_phone'], 'ad' => $d['shipping_address'], 'sub' => $subtotal,
                'dis' => $discount, 'ship' => $shipping, 'tot' => $total, 'st' => $status, 'note' => $d['note'],
            ]);

            $ins = $pdo->prepare(
                'INSERT INTO order_items (id, order_id, pet_id, product_id, item_name, unit_price, quantity, line_total)
                 VALUES (:id, :o, :pet, :prd, :n, :p, :q, :lt)'
            );
            foreach ($items as $it) {
                $ins->execute([
                    'id' => IdGenerator::next(IdGenerator::ORDER_ITEM), 'o' => $orderId,
                    'pet' => $it['item_type'] === 'pet' ? $it['item_id'] : null,
                    'prd' => $it['item_type'] === 'product' ? $it['item_id'] : null,
                    'n' => mb_substr($it['name'], 0, 150), 'p' => $it['unit_price'],
                    'q' => $it['quantity'], 'lt' => $it['line_total'],
                ]);
            }

            if ($coupon) {
                CouponService::consume($coupon, $userId, $orderId, $discount);
            }
            Payment::createForOrder($orderId, $d['payment_method'], $total);
            Cart::clear($userId);

            $pdo->commit();
            return ['order_id' => $orderId, 'error' => null];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Tự hủy các đơn thanh toán online (SEPAY) quá hạn chưa thanh toán của khách (hoàn kho / thú cưng / coupon).
     * Hạn = order_hold_minutes (mặc định 30) tính từ lần thanh toán gần nhất; gọi khi khách mở lịch sử đơn.
     * @return int số đơn đã hủy
     */
    public static function expireUnpaid(string $userId): int
    {
        $minutes = max(1, (int) config('order_hold_minutes', 30));
        $st = Database::pdo()->prepare(
            "SELECT o.id FROM orders o
             WHERE o.user_id = :u AND o.status = 'PENDING_PAYMENT'
               AND COALESCE((SELECT MAX(p.created_at) FROM payments p WHERE p.order_id = o.id), o.created_at)
                   < (NOW() - INTERVAL $minutes MINUTE)"
        );
        $st->execute(['u' => $userId]);
        $n = 0;
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            if (self::cancel((string) $id, $userId, 'Quá hạn thanh toán') === null) {
                $n++;
            }
        }
        return $n;
    }

    public static function find(string $orderId, ?string $userId = null): ?array
    {
        $sql = 'SELECT * FROM orders WHERE id = :id';
        $p = ['id' => $orderId];
        if ($userId !== null) {
            $sql .= ' AND user_id = :u';
            $p['u'] = $userId;
        }
        $st = Database::pdo()->prepare($sql);
        $st->execute($p);
        return $st->fetch() ?: null;
    }

    /** Lịch sử đơn của khách kèm mặt hàng & thanh toán (tránh N+1 truy vấn). */
    public static function historyForUser(string $userId): array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM orders WHERE user_id = :u ORDER BY created_at DESC, id DESC');
        $st->execute(['u' => $userId]);
        $orders = $st->fetchAll();
        if (!$orders) {
            return [];
        }
        $ids = array_column($orders, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));

        $st = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($in) ORDER BY id");
        $st->execute($ids);
        $items = [];
        foreach ($st->fetchAll() as $r) {
            $items[$r['order_id']][] = $r;
        }

        $st = $pdo->prepare("SELECT * FROM payments WHERE order_id IN ($in) ORDER BY created_at, id");
        $st->execute($ids);
        $pay = [];
        foreach ($st->fetchAll() as $r) {
            $pay[$r['order_id']] = $r; // bản ghi mới nhất ghi đè
        }

        foreach ($orders as &$o) {
            $o['items'] = $items[$o['id']] ?? [];
            $o['payment'] = $pay[$o['id']] ?? null;
        }
        return $orders;
    }

    /**
     * Khách tự hủy đơn: hoàn kho, hoàn thú cưng, hoàn coupon, đóng thanh toán chưa hoàn tất.
     * @return ?string null nếu thành công, ngược lại là thông báo lỗi
     */
    public static function cancel(string $orderId, string $userId, string $reason = 'Khách hủy đơn'): ?string
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :u FOR UPDATE');
            $st->execute(['id' => $orderId, 'u' => $userId]);
            $order = $st->fetch();
            if (!$order) {
                $pdo->rollBack();
                return 'Không tìm thấy đơn hàng.';
            }
            if (!in_array($order['status'], self::CANCELLABLE, true)) {
                $pdo->rollBack();
                return 'Đơn hàng đang giao hoặc đã hoàn tất/hủy, không thể hủy.';
            }
            $pay = Payment::latestByOrder($orderId);
            if ($pay && $pay['status'] === 'PAID') {
                $pdo->rollBack();
                return 'Đơn đã thanh toán online, vui lòng liên hệ cửa hàng để được hoàn tiền.';
            }

            $st = $pdo->prepare('SELECT pet_id, product_id, quantity FROM order_items WHERE order_id = :o');
            $st->execute(['o' => $orderId]);
            foreach ($st->fetchAll() as $it) {
                if ($it['pet_id'] !== null) {
                    Pet::markAvailable($it['pet_id']);
                } else {
                    Product::increaseStock($it['product_id'], (int) $it['quantity']);
                }
            }

            CouponService::release($orderId);
            $pdo->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = :id")->execute(['id' => $orderId]);
            if ($pay && $pay['status'] === 'PENDING') {
                $pdo->prepare("UPDATE payments SET status = 'FAILED' WHERE id = :id")->execute(['id' => $pay['id']]);
                Payment::log($pay['id'], 'CANCELLED', ['reason' => $reason]);
            }

            $pdo->commit();
            return null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
