<?php
/* ==============================================================================
   VIEW: THEM / SUA SAN PHAM
   File    : views/admin/catalog/products/form.php
   Dia chi  : ?r=admin/manage/products/form
   Controller: controllers/AdminProductController.php  (ham form)
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var ?array $product @var array $old @var array $errors @var array $cats */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <a class="lk" href="<?= e(url('admin/manage/products')) ?>">‹ <?= e(t('nav.products')) ?></a>
        <h1><?= e($product ? t('prod.edit_title') : t('prod.new_title')) ?></h1>
        <div class="sub"><?= e(t('prod.form_sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Form nhap lieu ----- */ ?>
<form class="c fm" method="post" enctype="multipart/form-data" style="max-width:900px"
      action="<?= e(url('admin/manage/products/form', $product ? ['id' => $product['id']] : [])) ?>">
    <?= csrf_field() ?>
    <label><?= e(t('pet.f.name')) ?>
        <input name="name" maxlength="150" value="<?= val($old, 'name') ?>" required><?= $err('name') ?>
    </label>
    <div class="gr2">
        <label><?= e(t('pet.f.category')) ?>
            <select name="category_id" required>
                <option value=""><?= e(t('pet.f.choose')) ?></option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= e($c['id']) ?>"<?= sel($old['category_id'] ?? '', $c['id']) ?>><?= e($c['name']) ?><?= $c['status'] !== 'active' ? ' ' . e(t('pet.cat_inactive')) : '' ?></option>
                <?php endforeach; ?>
            </select><?= $err('category_id') ?>
        </label>
        <label><?= e(t('prod.f.brand')) ?>
            <input name="brand" maxlength="100" value="<?= val($old, 'brand') ?>"><?= $err('brand') ?>
        </label>
        <label><?= e(t('pet.f.price')) ?>
            <input name="price" inputmode="numeric" data-money placeholder="<?= e(t('ph.example', ['v' => '250.000'])) ?>" value="<?= val($old, 'price') ?>" required><?= $err('price') ?>
        </label>
        <label><?= e(t('prod.f.stock')) ?>
            <input name="stock" type="number" min="0" step="1" value="<?= val($old, 'stock', '0') ?>" required>
            <span class="hint"><?= e(t('prod.f.stock_hint')) ?></span><?= $err('stock') ?>
        </label>
        <label><?= e(t('pet.f.status')) ?>
            <select name="status">
                <?php foreach (Product::STATUSES as $s): ?>
                    <option value="<?= e($s) ?>"<?= sel($old['status'] ?? 'active', $s) ?>><?= e(t('prod.status.' . $s)) ?></option>
                <?php endforeach; ?>
            </select><?= $err('status') ?>
        </label>
        <label><?= e(t('pet.f.image')) ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="hint"><?= e(t('pet.f.image_hint')) ?></span><?= $err('image') ?>
            <?php if (!empty($product['image'])): ?>
                <span class="hint"><img class="thumb" src="<?= e(asset($product['image'])) ?>" alt=""> <?= e(t('pet.f.image_current')) ?></span>
            <?php endif; ?>
        </label>
    </div>
    <label><?= e(t('pet.f.desc')) ?>
        <textarea name="description" rows="5"><?= val($old, 'description') ?></textarea>
    </label>
    <div class="rr">
        <button class="b1" type="submit"><?= e(t('prod.save')) ?></button>
        <a class="b2" href="<?= e(url('admin/manage/products')) ?>"><?= e(t('common.cancel')) ?></a>
    </div>
</form>
