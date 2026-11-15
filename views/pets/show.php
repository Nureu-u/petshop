<?php
/**
 * Giao diện Chi tiết thú cưng.
 * Biến nhận từ PetController::show(): $pet (thông tin chi tiết thú cưng), $reviews (danh sách đánh giá).
 */

if (!isset($reviews) && !empty($pet['id'])) {
    $reviews = [];
    try {
        $stRev = Database::pdo()->prepare(
            "SELECT r.*, u.full_name AS user_name,
                    (SELECT ri.image FROM review_images ri WHERE ri.review_id = r.id ORDER BY ri.created_at LIMIT 1) AS image
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.pet_id = :pid AND r.status = 'visible'
             ORDER BY r.created_at DESC"
        );
        $stRev->execute(['pid' => $pet['id']]);
        $reviews = $stRev->fetchAll();
    } catch (Throwable $e) {
        $reviews = [];
    }
}

// Lấy danh sách thú cưng tương tự cùng danh mục
if (!isset($relatedPets) && !empty($pet['category_id'])) {
    try {
        $stRel = Database::pdo()->prepare(
            "SELECT p.*, c.name AS category_name
             FROM pets p JOIN pet_categories c ON c.id = p.category_id
             WHERE p.category_id = :cat AND p.id <> :id AND p.status = 'available' AND c.status = 'active'
             ORDER BY p.created_at DESC LIMIT 4"
        );
        $stRel->execute(['cat' => $pet['category_id'], 'id' => $pet['id']]);
        $relatedPets = $stRel->fetchAll();
    } catch (Throwable $e) {
        $relatedPets = [];
    }
}


// Kiểm tra quyền gửi đánh giá trực tiếp tại trang chi tiết (khách đã nhận hàng thành công đơn chứa thú cưng này)
$canReviewPet = false;
$myPetReview = null;
if (!empty($pet['id']) && Auth::check()) {
    try {
        $uid = (string) Auth::id();
        $stCan = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.user_id = :u AND o.status = 'DELIVERED' AND oi.pet_id = :pid"
        );
        $stCan->execute(['u' => $uid, 'pid' => $pet['id']]);
        $canReviewPet = ((int) $stCan->fetchColumn()) > 0;

        if ($canReviewPet) {
            $stMyRev = Database::pdo()->prepare("SELECT * FROM reviews WHERE user_id = :u AND pet_id = :pid LIMIT 1");
            $stMyRev->execute(['u' => $uid, 'pid' => $pet['id']]);
            $myPetReview = $stMyRev->fetch() ?: null;
        }
    } catch (Throwable $e) {
        $canReviewPet = false;
    }
}

$imgSrc = !empty($pet['image']) && str_starts_with((string)$pet['image'], 'http')
    ? $pet['image']
    : (!empty($pet['image']) ? asset($pet['image']) : '');

// Kiểm tra thú cưng còn khả dụng để đặt mua (status = 'available' và danh mục đang hoạt động)
$canBuy = !empty($pet['id']) && (
    class_exists('Pet') && method_exists('Pet', 'isPurchasable')
        ? Pet::isPurchasable((string)$pet['id'])
        : (($pet['status'] ?? '') === 'available')
);
?>

