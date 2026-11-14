<?php
/* ==============================================================================
   VIEW: HO SO CUA TOI (thong tin ca nhan + doi mat khau)
   File    : views/admin/profile/index.php
   Dia chi  : ?r=admin/profile
   Controller: controllers/AdminProfileController.php  (ham index)
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $user @var array $old @var array $errors @var array $pwErrors */
$err = fn(array $a, string $k) => isset($a[$k]) ? '<span class="err">' . e($a[$k]) . '</span>' : '';
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('menu.profile')) ?></h1>
        <div class="sub"><?= e(t('profile.sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Luoi hai cot (noi dung chinh | thong tin phu) ----- */ ?>
<div class="dg">
    <form class="c fm" method="post" action="<?= e(url('admin/profile')) ?>">
        <h2><?= e(t('profile.info')) ?></h2>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">
        <label><?= e(t('profile.email')) ?>
            <input value="<?= e($user['email']) ?>" disabled>
        </label>
        <label><?= e(t('profile.full_name')) ?>
            <input name="full_name" maxlength="100" value="<?= val($old, 'full_name') ?>" required><?= $err($errors, 'full_name') ?>
        </label>
        <button class="b1" type="submit"><?= e(t('profile.save')) ?></button>
    </form>

    <form class="c fm" id="password" method="post" action="<?= e(url('admin/profile')) ?>">
        <h2><?= e(t('profile.pw_title')) ?></h2>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <label><?= e(t('profile.current')) ?>
            <input type="password" name="current_password" autocomplete="current-password" required><?= $err($pwErrors, 'current_password') ?>
        </label>
        <label><?= e(t('profile.new')) ?>
            <input type="password" name="new_password" autocomplete="new-password" minlength="8" maxlength="72" required>
            <span class="hint"><?= e(t('profile.new_hint')) ?></span><?= $err($pwErrors, 'new_password') ?>
        </label>
        <label><?= e(t('profile.confirm')) ?>
            <input type="password" name="new_password_confirm" autocomplete="new-password" required><?= $err($pwErrors, 'new_password_confirm') ?>
        </label>
        <button class="b1" type="submit"><?= e(t('profile.pw_save')) ?></button>
    </form>
</div>
