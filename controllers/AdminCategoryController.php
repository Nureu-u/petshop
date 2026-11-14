<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: DANH MUC THU CUNG VA SAN PHAM
   File    : controllers/AdminCategoryController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/manage/categories               -> index()
     ?r=admin/manage/categories/save          -> save()
     ?r=admin/manage/categories/toggle        -> toggle()
   Dung cua TV3: PetCategory, ProductCategory (chi goi, khong sua code cua TV3).
   ============================================================================== */
/** Danh muc thu cung (type=pet) va danh muc san pham (type=product), dung lai model cua TV3. */
final class AdminCategoryController
{
    private function type(): string
    {
        return input('type', 'pet') === 'product' ? 'product' : 'pet';
    }

    /** @return class-string<BaseCategory> */
    private function model(string $type): string
    {
        return $type === 'product' ? ProductCategory::class : PetCategory::class;
    }

    public function index(): void
    {
        AdminAuthController::guard();
        $this->render($this->type(), [], null);
    }

    public function save(): void
    {
        AdminAuthController::guard();
        require_post();
        $type = $this->type();
        $model = $this->model($type);
        $id = input('id');
        $existing = $id !== '' ? $model::find($id) : null;
        if ($id !== '' && !$existing) {
            abort(404, t('cats.msg.notfound'));
        }
        // Tu tao slug khong dau tu ten (ham slugify() cua TV3 lam roi chu co dau khi may khong bat intl)
        $in = $_POST;
        $in['slug'] = vn_slug(trim((string) ($in['slug'] ?? '')) ?: (string) ($in['name'] ?? ''));
        [$errors, $data] = $model::validate($in, $existing['id'] ?? null);
        if ($errors) {
            $this->render($type, $errors, array_merge($existing ?? [], $_POST));
            return;
        }
        if ($existing) {
            $model::update($existing['id'], $data);
            flash('success', t('cats.msg.updated'));
        } else {
            $model::create($data);
            flash('success', t('cats.msg.created'));
        }
        redirect('admin/manage/categories', ['type' => $type]);
    }

    /** Bat/tat hien thi. Khong xoa cung vi khoa ngoai RESTRICT. */
    public function toggle(): void
    {
        AdminAuthController::guard();
        require_post();
        $type = $this->type();
        $model = $this->model($type);
        $cat = $model::find(input('id'));
        if (!$cat) {
            abort(404, t('cats.msg.notfound'));
        }
        $model::setStatus($cat['id'], $cat['status'] === 'active' ? 'inactive' : 'active');
        flash('success', t('cats.msg.toggled'));
        redirect('admin/manage/categories', ['type' => $type]);
    }

    private function render(string $type, array $errors, ?array $editing): void
    {
        $model = $this->model($type);
        if ($editing === null && input('id') !== '') {
            $editing = $model::find(input('id'));
        }
        $items = $model::all();
        AdminView::render('catalog/categories/index', compact('type', 'items', 'editing', 'errors'), 'categories', t('nav.categories'));
    }
}
