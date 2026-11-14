<?php
declare(strict_types=1);

/* ==============================================================================
   CORE: XU LY CHU CHO CHATBOT (ham thuan, khong dung database)
   File    : core/ChatbotText.php
   Duoc dung boi: models/Chatbot.php
   Gom: bo dau tieng Viet, tach tu, do giong nhau cosine, cham diem tu khoa (BM25 rut gon),
        tron diem vector + tu khoa (buoc xep hang lai - ranking).
   ============================================================================== */
final class ChatbotText
{
    /** Tu hay gap, khong mang nghia khi tim kiem. Viet CO dau de khong nham "cho" (cho, tang) voi "cho" (con cho). */
    private const STOP_ACCENT = [
        'là', 'và', 'của', 'có', 'không', 'cho', 'tôi', 'mình', 'bạn', 'shop', 'nào', 'gì', 'thế', 'như', 'thì', 'để',
        'ở', 'mà', 'một', 'các', 'những', 'được', 'với', 'khi', 'bao', 'nhiêu', 'xin', 'hỏi', 'muốn', 'cần', 'biết',
        'này', 'kia', 'đó', 'vậy', 'à', 'ạ', 'nhé', 'nha', 'ơi', 'hộ', 'vui', 'lòng', 'giúp',
    ];

    /** Khi khach go khong dau: chi bo cac tu khong lam mat nghia tu khac (khong bo "cho", "ban", "long", "can", "do"). */
    private const STOP_PLAIN = [
        'la', 'va', 'cua', 'co', 'khong', 'toi', 'minh', 'shop', 'nao', 'gi', 'nhu', 'thi', 'de', 'ma', 'mot', 'cac',
        'nhung', 'duoc', 'voi', 'khi', 'bao', 'nhieu', 'xin', 'hoi', 'muon', 'biet', 'nay', 'kia', 'vay', 'nhe', 'nha',
        'oi', 'giup',
    ];

    /** Bo dau tieng Viet, chuyen chu thuong. "Chó Poodle" -> "cho poodle". */
    public static function fold(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        static $map = null;
        if ($map === null) {
            $groups = [
                'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
                'e' => 'èéẹẻẽêềếệểễ',
                'i' => 'ìíịỉĩ',
                'o' => 'òóọỏõôồốộổỗơờớợởỡ',
                'u' => 'ùúụủũưừứựửữ',
                'y' => 'ỳýỵỷỹ',
                'd' => 'đ',
            ];
            $map = [];
            foreach ($groups as $base => $chars) {
                foreach (preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                    $map[$ch] = $base;
                }
            }
        }
        return strtr($s, $map);
    }

