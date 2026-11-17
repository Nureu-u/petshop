<h1><?= e($title) ?></h1>
<p>
    <a href="<?= e(url('admin/categories', ['type' => 'pet'])) ?>"<?= $type === 'pet' ? ' class="current"' : '' ?>>Danh mục thú cưng</a> ·
    <a href="<?= e(url('admin/categories', ['type' => 'product'])) ?>"<?= $type === 'product' ? ' class="current"' : '' ?>>Danh mục sản phẩm</a>
</p>

<div class="two-col">
    <section>
        <table class="table">
            <thead><tr><th>Tên</th><th>Slug</th><th>Số mục</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?><br><small class="muted"><?= e($c['id']) ?></small></td>
                    <td><?= e($c['slug']) ?></td>
                    <td><?= (int) $c['item_count'] ?></td>
                    <td><span class="badge <?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(url('admin/categories', ['type' => $type, 'id' => $c['id']])) ?>">Sửa</a>
                        <form method="post" action="<?= e(url('admin/categories/toggle', ['type' => $type])) ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                            <button class="link" type="submit"><?= $c['status'] === 'active' ? 'Ẩn' : 'Hiện' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="5" class="muted">Chưa có danh mục.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2><?= $editing && !empty($editing['id']) ? 'Sửa danh mục' : 'Thêm danh mục' ?></h2>
        <form method="post" action="<?= e(url('admin/categories/save', ['type' => $type])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
            <label>Tên *
                <input name="name" maxlength="100" value="<?= val($editing ?? [], 'name') ?>" required>
                <?= field_error($errors, 'name') ?>
            </label>
            <label>Slug (để trống sẽ tự tạo từ tên)
                <input name="slug" maxlength="120" value="<?= val($editing ?? [], 'slug') ?>">
                <?= field_error($errors, 'slug') ?>
            </label>
            <label>Mô tả
                <input name="description" maxlength="255" value="<?= val($editing ?? [], 'description') ?>">
                <?= field_error($errors, 'description') ?>
            </label>
            <label>Trạng thái
                <select name="status">
                    <option value="active"<?= sel($editing['status'] ?? 'active', 'active') ?>>Hiển thị</option>
                    <option value="inactive"<?= sel($editing['status'] ?? 'active', 'inactive') ?>>Ẩn</option>
                </select>
            </label>
            <button class="btn" type="submit">Lưu</button>
            <?php if ($editing && !empty($editing['id'])): ?>
                <a href="<?= e(url('admin/categories', ['type' => $type])) ?>">Hủy</a>
            <?php endif; ?>
        </form>
    </section>
</div>
