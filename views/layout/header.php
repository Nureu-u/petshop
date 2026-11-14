<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Trang chủ') ?> - Pet Shop</title>
    <link rel="stylesheet" href="<?= e(asset('public/css/style.css')) ?>">
</head>
<body>
<header class="topbar">
    <div class="container bar">
        <a class="brand" href="<?= e(url('home')) ?>">Pet Shop</a>
        <nav>
            <a href="<?= e(url('pets')) ?>">Thú cưng</a>
            <a href="<?= e(url('products')) ?>">Sản phẩm</a>
            <?php if (Auth::isAdmin()): ?>
                <span class="sep">|</span>
                <a href="<?= e(url('admin/pets')) ?>">QL thú cưng</a>
                <a href="<?= e(url('admin/products')) ?>">QL sản phẩm</a>
                <a href="<?= e(url('admin/categories', ['type' => 'pet'])) ?>">DM thú cưng</a>
                <a href="<?= e(url('admin/categories', ['type' => 'product'])) ?>">DM sản phẩm</a>
            <?php endif; ?>
        </nav>
        <div class="account">
            <?php if (Auth::check()): ?>
                <a href="<?= e(url('auth/profile')) ?>"><?= e(Auth::user()['full_name']) ?></a>
                <form method="post" action="<?= e(url('auth/logout')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="link" type="submit">Đăng xuất</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('auth/login')) ?>">Đăng nhập</a>
                <a href="<?= e(url('auth/register')) ?>">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="container">
    <?php foreach (pull_flash() as $f): ?>
        <div class="alert <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
