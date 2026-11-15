<?php /** Trang lỗi. Biến từ abort(): $code, $title, $message */ ?>
<section class="error-page">
    <div class="code"><?= (int) $code ?></div>
    <h1><?= e($title) ?></h1>
    <?php if ($message !== ''): ?><p class="muted"><?= e($message) ?></p><?php endif; ?>
    <p>
        <a class="btn" href="<?= e(url('home')) ?>">Về trang chủ</a>
        <a class="btn outline" href="<?= e(url('pets')) ?>">Xem thú cưng</a>
    </p>
</section>
