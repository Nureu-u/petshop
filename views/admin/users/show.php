<?php
/* ==============================================================================
   VIEW: CHI TIET NGUOI DUNG
   File    : views/admin/users/show.php
   Dia chi  : ?r=admin/users/show
   Controller: controllers/AdminUserController.php  (ham show)
   Model   : models/AdminUser.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [13] BANG
           DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $account @var array $orders */
$meId = Auth::id();
$fmt = fn($d) => date('d/m/Y H:i', strtotime((string) $d));
$stClass = ['active' => 'gr', 'blocked' => 'rd', 'deleted' => 'gy'];
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <a class="lk" href="<?= e(url('admin/users')) ?>"><?= e(t('user.back')) ?></a>
        <h1><?= e($account['full_name']) ?></h1>
        <div class="sub"><?= e(t('user.detail_sub')) ?></div>
    </div>
    <?php if ($account['id'] !== $meId && $account['status'] !== 'deleted'): ?>
        <form method="post" action="<?= e(url('admin/users/status')) ?>" style="margin:0"
              <?= $account['status'] === 'active' ? 'data-confirm="' . e(t('user.lock_confirm')) . '"' : '' ?>>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($account['id']) ?>">
            <input type="hidden" name="back" value="show">
            <input type="hidden" name="status" value="<?= $account['status'] === 'active' ? 'blocked' : 'active' ?>">
            <button class="<?= $account['status'] === 'active' ? 'b3' : 'b2' ?>" type="submit"><?= e(t($account['status'] === 'active' ? 'user.lock' : 'user.unlock')) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php /* ----- KHOI 2: Luoi hai cot (noi dung chinh | thong tin phu) ----- */ ?>
<div class="dg">
    <div class="c">
        <h2><?= e(t('user.orders')) ?></h2>
        <table class="tb">
            <thead><tr>
                <th><?= e(t('order.col.code')) ?></th><th><?= e(t('order.col.date')) ?></th>
                <th class="r"><?= e(t('order.col.total')) ?></th><th><?= e(t('order.col.status')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td data-label="<?= e(t('order.col.code')) ?>"><a class="lk" href="<?= e(url('admin/orders/show', ['id' => $o['id']])) ?>"><?= e($o['order_code']) ?></a></td>
                    <td data-label="<?= e(t('order.col.date')) ?>"><?= e($fmt($o['created_at'])) ?></td>
                    <td class="r" data-label="<?= e(t('order.col.total')) ?>"><b><?= e(format_price($o['total_amount'])) ?></b></td>
                    <td data-label="<?= e(t('order.col.status')) ?>"><span class="p <?= e(AdminOrder::statusClass($o['status'])) ?>"><?= e(t('order.status.' . $o['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="4" class="hint"><?= e(t('user.empty_orders')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="c">
        <h2><?= e(t('user.info')) ?></h2>
        <div class="kv">
            <span><?= e(t('user.email')) ?></span><span><?= e($account['email']) ?></span>
            <span><?= e(t('user.col.phone')) ?></span><span><?= e($account['phone'] ?: '—') ?></span>
            <span><?= e(t('user.address')) ?></span><span><?= e($account['address'] ?: '—') ?></span>
            <span><?= e(t('user.col.role')) ?></span><span><?= e(t('user.role.' . $account['role'])) ?></span>
            <span><?= e(t('user.col.status')) ?></span><span><span class="p <?= e($stClass[$account['status']] ?? 'gy') ?>"><?= e(t('user.status.' . $account['status'])) ?></span></span>
            <span><?= e(t('user.col.created')) ?></span><span><?= e($fmt($account['created_at'])) ?></span>
        </div>
    </div>
</div>
