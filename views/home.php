<?php
/**
 * Giao diện Trang chủ khách hàng.
 * Biến nhận từ HomeController::index(): $pets, $products, $petCategories, $productCategories, $latestPosts.
 */
?>
<section class="hero">
    <div>
        <span class="badge" style="margin-bottom:10px; display:inline-block;">🐾 Cửa hàng Thú cưng & Phụ kiện Chính hãng</span>
        <h1>Tìm người bạn bốn chân hoàn hảo cho gia đình bạn</h1>
        <p>Thú cưng thuần chủng, khỏe mạnh, được kiểm tra thú y và tiêm phòng đầy đủ cùng trọn bộ thức ăn dinh dưỡng, đồ chơi và phụ kiện chăm sóc.</p>
        <form class="hero-search" method="get" action="<?= e(config('base_url')) ?>/index.php">
            <input type="hidden" name="r" value="pets">
            <input type="search" name="q" placeholder="Tìm theo tên, loài, giống thú cưng..." aria-label="Tìm thú cưng">
            <button type="submit">Tìm kiếm</button>
        </form>
        <div class="hero-cta">
            <a class="btn" href="<?= e(url('pets')) ?>"><?= ui_icon('paw', 18) ?> Khám phá thú cưng</a>
            <a class="btn outline" href="<?= e(url('products')) ?>"><?= ui_icon('box', 18) ?> Mua sắm phụ kiện</a>
        </div>
    </div>
    <div class="hero-art"><?= ui_icon('paw', 260, '0.9') ?></div>
</section>

<?php if (!empty($petCategories) || !empty($productCategories)): ?>
<section class="section">
    <div class="section-head">
        <h2>Danh mục nổi bật</h2>
    </div>
    <div class="grid grid-4">
        <?php foreach ($petCategories as $c): ?>
            <a class="cat-tile" href="<?= e(url('pets', ['category_id' => $c['id']])) ?>">
                <span class="ico"><?= ui_icon('paw', 22) ?></span>
                <div>
                    <div><?= e($c['name']) ?></div>
                    <small class="muted" style="font-weight:400; font-size:.78rem;">Thú cưng</small>
                </div>
            </a>
        <?php endforeach; ?>
        <?php foreach ($productCategories as $c): ?>
            <a class="cat-tile" href="<?= e(url('products', ['category_id' => $c['id']])) ?>">
                <span class="ico"><?= ui_icon('box', 22) ?></span>
                <div>
                    <div><?= e($c['name']) ?></div>
                    <small class="muted" style="font-weight:400; font-size:.78rem;">Sản phẩm</small>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="section-head">
        <h2>Thú cưng đang tìm chủ mới</h2>
        <a href="<?= e(url('pets')) ?>">Xem tất cả &rarr;</a>
    </div>
    <?php if (!empty($pets)): ?>
        <div class="grid grid-4">
            <?php foreach ($pets as $p): require BASE_PATH . '/views/partials/pet_card.php'; endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted">Hiện chưa có thú cưng nào trong danh sách.</p>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <h2>Thức ăn & Phụ kiện bán chạy</h2>
        <a href="<?= e(url('products')) ?>">Xem tất cả &rarr;</a>
    </div>
    <?php if (!empty($products)): ?>
        <div class="grid grid-4">
            <?php foreach ($products as $p): require BASE_PATH . '/views/partials/product_card.php'; endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted">Hiện chưa có sản phẩm nào trong danh sách.</p>
    <?php endif; ?>
</section>

<?php if (!empty($latestPosts)): ?>
<section class="section">
    <div class="section-head">
        <h2>Cẩm nang chăm sóc thú cưng</h2>
        <a href="<?= e(url('posts')) ?>">Xem tất cả bài viết &rarr;</a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($latestPosts as $post): ?>
            <a class="item-card" href="<?= e(url('posts/show', ['id' => $post['id']])) ?>">
                <div class="item-thumb">
                    <?= item_image($post['image'] ?? null, $post['title'], 'paw') ?>
                    <span class="badge"><?= e($post['category_name']) ?></span>
                </div>
                <div class="item-body">
                    <h3 class="item-title"><?= e($post['title']) ?></h3>
                    <?php if (!empty($post['excerpt'])): ?>
                        <p class="item-meta" style="margin:4px 0 8px;"><?= e($post['excerpt']) ?></p>
                    <?php endif; ?>
                    <div class="item-foot">
                        <span class="item-meta"><?= !empty($post['published_at']) ? e(date('d/m/Y', strtotime((string)$post['published_at']))) : '' ?></span>
                        <span style="color:var(--c-primary-dark); font-weight:600; font-size:.88rem;">Đọc tiếp &rarr;</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="grid grid-3">
        <div class="card feature">
            <div class="ico"><?= ui_icon('shield', 26) ?></div>
            <h3>Kiểm định sức khỏe rõ ràng</h3>
            <p class="muted">Thông tin tiêm phòng, tẩy giun, cân nặng và độ tuổi của từng bé được minh bạch tại trang chi tiết.</p>
        </div>
        <div class="card feature">
            <div class="ico"><?= ui_icon('truck', 26) ?></div>
            <h3>Thanh toán & Giao nhận an toàn</h3>
            <p class="muted">Hỗ trợ thanh toán chuyển khoản QR (SePay) hoặc kiểm tra khi nhận hàng (COD) kèm hoàn kho tự động.</p>
        </div>
        <div class="card feature">
            <div class="ico"><?= ui_icon('chat', 26) ?></div>
            <h3>Đồng hành chăm sóc 24/7</h3>
            <p class="muted">Tư vấn khẩu phần dinh dưỡng, huấn luyện và y tế thú y xuyên suốt quá trình nuôi dưỡng.</p>
        </div>
    </div>
</section>
