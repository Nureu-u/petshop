<?php /** Đăng nhập. Biến từ AuthController::login: $errors, $old */ ?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Đăng nhập</h1>
        <p class="lead">Chào mừng bạn quay lại Pet Shop.</p>
        <?php if (isset($errors['login'])): ?><div class="alert error"><span><?= e($errors['login']) ?></span></div><?php endif; ?>
        <form method="post" action="<?= e(url('auth/login')) ?>">
            <?= csrf_field() ?>
            <label>Email
                <input type="email" name="email" value="<?= val($old, 'email') ?>" autocomplete="email" required autofocus>
            </label>
            <label>Mật khẩu
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button class="btn block" type="submit">Đăng nhập</button>
        </form>
        <div class="auth-links">
            <a href="<?= e(url('auth/forgot')) ?>">Quên mật khẩu?</a>
            <span>Chưa có tài khoản? <a href="<?= e(url('auth/register')) ?>">Đăng ký</a></span>
        </div>
    </section>
</div>
