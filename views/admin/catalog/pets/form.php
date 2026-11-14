<?php
/* ==============================================================================
   VIEW: THEM / SUA THU CUNG
   File    : views/admin/catalog/pets/form.php
   Dia chi  : ?r=admin/manage/pets/form
   Controller: controllers/AdminPetController.php  (ham form)
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU; [14] CHI TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 2 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var ?array $pet @var array $old @var array $errors @var array $cats */
$err = fn(string $k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <a class="lk" href="<?= e(url('admin/manage/pets')) ?>">‹ <?= e(t('nav.pets')) ?></a>
        <h1><?= e($pet ? t('pet.edit_title') : t('pet.new_title')) ?></h1>
        <div class="sub"><?= e(t('pet.form_sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Form nhap lieu ----- */ ?>
<form class="c fm" method="post" enctype="multipart/form-data" style="max-width:900px"
      action="<?= e(url('admin/manage/pets/form', $pet ? ['id' => $pet['id']] : [])) ?>">
    <?= csrf_field() ?>
    <label><?= e(t('pet.f.name')) ?>
        <input name="name" maxlength="100" value="<?= val($old, 'name') ?>" required><?= $err('name') ?>
    </label>
    <div class="gr2">
        <label><?= e(t('pet.f.category')) ?>
            <select name="category_id" required>
                <option value=""><?= e(t('pet.f.choose')) ?></option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= e($c['id']) ?>"<?= sel($old['category_id'] ?? '', $c['id']) ?>><?= e($c['name']) ?><?= $c['status'] !== 'active' ? ' ' . e(t('pet.cat_inactive')) : '' ?></option>
                <?php endforeach; ?>
            </select><?= $err('category_id') ?>
        </label>
        <label><?= e(t('pet.f.price')) ?>
            <input name="price" inputmode="numeric" data-money placeholder="<?= e(t('ph.example', ['v' => '7.000.000'])) ?>" value="<?= val($old, 'price') ?>" required><?= $err('price') ?>
        </label>
        <label><?= e(t('pet.f.species')) ?>
            <input name="species" maxlength="50" value="<?= val($old, 'species') ?>" required><?= $err('species') ?>
        </label>
        <label><?= e(t('pet.f.breed')) ?>
            <input name="breed" maxlength="100" value="<?= val($old, 'breed') ?>"><?= $err('breed') ?>
        </label>
        <label><?= e(t('pet.f.gender')) ?>
            <select name="gender">
                <option value=""><?= e(t('pet.f.none')) ?></option>
                <option value="male"<?= sel($old['gender'] ?? '', 'male') ?>><?= e(t('pet.gender.male')) ?></option>
                <option value="female"<?= sel($old['gender'] ?? '', 'female') ?>><?= e(t('pet.gender.female')) ?></option>
            </select><?= $err('gender') ?>
        </label>
        <label><?= e(t('pet.f.birth')) ?>
            <input type="date" name="birth_date" max="<?= date('Y-m-d') ?>" value="<?= val($old, 'birth_date') ?>"><?= $err('birth_date') ?>
        </label>
        <label><?= e(t('pet.f.weight')) ?>
            <input name="weight" inputmode="decimal" value="<?= val($old, 'weight') ?>"><?= $err('weight') ?>
        </label>
        <label><?= e(t('pet.f.health')) ?>
            <input name="health_status" maxlength="100" value="<?= val($old, 'health_status') ?>"><?= $err('health_status') ?>
        </label>
        <label><?= e(t('pet.f.status')) ?>
            <select name="status">
                <?php foreach (Pet::STATUSES as $s): ?>
                    <option value="<?= e($s) ?>"<?= sel($old['status'] ?? 'available', $s) ?>><?= e(t('pet.status.' . $s)) ?></option>
                <?php endforeach; ?>
            </select><?= $err('status') ?>
        </label>
        <label><?= e(t('pet.f.image')) ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="hint"><?= e(t('pet.f.image_hint')) ?></span><?= $err('image') ?>
            <?php if (!empty($pet['image'])): ?>
                <span class="hint"><img class="thumb" src="<?= e(asset($pet['image'])) ?>" alt=""> <?= e(t('pet.f.image_current')) ?></span>
            <?php endif; ?>
        </label>
    </div>
    <label><?= e(t('pet.f.desc')) ?>
        <textarea name="description" rows="5"><?= val($old, 'description') ?></textarea>
    </label>
    <div class="rr">
        <button class="b1" type="submit"><?= e(t('pet.save')) ?></button>
        <a class="b2" href="<?= e(url('admin/manage/pets')) ?>"><?= e(t('common.cancel')) ?></a>
    </div>
</form>
