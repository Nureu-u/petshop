<?php
/**
 * Các bộ lọc đang áp dụng, mỗi cái có nút bỏ lọc.
 * Cần $chips = [ ['label' => 'Chó', 'remove' => ['category_id' => '']], ... ]
 */
if (!empty($chips)): ?>
    <div class="pills">
        <?php foreach ($chips as $chip): ?>
            <a class="pill chip" href="<?= e(current_url_with($chip['remove'] + ['page' => 1])) ?>" title="Bỏ lọc này">
                <?= e($chip['label']) ?> &times;
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
