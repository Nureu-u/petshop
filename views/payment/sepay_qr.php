<?php
/**
 * Trang chuyển khoản QR (SePay / VietinBank) - views/payment/sepay_qr.php
 * Dữ liệu $info do PaymentController::sepay_create() cấp. Trang tự hỏi payment/sepay_check mỗi 4 giây.
 * Dùng Tailwind (coral, espresso, mutedBrown, stoneWarm, cream) giống các view TV2.
 */
$o = $info['order'];
?>
<div class="max-w-xl mx-auto px-4 py-8">
    <div class="card-warm p-6 sm:p-8 bg-white border border-stoneWarm rounded-2xl shadow-lg space-y-5">
        <div class="text-center">
            <h1 class="text-xl sm:text-2xl font-extrabold text-espresso">Chuyển khoản qua mã QR</h1>
            <p class="text-xs text-mutedBrown mt-1">
                Đơn hàng <strong class="text-coral">#<?= e((string) $o['order_code']) ?></strong> — mở app ngân hàng và quét mã bên dưới.
            </p>
        </div>

        <div id="qrBox" class="flex justify-center">
            <img src="<?= e($info['qr_url']) ?>" alt="Mã QR chuyển khoản" class="w-60 h-60 object-contain rounded-xl border border-stoneWarm bg-white">
        </div>

        <div class="bg-cream p-4 rounded-xl border border-stoneWarm text-xs space-y-2.5">
            <div class="flex justify-between gap-3"><span class="text-mutedBrown">Ngân hàng</span><strong class="text-espresso"><?= e($info['bank']) ?></strong></div>
            <div class="flex justify-between gap-3"><span class="text-mutedBrown">Số tài khoản</span><strong class="font-mono-code text-espresso"><?= e($info['account']) ?></strong></div>
            <div class="flex justify-between gap-3"><span class="text-mutedBrown">Chủ tài khoản</span><strong class="text-espresso"><?= e($info['holder']) ?></strong></div>
            <div class="flex justify-between gap-3"><span class="text-mutedBrown">Số tiền</span><strong class="font-mono-code text-coral text-sm"><?= e(format_price($info['amount'])) ?></strong></div>
            <div class="flex justify-between gap-3 items-center">
                <span class="text-mutedBrown">Nội dung</span>
                <strong class="font-mono-code text-espresso" id="payContent"><?= e($info['content']) ?></strong>
            </div>
        </div>

        <p class="text-[11px] text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">
            Vui lòng giữ <strong>đúng số tiền</strong> và <strong>nguyên nội dung</strong> (bắt đầu bằng <strong>SEVQR</strong>)
            để hệ thống tự nhận diện. Quét mã QR sẽ điền sẵn các thông tin này.
        </p>

        <p class="text-[11px] text-mutedBrown text-center">
            Đơn chỉ được xác nhận khi ngân hàng báo <strong>có tiền thật</strong> vào tài khoản. Nếu app ngân hàng báo
            <strong>không đủ số dư</strong>, giao dịch chưa được thực hiện — vui lòng nạp thêm tiền rồi chuyển lại
            đúng số tiền và nội dung trên.
        </p>

        <div id="payStatus" class="text-center text-xs font-bold text-mutedBrown">
            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang chờ nhận tiền...
            <span class="font-mono-code" id="countdown"></span>
        </div>

        <div class="flex items-center justify-center gap-3 text-xs">
            <button type="button" id="btnCheckNow" class="px-4 py-2 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold border-0 cursor-pointer">
                <i class="fa-solid fa-rotate mr-1"></i> Tôi đã chuyển khoản — kiểm tra ngay
            </button>
            <a href="<?= e($info['orders_url']) ?>" class="px-4 py-2 rounded-xl bg-cream border border-stoneWarm text-espresso font-bold hover:border-coral">Xem đơn hàng của tôi</a>
        </div>
    </div>
</div>

<script>
(function () {
    var CHECK_URL = <?= json_encode($info['check_url']) ?>;
    var ORDERS_URL = <?= json_encode($info['orders_url']) ?>;
    var RESULT_URL = <?= json_encode($info['result_url']) ?>;
    var EXPIRES_AT = <?= (int) $info['expires_at'] ?> * 1000;
    var statusEl = document.getElementById('payStatus');
    var countEl = document.getElementById('countdown');
    var finished = false;

    function show(html, cls) {
        statusEl.className = 'text-center text-sm font-extrabold ' + cls;
        statusEl.innerHTML = html;
    }

    function tickCountdown() {
        if (finished) { return; }
        var left = Math.max(0, Math.floor((EXPIRES_AT - Date.now()) / 1000));
        var m = String(Math.floor(left / 60)).padStart(2, '0');
        var s = String(left % 60).padStart(2, '0');
        countEl.textContent = '(còn ' + m + ':' + s + ')';
    }

    function poll() {
        if (finished) { return; }
        fetch(CHECK_URL, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.status === 'paid') {
                    finished = true;
                    show('<i class="fa-solid fa-circle-check mr-1"></i> Đã nhận tiền! Đơn hàng đã được xác nhận. Đang chuyển trang...', 'text-emerald-600');
                    setTimeout(function () { window.location.href = RESULT_URL; }, 1500);
                } else if (d.status === 'cancelled') {
                    finished = true;
                    show('Đơn hàng đã hết hạn thanh toán hoặc đã bị hủy.', 'text-rose-600');
                    setTimeout(function () { window.location.href = RESULT_URL; }, 2000);
                } else if (d.status === 'short') {
                    show('<i class="fa-solid fa-triangle-exclamation mr-1"></i> ' + d.message, 'text-amber-600');
                } else if (d.status === 'error' && d.message) {
                    countEl.textContent = '— ' + d.message;
                }
            })
            .catch(function () { /* mất mạng / hết phiên: thử lại ở lần sau */ });
    }

    document.getElementById('btnCheckNow').addEventListener('click', poll);
    tickCountdown();
    setInterval(tickCountdown, 1000);
    setInterval(poll, 4000);
    poll();
})();
</script>
