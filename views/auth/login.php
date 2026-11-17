<section class="card narrow">
    <h1>Đăng nhập</h1>
    <?php if (isset($errors['login'])): ?><div class="alert error"><?= e($errors['login']) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('auth/login')) ?>">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" value="<?= val($old, 'email') ?>" required autofocus>
        </label>
        <label>Mật khẩu
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Đăng nhập</button>
    </form>
    <p><a href="<?= e(url('auth/forgot')) ?>">Quên mật khẩu?</a> · <a href="<?= e(url('auth/register')) ?>">Tạo tài khoản</a></p>
</section>
