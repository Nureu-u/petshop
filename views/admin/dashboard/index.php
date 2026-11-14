<?php
/* ==============================================================================
   VIEW: TONG QUAN (BANG DIEU KHIEN)
   File    : views/admin/dashboard/index.php
   Dia chi  : ?r=admin/dashboard
   Controller: controllers/AdminDashboardController.php  (ham index)
   Model   : models/AdminDashboard.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [8] THE SO
           LIEU CO O BIEU TUONG; [9] BIEU DO COT, THANH PHAN BO, VONG TRON TRANG TH; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI
           TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 7 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/**
 * @var string $period @var string $rangeCur @var string $rangePrev @var array $chips @var array $kpi
 * @var array $chart @var array $groups @var array $statusCounts @var int $ordersTotal @var array $vnpay
 * @var array $recent @var array $best @var string $bestKind @var array $low @var array $petcats @var array $petStatus
 */
$periods = ['today' => 'dash.p.today', '7d' => 'dash.p.7d', '30d' => 'dash.p.30d', 'year' => 'dash.p.year'];
$chg = function ($c, $p): string {
    $v = AdminDashboard::pct((float) $c, (float) $p);
    if ($v === null) {
        return '';
    }
    return '<span class="' . ($v >= 0 ? 'up' : 'down') . '">' . ($v >= 0 ? '+' : '') . $v . '%</span>';
};
$colors = ['PENDING_PAYMENT' => '#f59e0b', 'CONFIRMED' => '#3b82f6', 'SHIPPING' => '#8b5cf6', 'DELIVERED' => '#19b27a', 'CANCELLED' => '#ef4444'];
$donut = 'conic-gradient(#e6edf8 0 100%)';
if ($ordersTotal > 0) {
    $acc = 0.0;
    $stops = [];
    foreach ($colors as $stt => $col) {
        $n = (int) ($statusCounts[$stt] ?? 0);
        if ($n === 0) {
            continue;
        }
        $from = $acc;
        $acc += $n / $ordersTotal * 100;
        $stops[] = $col . ' ' . sprintf('%.2F', $from) . '% ' . sprintf('%.2F', $acc) . '%';
    }
    $donut = 'conic-gradient(' . implode(',', $stops) . ')';
}
$unitKey = $period === 'today' ? 'hour' : ($period === 'year' ? 'month' : 'day');
$nice = (float) $chart['nice'];
$step = (float) $chart['step'];
$paid = (int) ($vnpay['counts']['PAID'] ?? 0);
$payTotal = (int) $vnpay['total'];
$rate = $payTotal > 0 ? round($paid / $payTotal * 100, 1) : 0;
$maxCat = 1;
foreach ($petcats as $pc) {
    $maxCat = max($maxCat, (int) $pc['n']);
}
$fmtBest = fn($k) => url('admin/dashboard', ['p' => $period, 'best' => $k]);
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.dashboard')) ?></h1>
        <div class="sub"><?= e(t('dash.sub', ['cur' => $rangeCur, 'prev' => $rangePrev])) ?></div>
        <div class="vt" style="margin-top:10px">
            <?php foreach ($periods as $k => $lbl): ?>
                <a href="<?= e(url('admin/dashboard', ['p' => $k])) ?>"<?= $period === $k ? ' class="on"' : '' ?>><?= e(t($lbl)) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="acts">
        <a class="act" href="<?= e(url('admin/manage/pets/form')) ?>"><?= e(t('dash.add_pet')) ?></a>
        <a class="act" href="<?= e(url('admin/manage/products/form')) ?>"><?= e(t('dash.add_product')) ?></a>
        <a class="act" href="<?= e(url('admin/coupons/form')) ?>"><?= e(t('dash.add_coupon')) ?></a>
        <a class="act" href="<?= e(url('admin/posts/form')) ?>"><?= e(t('dash.add_post')) ?></a>
    </div>
</div>

