<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: SO LIEU TRANG TONG QUAN
   File    : models/AdminDashboard.php
   Bang doc / ghi: coupons, order_items, orders, payments, pet_categories, pets,
                product_categories, products, users
   Duoc dung boi: controllers/AdminDashboardController.php, views/admin/dashboard/index.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** So lieu cho trang Tong quan (TV5). Chi doc dung bang va cot trong schema cua TV3. */
final class AdminDashboard
{
    public const PERIODS = ['today', '7d', '30d', 'year'];
    private const PAID_AT = 'COALESCE(vnp_paid_at, created_at)';

    /** Khoang thoi gian "ky nay" va "ky truoc" (cung do dai, nam ngay truoc do). */
    public static function range(string $key): array
    {
        if (!in_array($key, self::PERIODS, true)) {
            $key = '7d';
        }
        $today = new DateTimeImmutable('today');
        switch ($key) {
            case 'today':
                $cs = $today; $ce = $today;
                $ps = $today->modify('-1 day'); $pe = $ps;
                $unit = 'hour';
                break;
            case '30d':
                $cs = $today->modify('-29 days'); $ce = $today;
                $ps = $today->modify('-59 days'); $pe = $today->modify('-30 days');
                $unit = 'day';
                break;
            case 'year':
                $cs = new DateTimeImmutable($today->format('Y') . '-01-01'); $ce = $today;
                $ps = $cs->modify('-1 year'); $pe = $today->modify('-1 year');
                $unit = 'month';
                break;
            default:
                $key = '7d';
                $cs = $today->modify('-6 days'); $ce = $today;
                $ps = $today->modify('-13 days'); $pe = $today->modify('-7 days');
                $unit = 'day';
        }
        return ['key' => $key, 'unit' => $unit, 'cs' => $cs, 'ce' => $ce, 'ps' => $ps, 'pe' => $pe];
    }

    private static function bounds(DateTimeImmutable $a, DateTimeImmutable $b): array
    {
        return [$a->format('Y-m-d') . ' 00:00:00', $b->format('Y-m-d') . ' 23:59:59'];
    }

    public static function label(DateTimeImmutable $a, DateTimeImmutable $b): string
    {
        if ($a->format('Y-m-d') === $b->format('Y-m-d')) {
            return $a->format('d/m/Y');
        }
        return $a->format('d/m') . ' – ' . $b->format('d/m/Y');
    }

    /** % thay doi so voi ky truoc; null neu ky truoc bang 0. */
    public static function pct(float $cur, float $prev): ?int
    {
        if ($prev <= 0) {
            return null;
        }
        return (int) round(($cur - $prev) / $prev * 100);
    }

