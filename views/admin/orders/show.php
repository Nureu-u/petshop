<?php
/* ==============================================================================
   VIEW: CHI TIET DON HANG
   File    : views/admin/orders/show.php
   Dia chi  : ?r=admin/orders/show
   Controller: controllers/AdminOrderController.php  (ham show)
   Model   : models/AdminOrder.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI
           TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $order @var array $items @var array $payments @var ?array $coupon */
$s = $order['status'];
$allowed = AdminOrder::NEXT[$s] ?? [];
$forward = array_values(array_filter($allowed, fn($x) => $x !== 'CANCELLED'));
$canCancel = in_array('CANCELLED', $allowed, true);
$flow = ['PENDING_PAYMENT', 'CONFIRMED', 'SHIPPING', 'DELIVERED'];
$pos = array_search($s, $flow, true);
$fmt = fn($d) => date('d/m/Y H:i', strtotime((string) $d));
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <a class="lk" href="<?= e(url('admin/orders')) ?>">‹ <?= e(t('nav.orders')) ?></a>
        <h1><?= e($order['order_code']) ?> <span class="p <?= e(AdminOrder::statusClass($s)) ?>" style="font-size:13px;vertical-align:middle"><?= e(t('order.status.' . $s)) ?></span></h1>
        <div class="sub"><?= e(t('order.placed_at')) ?> <?= e($fmt($order['created_at'])) ?> · <?= e(t('order.updated_at')) ?> <?= e($fmt($order['updated_at'])) ?></div>
    </div>
    <div class="rr">
        <a class="b2" target="_blank" href="<?= e(url('admin/orders/invoice', ['id' => $order['id']])) ?>"><?= e(t('order.print')) ?></a>
        <?php if ($canCancel): ?>
            <form method="post" action="<?= e(url('admin/orders/status')) ?>" data-confirm="<?= e(t('order.cancel_confirm')) ?>" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($order['id']) ?>">
                <input type="hidden" name="status" value="CANCELLED">
                <button class="b3" type="submit"><?= e(t('order.cancel')) ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php /* ----- KHOI 2: Luoi hai cot (noi dung chinh | thong tin phu) ----- */ ?>
