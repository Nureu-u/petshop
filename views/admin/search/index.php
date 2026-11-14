<?php
/* ==============================================================================
   VIEW: KET QUA TIM KIEM CHUNG
   File    : views/admin/search/index.php
   Dia chi  : ?r=admin/search
   Controller: controllers/AdminSearchController.php  (ham index)
   Model   : models/AdminSearch.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [9] BIEU DO
           COT, THANH PHAN BO, VONG TRON TRANG TH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN
           TRANG.
   Cac khoi: 1 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var string $q @var ?array $res */
$groups = [
    ['orders', 'search.orders'], ['users', 'search.users'], ['pets', 'search.pets'],
    ['products', 'search.products'], ['posts', 'search.posts'], ['coupons', 'search.coupons'],
];
$found = $res ? array_sum(array_map('count', $res)) : 0;
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('search.title')) ?></h1>
        <div class="sub"><?= e($res === null ? t('search.hint') : t('search.for', ['q' => $q])) ?></div>
    </div>
</div>

<?php if ($res !== null && $found === 0): ?>
    <div class="c"><?= e(t('search.none', ['q' => $q])) ?></div>
<?php endif; ?>

<?php if ($res !== null): ?>
    <?php foreach ($groups as [$key, $label]): ?>
        <?php if (!$res[$key]) { continue; } ?>
        <div class="c">
            <h2><?= e(t($label)) ?></h2>
            <?php foreach ($res[$key] as $r): ?>
                <div class="row">
                    <?php if ($key === 'orders'): ?>
                        <span><a class="lk" href="<?= e(url('admin/orders/show', ['id' => $r['id']])) ?>"><?= e($r['order_code']) ?></a> · <?= e($r['customer_name']) ?> · <?= e($r['customer_phone']) ?></span>
                        <span><b><?= e(format_price($r['total_amount'])) ?></b> <span class="p <?= e(AdminOrder::statusClass($r['status'])) ?>"><?= e(t('order.status.' . $r['status'])) ?></span></span>
                    <?php elseif ($key === 'users'): ?>
                        <span><a class="lk" href="<?= e(url('admin/users/show', ['id' => $r['id']])) ?>"><?= e($r['full_name']) ?></a> · <?= e($r['email']) ?></span>
                        <span class="p <?= $r['role'] === 'admin' ? 'bl' : 'gy' ?>"><?= e(t('user.role.' . $r['role'])) ?></span>
                    <?php elseif ($key === 'pets'): ?>
                        <span><a class="lk" href="<?= e(url('admin/manage/pets/form', ['id' => $r['id']])) ?>"><?= e($r['name']) ?></a> · <?= e($r['species']) ?><?= $r['breed'] ? ' / ' . e($r['breed']) : '' ?></span>
                        <b><?= e(format_price($r['price'])) ?></b>
                    <?php elseif ($key === 'products'): ?>
                        <span><a class="lk" href="<?= e(url('admin/manage/products/form', ['id' => $r['id']])) ?>"><?= e($r['name']) ?></a></span>
                        <span><b><?= e(format_price($r['price'])) ?></b> <span class="hint"><?= e(t('dash.left', ['n' => (int) $r['stock']])) ?></span></span>
                    <?php elseif ($key === 'posts'): ?>
                        <span><a class="lk" href="<?= e(url('admin/posts/form', ['id' => $r['id']])) ?>"><?= e($r['title']) ?></a></span>
                        <span class="p <?= e(AdminPost::statusClass($r['status'])) ?>"><?= e(t('post.status.' . $r['status'])) ?></span>
                    <?php else: ?>
                        <span><a class="lk" href="<?= e(url('admin/coupons/form', ['id' => $r['id']])) ?>"><?= e($r['code']) ?></a></span>
                        <span class="p <?= $r['status'] === 'active' ? 'gr' : 'gy' ?>"><?= e(t($r['status'] === 'active' ? 'coupon.status.on' : 'coupon.status.off')) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
