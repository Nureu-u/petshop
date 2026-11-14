<?php
declare(strict_types=1);

/* ==============================================================================
   CORE: GOI GOOGLE GEMINI (nhung vector + sinh cau tra loi)
   File    : core/ChatbotGemini.php
   Duoc dung boi: models/Chatbot.php
   Khoa API lay tu config/config.local.php (khong commit) hoac bien moi truong GEMINI_API_KEY:
     'gemini' => ['api_key' => '...']
   Cac tuy chon khac (deu co the bo trong):
     chat_model  mac dinh 'gemini-flash-latest'
     embed_model mac dinh 'gemini-embedding-001'
     embed_dim   mac dinh 768
     verify_ssl  mac dinh true (chi dat false khi chay thu tren may ca nhan bi loi chung chi SSL cua XAMPP)
   Moi ham loi tra ve null va ghi ly do vao lastError(), khong lam trang bi vo.
   ============================================================================== */
final class ChatbotGemini
{
    private const BASE = 'https://generativelanguage.googleapis.com/v1beta/';
    private static string $lastError = '';

    public static function key(): string
    {
        $k = (string) config('gemini.api_key', '');
        if ($k === '') {
            $k = (string) (getenv('GEMINI_API_KEY') ?: '');
        }
        return trim($k);
    }

    public static function enabled(): bool
    {
        return self::key() !== '';
    }

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function chatModel(): string
    {
        return (string) config('gemini.chat_model', 'gemini-flash-latest');
    }

    public static function embedModel(): string
    {
        return (string) config('gemini.embed_model', 'gemini-embedding-001');
    }

    public static function embedDim(): int
    {
        return max(64, (int) config('gemini.embed_dim', 768));
    }

    /**
     * Nhung nhieu doan chu thanh vector (da chuan hoa).
     * @param string $task 'RETRIEVAL_DOCUMENT' (luu kho) hoac 'RETRIEVAL_QUERY' (cau hoi)
     * @return array|null danh sach vector cung thu tu $texts, hoac null neu loi
     */
    public static function embed(array $texts, string $task = 'RETRIEVAL_DOCUMENT'): ?array
    {
        if (!$texts) {
            return [];
        }
        $model = 'models/' . self::embedModel();
        $reqs = [];
        foreach ($texts as $t) {
            $reqs[] = [
                'model' => $model,
                'content' => ['parts' => [['text' => mb_substr((string) $t, 0, 6000)]]],
                'taskType' => $task,
                'outputDimensionality' => self::embedDim(),
            ];
        }
        $res = self::post($model . ':batchEmbedContents', ['requests' => $reqs]);
        if ($res === null || !isset($res['embeddings']) || count($res['embeddings']) !== count($texts)) {
            if ($res !== null && self::$lastError === '') {
                self::$lastError = 'Gemini tra ve du lieu nhung vector khong dung dinh dang.';
            }
            return null;
        }
        $out = [];
        foreach ($res['embeddings'] as $e) {
            $vec = array_map('floatval', $e['values'] ?? []);
            $out[] = ChatbotText::normalize($vec);
        }
        return $out;
    }

    /**
     * Sinh cau tra loi.
     * @param array $contents danh sach ['role' => 'user'|'model', 'text' => '...']
     */
    public static function generate(string $system, array $contents, float $temperature = 0.3, int $maxTokens = 700): ?string
    {
        $parts = [];
        foreach ($contents as $c) {
            $parts[] = ['role' => $c['role'] === 'model' ? 'model' : 'user', 'parts' => [['text' => (string) $c['text']]]];
        }
        $body = [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => $parts,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'thinkingConfig' => ['thinkingBudget' => 0],
            ],
        ];
        $res = self::postChat(self::chatModel(), $body);
        if ($res === null && self::isOverload(self::$lastError) && !str_contains(self::$lastError, 'timed out')) {
            sleep(1);
            $res = self::postChat(self::chatModel(), $body);         // thu lai 1 lan
        }
        if ($res === null && self::isOverload(self::$lastError)) {
            $alt = (string) config('gemini.fallback_chat_model', 'gemini-flash-lite-latest');
            if ($alt !== '' && $alt !== self::chatModel()) {
                $res = self::postChat($alt, $body);                  // doi sang model nhe hon
            }
        }
        if ($res === null) {
            return null;
        }
        $text = '';
        foreach (($res['candidates'][0]['content']['parts'] ?? []) as $p) {
            $text .= (string) ($p['text'] ?? '');
        }
        $text = trim($text);
        if ($text === '') {
            self::$lastError = 'Gemini khong tra ve noi dung (co the bi chan boi bo loc an toan).';
            return null;
        }
        return $text;
    }

    /** Goi generateContent cho mot model; neu model khong ho tro thinkingConfig thi thu lai khong co. */
    private static function postChat(string $model, array $body): ?array
    {
        $path = 'models/' . $model . ':generateContent';
        $res = self::post($path, $body);
        if ($res === null && isset($body['generationConfig']['thinkingConfig'])
            && (stripos(self::$lastError, 'thinking') !== false || str_contains(self::$lastError, 'HTTP 400'))) {
            // Mot so model khong nhan thinkingConfig (bao 400): gui lai khong co
            unset($body['generationConfig']['thinkingConfig']);
            $res = self::post($path, $body);
        }
        return $res;
    }

    /** Loi tam thoi cua Google (qua tai, het luot trong phut): dang thu lai duoc. */
    private static function isOverload(string $err): bool
    {
        return (bool) preg_match('/HTTP (429|500|502|503|504)\b|timed out|timeout/i', $err);
    }

    /** Gui POST JSON, tra ve mang da giai ma hoac null. */
    private static function post(string $path, array $body): ?array
    {
        self::$lastError = '';
        $key = self::key();
        if ($key === '') {
            self::$lastError = 'Chua cau hinh khoa API Gemini.';
            return null;
        }
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $url = self::BASE . $path;
        $headers = ['Content-Type: application/json', 'x-goog-api-key: ' . $key];
        $verify = (bool) config('gemini.verify_ssl', true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,   // XAMPP tren Windows hay treo khi dung IPv6
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            ]);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                self::$lastError = 'Khong ket noi duoc Gemini: ' . $err;
                return null;
            }
        } else {
            $ctx = stream_context_create(['http' => [
                'method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $json,
                'timeout' => 30, 'ignore_errors' => true,
            ], 'ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify]]);
            $raw = @file_get_contents($url, false, $ctx);
            $code = 0;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $code = (int) $m[1];
            }
            if ($raw === false) {
                self::$lastError = 'Khong ket noi duoc Gemini (file_get_contents).';
                return null;
            }
        }

        $data = json_decode((string) $raw, true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $msg = is_array($data) ? (string) ($data['error']['message'] ?? '') : '';
            self::$lastError = 'Gemini loi HTTP ' . $code . ($msg !== '' ? ': ' . $msg : '');
            return null;
        }
        return $data;
    }
}