<?php /* ----- KHOI 2: Cac o "can xu ly ngay" ----- */ ?>
<div class="chips">
    <div class="chip"><b data-count="<?= (int) $chips['confirmed'] ?>"><?= (int) $chips['confirmed'] ?></b><div><?= e(t('dash.chip.confirmed')) ?><small><a class="lk" href="<?= e(url('admin/orders', ['status' => 'CONFIRMED'])) ?>"><?= e(t('dash.see')) ?></a></small></div></div>
    <div class="chip"><b data-count="<?= (int) $chips['failed'] ?>"><?= (int) $chips['failed'] ?></b><div><?= e(t('dash.chip.failed')) ?><small><a class="lk" href="<?= e(url('admin/orders', ['pay' => 'FAILED'])) ?>"><?= e(t('dash.see')) ?></a></small></div></div>
    <div class="chip"><b data-count="<?= (int) $chips['stock'] ?>"><?= (int) $chips['stock'] ?></b><div><?= e(t('dash.chip.stock')) ?><small><a class="lk" href="<?= e(url('admin/manage/products')) ?>"><?= e(t('dash.see')) ?></a></small></div></div>
    <div class="chip"><b data-count="<?= (int) $chips['coupons'] ?>"><?= (int) $chips['coupons'] ?></b><div><?= e(t('dash.chip.coupon')) ?><small><a class="lk" href="<?= e(url('admin/coupons')) ?>"><?= e(t('dash.see')) ?></a></small></div></div>
</div>

<?php /* ----- KHOI 3: The so lieu (Doanh thu, Don hang, Khach moi, Thu cung) ----- */ ?>
<div class="kpis">
    <div class="c kc"><div class="kc-main"><div class="lbl"><?= e(t('dash.revenue')) ?> <?= $chg($kpi['revenue'][0], $kpi['revenue'][1]) ?></div><div class="v"><?= e(money_short($kpi['revenue'][0])) ?></div><div class="hint"><?= e(t('dash.revenue_hint')) ?></div></div><span class="kc-ico k1"><?= AdminView::icon('coin', 22) ?></span></div>
    <div class="c kc"><div class="kc-main"><div class="lbl"><?= e(t('dash.orders')) ?> <?= $chg($kpi['orders'][0], $kpi['orders'][1]) ?></div><div class="v" data-count="<?= (int) $kpi['orders'][0] ?>"><?= (int) $kpi['orders'][0] ?></div><div class="hint"><?= e(t('dash.orders_hint')) ?></div></div><span class="kc-ico k2"><?= AdminView::icon('box', 22) ?></span></div>
    <div class="c kc"><div class="kc-main"><div class="lbl"><?= e(t('dash.new_customers')) ?> <?= $chg($kpi['customers'][0], $kpi['customers'][1]) ?></div><div class="v" data-count="<?= (int) $kpi['customers'][0] ?>"><?= (int) $kpi['customers'][0] ?></div><div class="hint"><?= e(t('dash.customers_hint')) ?></div></div><span class="kc-ico k3"><?= AdminView::icon('users', 22) ?></span></div>
    <div class="c kc"><div class="kc-main"><div class="lbl"><?= e(t('dash.pets_selling')) ?></div><div class="v" data-count="<?= (int) ($petStatus['available'] ?? 0) ?>"><?= (int) ($petStatus['available'] ?? 0) ?></div><div class="hint"><?= e(t('dash.pets_sold', ['n' => (int) ($petStatus['sold'] ?? 0)])) ?></div></div><span class="kc-ico k4"><?= AdminView::icon('pets', 22) ?></span></div>
</div>

