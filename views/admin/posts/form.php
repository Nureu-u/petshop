<?php
/* ==============================================================================
   VIEW: VIET / SUA BAI VIET
   File    : views/admin/posts/form.php
   Dia chi  : ?r=admin/posts/form
   Controller: controllers/AdminPostController.php  (ham form)
   Model   : models/AdminPost.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var ?array $post @var array $old @var array $errors @var array $cats */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$status = (string) ($old['status'] ?? 'draft');
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <a class="lk" href="<?= e(url('admin/posts')) ?>">‹ <?= e(t('nav.posts')) ?></a>
        <h1><?= e($post ? t('post.edit_title') : t('post.new_title')) ?></h1>
        <div class="sub"><?= e(t('post.form_sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Form nhap lieu ----- */ ?>
<form class="c fm" method="post" enctype="multipart/form-data" style="max-width:900px"
      action="<?= e(url('admin/posts/form', $post ? ['id' => $post['id']] : [])) ?>">
    <?= csrf_field() ?>
    <label><?= e(t('post.f.title')) ?>
        <input name="title" maxlength="200" value="<?= val($old, 'title') ?>" required><?= $err('title') ?>
    </label>
    <div class="gr2">
        <label><?= e(t('post.f.cat')) ?>
            <select name="category_id" required>
                <option value=""><?= e(t('post.f.choose')) ?></option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= e($c['id']) ?>"<?= sel($old['category_id'] ?? '', $c['id']) ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select><?= $err('category_id') ?>
        </label>
        <label><?= e(t('post.f.slug')) ?>
            <input name="slug" maxlength="220" value="<?= val($old, 'slug') ?>">
            <span class="hint"><?= e(t('post.f.slug_hint')) ?></span><?= $err('slug') ?>
        </label>
    </div>
    <label><?= e(t('post.f.excerpt')) ?>
        <input name="excerpt" maxlength="500" value="<?= val($old, 'excerpt') ?>">
        <span class="hint"><?= e(t('post.f.excerpt_hint')) ?></span><?= $err('excerpt') ?>
    </label>
    <label><?= e(t('post.f.content')) ?>
        <textarea name="content" rows="10" required><?= val($old, 'content') ?></textarea><?= $err('content') ?>
    </label>
    <div class="gr2">
        <label><?= e(t('post.f.image')) ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="hint"><?= e(t('post.f.image_hint')) ?></span><?= $err('image') ?>
            <?php if (!empty($post['image'])): ?>
                <span class="hint"><img class="thumb" src="<?= e(asset($post['image'])) ?>" alt=""> <?= e(t('post.f.image_current')) ?></span>
            <?php endif; ?>
        </label>
        <label><?= e(t('post.f.status')) ?>
            <select name="status">
                <?php foreach (AdminPost::STATUSES as $s): ?>
                    <option value="<?= e($s) ?>"<?= sel($status, $s) ?>><?= e(t('post.status.' . $s)) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="hint"><?= e(t('post.f.status_hint')) ?></span><?= $err('status') ?>
        </label>
    </div>
    <div class="rr">
        <button class="b1" type="submit"><?= e(t('post.save')) ?></button>
        <a class="b2" href="<?= e(url('admin/posts')) ?>"><?= e(t('common.cancel')) ?></a>
    </div>
</form>
