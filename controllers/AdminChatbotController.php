<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: QUAN LY CHATBOT (khu quan tri)
   File    : controllers/AdminChatbotController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/chatbot                         -> index()      hien thi views/admin/chatbot/index.php
     ?r=admin/chatbot/faq                     -> faqSave()    them / sua cau hoi thuong gap
     ?r=admin/chatbot/faq/toggle              -> faqToggle()  an / hien cau hoi thuong gap
     ?r=admin/chatbot/reindex                 -> reindex()    cap nhat kho tri thuc va vector
     ?r=admin/chatbot/test                    -> test()       hoi thu (tra JSON, kem diem tung doan)
   Du lieu lay tu: models/Chatbot.php
   ============================================================================== */
final class AdminChatbotController
{
    public function index(): void
    {
        AdminAuthController::guard();
        Chatbot::ensureTables();
        $stats = Chatbot::stats();
        $faqs = Chatbot::faqs();
        $recent = Chatbot::recent(15);
        $aiOn = ChatbotGemini::enabled();
        $edit = null;
        $editId = (int) input('edit', '0');
        if ($editId > 0) {
            $edit = Chatbot::faqFind($editId);
        }
        $model = ChatbotGemini::chatModel();
        AdminView::render('chatbot/index', compact('stats', 'faqs', 'recent', 'aiOn', 'edit', 'model'), 'chatbot', t('nav.chatbot'));
    }

    public function faqSave(): void
    {
        AdminAuthController::guard();
        require_post();
        Chatbot::ensureTables();
        $id = (int) input('id', '0');
        [$ok, $msg] = Chatbot::faqSave($id > 0 ? $id : null, input('question'), input('answer'), input('status', 'active'));
        flash($ok ? 'success' : 'error', t($msg));
        redirect('admin/chatbot');
    }

    public function faqToggle(): void
    {
        AdminAuthController::guard();
        require_post();
        $ok = Chatbot::faqToggle((int) input('id', '0'));
        flash($ok ? 'success' : 'error', t($ok ? 'chatbot.faq.toggled' : 'chatbot.err.notfound'));
        redirect('admin/chatbot');
    }

    public function reindex(): void
    {
        AdminAuthController::guard();
        require_post();
        $s = Chatbot::reindex();
        $msg = t('chatbot.reindex.done', ['a' => $s['added'], 'u' => $s['updated'], 'r' => $s['removed'], 'e' => $s['embedded']]);
        if ($s['error'] !== '') {
            flash('error', $msg . ' ' . t('chatbot.reindex.warn', ['err' => $s['error']]));
        } else {
            flash('success', $msg);
        }
        redirect('admin/chatbot');
    }

    /** Hoi thu: khong luu nhat ky, khong tra du lieu don hang. Tra them diem cua tung doan tim duoc. */
    public function test(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::check() || !Auth::isAdmin() || !is_post()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        csrf_verify();
        try {
            // Lich su cac luot truoc do client gui len (chi admin dung o day), kiem tra lai truoc khi dung
            $history = [];
            $raw = json_decode((string) ($_POST['history'] ?? '[]'), true);
            foreach (is_array($raw) ? array_slice($raw, -12) : [] as $h) {
                if (is_array($h) && in_array($h['role'] ?? '', ['user', 'model'], true) && is_string($h['text'] ?? null)) {
                    $history[] = ['role' => $h['role'], 'text' => mb_substr($h['text'], 0, 1500)];
                }
            }
            $shown = [];
            $rawShown = json_decode((string) ($_POST['shown'] ?? '[]'), true);
            foreach (is_array($rawShown) ? array_slice($rawShown, -30) : [] as $n) {
                if (is_string($n)) {
                    $shown[] = mb_substr($n, 0, 200);
                }
            }
            $r = Chatbot::answer(input('message'), $history, null, $shown);
            $hits = array_map(fn($h) => ['title' => $h['title'], 'score' => $h['score'], 'type' => $h['type']], $r['hits']);
            echo json_encode(['answer' => $r['answer'], 'mode' => $r['mode'], 'reason' => $r['error'], 'items' => $r['items'], 'hits' => $hits], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('chatbot test: ' . $e);
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}
