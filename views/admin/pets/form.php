<h1><?= e($title) ?></h1>
<form method="post" enctype="multipart/form-data" class="card form-grid"
      action="<?= e(url('admin/pets/form', $pet ? ['id' => $pet['id']] : [])) ?>">
    <?= csrf_field() ?>
    <label>Tên *
        <input name="name" maxlength="100" value="<?= val($old, 'name') ?>" required>
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
    <label>Loài *
        <input name="species" maxlength="50" value="<?= val($old, 'species') ?>" required>
        <?= field_error($errors, 'species') ?>
    </label>
    <label>Giống
        <input name="breed" maxlength="100" value="<?= val($old, 'breed') ?>">
        <?= field_error($errors, 'breed') ?>
    </label>
    <label>Giới tính
        <select name="gender">
            <option value="">—</option>
            <option value="male"<?= sel($old['gender'] ?? '', 'male') ?>>Đực</option>
            <option value="female"<?= sel($old['gender'] ?? '', 'female') ?>>Cái</option>
        </select>
        <?= field_error($errors, 'gender') ?>
    </label>
    <label>Ngày sinh
        <input type="date" name="birth_date" max="<?= date('Y-m-d') ?>" value="<?= val($old, 'birth_date') ?>">
        <?= field_error($errors, 'birth_date') ?>
    </label>
    <label>Cân nặng (kg)
        <input name="weight" inputmode="decimal" value="<?= val($old, 'weight') ?>">
        <?= field_error($errors, 'weight') ?>
    </label>
    <label>Giá (₫) *
        <input name="price" inputmode="numeric" value="<?= val($old, 'price') ?>" required>
        <?= field_error($errors, 'price') ?>
    </label>
    <label>Tình trạng sức khỏe
        <input name="health_status" maxlength="100" value="<?= val($old, 'health_status') ?>">
        <?= field_error($errors, 'health_status') ?>
    </label>
    <label>Trạng thái
        <select name="status">
            <?php foreach (Pet::STATUSES as $s): ?>
                <option value="<?= e($s) ?>"<?= sel($old['status'] ?? 'available', $s) ?>><?= e(status_label($s)) ?></option>
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
        <?php if (!empty($pet['image'])): ?>
            <img class="thumb" src="<?= e(asset($pet['image'])) ?>" alt=""> <small class="muted">Ảnh hiện tại (chọn file mới để thay)</small>
        <?php endif; ?>
    </label>
    <div class="full">
        <button class="btn" type="submit">Lưu</button>
        <a href="<?= e(url('admin/pets')) ?>">Hủy</a>
    </div>
</form>
