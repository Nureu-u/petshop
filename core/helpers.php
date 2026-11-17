<?php
declare(strict_types=1);

/* ---------- Cấu hình / URL ---------- */

function config(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require dirname(__DIR__) . '/config/config.php';
        // Cấu hình riêng từng máy / bí mật (không commit): config/config.local.php
        $local = dirname(__DIR__) . '/config/config.local.php';
        if (is_file($local)) {
            $cfg = array_replace_recursive($cfg, require $local);
        }
    }
    $v = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($v) || !array_key_exists($part, $v)) {
            return $default;
        }
        $v = $v[$part];
    }
    return $v;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route = 'home', array $params = []): string
{
    return config('base_url') . '/index.php?' . http_build_query(array_merge(['r' => $route], $params));
}

function asset(?string $path): string
{
    return config('base_url') . '/' . ltrim((string) $path, '/');
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function require_post(): void
{
    if (!is_post()) {
        abort(405, 'Phương thức không được hỗ trợ.');
    }
    csrf_verify();
}

function abort(int $code, string $message = ''): void
{
    http_response_code($code);
    $titles = [403 => 'Không có quyền truy cập', 404 => 'Không tìm thấy', 405 => 'Sai phương thức', 419 => 'Phiên làm việc hết hạn'];
    $title = $titles[$code] ?? 'Lỗi';
    view('errors/error', compact('code', 'title', 'message'));
    exit;
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/views/layout/header.php';
    require BASE_PATH . '/views/' . $name . '.php';
    require BASE_PATH . '/views/layout/footer.php';
}

/* ---------- Flash message ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        abort(419, 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

/* ---------- Chuẩn hóa / validate dữ liệu ---------- */

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function normalize_email(string $email): string
{
    return mb_strtolower(trim($email));
}

/** "1.500.000" / "1500000" / "1,500,000" => 1500000. Không hợp lệ => null. */
function parse_money($value): ?int
{
    $s = str_replace(['.', ',', ' '], '', trim((string) $value));
    if ($s === '' || !ctype_digit($s) || strlen($s) > 15) {
        return null;
    }
    return (int) $s;
}

function format_price($value): string
{
    return number_format((float) $value, 0, ',', '.') . ' ₫';
}

function is_valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function slugify(string $text): string
{
    $text = str_replace(['đ', 'Đ'], 'd', $text);
    if (class_exists('Normalizer')) {
        $text = Normalizer::normalize($text, Normalizer::FORM_D);
        $text = preg_replace('/\p{Mn}+/u', '', (string) $text);
    } else {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = $t !== false ? $t : $text;
    }
    $text = strtolower((string) $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-');
}

/* ---------- Nhãn hiển thị ---------- */

function status_label(string $status): string
{
    static $map = [
        'available' => 'Còn bán', 'sold' => 'Đã bán', 'hidden' => 'Đã ẩn',
        'active' => 'Đang bán', 'inactive' => 'Ngừng bán',
        'blocked' => 'Bị khóa', 'deleted' => 'Đã xóa',
        'male' => 'Đực', 'female' => 'Cái',
    ];
    return $map[$status] ?? $status;
}

/** Giữ nguyên query string hiện tại, thay đổi/ thêm tham số (dùng cho phân trang). */
function current_url_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    return config('base_url') . '/index.php?' . http_build_query($q);
}

/* ---------- Tiện ích cho view ---------- */

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="err">' . e($errors[$key]) . '</small>' : '';
}

/** Giá trị cũ của ô nhập (đã escape). */
function val(array $old, string $key, $default = ''): string
{
    return e($old[$key] ?? $default);
}

function sel($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}
