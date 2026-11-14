<?php
/* ==============================================================================
   VIEW: DANH MUC BAI VIET
   File    : views/admin/posts/categories.php
   Dia chi  : ?r=admin/posts/categories, ?r=admin/posts/categories/save
   Controller: controllers/AdminPostController.php  (ham renderCategories)
   Model   : models/AdminPost.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [12] FORM NHAP LIEU; [13] BANG DANH SACH, NHAN TRANG THAI VA
           PHAN TRANG; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 3 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $items @var array $errors @var array $old */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$editingId = (string) ($old['id'] ?? '');
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.posts')) ?></h1>
        <div class="sub"><?= e(t('pcat.sub')) ?></div>
    </div>
    <a class="b1" href="<?= e(url('admin/posts/form')) ?>"><?= e(t('post.write')) ?></a>
</div>

<?php require BASE_PATH . '/views/admin/layout/tabs_content.php'; ?>
<?php /* ----- KHOI 2: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a href="<?= e(url('admin/posts')) ?>"><?= e(t('post.tab.posts')) ?></a>
    <a class="on" href="<?= e(url('admin/posts/categories')) ?>"><?= e(t('post.tab.cats')) ?> <b><?= count($items) ?></b></a>
</div>

<?php /* ----- KHOI 3: Luoi hai cot (noi dung chinh | thong tin phu) ----- */ ?>
<div class="dg">
    <div class="c">
        <table class="tb">
            <thead><tr><th class="no"><?= e(t('common.no')) ?></th><th><?= e(t('pcat.name')) ?></th><th><?= e(t('pcat.desc')) ?></th><th><?= e(t('pcat.slug')) ?></th><th class="r"><?= e(t('pcat.count')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $i => $c): ?>
                <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no([], $i) ?></td>
                    <td data-label="<?= e(t('pcat.name')) ?>"><b><?= e($c['name']) ?></b></td>
                    <td class="desc" data-label="<?= e(t('pcat.desc')) ?>">
                        <?php if (!empty($c['description'])): ?>
                            <div class="clamp" title="<?= e($c['description']) ?>"><?= e($c['description']) ?></div>
                        <?php else: ?>
                            <span class="hint">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= e(t('pcat.slug')) ?>"><?= e($c['slug']) ?></td>
                    <td class="r" data-label="<?= e(t('pcat.count')) ?>"><?= (int) $c['post_count'] ?></td>
                    <td data-label="">
                        <div class="rr">
                            <a class="lk" href="<?= e(url('admin/posts/categories', ['id' => $c['id']])) ?>"><?= e(t('post.edit')) ?></a>
                            <?php if ((int) $c['post_count'] === 0): ?>
                                <form method="post" action="<?= e(url('admin/posts/categories/delete')) ?>" data-confirm="<?= e(t('pcat.delete_confirm')) ?>" style="margin:0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                                    <button class="b3 sm" type="submit"><?= e(t('pcat.delete')) ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="6" class="hint"><?= e(t('pcat.empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="note"><?= e(t('pcat.note')) ?></div>
    </div>

    <form class="c fm" method="post" action="<?= e(url('admin/posts/categories/save')) ?>">
        <h2><?= e($editingId !== '' ? t('pcat.edit') : t('pcat.add')) ?></h2>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($editingId) ?>">
        <label><?= e(t('pcat.f.name')) ?>
            <input name="name" maxlength="100" value="<?= val($old, 'name') ?>" required><?= $err('name') ?>
        </label>
        <label><?= e(t('pcat.slug')) ?>
            <input name="slug" maxlength="120" value="<?= val($old, 'slug') ?>">
            <span class="hint"><?= e(t('cats.f.slug_hint')) ?></span><?= $err('slug') ?>
        </label>
        <label><?= e(t('pcat.desc')) ?>
            <input name="description" maxlength="255" value="<?= val($old, 'description') ?>"><?= $err('description') ?>
        </label>
        <div class="rr">
            <button class="b1" type="submit"><?= e(t('pcat.save')) ?></button>
            <?php if ($editingId !== ''): ?><a class="b2" href="<?= e(url('admin/posts/categories')) ?>"><?= e(t('common.cancel')) ?></a><?php endif; ?>
        </div>
    </form>
</div>
