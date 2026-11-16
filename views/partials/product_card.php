<?php
/** Thẻ sản phẩm. Cần biến $p (một dòng từ Product::search). */
$stock = (int) $p['stock'];
?>
<a class="item-card" href="<?= e(url('products/show', ['id' => $p['id']])) ?>">
    <div class="item-thumb">
        <?= item_image($p['image'], $p['name'], 'box') ?>
        <?php if ($stock <= 0): ?>
            <span class="badge red">Hết hàng</span>
        <?php elseif (!empty($p['category_name'])): ?>
            <span class="badge"><?= e($p['category_name']) ?></span>
        <?php endif; ?>
    </div>
    <div class="item-body">
        <h3 class="item-title"><?= e($p['name']) ?></h3>
        <?php if (!empty($p['brand'])): ?><span class="item-meta"><?= e($p['brand']) ?></span><?php endif; ?>
        <div class="item-foot">
            <span class="price"><?= e(format_price($p['price'])) ?></span>
            <?php if ($stock > 0 && $stock <= 5): ?><span class="item-meta">Còn <?= $stock ?></span><?php endif; ?>
        </div>
    </div>
</a>
