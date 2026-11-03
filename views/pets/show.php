<p><a href="<?= e(url('pets')) ?>">← Danh sách thú cưng</a></p>
<div class="detail">
    <div>
        <?php if ($pet['image']): ?>
            <img class="big" src="<?= e(asset($pet['image'])) ?>" alt="<?= e($pet['name']) ?>">
        <?php else: ?>
            <div class="noimg big">Chưa có ảnh</div>
        <?php endif; ?>
    </div>
    <div>
        <h1><?= e($pet['name']) ?></h1>
        <p class="price"><?= e(format_price($pet['price'])) ?></p>
        <p><span class="badge <?= e($pet['status']) ?>"><?= e(status_label($pet['status'])) ?></span></p>
        <table class="info">
            <tr><th>Danh mục</th><td><?= e($pet['category_name']) ?></td></tr>
            <tr><th>Loài</th><td><?= e($pet['species']) ?></td></tr>
            <tr><th>Giống</th><td><?= e($pet['breed'] ?? '—') ?></td></tr>
            <tr><th>Giới tính</th><td><?= $pet['gender'] ? e(status_label($pet['gender'])) : '—' ?></td></tr>
            <tr><th>Ngày sinh</th><td><?= $pet['birth_date'] ? e(date('d/m/Y', strtotime($pet['birth_date']))) : '—' ?></td></tr>
            <tr><th>Cân nặng</th><td><?= $pet['weight'] !== null ? e($pet['weight']) . ' kg' : '—' ?></td></tr>
            <tr><th>Sức khỏe</th><td><?= e($pet['health_status'] ?? '—') ?></td></tr>
        </table>
        <?php if ($pet['description']): ?>
            <h3>Mô tả</h3>
            <p><?= nl2br(e($pet['description'])) ?></p>
        <?php endif; ?>
        <?php /* TV4: đặt nút "Thêm vào giỏ" ở đây, chỉ khi Pet::isPurchasable($pet['id']) */ ?>
    </div>
</div>
