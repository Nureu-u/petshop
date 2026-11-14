<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: DON HANG
   File    : controllers/AdminOrderController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/orders                          -> index() -> hien thi views/admin/orders/index.php
     ?r=admin/orders/show                     -> show() -> hien thi views/admin/orders/show.php
     ?r=admin/orders/status                   -> status()
     ?r=admin/orders/export                   -> export()
     ?r=admin/orders/invoice                  -> invoice() -> hien thi views/admin/orders/print.php
   Du lieu lay tu: models/AdminOrder.php
   ============================================================================== */
final class AdminOrderController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $flt = [
            'status' => input('status'),
            'q'      => input('q'),
            'pay'    => input('pay'),
            'from'   => input('from'),
            'to'     => input('to'),
        ];
        $result = AdminOrder::search($flt, (int) input('page', '1'), 10);
        $counts = AdminOrder::statusCounts();
        AdminView::render('orders/index', compact('result', 'flt', 'counts'), 'orders', t('nav.orders'));
    }

    public function show(): void
    {
        AdminAuthController::guard();
        $order = AdminOrder::find(input('id'));
        if (!$order) {
            abort(404, t('order.msg.notfound'));
        }
        $items = AdminOrder::items($order['id']);
        $payments = AdminOrder::payments($order['id']);
        $coupon = AdminOrder::coupon($order['id']);
        AdminView::render('orders/show', compact('order', 'items', 'payments', 'coupon'), 'orders', $order['order_code']);
    }

    public function status(): void
    {
        AdminAuthController::guard();
        require_post();
        $id = input('id');
        $res = AdminOrder::changeStatus($id, input('status'));
        flash($res['ok'] ? 'success' : 'error', t($res['msg']));
        if ($res['ok'] && $res['refund']) {
            flash('info', t('order.msg.refund'));
        }
        redirect('admin/orders/show', ['id' => $id]);
    }

    /** Xuat danh sach don (theo bo loc hien tai) ra file CSV mo duoc bang Excel. */
    public function export(): void
    {
        AdminAuthController::guard();
        $flt = [
            'status' => input('status'), 'q' => input('q'), 'pay' => input('pay'),
            'from' => input('from'), 'to' => input('to'),
        ];
        $rows = [];
        $page = 1;
        do {
            $res = AdminOrder::search($flt, $page, 500);
            $rows = array_merge($rows, $res['items']);
            $page++;
        } while ($page <= $res['pages'] && $page <= 10);

        $safe = fn($v): string => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            t('order.col.code'), t('order.col.customer'), t('order.phone'), t('order.address'), t('order.col.date'),
            t('order.subtotal'), t('order.discount'), t('order.shipping'), t('order.total'),
            t('order.col.payment'), t('order.col.status'),
        ], ',', '"', '\\');
        foreach ($rows as $o) {
            fputcsv($out, [
                $safe($o['order_code']), $safe($o['customer_name']), $safe($o['customer_phone']), $safe($o['shipping_address']),
                $o['created_at'], (int) $o['subtotal'], (int) $o['discount_amount'], (int) $o['shipping_fee'], (int) $o['total_amount'],
                t(AdminOrder::payKey($o['pay_status'])), t('order.status.' . $o['status']),
            ], ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /** Trang in hoa don: khong dung layout admin. */
    public function invoice(): void
    {
        AdminAuthController::guard();
        $order = AdminOrder::find(input('id'));
        if (!$order) {
            abort(404, t('order.msg.notfound'));
        }
        $items = AdminOrder::items($order['id']);
        $coupon = AdminOrder::coupon($order['id']);
        require BASE_PATH . '/views/admin/orders/print.php';
    }
}
