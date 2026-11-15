<?php
/**
 * Giao diện Lịch sử mua hàng và theo dõi trạng thái đơn hàng.
 * Biến nhận từ OrderController::history(): $orders (danh sách đơn hàng của khách).
 * Hỗ trợ hủy đơn hàng (khi đơn chưa giao/chưa thanh toán online) và gửi đánh giá thực tế vào bảng reviews & review_images (khi đơn đã hoàn thành).
 */
$orders = $orders ?? [];

$statusMeta = [
    'pending'   => ['label' => 'Chờ thanh toán / xác nhận', 'badge' => 'bg-amber-100 text-amber-800'],
    'confirmed' => ['label' => 'Đã xác nhận',               'badge' => 'bg-sky-100 text-sky-800'],
    'shipping'  => ['label' => 'Đang giao hàng',            'badge' => 'bg-orange-100 text-orange-800'],
    'completed' => ['label' => 'Hoàn thành (Đã giao)',      'badge' => 'bg-emerald-100 text-emerald-800'],
    'cancelled' => ['label' => 'Đã hủy',                    'badge' => 'bg-rose-100 text-rose-800']
];
?>

<div class="max-w-6xl mx-auto px-2 sm:px-4 py-6 space-y-6">
    <!-- Đường dẫn điều hướng (Breadcrumb) -->
    <nav class="text-xs text-stone-500 font-medium">
        <a href="<?= e(url('home')) ?>" class="hover:text-coral">Trang chủ</a>
        <span class="mx-2">/</span>
        <a href="<?= e(url('auth/profile')) ?>" class="hover:text-coral">Tài khoản</a>
        <span class="mx-2">/</span>
        <span class="text-espresso font-semibold">Lịch sử đơn hàng</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-espresso">Lịch sử mua hàng & Theo dõi đơn hàng</h1>
            <p class="text-xs text-mutedBrown mt-0.5">Theo dõi tiến trình xử lý đơn hàng, hủy đơn chờ duyệt hoặc gửi đánh giá sản phẩm</p>
        </div>
        <span class="px-3 py-1.5 rounded-full bg-coral-light text-coral font-bold text-xs font-mono-code">
            <?= count($orders) ?> đơn hàng
        </span>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card-warm p-10 bg-white border border-stoneWarm rounded-2xl text-center space-y-3">
            <div class="w-16 h-16 rounded-full bg-coral-light text-coral flex items-center justify-center text-2xl mx-auto">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h2 class="text-lg font-extrabold text-espresso">Bạn chưa có đơn hàng nào</h2>
            <p class="text-xs text-mutedBrown">Hãy đặt mua thú cưng hoặc phụ kiện chăm sóc để theo dõi đơn hàng tại đây nhé.</p>
            <div class="pt-2">
                <a href="<?= e(url('pets')) ?>" class="inline-block px-5 py-2.5 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold text-xs shadow transition">
                    Mua sắm ngay
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $ord): 
                $st = $statusMeta[$ord['status'] ?? 'pending'] ?? $statusMeta['pending'];
                $canCancel = !empty($ord['can_cancel']);
                $isCompleted = ($ord['status'] ?? '') === 'completed';
            ?>
                <div class="card-warm p-5 bg-white border border-stoneWarm rounded-2xl space-y-4" id="order-card-<?= e((string)$ord['id']) ?>">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-stoneWarm pb-3">
                        <div class="flex items-center gap-3">
                            <span class="font-mono-code font-extrabold text-sm text-coral">#<?= e((string)$ord['order_code']) ?></span>
                            <span class="text-xs text-mutedBrown font-mono-code">• <?= e((string)$ord['created_at']) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= ($ord['payment_status'] ?? '') === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-700' ?>">
                                <?= ($ord['payment_status'] ?? '') === 'paid' ? 'Đã thanh toán (' . e((string)$ord['payment_method']) . ')' : 'Chưa thu tiền (' . e((string)$ord['payment_method']) . ')' ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $st['badge'] ?>">
                                <?= $st['label'] ?>
                            </span>
                        </div>
                    </div>

                    <!-- Danh sách mặt hàng trong đơn -->
                    <div class="space-y-2 text-xs">
                        <?php foreach ($ord['items'] ?? [] as $item): 
                            $itemIdStr = (string)($item['id'] ?? '');
                            $itemTypeStr = str_starts_with($itemIdStr, 'PET') ? 'pet' : 'product';
                            $detailHref = $itemTypeStr === 'pet'
                                ? url('pets/show', ['id' => $itemIdStr])
                                : url('products/show', ['id' => $itemIdStr]);
                        ?>
                            <div class="flex flex-wrap justify-between items-center gap-2 py-1.5 border-b border-stoneWarm/40 last:border-0">
                                <div>
                                    <a href="<?= e($detailHref) ?>" class="font-bold text-espresso hover:text-coral">
                                        <?= e((string)$item['name']) ?>
                                    </a>
                                    <span class="text-mutedBrown font-mono-code ml-2">(<?= e($itemIdStr) ?> × <?= (int)$item['qty'] ?>)</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-mono-code font-semibold text-espresso"><?= e(format_price((int)$item['price'] * (int)$item['qty'])) ?></span>
                                    <?php if ($isCompleted && $itemIdStr !== ''): ?>
                                        <button type="button"
                                                onclick="openReviewModal('<?= e($itemTypeStr) ?>', '<?= e($itemIdStr) ?>', '<?= e((string)$item['name']) ?>')"
                                                class="px-2.5 py-1 rounded-lg bg-coral-light hover:bg-coral text-coral hover:text-white font-bold text-[11px] transition border-0 cursor-pointer">
                                            <i class="fa-solid fa-star mr-1"></i> Đánh giá
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tổng tiền & Thao tác đơn hàng -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-stoneWarm text-xs">
                        <div class="text-mutedBrown">
                            Giao đến: <strong class="text-espresso"><?= e((string)$ord['shipping_address']) ?></strong>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-mutedBrown">Tổng thanh toán: <strong class="font-mono-code text-base text-coral"><?= e(format_price($ord['total_amount'])) ?></strong></span>

                            <?php if (!empty($ord['pay_url'])): ?>
                                <!-- Thanh toán / Thanh toán lại cho đơn online đang chờ thanh toán -->
                                <a href="<?= e((string)$ord['pay_url']) ?>" class="px-3.5 py-2 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold shadow-sm transition">
                                    <i class="fa-solid fa-credit-card mr-1"></i> Thanh toán ngay
                                </a>
                            <?php endif; ?>

                            <?php if ($canCancel): ?>
                                <!-- Hủy đơn hàng và hoàn lại tồn kho -->
                                <form action="<?= e(url('order/cancel')) ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?= e((string)$ord['order_code']) ?>? Tồn kho và mã giảm giá (nếu có) sẽ được hoàn lại.');" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= e((string)$ord['id']) ?>">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white font-bold transition border-0 cursor-pointer">
                                        <i class="fa-solid fa-ban mr-1"></i> Hủy đơn hàng
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Hộp thoại gửi đánh giá thú cưng / sản phẩm lưu trực tiếp vào bảng reviews & review_images -->
<div id="reviewModal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 border border-stoneWarm shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-stoneWarm pb-3">
            <h3 class="text-base font-extrabold text-espresso m-0">Đánh giá Thú cưng / Sản phẩm</h3>
            <button type="button" onclick="closeReviewModal()" class="w-8 h-8 rounded-lg bg-cream text-mutedBrown border-0 cursor-pointer">✕</button>
        </div>
        <form action="<?= e(url('review/store')) ?>" method="POST" enctype="multipart/form-data" class="space-y-3.5 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="item_type" id="reviewItemType" value="product">
            <input type="hidden" name="item_id" id="reviewItemId" value="">

            <p class="text-mutedBrown m-0">Mặt hàng: <strong id="reviewTargetName" class="text-coral"></strong></p>
            <div>
                <label class="block font-bold text-espresso mb-1">Điểm xếp hạng (1 - 5 sao)</label>
                <select name="rating" class="w-full px-3 py-2 rounded-xl bg-cream border border-stoneWarm font-bold text-amber-600">
                    <option value="5">★★★★★ - Tuyệt vời (5 sao)</option>
                    <option value="4">★★★★☆ - Hài lòng (4 sao)</option>
                    <option value="3">★★★☆☆ - Bình thường (3 sao)</option>
                    <option value="2">★★☆☆☆ - Kém (2 sao)</option>
                    <option value="1">★☆☆☆☆ - Rất tệ (1 sao)</option>
                </select>
            </div>
            <div>
                <label class="block font-bold text-espresso mb-1">Nhận xét chi tiết <span class="text-rose-500">*</span></label>
                <textarea name="comment" rows="3" maxlength="1000" required placeholder="Chia sẻ trải nghiệm của bạn về sức khỏe thú cưng hoặc chất lượng sản phẩm..."
                          class="w-full px-3 py-2 rounded-xl bg-cream border border-stoneWarm"></textarea>
            </div>
            <div>
                <label class="block font-bold text-espresso mb-1">Ảnh thực tế đính kèm (JPG, PNG, WEBP tối đa 2MB)</label>
                <input type="file" name="review_image" accept="image/jpeg,image/png,image/webp" class="w-full px-3 py-1.5 rounded-xl bg-cream border border-stoneWarm text-xs">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeReviewModal()" class="px-4 py-2 rounded-xl bg-stone-100 font-bold border-0 cursor-pointer">Đóng</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold border-0 cursor-pointer">Gửi đánh giá</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(itemType, itemId, itemName) {
    document.getElementById('reviewItemType').value = itemType;
    document.getElementById('reviewItemId').value = itemId;
    document.getElementById('reviewTargetName').textContent = itemName;
    document.getElementById('reviewModal').classList.remove('hidden');
}
function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}
</script>