    /**
     * Tach tu, bo tu dung va tu qua ngan. Giu lai so (vi du "3" thang, "2kg").
     * @return array[] moi phan tu [tu goc co dau, tu da bo dau]
     */
    public static function tokenPairs(string $s): array
    {
        $parts = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($s, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        // Cau go khong dau thi "cho" nhieu kha nang la con cho: giu lai. Cau co dau thi "cho" la gioi tu: bo.
        $hasMarks = mb_strtolower($s, 'UTF-8') !== self::fold($s);
        foreach ($parts as $raw) {
            $f = self::fold($raw);
            if (in_array($raw, self::STOP_ACCENT, true) && !($raw === 'cho' && !$hasMarks)) {
                continue;
            }
            if ($raw === $f && in_array($f, self::STOP_PLAIN, true)) {
                continue;
            }
            $f = preg_replace('/[^a-z0-9]+/', '', $f) ?? '';
            if ($f === '' || (strlen($f) < 2 && !ctype_digit($f))) {
                continue;
            }
            $out[] = [$raw, $f];
        }
        return $out;
    }

    /** Chi cac tu da bo dau (dung cho cho can truy van don gian). */
    public static function tokens(string $s): array
    {
        return array_map(fn($p) => $p[1], self::tokenPairs($s));
    }

    /** Chuan hoa vector ve do dai 1 (can khi Gemini tra vector rut gon chieu). */
    public static function normalize(array $v): array
    {
        $sum = 0.0;
        foreach ($v as $x) {
            $sum += $x * $x;
        }
        $n = sqrt($sum);
        if ($n <= 0.0) {
            return $v;
        }
        foreach ($v as $i => $x) {
            $v[$i] = $x / $n;
        }
        return $v;
    }

    /** Do giong nhau giua 2 vector (-1..1). Hai vector khac do dai thi coi nhu 0. */
    public static function cosine(array $a, array $b): float
    {
        $n = count($a);
        if ($n === 0 || $n !== count($b)) {
            return 0.0;
        }
        $dot = $na = $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $na += $x * $x;
            $nb += $y * $y;
        }
        if ($na <= 0.0 || $nb <= 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * Diem tu khoa 0..1 cho tung tai lieu (BM25 rut gon: trong so idf, tu trong tieu de nang gap doi).
     * Khach go CO dau ma tai lieu chi khop khi bo dau (vi du "chó" khop "chờ") thi chi tinh 40% diem,
     * vi nhieu kha nang la tu khac nghia.
     * @param array $query    cac tu cua cau hoi: chuoi, hoac ket qua cua tokenPairs()
     * @param array $docs     moi phan tu co 'title' va 'content'
     * @return float[]        cung chi so voi $docs
     */
    public static function keywordScores(array $query, array $docs): array
    {
        $pairs = [];
        foreach ($query as $q) {
            $pairs[] = is_array($q) ? $q : [$q, $q];
        }
        $scores = array_fill(0, count($docs), 0.0);
        if (!$pairs || !$docs) {
            return $scores;
        }
        $uniq = [];
        foreach ($pairs as [$raw, $f]) {
            $uniq[$f] = $raw;
        }
        $N = count($docs);
        $titleSets = $bodySets = $rawSets = [];
        $df = array_fill_keys(array_keys($uniq), 0);
        foreach ($docs as $i => $d) {
            $titleSets[$i] = array_flip(self::tokens((string) $d['title']));
            $bodyPairs = self::tokenPairs((string) $d['title'] . ' ' . (string) $d['content']);
            $bodySets[$i] = array_flip(array_column($bodyPairs, 1));
            $rawSets[$i] = array_flip(array_column($bodyPairs, 0));
            foreach ($uniq as $f => $raw) {
                if (isset($bodySets[$i][$f])) {
                    $df[$f]++;
                }
            }
        }
        $idf = [];
        $max = 0.0;
        foreach ($uniq as $f => $raw) {
            $idf[$f] = log(1 + ($N - $df[$f] + 0.5) / ($df[$f] + 0.5));
            $max += $idf[$f] * 2;
        }
        if ($max <= 0.0) {
            return $scores;
        }
        foreach ($docs as $i => $d) {
            $s = 0.0;
            foreach ($uniq as $f => $raw) {
                if (!isset($bodySets[$i][$f])) {
                    continue;
                }
                $w = ($raw !== $f && !isset($rawSets[$i][$raw])) ? 0.4 : 1.0;
                $s += $idf[$f] * (isset($titleSets[$i][$f]) ? 2 : 1) * $w;
            }
            $scores[$i] = min(1.0, $s / $max);
        }
        return $scores;
    }

    /** Doi do giong cosine (thuong 0.4-0.8) ve thang 0..1. */
    public static function cosineToUnit(float $c): float
    {
        return max(0.0, min(1.0, ($c - 0.40) / 0.40));
    }

    /**
     * Buoc xep hang lai: tron diem vector va diem tu khoa, cong them neu ten xuat hien nguyen van trong cau hoi.
     * Co vector: 60% vector + 40% tu khoa. Khong co vector: chi dung tu khoa.
     */
    public static function fuse(?float $vector, float $keyword, bool $nameInQuestion): float
    {
        $base = $vector === null ? $keyword : 0.6 * self::cosineToUnit($vector) + 0.4 * $keyword;
        return min(1.0, $base + ($nameInQuestion ? 0.25 : 0.0));
    }

    /** Cat noi dung dai thanh cac doan ~$size ky tu, ngat o cuoi cau, chong lan $overlap ky tu. */
    public static function chunk(string $text, int $size = 700, int $overlap = 120): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }
        if (mb_strlen($text) <= $size) {
            return [$text];
        }
        $chunks = [];
        $pos = 0;
        $len = mb_strlen($text);
        while ($pos < $len) {
            $piece = mb_substr($text, $pos, $size);
            if ($pos + $size < $len) {
                $cut = max(mb_strrpos($piece, '. ') ?: 0, mb_strrpos($piece, '! ') ?: 0, mb_strrpos($piece, '? ') ?: 0);
                if ($cut > $size * 0.5) {
                    $piece = mb_substr($piece, 0, $cut + 1);
                }
            }
            $chunks[] = trim($piece);
            $step = mb_strlen($piece) - $overlap;
            $pos += max($step, 1);
            if ($pos + $overlap >= $len && $pos < $len && mb_strlen(mb_substr($text, $pos)) <= $overlap) {
                break;
            }
        }
        return $chunks;
    }
}
