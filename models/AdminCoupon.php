<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: MA GIAM GIA
   File    : models/AdminCoupon.php
   Bang doc / ghi: coupons
   Duoc dung boi: controllers/AdminCouponController.php, views/admin/coupons/index.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Quan ly ma giam gia cho khu quan tri (TV5). Theo dung rang buoc CHECK cua bang coupons. */
final class AdminCoupon
{
    /** running | upcoming | ended | off */
    public static function state(array $c): string
    {
        if ($c['status'] !== 'active') {
            return 'off';
        }
        $now = date('Y-m-d H:i:s');
        if ($c['end_at'] < $now) {
            return 'ended';
        }
        if ($c['start_at'] > $now) {
            return 'upcoming';
        }
        return 'running';
    }

    public static function stateClass(string $s): string
    {
        return ['running' => 'gr', 'upcoming' => 'bl', 'ended' => 'rd', 'off' => 'gy'][$s] ?? 'gy';
    }

    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM coupons ORDER BY created_at DESC, id DESC')->fetchAll();
    }

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM coupons WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Ma da co nguoi dung chua? (cot used_count hoac co dong trong coupon_usages).
     * Ma da dung thi KHOA: Ma, Loai giam, Muc giam, Giam toi da khong doi duoc de giu nguyen lich su.
     */
    public static function isLocked(?array $coupon): bool
    {
        if (!$coupon) {
            return false;
        }
        if ((int) ($coupon['used_count'] ?? 0) > 0) {
            return true;
        }
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = :id');
        $st->execute(['id' => $coupon['id']]);
        return (int) $st->fetchColumn() > 0;
    }

    /** Lich su cac don da dung ma (bang coupon_usages cua TV4, chi doc). Toi da 20 dong moi nhat. */
    public static function usages(string $couponId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT cu.discount_amount, cu.used_at, o.id AS order_id, o.order_code, u.full_name, u.email
             FROM coupon_usages cu
             JOIN orders o ON o.id = cu.order_id
             JOIN users u ON u.id = cu.user_id
             WHERE cu.coupon_id = :id
             ORDER BY cu.used_at DESC LIMIT 20'
        );
        $st->execute(['id' => $couponId]);
        return $st->fetchAll();
    }

    public static function findByCode(string $code): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM coupons WHERE code = :c');
        $st->execute(['c' => $code]);
        return $st->fetch() ?: null;
    }

    /** "2026-11-06T09:30" hoac "2026-11-06 09:30:00" -> "2026-11-06 09:30:00"; sai dinh dang -> null */
    public static function parseDt(string $s): ?string
    {
        $s = trim($s);
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $fmt) {
            $d = DateTime::createFromFormat($fmt, $s);
            if ($d !== false) {
                return $d->format('Y-m-d H:i:00');
            }
        }
        return null;
    }

    /** Gia tri cho o datetime-local. */
    public static function toInput(?string $dt): string
    {
        return $dt ? date('Y-m-d\TH:i', strtotime($dt)) : '';
    }

    /** @return array{0: array, 1: array} [loi, du lieu sach] */
    public static function validate(array $in, ?array $existing = null): array
    {
        $errors = [];

        // Ma da co nguoi dung: giu nguyen Ma, Loai giam, Muc giam, Giam toi da (du co ai sua form bang tay)
        if ($existing && self::isLocked($existing)) {
            $in['code'] = $existing['code'];
            $in['discount_type'] = $existing['discount_type'];
            $in['discount_value'] = $existing['discount_value'];
            $in['max_discount_amount'] = $existing['max_discount_amount'] ?? '';
        }

        $code = strtoupper(trim((string) ($in['code'] ?? '')));
        if (!preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
            $errors['code'] = t('coupon.err.code');
        } else {
            $o = self::findByCode($code);
            if ($o && (!$existing || $o['id'] !== $existing['id'])) {
                $errors['code'] = t('coupon.err.code_dup');
            }
        }

        $type = (string) ($in['discount_type'] ?? '');
        if (!in_array($type, ['percent', 'fixed'], true)) {
            $errors['discount_type'] = t('coupon.err.type');
        }

        $val = parse_money($in['discount_value'] ?? '');
        if ($val === null || $val < 1) {
            $errors['discount_value'] = t($type === 'percent' ? 'coupon.err.value_pct' : 'coupon.err.value_fixed');
        } elseif ($type === 'percent' && $val > 100) {
            $errors['discount_value'] = t('coupon.err.value_pct');
        }

        $max = null;
        $maxRaw = trim((string) ($in['max_discount_amount'] ?? ''));
        if ($type === 'percent' && $maxRaw !== '') {
            $m = parse_money($maxRaw);
            if ($m === null) {
                $errors['max_discount_amount'] = t('coupon.err.max');
            } else {
                $max = $m;
            }
        }

        $minRaw = trim((string) ($in['min_order_amount'] ?? ''));
        $min = $minRaw === '' ? 0 : parse_money($minRaw);
        if ($min === null) {
            $errors['min_order_amount'] = t('coupon.err.min');
            $min = 0;
        }

        $limit = null;
        $limRaw = str_replace(['.', ',', ' '], '', trim((string) ($in['usage_limit'] ?? '')));
        if ($limRaw !== '') {
            if (!ctype_digit($limRaw) || (int) $limRaw < 1 || strlen($limRaw) > 9) {
                $errors['usage_limit'] = t('coupon.err.limit');
            } else {
                $limit = (int) $limRaw;
                if ($existing && $limit < (int) $existing['used_count']) {
                    $errors['usage_limit'] = t('coupon.err.limit_used', ['n' => (int) $existing['used_count']]);
                }
            }
        }

        $start = self::parseDt((string) ($in['start_at'] ?? ''));
        $end = self::parseDt((string) ($in['end_at'] ?? ''));
        if ($start === null) {
            $errors['start_at'] = t('coupon.err.start');
        }
        if ($end === null) {
            $errors['end_at'] = t('coupon.err.end');
        }
        if ($start !== null && $end !== null && $end <= $start) {
            $errors['end_at'] = t('coupon.err.range');
        }

        $status = (string) ($in['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'] = t('coupon.err.status');
        }

        return [$errors, [
            'code' => $code, 'discount_type' => $type, 'discount_value' => $val ?? 0,
            'min_order_amount' => $min, 'max_discount_amount' => $max, 'usage_limit' => $limit,
            'start_at' => $start, 'end_at' => $end, 'status' => $status,
        ]];
    }

    public static function create(array $d): string
    {
        $id = IdGenerator::next(IdGenerator::COUPON);
        Database::pdo()->prepare(
            'INSERT INTO coupons (id, code, discount_type, discount_value, min_order_amount, max_discount_amount,
                                  usage_limit, start_at, end_at, status)
             VALUES (:id, :code, :discount_type, :discount_value, :min_order_amount, :max_discount_amount,
                     :usage_limit, :start_at, :end_at, :status)'
        )->execute(['id' => $id] + $d);
        return $id;
    }

    public static function update(string $id, array $d): void
    {
        Database::pdo()->prepare(
            'UPDATE coupons SET code = :code, discount_type = :discount_type, discount_value = :discount_value,
                    min_order_amount = :min_order_amount, max_discount_amount = :max_discount_amount,
                    usage_limit = :usage_limit, start_at = :start_at, end_at = :end_at, status = :status
             WHERE id = :id'
        )->execute(['id' => $id] + $d);
    }

    public static function toggle(string $id): bool
    {
        $c = self::find($id);
        if (!$c) {
            return false;
        }
        Database::pdo()->prepare('UPDATE coupons SET status = :s WHERE id = :id')
            ->execute(['s' => $c['status'] === 'active' ? 'inactive' : 'active', 'id' => $id]);
        return true;
    }
}
