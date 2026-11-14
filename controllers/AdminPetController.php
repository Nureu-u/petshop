<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: THU CUNG
   File    : controllers/AdminPetController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/manage/pets                     -> index() -> hien thi views/admin/catalog/pets/index.php
     ?r=admin/manage/pets/form                -> form() -> hien thi views/admin/catalog/pets/form.php
     ?r=admin/manage/pets/status              -> status()
   Dung cua TV3: Database, Pet, PetCategory, Uploader (chi goi, khong sua code cua TV3).
   ============================================================================== */
/** Quan ly thu cung giao dien moi cua TV5. Dung lai Pet, PetCategory, Uploader cua TV3 (khong sua file TV3). */
final class AdminPetController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $flt = ['q' => input('q'), 'category_id' => input('category_id'), 'status' => input('status'), 'sort' => input('sort'), 'species' => input('species')];
        $result = AdminCatalog::pets($flt, (int) input('page', '1'), 12);
        $cats = PetCategory::all();
        $speciesList = AdminCatalog::species();
        $counts = array_map('intval', Database::pdo()->query('SELECT status, COUNT(*) FROM pets GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR));
        AdminView::render('catalog/pets/index', compact('result', 'flt', 'cats', 'counts', 'speciesList'), 'pets', t('nav.pets'));
    }

    /** Them moi (khong co id) hoac sua (?id=...). */
    public function form(): void
    {
        AdminAuthController::guard();
        $id = input('id');
        $pet = null;
        if ($id !== '') {
            $pet = Pet::find($id);
            if (!$pet) {
                abort(404, t('pet.msg.notfound'));
            }
        }
        $errors = [];
        $old = $pet ?? ['status' => 'available'];

        if (is_post()) {
            csrf_verify();
            [$errors, $data] = Pet::validate($_POST);
            [$img, $imgErr] = Uploader::image($_FILES['image'] ?? null, 'pets');
            if ($imgErr) {
                $errors['image'] = $imgErr;
            }
            if (!$errors) {
                if ($pet) {
                    Pet::update($pet['id'], $data, $img);
                    if ($img) {
                        Uploader::delete($pet['image']);
                    }
                    flash('success', t('pet.msg.updated'));
                } else {
                    Pet::create($data, $img);
                    flash('success', t('pet.msg.created'));
                }
                redirect('admin/manage/pets');
            }
            if ($img) {
                Uploader::delete($img);
            }
            $old = array_merge($pet ?? [], $_POST);
        }

        $cats = PetCategory::all();
        AdminView::render('catalog/pets/form', compact('pet', 'old', 'errors', 'cats'), 'pets', $pet ? t('pet.edit_title') : t('pet.new_title'));
    }

    /** Con ban / da ban / an (khong xoa cung de giu du lieu don hang). */
    public function status(): void
    {
        AdminAuthController::guard();
        require_post();
        $ok = Pet::setStatus(input('id'), input('status'));
        flash($ok ? 'success' : 'error', t($ok ? 'pet.msg.status' : 'pet.msg.fail'));
        $back = input('back');
        if ($back !== '' && $back[0] === '/' && !str_starts_with($back, '//')) {
            header('Location: ' . $back);
            exit;
        }
        redirect('admin/manage/pets');
    }
}
