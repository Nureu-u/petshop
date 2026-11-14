<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: TIM KIEM CHUNG
   File    : controllers/AdminSearchController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/search                          -> index() -> hien thi views/admin/search/index.php
   Du lieu lay tu: models/AdminSearch.php
   ============================================================================== */
final class AdminSearchController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $q = trim(input('q'));
        $res = mb_strlen($q) >= 2 ? AdminSearch::run($q) : null;
        AdminView::render('search/index', compact('q', 'res'), 'search', t('search.title'));
    }
}