<div class="dg">
    <div>
        <div class="c">
            <h2><?= e(t('order.items')) ?></h2>
            <table class="tb">
                <thead>
                <tr>
                    <th><?= e(t('order.col.item')) ?></th><th><?= e(t('order.col.type')) ?></th>
                    <th class="r"><?= e(t('order.col.price')) ?></th><th class="r"><?= e(t('order.col.qty')) ?></th>
                    <th class="r"><?= e(t('order.col.line')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td data-label="<?= e(t('order.col.item')) ?>"><?= e($it['item_name']) ?></td>
                        <td data-label="<?= e(t('order.col.type')) ?>"><?= e(t(!empty($it['pet_id']) ? 'order.type.pet' : 'order.type.product')) ?></td>
                        <td class="r" data-label="<?= e(t('order.col.price')) ?>"><?= e(format_price($it['unit_price'])) ?></td>
                        <td class="r" data-label="<?= e(t('order.col.qty')) ?>"><?= (int) $it['quantity'] ?></td>
                        <td class="r" data-label="<?= e(t('order.col.line')) ?>"><b><?= e(format_price($it['line_total'])) ?></b></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="tot">
                <div><span><?= e(t('order.subtotal')) ?></span><span><?= e(format_price($order['subtotal'])) ?></span></div>
                <div>
                    <span><?= e(t('order.discount')) ?><?php if ($coupon): ?> <span class="p gy"><?= e($coupon['code']) ?></span><?php endif; ?></span>
                    <span>- <?= e(format_price($order['discount_amount'])) ?></span>
                </div>
                <div><span><?= e(t('order.shipping')) ?></span><span><?= e(format_price($order['shipping_fee'])) ?></span></div>
                <div class="t"><span><?= e(t('order.total')) ?></span><span><?= e(format_price($order['total_amount'])) ?></span></div>
            </div>
        </div>

        <div class="c">
            <h2><?= e(t('order.payments')) ?></h2>
            <?php if (!$payments): ?>
                <div class="hint"><?= e(t('pay.status.NONE')) ?></div>
            <?php else: ?>
                <table class="tb">
                    <thead>
                    <tr>
                        <th><?= e(t('order.pay.method')) ?></th><th><?= e(t('order.pay.ref')) ?></th>
                        <th class="r"><?= e(t('order.pay.amount')) ?></th><th><?= e(t('order.pay.status')) ?></th>
                        <th><?= e(t('order.pay.txn')) ?></th><th><?= e(t('order.pay.bank')) ?></th><th><?= e(t('order.pay.time')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $pm): ?>
                        <tr>
                            <td data-label="<?= e(t('order.pay.method')) ?>"><?= e($pm['payment_method']) ?></td>
                            <td data-label="<?= e(t('order.pay.ref')) ?>"><?= e($pm['transaction_ref']) ?></td>
                            <td class="r" data-label="<?= e(t('order.pay.amount')) ?>"><?= e(format_price($pm['amount'])) ?></td>
                            <td data-label="<?= e(t('order.pay.status')) ?>"><span class="p <?= e(AdminOrder::payClass($pm['status'])) ?>"><?= e(t(AdminOrder::payKey($pm['status']))) ?></span>
                                <?php if (!empty($pm['vnp_response_code']) || !empty($pm['vnp_transaction_status'])): ?>
                                    <br><span class="hint mono"><?= e(t('order.pay.codes', ['rc' => $pm['vnp_response_code'] ?: '—', 'ts' => $pm['vnp_transaction_status'] ?: '—'])) ?></span>
                                <?php endif; ?></td>
                            <td data-label="<?= e(t('order.pay.txn')) ?>"><?= e($pm['vnp_transaction_no'] ?: '—') ?></td>
                            <td data-label="<?= e(t('order.pay.bank')) ?>"><?= e($pm['vnp_bank_code'] ?: '—') ?></td>
                            <td data-label="<?= e(t('order.pay.time')) ?>"><?= e($pm['vnp_paid_at'] ? $fmt($pm['vnp_paid_at']) : '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="c">
            <h2><?= e(t('order.update_status')) ?></h2>
            <div class="stp">
                <?php foreach ($flow as $i => $st): ?>
                    <?php
                    $cls = '';
                    $mark = '';
                    if ($pos !== false) {
                        if ($i < $pos) { $cls = 'ok'; $mark = '✓'; }
                        elseif ($i === $pos) { $cls = 'now'; $mark = '●'; }
                    }
                    ?>
                    <div class="<?= e($cls) ?>"><i><?= e($mark) ?></i><?= e(t('order.status.' . $st)) ?></div>
                <?php endforeach; ?>
            </div>
            <?php if ($s === 'CANCELLED'): ?>
                <div class="hint"><?= e(t('order.cancelled_note')) ?></div>
            <?php elseif (!$forward): ?>
                <div class="hint"><?= e(t('order.no_next')) ?></div>
            <?php else: ?>
                <form method="post" action="<?= e(url('admin/orders/status')) ?>" class="fl">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e($order['id']) ?>">
                    <select class="grow" name="status" aria-label="<?= e(t('order.next_ph')) ?>">
                        <?php foreach ($forward as $nx): ?>
                            <option value="<?= e($nx) ?>"><?= e(t('order.move_to', ['s' => t('order.status.' . $nx)])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="b1" type="submit"><?= e(t('order.update')) ?></button>
                </form>
            <?php endif; ?>
            <div class="warn"><?= e(t('order.warn')) ?></div>
        </div>

        <div class="c">
            <h2><?= e(t('order.customer')) ?></h2>
            <div class="kv">
                <span><?= e(t('order.name')) ?></span><span><?= e($order['customer_name']) ?></span>
                <span><?= e(t('order.phone')) ?></span><span><?= e($order['customer_phone']) ?></span>
                <span><?= e(t('order.address')) ?></span><span><?= e($order['shipping_address']) ?></span>
                <span><?= e(t('order.note')) ?></span><span><?= e($order['note'] ?: '—') ?></span>
                <span><?= e(t('order.account')) ?></span><span><?= e($order['user_email'] ?: '—') ?></span>
            </div>
        </div>
    </div>
</div>
