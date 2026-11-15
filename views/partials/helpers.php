<?php
/**
 * Hàm hiển thị dùng chung cho view khách hàng (TV1).
 * Nạp từ views/layout/header.php (require_once) nên mọi view đều dùng được.
 * Không sửa core/helpers.php của TV3.
 */

if (!function_exists('ui_icon')) {
    /** Icon SVG nét mảnh. Tên: paw, search, cart, menu, box, shield, truck, chat. */
    function ui_icon(string $name, int $size = 20, string $stroke = '1.8'): string
    {
        static $icons = [
            'paw'    => '<circle cx="6" cy="10" r="2"/><circle cx="10" cy="5" r="2"/><circle cx="14" cy="5" r="2"/><circle cx="18" cy="10" r="2"/><path d="M12 12c-3 0-5 3-5 5 0 2 2 3 5 2 3 1 5 0 5-2 0-2-2-5-5-5z"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
            'cart'   => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12h11l2-8H6"/>',
            'menu'   => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'box'    => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3.3 7.3L12 12l8.7-4.7M12 22V12"/>',
            'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
            'truck'  => '<path d="M3 6h11v10H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
            'chat'   => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.3A8 8 0 1 1 21 12z"/>',
        ];
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="' . $stroke . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . ($icons[$name] ?? '') . '</svg>';
    }
}

if (!function_exists('item_image')) {
    /** Ảnh sản phẩm/thú cưng; chưa có ảnh thì hiện icon thay thế. */
    function item_image(?string $path, string $alt, string $icon = 'paw', int $iconSize = 56): string
    {
        if ($path) {
            return '<img src="' . e(asset($path)) . '" alt="' . e($alt) . '" loading="lazy">';
        }
        return '<div class="thumb-empty">' . ui_icon($icon, $iconSize, '1.4') . '</div>';
    }
}

if (!function_exists('pet_age_label')) {
    /** "2 tháng tuổi", "1 tuổi"... tính từ ngày sinh (Y-m-d). Rỗng nếu không có. */
    function pet_age_label(?string $birth): string
    {
        if (!$birth) {
            return '';
        }
        try {
            $diff = (new DateTime($birth))->diff(new DateTime('today'));
        } catch (Throwable $e) {
            return '';
        }
        $months = $diff->y * 12 + $diff->m;
        if ($months < 1) {
            return max(1, (int) $diff->days) . ' ngày tuổi';
        }
        return $months < 24 ? $months . ' tháng tuổi' : $diff->y . ' tuổi';
    }
}

if (!function_exists('user_initial')) {
    function user_initial(string $name): string
    {
        $c = mb_strtoupper(mb_substr(trim($name), 0, 1));
        return $c !== '' ? $c : '?';
    }
}

if (!function_exists('admin_home_url')) {
    /** Có dashboard của TV5 thì vào dashboard, chưa có thì về QL thú cưng của TV3. */
    function admin_home_url(): string
    {
        return is_file(BASE_PATH . '/controllers/AdminDashboardController.php')
            ? url('admin/dashboard')
            : url('admin/pets');
    }
}
