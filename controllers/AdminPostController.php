<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: BAI VIET VA DANH MUC BAI VIET
   File    : controllers/AdminPostController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/posts                           -> index() -> hien thi views/admin/posts/index.php
     ?r=admin/posts/form                      -> form() -> hien thi views/admin/posts/form.php
     ?r=admin/posts/status                    -> status()
     ?r=admin/posts/categories                -> categories()
     ?r=admin/posts/categories/save           -> categorySave()
     ?r=admin/posts/categories/delete         -> categoryDelete()
   Du lieu lay tu: models/AdminPost.php
   Dung cua TV3: Auth, Uploader (chi goi, khong sua code cua TV3).
   ============================================================================== */
final class AdminPostController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $flt = ['q' => input('q'), 'status' => input('status'), 'category' => input('category')];
        $result = AdminPost::search($flt, (int) input('page', '1'), 10);
        $cats = AdminPost::categories();
        AdminView::render('posts/index', compact('result', 'flt', 'cats'), 'posts', t('nav.posts'));
    }

    /** Viet moi (khong co id) hoac sua (?id=...). */
    public function form(): void
    {
        AdminAuthController::guard();
        $id = input('id');
        $post = null;
        if ($id !== '') {
            $post = AdminPost::find($id);
            if (!$post) {
                abort(404, t('post.msg.notfound'));
            }
        }
        $errors = [];
        $old = $post ?? ['status' => 'draft'];

        if (is_post()) {
            csrf_verify();
            [$errors, $data] = AdminPost::validate($_POST, $post['id'] ?? null);
            [$img, $imgErr] = Uploader::image($_FILES['image'] ?? null, 'posts');
            if ($imgErr) {
                $errors['image'] = $imgErr;
            }
            if (!$errors) {
                if ($post) {
                    AdminPost::update($post['id'], $data, $img);
                    if ($img) {
                        Uploader::delete($post['image']);
                    }
                    flash('success', t('post.msg.updated'));
                } else {
                    AdminPost::create($data, $img, (string) Auth::id());
                    flash('success', t('post.msg.created'));
                }
                redirect('admin/posts');
            }
            if ($img) {
                Uploader::delete($img);
            }
            $old = array_merge($post ?? [], $_POST);
        }

        $cats = AdminPost::categories();
        $title = $post ? t('post.edit_title') : t('post.new_title');
        AdminView::render('posts/form', compact('post', 'old', 'errors', 'cats'), 'posts', $title);
    }

    public function status(): void
    {
        AdminAuthController::guard();
        require_post();
        $ok = AdminPost::setStatus(input('id'), input('status'));
        flash($ok ? 'success' : 'error', t($ok ? 'post.msg.status' : 'post.msg.notfound'));
        redirect('admin/posts');
    }

    public function categories(): void
    {
        AdminAuthController::guard();
        $editing = input('id') !== '' ? AdminPost::categoryFind(input('id')) : null;
        $this->renderCategories([], $editing ?? []);
    }

    public function categorySave(): void
    {
        AdminAuthController::guard();
        require_post();
        $id = input('id');
        $existing = $id !== '' ? AdminPost::categoryFind($id) : null;
        if ($id !== '' && !$existing) {
            abort(404, t('pcat.msg.notfound'));
        }
        [$errors, $data] = AdminPost::validateCategory($_POST, $existing['id'] ?? null);
        if ($errors) {
            $this->renderCategories($errors, array_merge($existing ?? [], $_POST));
            return;
        }
        if ($existing) {
            AdminPost::categoryUpdate($existing['id'], $data);
            flash('success', t('pcat.msg.updated'));
        } else {
            AdminPost::categoryCreate($data);
            flash('success', t('pcat.msg.created'));
        }
        redirect('admin/posts/categories');
    }

    public function categoryDelete(): void
    {
        AdminAuthController::guard();
        require_post();
        $key = AdminPost::categoryDelete(input('id'));
        flash($key === 'pcat.msg.deleted' ? 'success' : 'error', t($key));
        redirect('admin/posts/categories');
    }

    private function renderCategories(array $errors, array $old): void
    {
        $items = AdminPost::categories();
        AdminView::render('posts/categories', compact('items', 'errors', 'old'), 'posts', t('post.tab.cats'));
    }
}
