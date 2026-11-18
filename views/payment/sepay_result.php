<?php
/** Kết quả thanh toán SePay - views/payment/sepay_result.php (giao diện giống trang VNPay cũ) */
$amount = (int) ($pay['amount'] ?? $order['total_amount'] ?? 0);
$code   = $order['order_code'] ?? $order['id'];
$cancelled = ($order['status'] ?? '') === 'CANCELLED';
$canRetry  = !$isPaid && !$cancelled && ($order['status'] ?? '') === 'PENDING_PAYMENT';
?>
<div class="max-w-xl mx-auto px-4 py-12">
    <div class="card-warm p-6 sm:p-8 bg-white border border-stoneWarm rounded-2xl shadow-lg text-center space-y-5">
        <?php if ($isPaid): ?>
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-inner">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-200">
                    Thanh toán: Đã thanh toán • Đơn hàng: Đã xác nhận
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold text-espresso mt-3">Thanh toán thành công!</h1>
                <p class="text-xs text-mutedBrown mt-1">SePay đã xác nhận nhận được tiền chuyển khoản. Đơn hàng của bạn đã được chuyển sang trạng thái <strong>Đã xác nhận</strong>.</p>
            </div>
        <?php else: ?>
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-3xl mx-auto">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <div>
                <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-700 font-bold text-xs border border-rose-200">
                    Thanh toán: Thất bại • Đơn hàng: <?= $cancelled ? 'Đã hủy' : 'Chờ thanh toán' ?>
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold text-espresso mt-3">Giao dịch thanh toán không thành công</h1>
                <p class="text-xs text-mutedBrown mt-1">
                    <?php if ($cancelled): ?>
                        Đơn hàng đã hết hạn thanh toán hoặc đã bị hủy, hệ thống chưa nhận được tiền chuyển khoản. Nếu bạn đã chuyển tiền, vui lòng liên hệ cửa hàng để được hỗ trợ.
                    <?php else: ?>
                        Hệ thống chưa nhận được tiền chuyển khoản. Đơn hàng đang được giữ ở trạng thái <strong>Chờ thanh toán</strong>, bạn có thể thanh toán lại.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="bg-cream p-4 rounded-xl border border-stoneWarm text-left text-xs space-y-2.5">
            <div class="flex justify-between">
                <span class="text-mutedBrown">Mã đơn hàng:</span>
                <strong class="text-coral"><?= e($code) ?></strong>
            </div>
            <div class="border-t border-stoneWarm pt-2.5 flex justify-between text-sm font-extrabold">
                <span class="text-espresso">Số tiền thanh toán:</span>
                <span class="text-coral"><?= number_format($amount, 0, ',', '.') ?> ₫</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <?php if ($canRetry): ?>
                <a href="<?= e(url('payment/sepay_create', ['order_id' => $order['id']])) ?>" class="flex-1 py-3 px-4 bg-coral hover:bg-coral-hover text-white font-bold text-xs rounded-xl shadow transition"><i class="fa-solid fa-rotate-right mr-1.5"></i> Thanh toán lại</a>
            <?php endif; ?>
            <a href="<?= e(url('orders')) ?>" class="flex-1 py-3 px-4 <?= ($isPaid || !$canRetry) ? 'bg-coral hover:bg-coral-hover text-white' : 'bg-stone-100 hover:bg-stone-200 text-espresso' ?> font-bold text-xs rounded-xl transition">
                <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Xem lịch sử đơn hàng
            </a>
            <a href="<?= e(url('home')) ?>" class="flex-1 py-3 px-4 bg-stone-100 hover:bg-stone-200 text-espresso font-bold text-xs rounded-xl transition">Về trang chủ</a>
        </div>
    </div>
</div>