<?php /* ----- KHOI 4: Bieu do chinh va cac khoi ben phai ----- */ ?>
<div class="r2">
    <div class="c">
        <h2><?= e(t('dash.chart.' . $unitKey)) ?></h2>
        <p class="d"><?= e(t('dash.chart.hint')) ?></p>
        <div class="lg"><span><i class="cur"></i><?= e(t('dash.cur')) ?></span><span><i class="prev"></i><?= e(t('dash.prev')) ?></span></div>
        <div class="plot">
            <?php foreach ([3, 2, 1, 0] as $i): ?>
                <div class="gl" style="top:<?= (3 - $i) * 70 ?>px"><span><?= e($i === 0 ? '0' : money_short($step * $i)) ?></span></div>
            <?php endforeach; ?>
            <div class="cols">
                <?php foreach ($chart['cur'] as $i => $cv): ?>
                    <div class="g" style="--i:<?= (int) $i ?>">
                        <i class="prev" style="height:<?= sprintf('%.1F', min(100, $chart['prev'][$i] / $nice * 100)) ?>%"></i>
                        <i class="cur" style="height:<?= sprintf('%.1F', min(100, $cv / $nice * 100)) ?>%"></i>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="xl2">
            <?php foreach ($chart['labels'] as $lb): ?><span><?= e($lb) ?></span><?php endforeach; ?>
        </div>
    </div>

    <div class="c">
        <h2><?= e(t('dash.group.title')) ?></h2>
        <div class="hint"><?= e(t('dash.group.split', ['a' => round($groups['petPct']), 'b' => round($groups['prodPct'])])) ?></div>
        <div class="split">
            <i style="width:<?= sprintf('%.1F', $groups['petPct']) ?>%;background:#2f7bf0"></i>
            <i style="width:<?= sprintf('%.1F', $groups['prodPct']) ?>%;background:#19b27a"></i>
        </div>
        <?php foreach ($groups['rows'] as $g): ?>
            <div class="row"><span><?= e(($g['kind'] === 'pet' ? t('dash.group.pets') : t('dash.group.products')) . ' · ' . $g['name']) ?></span><b><?= e(money_short($g['amount'])) ?></b></div>
        <?php endforeach; ?>
        <?php if ($groups['other'] > 0): ?>
            <div class="row"><span><?= e(t('dash.group.other')) ?></span><b><?= e(money_short($groups['other'])) ?></b></div>
        <?php endif; ?>
        <?php if (!$groups['rows']): ?><div class="hint"><?= e(t('dash.empty')) ?></div><?php endif; ?>
    </div>
</div>

<?php /* ----- KHOI 5: Cum hai the phia duoi ----- */ ?>
<div class="r2b">
    <div class="c">
        <h2><?= e(t('dash.status.title')) ?></h2>
        <div style="display:flex;gap:18px;align-items:center">
            <div class="donut" style="background:<?= e($donut) ?>"><div><b><?= (int) $ordersTotal ?></b><?= e(t('dash.orders_unit')) ?></div></div>
            <div style="flex:1;min-width:0">
                <?php foreach ($colors as $stt => $col): ?>
                    <div class="row"><span><i class="dot" style="background:<?= e($col) ?>"></i><?= e(t('order.status.' . $stt)) ?></span><b><?= (int) ($statusCounts[$stt] ?? 0) ?></b></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="c">
        <h2><?= e(t('dash.vnpay.title')) ?></h2>
        <div class="v" style="margin:0"><?= e(number_format((float) $rate, 1, ',', '.')) ?>%</div>
        <div class="hint"><?= e(t('dash.vnpay.rate', ['ok' => $paid, 'all' => $payTotal])) ?></div>
        <div class="split" style="margin:8px 0"><i style="width:<?= sprintf('%.1F', (float) $rate) ?>%;background:#19b27a"></i></div>
        <?php foreach (['PAID', 'PENDING', 'FAILED', 'REFUNDED'] as $ps): ?>
            <div class="row"><span><?= e(t('pay.status.' . $ps)) ?></span><b><?= (int) ($vnpay['counts'][$ps] ?? 0) ?></b></div>
        <?php endforeach; ?>
    </div>
</div>

