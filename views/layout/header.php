<?php
/**
 * Khung đầu trang khách hàng.
 * Hiển thị thanh điều hướng, ô tìm kiếm nhanh, biểu tượng giỏ hàng và menu tài khoản.
 */
require_once BASE_PATH . '/views/partials/helpers.php';

$authUser = Auth::user();
$route = isset($_GET['r']) && is_string($_GET['r']) && $_GET['r'] !== '' ? $_GET['r'] : 'home';
$navItems = [
    'home'     => 'Trang chủ',
    'pets'     => 'Thú cưng',
    'products' => 'Sản phẩm',
    'posts'    => 'Góc kiến thức',
];
$isActive = static function (string $key) use ($route): bool {
    return $key === 'home' ? $route === 'home' : ($route === $key || strpos($route, $key . '/') === 0);
};
$hasDashboard = is_file(BASE_PATH . '/controllers/AdminDashboardController.php');
$cssFile = BASE_PATH . '/public/css/style.css';
$cssVer = is_file($cssFile) ? filemtime($cssFile) : 1;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Trang chủ') ?> - Pet Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('public/css/style.css')) ?>?v=<?= (int) $cssVer ?>">
    <!-- Hỗ trợ hiển thị tiện ích giao diện -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            corePlugins: { preflight: false },
            theme: {
                extend: {
                    colors: {
                        cream: '#FFF9F3',
                        espresso: '#2B2D42',
                        coral: { DEFAULT: '#E8743B', hover: '#C95C26', light: '#FDEBDD' },
                        stoneWarm: '#EADFD3',
                        mutedBrown: '#6B7280',
                        charcoal: '#1C1613'
                    },
                    fontFamily: {
                        sans: ['Be Vietnam Pro', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        .font-mono-code { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }
        .card-warm {
            background-color: #FFFFFF;
            border: 1px solid #EADFD3;
            border-radius: 16px;
            box-shadow: 0 4px 20px -4px rgba(43, 45, 66, 0.06);
        }
    </style>
    <script src="<?= e(asset('public/js/site.js')) ?>" defer></script>
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= e(url('home')) ?>"><?= ui_icon('paw', 26, '2') ?> Pet Shop</a>

        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Mở menu" aria-expanded="false"><?= ui_icon('menu', 22) ?></button>

        <nav class="nav-links" aria-label="Menu chính">
            <?php foreach ($navItems as $key => $label): ?>
                <a href="<?= e(url($key)) ?>"<?= $isActive($key) ? ' class="active"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <form class="nav-search" method="get" action="<?= e(config('base_url')) ?>/index.php" role="search">
            <select name="r" aria-label="Tìm trong">
                <option value="pets"<?= strpos($route, 'products') === 0 || strpos($route, 'posts') === 0 ? '' : ' selected' ?>>Thú cưng</option>
                <option value="products"<?= strpos($route, 'products') === 0 ? ' selected' : '' ?>>Sản phẩm</option>
                <option value="posts"<?= strpos($route, 'posts') === 0 ? ' selected' : '' ?>>Bài viết</option>
            </select>
            <input type="search" name="q" placeholder="Tìm thú cưng, phụ kiện, bài viết..." value="<?= e(input('q')) ?>" aria-label="Từ khóa">
            <button type="submit" aria-label="Tìm"><?= ui_icon('search', 18) ?></button>
        </form>

        <div class="nav-actions">
            <?php
            $cartCount = 0;
            if ($authUser && class_exists('Cart') && method_exists('Cart', 'count')) {
                try {
                    $cartCount = (int) Cart::count((string) $authUser['id']);
                } catch (Throwable $e) {
                    $cartCount = 0;
                }
            }
            ?>
            <a class="icon-link cart-link" href="<?= e(url('cart')) ?>" aria-label="Giỏ hàng" title="Giỏ hàng của bạn">
                <?= ui_icon('cart', 22) ?>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <?php if ($authUser): ?>
                <details class="dropdown">
                    <summary>
                        <span class="avatar"><?= e(user_initial((string) $authUser['full_name'])) ?></span>
                        <?= e($authUser['full_name']) ?>
                    </summary>
                    <div class="dropdown-menu">
                        <a href="<?= e(url('auth/profile')) ?>"><i class="fa-regular fa-id-badge mr-2 text-coral"></i>Hồ sơ cá nhân</a>
                        <a href="<?= e(url('orders')) ?>"><i class="fa-solid fa-box-open mr-2 text-coral"></i>Đơn hàng của tôi</a>
                        <?php if (Auth::isAdmin()): ?>
                            <hr>
                            <?php if ($hasDashboard): ?>
                                <a href="<?= e(url('admin/dashboard')) ?>"><i class="fa-solid fa-gauge-high mr-2 text-coral"></i>Trang quản trị</a>
                            <?php else: ?>
                                <a href="<?= e(url('admin/pets')) ?>">QL thú cưng</a>
                                <a href="<?= e(url('admin/products')) ?>">QL sản phẩm</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <hr>
                        <button type="button" data-modal-open="logout-modal"><i class="fa-solid fa-right-from-bracket mr-2 text-rose-600"></i>Đăng xuất</button>
                    </div>
                </details>
            <?php else: ?>
                <a class="btn ghost sm" href="<?= e(url('auth/login')) ?>">Đăng nhập</a>
                <a class="btn sm" href="<?= e(url('auth/register')) ?>">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container site-main">
    <?php foreach (pull_flash() as $f): ?>
        <div class="alert <?= e($f['type']) ?>">
            <span><?= e($f['message']) ?></span>
            <button type="button" class="x" data-dismiss aria-label="Đóng">&times;</button>
        </div>
    <?php endforeach; ?>
