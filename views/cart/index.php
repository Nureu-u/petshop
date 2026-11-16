<?php
/**
 * Giao diện Quản lý giỏ hàng.
 * Biến nhận từ CartController::index(): $cartItems (danh sách mặt hàng trong giỏ).
 * Hỗ trợ cập nhật số lượng và xóa mặt hàng qua AJAX.
 */
$cartItems = $cartItems ?? [];
$subtotal = 0;
foreach ($cartItems as $it) {
    $subtotal += (int)($it['unit_price'] ?? 0) * (int)($it['quantity'] ?? 1);
}
?>

<div class="max-w-7xl mx-auto px-2 sm:px-4 py-6">
    <!-- Đường dẫn điều hướng (Breadcrumb) -->
    <nav class="text-xs text-stone-500 mb-6 font-medium">
        <a href="<?= e(url('home')) ?>" class="hover:text-coral">Trang chủ</a>
        <span class="mx-2">/</span>
        <span class="text-espresso font-semibold">Giỏ hàng của bạn</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-espresso">Giỏ hàng của bạn</h1>
            <p class="text-xs text-mutedBrown mt-0.5">Kiểm tra danh sách thú cưng và phụ kiện trước khi tiến hành đặt hàng</p>
        </div>
        <span id="cartItemCountBadge" class="px-3 py-1.5 rounded-full bg-coral-light text-coral font-bold text-xs font-mono-code">
            <?= count($cartItems) ?> mặt hàng
        </span>
    </div>

    <!-- Khung hiển thị cảnh báo khi số lượng vượt quá tồn kho -->
    <div id="cartAlertBox" class="hidden mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>

    <?php if (empty($cartItems)): ?>
        <div class="card-warm p-10 bg-white border border-stoneWarm rounded-2xl text-center space-y-3">
            <div class="w-16 h-16 rounded-full bg-coral-light text-coral flex items-center justify-center text-2xl mx-auto">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <h2 class="text-lg font-extrabold text-espresso">Giỏ hàng của bạn đang trống</h2>
            <p class="text-xs text-mutedBrown">Hãy khám phá các bé thú cưng đáng yêu và phụ kiện chăm sóc tại cửa hàng nhé.</p>
            <div class="flex justify-center gap-3 pt-2">
                <a href="<?= e(url('pets')) ?>" class="px-5 py-2.5 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold text-xs shadow transition">
                    Xem thú cưng
                </a>
                <a href="<?= e(url('products')) ?>" class="px-5 py-2.5 rounded-xl bg-cream hover:bg-stoneWarm text-espresso font-bold text-xs border border-stoneWarm transition">
                    Xem sản phẩm
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Bảng danh sách mặt hàng trong giỏ -->
            <div class="lg:col-span-2 card-warm bg-white border border-stoneWarm rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[640px]">
                        <thead>
                            <tr class="bg-cream border-b border-stoneWarm text-[11px] font-bold uppercase text-mutedBrown">
                                <th class="py-3.5 px-4">Thú cưng / Sản phẩm</th>
                                <th class="py-3.5 px-4 text-right">Đơn giá</th>
                                <th class="py-3.5 px-4 text-center">Số lượng</th>
                                <th class="py-3.5 px-4 text-right">Thành tiền</th>
                                <th class="py-3.5 px-4 text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody" class="divide-y divide-stoneWarm/60 text-xs">
                            <?php foreach ($cartItems as $item): 
                                $lineTotal = (int)$item['unit_price'] * (int)$item['quantity'];
                                $detailUrl = ($item['item_type'] ?? 'product') === 'pet'
                                    ? url('pets/show', ['id' => $item['item_id']])
                                    : url('products/show', ['id' => $item['item_id']]);
                            ?>
                                <tr class="cart-row hover:bg-cream/40 transition"
                                    data-id="<?= e((string)$item['id']) ?>"
                                    data-price="<?= (int)$item['unit_price'] ?>"
                                    data-max="<?= (int)($item['max_stock'] ?? 99) ?>">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= e($item['image']) ?>" alt="" class="w-14 h-14 rounded-xl object-cover border border-stoneWarm shrink-0">
                                            <?php else: ?>
                                                <div class="w-14 h-14 rounded-xl bg-coral-light text-coral flex items-center justify-center border border-stoneWarm shrink-0">
                                                    <i class="fa-solid <?= ($item['item_type'] ?? '') === 'pet' ? 'fa-paw' : 'fa-box' ?> text-lg"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded <?= ($item['item_type'] ?? '') === 'pet' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' ?>">
                                                    <?= ($item['item_type'] ?? '') === 'pet' ? 'Thú cưng' : 'Sản phẩm' ?> • <?= e((string)$item['item_id']) ?>
                                                </span>
                                                <a href="<?= e($detailUrl) ?>" class="block font-bold text-espresso hover:text-coral mt-1">
                                                    <?= e($item['name']) ?>
                                                </a>
                                                <p class="text-[11px] text-mutedBrown"><?= e($item['subtitle'] ?? '') ?></p>
                                                <?php if (!empty($item['warning'])): ?>
                                                    <p class="text-[11px] text-rose-600 font-bold mt-1">
                                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i><?= e($item['warning']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono-code font-semibold text-espresso">
                                        <?= e(format_price($item['unit_price'])) ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <?php if (($item['item_type'] ?? '') === 'pet'): ?>
                                            <span class="px-3 py-1 rounded-lg bg-cream border border-stoneWarm font-mono-code font-bold text-espresso inline-block" title="Mỗi thú cưng là 1 cá thể duy nhất">
                                                1 (Cá thể)
                                            </span>
                                            <input type="hidden" class="row-qty-input" value="1">
                                        <?php else: ?>
                                            <div class="inline-flex items-center border border-stoneWarm rounded-xl bg-white overflow-hidden">
                                                <button type="button" onclick="changeCartQty(this, -1)" class="w-7 h-7 bg-cream hover:bg-stoneWarm font-bold">-</button>
                                                <input type="number" class="row-qty-input w-11 h-7 text-center font-mono-code font-bold focus:outline-none"
                                                       value="<?= (int)$item['quantity'] ?>" min="1" max="<?= (int)($item['max_stock'] ?? 99) ?>"
                                                       onchange="changeCartQty(this, 0)">
                                                <button type="button" onclick="changeCartQty(this, 1)" class="w-7 h-7 bg-cream hover:bg-stoneWarm font-bold">+</button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono-code font-extrabold text-coral row-line-total">
                                        <?= e(format_price($lineTotal)) ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <button type="button" onclick="removeCartRow(this)" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition" title="Xóa khỏi giỏ">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Khung Tóm tắt đơn hàng -->
            <div class="card-warm p-5 bg-white border border-stoneWarm rounded-2xl space-y-4">
                <h2 class="text-base font-extrabold text-espresso border-b border-stoneWarm pb-3">Tóm tắt đơn hàng</h2>
                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-mutedBrown">Tạm tính:</span>
                        <span id="cartSubtotalText" class="font-mono-code font-bold text-espresso"><?= e(format_price($subtotal)) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-mutedBrown">Phí vận chuyển dự kiến:</span>
                        <span class="font-mono-code text-espresso">Tính ở bước Thanh toán</span>
                    </div>
                    <div class="border-t border-stoneWarm pt-3 flex justify-between items-center">
                        <span class="font-bold text-sm text-espresso">Tổng cộng tạm tính:</span>
                        <span id="cartTotalText" class="font-mono-code text-lg font-extrabold text-coral"><?= e(format_price($subtotal)) ?></span>
                    </div>
                </div>

                <a id="proceedCheckoutBtn" href="<?= e(url('checkout')) ?>"
                   class="w-full py-3.5 px-5 bg-coral hover:bg-coral-hover text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2 uppercase tracking-wider">
                    <span>Tiến hành đặt hàng</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <a href="<?= e(url('pets')) ?>" class="block text-center text-xs font-bold text-mutedBrown hover:text-espresso">
                    &larr; Tiếp tục chọn thú cưng & phụ kiện
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const CART_CSRF = <?= json_encode(csrf_token()) ?>;
const CART_UPDATE_URL = <?= json_encode(url('cart/update')) ?>;
const CART_REMOVE_URL = <?= json_encode(url('cart/remove')) ?>;

function formatCurrencyVND(num) {
    return Number(num || 0).toLocaleString('vi-VN') + ' ₫';
}

function showCartAlert(msg) {
    const box = document.getElementById('cartAlertBox');
    box.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1.5"></i> ${msg}`;
    box.classList.remove('hidden');
    setTimeout(() => box.classList.add('hidden'), 4500);
}

// Cập nhật số lượng sản phẩm qua AJAX
async function changeCartQty(btnOrInput, delta) {
    const row = btnOrInput.closest('.cart-row');
    const input = row.querySelector('.row-qty-input');
    const maxStock = parseInt(row.dataset.max || 99, 10);
    const itemId = row.dataset.id;
    let qty = parseInt(input.value || 1, 10) + delta;

    if (qty < 1) qty = 1;
    if (qty > maxStock) {
        qty = maxStock;
        showCartAlert('Số lượng sản phẩm vượt quá số lượng tồn kho hiện có (' + maxStock + ')!');
    }
    input.value = qty;

    const fd = new FormData();
    fd.append('_csrf', CART_CSRF);
    fd.append('item_id', itemId);
    fd.append('quantity', String(qty));
    fd.append('ajax', '1');

    try {
        const res = await fetch(CART_UPDATE_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok && data.error) {
            showCartAlert(data.error);
        }
        recalculateCart(data.subtotal, data.count);
    } catch (e) {
        recalculateCart();
    }
}

// Xóa mặt hàng khỏi giỏ qua AJAX
async function removeCartRow(btn) {
    const row = btn.closest('.cart-row');
    const itemId = row.dataset.id;

    const fd = new FormData();
    fd.append('_csrf', CART_CSRF);
    fd.append('item_id', itemId);
    fd.append('ajax', '1');

    row.remove();
    try {
        const res = await fetch(CART_REMOVE_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.count === 0) {
            window.location.reload();
            return;
        }
        recalculateCart(data.subtotal, data.count);
    } catch (e) {
        recalculateCart();
    }
}

function recalculateCart(serverSubtotal = null, serverCount = null) {
    const rows = document.querySelectorAll('.cart-row');
    let subtotal = 0;
    rows.forEach(r => {
        const price = parseFloat(r.dataset.price || 0);
        const qty = parseInt(r.querySelector('.row-qty-input').value || 1, 10);
        const line = price * qty;
        subtotal += line;
        r.querySelector('.row-line-total').textContent = formatCurrencyVND(line);
    });
    const finalSub = serverSubtotal !== null ? serverSubtotal : subtotal;
    const finalCnt = serverCount !== null ? serverCount : rows.length;
    document.getElementById('cartSubtotalText').textContent = formatCurrencyVND(finalSub);
    document.getElementById('cartTotalText').textContent = formatCurrencyVND(finalSub);
    document.getElementById('cartItemCountBadge').textContent = finalCnt + ' mặt hàng';
}
</script>
