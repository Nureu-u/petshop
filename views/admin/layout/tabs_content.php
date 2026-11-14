<?php
/* ==============================================================================
   VIEW: TAB chuyen giua Bai viet va Danh gia
   File    : views/admin/layout/tabs_content.php
   Dung boi : duoc "require" vao cac view khac (xem cac dong require trong views/admin/).
   CSS      : public/css/admin-ui.css, nhom [11] TAB LOC NHANH.
   Cac khoi: 1 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php /** Thanh chuyen giua Bai viet va Danh gia (chung mot muc "Blog & Danh gia" tren menu). */ ?>
<?php /* ----- KHOI 1: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a href="<?= e(url('admin/posts')) ?>"<?= $adminActive === 'posts' ? ' class="on"' : '' ?>><?= AdminView::icon('posts', 16) ?> <?= e(t('post.tab.posts')) ?></a>
    <a href="<?= e(url('admin/reviews')) ?>"<?= $adminActive === 'reviews' ? ' class="on"' : '' ?>><?= AdminView::icon('reviews', 16) ?> <?= e(t('nav.reviews')) ?></a>
</div>
