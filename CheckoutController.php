<?php
declare(strict_types=1);

final class CheckoutController
{
    use JsonResponds;

    public function index(): void
    {
        Auth::requireLogin();
        $uid = (string) Auth::id();

        $cartItems = Cart::items($uid);
        if (!$cartItems) {
            flash('error', 'Giỏ hàng đang trống.');
            redirect('cart');
        }
        foreach ($cartItems as $it) {
            if (!$it['available']) {
                flash('error', '"' . $it['name'] . '": ' . $it['warning'] . ' Vui lòng xóa khỏi giỏ trước khi đặt hàng.');
                redirect('cart');
            }
        }

        // Điền sẵn thông tin giao hàng: dữ liệu vừa nhập (nếu đặt lỗi) > hồ sơ tài khoản
        $u = Auth::user();
        $old = $_SESSION['checkout_old'] ?? [];
        unset($_SESSION['checkout_old']);
        $user = [
            'id'        => $u['id'],
            'full_name' => $old['customer_name'] ?? $u['full_name'],
            'phone'     => $old['customer_phone'] ?? ($u['phone'] ?? ''),
            'address'   => $old['shipping_address'] ?? ($u['address'] ?? ''),
        ];

        $subtotal = Cart::subtotal($cartItems);
        $shippingFee = Order::shippingFee();
        $title = 'Thanh toán';
        view('checkout/index', compact('user', 'cartItems', 'subtotal', 'shippingFee', 'title'));
    }

    /** POST ajax: xem trước mã giảm giá. Số tiền thật vẫn được tính lại khi tạo đơn. */
    public function coupon(): void
    {
        Auth::requireLogin();
        require_post();

        $uid = (string) Auth::id();
        $subtotal = Cart::subtotal(Cart::items($uid));
        [$coupon, $err, $discount] = CouponService::evaluate(input('coupon_code'), $uid, $subtotal);
        $shipping = Order::shippingFee();

        $this->json([
            'ok'       => $err === null,
            'message'  => $err ?? ($coupon ? 'Áp dụng mã ' . $coupon['code'] . ' thành công! Giảm ' . format_price($discount) : 'Không sử dụng mã giảm giá.'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total'    => $subtotal - $discount + $shipping,
        ]);
    }
}