    private static function scalar(string $sql, array $p = []): int
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($p);
        return (int) $st->fetchColumn();
    }

    private static function revenue(array $b): int
    {
        return self::scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'PAID' AND " . self::PAID_AT . ' BETWEEN :a AND :b',
            ['a' => $b[0], 'b' => $b[1]]
        );
    }

    private static function sumBy(string $unit, array $b): array
    {
        $x = self::PAID_AT;
        $expr = $unit === 'hour' ? "HOUR($x)" : ($unit === 'month' ? "DATE_FORMAT($x, '%Y-%m')" : "DATE($x)");
        $st = Database::pdo()->prepare(
            "SELECT $expr AS k, SUM(amount) AS s FROM payments WHERE status = 'PAID' AND $x BETWEEN :a AND :b GROUP BY k"
        );
        $st->execute(['a' => $b[0], 'b' => $b[1]]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Doanh thu theo gio / ngay / thang, ky nay va ky truoc. */
    public static function series(array $r): array
    {
        $unit = $r['unit'];
        $cm = self::sumBy($unit, self::bounds($r['cs'], $r['ce']));
        $pm = self::sumBy($unit, self::bounds($r['ps'], $r['pe']));
        $labels = [];
        $cur = [];
        $prev = [];
        if ($unit === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $cur[] = (int) ($cm[$h] ?? 0);
                $prev[] = (int) ($pm[$h] ?? 0);
                $labels[] = $h % 3 === 0 ? sprintf('%02d', $h) : '';
            }
        } elseif ($unit === 'month') {
            $y = (int) $r['ce']->format('Y');
            $lastM = (int) $r['ce']->format('n');
            for ($m = 1; $m <= $lastM; $m++) {
                $cur[] = (int) ($cm[sprintf('%d-%02d', $y, $m)] ?? 0);
                $prev[] = (int) ($pm[sprintf('%d-%02d', $y - 1, $m)] ?? 0);
                $labels[] = t('month.short', ['m' => $m]);
            }
        } else {
            $n = (int) $r['cs']->diff($r['ce'])->days + 1;
            for ($i = 0; $i < $n; $i++) {
                $d = $r['cs']->modify("+$i days");
                $p = $r['ps']->modify("+$i days");
                $cur[] = (int) ($cm[$d->format('Y-m-d')] ?? 0);
                $prev[] = (int) ($pm[$p->format('Y-m-d')] ?? 0);
                if ($n <= 7) {
                    $labels[] = t('dow.' . $d->format('N'));
                } else {
                    $labels[] = ($i % 5 === 0 || $i === $n - 1) ? $d->format('d/m') : '';
                }
            }
        }
        return ['labels' => $labels, 'cur' => $cur, 'prev' => $prev];
    }

    private static function niceStep(float $x): float
    {
        if ($x <= 0) {
            return 1.0;
        }
        $p = 10 ** floor(log10($x));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($x <= $m * $p) {
                return (float) ($m * $p);
            }
        }
        return (float) (10 * $p);
    }

    /** Doanh thu theo nhom hang (don da co thanh toan PAID trong ky). */
    public static function groups(array $b): array
    {
        $ex = "EXISTS (SELECT 1 FROM payments pm WHERE pm.order_id = o.id AND pm.status = 'PAID' AND COALESCE(pm.vnp_paid_at, pm.created_at) BETWEEN :%s AND :%s)";
        $sql = "SELECT 'pet' AS kind, c.name AS cat, SUM(oi.line_total) AS total
                FROM order_items oi JOIN orders o ON o.id = oi.order_id
                JOIN pets pe ON pe.id = oi.pet_id JOIN pet_categories c ON c.id = pe.category_id
                WHERE " . sprintf($ex, 'a1', 'b1') . " GROUP BY c.id, c.name
                UNION ALL
                SELECT 'product', c.name, SUM(oi.line_total)
                FROM order_items oi JOIN orders o ON o.id = oi.order_id
                JOIN products pr ON pr.id = oi.product_id JOIN product_categories c ON c.id = pr.category_id
                WHERE " . sprintf($ex, 'a2', 'b2') . " GROUP BY c.id, c.name
                ORDER BY total DESC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a1' => $b[0], 'b1' => $b[1], 'a2' => $b[0], 'b2' => $b[1]]);
        $all = $st->fetchAll();

        $pet = 0.0;
        $prod = 0.0;
        foreach ($all as $row) {
            if ($row['kind'] === 'pet') { $pet += (float) $row['total']; } else { $prod += (float) $row['total']; }
        }
        $sum = $pet + $prod;
        $rows = [];
        $other = 0.0;
        foreach ($all as $i => $row) {
            if ($i < 5) {
                $rows[] = ['kind' => $row['kind'], 'name' => $row['cat'], 'amount' => (float) $row['total']];
            } else {
                $other += (float) $row['total'];
            }
        }
        return [
            'rows' => $rows, 'other' => $other, 'total' => $sum,
            'petPct' => $sum > 0 ? $pet / $sum * 100 : 0.0,
            'prodPct' => $sum > 0 ? $prod / $sum * 100 : 0.0,
        ];
    }

    public static function best(array $b, string $kind): array
    {
        $col = $kind === 'pet' ? 'pet_id' : 'product_id';
        $st = Database::pdo()->prepare(
            "SELECT MAX(oi.item_name) AS name, SUM(oi.quantity) AS qty
             FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE oi.$col IS NOT NULL AND o.status <> 'CANCELLED' AND o.created_at BETWEEN :a AND :b
             GROUP BY oi.$col ORDER BY qty DESC, name LIMIT 4"
        );
        $st->execute(['a' => $b[0], 'b' => $b[1]]);
        return $st->fetchAll();
    }

    public static function overview(array $r, string $bestKind): array
    {
        $bc = self::bounds($r['cs'], $r['ce']);
        $bp = self::bounds($r['ps'], $r['pe']);
        $pdo = Database::pdo();

        $cnt = fn(string $sql, array $b) => self::scalar($sql, ['a' => $b[0], 'b' => $b[1]]);
        $ordSql = 'SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :a AND :b';
        $cusSql = "SELECT COUNT(*) FROM users WHERE role = 'customer' AND status <> 'deleted' AND created_at BETWEEN :a AND :b";

        $petStatus = array_map('intval', $pdo->query('SELECT status, COUNT(*) FROM pets GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR));

        $s = self::series($r);
        $max = max(array_merge([0], $s['cur'], $s['prev']));
        $step = $max > 0 ? self::niceStep($max / 3) : 1000000.0;
        $chart = $s + ['step' => $step, 'nice' => $step * 3];

        $stSt = $pdo->prepare('SELECT status, COUNT(*) FROM orders WHERE created_at BETWEEN :a AND :b GROUP BY status');
        $stSt->execute(['a' => $bc[0], 'b' => $bc[1]]);
        $statusCounts = array_map('intval', $stSt->fetchAll(PDO::FETCH_KEY_PAIR));

        $pySt = $pdo->prepare('SELECT status, COUNT(*) FROM payments WHERE created_at BETWEEN :a AND :b GROUP BY status');
        $pySt->execute(['a' => $bc[0], 'b' => $bc[1]]);
        $pay = array_map('intval', $pySt->fetchAll(PDO::FETCH_KEY_PAIR));

        $pc = $pdo->query(
            'SELECT c.name, COUNT(p.id) AS n FROM pet_categories c LEFT JOIN pets p ON p.category_id = c.id
             GROUP BY c.id, c.name ORDER BY n DESC, c.name'
        )->fetchAll();

        return [
            'period'      => $r['key'],
            'rangeCur'    => self::label($r['cs'], $r['ce']),
            'rangePrev'   => self::label($r['ps'], $r['pe']),
            'chips' => [
                'confirmed' => self::scalar("SELECT COUNT(*) FROM orders WHERE status = 'CONFIRMED'"),
                'failed'    => $cnt("SELECT COUNT(*) FROM payments WHERE status = 'FAILED' AND created_at BETWEEN :a AND :b", $bc),
                'stock'     => self::scalar("SELECT COUNT(*) FROM products WHERE status = 'active' AND stock <= 5"),
                'coupons'   => self::scalar("SELECT COUNT(*) FROM coupons WHERE status = 'active' AND end_at >= NOW() AND end_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)"),
            ],
            'kpi' => [
                'revenue'   => [self::revenue($bc), self::revenue($bp)],
                'orders'    => [$cnt($ordSql, $bc), $cnt($ordSql, $bp)],
                'customers' => [$cnt($cusSql, $bc), $cnt($cusSql, $bp)],
            ],
            'chart'        => $chart,
            'groups'       => self::groups($bc),
            'statusCounts' => $statusCounts,
            'ordersTotal'  => array_sum($statusCounts),
            'vnpay'        => ['counts' => $pay, 'total' => array_sum($pay)],
            'recent'       => AdminOrder::search([], 1, 5)['items'],
            'best'         => self::best($bc, $bestKind),
            'bestKind'     => $bestKind,
            'low'          => $pdo->query("SELECT id, name, stock FROM products WHERE status = 'active' AND stock <= 5 ORDER BY stock, name LIMIT 5")->fetchAll(),
            'petcats'      => $pc,
            'petStatus'    => $petStatus,
        ];
    }
}
