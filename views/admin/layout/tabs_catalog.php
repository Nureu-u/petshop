<?php
/* ==============================================================================
   VIEW: TAB chuyen giua Thu cung va San pham
   File    : views/admin/layout/tabs_catalog.php
   Dung boi : duoc "require" vao cac view khac (xem cac dong require trong views/admin/).
   CSS      : public/css/admin-ui.css, nhom [11] TAB LOC NHANH.
   Cac khoi: 1 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php /** Thanh chuyen giua Thu cung va San pham (chung mot muc "Thu cung & San pham" tren menu). */ ?>
<?php /* ----- KHOI 1: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a href="<?= e(url('admin/manage/pets')) ?>"<?= $adminActive === 'pets' ? ' class="on"' : '' ?>><?= AdminView::icon('pets', 16) ?> <?= e(t('tabs.pets')) ?></a>
    <a href="<?= e(url('admin/manage/products')) ?>"<?= $adminActive === 'products' ? ' class="on"' : '' ?>><?= AdminView::icon('products', 16) ?> <?= e(t('tabs.products')) ?></a>
</div>
