<?php
/** Danh sách thú cưng. Biến từ PetController::index: $result, $filters, $categories, $speciesList */
$sort = $filters['sort'] !== '' ? $filters['sort'] : 'newest';
$sorts = ['newest' => 'Mới nhất', 'price_asc' => 'Giá tăng', 'price_desc' => 'Giá giảm', 'name' => 'Tên A-Z'];
$catNames = array_column($categories, 'name', 'id');

// Các bộ lọc đang bật (hiện thành chip, bấm để bỏ)
$chips = [];
if ($filters['q'] !== '') { $chips[] = ['label' => 'Từ khóa: ' . $filters['q'], 'remove' => ['q' => '']]; }
if ($filters['category_id'] !== '') { $chips[] = ['label' => $catNames[$filters['category_id']] ?? 'Danh mục', 'remove' => ['category_id' => '']]; }
if ($filters['species'] !== '') { $chips[] = ['label' => 'Loài: ' . $filters['species'], 'remove' => ['species' => '']]; }
if (in_array($filters['gender'], Pet::GENDERS, true)) { $chips[] = ['label' => status_label($filters['gender']), 'remove' => ['gender' => '']]; }
if ($filters['min_price'] !== '') { $v = parse_money($filters['min_price']); $chips[] = ['label' => 'Từ ' . ($v !== null ? format_price($v) : $filters['min_price']), 'remove' => ['min_price' => '']]; }
if ($filters['max_price'] !== '') { $v = parse_money($filters['max_price']); $chips[] = ['label' => 'Đến ' . ($v !== null ? format_price($v) : $filters['max_price']), 'remove' => ['max_price' => '']]; }
?>
<h1>Thú cưng</h1>

<div class="shop-layout">
    <aside class="card filter-box">
        <form method="get" action="<?= e(config('base_url')) ?>/index.php">
            <input type="hidden" name="r" value="pets">
            <input type="hidden" name="category_id" value="<?= e($filters['category_id']) ?>">
            <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            <h3>Bộ lọc</h3>
            <label>Từ khóa
                <input type="search" name="q" placeholder="Tên, loài, giống..." value="<?= e($filters['q']) ?>">
            </label>
            <label>Loài
                <select name="species">
                    <option value="">Tất cả</option>
                    <?php foreach ($speciesList as $s): ?>
                        <option value="<?= e($s) ?>"<?= sel($filters['species'], $s) ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Giới tính
                <select name="gender">
                    <option value="">Tất cả</option>
                    <option value="male"<?= sel($filters['gender'], 'male') ?>>Đực</option>
                    <option value="female"<?= sel($filters['gender'], 'female') ?>>Cái</option>
                </select>
            </label>
            <label>Khoảng giá (₫)
                <div class="form-row">
                    <input name="min_price" type="text" inputmode="numeric" placeholder="Từ" value="<?= e($filters['min_price']) ?>">
                    <input name="max_price" type="text" inputmode="numeric" placeholder="Đến" value="<?= e($filters['max_price']) ?>">
                </div>
            </label>
            <div class="filter-actions">
                <button class="btn" type="submit">Áp dụng</button>
                <a class="btn outline" href="<?= e(url('pets')) ?>">Xóa lọc</a>
            </div>
        </form>
    </aside>

    <section>
        <div class="pills" aria-label="Phân loại">
            <a class="pill<?= $filters['category_id'] === '' ? ' on' : '' ?>" href="<?= e(current_url_with(['category_id' => '', 'page' => 1])) ?>">Tất cả</a>
            <?php foreach ($categories as $c): ?>
                <a class="pill<?= $filters['category_id'] === $c['id'] ? ' on' : '' ?>" href="<?= e(current_url_with(['category_id' => $c['id'], 'page' => 1])) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php require BASE_PATH . '/views/partials/chips.php'; ?>

        <div class="toolbar">
            <span class="muted"><?= (int) $result['total'] ?> kết quả</span>
            <div class="sort-links">
                <span class="muted">Sắp xếp:</span>
                <?php foreach ($sorts as $key => $label): ?>
                    <a class="pill<?= $sort === $key ? ' on' : '' ?>" href="<?= e(current_url_with(['sort' => $key, 'page' => 1])) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$result['items']): ?>
            <div class="card empty">
                <?= ui_icon('paw', 56, '1.4') ?>
                <h3>Không tìm thấy thú cưng phù hợp</h3>
                <p class="muted">Thử đổi từ khóa hoặc bỏ bớt bộ lọc.</p>
                <a class="btn" href="<?= e(url('pets')) ?>">Xóa bộ lọc</a>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($result['items'] as $p): require BASE_PATH . '/views/partials/pet_card.php'; endforeach; ?>
            </div>
            <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
        <?php endif; ?>
    </section>
</div>
