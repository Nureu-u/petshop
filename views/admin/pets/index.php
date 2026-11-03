<div class="head">
    <h1>Quản lý thú cưng</h1>
    <a class="btn" href="<?= e(url('admin/pets/form')) ?>">+ Thêm thú cưng</a>
</div>

<form method="get" action="<?= e(config('base_url')) ?>/index.php" class="filters">
    <input type="hidden" name="r" value="admin/pets">
    <input name="q" placeholder="Tìm kiếm..." value="<?= e($filters['q']) ?>">
    <select name="category_id">
        <option value="">Tất cả danh mục</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['id']) ?>"<?= sel($filters['category_id'], $c['id']) ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">Mọi trạng thái</option>
        <?php foreach (Pet::STATUSES as $s): ?>
            <option value="<?= e($s) ?>"<?= sel($filters['status'], $s) ?>><?= e(status_label($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Lọc</button>
</form>

<table class="table">
    <thead><tr><th>Ảnh</th><th>Tên</th><th>Danh mục</th><th>Loài / Giống</th><th>Giá</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($result['items'] as $p): ?>
        <tr>
            <td><?php if ($p['image']): ?><img class="thumb" src="<?= e(asset($p['image'])) ?>" alt=""><?php endif; ?></td>
            <td><a href="<?= e(url('pets/show', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a><br><small class="muted"><?= e($p['id']) ?></small></td>
            <td><?= e($p['category_name']) ?></td>
            <td><?= e($p['species']) ?><?= $p['breed'] ? ' / ' . e($p['breed']) : '' ?></td>
            <td><?= e(format_price($p['price'])) ?></td>
            <td><span class="badge <?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
            <td class="actions">
                <a href="<?= e(url('admin/pets/form', ['id' => $p['id']])) ?>">Sửa</a>
                <?php foreach (Pet::STATUSES as $s): if ($s === $p['status']) continue; ?>
                    <form method="post" action="<?= e(url('admin/pets/status')) ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                        <input type="hidden" name="status" value="<?= e($s) ?>">
                        <button class="link" type="submit"><?= e(status_label($s)) ?></button>
                    </form>
                <?php endforeach; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="7" class="muted">Không có dữ liệu.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php require BASE_PATH . '/views/partials/pagination.php'; ?>
