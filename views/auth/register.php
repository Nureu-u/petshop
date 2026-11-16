<?php /** Đăng ký. Biến từ AuthController::register: $errors, $old */ ?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Tạo tài khoản</h1>
        <p class="lead">Đăng ký để đặt mua thú cưng và sản phẩm.</p>
        <form method="post" action="<?= e(url('auth/register')) ?>">
            <?= csrf_field() ?>
            <label>Họ tên
                <input name="full_name" value="<?= val($old, 'full_name') ?>" maxlength="100" autocomplete="name" required>
                <?= field_error($errors, 'full_name') ?>
            </label>
            <label>Email
                <input type="email" name="email" value="<?= val($old, 'email') ?>" maxlength="150" autocomplete="email" required>
                <?= field_error($errors, 'email') ?>
            </label>
            <label>Số điện thoại (không bắt buộc)
                <input name="phone" value="<?= val($old, 'phone') ?>" maxlength="20" inputmode="tel" autocomplete="tel">
                <?= field_error($errors, 'phone') ?>
            </label>
            <label>Mật khẩu (tối thiểu 8 ký tự)
                <input type="password" name="password" minlength="8" autocomplete="new-password" required>
                <?= field_error($errors, 'password') ?>
            </label>
            <label>Nhập lại mật khẩu
                <input type="password" name="password_confirm" autocomplete="new-password" required>
                <?= field_error($errors, 'password_confirm') ?>
            </label>
            <button class="btn block" type="submit">Đăng ký</button>
        </form>
        <div class="auth-links">
            <span>Đã có tài khoản? <a href="<?= e(url('auth/login')) ?>">Đăng nhập</a></span>
        </div>
    </section>
</div>
