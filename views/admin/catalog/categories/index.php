<?php
/* ==============================================================================
   VIEW: DANH MUC THU CUNG / DANH MUC SAN PHAM
   File    : views/admin/catalog/categories/index.php
   Dia chi  : ?r=admin/manage/categories, ?r=admin/manage/categories/save
   Controller: controllers/AdminCategoryController.php  (ham render)
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [12] FORM NHAP LIEU; [13] BANG DANH SACH, NHAN TRANG THAI VA
           PHAN TRANG; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 3 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var string $type @var array $items @var ?array $editing @var array $errors */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$ed = $editing ?? [];
$editingId = (string) ($ed['id'] ?? '');
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.categories')) ?></h1>
        <div class="sub"><?= e(t('cats.sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a href="<?= e(url('admin/manage/categories', ['type' => 'pet'])) ?>"<?= $type === 'pet' ? ' class="on"' : '' ?>><?= e(t('cats.tab.pet')) ?></a>
    <a href="<?= e(url('admin/manage/categories', ['type' => 'product'])) ?>"<?= $type === 'product' ? ' class="on"' : '' ?>><?= e(t('cats.tab.product')) ?></a>
</div>

<?php /* ----- KHOI 3: Luoi hai cot (noi dung chinh | thong tin phu) ----- */ ?>
<div class="dg">
    <div class="c">
        <table class="tb">
            <thead><tr><th class="no"><?= e(t('common.no')) ?></th>
                <th><?= e(t('cats.col.name')) ?></th><th><?= e(t('cats.col.desc')) ?></th><th><?= e(t('cats.col.slug')) ?></th><th class="r"><?= e(t('cats.col.count')) ?></th>
                <th><?= e(t('cats.col.status')) ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $i => $c): ?>
                <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no([], $i) ?></td>
                    <td data-label="<?= e(t('cats.col.name')) ?>">
                        <b><?= e($c['name']) ?></b>
                        <br><span class="hint mono"><?= e($c['id']) ?></span>
                    </td>
                    <td class="desc" data-label="<?= e(t('cats.col.desc')) ?>">
                        <?php if (!empty($c['description'])): ?>
                            <div class="clamp" title="<?= e($c['description']) ?>"><?= e($c['description']) ?></div>
                        <?php else: ?>
                            <span class="hint">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= e(t('cats.col.slug')) ?>"><?= e($c['slug']) ?></td>
                    <td class="r" data-label="<?= e(t('cats.col.count')) ?>"><?= (int) $c['item_count'] ?></td>
                    <td data-label="<?= e(t('cats.col.status')) ?>"><span class="p <?= $c['status'] === 'active' ? 'gr' : 'gy' ?>"><?= e(t('cats.status.' . $c['status'])) ?></span></td>
                    <td data-label="">
                        <div class="rr">
                            <a class="lk" href="<?= e(url('admin/manage/categories', ['type' => $type, 'id' => $c['id']])) ?>"><?= e(t('pet.edit')) ?></a>
                            <form method="post" action="<?= e(url('admin/manage/categories/toggle', ['type' => $type])) ?>" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                                <button class="<?= $c['status'] === 'active' ? 'b3' : 'b2' ?> sm" type="submit"><?= e(t($c['status'] === 'active' ? 'cats.hide' : 'cats.show')) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="7" class="hint"><?= e(t('cats.empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="note"><?= e(t('cats.note')) ?></div>
    </div>

    <form class="c fm" method="post" action="<?= e(url('admin/manage/categories/save', ['type' => $type])) ?>">
        <h2><?= e($editingId !== '' ? t('cats.edit') : t('cats.add')) ?></h2>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($editingId) ?>">
        <label><?= e(t('cats.f.name')) ?>
            <input name="name" maxlength="100" value="<?= val($ed, 'name') ?>" required><?= $err('name') ?>
        </label>
        <label><?= e(t('cats.f.slug')) ?>
            <input name="slug" maxlength="120" value="<?= val($ed, 'slug') ?>">
            <span class="hint"><?= e(t('cats.f.slug_hint')) ?></span><?= $err('slug') ?>
        </label>
        <label><?= e(t('cats.f.desc')) ?>
            <input name="description" maxlength="255" value="<?= val($ed, 'description') ?>"><?= $err('description') ?>
        </label>
        <label><?= e(t('cats.f.status')) ?>
            <select name="status">
                <option value="active"<?= sel($ed['status'] ?? 'active', 'active') ?>><?= e(t('cats.status.active')) ?></option>
                <option value="inactive"<?= sel($ed['status'] ?? 'active', 'inactive') ?>><?= e(t('cats.status.inactive')) ?></option>
            </select><?= $err('status') ?>
        </label>
        <div class="rr">
            <button class="b1" type="submit"><?= e(t('cats.save')) ?></button>
            <?php if ($editingId !== ''): ?><a class="b2" href="<?= e(url('admin/manage/categories', ['type' => $type])) ?>"><?= e(t('common.cancel')) ?></a><?php endif; ?>
        </div>
    </form>
</div>
