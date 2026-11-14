<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: SAN PHAM
   File    : controllers/AdminProductController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/manage/products                 -> index() -> hien thi views/admin/catalog/products/index.php
     ?r=admin/manage/products/form            -> form() -> hien thi views/admin/catalog/products/form.php
     ?r=admin/manage/products/status          -> status()
   Dung cua TV3: Database, Product, ProductCategory, Uploader (chi goi, khong sua code cua TV3).
   ============================================================================== */
/** Quan ly san pham giao dien moi cua TV5. Dung lai Product, ProductCategory, Uploader cua TV3. */
final class AdminProductController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $flt = ['q' => input('q'), 'category_id' => input('category_id'), 'status' => input('status'), 'sort' => input('sort'),
                'brand' => input('brand'), 'stock' => input('stock')];
        $result = AdminCatalog::products($flt, (int) input('page', '1'), 12);
        $cats = ProductCategory::all();
        $brandList = AdminCatalog::brands();
        $counts = array_map('intval', Database::pdo()->query('SELECT status, COUNT(*) FROM products GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR));
        AdminView::render('catalog/products/index', compact('result', 'flt', 'cats', 'counts', 'brandList'), 'products', t('nav.products'));
    }

    public function form(): void
    {
        AdminAuthController::guard();
        $id = input('id');
        $product = null;
        if ($id !== '') {
            $product = Product::find($id);
            if (!$product) {
                abort(404, t('prod.msg.notfound'));
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
                    flash('success', t('prod.msg.updated'));
                } else {
                    Product::create($data, $img);
                    flash('success', t('prod.msg.created'));
                }
                redirect('admin/manage/products');
            }
            if ($img) {
                Uploader::delete($img);
            }
            $old = array_merge($product ?? [], $_POST);
        }

        $cats = ProductCategory::all();
        AdminView::render('catalog/products/form', compact('product', 'old', 'errors', 'cats'), 'products', $product ? t('prod.edit_title') : t('prod.new_title'));
    }

    public function status(): void
    {
        AdminAuthController::guard();
        require_post();
        $ok = Product::setStatus(input('id'), input('status'));
        flash($ok ? 'success' : 'error', t($ok ? 'pet.msg.status' : 'pet.msg.fail'));
        redirect('admin/manage/products');
    }
}
