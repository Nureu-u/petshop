<?php
/* ==============================================================================
   VIEW: KHUNG TRANG - PHAN DAU (menu trai, thanh dau trang, thong bao noi)
   File    : views/admin/layout/header.php
   Dung boi : moi trang admin: AdminView::render() nap header.php, roi file view cua trang, roi
           footer.php.
   CSS      : public/css/admin-ui.css, nhom [3] MENU BEN TRAI; [4] THANH DAU TRANG VA CAC MENU THA
           XUONG; [6] THONG BAO NOI; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG.
   Cac khoi: danh dau bang cac dong "----- ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var string $adminActive @var string $adminTitle */
$me = Auth::user();
$parts = preg_split('/\s+/', trim((string) ($me['full_name'] ?? 'A'))) ?: ['A'];
$initial = mb_strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
$pending = AdminView::pendingOrders();
$lang = AdminView::lang();
$here = $_SERVER['REQUEST_URI'] ?? '';
$notes = AdminNotify::items();
$noteTotal = array_sum(array_column($notes, 1));
$navGroups = [
    ['nav.cap.overview', [
        ['dashboard', 'nav.dashboard', url('admin/dashboard'), ['dashboard']],
    ]],
    ['nav.cap.store', [
        ['pets',       'nav.catalog',    url('admin/manage/pets'), ['pets', 'products']],
        ['categories', 'nav.categories', url('admin/manage/categories', ['type' => 'pet']), ['categories']],
        ['coupons',    'nav.coupons',    url('admin/coupons'), ['coupons']],
        ['orders',     'nav.orders',     url('admin/orders'), ['orders']],
    ]],
    ['nav.cap.people', [
        ['users', 'nav.users',   url('admin/users'), ['users']],
        ['posts', 'nav.content', url('admin/posts'), ['posts', 'reviews']],
        ['chatbot', 'nav.chatbot', url('admin/chatbot'), ['chatbot']],
    ]],
];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> - Pet Shop Admin</title>
    <script>try{var __t=localStorage.getItem('ps_admin_theme');if(__t){document.documentElement.setAttribute('data-theme',__t);}if(sessionStorage.getItem('ps_dash_anim')){document.documentElement.classList.add('no-anim');}}catch(e){}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(admin_asset('public/css/admin-ui.css')) ?>">
