<?php
declare(strict_types=1);

final class PetController
{
    private function filters(): array
    {
        $f = [];
        foreach (['q', 'category_id', 'species', 'gender', 'min_price', 'max_price', 'sort', 'status'] as $k) {
            $f[$k] = input($k);
        }
        return $f;
    }

    /* ---------- Công khai ---------- */

    public function index(): void
    {
        $filters = $this->filters();
        $result = Pet::search($filters, (int) input('page', '1'));
        $categories = PetCategory::all(true);
        $speciesList = Pet::species();
        $title = 'Thú cưng';
        view('pets/index', compact('result', 'filters', 'categories', 'speciesList', 'title'));
    }

    public function show(): void
    {
        $pet = Pet::find(input('id'));
        $visible = $pet && $pet['status'] !== 'hidden' && $pet['category_status'] === 'active';
        if (!$visible && !Auth::isAdmin()) {
            abort(404, 'Không tìm thấy thú cưng này.');
        }
        if (!$pet) {
            abort(404, 'Không tìm thấy thú cưng này.');
        }
        $title = $pet['name'];
        view('pets/show', compact('pet', 'title'));
    }

    /* ---------- Quản trị ---------- */

    public function adminIndex(): void
    {
        Auth::requireAdmin();
        $filters = $this->filters();
        $result = Pet::search($filters, (int) input('page', '1'), 15, false);
        $categories = PetCategory::all();
        $title = 'Quản lý thú cưng';
        view('admin/pets/index', compact('result', 'filters', 'categories', 'title'));
    }

    /** Dùng cho cả thêm mới (không có id) và chỉnh sửa (?id=...). */
    public function form(): void
    {
        Auth::requireAdmin();
        $id = input('id');
        $pet = null;
        if ($id !== '') {
            $pet = Pet::find($id);
            if (!$pet) {
                abort(404, 'Không tìm thấy thú cưng.');
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
                    flash('success', 'Đã cập nhật thú cưng.');
                } else {
                    Pet::create($data, $img);
                    flash('success', 'Đã thêm thú cưng.');
                }
                redirect('admin/pets');
            }
            if ($img) {
                Uploader::delete($img); // form lỗi thì bỏ ảnh vừa tải lên
            }
            $old = array_merge($pet ?? [], $_POST);
        }

        $categories = PetCategory::all();
        $title = $pet ? 'Sửa thú cưng' : 'Thêm thú cưng';
        view('admin/pets/form', compact('pet', 'old', 'errors', 'categories', 'title'));
    }

    /** Ẩn / đánh dấu đã bán / hiển thị lại (không xóa cứng để giữ dữ liệu đơn hàng). */
    public function setStatus(): void
    {
        Auth::requireAdmin();
        require_post();
        $ok = Pet::setStatus(input('id'), input('status'));
        flash($ok ? 'success' : 'error', $ok ? 'Đã cập nhật trạng thái.' : 'Không cập nhật được trạng thái.');
        redirect('admin/pets');
    }
}
