<section class="card narrow">
    <h1>Quên mật khẩu</h1>
    <?php if ($resetLink): ?>
        <div class="alert info">
            <strong>Chế độ demo:</strong> hệ thống chưa gửi email thật. Dùng liên kết sau (hiệu lực 30 phút):<br>
            <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('auth/forgot')) ?>">
        <?= csrf_field() ?>
        <label>Email đã đăng ký
            <input type="email" name="email" required>
            <?= field_error($errors, 'email') ?>
        </label>
        <button class="btn" type="submit">Tạo liên kết đặt lại</button>
    </form>
</section>
