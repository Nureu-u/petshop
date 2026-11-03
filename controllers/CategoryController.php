<?php
declare(strict_types=1);

/** Quản trị danh mục thú cưng (type=pet) và danh mục sản phẩm (type=product). */
final class CategoryController
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
        Auth::requireAdmin();
        $this->render($this->type(), [], null);
    }

    public function save(): void
    {
        Auth::requireAdmin();
        require_post();
        $type = $this->type();
        $model = $this->model($type);
        $id = input('id');

        $existing = $id !== '' ? $model::find($id) : null;
        if ($id !== '' && !$existing) {
            abort(404, 'Không tìm thấy danh mục.');
        }

        [$errors, $data] = $model::validate($_POST, $existing['id'] ?? null);
        if ($errors) {
            $this->render($type, $errors, array_merge($existing ?? [], $_POST));
            return;
        }
        if ($existing) {
            $model::update($existing['id'], $data);
            flash('success', 'Đã cập nhật danh mục.');
        } else {
            $model::create($data);
            flash('success', 'Đã thêm danh mục.');
        }
        redirect('admin/categories', ['type' => $type]);
    }

    /** Bật/tắt hiển thị. Không xóa cứng vì khóa ngoại ON DELETE RESTRICT. */
    public function toggle(): void
    {
        Auth::requireAdmin();
        require_post();
        $type = $this->type();
        $model = $this->model($type);
        $cat = $model::find(input('id'));
        if (!$cat) {
            abort(404, 'Không tìm thấy danh mục.');
        }
        $model::setStatus($cat['id'], $cat['status'] === 'active' ? 'inactive' : 'active');
        flash('success', 'Đã đổi trạng thái danh mục.');
        redirect('admin/categories', ['type' => $type]);
    }

    private function render(string $type, array $errors, ?array $editing): void
    {
        $model = $this->model($type);
        if ($editing === null && input('id') !== '') {
            $editing = $model::find(input('id'));
        }
        $items = $model::all();
        $title = $type === 'product' ? 'Danh mục sản phẩm' : 'Danh mục thú cưng';
        view('admin/categories/index', compact('type', 'items', 'editing', 'errors', 'title'));
    }
}
