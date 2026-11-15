<?php /** Quên mật khẩu. Biến từ AuthController::forgot: $errors, $resetLink */ ?>
<div class="auth-wrap">
    <section class="card auth-card">
        <h1>Quên mật khẩu</h1>
        <p class="lead">Nhập email đã đăng ký, hệ thống sẽ tạo liên kết đặt lại mật khẩu.</p>
        <?php if ($resetLink): ?>
            <div class="alert info">
                <span><strong>Chế độ demo:</strong> hệ thống chưa gửi email thật. Dùng liên kết sau (hiệu lực 30 phút):<br>
                <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></span>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('auth/forgot')) ?>">
            <?= csrf_field() ?>
            <label>Email đã đăng ký
                <input type="email" name="email" autocomplete="email" required>
                <?= field_error($errors, 'email') ?>
            </label>
            <button class="btn block" type="submit">Tạo liên kết đặt lại</button>
        </form>
        <div class="auth-links">
            <a href="<?= e(url('auth/login')) ?>">&larr; Quay lại đăng nhập</a>
        </div>
    </section>
</div>
