<?php
declare(strict_types=1);

/* ==============================================================================
   LOP DUNG CHUNG: core/AdminView.php
   Gom : AdminView::render() ve mot trang admin (nap header, view, footer); AdminView::lang() va
      ham t() dich chu VI/EN (tu lang/vi.php, lang/en.php); AdminView::icon() tra ve bieu tuong
      SVG; AdminView::pendingOrders() dem don cho cho huy hieu menu; money_short() rut gon tien
      (1,2 trieu); row_no() danh so thu tu trong bang; phone_like() chuan hoa so dien thoai khi
      tim kiem.
   Muon them bieu tuong moi: them mot dong vao hang so ICONS trong file nay.
   Muon them cau chu moi: them cung mot khoa vao lang/vi.php va lang/en.php.
   ============================================================================== */
/**
 * Khung giao dien khu quan tri cua TV5 (layout rieng, VI/EN, sang/toi).
 * Khong sua view() hay header cua TV3.
 */
final class AdminView
{
    public const ICONS = [
        'dashboard'  => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'pets'       => '<circle cx="6" cy="10" r="2"/><circle cx="10" cy="5" r="2"/><circle cx="14" cy="5" r="2"/><circle cx="18" cy="10" r="2"/><path d="M12 12c-3 0-5 3-5 5 0 2 2 3 5 2 3 1 5 0 5-2 0-2-2-5-5-5z"/>',
        'products'   => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>',
        'categories' => '<rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/>',
        'users'      => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 5a3.5 3.5 0 0 1 0 7M18 14c2 .7 3.5 2.4 3.5 6"/>',
        'orders'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12h11l2-8H6"/>',
        'coupons'    => '<path d="M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4V7z"/><path d="M13 8v8" stroke-dasharray="2 2"/>',
        'posts'      => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'reviews'    => '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.2 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3z"/>',
        'chatbot'    => '<path d="M4 5h16v11H9l-5 4V5z"/><path d="M9 10h.01M15 10h.01"/>',
        'sun'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.4 1.4M17.6 17.6L19 19M5 19l1.4-1.4M17.6 6.4L19 5"/>',
        'moon'       => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
        'menu'       => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'menualt'    => '<path d="M4 7h16M4 12h10M4 17h16"/>',
        'shield'     => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'bell'       => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'key'        => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'store'      => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/>',
        'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h0a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h0a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v0a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'coin'       => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.2c-.6-.8-1.6-1.2-2.7-1.2-1.6 0-2.8.8-2.8 2.1 0 3 5.6 1.6 5.6 4.3 0 1.3-1.2 2.1-2.8 2.1-1.2 0-2.2-.5-2.9-1.3M12 6v2M12 16v2"/>',
        'box'        => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3.3 7.3L12 12l8.7-4.7M12 22V12"/>',
        'eyeoff'     => '<path d="M17.94 17.94A10.5 10.5 0 0 1 12 19c-6 0-10-7-10-7a18.5 18.5 0 0 1 4.06-5.06M9.9 4.24A9.9 9.9 0 0 1 12 5c6 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12A3 3 0 1 1 9.88 9.88"/><path d="M1 1l22 22"/>',
        'eye'        => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'mail'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'lock'       => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'arrow'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];

    /** 'vi' hoac 'en' (luu trong session, doi bang route admin/lang). */
    public static function lang(): string
    {
        return (($_SESSION['admin_lang'] ?? 'vi') === 'en') ? 'en' : 'vi';
    }

    public static function icon(string $name, int $size = 20): string
    {
        $p = self::ICONS[$name] ?? '';
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
             . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
    }

