<?php
/* ==============================================================================
   VIEW: TAO / SUA MA GIAM GIA
   File    : views/admin/coupons/form.php
   Dia chi  : ?r=admin/coupons/form
   Controller: controllers/AdminCouponController.php  (ham form)
   Model   : models/AdminCoupon.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 3 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var ?array $coupon @var array $old @var array $errors @var array $usages @var bool $locked */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$type = (string) ($old['discount_type'] ?? 'percent');
$status = (string) ($old['status'] ?? 'active');
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <a class="lk" href="<?= e(url('admin/coupons')) ?>">‹ <?= e(t('nav.coupons')) ?></a>
        <h1><?= e($coupon ? t('coupon.edit_title') : t('coupon.new_title')) ?></h1>
        <div class="sub"><?= e(t('coupon.form_sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Form nhap lieu ----- */ ?>
<form class="c fm" method="post" action="<?= e(url('admin/coupons/form', $coupon ? ['id' => $coupon['id']] : [])) ?>" style="max-width:820px">
    <?= csrf_field() ?>
    <?php if ($locked): ?>
        <div class="warn" style="margin-bottom:14px"><?= e(t('coupon.locked_note', ['n' => (int) $coupon['used_count']])) ?></div>
    <?php endif; ?>
    <label><?= e(t('coupon.f.code')) ?>
        <input name="code" maxlength="50" value="<?= val($old, 'code') ?>" required<?= $locked ? ' readonly' : '' ?>>
        <span class="hint"><?= e(t('coupon.f.code_hint')) ?></span><?= $err('code') ?>
    </label>
    <div class="gr2">
        <label><?= e(t('coupon.f.type')) ?>
            <select name="discount_type"<?= $locked ? ' disabled' : '' ?>>
                <option value="percent"<?= sel($type, 'percent') ?>><?= e(t('coupon.type.percent')) ?></option>
                <option value="fixed"<?= sel($type, 'fixed') ?>><?= e(t('coupon.type.fixed')) ?></option>
            </select><?= $err('discount_type') ?>
        </label>
        <label><?= e(t('coupon.f.value')) ?>
            <span class="unit-wrap">
                <input name="discount_value"<?= $locked ? ' readonly' : '' ?> inputmode="numeric" data-money placeholder="<?= e(t('ph.example', ['v' => $type === 'percent' ? '10' : '10.000'])) ?>" data-ph-percent="<?= e(t('ph.example', ['v' => '10'])) ?>" data-ph-fixed="<?= e(t('ph.example', ['v' => '10.000'])) ?>" value="<?= val($old, 'discount_value') ?>" required>
                <b class="unit" data-unit><?= $type === 'percent' ? '%' : '₫' ?></b>
            </span>
            <span class="hint" data-hint data-hint-percent="<?= e(t('coupon.f.value_hint_pct')) ?>" data-hint-fixed="<?= e(t('coupon.f.value_hint_fixed')) ?>"><?= e(t($type === 'percent' ? 'coupon.f.value_hint_pct' : 'coupon.f.value_hint_fixed')) ?></span><?= $err('discount_value') ?>
        </label>
        <label><?= e(t('coupon.f.max')) ?>
            <input name="max_discount_amount"<?= $locked ? ' readonly' : '' ?> inputmode="numeric" data-money placeholder="<?= e(t('ph.example', ['v' => '50.000'])) ?>" value="<?= val($old, 'max_discount_amount') ?>">
            <span class="hint"><?= e(t('coupon.f.max_hint')) ?></span><?= $err('max_discount_amount') ?>
        </label>
        <label><?= e(t('coupon.f.min')) ?>
            <input name="min_order_amount" inputmode="numeric" data-money placeholder="<?= e(t('ph.example', ['v' => '200.000'])) ?>" value="<?= val($old, 'min_order_amount') ?>">
            <span class="hint"><?= e(t('coupon.f.min_hint')) ?></span><?= $err('min_order_amount') ?>
        </label>
        <label><?= e(t('coupon.f.limit')) ?>
            <input name="usage_limit" inputmode="numeric" data-money placeholder="<?= e(t('ph.example', ['v' => '100'])) ?>" value="<?= val($old, 'usage_limit') ?>">
            <span class="hint"><?= e(t('coupon.f.limit_hint')) ?></span><?= $err('usage_limit') ?>
        </label>
        <label><?= e(t('coupon.f.status')) ?>
            <select name="status">
                <option value="active"<?= sel($status, 'active') ?>><?= e(t('coupon.status.on')) ?></option>
                <option value="inactive"<?= sel($status, 'inactive') ?>><?= e(t('coupon.status.off')) ?></option>
            </select><?= $err('status') ?>
        </label>
        <label><?= e(t('coupon.f.start')) ?>
            <input type="datetime-local" name="start_at" value="<?= val($old, 'start_at') ?>" required><?= $err('start_at') ?>
        </label>
        <label><?= e(t('coupon.f.end')) ?>
            <input type="datetime-local" name="end_at" value="<?= val($old, 'end_at') ?>" required>
            <span class="hint"><?= e(t('coupon.f.end_hint')) ?></span><?= $err('end_at') ?>
        </label>
    </div>
    <div class="rr">
        <button class="b1" type="submit"><?= e(t('coupon.save')) ?></button>
        <a class="b2" href="<?= e(url('admin/coupons')) ?>"><?= e(t('common.cancel')) ?></a>
    </div>
</form>

<?php if ($coupon): ?>
<?php /* ----- KHOI 3: Lich su su dung ma (doc tu bang coupon_usages cua TV4) ----- */ ?>
<div class="c" style="max-width:820px;margin-top:16px">
    <h2><?= e(t('coupon.usage.title')) ?></h2>
    <?php if (!$usages): ?>
        <div class="hint"><?= e(t('coupon.usage.empty')) ?></div>
    <?php else: ?>
        <table class="tb">
            <thead><tr>
                <th><?= e(t('coupon.usage.order')) ?></th><th><?= e(t('coupon.usage.customer')) ?></th>
                <th class="r"><?= e(t('coupon.usage.discount')) ?></th><th><?= e(t('coupon.usage.time')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($usages as $u): ?>
                <tr>
                    <td data-label="<?= e(t('coupon.usage.order')) ?>"><a class="lk" href="<?= e(url('admin/orders/show', ['id' => $u['order_id']])) ?>"><?= e($u['order_code']) ?></a></td>
                    <td data-label="<?= e(t('coupon.usage.customer')) ?>"><?= e($u['full_name']) ?><br><span class="hint"><?= e($u['email']) ?></span></td>
                    <td class="r" data-label="<?= e(t('coupon.usage.discount')) ?>"><b><?= e(format_price($u['discount_amount'])) ?></b></td>
                    <td data-label="<?= e(t('coupon.usage.time')) ?>"><?= e(date('d/m/Y H:i', strtotime((string) $u['used_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php endif; ?>
