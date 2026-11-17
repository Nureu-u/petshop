<?php
declare(strict_types=1);

/**
 * Thanh toán chuyển khoản QR qua SePay (VietinBank) - kiểu POLLING (không cần webhook / URL Internet).
 *
 * Luồng: web hiển thị QR -> khách chuyển khoản với nội dung "SEVQR <mã đối soát>" ->
 *        trang QR gọi payment/sepay_check mỗi vài giây -> server hỏi API SePay
 *        (GET https://my.sepay.vn/userapi/transactions/list) -> thấy tiền vào khớp => Payment::markPaid().
 *
 * Cấu hình trong config/config.local.php (file riêng máy bạn, KHÔNG commit):
 *   'sepay' => [
 *       'api_token' => '...',          // SePay > Cấu hình công ty > API Access > Thêm API (BÍ MẬT)
 *       // các mục dưới đây có mặc định, chỉ khai báo khi cần đổi
 *       'account_number' => '103627593520',
 *       'account_name'   => 'TA MINH KY',
 *       'bank'           => 'VietinBank',   // tên ngân hàng dùng cho ảnh QR (qr.sepay.vn)
 *       'verify_ssl'     => true,           // XAMPP Windows báo lỗi SSL => tạm đặt false khi chạy local
 *   ],
 *
 * VietinBank BẮT BUỘC nội dung chuyển khoản bắt đầu bằng SEVQR thì SePay mới nhận biến động số dư.
 */
final class SepayClient
{
    private const API_LIST = 'https://my.sepay.vn/userapi/transactions/list';
    private const QR_URL   = 'https://qr.sepay.vn/img';

    public static function accountNumber(): string
    {
        return (string) config('sepay.account_number', '103627593520');
    }

    public static function accountName(): string
    {
        return (string) config('sepay.account_name', 'TA MINH KY');
    }

    public static function bankName(): string
    {
        return (string) config('sepay.bank', 'VietinBank');
    }

    private static function apiToken(): string
    {
        // bỏ mọi khoảng trắng / xuống dòng thừa khi dán token vào file cấu hình
        return (string) preg_replace('/\s+/', '', (string) config('sepay.api_token', ''));
    }

    public static function isConfigured(): bool
    {
        return self::apiToken() !== '';
    }

