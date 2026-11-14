<?php
/* ==============================================================================
   VIEW: DANH SACH DANH GIA
   File    : views/admin/reviews/index.php
   Dia chi  : ?r=admin/reviews
   Controller: controllers/AdminReviewController.php  (ham index)
   Model   : models/AdminReview.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI
           TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 4 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $result @var array $flt @var array $stats @var array $images */
$fmt = fn($d) => date('d/m/Y', strtotime((string) $d));
$back = $_SERVER['REQUEST_URI'] ?? '';
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.reviews')) ?></h1>
        <div class="sub"><?= e(t('review.sub')) ?></div>
    </div>
</div>

<?php require BASE_PATH . '/views/admin/layout/tabs_content.php'; ?>
<?php /* ----- KHOI 2: The tom tat ----- */ ?>
<div class="c sum">
    <div>
        <div class="v"><?= e(number_format($stats['avg'], 1, ',', '.')) ?> / 5</div>
        <div class="hint"><?= e(t('review.count', ['n' => $stats['count']])) ?></div>
    </div>
    <?php foreach ([5, 4, 3, 2, 1] as $s): ?>
        <div class="hint" style="min-width:110px"><?= e(t('review.star_n', ['n' => $s])) ?>: <b><?= (int) ($stats['dist'][$s] ?? 0) ?></b></div>
    <?php endforeach; ?>
</div>

<?php /* ----- KHOI 3: Thanh bo loc va tim kiem ----- */ ?>
<form class="c" method="get" action="<?= e(config('base_url')) ?>/index.php">
    <input type="hidden" name="r" value="admin/reviews">
    <div class="fl">
        <select name="type" aria-label="<?= e(t('review.col.target')) ?>">
            <option value=""><?= e(t('review.type.all')) ?></option>
            <option value="pet"<?= sel($flt['type'], 'pet') ?>><?= e(t('review.type.pet')) ?></option>
            <option value="product"<?= sel($flt['type'], 'product') ?>><?= e(t('review.type.product')) ?></option>
        </select>
        <select name="rating" aria-label="<?= e(t('review.col.rating')) ?>">
            <option value=""><?= e(t('review.star.all')) ?></option>
            <?php foreach ([5, 4, 3, 2, 1] as $s): ?>
                <option value="<?= $s ?>"<?= sel($flt['rating'], (string) $s) ?>><?= e(t('review.star_n', ['n' => $s])) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="<?= e(t('review.col.status')) ?>">
            <option value=""><?= e(t('review.status.all')) ?></option>
            <option value="visible"<?= sel($flt['status'], 'visible') ?>><?= e(t('review.status.visible')) ?></option>
            <option value="hidden"<?= sel($flt['status'], 'hidden') ?>><?= e(t('review.status.hidden')) ?></option>
        </select>
        <button class="b1" type="submit"><?= e(t('order.filter')) ?></button>
        <a class="b2" href="<?= e(url('admin/reviews')) ?>"><?= e(t('order.clear')) ?></a>
    </div>
</form>

<?php /* ----- KHOI 4: Bang danh sach ----- */ ?>
<div class="c">
    <table class="tb">
        <thead>
        <tr><th class="no"><?= e(t('common.no')) ?></th>
            <th><?= e(t('review.col.user')) ?></th><th><?= e(t('review.col.target')) ?></th><th><?= e(t('review.col.rating')) ?></th>
            <th><?= e(t('review.col.comment')) ?></th><th><?= e(t('review.col.date')) ?></th><th><?= e(t('review.col.status')) ?></th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['items'] as $i => $r): ?>
            <?php $imgs = $images[$r['id']] ?? []; $n = (int) $r['rating']; $hidden = $r['status'] === 'hidden'; ?>
            <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no($result, $i) ?></td>
                <td data-label="<?= e(t('review.col.user')) ?>"><b><?= e($r['full_name']) ?></b></td>
                <td data-label="<?= e(t('review.col.target')) ?>"><?= e($r['target_name']) ?><br><span class="hint"><?= e(t($r['pet_id'] ? 'review.type.pet' : 'review.type.product')) ?></span></td>
                <td data-label="<?= e(t('review.col.rating')) ?>"><span class="stars" aria-label="<?= e(t('review.stars_label', ['n' => $n])) ?>"><?= str_repeat('★', $n) . str_repeat('☆', 5 - $n) ?></span></td>
                <td data-label="<?= e(t('review.col.comment')) ?>" style="max-width:320px">
                    <?php if ($r['comment'] !== null && $r['comment'] !== '' || $imgs): ?>
                        <details>
                            <summary><?= e($r['comment'] ? mb_strimwidth($r['comment'], 0, 60, '…') : t('review.images', ['n' => count($imgs)])) ?></summary>
                            <?php if ($r['comment']): ?><div style="margin-top:6px"><?= nl2br(e($r['comment'])) ?></div><?php endif; ?>
                            <?php foreach ($imgs as $im): ?>
                                <a href="<?= e(asset($im)) ?>" target="_blank"><img class="thumb" src="<?= e(asset($im)) ?>" alt=""></a>
                            <?php endforeach; ?>
                        </details>
                    <?php else: ?>
                        <span class="hint"><?= e(t('review.no_comment')) ?></span>
                    <?php endif; ?>
                </td>
                <td data-label="<?= e(t('review.col.date')) ?>"><?= e($fmt($r['created_at'])) ?></td>
                <td data-label="<?= e(t('review.col.status')) ?>"><span class="p <?= $hidden ? 'gy' : 'gr' ?>"><?= e(t($hidden ? 'review.status.hidden' : 'review.status.visible')) ?></span></td>
                <td data-label="">
                    <form method="post" action="<?= e(url('admin/reviews/status')) ?>" style="margin:0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                        <input type="hidden" name="status" value="<?= $hidden ? 'visible' : 'hidden' ?>">
                        <input type="hidden" name="back" value="<?= e($back) ?>">
                        <button class="<?= $hidden ? 'b2' : 'b3' ?> sm" type="submit"><?= e(t($hidden ? 'review.show' : 'review.hide')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?><tr><td colspan="8" class="hint"><?= e(t('review.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php require BASE_PATH . '/views/admin/layout/pagination.php'; ?>
</div>
<div class="note"><?= e(t('review.note')) ?></div>
