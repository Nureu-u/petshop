<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: NGUOI DUNG
   File    : controllers/AdminUserController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/users                           -> index() -> hien thi views/admin/users/index.php
     ?r=admin/users/show                      -> show() -> hien thi views/admin/users/show.php
     ?r=admin/users/status                    -> status()
   Du lieu lay tu: models/AdminUser.php
   Dung cua TV3: Auth (chi goi, khong sua code cua TV3).
   ============================================================================== */
final class AdminUserController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $flt = ['q' => input('q'), 'role' => input('role'), 'status' => input('status')];
        $result = AdminUser::search($flt, (int) input('page', '1'), 10);
        AdminView::render('users/index', compact('result', 'flt'), 'users', t('nav.users'));
    }

    public function show(): void
    {
        AdminAuthController::guard();
        $account = AdminUser::find(input('id'));
        if (!$account) {
            abort(404, t('user.msg.notfound'));
        }
        $orders = AdminUser::orders($account['id']);
        AdminView::render('users/show', compact('account', 'orders'), 'users', $account['full_name']);
    }

    public function status(): void
    {
        AdminAuthController::guard();
        require_post();
        $id = input('id');
        $res = AdminUser::setStatus($id, input('status'), Auth::id());
        flash($res['ok'] ? 'success' : 'error', t($res['msg']));
        $back = input('back');
        redirect($back === 'show' ? 'admin/users/show' : 'admin/users', $back === 'show' ? ['id' => $id] : []);
    }
}