<?php /* ----- KHOI 6: Cum hai the phia duoi ----- */ ?>
<div class="r2b">
    <div class="c">
        <div style="display:flex;justify-content:space-between"><h2><?= e(t('dash.recent')) ?></h2><a class="lk" href="<?= e(url('admin/orders')) ?>"><?= e(t('dash.see_all')) ?></a></div>
        <table class="tb">
            <thead><tr>
                <th><?= e(t('order.col.code')) ?></th><th><?= e(t('order.col.customer')) ?></th><th class="r"><?= e(t('order.col.total')) ?></th>
                <th><?= e(t('order.col.payment')) ?></th><th><?= e(t('order.col.status')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent as $o): ?>
                <tr>
                    <td data-label="<?= e(t('order.col.code')) ?>"><a class="lk" href="<?= e(url('admin/orders/show', ['id' => $o['id']])) ?>"><?= e($o['order_code']) ?></a></td>
                    <td data-label="<?= e(t('order.col.customer')) ?>"><?= e($o['customer_name']) ?></td>
                    <td class="r" data-label="<?= e(t('order.col.total')) ?>"><b><?= e(format_price($o['total_amount'])) ?></b></td>
                    <td data-label="<?= e(t('order.col.payment')) ?>"><span class="p <?= e(AdminOrder::payClass($o['pay_status'])) ?>"><?= e(t(AdminOrder::payKey($o['pay_status']))) ?></span></td>
                    <td data-label="<?= e(t('order.col.status')) ?>"><span class="p <?= e(AdminOrder::statusClass($o['status'])) ?>"><?= e(t('order.status.' . $o['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?><tr><td colspan="5" class="hint"><?= e(t('order.empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="c">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <h2><?= e(t('dash.best.title')) ?></h2>
            <div class="vt">
                <a href="<?= e($fmtBest('product')) ?>" data-keep-scroll<?= $bestKind === 'product' ? ' class="on"' : '' ?>><?= e(t('dash.best.product')) ?></a>
                <a href="<?= e($fmtBest('pet')) ?>" data-keep-scroll<?= $bestKind === 'pet' ? ' class="on"' : '' ?>><?= e(t('dash.best.pet')) ?></a>
            </div>
        </div>
        <?php foreach ($best as $i => $b): ?>
            <div class="row"><span><b><?= $i + 1 ?></b> · <?= e($b['name']) ?></span><span class="hint"><?= e(t('dash.sold_n', ['n' => (int) $b['qty']])) ?></span></div>
        <?php endforeach; ?>
        <?php if (!$best): ?><div class="hint"><?= e(t('dash.empty')) ?></div><?php endif; ?>
    </div>
</div>

<?php /* ----- KHOI 7: Cum hai the phia duoi ----- */ ?>
<div class="r2b">
    <div class="c">
        <div style="display:flex;justify-content:space-between"><h2><?= e(t('dash.low.title')) ?></h2><a class="lk" href="<?= e(url('admin/manage/products')) ?>"><?= e(t('dash.see_all')) ?></a></div>
        <?php foreach ($low as $p): ?>
            <div class="row">
                <a class="lk" href="<?= e(url('admin/manage/products/form', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a>
                <?php if ((int) $p['stock'] === 0): ?><span class="p rd"><?= e(t('dash.out')) ?></span>
                <?php else: ?><span class="p y"><?= e(t('dash.left', ['n' => (int) $p['stock']])) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$low): ?><div class="hint"><?= e(t('dash.low.none')) ?></div><?php endif; ?>
    </div>
    <div class="c">
        <div style="display:flex;justify-content:space-between"><h2><?= e(t('dash.pets_cat')) ?></h2><a class="lk" href="<?= e(url('admin/manage/pets')) ?>"><?= e(t('dash.see_all')) ?></a></div>
        <?php foreach ($petcats as $pc): ?>
            <div class="row"><span><?= e($pc['name']) ?></span><b><?= (int) $pc['n'] ?></b></div>
            <div class="rbar"><i style="width:<?= sprintf('%.1F', (int) $pc['n'] / $maxCat * 100) ?>%"></i></div>
        <?php endforeach; ?>
        <div class="rr" style="margin-top:8px">
            <span class="p gr"><?= e(t('dash.st.available', ['n' => (int) ($petStatus['available'] ?? 0)])) ?></span>
            <span class="p bl"><?= e(t('dash.st.sold', ['n' => (int) ($petStatus['sold'] ?? 0)])) ?></span>
            <span class="p y"><?= e(t('dash.st.hidden', ['n' => (int) ($petStatus['hidden'] ?? 0)])) ?></span>
        </div>
    </div>
</div>