    /** Mã đơn rút gọn chỉ gồm chữ + số: "PS-20261115-001" -> "PS20261115001". */
    public static function compactCode(string $orderCode): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $orderCode));
    }

    /**
     * Mã đối soát RIÊNG CHO TỪNG LẦN THANH TOÁN: mã đơn rút gọn + 4 ký tự suy ra từ transaction_ref của payment.
     * Nhờ vậy giao dịch cũ (đơn cũ trùng mã khi xóa dữ liệu test, lần thanh toán trước của cùng đơn...) không bao giờ khớp nhầm.
     * Ví dụ: PS20261115001 + A1B2 => PS20261115001A1B2
     */
    public static function paymentRef(array $order, array $payment): string
    {
        $suffix = strtoupper(substr(sprintf('%08x', crc32((string) $payment['transaction_ref'])), 0, 4));
        return self::compactCode((string) $order['order_code']) . $suffix;
    }

    /** Nội dung chuyển khoản: bắt đầu bằng SEVQR (yêu cầu của VietinBank + SePay). Tối đa ~23 ký tự. */
    public static function transferContent(array $order, array $payment): string
    {
        return 'SEVQR ' . self::paymentRef($order, $payment);
    }

    /** Ảnh QR VietQR (điền sẵn số tài khoản, số tiền, nội dung). */
    public static function qrUrl(int $amount, string $content): string
    {
        return self::QR_URL . '?' . http_build_query([
            'acc'    => self::accountNumber(),
            'bank'   => self::bankName(),
            'amount' => $amount,
            'des'    => $content,
        ]);
    }

    /**
     * Tìm các giao dịch tiền VÀO thật sự thuộc về lần thanh toán này rồi cộng dồn số tiền.
     * Một giao dịch chỉ được tính khi THỎA TẤT CẢ:
     *   - tiền vào (amount_in > 0) và đúng số tài khoản nhận của cửa hàng;
     *   - nội dung chứa mã đối soát riêng của lần thanh toán (paymentRef);
     *   - id giao dịch SePay chưa được dùng để xác nhận một thanh toán khác.
     * Kết quả:
     *  - Tổng >= số tiền đơn  => state 'paid'  (kèm 'gateway' cho Payment::markPaid và 'raw' để ghi log)
     *  - 0 < tổng < số tiền   => state 'short' (kèm 'received')
     *  - chưa thấy giao dịch  => state 'none'
     * @throws RuntimeException khi không gọi được API SePay
     */
    public static function findMatch(array $order, array $payment): array
    {
        $ref    = self::paymentRef($order, $payment);
        $amount = (int) $payment['amount'];
        if ($amount <= 0) {
            return ['state' => 'none']; // không bao giờ tự xác nhận khoản 0đ
        }

        // Giờ ngân hàng = giờ Việt Nam. Lấy cả thời gian giữ đơn + 2 giờ dự phòng; việc chống khớp nhầm đã do paymentRef lo.
        $hold  = max(1, (int) config('order_hold_minutes', 30));
        $since = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))
            ->modify('-' . ($hold + 120) . ' minutes')->format('Y-m-d H:i:s');

        $total = 0;
        $last  = null;
        $seen  = [];
        foreach (self::fetchTransactions($since) as $tx) {
            $in = (int) round((float) ($tx['amount_in'] ?? 0));
            if ($in <= 0) {
                continue;
            }
            $txAcc = (string) ($tx['account_number'] ?? '');
            if ($txAcc !== '' && $txAcc !== self::accountNumber()) {
                continue; // giao dịch của tài khoản khác
            }
            $content = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) ($tx['transaction_content'] ?? '')));
            if (strpos($content, $ref) === false) {
                continue;
            }
            $txId = (string) ($tx['id'] ?? '');
            if ($txId === '' || isset($seen[$txId])) {
                continue;
            }
            if (Payment::gatewayTxnUsed($txId, (string) $payment['id'])) {
                continue; // giao dịch này đã xác nhận cho thanh toán khác
            }
            $seen[$txId] = true;
            $total += $in;
            if ($last === null || strcmp((string) ($tx['transaction_date'] ?? ''), (string) ($last['transaction_date'] ?? '')) >= 0) {
                $last = $tx;
            }
        }

        if ($last === null) {
            return ['state' => 'none'];
        }
        if ($total < $amount) {
            return ['state' => 'short', 'received' => $total];
        }

        $d = DateTime::createFromFormat('Y-m-d H:i:s', (string) ($last['transaction_date'] ?? ''));
        return [
            'state'   => 'paid',
            'gateway' => [
                'transaction_no'     => substr((string) ($last['id'] ?? ''), 0, 40),
                'response_code'      => '00',
                'transaction_status' => '00',
                'bank_code'          => 'VietinBank',
                'pay_date'           => $d ? $d->format('YmdHis') : date('YmdHis'),
            ],
            'raw' => [
                'sepay_id'         => (string) ($last['id'] ?? ''),
                'reference_number' => (string) ($last['reference_number'] ?? ''),
                'received_total'   => $total,
                'expected'         => $amount,
                'ref'              => $ref,
                'content'          => (string) ($last['transaction_content'] ?? ''),
                'date'             => (string) ($last['transaction_date'] ?? ''),
            ],
        ];
    }

    /** @return array[] danh sách giao dịch từ $since trở đi của tài khoản liên kết. */
    private static function fetchTransactions(string $since): array
    {
        if (!self::isConfigured()) {
            throw new RuntimeException('Chưa cấu hình SePay (sepay.api_token) trong config/config.local.php.');
        }
        $url = self::API_LIST . '?' . http_build_query([
            'account_number'       => self::accountNumber(),
            'transaction_date_min' => $since,
            'limit'                => 50,
        ]);
        [$code, $body] = self::httpGet($url);

        if ($code === 401) {
            throw new RuntimeException('SePay từ chối API token (401). Kiểm tra lại sepay.api_token.');
        }
        if ($code === 404) { // không có giao dịch nào trong khoảng thời gian
            return [];
        }
        if ($code === 429) {
            throw new RuntimeException('SePay giới hạn tốc độ (429). Thử lại sau vài giây.');
        }
        $json = json_decode($body, true);
        if ($code !== 200 || !is_array($json)) {
            throw new RuntimeException('SePay trả về HTTP ' . $code . ' / dữ liệu không hợp lệ.');
        }
        return is_array($json['transactions'] ?? null) ? $json['transactions'] : [];
    }

    /** @return array{0:int,1:string} [HTTP code, body] */
    private static function httpGet(string $url): array
    {
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . self::apiToken()];
        $verify  = (bool) config('sepay.verify_ssl', true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            ]);
            $body = curl_exec($ch);
            if ($body === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Không gọi được SePay: ' . $err);
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$code, (string) $body];
        }

        // Dự phòng khi PHP không bật cURL
        $ctx = stream_context_create([
            'http' => ['method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => 10, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => $verify, 'verify_peer_name' => $verify],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new RuntimeException('Không gọi được SePay (file_get_contents thất bại).');
        }
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
        return [$code, (string) $body];
    }
}
