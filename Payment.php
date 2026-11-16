<?php
declare(strict_types=1);

/**
 * Thanh toán & nhật ký giao dịch (TV4). Bảng: payments, payment_transactions.
 * Trạng thái thống nhất của nhóm:
 *   Payment: PENDING | PAID | FAILED | REFUNDED
 *   Order  : PENDING_PAYMENT | CONFIRMED | SHIPPING | DELIVERED | CANCELLED
 *   Payment = PAID  =>  Order = CONFIRMED
 *
 * Thanh toán chuyển khoản QR (SEPAY) và COD. Dùng lại:
 *   Payment::createForOrder()  -> sinh transaction_ref cho mỗi lần thanh toán
 *   Payment::markPaid() / markFailed()  -> gọi từ PaymentController::sepay_check; đã idempotent
 *   Payment::log()  -> ghi lại dữ liệu return/IPN
 */
final class Payment
{
    public const METHODS = ['SEPAY', 'COD'];

    /** Mã tham chiếu duy nhất cho mỗi lần thanh toán (chỉ chữ + số, hợp lệ làm vnp_TxnRef). */
    public static function newTxnRef(string $orderId): string
    {
        return $orderId . date('His') . random_int(100, 999);
    }

    public static function createForOrder(string $orderId, string $method, int $amount): string
    {
        if (!in_array($method, self::METHODS, true)) {
            throw new InvalidArgumentException('Phương thức thanh toán không hợp lệ.');
        }
        $id = IdGenerator::next(IdGenerator::PAYMENT);
        Database::pdo()->prepare(
            "INSERT INTO payments (id, order_id, payment_method, transaction_ref, amount, status)
             VALUES (:id, :o, :m, :t, :a, 'PENDING')"
        )->execute([
            'id' => $id, 'o' => $orderId, 'm' => $method,
            't' => self::newTxnRef($orderId), 'a' => $amount,
        ]);
        self::log($id, 'CREATED', ['method' => $method, 'amount' => $amount]);
        return $id;
    }

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /** Đã từng chuyển khách sang cổng thanh toán với bản ghi này chưa? */
    public static function wasRedirected(string $paymentId): bool
    {
        $st = Database::pdo()->prepare("SELECT 1 FROM payment_transactions WHERE payment_id = :p AND event = 'REDIRECTED' LIMIT 1");
        $st->execute(['p' => $paymentId]);
        return (bool) $st->fetchColumn();
    }

    /** Đổi vnp_TxnRef mới cho lần thanh toán lại (mỗi lần gửi sang VNPAY cần mã duy nhất). Chỉ áp dụng khi PENDING. */
    public static function renewTxnRef(string $paymentId): ?string
    {
        $p = self::find($paymentId);
        if (!$p || $p['status'] !== 'PENDING') {
            return null;
        }
        $new = self::newTxnRef($p['order_id']);
        $st = Database::pdo()->prepare("UPDATE payments SET transaction_ref = :n WHERE id = :id AND status = 'PENDING'");
        $st->execute(['n' => $new, 'id' => $paymentId]);
        return $st->rowCount() === 1 ? $new : null;
    }

    /** Id giao dịch SePay này đã được dùng để xác nhận một thanh toán KHÁC (đã PAID) chưa? */
    public static function gatewayTxnUsed(string $gatewayTxnNo, string $exceptPaymentId): bool
    {
        $st = Database::pdo()->prepare(
            "SELECT 1 FROM payments WHERE vnp_transaction_no = :n AND status = 'PAID' AND id <> :p LIMIT 1"
        );
        $st->execute(['n' => substr($gatewayTxnNo, 0, 40), 'p' => $exceptPaymentId]);
        return (bool) $st->fetchColumn();
    }

    /** Lần thanh toán mới nhất của đơn. */
    public static function latestByOrder(string $orderId): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE order_id = :o ORDER BY created_at DESC, id DESC LIMIT 1');
        $st->execute(['o' => $orderId]);
        return $st->fetch() ?: null;
    }

    public static function findByTxnRef(string $txnRef): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM payments WHERE transaction_ref = :t');
        $st->execute(['t' => $txnRef]);
        return $st->fetch() ?: null;
    }

    /**
     * PENDING -> PAID và đơn PENDING_PAYMENT -> CONFIRMED trong cùng transaction.
     * Idempotent: gọi lại nhiều lần (return + IPN) chỉ có lần đầu trả true.
     * @param array $gw dữ liệu cổng: transaction_no, response_code, transaction_status, bank_code, pay_date
     */
    public static function markPaid(string $txnRef, array $gw = []): bool
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                "UPDATE payments SET status = 'PAID', vnp_transaction_no = :no, vnp_response_code = :rc,
                        vnp_transaction_status = :ts, vnp_bank_code = :bk, vnp_pay_date = :pd, vnp_paid_at = NOW()
                 WHERE transaction_ref = :t AND status = 'PENDING'"
            );
            $st->execute([
                'no' => $gw['transaction_no'] ?? null, 'rc' => $gw['response_code'] ?? null,
                'ts' => $gw['transaction_status'] ?? null, 'bk' => $gw['bank_code'] ?? null,
                'pd' => $gw['pay_date'] ?? null, 't' => $txnRef,
            ]);
            if ($st->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            $p = self::findByTxnRef($txnRef);
            $pdo->prepare("UPDATE orders SET status = 'CONFIRMED' WHERE id = :o AND status = 'PENDING_PAYMENT'")
                ->execute(['o' => $p['order_id']]);
            self::log($p['id'], 'PAID', $gw);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** PENDING -> FAILED. Đơn vẫn giữ PENDING_PAYMENT để khách thanh toán lại. */
    public static function markFailed(string $txnRef, array $gw = []): bool
    {
        $st = Database::pdo()->prepare(
            "UPDATE payments SET status = 'FAILED', vnp_response_code = :rc, vnp_transaction_status = :ts,
                    vnp_transaction_no = :no, vnp_bank_code = :bk
             WHERE transaction_ref = :t AND status = 'PENDING'"
        );
        $st->execute([
            'rc' => $gw['response_code'] ?? null, 'ts' => $gw['transaction_status'] ?? null,
            'no' => $gw['transaction_no'] ?? null, 'bk' => $gw['bank_code'] ?? null, 't' => $txnRef,
        ]);
        if ($st->rowCount() !== 1) {
            return false;
        }
        $p = self::findByTxnRef($txnRef);
        self::log($p['id'], 'FAILED', $gw);
        return true;
    }

    /** Ghi nhật ký giao dịch. KHÔNG đưa secret / hash secret vào $data. */
    public static function log(string $paymentId, string $event, array $data = []): void
    {
        Database::pdo()->prepare(
            'INSERT INTO payment_transactions (id, payment_id, event, raw_data) VALUES (:id, :p, :e, :d)'
        )->execute([
            'id' => IdGenerator::next('PTX'), 'p' => $paymentId, 'e' => $event,
            'd' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }
}
