<?php
declare(strict_types=1);

/* ==============================================================================
   CONTROLLER: CHATBOT CHO KHACH HANG
   File    : controllers/ChatbotController.php
   Moi ham cong khai (public) la mot thao tac tra ve JSON. Bang duong dan (nam trong index.php):
     ?r=chatbot/ask                           -> ask()    nhan cau hoi (POST), tra loi
     ?r=chatbot/reset                         -> reset()  xoa cuoc tro chuyen dang mo (POST)
   Du lieu lay tu: models/Chatbot.php
   Khung chat hien o trang khach: them 1 dong vao footer, xem docs/CHATBOT_HUONG_DAN.md
   ============================================================================== */
final class ChatbotController
{
    public function ask(): void
    {
        $this->json(function () {
            $message = input('message');
            if (Chatbot::rateLimited()) {
                return ['answer' => 'Bạn hỏi nhanh quá, vui lòng chờ vài phút rồi hỏi tiếp nhé.', 'sources' => [], 'items' => [], 'mode' => 'blocked'];
            }
            $user = Auth::check() ? Auth::user() : null;
            $history = (array) ($_SESSION['chatbot_hist'] ?? []);

            $shown = (array) ($_SESSION['chatbot_shown'] ?? []);

            $res = Chatbot::answer($message, $history, $user, $shown);

            if ($res['mode'] !== 'blocked') {
                $history[] = ['role' => 'user', 'text' => $message];
                $history[] = ['role' => 'model', 'text' => $res['answer']];
                $_SESSION['chatbot_hist'] = array_slice($history, -2 * Chatbot::HISTORY_TURNS);
                // Nho cac the da hien de hoi tiep khong hien lai anh
                $_SESSION['chatbot_shown'] = array_slice(array_merge($shown, array_column($res['items'], 'name')), -30);
                Chatbot::log($user, $message, $res);
            }
            // Co bang ket qua thi khong can hien them the lien ket nguon
            return ['answer' => $res['answer'], 'sources' => $res['items'] ? [] : $res['sources'], 'items' => $res['items'], 'mode' => $res['mode']];
        });
    }

    public function reset(): void
    {
        $this->json(function () {
            unset($_SESSION['chatbot_hist'], $_SESSION['chatbot_shown']);
            return ['ok' => true];
        });
    }

    /** Chay ham, kiem tra POST + CSRF, tra JSON. Loi he thong khong lo chi tiet cho khach. */
    private function json(callable $fn): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (!is_post()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            http_response_code(419);
            echo json_encode(['error' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            echo json_encode($fn(), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('chatbot: ' . $e);
            http_response_code(500);
            echo json_encode(['error' => config('debug') ? $e->getMessage() : 'Trợ lý đang gặp sự cố, bạn thử lại sau nhé.'], JSON_UNESCAPED_UNICODE);
        }
    }
}
