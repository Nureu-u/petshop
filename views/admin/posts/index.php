<?php
/* ==============================================================================
   VIEW: DANH SACH BAI VIET
   File    : views/admin/posts/index.php
   Dia chi  : ?r=admin/posts
   Controller: controllers/AdminPostController.php  (ham index)
   Model   : models/AdminPost.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI
           TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 4 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $result @var array $flt @var array $cats */
$fmt = fn($d) => date('d/m/Y', strtotime((string) $d));
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.posts')) ?></h1>
        <div class="sub"><?= e(t('post.sub')) ?></div>
    </div>
    <a class="b1" href="<?= e(url('admin/posts/form')) ?>"><?= e(t('post.write')) ?></a>
</div>

<?php require BASE_PATH . '/views/admin/layout/tabs_content.php'; ?>
<?php /* ----- KHOI 2: Tab loc nhanh ----- */ ?>
<div class="vt" style="margin-bottom:14px">
    <a class="on" href="<?= e(url('admin/posts')) ?>"><?= e(t('post.tab.posts')) ?></a>
    <a href="<?= e(url('admin/posts/categories')) ?>"><?= e(t('post.tab.cats')) ?> <b><?= count($cats) ?></b></a>
</div>

<?php /* ----- KHOI 3: Thanh bo loc va tim kiem ----- */ ?>
<form class="c" method="get" action="<?= e(config('base_url')) ?>/index.php">
    <input type="hidden" name="r" value="admin/posts">
    <div class="fl">
        <input class="grow" type="search" name="q" value="<?= e($flt['q']) ?>" placeholder="<?= e(t('post.search_ph')) ?>" aria-label="<?= e(t('post.search_ph')) ?>">
        <select name="category" aria-label="<?= e(t('post.col.category')) ?>">
            <option value=""><?= e(t('post.cat.all')) ?></option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= e($c['id']) ?>"<?= sel($flt['category'], $c['id']) ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="<?= e(t('post.col.status')) ?>">
            <option value=""><?= e(t('post.status.all')) ?></option>
            <?php foreach (AdminPost::STATUSES as $s): ?>
                <option value="<?= e($s) ?>"<?= sel($flt['status'], $s) ?>><?= e(t('post.status.' . $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="b1" type="submit"><?= e(t('order.filter')) ?></button>
        <a class="b2" href="<?= e(url('admin/posts')) ?>"><?= e(t('order.clear')) ?></a>
    </div>
</form>

<?php /* ----- KHOI 4: Bang danh sach ----- */ ?>
<div class="c">
    <table class="tb">
        <thead>
        <tr><th class="no"><?= e(t('common.no')) ?></th>
            <th><?= e(t('pet.col.photo')) ?></th><th><?= e(t('post.col.title')) ?></th><th><?= e(t('post.col.category')) ?></th><th><?= e(t('post.col.author')) ?></th>
            <th><?= e(t('post.col.status')) ?></th><th><?= e(t('post.col.published')) ?></th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['items'] as $i => $p): ?>
            <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no($result, $i) ?></td>
                <td data-label="<?= e(t('pet.col.photo')) ?>">
                    <?php if ($p['image']): ?><img class="thumb" src="<?= e(asset($p['image'])) ?>" alt=""><?php else: ?><span class="thumb ph"><?= AdminView::icon('posts', 20) ?></span><?php endif; ?>
                </td>
                <td data-label="<?= e(t('post.col.title')) ?>"><b><?= e($p['title']) ?></b><br><span class="hint"><?= e($p['slug']) ?></span></td>
                <td data-label="<?= e(t('post.col.category')) ?>"><?= e($p['category_name']) ?></td>
                <td data-label="<?= e(t('post.col.author')) ?>"><?= e($p['author_name']) ?></td>
                <td data-label="<?= e(t('post.col.status')) ?>"><span class="p <?= e(AdminPost::statusClass($p['status'])) ?>"><?= e(t('post.status.' . $p['status'])) ?></span></td>
                <td data-label="<?= e(t('post.col.published')) ?>"><?= e($p['published_at'] ? $fmt($p['published_at']) : t('post.not_published')) ?></td>
                <td data-label="">
                    <div class="rr">
                        <a class="lk" href="<?= e(url('admin/posts/form', ['id' => $p['id']])) ?>"><?= e(t('post.edit')) ?></a>
                        <form method="post" action="<?= e(url('admin/posts/status')) ?>" style="margin:0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <?php if ($p['status'] === 'published'): ?>
                                <input type="hidden" name="status" value="hidden">
                                <button class="b3 sm" type="submit"><?= e(t('post.hide')) ?></button>
                            <?php else: ?>
                                <input type="hidden" name="status" value="published">
                                <button class="b2 sm" type="submit"><?= e(t($p['status'] === 'hidden' ? 'post.republish' : 'post.publish')) ?></button>
                            <?php endif; ?>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?><tr><td colspan="8" class="hint"><?= e(t('post.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php require BASE_PATH . '/views/admin/layout/pagination.php'; ?>
</div>
