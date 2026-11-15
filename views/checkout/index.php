<?php
/**
 * Giao diện Đặt hàng, áp dụng mã giảm giá và chọn phương thức thanh toán.
 * Biến nhận từ CheckoutController::index(): $user, $cartItems, $subtotal, $shippingFee.
 */
$currentUser = $user ?? (Auth::user() ?: [
    'id' => '',
    'full_name' => '',
    'phone' => '',
    'address' => ''
]);

$checkoutItems = $cartItems ?? [];
$subtotal = (int)($subtotal ?? 0);
$shippingFee = (int)($shippingFee ?? 50000);
$totalAmount = $subtotal + $shippingFee;
?>

<div class="max-w-7xl mx-auto px-2 sm:px-4 py-6">
    <!-- Đường dẫn điều hướng (Breadcrumb) -->
    <nav class="text-xs text-stone-500 mb-6 font-medium">
        <a href="<?= e(url('home')) ?>" class="hover:text-coral">Trang chủ</a>
        <span class="mx-2">/</span>
        <a href="<?= e(url('cart')) ?>" class="hover:text-coral">Giỏ hàng</a>
        <span class="mx-2">/</span>
        <span class="text-espresso font-semibold">Thanh toán</span>
    </nav>

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-extrabold text-espresso">Xác nhận Đặt hàng & Thanh toán</h1>
        <p class="text-xs text-mutedBrown mt-0.5">Điền thông tin giao hàng, áp dụng mã khuyến mãi và lựa chọn phương thức thanh toán</p>
    </div>

    <form id="checkoutForm" action="<?= e(url('order/create')) ?>" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <?= csrf_field() ?>
        <!-- Cột trái: Thông tin nhận hàng & Phương thức thanh toán -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 1. Thông tin người nhận hàng -->
            <div class="card-warm p-6 bg-white border border-stoneWarm rounded-2xl space-y-4">
                <div class="flex items-center gap-2.5 border-b border-stoneWarm pb-3">
                    <span class="w-8 h-8 rounded-xl bg-coral-light text-coral flex items-center justify-center font-bold text-xs">1</span>
                    <h2 class="text-sm font-extrabold text-espresso uppercase tracking-wider">Thông tin người nhận hàng</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-espresso mb-1">Họ và tên người nhận <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_name" required maxlength="100" value="<?= e($currentUser['full_name'] ?? '') ?>"
                               placeholder="Nhập họ và tên người nhận"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-cream border border-stoneWarm focus:outline-none focus:border-coral">
                    </div>
                    <div>
                        <label class="block font-bold text-espresso mb-1">Số điện thoại liên hệ <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_phone" required maxlength="20" value="<?= e($currentUser['phone'] ?? '') ?>"
                               placeholder="VD: 0912345678"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-cream border border-stoneWarm font-mono-code focus:outline-none focus:border-coral">
                    </div>
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-espresso mb-1">Địa chỉ nhận hàng chi tiết <span class="text-rose-500">*</span></label>
                    <input type="text" name="shipping_address" required maxlength="255" value="<?= e($currentUser['address'] ?? '') ?>"
                           placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-cream border border-stoneWarm focus:outline-none focus:border-coral">
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-espresso mb-1">Ghi chú giao hàng</label>
                    <textarea name="note" rows="2" maxlength="500" placeholder="Thời gian giao hàng thuận tiện, lưu ý chăm sóc thú cưng..."
                              class="w-full px-3.5 py-2.5 rounded-xl bg-cream border border-stoneWarm focus:outline-none focus:border-coral"></textarea>
                </div>
            </div>

            <!-- 2. Chọn phương thức thanh toán (COD / VNPAY) -->
            <div class="card-warm p-6 bg-white border border-stoneWarm rounded-2xl space-y-4">
                <div class="flex items-center gap-2.5 border-b border-stoneWarm pb-3">
                    <span class="w-8 h-8 rounded-xl bg-coral-light text-coral flex items-center justify-center font-bold text-xs">2</span>
                    <h2 class="text-sm font-extrabold text-espresso uppercase tracking-wider">Phương thức thanh toán</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-xs">
                    <!-- Thanh toán khi nhận hàng (COD) -->
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-stoneWarm bg-cream/50 hover:border-coral cursor-pointer transition">
                        <input type="radio" name="payment_method" value="COD" checked class="mt-1 accent-coral">
                        <div>
                            <p class="font-extrabold text-espresso flex items-center gap-1.5">
                                <i class="fa-solid fa-truck-fast text-amber-600"></i>
                                <span>Tiền mặt (COD)</span>
                            </p>
                            <p class="text-[11px] text-mutedBrown mt-1">Kiểm tra thú cưng và sản phẩm trước khi thanh toán khi nhận hàng.</p>
                        </div>
                    </label>

                    <!-- Thanh toán trực tuyến VNPAY -->
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-stoneWarm bg-cream/50 hover:border-coral cursor-pointer transition">
                        <input type="radio" name="payment_method" value="VNPAY" class="mt-1 accent-coral">
                        <div>
                            <p class="font-extrabold text-espresso flex items-center gap-1.5">
                                <i class="fa-solid fa-credit-card text-coral"></i>
                                <span>Cổng VNPAY</span>
                            </p>
                            <p class="text-[11px] text-mutedBrown mt-1">Thanh toán qua thẻ ATM nội địa, thẻ ngân hàng hoặc Ví VNPAY.</p>
                        </div>
                    </label>

                    <!-- Chuyển khoản QR ngân hàng (SePay / VietinBank) -->
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-stoneWarm bg-cream/50 hover:border-coral cursor-pointer transition">
                        <input type="radio" name="payment_method" value="SEPAY" class="mt-1 accent-coral">
                        <div>
                            <p class="font-extrabold text-espresso flex items-center gap-1.5">
                                <i class="fa-solid fa-qrcode text-emerald-600"></i>
                                <span>Chuyển khoản QR</span>
                            </p>
                            <p class="text-[11px] text-mutedBrown mt-1">Quét mã VietQR ngân hàng, tự động xác nhận đơn hàng ngay khi nhận tiền.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Cột phải: Mã giảm giá & Tổng tiền thanh toán -->
        <div class="card-warm p-6 bg-white border border-stoneWarm rounded-2xl space-y-5">
            <h2 class="text-base font-extrabold text-espresso border-b border-stoneWarm pb-3">Đơn hàng (<?= count($checkoutItems) ?> món)</h2>

            <!-- Danh sách rút gọn các mặt hàng -->
            <div class="space-y-3 max-h-56 overflow-y-auto pr-1 text-xs">
                <?php foreach ($checkoutItems as $it): ?>
                    <div class="flex justify-between gap-2 pb-2.5 border-b border-stoneWarm/60">
                        <div>
                            <p class="font-bold text-espresso"><?= e($it['item_name'] ?? $it['name'] ?? '') ?></p>
                            <p class="text-[11px] text-mutedBrown font-mono-code">Mã: <?= e((string)$it['item_id']) ?> × <?= (int)$it['quantity'] ?></p>
                        </div>
                        <span class="font-mono-code font-bold text-espresso shrink-0">
                            <?= e(format_price($it['line_total'])) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Ô nhập Mã giảm giá (Kiểm tra điều kiện qua AJAX) -->
            <div class="bg-cream p-3.5 rounded-xl border border-stoneWarm space-y-2">
                <label class="block text-xs font-bold text-espresso">
                    <i class="fa-solid fa-ticket text-coral mr-1"></i> Mã giảm giá
                </label>
                <div class="flex gap-2">
                    <input id="couponInput" type="text" name="coupon_code" value="" placeholder="Nhập mã khuyến mãi"
                           class="flex-1 px-3 py-2 rounded-lg bg-white border border-stoneWarm text-xs font-mono-code uppercase focus:outline-none focus:border-coral">
                    <button type="button" onclick="applyCouponCode()" class="px-3.5 py-2 rounded-lg bg-espresso hover:bg-coral text-white text-xs font-bold transition shrink-0">
                        Áp dụng
                    </button>
                </div>
                <p id="couponFeedback" class="text-[11px] font-semibold text-mutedBrown">
                    Nhập mã khuyến mãi nếu có để được giảm giá đơn hàng.
                </p>
            </div>

            <!-- Chi tiết tổng tiền thanh toán -->
            <div class="space-y-2 text-xs font-mono-code border-t border-stoneWarm pt-3">
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Tạm tính:</span>
                    <span id="chkSubtotal"><?= e(format_price($subtotal)) ?></span>
                </div>
                <div class="flex justify-between text-emerald-700 font-bold">
                    <span class="font-sans">Giảm giá:</span>
                    <span id="chkDiscount">-0 ₫</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-sans text-mutedBrown">Phí giao hàng:</span>
                    <span id="chkShipping">+<?= e(format_price($shippingFee)) ?></span>
                </div>
                <div class="flex justify-between items-center text-base font-extrabold text-coral border-t border-stoneWarm pt-3">
                    <span class="font-sans text-sm text-espresso">TỔNG THANH TOÁN:</span>
                    <span id="chkTotal"><?= e(format_price($totalAmount)) ?></span>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 px-6 bg-coral hover:bg-coral-hover text-white font-extrabold text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2 uppercase tracking-wider">
                <i class="fa-solid fa-shield-check"></i>
                <span>Xác nhận Đặt hàng</span>
            </button>
            <a href="<?= e(url('cart')) ?>" class="block text-center text-xs font-bold text-mutedBrown hover:text-espresso">
                &larr; Quay lại giỏ hàng
            </a>
        </div>
    </form>
