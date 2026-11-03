<div class="two-col">
    <section class="card">
        <h1>Hồ sơ cá nhân</h1>
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
                <input name="phone" value="<?= val($old, 'phone') ?>" maxlength="20">
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
                <input type="password" name="current_password" required>
                <?= field_error($errors, 'current_password') ?>
            </label>
            <label>Mật khẩu mới
                <input type="password" name="new_password" minlength="8" required>
                <?= field_error($errors, 'new_password') ?>
            </label>
            <label>Nhập lại mật khẩu mới
                <input type="password" name="new_password_confirm" required>
                <?= field_error($errors, 'new_password_confirm') ?>
            </label>
            <button class="btn" type="submit">Đổi mật khẩu</button>
        </form>
    </section>
</div>
