<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: DOI NGON NGU VI / EN
   File    : controllers/AdminCommonController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/lang                            -> lang()
   ============================================================================== */
/** Viec chung cua khu quan tri TV5: doi ngon ngu. */
final class AdminCommonController
{
    public function lang(): void
    {
        AdminAuthController::guard();
        $_SESSION['admin_lang'] = input('l') === 'en' ? 'en' : 'vi';

        // Chi cho quay lai duong dan noi bo cua site
        $back = input('back');
        if ($back !== '' && $back[0] === '/' && !str_starts_with($back, '//')) {
            header('Location: ' . $back);
            exit;
        }
        redirect('admin/dashboard');
    }
}