</div>

<script>
const CHK_CSRF = <?= json_encode(csrf_token()) ?>;
const CHK_COUPON_URL = <?= json_encode(url('checkout/coupon')) ?>;

function formatVND(n) {
    return Number(n || 0).toLocaleString('vi-VN') + ' ₫';
}

// Kiểm tra mã giảm giá và tính lại tổng tiền thanh toán qua AJAX
async function applyCouponCode() {
    const code = document.getElementById('couponInput').value.trim().toUpperCase();
    const fb = document.getElementById('couponFeedback');

    const fd = new FormData();
    fd.append('_csrf', CHK_CSRF);
    fd.append('coupon_code', code);
    fd.append('ajax', '1');

    try {
        const res = await fetch(CHK_COUPON_URL, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            fb.className = 'text-[11px] font-semibold ' + (data.discount > 0 ? 'text-emerald-700' : 'text-mutedBrown');
            fb.innerHTML = (data.discount > 0 ? '<i class="fa-solid fa-circle-check mr-1"></i> ' : '') + data.message;
        } else {
            fb.className = 'text-[11px] font-semibold text-rose-600';
            fb.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i> ' + data.message;
        }

        document.getElementById('chkSubtotal').textContent = formatVND(data.subtotal);
        document.getElementById('chkDiscount').textContent = '-' + formatVND(data.discount);
        document.getElementById('chkShipping').textContent = '+' + formatVND(data.shipping);
        document.getElementById('chkTotal').textContent = formatVND(data.total);
    } catch (e) {
        fb.className = 'text-[11px] font-semibold text-rose-600';
        fb.textContent = 'Không thể kiểm tra mã giảm giá lúc này. Vui lòng thử lại.';
    }
}
</script>
