<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: DON HANG
   File    : models/AdminOrder.php
   Bang doc / ghi: coupon_usages, coupons, order_items, orders, payments, users
   Duoc dung boi: controllers/AdminOrderController.php, models/AdminDashboard.php,
               views/admin/dashboard/index.php, views/admin/orders/index.php,
               views/admin/orders/show.php, views/admin/search/index.php,
               views/admin/users/show.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Truy van don hang cho khu quan tri (TV5). Chi doc dung cot trong schema cua TV3. */
final class AdminOrder
{
    public const STATUSES = ['PENDING_PAYMENT', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED'];
    public const PAY_STATUSES = ['PENDING', 'PAID', 'FAILED', 'REFUNDED'];

    /** Cac buoc admin duoc phep chuyen. Don cho thanh toan chi tu xac nhan khi VNPAY bao thanh cong (TV4). */
    public const NEXT = [
        'PENDING_PAYMENT' => ['CANCELLED'],
        'CONFIRMED'       => ['SHIPPING', 'CANCELLED'],
        'SHIPPING'        => ['DELIVERED'],
        'DELIVERED'       => [],
        'CANCELLED'       => [],
    ];

    /** Lay giao dich thanh toan moi nhat cua moi don. */
    private const PAY_JOIN = 'LEFT JOIN payments p ON p.id = (SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.created_at DESC, p2.id DESC LIMIT 1)';

    public static function statusClass(string $s): string
    {
        return ['PENDING_PAYMENT' => 'y', 'CONFIRMED' => 'bl', 'SHIPPING' => 'pu', 'DELIVERED' => 'gr', 'CANCELLED' => 'rd'][$s] ?? 'gy';
    }

    public static function payClass(?string $s): string
    {
        return ['PENDING' => 'y', 'PAID' => 'gr', 'FAILED' => 'rd', 'REFUNDED' => 'gy'][(string) $s] ?? 'gy';
    }

    public static function payKey(?string $s): string
    {
        return 'pay.status.' . ($s ?: 'NONE');
    }

    /** @return array<string,int> so don theo tung trang thai */
    public static function statusCounts(): array
    {
        $rows = Database::pdo()->query('SELECT status, COUNT(*) FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map('intval', $rows);
    }

    /** $f: status, q, pay (PENDING|PAID|FAILED|REFUNDED|NONE), from, to (Y-m-d) */
    public static function search(array $f, int $page = 1, int $perPage = 10): array
    {
        $where = [];
        $p = [];

        $status = (string) ($f['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'o.status = :st';
            $p['st'] = $status;
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(o.order_code LIKE :q1 OR o.customer_name LIKE :q2 OR o.customer_phone LIKE :q3)';
            $p['q1'] = $p['q2'] = $like;
            $p['q3'] = phone_like($q);
        }
        $pay = (string) ($f['pay'] ?? '');
        if ($pay === 'NONE') {
            $where[] = 'p.id IS NULL';
        } elseif (in_array($pay, self::PAY_STATUSES, true)) {
            $where[] = 'p.status = :pay';
            $p['pay'] = $pay;
        }
        $dateFrom = (string) ($f['from'] ?? '');
        if ($dateFrom !== '' && is_valid_date($dateFrom)) {
            $where[] = 'o.created_at >= :df';
            $p['df'] = $dateFrom . ' 00:00:00';
        }
        $dateTo = (string) ($f['to'] ?? '');
        if ($dateTo !== '' && is_valid_date($dateTo)) {
            $where[] = 'o.created_at <= :dt';
            $p['dt'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $fromSql = 'FROM orders o ' . self::PAY_JOIN;

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) $fromSql $whereSql");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT o.*, p.status AS pay_status $fromSql $whereSql
             ORDER BY o.created_at DESC, o.id DESC LIMIT $perPage OFFSET $offset"
        );
        $st->execute($p);

        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $perPage];
    }

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, u.email AS user_email FROM orders o
             LEFT JOIN users u ON u.id = o.user_id WHERE o.id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public static function items(string $orderId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
        $st->execute(['id' => $orderId]);
        return $st->fetchAll();
    }

    public static function payments(string $orderId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY created_at DESC, id DESC');
        $st->execute(['id' => $orderId]);
        return $st->fetchAll();
    }

    /** Ma giam gia da dung cho don (neu co). */
    public static function coupon(string $orderId): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT c.code, cu.discount_amount FROM coupon_usages cu
             JOIN coupons c ON c.id = cu.coupon_id WHERE cu.order_id = :id'
        );
        $st->execute(['id' => $orderId]);
        return $st->fetch() ?: null;
    }

    /**
     * Doi trang thai don theo luat NEXT.
     * Huy don: hoan lai ton kho san pham, dua thu cung ve "Con ban" (cung mot transaction).
     * Khong dung bang payments (cua TV4).
     * @return array{ok:bool,msg:string,refund:bool}
     */
    public static function changeStatus(string $id, string $new): array
    {
        if (!in_array($new, self::STATUSES, true)) {
            return ['ok' => false, 'msg' => 'order.msg.invalid', 'refund' => false];
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, status FROM orders WHERE id = :id FOR UPDATE');
            $st->execute(['id' => $id]);
            $o = $st->fetch();
            if (!$o) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'order.msg.notfound', 'refund' => false];
            }
            if (!in_array($new, self::NEXT[$o['status']] ?? [], true)) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'order.msg.invalid', 'refund' => false];
            }

            $refund = false;
            if ($new === 'CANCELLED') {
                foreach (self::items($id) as $it) {
                    if (!empty($it['pet_id'])) {
                        Pet::markAvailable($it['pet_id']);
                    } elseif (!empty($it['product_id'])) {
                        Product::increaseStock($it['product_id'], (int) $it['quantity']);
                    }
                }
                $c = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE order_id = :id AND status = 'PAID'");
                $c->execute(['id' => $id]);
                $refund = (int) $c->fetchColumn() > 0;
            }

            $pdo->prepare('UPDATE orders SET status = :s WHERE id = :id')->execute(['s' => $new, 'id' => $id]);
            $pdo->commit();
            return ['ok' => true, 'msg' => 'order.msg.updated', 'refund' => $refund];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
