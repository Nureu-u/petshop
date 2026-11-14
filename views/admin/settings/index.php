<?php
/* ==============================================================================
   VIEW: CAI DAT GIAO DIEN (anh nen trang dang nhap)
   File    : views/admin/settings/index.php
   Dia chi  : ?r=admin/settings
   Controller: controllers/AdminSettingsController.php  (ham index)
   Model   : models/AdminSettings.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU; [18] O XEM TRUOC ANH NEN TRANG DANG NHAP.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $cfg @var array $errors */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$pos = (string) ($cfg['login_pos'] ?? 'center');
$tag = trim((string) ($cfg['login_tagline'] ?? ''));
$posCss = ['top' => 'center top', 'center' => 'center', 'bottom' => 'center bottom'][$pos] ?? 'center';
$image = '';
if (!empty($cfg['login_image']) && is_file(BASE_PATH . '/' . $cfg['login_image'])) {
    $image = asset($cfg['login_image']);
}
$pvStyle = $image !== '' ? "background-image:url('" . e($image) . "');background-position:" . $posCss : '';
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('menu.settings')) ?></h1>
        <div class="sub"><?= e(t('settings.sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Luoi hai cot (noi dung chinh | thong tin phu) ----- */ ?>
<div class="dg">
    <form class="c fm" method="post" enctype="multipart/form-data" action="<?= e(url('admin/settings')) ?>">
        <h2><?= e(t('settings.login_title')) ?></h2>
        <p class="d"><?= e(t('settings.login_hint')) ?></p>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <label><?= e(t('settings.image')) ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"><?= $err('image') ?>
        </label>
        <label><?= e(t('settings.pos')) ?>
            <select name="login_pos">
                <?php foreach (['top', 'center', 'bottom'] as $p): ?>
                    <option value="<?= e($p) ?>"<?= sel($pos, $p) ?>><?= e(t('settings.pos.' . $p)) ?></option>
                <?php endforeach; ?>
            </select><?= $err('login_pos') ?>
        </label>
        <label><?= e(t('settings.tagline')) ?>
            <input name="login_tagline" maxlength="120" value="<?= e($tag) ?>">
            <span class="hint"><?= e(t('settings.tagline_hint')) ?></span><?= $err('login_tagline') ?>
        </label>
        <button class="b1" type="submit"><?= e(t('settings.save')) ?></button>
    </form>

    <div>
        <div class="c">
            <h2><?= e(t('settings.preview')) ?></h2>
            <div class="pv" style="<?= $pvStyle ?>">
                <div class="pv-tag"><?= e($tag !== '' ? $tag : t('login.tagline')) ?></div>
            </div>
            <p class="hint" style="margin:10px 0 0"><?= e(t($image !== '' ? 'settings.preview_note' : 'settings.no_image')) ?></p>
            <?php if ($image !== ''): ?>
                <form method="post" action="<?= e(url('admin/settings')) ?>" data-confirm="<?= e(t('settings.reset_confirm')) ?>" style="margin:12px 0 0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset">
                    <button class="b3" type="submit"><?= e(t('settings.reset')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
