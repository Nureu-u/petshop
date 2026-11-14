<h1><?= e($title) ?></h1>
<form method="post" enctype="multipart/form-data" class="card form-grid"
      action="<?= e(url('admin/products/form', $product ? ['id' => $product['id']] : [])) ?>">
    <?= csrf_field() ?>
    <label>Tên *
        <input name="name" maxlength="150" value="<?= val($old, 'name') ?>" required>
        <?= field_error($errors, 'name') ?>
    </label>
    <label>Danh mục *
        <select name="category_id" required>
            <option value="">-- Chọn --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['id']) ?>"<?= sel($old['category_id'] ?? '', $c['id']) ?>>
                    <?= e($c['name']) ?><?= $c['status'] !== 'active' ? ' (đang ẩn)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'category_id') ?>
    </label>
    <label>Thương hiệu
        <input name="brand" maxlength="100" value="<?= val($old, 'brand') ?>">
        <?= field_error($errors, 'brand') ?>
    </label>
    <label>Giá (₫) *
        <input name="price" inputmode="numeric" value="<?= val($old, 'price') ?>" required>
        <?= field_error($errors, 'price') ?>
    </label>
    <label>Tồn kho *
        <input name="stock" type="number" min="0" step="1" value="<?= val($old, 'stock', '0') ?>" required>
        <?= field_error($errors, 'stock') ?>
    </label>
    <label>Trạng thái
        <select name="status">
            <?php foreach (Product::STATUSES as $s): ?>
                <option value="<?= e($s) ?>"<?= sel($old['status'] ?? 'active', $s) ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'status') ?>
    </label>
    <label class="full">Mô tả
        <textarea name="description" rows="5"><?= val($old, 'description') ?></textarea>
    </label>
    <label class="full">Ảnh (JPG/PNG/WEBP, tối đa 2MB)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?= field_error($errors, 'image') ?>
        <?php if (!empty($product['image'])): ?>
            <img class="thumb" src="<?= e(asset($product['image'])) ?>" alt=""> <small class="muted">Ảnh hiện tại (chọn file mới để thay)</small>
        <?php endif; ?>
    </label>
    <div class="full">
        <button class="btn" type="submit">Lưu</button>
        <a href="<?= e(url('admin/products')) ?>">Hủy</a>
    </div>
</form>
