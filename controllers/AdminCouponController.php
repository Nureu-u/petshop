<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: MA GIAM GIA
   File    : controllers/AdminCouponController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/coupons                         -> index() -> hien thi views/admin/coupons/index.php
     ?r=admin/coupons/form                    -> form() -> hien thi views/admin/coupons/form.php
     ?r=admin/coupons/toggle                  -> toggle()
   Du lieu lay tu: models/AdminCoupon.php
   ============================================================================== */
final class AdminCouponController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $items = AdminCoupon::all();
        AdminView::render('coupons/index', compact('items'), 'coupons', t('nav.coupons'));
    }

    /** Tao moi (khong co id) hoac sua (?id=...). */
    public function form(): void
    {
        AdminAuthController::guard();
        $id = input('id');
        $coupon = null;
        if ($id !== '') {
            $coupon = AdminCoupon::find($id);
            if (!$coupon) {
                abort(404, t('coupon.msg.notfound'));
            }
        }
        $errors = [];
        $old = $coupon
            ? array_merge($coupon, [
                'start_at' => AdminCoupon::toInput($coupon['start_at']),
                'end_at'   => AdminCoupon::toInput($coupon['end_at']),
            ])
            : ['discount_type' => 'percent', 'status' => 'active'];

        if (is_post()) {
            csrf_verify();
            [$errors, $data] = AdminCoupon::validate($_POST, $coupon);
            if (!$errors) {
                try {
                    if ($coupon) {
                        AdminCoupon::update($coupon['id'], $data);
                        flash('success', t('coupon.msg.updated'));
                    } else {
                        AdminCoupon::create($data);
                        flash('success', t('coupon.msg.created'));
                    }
                    redirect('admin/coupons');
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $errors['code'] = t('coupon.err.code_dup');
                    } else {
                        throw $e;
                    }
                }
            }
            $old = array_merge($coupon ?? [], $_POST);
        }

        $title = $coupon ? t('coupon.edit_title') : t('coupon.new_title');
        $usages = $coupon ? AdminCoupon::usages($coupon['id']) : [];
        $locked = AdminCoupon::isLocked($coupon);
        AdminView::render('coupons/form', compact('coupon', 'old', 'errors', 'usages', 'locked'), 'coupons', $title);
    }

    public function toggle(): void
    {
        AdminAuthController::guard();
        require_post();
        $ok = AdminCoupon::toggle(input('id'));
        flash($ok ? 'success' : 'error', t($ok ? 'coupon.msg.toggled' : 'coupon.msg.notfound'));
        redirect('admin/coupons');
    }
}
