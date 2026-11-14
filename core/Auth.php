<?php
declare(strict_types=1);

/**
 * Xác thực & phân quyền bằng PHP session.
 *
 * API cho các thành viên khác:
 *   Auth::check()         : đã đăng nhập chưa
 *   Auth::id()            : ID người dùng hiện tại (hoặc null)
 *   Auth::user()          : mảng thông tin user (không có password_hash)
 *   Auth::isAdmin()
 *   Auth::requireLogin()  : chưa đăng nhập => chuyển tới trang đăng nhập
 *   Auth::requireAdmin()  : không phải admin => 403
 *
 * Mỗi request đều đọc lại user từ DB nên khi admin khóa tài khoản,
 * người đó mất quyền ngay lập tức; role không bao giờ lấy từ form.
 */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(config('session_name', 'PETSHOPSESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }

    /** @return ?string null nếu thành công, ngược lại là thông báo lỗi */
    public static function attempt(string $email, string $password): ?string
    {
        $lockUntil = $_SESSION['login_lock_until'] ?? 0;
        if ($lockUntil > time()) {
            return 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . ceil(($lockUntil - time()) / 60) . ' phút.';
        }

        $user = User::findByEmail($email);
        // Luôn chạy password_verify để thời gian phản hồi không lộ email có tồn tại hay không.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $ok = password_verify($password, $hash) && $user !== null;

        if (!$ok) {
            $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
            if ($_SESSION['login_fail'] >= self::MAX_ATTEMPTS) {
                $_SESSION['login_lock_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_fail'] = 0;
            }
            return 'Email hoặc mật khẩu không đúng.';
        }
        if ($user['status'] === 'blocked') {
            return 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ cửa hàng.';
        }
        if ($user['status'] !== 'active') {
            return 'Email hoặc mật khẩu không đúng.';
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword($user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        self::login($user['id']);
        return null;
    }

    public static function login(string $userId): void
    {
        session_regenerate_id(true);
        unset($_SESSION['login_fail'], $_SESSION['login_lock_until']);
        $_SESSION['user_id'] = $userId;
        self::$loaded = false;
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
        self::$loaded = true;
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            if (is_string($id)) {
                $u = User::findById($id);
                if ($u && $u['status'] === 'active') {
                    unset($u['password_hash']);
                    self::$user = $u;
                } else {
                    unset($_SESSION['user_id']);
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?string
    {
        return self::user()['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Vui lòng đăng nhập để tiếp tục.');
            redirect('auth/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }
    }
}