<div class="max-w-7xl mx-auto px-2 sm:px-4 py-6">
    <!-- Đường dẫn điều hướng (Breadcrumb) -->
    <nav class="text-xs text-stone-500 mb-6 font-medium">
        <a href="<?= e(url('home')) ?>" class="hover:text-coral">Trang chủ</a> 
        <span class="mx-2">/</span> 
        <a href="<?= e(url('pets')) ?>" class="hover:text-coral">Thú cưng</a> 
        <span class="mx-2">/</span> 
        <span class="text-espresso font-semibold"><?= e($pet['name'] ?? 'Chi tiết thú cưng') ?></span>
    </nav>

    <?php if (empty($pet)): ?>
        <!-- Thông báo khi thú cưng không tồn tại hoặc đã ẩn -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-3xl mb-3"></i>
            <h2 class="text-lg font-bold text-espresso">Thú cưng không tồn tại hoặc đã ngừng kinh doanh!</h2>
            <p class="text-xs text-mutedBrown mt-1">Vui lòng quay lại danh sách để lựa chọn các bé thú cưng khác đang sẵn có.</p>
            <a href="<?= e(url('pets')) ?>" class="inline-block mt-4 px-5 py-2.5 bg-coral text-white text-xs font-bold rounded-xl shadow-md hover:bg-coral-hover transition">
                Xem danh sách thú cưng
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            <!-- Hình ảnh thú cưng & Nhãn trạng thái -->
            <div class="card-warm p-4 bg-white border border-stoneWarm rounded-2xl shadow-sm">
                <div class="relative overflow-hidden rounded-xl aspect-square bg-cream">
                    <img src="<?= e($imgSrc) ?>" 
                         alt="<?= e($pet['name']) ?>" 
                         class="w-full h-full object-cover"
                         onerror="this.onerror=null;this.src='data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20120%20120%22%3E%3Crect%20width%3D%22120%22%20height%3D%22120%22%20rx%3D%2220%22%20fill%3D%22%23FFF0E8%22/%3E%3Cg%20fill%3D%22%23FF6B35%22%3E%3Cellipse%20cx%3D%2260%22%20cy%3D%2274%22%20rx%3D%2211%22%20ry%3D%229%22/%3E%3Ccircle%20cx%3D%2245%22%20cy%3D%2260%22%20r%3D%225.5%22/%3E%3Ccircle%20cx%3D%2255%22%20cy%3D%2252%22%20r%3D%225.5%22/%3E%3Ccircle%20cx%3D%2266%22%20cy%3D%2252%22%20r%3D%225.5%22/%3E%3Ccircle%20cx%3D%2276%22%20cy%3D%2260%22%20r%3D%225.5%22/%3E%3C/g%3E%3C/svg%3E';">
                    
                    <?php if (!$canBuy): ?>
                        <span class="absolute top-4 right-4 px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full shadow">Đã có chủ (Đã bán)</span>
                    <?php else: ?>
                        <span class="absolute top-4 right-4 px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full shadow">Đang sẵn sàng về nhà mới</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thông tin chi tiết & Thao tác mua hàng -->
            <div class="space-y-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold tracking-wider uppercase text-coral bg-coral-light px-2.5 py-1 rounded-lg">
                            Mã thú cưng: <span class="font-mono-code"><?= e($pet['id']) ?></span>
                        </span>
                        <?php if (!empty($pet['category_name'])): ?>
                            <span class="text-xs font-semibold text-mutedBrown bg-cream px-2.5 py-1 rounded-lg border border-stoneWarm">
                                Danh mục: <strong class="text-espresso"><?= e($pet['category_name']) ?></strong>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-espresso mt-3">
                        <?= e($pet['name']) ?>
                    </h1>
                    <p class="text-2xl font-extrabold text-coral font-mono-code mt-2">
                        <?= e(format_price($pet['price'] ?? 0)) ?>
                    </p>
                </div>

                <!-- Thông số cá thể thú cưng -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-cream p-4 rounded-2xl border border-stoneWarm text-xs">
                    <div>
                        <span class="text-mutedBrown block">Loài:</span>
                        <strong class="text-espresso text-sm"><?= e($pet['species'] ?? '—') ?></strong>
                    </div>
                    <div>
                        <span class="text-mutedBrown block">Giống:</span>
                        <strong class="text-espresso text-sm"><?= e($pet['breed'] ?: '—') ?></strong>
                    </div>
                    <div>
                        <span class="text-mutedBrown block">Giới tính:</span>
                        <strong class="text-espresso text-sm"><?= !empty($pet['gender']) ? e(status_label((string)$pet['gender'])) : '—' ?></strong>
                    </div>
                    <div>
                        <span class="text-mutedBrown block">Ngày sinh:</span>
                        <strong class="text-espresso text-sm font-mono-code"><?= !empty($pet['birth_date']) ? e(date('d/m/Y', strtotime((string)$pet['birth_date']))) : '—' ?></strong>
                    </div>
                    <div>
                        <span class="text-mutedBrown block">Cân nặng:</span>
                        <strong class="text-espresso text-sm font-mono-code"><?= $pet['weight'] !== null ? e((string)$pet['weight']) . ' kg' : '—' ?></strong>
                    </div>
                    <div>
                        <span class="text-mutedBrown block">Sức khỏe:</span>
                        <strong class="text-emerald-700 text-sm"><?= e($pet['health_status'] ?: 'Khỏe mạnh') ?></strong>
                    </div>
                </div>

                <!-- Mô tả chi tiết -->
                <div>
                    <h3 class="font-bold text-sm text-espresso mb-1">Đặc điểm & Cam kết bảo hành</h3>
                    <p class="text-xs text-mutedBrown leading-relaxed bg-white p-4 rounded-xl border border-stoneWarm">
                        <?= nl2br(e($pet['description'] ?: 'Chưa có mô tả chi tiết cho bé thú cưng này.')) ?>
                    </p>
                </div>

                <!-- Form thêm vào giỏ hàng (Mỗi thú cưng là cá thể duy nhất nên quantity = 1) -->
                <div class="buy-box">
                    <?php if (!$canBuy): ?>
                        <button type="button" disabled class="w-full py-3.5 px-6 bg-stone-300 text-stone-600 font-bold text-xs rounded-xl cursor-not-allowed uppercase tracking-wider">
                            <i class="fa-solid fa-ban mr-2"></i> Bé thú cưng này đã có chủ
                        </button>
                    <?php else: ?>
                        <form action="<?= e(url('cart/add')) ?>" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="item_type" value="pet">
                            <input type="hidden" name="item_id" value="<?= e((string)$pet['id']) ?>">
                            <input type="hidden" name="quantity" value="1">
                            
                            <button type="submit" class="flex-1 py-3.5 px-6 bg-coral hover:bg-coral-hover text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2 uppercase tracking-wider">
                                <i class="fa-solid fa-cart-shopping text-sm"></i>
                                <span>Thêm vào giỏ hàng</span>
                            </button>
                            <button type="submit" name="buy_now" value="1" class="py-3.5 px-6 bg-espresso hover:bg-stone-800 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2 uppercase tracking-wider">
                                <i class="fa-solid fa-bolt text-amber-400"></i>
                                <span>Đặt mua ngay</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Danh sách đánh giá của khách hàng -->
        <div class="mt-10 card-warm p-6 bg-white border border-stoneWarm rounded-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stoneWarm pb-4 mb-5">
                <div>
                    <h2 class="text-base font-extrabold text-espresso">Đánh giá từ khách hàng</h2>
                    <p class="text-xs text-mutedBrown">Nhận xét thực tế từ khách hàng đã mua và nhận thú cưng thành công</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 font-bold text-xs border border-amber-200">
                        <i class="fa-solid fa-star text-amber-400 mr-1"></i> <?= count($reviews ?? []) ?> đánh giá
                    </span>
                    <?php if ($canReviewPet): ?>
                        <button type="button" onclick="document.getElementById('petReviewModal').classList.remove('hidden')"
                                class="px-3.5 py-1.5 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold text-xs shadow-sm transition border-0 cursor-pointer">
                            <i class="fa-solid fa-pen-to-square mr-1"></i> <?= $myPetReview ? 'Sửa đánh giá của bạn' : 'Viết đánh giá' ?>
                        </button>
                    <?php elseif (Auth::check()): ?>
                        <a href="<?= e(url('orders')) ?>" class="px-3 py-1.5 rounded-xl bg-cream hover:bg-stoneWarm text-espresso font-bold text-xs border border-stoneWarm transition" title="Bạn có thể gửi đánh giá sau khi đơn hàng chuyển sang trạng thái Đã giao">
                            <i class="fa-regular fa-star mr-1 text-coral"></i> Đánh giá trong Đơn hàng
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($reviews)): ?>
                <p class="text-xs text-mutedBrown">Chưa có đánh giá nào cho bé thú cưng này.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="p-4 rounded-xl bg-cream/60 border border-stoneWarm flex flex-col sm:flex-row gap-4 justify-between">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-espresso"><?= e($rev['user_name']) ?></span>
                                    <span class="text-amber-400 text-xs">
                                        <?= str_repeat('<i class="fa-solid fa-star"></i>', (int)$rev['rating']) ?>
                                    </span>
                                    <span class="text-[11px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-semibold">Đã mua hàng</span>
                                </div>
                                <p class="text-xs text-espresso leading-relaxed"><?= e($rev['comment'] ?? '') ?></p>
                                <p class="text-[10px] text-mutedBrown font-mono-code"><?= e($rev['created_at'] ?? '') ?></p>
                            </div>
                            <?php if (!empty($rev['image'])): ?>
                                <img src="<?= e(str_starts_with((string)$rev['image'], 'http') ? $rev['image'] : asset($rev['image'])) ?>" alt="Review" class="w-16 h-16 rounded-xl object-cover border border-stoneWarm shrink-0">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Gợi ý thú cưng tương tự cùng danh mục -->
        <?php if (!empty($relatedPets)): ?>
        <section class="section" style="margin-top:36px;">
            <div class="section-head">
                <h2>Thú cưng tương tự cùng danh mục</h2>
                <a href="<?= e(url('pets', ['category_id' => $pet['category_id']])) ?>">Xem thêm &rarr;</a>
            </div>
            <div class="grid grid-4">
                <?php foreach ($relatedPets as $p): require BASE_PATH . '/views/partials/pet_card.php'; endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($canReviewPet && !empty($pet['id'])): ?>
