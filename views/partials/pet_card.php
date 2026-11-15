<?php
/** Thẻ thú cưng. Cần biến $p (một dòng từ Pet::search). */
$meta = trim($p['species'] . ($p['breed'] ? ' · ' . $p['breed'] : ''));
$sub = array_filter([$p['gender'] ? status_label($p['gender']) : '', pet_age_label($p['birth_date'] ?? null)]);
?>
<a class="item-card" href="<?= e(url('pets/show', ['id' => $p['id']])) ?>">
    <div class="item-thumb">
        <?= item_image($p['image'], $p['name'], 'paw') ?>
        <?php if (!empty($p['category_name'])): ?><span class="badge"><?= e($p['category_name']) ?></span><?php endif; ?>
    </div>
    <div class="item-body">
        <h3 class="item-title"><?= e($p['name']) ?></h3>
        <span class="item-meta"><?= e($meta) ?></span>
        <?php if ($sub): ?><span class="item-meta"><?= e(implode(' · ', $sub)) ?></span><?php endif; ?>
        <div class="item-foot"><span class="price"><?= e(format_price($p['price'])) ?></span></div>
    </div>
</a>
