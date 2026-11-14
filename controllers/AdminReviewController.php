<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: DANH GIA
   File    : controllers/AdminReviewController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/reviews                         -> index() -> hien thi views/admin/reviews/index.php
     ?r=admin/reviews/status                  -> status()
   Du lieu lay tu: models/AdminReview.php
   ============================================================================== */
final class AdminReviewController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $flt = ['type' => input('type'), 'rating' => input('rating'), 'status' => input('status')];
        $result = AdminReview::search($flt, (int) input('page', '1'), 10);
        $stats = AdminReview::stats();
        $images = AdminReview::imagesFor(array_column($result['items'], 'id'));
        AdminView::render('reviews/index', compact('result', 'flt', 'stats', 'images'), 'reviews', t('nav.reviews'));
    }

    public function status(): void
    {
        AdminAuthController::guard();
        require_post();
        $res = AdminReview::setStatus(input('id'), input('status'));
        flash($res['ok'] ? 'success' : 'error', t($res['msg']));
        $back = input('back');
        if ($back !== '' && $back[0] === '/' && !str_starts_with($back, '//')) {
            header('Location: ' . $back);
            exit;
        }
        redirect('admin/reviews');
    }
}
