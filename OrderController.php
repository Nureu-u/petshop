<?php
declare(strict_types=1);

final class OrderController
{
    /** POST từ views/checkout/index.php. subtotal/discount/total trong form bị bỏ qua. */
    public function create(): void
    {
        Auth::requireLogin();
        require_post();

        [$errors, $data] = Order::validate($_POST);
        if ($errors) {
            $_SESSION['checkout_old'] = $data;
            flash('error', reset($errors));
            redirect('checkout');
        }

        $res = Order::createFromCart((string) Auth::id(), $data);
        if ($res['error'] !== null) {
            $_SESSION['checkout_old'] = $data;
            flash('error', $res['error']);
            redirect('checkout');
        }

        if ($data['payment_method'] === 'SEPAY') {
            redirect('payment/sepay_create', ['order_id' => $res['order_id']]);
        }
        flash('success', 'Đặt hàng thành công! Bạn sẽ thanh toán khi nhận hàng.');
        redirect('orders');
    }

    public function history(): void
    {
        Auth::requireLogin();
        if (Order::expireUnpaid((string) Auth::id()) > 0) {
            flash('info', 'Một số đơn chờ thanh toán đã quá hạn nên được tự động hủy.');
        }

        $statusView = [
            'PENDING_PAYMENT' => 'pending', 'CONFIRMED' => 'confirmed', 'SHIPPING' => 'shipping',
            'DELIVERED' => 'completed', 'CANCELLED' => 'cancelled',
        ];

        // Chuyển sang đúng cấu trúc mà views/orders/history.php (TV2) đang đọc
        $orders = [];
        foreach (Order::historyForUser((string) Auth::id()) as $o) {
            $pay = $o['payment'];
            $items = [];
            foreach ($o['items'] as $it) {
                $items[] = [
                    'id' => $it['pet_id'] ?? $it['product_id'], 'name' => $it['item_name'],
                    'qty' => (int) $it['quantity'], 'price' => (int) $it['unit_price'],
                ];
            }
            $orders[] = [
                'id' => $o['id'], 'order_code' => $o['order_code'],
                'created_at' => date('Y-m-d H:i', strtotime((string) $o['created_at'])),
                'shipping_address' => $o['shipping_address'], 'total_amount' => (int) $o['total_amount'],
                'status' => $statusView[$o['status']] ?? 'pending',
                'status_raw' => $o['status'],
                'payment_method' => $pay['payment_method'] ?? 'COD',
                'payment_status' => ($pay['status'] ?? '') === 'PAID' ? 'paid' : 'unpaid',
                'can_cancel' => in_array($o['status'], ['PENDING_PAYMENT', 'CONFIRMED'], true)
                                && ($pay['status'] ?? '') !== 'PAID',
                // Link "Thanh toán / Thanh toán lại" cho đơn online còn chờ thanh toán
                'pay_url' => ($o['status'] === 'PENDING_PAYMENT' && ($pay['status'] ?? '') !== 'PAID'
                              && ($pay['payment_method'] ?? '') === 'SEPAY')
                    ? url('payment/sepay_create', ['order_id' => $o['id']])
                    : null,
                'items' => $items,
            ];
        }

        $title = 'Lịch sử đơn hàng';
        view('orders/history', compact('orders', 'title'));
    }

    /** POST: order_id */
    public function cancel(): void
    {
        Auth::requireLogin();
        require_post();

        $err = Order::cancel(input('order_id'), (string) Auth::id());
        flash($err === null ? 'success' : 'error', $err ?? 'Đã hủy đơn hàng và hoàn lại tồn kho.');
        redirect('orders');
    }
}
