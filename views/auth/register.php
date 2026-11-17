<section class="card narrow">
    <h1>Đăng ký</h1>
    <form method="post" action="<?= e(url('auth/register')) ?>">
        <?= csrf_field() ?>
        <label>Họ tên
            <input name="full_name" value="<?= val($old, 'full_name') ?>" maxlength="100" required>
            <?= field_error($errors, 'full_name') ?>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= val($old, 'email') ?>" maxlength="150" required>
            <?= field_error($errors, 'email') ?>
        </label>
        <label>Số điện thoại (không bắt buộc)
            <input name="phone" value="<?= val($old, 'phone') ?>" maxlength="20">
            <?= field_error($errors, 'phone') ?>
        </label>
        <label>Mật khẩu (tối thiểu 8 ký tự)
            <input type="password" name="password" minlength="8" required>
            <?= field_error($errors, 'password') ?>
        </label>
        <label>Nhập lại mật khẩu
            <input type="password" name="password_confirm" required>
            <?= field_error($errors, 'password_confirm') ?>
        </label>
        <button class="btn" type="submit">Đăng ký</button>
    </form>
    <p>Đã có tài khoản? <a href="<?= e(url('auth/login')) ?>">Đăng nhập</a></p>
</section>
