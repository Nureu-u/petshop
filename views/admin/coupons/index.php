<?php
/* ==============================================================================
   VIEW: DANH SACH MA GIAM GIA
   File    : views/admin/coupons/index.php
   Dia chi  : ?r=admin/coupons
   Controller: controllers/AdminCouponController.php  (ham index)
   Model   : models/AdminCoupon.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [9] BIEU DO
           COT, THANH PHAN BO, VONG TRON TRANG TH; [10] NUT BAM; [13] BANG DANH SACH, NHAN TRANG
           THAI VA PHAN TRANG; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $items */
$fmt = fn($d) => date('d/m/Y H:i', strtotime((string) $d));
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.coupons')) ?></h1>
        <div class="sub"><?= e(t('coupon.sub')) ?></div>
    </div>
    <a class="b1" href="<?= e(url('admin/coupons/form')) ?>"><?= e(t('coupon.create')) ?></a>
</div>

<?php /* ----- KHOI 2: Bang danh sach ----- */ ?>
<div class="c">
    <table class="tb">
        <thead>
        <tr><th class="no"><?= e(t('common.no')) ?></th>
            <th><?= e(t('coupon.col.code')) ?></th><th><?= e(t('coupon.col.discount')) ?></th><th class="r"><?= e(t('coupon.col.min')) ?></th>
            <th><?= e(t('coupon.col.used')) ?></th><th><?= e(t('coupon.col.time')) ?></th><th><?= e(t('coupon.col.status')) ?></th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $c): ?>
            <?php
            $state = AdminCoupon::state($c);
            $limit = $c['usage_limit'] !== null ? (int) $c['usage_limit'] : null;
            $used = (int) $c['used_count'];
            $w = ($limit && $limit > 0) ? min(100, $used / $limit * 100) : 0;
            ?>
            <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no([], $i) ?></td>
                <td data-label="<?= e(t('coupon.col.code')) ?>"><b><?= e($c['code']) ?></b><br><span class="hint mono"><?= e($c['id']) ?></span></td>
                <td data-label="<?= e(t('coupon.col.discount')) ?>">
                    <?php if ($c['discount_type'] === 'percent'): ?>
                        <?= e(t('coupon.off_pct', ['v' => (int) $c['discount_value']])) ?>
                        <?php if ($c['max_discount_amount'] !== null): ?><br><span class="hint"><?= e(t('coupon.up_to', ['v' => format_price($c['max_discount_amount'])])) ?></span><?php endif; ?>
                    <?php else: ?>
                        <?= e(t('coupon.off_fixed', ['v' => format_price($c['discount_value'])])) ?>
                    <?php endif; ?>
                </td>
                <td class="r" data-label="<?= e(t('coupon.col.min')) ?>"><?= e(format_price($c['min_order_amount'])) ?></td>
                <td data-label="<?= e(t('coupon.col.used')) ?>" style="min-width:110px">
                    <?= $used ?> / <?= $limit !== null ? $limit : e(t('coupon.unlimited')) ?>
                    <?php if ($limit): ?><div class="rbar" style="margin:4px 0 0"><i style="width:<?= sprintf('%.1F', $w) ?>%"></i></div><?php endif; ?>
                </td>
                <td data-label="<?= e(t('coupon.col.time')) ?>"><?= e($fmt($c['start_at'])) ?><br><span class="hint">→ <?= e($fmt($c['end_at'])) ?></span></td>
                <td data-label="<?= e(t('coupon.col.status')) ?>"><span class="p <?= e(AdminCoupon::stateClass($state)) ?>"><?= e(t('coupon.state.' . $state)) ?></span></td>
                <td data-label="">
                    <div class="rr">
                        <a class="lk" href="<?= e(url('admin/coupons/form', ['id' => $c['id']])) ?>"><?= e(t('coupon.edit')) ?></a>
                        <form method="post" action="<?= e(url('admin/coupons/toggle')) ?>" style="margin:0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                            <button class="<?= $c['status'] === 'active' ? 'b3' : 'b2' ?> sm" type="submit"><?= e(t($c['status'] === 'active' ? 'coupon.turn_off' : 'coupon.turn_on')) ?></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="8" class="hint"><?= e(t('coupon.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="note"><?= e(t('coupon.note')) ?></div>
