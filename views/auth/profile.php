<?php
/** Hồ sơ cá nhân. Biến từ AuthController::profile: $errors, $old, $user */
$since = !empty($user['created_at']) ? date('d/m/Y', strtotime((string) $user['created_at'])) : '';
?>
<div class="profile-head">
    <span class="avatar"><?= e(user_initial((string) $user['full_name'])) ?></span>
    <div>
        <h1><?= e($user['full_name']) ?></h1>
        <span class="muted"><?= e($user['email']) ?><?= $since ? ' · Thành viên từ ' . e($since) : '' ?></span>
    </div>
</div>
<p style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px;">
    <a class="btn outline sm" href="<?= e(url('orders/history')) ?>">Đơn hàng của tôi</a>
    <?php if (Auth::isAdmin()): ?>
        <a class="btn outline sm" href="<?= e(admin_home_url()) ?>">Vào trang quản trị</a>
    <?php endif; ?>
</p>

<div class="two-col">
    <section class="card">
        <h2>Thông tin cá nhân</h2>
        <form method="post" action="<?= e(url('auth/profile')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <label>Email
                <input value="<?= e($user['email']) ?>" disabled>
            </label>
            <label>Họ tên
                <input name="full_name" value="<?= val($old, 'full_name') ?>" maxlength="100" required>
                <?= field_error($errors, 'full_name') ?>
            </label>
            <label>Số điện thoại
                <input name="phone" value="<?= val($old, 'phone') ?>" maxlength="20" inputmode="tel">
                <?= field_error($errors, 'phone') ?>
            </label>
            <label>Địa chỉ
                <input name="address" value="<?= val($old, 'address') ?>" maxlength="255">
                <?= field_error($errors, 'address') ?>
            </label>
            <button class="btn" type="submit">Lưu thay đổi</button>
        </form>
    </section>

    <section class="card">
        <h2>Đổi mật khẩu</h2>
        <form method="post" action="<?= e(url('auth/profile')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <label>Mật khẩu hiện tại
                <input type="password" name="current_password" autocomplete="current-password" required>
                <?= field_error($errors, 'current_password') ?>
            </label>
            <label>Mật khẩu mới (tối thiểu 8 ký tự)
                <input type="password" name="new_password" minlength="8" autocomplete="new-password" required>
                <?= field_error($errors, 'new_password') ?>
            </label>
            <label>Nhập lại mật khẩu mới
                <input type="password" name="new_password_confirm" autocomplete="new-password" required>
                <?= field_error($errors, 'new_password_confirm') ?>
            </label>
            <button class="btn" type="submit">Đổi mật khẩu</button>
        </form>
    </section>
</div>