<!-- Hộp thoại gửi / cập nhật đánh giá trực tiếp tại trang chi tiết thú cưng -->
<div id="petReviewModal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 border border-stoneWarm shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-stoneWarm pb-3">
            <h3 class="text-base font-extrabold text-espresso m-0"><?= $myPetReview ? 'Cập nhật đánh giá thú cưng' : 'Viết đánh giá thú cưng' ?></h3>
            <button type="button" onclick="document.getElementById('petReviewModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-cream text-mutedBrown border-0 cursor-pointer">✕</button>
        </div>
        <form action="<?= e(url('review/store')) ?>" method="POST" enctype="multipart/form-data" class="space-y-3.5 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="item_type" value="pet">
            <input type="hidden" name="item_id" value="<?= e((string)$pet['id']) ?>">

            <p class="text-mutedBrown m-0">Thú cưng: <strong class="text-coral"><?= e($pet['name']) ?></strong></p>
            <div>
                <label class="block font-bold text-espresso mb-1">Điểm xếp hạng (1 - 5 sao)</label>
                <?php $curRating = (int)($myPetReview['rating'] ?? 5); ?>
                <select name="rating" class="w-full px-3 py-2 rounded-xl bg-cream border border-stoneWarm font-bold text-amber-600">
                    <option value="5"<?= $curRating === 5 ? ' selected' : '' ?>>★★★★★ - Tuyệt vời (5 sao)</option>
                    <option value="4"<?= $curRating === 4 ? ' selected' : '' ?>>★★★★☆ - Hài lòng (4 sao)</option>
                    <option value="3"<?= $curRating === 3 ? ' selected' : '' ?>>★★★☆☆ - Bình thường (3 sao)</option>
                    <option value="2"<?= $curRating === 2 ? ' selected' : '' ?>>★★☆☆☆ - Kém (2 sao)</option>
                    <option value="1"<?= $curRating === 1 ? ' selected' : '' ?>>★☆☆☆☆ - Rất tệ (1 sao)</option>
                </select>
            </div>
            <div>
                <label class="block font-bold text-espresso mb-1">Nhận xét chi tiết <span class="text-rose-500">*</span></label>
                <textarea name="comment" rows="3" maxlength="1000" required placeholder="Chia sẻ trải nghiệm của bạn về bé thú cưng..."
                          class="w-full px-3 py-2 rounded-xl bg-cream border border-stoneWarm"><?= e((string)($myPetReview['comment'] ?? '')) ?></textarea>
            </div>
            <div>
                <label class="block font-bold text-espresso mb-1">Ảnh thực tế đính kèm (JPG, PNG, WEBP tối đa 2MB)</label>
                <input type="file" name="review_image" accept="image/jpeg,image/png,image/webp" class="w-full px-3 py-1.5 rounded-xl bg-cream border border-stoneWarm text-xs">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('petReviewModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-stone-100 font-bold border-0 cursor-pointer">Đóng</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-coral hover:bg-coral-hover text-white font-bold border-0 cursor-pointer">Gửi đánh giá</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
