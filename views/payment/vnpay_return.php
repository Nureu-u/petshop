<?php
/**
 * Kết quả thanh toán VNPAY - views/payment/vnpay_return.php
 * Giữ giao diện của TV2 (payment/vnpay_return.php); dữ liệu $result do PaymentController::vnpay_return() cấp
 * sau khi đã kiểm tra chữ ký và cập nhật payments/orders (view không còn tự đọc $_GET).
 */
$state = $result['state'] ?? 'invalid';
$isSuccess = $state === 'success';
$isReview  = $state === 'review';
?>

<div class="max-w-xl mx-auto px-4 py-12">
    <div class="card-warm p-6 sm:p-8 bg-white border border-stoneWarm rounded-2xl shadow-lg text-center space-y-5">
        <?php if ($isSuccess): ?>
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-inner">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-200">
                    Thanh toán: Đã thanh toán • Đơn hàng: Đã xác nhận
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold text-espresso mt-3">Thanh toán VNPAY thành công!</h1>
                <p class="text-xs text-mutedBrown mt-1">Chữ ký HMAC-SHA512 hợp lệ. Đơn hàng của bạn đã được chuyển sang trạng thái <strong>Đã xác nhận</strong>.</p>
            </div>
        <?php elseif ($isReview): ?>
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-3xl mx-auto">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-espresso mt-3">Giao dịch cần cửa hàng xác nhận</h1>
                <p class="text-xs text-mutedBrown mt-1"><?php echo htmlspecialchars($result['message'] ?? ''); ?></p>
            </div>
        <?php elseif ($state === 'failed'): ?>
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-3xl mx-auto">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <div>
                <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-700 font-bold text-xs border border-rose-200">
                    Thanh toán: Thất bại (Mã lỗi: <?php echo htmlspecialchars($result['response_code'] ?? ''); ?>)
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold text-espresso mt-3">Giao dịch thanh toán không thành công</h1>
                <p class="text-xs text-mutedBrown mt-1">
                    [EF2] <?php echo htmlspecialchars($result['message'] ?? ''); ?>
                    Đơn hàng đang được giữ ở trạng thái <strong>Chờ thanh toán</strong>.
                </p>
            </div>
        <?php else: ?>
            <div class="w-16 h-16 rounded-full bg-stone-100 text-stone-500 flex items-center justify-center text-3xl mx-auto">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-espresso mt-3">Không thể xác thực giao dịch</h1>
                <p class="text-xs text-mutedBrown mt-1"><?php echo htmlspecialchars($result['message'] ?? ''); ?></p>
            </div>
        <?php endif; ?>

        <?php if (in_array($state, ['success', 'failed', 'review'], true)): ?>
            <!-- Bảng chi tiết đối soát bảng `payments` -->
            <div class="bg-cream p-4 rounded-xl border border-stoneWarm text-left text-xs space-y-2.5 font-mono-code">
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Mã đơn hàng (`order_code`):</span>
                    <strong class="text-coral"><?php echo htmlspecialchars($result['order_code']); ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Mã tham chiếu (`vnp_TxnRef`):</span>
                    <strong class="text-espresso"><?php echo htmlspecialchars($result['txn_ref']); ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Mã GD tại VNPAY (`vnp_TransactionNo`):</span>
                    <strong class="text-espresso"><?php echo htmlspecialchars($result['transaction_no'] ?: '—'); ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Ngân hàng (`vnp_BankCode`):</span>
                    <strong class="text-espresso"><?php echo htmlspecialchars($result['bank_code'] ?: '—'); ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Thời gian (`vnp_PayDate`):</span>
                    <strong class="text-espresso"><?php echo htmlspecialchars($result['paid_at'] ?: '—'); ?></strong>
                </div>
                <div class="border-t border-stoneWarm pt-2.5 flex justify-between text-sm font-extrabold">
                    <span class="font-sans text-espresso">Số tiền thanh toán:</span>
                    <span class="text-coral"><?php echo number_format((int) $result['amount'], 0, ',', '.'); ?> ₫</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <?php if (!empty($result['can_retry'])): ?>
                <a href="<?php echo e(url('payment/vnpay_create', ['order_id' => $result['order_id']])); ?>" class="flex-1 py-3 px-4 bg-coral hover:bg-coral-hover text-white font-bold text-xs rounded-xl shadow transition">
                    <i class="fa-solid fa-rotate-right mr-1.5"></i> Thanh toán lại
                </a>
            <?php endif; ?>
            <a href="<?php echo e(url('orders')); ?>" class="flex-1 py-3 px-4 <?php echo empty($result['can_retry']) ? 'bg-coral hover:bg-coral-hover text-white' : 'bg-stone-100 hover:bg-stone-200 text-espresso'; ?> font-bold text-xs rounded-xl transition">
                <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Xem lịch sử đơn hàng
            </a>
            <a href="<?php echo e(url('home')); ?>" class="flex-1 py-3 px-4 bg-stone-100 hover:bg-stone-200 text-espresso font-bold text-xs rounded-xl transition">
                Về trang chủ
            </a>
        </div>
    </div>
</div>
