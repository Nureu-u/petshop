<h1>Thú cưng</h1>

<form method="get" action="<?= e(config('base_url')) ?>/index.php" class="filters">
    <input type="hidden" name="r" value="pets">
    <input name="q" placeholder="Tìm theo tên, loài, giống..." value="<?= e($filters['q']) ?>">
    <select name="category_id">
        <option value="">Tất cả danh mục</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['id']) ?>"<?= sel($filters['category_id'], $c['id']) ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="species">
        <option value="">Mọi loài</option>
        <?php foreach ($speciesList as $s): ?>
            <option value="<?= e($s) ?>"<?= sel($filters['species'], $s) ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="gender">
        <option value="">Giới tính</option>
        <option value="male"<?= sel($filters['gender'], 'male') ?>>Đực</option>
        <option value="female"<?= sel($filters['gender'], 'female') ?>>Cái</option>
    </select>
    <input name="min_price" type="text" inputmode="numeric" placeholder="Giá từ" value="<?= e($filters['min_price']) ?>">
    <input name="max_price" type="text" inputmode="numeric" placeholder="Giá đến" value="<?= e($filters['max_price']) ?>">
    <select name="sort">
        <option value="newest"<?= sel($filters['sort'], 'newest') ?>>Mới nhất</option>
        <option value="price_asc"<?= sel($filters['sort'], 'price_asc') ?>>Giá tăng dần</option>
        <option value="price_desc"<?= sel($filters['sort'], 'price_desc') ?>>Giá giảm dần</option>
        <option value="name"<?= sel($filters['sort'], 'name') ?>>Tên A-Z</option>
    </select>
    <button class="btn" type="submit">Lọc</button>
</form>

<?php if (!$result['items']): ?>
    <p class="muted">Không tìm thấy thú cưng phù hợp.</p>
<?php else: ?>
    <p class="muted"><?= (int) $result['total'] ?> kết quả</p>
    <div class="grid">
        <?php foreach ($result['items'] as $p): ?>
            <a class="item" href="<?= e(url('pets/show', ['id' => $p['id']])) ?>">
                <?php if ($p['image']): ?>
                    <img src="<?= e(asset($p['image'])) ?>" alt="<?= e($p['name']) ?>">
                <?php else: ?>
                    <div class="noimg">Chưa có ảnh</div>
                <?php endif; ?>
                <h3><?= e($p['name']) ?></h3>
                <p class="muted"><?= e($p['species']) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?></p>
                <p class="price"><?= e(format_price($p['price'])) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
<?php endif; ?>
