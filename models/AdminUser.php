<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: NGUOI DUNG
   File    : models/AdminUser.php
   Bang doc / ghi: orders, users
   Duoc dung boi: controllers/AdminUserController.php, views/admin/users/index.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Quan ly nguoi dung cho khu quan tri (TV5). */
final class AdminUser
{
    public const ROLES = ['customer', 'admin'];
    public const STATUSES = ['active', 'blocked', 'deleted'];

    public static function search(array $f, int $page = 1, int $perPage = 10): array
    {
        $where = [];
        $p = [];
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(full_name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3)';
            $p['q1'] = $p['q2'] = $like;
            $p['q3'] = phone_like($q);
        }
        $role = (string) ($f['role'] ?? '');
        if (in_array($role, self::ROLES, true)) {
            $where[] = 'role = :role';
            $p['role'] = $role;
        }
        $status = (string) ($f['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'status = :st';
            $p['st'] = $status;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT id, full_name, email, phone, role, status, created_at FROM users $whereSql
             ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset"
        );
        $st->execute($p);
        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $perPage];
    }

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT id, full_name, email, phone, address, role, status, created_at FROM users WHERE id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public static function orders(string $userId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT id, order_code, created_at, total_amount, status FROM orders
             WHERE user_id = :u ORDER BY created_at DESC LIMIT 50'
        );
        $st->execute(['u' => $userId]);
        return $st->fetchAll();
    }

    /** @return array{ok:bool,msg:string} khoa / mo khoa tai khoan */
    public static function setStatus(string $id, string $status, ?string $actingId): array
    {
        if (!in_array($status, ['active', 'blocked'], true)) {
            return ['ok' => false, 'msg' => 'user.msg.invalid'];
        }
        if ($actingId !== null && $id === $actingId) {
            return ['ok' => false, 'msg' => 'user.msg.self'];
        }
        $u = self::find($id);
        if (!$u) {
            return ['ok' => false, 'msg' => 'user.msg.notfound'];
        }
        User::setStatus($id, $status);
        return ['ok' => true, 'msg' => $status === 'blocked' ? 'user.msg.locked' : 'user.msg.unlocked'];
    }
}