</head>
<body>
<div class="adm">
    <div class="adm-dim" data-menu-close></div>

    <?php /* ----- MENU TRAI (logo, cac muc menu, khoi ten admin) ----- */ ?>
    <aside class="side" id="adminSide">
        <div style="display:flex;flex-direction:column;min-height:0;flex:1">
            <div class="brand"><i><?= AdminView::icon('pets', 20) ?></i><div>PETSHOP<small>Admin Portal</small></div></div>
            <div class="side-top"><button type="button" class="side-close" data-menu-close aria-label="<?= e(t('common.close_menu')) ?>"><?= AdminView::icon('menualt', 22) ?></button></div>
            <?php /* ----- Cac muc menu, chia nhom (TONG QUAN HE THONG / QUAN LY CUA HANG / ...) ----- */ ?>
            <nav class="snav">
                <?php foreach ($navGroups as [$navCap, $navItems]): ?>
                    <div class="cap"><?= e(t($navCap)) ?></div>
                    <?php foreach ($navItems as [$navIcon, $navLabel, $navHref, $navKeys]): ?>
                        <a href="<?= e($navHref) ?>"<?= in_array($adminActive, $navKeys, true) ? ' class="on"' : '' ?>>
                            <?= AdminView::icon($navIcon) ?>
                            <span><?= e(t($navLabel)) ?></span>
                            <?php if ($navIcon === 'orders' && $pending > 0): ?><span class="n"><?= (int) $pending ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php /* ----- Khoi ten admin o cuoi menu ----- */ ?>
        <div class="ub">
            <div class="ub-row">
                <span class="ub-av"><?= e($initial) ?></span>
                <span class="ub-txt">
                    <span class="ub-name"><?= e($me['full_name'] ?? '') ?></span>
                    <span class="ub-role"><?= e(t('user.role.admin')) ?></span>
                </span>
            </div>
        </div>
    </aside>

    <?php /* ----- COT NOI DUNG ben phai menu ----- */ ?>
    <section class="main">
        <?php /* ----- THANH DAU TRANG (tim kiem, VI/EN, sang/toi, chuong, ten admin) ----- */ ?>
        <header class="util">
            <button type="button" class="burger" data-menu-toggle aria-expanded="false" aria-label="<?= e(t('common.open_menu')) ?>"><?= AdminView::icon('menualt', 22) ?></button>

            <?php /* ----- O tim kiem chung ----- */ ?>
            <form class="adm-search" method="get" action="<?= e(config('base_url')) ?>/index.php" role="search">
                <input type="hidden" name="r" value="admin/search">
                <?= AdminView::icon('search', 16) ?>
                <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="<?= e(t('search.ph')) ?>" aria-label="<?= e(t('search.ph')) ?>">
            </form>

            <span class="sp"></span>

            <?php /* ----- Nut chuyen ngon ngu VI / EN ----- */ ?>
            <div class="lang" role="group" aria-label="<?= e(t('common.language')) ?>">
                <a href="<?= e(url('admin/lang', ['l' => 'vi', 'back' => $here])) ?>"<?= $lang === 'vi' ? ' class="on"' : '' ?>>VI</a>
                <a href="<?= e(url('admin/lang', ['l' => 'en', 'back' => $here])) ?>"<?= $lang === 'en' ? ' class="on"' : '' ?>>EN</a>
            </div>
            <?php /* ----- Nut chuyen sang / toi ----- */ ?>
            <div class="theme" role="group">
                <button type="button" data-theme-set="light" aria-label="<?= e(t('common.light')) ?>"><?= AdminView::icon('sun', 16) ?></button>
                <button type="button" data-theme-set="dark" aria-label="<?= e(t('common.dark')) ?>"><?= AdminView::icon('moon', 16) ?></button>
            </div>

            <?php /* ----- Chuong thong bao (menu tha xuong) ----- */ ?>
            <div class="dd" data-dd>
                <button type="button" class="iconbtn" data-dd-toggle aria-haspopup="true" aria-label="<?= e(t('notif.title')) ?>">
                    <?= AdminView::icon('bell', 18) ?>
                    <?php if ($noteTotal > 0): ?><span class="dot2"></span><?php endif; ?>
                </button>
                <div class="dd-panel" role="menu">
                    <div class="dd-title"><?= e(t('notif.title')) ?></div>
                    <?php foreach ($notes as [$nk, $nn, $nhref, $ncls]): ?>
                        <a class="dd-item" href="<?= e($nhref) ?>">
                            <span class="cnt <?= e($ncls) ?>"><?= (int) $nn ?></span>
                            <span><?= e(t('notif.' . $nk, ['n' => $nn])) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$notes): ?><div class="hint" style="padding:10px 12px"><?= e(t('notif.empty')) ?></div><?php endif; ?>
                </div>
            </div>

            <?php /* ----- Nut tron ten admin (menu tha xuong: Ho so, Cai dat, Vao trang cua hang, Dang xuat Admin) ----- */ ?>
            <div class="dd" data-dd>
                <button type="button" class="avbtn" data-dd-toggle aria-haspopup="true" aria-label="<?= e($me['full_name'] ?? '') ?>"><?= e($initial) ?></button>
                <div class="dd-panel" role="menu">
                    <div class="dd-head">
                        <span class="av"><?= e($initial) ?></span>
                        <div style="min-width:0">
                            <b><?= e($me['full_name'] ?? '') ?></b>
                            <div class="hint" style="overflow:hidden;text-overflow:ellipsis"><?= e($me['email'] ?? '') ?></div>
                            <span class="p bl" style="margin-top:4px"><?= e(t('user.role.admin')) ?></span>
                        </div>
                    </div>
                    <a class="dd-item" href="<?= e(url('admin/profile')) ?>"><?= AdminView::icon('user', 18) ?><span><?= e(t('menu.profile')) ?></span></a>
                    <a class="dd-item" href="<?= e(url('admin/settings')) ?>"><?= AdminView::icon('settings', 18) ?><span><?= e(t('menu.settings')) ?></span></a>
                    <a class="dd-item" href="<?= e(url('home')) ?>"><?= AdminView::icon('store', 18) ?><span><?= e(t('menu.shop')) ?></span></a>
                    <form method="post" action="<?= e(url('admin/logout')) ?>" data-confirm="<?= e(t('menu.logout_confirm')) ?>" style="margin:0">
                        <?= csrf_field() ?>
                        <button type="submit" class="dd-item danger"><?= AdminView::icon('logout', 18) ?><span><?= e(t('menu.logout_btn')) ?></span></button>
                    </form>
                </div>
            </div>
        </header>

        <?php $navFlashes = pull_flash(); if ($navFlashes): ?>
            <?php /* ----- THONG BAO NOI (Da luu..., loi...) ----- */ ?>
            <div class="toasts" role="status">
                <?php foreach ($navFlashes as $f): ?>
                    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php /* ----- VUNG NOI DUNG: file view cua tung trang duoc chen vao day ----- */ ?>
        <div class="content">