    /** So don da xac nhan, dang cho giao (hien o menu). */
    public static function pendingOrders(): int
    {
        try {
            return (int) Database::pdo()
                ->query("SELECT COUNT(*) FROM orders WHERE status = 'CONFIRMED'")
                ->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /** Hien thi view admin: views/admin/<view>.php nam giua header va footer cua TV5. */
    public static function render(string $__view, array $data = [], string $active = 'dashboard', string $title = ''): void
    {
        extract($data, EXTR_SKIP);
        $adminActive = $active;
        $adminTitle = $title !== '' ? $title : t('nav.' . $active);
        require BASE_PATH . '/views/admin/layout/header.php';
        require BASE_PATH . '/views/admin/' . $__view . '.php';
        require BASE_PATH . '/views/admin/layout/footer.php';
    }
}

/** Dich chuoi: t('nav.orders'). Thieu ban EN thi dung ban VI. Co the truyen {bien}. */
function t(string $key, array $vars = []): string
{
    static $dict = null;
    if ($dict === null) {
        $dict = require BASE_PATH . '/lang/vi.php';
        if (AdminView::lang() === 'en') {
            $dict = array_replace($dict, require BASE_PATH . '/lang/en.php');
        }
    }
    $s = $dict[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}

/** 72500000 -> "72,5 trd" (don vi lay tu file lang). */
function money_short($v): string
{
    $v = (float) $v;
    if ($v >= 1e9) { $n = $v / 1e9; $u = t('unit.b'); }
    elseif ($v >= 1e6) { $n = $v / 1e6; $u = t('unit.m'); }
    elseif ($v >= 1e3) { $n = $v / 1e3; $u = t('unit.k'); }
    else { return number_format($v, 0, ',', '.'); }
    return preg_replace('/,0$/', '', number_format($n, 1, ',', '.')) . $u;
}

/** So thu tu cua dong trong bang (tiep noi qua cac trang). $result: ket qua co 'page' va 'per'; [] neu khong phan trang. */
function row_no(array $result, int $i): int
{
    return ((int) ($result['page'] ?? 1) - 1) * (int) ($result['per'] ?? 0) + $i + 1;
}

/** Mau LIKE cho o tim so dien thoai: bo khoang trang, dau cham, gach ngang neu nguoi dung go kieu "0901 000 001". */
function phone_like(string $q): string
{
    $digits = preg_replace('/[\s.\-]/', '', $q);
    if ($digits !== '' && preg_match('/^\+?[0-9]{3,}$/', $digits)) {
        return '%' . $digits . '%';
    }
    return '%' . addcslashes($q, '%_\\') . '%';
}

/**
 * Tao slug khong dau tu chu tieng Viet: "Cho meo ga vit" -> "cho-meo-ga-vit".
 * May co phan mo rong intl: goi thang slugify() cua TV3.
 * May KHONG co intl (slugify() cua TV3 lam roi chu co dau): dung bang chuyen chu rieng ben duoi,
 * cho ra cung dinh dang slug ma TV3 mong muon (a-z, 0-9, dau gach ngang).
 */
function vn_slug(string $text): string
{
    // May co intl: dung dung ham slugify() cua TV3 de ket qua giong het TV3
    if (class_exists('Normalizer') && function_exists('slugify')) {
        return slugify($text);
    }
    static $map = null;
    if ($map === null) {
        $groups = [
            'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
            'e' => 'èéẹẻẽêềếệểễ',
            'i' => 'ìíịỉĩ',
            'o' => 'òóọỏõôồốộổỗơờớợởỡ',
            'u' => 'ùúụủũưừứựửữ',
            'y' => 'ỳýỵỷỹ',
            'd' => 'đ',
        ];
        $map = [];
        foreach ($groups as $base => $chars) {
            foreach (mb_str_split($chars) as $ch) {
                $map[$ch] = $base;
            }
        }
    }
    $text = strtr(mb_strtolower(trim($text), 'UTF-8'), $map);
    $text = (string) preg_replace('/\p{Mn}+/u', '', $text);   // dau roi (chu go dang to hop)
    $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Dia chi file CSS/JS cua trang admin kem so phien ban (thoi diem sua file).
 * Moi lan file doi, so nay doi theo nen trinh duyet tu tai ban moi, khong bi dinh ban cu trong bo nho dem.
 */
function admin_asset(string $path): string
{
    $file = BASE_PATH . '/' . $path;
    return asset($path) . '?v=' . (is_file($file) ? (int) filemtime($file) : 0);
}
