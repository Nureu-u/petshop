<?php
/** Phân trang. Cần biến $result = ['page' => n, 'pages' => m]. Giữ nguyên các tham số lọc đang có. */
$cur = (int) ($result['page'] ?? 1);
$last = (int) ($result['pages'] ?? 1);
if ($last > 1):
    $from = max(1, $cur - 2);
    $to = min($last, $cur + 2);
?>
    <nav class="pagination" aria-label="Phân trang">
        <?php if ($cur > 1): ?><a href="<?= e(current_url_with(['page' => $cur - 1])) ?>" aria-label="Trang trước">&laquo;</a><?php endif; ?>
        <?php if ($from > 1): ?>
            <a href="<?= e(current_url_with(['page' => 1])) ?>">1</a>
            <?php if ($from > 2): ?><span class="gap">…</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $from; $i <= $to; $i++): ?>
            <?php if ($i === $cur): ?>
                <span class="current" aria-current="page"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e(current_url_with(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($to < $last): ?>
            <?php if ($to < $last - 1): ?><span class="gap">…</span><?php endif; ?>
            <a href="<?= e(current_url_with(['page' => $last])) ?>"><?= $last ?></a>
        <?php endif; ?>
        <?php if ($cur < $last): ?><a href="<?= e(current_url_with(['page' => $cur + 1])) ?>" aria-label="Trang sau">&raquo;</a><?php endif; ?>
    </nav>
<?php endif; ?>
