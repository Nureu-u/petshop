<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: TONG QUAN
   File    : controllers/AdminDashboardController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/dashboard                       -> index() -> hien thi views/admin/dashboard/index.php
   Du lieu lay tu: models/AdminDashboard.php
   ============================================================================== */
final class AdminDashboardController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $r = AdminDashboard::range(input('p', '7d'));
        $kind = input('best') === 'pet' ? 'pet' : 'product';
        AdminView::render('dashboard/index', AdminDashboard::overview($r, $kind), 'dashboard');
    }
}
