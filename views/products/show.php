<p><a href="<?= e(url('products')) ?>">← Danh sách sản phẩm</a></p>
<div class="detail">
    <div>
        <?php if ($product['image']): ?>
            <img class="big" src="<?= e(asset($product['image'])) ?>" alt="<?= e($product['name']) ?>">
        <?php else: ?>
            <div class="noimg big">Chưa có ảnh</div>
        <?php endif; ?>
    </div>
    <div>
        <h1><?= e($product['name']) ?></h1>
        <p class="price"><?= e(format_price($product['price'])) ?></p>
        <p>
            <?php if ((int) $product['stock'] > 0): ?>
                <span class="badge available">Còn <?= (int) $product['stock'] ?> sản phẩm</span>
            <?php else: ?>
                <span class="badge hidden">Hết hàng</span>
            <?php endif; ?>
        </p>
        <table class="info">
            <tr><th>Danh mục</th><td><?= e($product['category_name']) ?></td></tr>
            <tr><th>Thương hiệu</th><td><?= e($product['brand'] ?? '—') ?></td></tr>
        </table>
        <?php if ($product['description']): ?>
            <h3>Mô tả</h3>
            <p><?= nl2br(e($product['description'])) ?></p>
        <?php endif; ?>
        <?php /* TV4: đặt nút "Thêm vào giỏ" ở đây, chỉ khi Product::isPurchasable($product['id'], $qty) */ ?>
    </div>
</div>
