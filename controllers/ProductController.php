<?php
declare(strict_types=1);

final class ProductController
{
    private function filters(): array
    {
        $f = [];
        foreach (['q', 'category_id', 'brand', 'min_price', 'max_price', 'sort', 'status', 'in_stock'] as $k) {
            $f[$k] = input($k);
        }
        return $f;
    }

    /* ---------- Công khai ---------- */

    public function index(): void
    {
        $filters = $this->filters();
        $result = Product::search($filters, (int) input('page', '1'));
        $categories = ProductCategory::all(true);
        $brands = Product::brands();
        $title = 'Sản phẩm';
        view('products/index', compact('result', 'filters', 'categories', 'brands', 'title'));
    }

    public function show(): void
    {
        $product = Product::find(input('id'));
        if (!$product) {
            abort(404, 'Không tìm thấy sản phẩm này.');
        }
        $visible = $product['status'] === 'active' && $product['category_status'] === 'active';
        if (!$visible && !Auth::isAdmin()) {
            abort(404, 'Không tìm thấy sản phẩm này.');
        }
        $title = $product['name'];
        view('products/show', compact('product', 'title'));
    }

    /* ---------- Quản trị ---------- */

    public function adminIndex(): void
    {
        Auth::requireAdmin();
        $filters = $this->filters();
        $result = Product::search($filters, (int) input('page', '1'), 15, false);
        $categories = ProductCategory::all();
        $title = 'Quản lý sản phẩm';
        view('admin/products/index', compact('result', 'filters', 'categories', 'title'));
    }

    public function form(): void
    {
        Auth::requireAdmin();
        $id = input('id');
        $product = null;
        if ($id !== '') {
            $product = Product::find($id);
            if (!$product) {
                abort(404, 'Không tìm thấy sản phẩm.');
            }
        }

        $errors = [];
        $old = $product ?? ['status' => 'active', 'stock' => 0];

        if (is_post()) {
            csrf_verify();
            [$errors, $data] = Product::validate($_POST);
            [$img, $imgErr] = Uploader::image($_FILES['image'] ?? null, 'products');
            if ($imgErr) {
                $errors['image'] = $imgErr;
            }

            if (!$errors) {
                if ($product) {
                    Product::update($product['id'], $data, $img);
                    if ($img) {
                        Uploader::delete($product['image']);
                    }
                    flash('success', 'Đã cập nhật sản phẩm.');
                } else {
                    Product::create($data, $img);
                    flash('success', 'Đã thêm sản phẩm.');
                }
                redirect('admin/products');
            }
            if ($img) {
                Uploader::delete($img);
            }
            $old = array_merge($product ?? [], $_POST);
        }

        $categories = ProductCategory::all();
        $title = $product ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
        view('admin/products/form', compact('product', 'old', 'errors', 'categories', 'title'));
    }

    public function setStatus(): void
    {
        Auth::requireAdmin();
        require_post();
        $ok = Product::setStatus(input('id'), input('status'));
        flash($ok ? 'success' : 'error', $ok ? 'Đã cập nhật trạng thái.' : 'Không cập nhật được trạng thái.');
        redirect('admin/products');
    }
}
