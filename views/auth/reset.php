<section class="card narrow">
    <h1>Đặt lại mật khẩu</h1>
    <form method="post" action="<?= e(url('auth/reset', ['token' => $token])) ?>">
        <?= csrf_field() ?>
        <label>Mật khẩu mới
            <input type="password" name="password" minlength="8" required>
            <?= field_error($errors, 'password') ?>
        </label>
        <label>Nhập lại mật khẩu
            <input type="password" name="password_confirm" required>
            <?= field_error($errors, 'password_confirm') ?>
        </label>
        <button class="btn" type="submit">Đặt lại mật khẩu</button>
    </form>
</section>
