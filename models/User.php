<?php
declare(strict_types=1);

final class User
{
    public static function findById(string $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email');
        $st->execute(['email' => normalize_email($email)]);
        return $st->fetch() ?: null;
    }

    /** Validate dữ liệu đăng ký. @return array{0: array, 1: array} [lỗi, dữ liệu sạch] */
    public static function validateRegister(array $in): array
    {
        $errors = [];
        $name  = trim((string) ($in['full_name'] ?? ''));
        $email = normalize_email((string) ($in['email'] ?? ''));
        $phone = preg_replace('/[\s.\-]/', '', (string) ($in['phone'] ?? ''));
        $pass  = (string) ($in['password'] ?? '');
        $pass2 = (string) ($in['password_confirm'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['full_name'] = 'Họ tên bắt buộc, tối đa 100 ký tự.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'Email không hợp lệ.';
        } elseif (self::findByEmail($email)) {
            $errors['email'] = 'Email này đã được đăng ký.';
        }
        if ($phone !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        }
        if (strlen($pass) < 8 || strlen($pass) > 72) {
            $errors['password'] = 'Mật khẩu từ 8 đến 72 ký tự.';
        } elseif ($pass !== $pass2) {
            $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp.';
        }

        return [$errors, [
            'full_name' => $name, 'email' => $email,
            'phone' => $phone !== '' ? $phone : null, 'password' => $pass,
        ]];
    }

    /** Tạo tài khoản customer (hoặc admin khi gọi từ script CLI). Trả về ID mới. */
    public static function create(array $d, string $role = 'customer'): string
    {
        $id = IdGenerator::next(IdGenerator::USER);
        Database::pdo()->prepare(
            'INSERT INTO users (id, full_name, email, password_hash, phone, role)
             VALUES (:id, :name, :email, :hash, :phone, :role)'
        )->execute([
            'id' => $id, 'name' => $d['full_name'], 'email' => normalize_email($d['email']),
            'hash' => password_hash($d['password'], PASSWORD_DEFAULT),
            'phone' => $d['phone'] ?? null, 'role' => $role,
        ]);
        return $id;
    }

    /** @return array{0: array, 1: array} */
    public static function validateProfile(array $in): array
    {
        $errors = [];
        $name    = trim((string) ($in['full_name'] ?? ''));
        $phone   = preg_replace('/[\s.\-]/', '', (string) ($in['phone'] ?? ''));
        $address = trim((string) ($in['address'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['full_name'] = 'Họ tên bắt buộc, tối đa 100 ký tự.';
        }
        if ($phone !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        }
        if (mb_strlen($address) > 255) {
            $errors['address'] = 'Địa chỉ tối đa 255 ký tự.';
        }
        return [$errors, [
            'full_name' => $name, 'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
        ]];
    }

    public static function updateProfile(string $id, array $d): void
    {
        Database::pdo()->prepare(
            'UPDATE users SET full_name = :n, phone = :p, address = :a WHERE id = :id'
        )->execute(['n' => $d['full_name'], 'p' => $d['phone'], 'a' => $d['address'], 'id' => $id]);
    }

    public static function updatePassword(string $id, string $hash): void
    {
        Database::pdo()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
            ->execute(['h' => $hash, 'id' => $id]);
    }

    /* --- Dành cho trang quản trị (TV5 có thể dùng) --- */

    public static function paginate(string $q = '', int $page = 1, int $perPage = 20): array
    {
        $where = '1=1';
        $p = [];
        if ($q !== '') {
            $where = '(full_name LIKE :q1 OR email LIKE :q2)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $p = ['q1' => $like, 'q2' => $like];
        }
        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT id, full_name, email, phone, role, status, created_at FROM users
             WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
        );
        $st->execute($p);
        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function setStatus(string $id, string $status): bool
    {
        if (!in_array($status, ['active', 'blocked', 'deleted'], true)) {
            return false;
        }
        $st = Database::pdo()->prepare('UPDATE users SET status = :s WHERE id = :id');
        $st->execute(['s' => $status, 'id' => $id]);
        return $st->rowCount() > 0;
    }
}
