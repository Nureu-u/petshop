<?php if (($result['pages'] ?? 1) > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <?php if ($i === $result['page']): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e(current_url_with(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
