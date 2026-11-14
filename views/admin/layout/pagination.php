<?php
/* ==============================================================================
   VIEW: THANH CHUYEN TRANG dung chung cho cac bang
   File    : views/admin/layout/pagination.php
   Dung boi : duoc "require" vao cac view khac (xem cac dong require trong views/admin/).
   CSS      : public/css/admin-ui.css, nhom [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG.
   ============================================================================== */
?>
<?php if (($result['pages'] ?? 1) > 1): ?>
    <nav class="pg">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <?php if ($i === $result['page']): ?>
                <span class="on"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e(current_url_with(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
