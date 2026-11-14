<?php
/* ==============================================================================
   VIEW: HOA DON IN (trang rieng, khong co menu)
   File    : views/admin/orders/print.php
   Dia chi  : ?r=admin/orders/invoice
   Controller: controllers/AdminOrderController.php  (ham invoice)
   Model   : models/AdminOrder.php
   CSS      : public/css/admin-ui.css, nhom [14] CHI TIET DON HANG VA CAC KHOI PHU.
   ============================================================================== */
?>
<?php
/** @var array $order @var array $items @var ?array $coupon */
$fmt = fn($d) => date('d/m/Y H:i', strtotime((string) $d));
?>
<!DOCTYPE html>
<html lang="<?= e(AdminView::lang()) ?>">
<head>
    <meta charset="utf-8">
    <title><?= e(t('invoice.title')) ?> <?= e($order['order_code']) ?></title>
    <style>
        body{font-family:Arial,sans-serif;color:#111;margin:30px;font-size:14px}
        h1{margin:0 0 4px}table{width:100%;border-collapse:collapse;margin-top:14px}
        th,td{border-bottom:1px solid #ccc;padding:8px;text-align:left}.r{text-align:right}
        .tot{margin-left:auto;width:300px;margin-top:12px}.tot div{display:flex;justify-content:space-between;padding:4px 0}
        .t{font-weight:bold;font-size:16px;border-top:1px solid #333}
        @media print{.noprint{display:none}}
    </style>
</head>
<body onload="window.print()">
<h1>Pet Shop</h1>
<div><?= e(t('invoice.title')) ?>: <b><?= e($order['order_code']) ?></b> · <?= e($fmt($order['created_at'])) ?></div>
<p>
    <b><?= e($order['customer_name']) ?></b> · <?= e($order['customer_phone']) ?><br>
    <?= e($order['shipping_address']) ?>
</p>
<table>
    <tr><th><?= e(t('order.col.item')) ?></th><th class="r"><?= e(t('order.col.price')) ?></th><th class="r"><?= e(t('order.col.qty')) ?></th><th class="r"><?= e(t('order.col.line')) ?></th></tr>
    <?php foreach ($items as $it): ?>
        <tr>
            <td><?= e($it['item_name']) ?></td>
            <td class="r"><?= e(format_price($it['unit_price'])) ?></td>
            <td class="r"><?= (int) $it['quantity'] ?></td>
            <td class="r"><?= e(format_price($it['line_total'])) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<div class="tot">
    <div><span><?= e(t('order.subtotal')) ?></span><span><?= e(format_price($order['subtotal'])) ?></span></div>
    <div><span><?= e(t('order.discount')) ?><?= $coupon ? ' (' . e($coupon['code']) . ')' : '' ?></span><span>- <?= e(format_price($order['discount_amount'])) ?></span></div>
    <div><span><?= e(t('order.shipping')) ?></span><span><?= e(format_price($order['shipping_fee'])) ?></span></div>
    <div class="t"><span><?= e(t('order.total')) ?></span><span><?= e(format_price($order['total_amount'])) ?></span></div>
</div>
<p class="noprint"><button onclick="window.print()"><?= e(t('order.print')) ?></button></p>
</body>
</html>
