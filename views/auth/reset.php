<?php /** Đặt lại mật khẩu. Biến từ AuthController::reset: $errors, $token */ ?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Đặt lại mật khẩu</h1>
        <p class="lead">Nhập mật khẩu mới cho tài khoản của bạn.</p>
        <form method="post" action="<?= e(url('auth/reset', ['token' => $token])) ?>">
            <?= csrf_field() ?>
            <label>Mật khẩu mới (tối thiểu 8 ký tự)
                <input type="password" name="password" minlength="8" autocomplete="new-password" required>
                <?= field_error($errors, 'password') ?>
            </label>
            <label>Nhập lại mật khẩu
                <input type="password" name="password_confirm" autocomplete="new-password" required>
                <?= field_error($errors, 'password_confirm') ?>
            </label>
            <button class="btn block" type="submit">Đặt lại mật khẩu</button>
        </form>
    </section>
</div>
