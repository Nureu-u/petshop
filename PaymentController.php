<?php
declare(strict_types=1);

/**
 * Thanh toán chuyển khoản QR VietinBank qua SePay (TV4) - kiểm tra bằng polling API SePay.
 *
 *   payment/sepay_create : hiện mã QR + nội dung "SEVQR <mã đối soát>"
 *   payment/sepay_check  : AJAX, hỏi SePay xem tiền đã vào chưa (pending | short | paid | cancelled | error)
 *
 * Quy ước trạng thái (thống nhất cả nhóm):
 *   Thành công : payments PAID    + orders CONFIRMED
 *   Hết hạn    : payments FAILED  + orders CANCELLED (do Order::expireUnpaid)
 * Payment::markPaid() idempotent nên gọi lặp lại vẫn an toàn.
 */
final class PaymentController
{
    use JsonResponds;

    /** Hiện mã QR + nội dung SEVQR cho khách; trang tự gọi sepay_check để biết đã nhận tiền chưa. */
    public function sepay_create(): void
    {
        Auth::requireLogin();
        $uid = (string) Auth::id();
        Order::expireUnpaid($uid);

        $order = Order::find(input('order_id'), $uid);
        if (!$order) {
            abort(404, 'Không tìm thấy đơn hàng.');
        }
        if ($order['status'] !== 'PENDING_PAYMENT') {
            flash('error', 'Đơn hàng này không ở trạng thái chờ thanh toán.');
            redirect('orders');
        }

        $pay = Payment::latestByOrder($order['id']);
        if (!$pay || $pay['payment_method'] !== 'SEPAY') {
            flash('error', 'Đơn hàng này không thanh toán qua chuyển khoản QR.');
            redirect('orders');
        }
        if ($pay['status'] === 'PAID') {
            redirect('orders');
        }
        if ($pay['status'] === 'FAILED') { // lần trước thất bại/hết hạn => tạo lần thanh toán mới
            $pay = Payment::find(Payment::createForOrder($order['id'], 'SEPAY', (int) $order['total_amount']));
        }

        $amount  = (int) $pay['amount'];
        $content = SepayClient::transferContent($order, $pay);
        $info = [
            'order'      => $order,
            'amount'     => $amount,
            'content'    => $content,
            'qr_url'     => SepayClient::qrUrl($amount, $content),
            'bank'       => SepayClient::bankName(),
            'account'    => SepayClient::accountNumber(),
            'holder'     => SepayClient::accountName(),
            'expires_at' => strtotime((string) $pay['created_at']) + max(1, (int) config('order_hold_minutes', 30)) * 60,
            'check_url'  => url('payment/sepay_check', ['order_id' => $order['id']]),
            'orders_url' => url('orders'),
        ];
        $title = 'Chuyển khoản QR';
        view('payment/sepay_qr', compact('info', 'title'));
    }

    /** AJAX (GET): hỏi SePay xem tiền đã vào chưa. */
    public function sepay_check(): void
    {
        Auth::requireLogin();
        $uid = (string) Auth::id();
        Order::expireUnpaid($uid);

        $order = Order::find(input('order_id'), $uid);
        $pay = $order ? Payment::latestByOrder($order['id']) : null;
        if (!$order || !$pay || $pay['payment_method'] !== 'SEPAY') {
            $this->json(['status' => 'error', 'message' => 'Không tìm thấy giao dịch.'], 404);
        }
        if ($pay['status'] === 'PAID') {
            $this->json(['status' => 'paid']);
        }
        if ($pay['status'] !== 'PENDING' || $order['status'] !== 'PENDING_PAYMENT') {
            $this->json(['status' => 'cancelled']);
        }

        // Giới hạn 1 lần gọi SePay / 3 giây cho mỗi đơn (API SePay giới hạn 3 request/giây/token)
        $key = 'sepay_last_' . $order['id'];
        if (isset($_SESSION[$key]) && microtime(true) - (float) $_SESSION[$key] < 3.0) {
            $this->json(['status' => 'pending']);
        }
        $_SESSION[$key] = microtime(true);

        try {
            $res = SepayClient::findMatch($order, $pay);
        } catch (RuntimeException $e) {
            error_log('[SePay] ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => config('debug') ? $e->getMessage() : 'Chưa kiểm tra được giao dịch, đang thử lại...']);
        }

        if ($res['state'] === 'none') {
            $this->json(['status' => 'pending']);
        }
        if ($res['state'] === 'short') {
            // Có chuyển tiền đúng nội dung nhưng chưa đủ số tiền của đơn
            $missing = (int) $pay['amount'] - $res['received'];
            $this->json([
                'status'   => 'short',
                'received' => $res['received'],
                'missing'  => $missing,
                'message'  => 'Số tiền quý khách đã chuyển (' . format_price($res['received']) . ') chưa đủ. '
                            . 'Vui lòng chuyển bổ sung ' . format_price($missing) . ' với cùng nội dung chuyển khoản.',
            ]);
        }

        // Chốt chặn cuối: chỉ xác nhận khi tiền thật đã nhận ĐỦ số tiền của đơn
        if ($res['state'] !== 'paid' || (int) ($res['raw']['received_total'] ?? 0) < (int) $pay['amount']) {
            $this->json(['status' => 'pending']);
        }

        Payment::log($pay['id'], 'SEPAY_MATCHED', $res['raw']);
        Payment::markPaid($pay['transaction_ref'], $res['gateway']); // idempotent: gọi lặp vẫn an toàn
        $this->json(['status' => 'paid']);
    }
}
