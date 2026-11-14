<?php
/* ==============================================================================
   VIEW: DANH SACH SAN PHAM
   File    : views/admin/catalog/products/index.php
   Dia chi  : ?r=admin/manage/products
   Controller: controllers/AdminProductController.php  (ham index)
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI
           TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 4 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $result @var array $flt @var array $cats @var array $counts @var array $brandList */
$total = array_sum($counts);
$stCls = ['active' => 'gr', 'inactive' => 'y', 'hidden' => 'gy'];
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.products')) ?></h1>
        <div class="sub"><?= e(t('prod.sub')) ?></div>
    </div>
    <a class="b1" href="<?= e(url('admin/manage/products/form')) ?>"><?= e(t('prod.add')) ?></a>
</div>

<?php require BASE_PATH . '/views/admin/layout/tabs_catalog.php'; ?>
<?php /* ----- KHOI 2: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a href="<?= e(current_url_with(['status' => '', 'page' => 1])) ?>"<?= $flt['status'] === '' ? ' class="on"' : '' ?>><?= e(t('order.tab.all')) ?> <b><?= (int) $total ?></b></a>
    <?php foreach (Product::STATUSES as $s): ?>
        <a href="<?= e(current_url_with(['status' => $s, 'page' => 1])) ?>"<?= $flt['status'] === $s ? ' class="on"' : '' ?>><?= e(t('prod.status.' . $s)) ?> <b><?= (int) ($counts[$s] ?? 0) ?></b></a>
    <?php endforeach; ?>
</div>

<?php /* ----- KHOI 3: Thanh bo loc va tim kiem ----- */ ?>
<form class="c" method="get" action="<?= e(config('base_url')) ?>/index.php">
    <input type="hidden" name="r" value="admin/manage/products">
    <input type="hidden" name="status" value="<?= e($flt['status']) ?>">
    <div class="fl">
        <input class="grow" type="search" name="q" value="<?= e($flt['q']) ?>" placeholder="<?= e(t('prod.search_ph')) ?>" aria-label="<?= e(t('prod.search_ph')) ?>">
        <select name="category_id" aria-label="<?= e(t('pet.col.category')) ?>">
            <option value=""><?= e(t('pet.cat_all')) ?></option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= e($c['id']) ?>"<?= sel($flt['category_id'], $c['id']) ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="brand" aria-label="<?= e(t('prod.f.brand')) ?>">
            <option value=""><?= e(t('prod.brand_all')) ?></option>
            <?php foreach ($brandList as $br): ?>
                <option value="<?= e($br) ?>"<?= sel($flt['brand'], $br) ?>><?= e($br) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="stock" aria-label="<?= e(t('prod.col.stock')) ?>">
            <option value=""><?= e(t('prod.stock_all')) ?></option>
            <?php foreach (['in', 'low', 'out'] as $sk): ?>
                <option value="<?= e($sk) ?>"<?= sel($flt['stock'], $sk) ?>><?= e(t('prod.stock.' . $sk)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" aria-label="<?= e(t('pet.sort')) ?>">
            <?php foreach (['oldest', 'newest', 'price_asc', 'price_desc', 'name'] as $so): ?>
                <option value="<?= e($so) ?>"<?= sel($flt['sort'] !== '' ? $flt['sort'] : 'oldest', $so) ?>><?= e(t('pet.sort.' . $so)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="b1" type="submit"><?= e(t('order.filter')) ?></button>
        <a class="b2" href="<?= e(url('admin/manage/products')) ?>"><?= e(t('order.clear')) ?></a>
    </div>
</form>

<?php /* ----- KHOI 4: Bang danh sach ----- */ ?>
<div class="c">
    <table class="tb">
        <thead>
        <tr><th class="no"><?= e(t('common.no')) ?></th>
            <th><?= e(t('pet.col.photo')) ?></th><th><?= e(t('pet.col.name')) ?></th><th><?= e(t('pet.col.category')) ?></th>
            <th><?= e(t('prod.col.brand')) ?></th><th class="r"><?= e(t('pet.col.price')) ?></th><th class="r"><?= e(t('prod.col.stock')) ?></th>
            <th><?= e(t('pet.col.status')) ?></th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['items'] as $i => $p): ?>
            <?php $stock = (int) $p['stock']; ?>
            <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no($result, $i) ?></td>
                <td data-label="<?= e(t('pet.col.photo')) ?>">
                    <?php if ($p['image']): ?><img class="thumb" src="<?= e(asset($p['image'])) ?>" alt=""><?php else: ?><span class="thumb ph"><?= AdminView::icon('products', 20) ?></span><?php endif; ?>
                </td>
                <td data-label="<?= e(t('pet.col.name')) ?>"><a class="lk" href="<?= e(url('admin/manage/products/form', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a><br><span class="hint mono"><?= e($p['id']) ?></span></td>
                <td data-label="<?= e(t('pet.col.category')) ?>"><?= e($p['category_name']) ?></td>
                <td data-label="<?= e(t('prod.col.brand')) ?>"><?= e($p['brand'] ?: '—') ?></td>
                <td class="r" data-label="<?= e(t('pet.col.price')) ?>"><b><?= e(format_price($p['price'])) ?></b></td>
                <td class="r" data-label="<?= e(t('prod.col.stock')) ?>">
                    <?php if ($stock === 0): ?><span class="p rd"><?= e(t('dash.out')) ?></span>
                    <?php elseif ($stock <= 5): ?><span class="p y"><?= e(t('dash.left', ['n' => $stock])) ?></span>
                    <?php else: ?><?= $stock ?><?php endif; ?>
                </td>
                <td data-label="<?= e(t('pet.col.status')) ?>"><span class="p <?= e($stCls[$p['status']] ?? 'gy') ?>"><?= e(t('prod.status.' . $p['status'])) ?></span></td>
                <td data-label="">
                    <div class="rr">
                        <a class="lk" href="<?= e(url('admin/manage/products/form', ['id' => $p['id']])) ?>"><?= e(t('pet.edit')) ?></a>
                        <form method="post" action="<?= e(url('admin/manage/products/status')) ?>" class="rr" style="margin:0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <?php foreach (Product::STATUSES as $s): ?>
                                <?php if ($s !== $p['status']): ?>
                                    <button class="b2 sm" type="submit" name="status" value="<?= e($s) ?>"><?= e(t('prod.set.' . $s)) ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?><tr><td colspan="9" class="hint"><?= e(t('prod.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php require BASE_PATH . '/views/admin/layout/pagination.php'; ?>
</div>
