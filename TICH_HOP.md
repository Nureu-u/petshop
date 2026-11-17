# Phần TV4 – Giỏ hàng, Đặt hàng, Thanh toán (chuyển khoản QR VietinBank qua SePay + COD)

Không còn VNPAY và không còn chế độ giả lập: thanh toán online chỉ xác nhận khi SePay báo có TIỀN THẬT vào tài khoản.

## File
| File | Việc |
|---|---|
| core/SepayClient.php | QR VietinBank, gọi API SePay (polling), đối khớp giao dịch theo nội dung `SEVQR <mã đơn>` |
| models/Cart.php, Order.php, Payment.php, CouponService.php | Giỏ, đơn, thanh toán, mã giảm giá |
| controllers/Cart, Checkout, Order, PaymentController.php | Xử lý request |
| views/payment/sepay_qr.php | Trang quét QR, tự kiểm tra 4 giây/lần |
| database/patch_tv4.sql | Chạy 1 lần SAU schema + patch_id_sequences + seed |

## Route cần thêm vào `$routes` trong index.php
```php
    'cart'                 => ['CartController', 'index'],
    'cart/add'             => ['CartController', 'add'],
    'cart/update'          => ['CartController', 'update'],
    'cart/remove'          => ['CartController', 'remove'],
    'checkout'             => ['CheckoutController', 'index'],
    'checkout/coupon'      => ['CheckoutController', 'coupon'],
    'order/create'         => ['OrderController', 'create'],
    'order/cancel'         => ['OrderController', 'cancel'],
    'orders'               => ['OrderController', 'history'],
    'payment/sepay_create' => ['PaymentController', 'sepay_create'],
    'payment/sepay_check'  => ['PaymentController', 'sepay_check'],
```
(Tên hàm Cart/Checkout/Order: lấy đúng theo bản route cũ trong nhóm nếu khác.)

## config/config.local.php (không commit, không nộp)
```php
'sepay' => [
    'api_token' => '',   // (khoảng trắng/xuống dòng thừa được tự cắt) SePay > Cấu hình công ty > API Access > Thêm API. BÍ MẬT
    // Mặc định đã là VietinBank 103627593520 / TA MINH KY (tài khoản phụ nhận tiền cửa hàng); chỉ khai báo khi đổi:
    // 'account_number' => '...', 'account_name' => '...', 'bank' => 'VietinBank',
    // 'verify_ssl' => false,   // chỉ khi XAMPP Windows báo lỗi SSL
],
// 'shipping_fee' => 0,         // tạm đặt 0 khi test số tiền nhỏ
// 'order_hold_minutes' => 30,  // quá hạn chưa thanh toán thì tự hủy đơn
```

## Cách hoạt động
- Checkout chọn "Chuyển khoản QR" -> đơn PENDING_PAYMENT -> trang QR (`payment/sepay_create`).
- Nội dung chuyển khoản bắt buộc bắt đầu bằng `SEVQR` (yêu cầu của VietinBank + SePay), ví dụ `SEVQR PS20261115001A1B2`:
  mã đơn rút gọn + 4 ký tự riêng của MỖI LẦN thanh toán (`SepayClient::paymentRef`). Nhờ vậy giao dịch cũ / đơn cũ trùng mã không thể khớp nhầm.
- Số tiền trong QR = tổng đơn (tiền hàng − giảm giá + phí ship) do server tính, mua nhiều thì QR tăng theo.
- Trang QR gọi `payment/sepay_check` mỗi 4 giây -> server gọi `GET https://my.sepay.vn/userapi/transactions/list`
  (Bearer API token, giới hạn 3 request/giây) và cộng các khoản tiền vào có nội dung chứa mã đơn:
  - chỉ tính giao dịch tiền VÀO, đúng STK nhận, đúng mã đối soát và id SePay chưa dùng cho thanh toán khác;
  - tổng >= tiền đơn  -> `Payment::markPaid()` (payments PAID + orders CONFIRMED);
  - có chuyển nhưng thiếu -> báo "Số tiền đã chuyển chưa đủ, vui lòng chuyển bổ sung ..." (khách chuyển thêm cùng nội dung);
  - quá hạn -> đơn tự hủy (hoàn kho / thú cưng / coupon).
- Web KHÔNG biết số dư của khách. "Không đủ số dư" do app ngân hàng của khách báo và giao dịch không phát sinh;
  trang QR chỉ hiển thị lời nhắc. Web chỉ phát hiện được trường hợp đã chuyển nhưng SỐ TIỀN THIẾU.
- Nhật ký: bảng `payment_transactions` (SEPAY_MATCHED...). Dữ liệu cổng ghi vào cột vnp_* sẵn có
  (vnp_transaction_no = id giao dịch SePay, vnp_bank_code = VietinBank) - đây chỉ là tên cột của schema TV3.
- Khách chuyển tiền SAU khi đơn đã hết hạn/bị hủy: hệ thống không tự nhận, cần hoàn tiền thủ công.

## Ghép với TV2 (tách riêng, mỗi người nộp 1 zip)
- Checkout: `payment_method` = `COD` hoặc `SEPAY`.
- Lịch sử đơn: dùng cờ `can_cancel` và link `pay_url` (nút "Thanh toán ngay", chỉ có ở đơn SEPAY còn chờ).
- Các view dùng Tailwind + Font Awesome: cần header của TV1 (header TV3 chưa nạp).

## Test
1. Đăng nhập -> thêm giỏ -> Thanh toán -> chọn "Chuyển khoản QR" -> Đặt hàng.
2. Quét QR bằng app ngân hàng NHƯNG CHƯA bấm chuyển: kiểm tra tên TA MINH KY, STK 103627593520,
   đúng số tiền, nội dung bắt đầu bằng SEVQR.
3. Chuyển tiền thật (nên đặt `shipping_fee` = 0 và chọn món rẻ). Trang báo "Đã nhận tiền" sau vài giây.
4. Đối chiếu: mục Giao dịch trên SePay và bảng `payments` / `payment_transactions`.

## Sửa lỗi "chưa đủ tiền vẫn báo thành công" (ver8)
Bản trước đối soát chỉ theo mã đơn + mốc thời gian nên có thể khớp nhầm giao dịch cũ. Nay: mã đối soát riêng từng lần thanh toán,
kiểm tra đúng STK nhận, không dùng lại id giao dịch SePay, không bao giờ xác nhận đơn 0đ, và có chốt chặn ở controller.
Trang QR có thêm nút "Tôi đã chuyển khoản — kiểm tra ngay".
Khi test: đơn tạo TRƯỚC bản này vẫn mang nội dung cũ -> hãy hủy và đặt lại đơn mới.

## Lưu ý
- Token SePay lộ thì xóa và tạo token mới ngay.
- Chưa chạy thử trên PHP thật ở môi trường soạn thảo: chạy thử trên XAMPP trước khi nộp.
