<?php
/* ==============================================================================
   VIEW: DANH SACH DON HANG
   File    : views/admin/orders/index.php
   Dia chi  : ?r=admin/orders
   Controller: controllers/AdminOrderController.php  (ham index)
   Model   : models/AdminOrder.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG.
   Cac khoi: 4 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $result @var array $flt @var array $counts */
$total = array_sum($counts);
$fmt = fn($d) => date('d/m/Y H:i', strtotime((string) $d));
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.orders')) ?></h1>
        <div class="sub"><?= e(t('order.sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a href="<?= e(current_url_with(['status' => '', 'page' => 1])) ?>"<?= $flt['status'] === '' ? ' class="on"' : '' ?>>
        <?= e(t('order.tab.all')) ?> <b><?= (int) $total ?></b>
    </a>
    <?php foreach (AdminOrder::STATUSES as $s): ?>
        <a href="<?= e(current_url_with(['status' => $s, 'page' => 1])) ?>"<?= $flt['status'] === $s ? ' class="on"' : '' ?>>
            <?= e(t('order.status.' . $s)) ?> <b><?= (int) ($counts[$s] ?? 0) ?></b>
        </a>
    <?php endforeach; ?>
</div>

<?php /* ----- KHOI 3: Thanh bo loc va tim kiem ----- */ ?>
<form class="c" method="get" action="<?= e(config('base_url')) ?>/index.php">
    <input type="hidden" name="r" value="admin/orders">
    <input type="hidden" name="status" value="<?= e($flt['status']) ?>">
    <div class="fl">
        <input class="grow" type="search" name="q" value="<?= e($flt['q']) ?>" placeholder="<?= e(t('order.search_ph')) ?>" aria-label="<?= e(t('order.search_ph')) ?>">
        <select name="pay" aria-label="<?= e(t('order.col.payment')) ?>">
            <option value=""><?= e(t('order.pay_all')) ?></option>
            <?php foreach (['PAID', 'PENDING', 'FAILED', 'REFUNDED', 'NONE'] as $ps): ?>
                <option value="<?= e($ps) ?>"<?= sel($flt['pay'], $ps) ?>><?= e(t('pay.status.' . $ps)) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= e($flt['from']) ?>" aria-label="<?= e(t('order.from')) ?>">
        <input type="date" name="to" value="<?= e($flt['to']) ?>" aria-label="<?= e(t('order.to')) ?>">
        <button class="b1" type="submit"><?= e(t('order.filter')) ?></button>
        <a class="b2" href="<?= e(url('admin/orders')) ?>"><?= e(t('order.clear')) ?></a>
        <a class="b2" href="<?= e(url('admin/orders/export', array_filter($flt, fn($v) => $v !== ''))) ?>"><?= e(t('order.export')) ?></a>
    </div>
</form>

<?php /* ----- KHOI 4: Bang danh sach ----- */ ?>
<div class="c">
    <table class="tb">
        <thead>
        <tr><th class="no"><?= e(t('common.no')) ?></th>
            <th><?= e(t('order.col.code')) ?></th>
            <th><?= e(t('order.col.customer')) ?></th>
            <th><?= e(t('order.col.date')) ?></th>
            <th class="r"><?= e(t('order.col.total')) ?></th>
            <th><?= e(t('order.col.payment')) ?></th>
            <th><?= e(t('order.col.status')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['items'] as $i => $o): ?>
            <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no($result, $i) ?></td>
                <td data-label="<?= e(t('order.col.code')) ?>"><a class="lk" href="<?= e(url('admin/orders/show', ['id' => $o['id']])) ?>"><?= e($o['order_code']) ?></a></td>
                <td data-label="<?= e(t('order.col.customer')) ?>"><b><?= e($o['customer_name']) ?></b><br><span class="hint"><?= e($o['customer_phone']) ?></span></td>
                <td data-label="<?= e(t('order.col.date')) ?>"><?= e($fmt($o['created_at'])) ?></td>
                <td class="r" data-label="<?= e(t('order.col.total')) ?>"><b><?= e(format_price($o['total_amount'])) ?></b></td>
                <td data-label="<?= e(t('order.col.payment')) ?>"><span class="p <?= e(AdminOrder::payClass($o['pay_status'])) ?>"><?= e(t(AdminOrder::payKey($o['pay_status']))) ?></span></td>
                <td data-label="<?= e(t('order.col.status')) ?>"><span class="p <?= e(AdminOrder::statusClass($o['status'])) ?>"><?= e(t('order.status.' . $o['status'])) ?></span></td>
                <td data-label=""><a class="lk" href="<?= e(url('admin/orders/show', ['id' => $o['id']])) ?>"><?= e(t('order.view')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?>
            <tr><td colspan="8" class="hint"><?= e(t('order.empty')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php require BASE_PATH . '/views/admin/layout/pagination.php'; ?>
</div>
