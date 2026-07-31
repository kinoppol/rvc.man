<?php
declare(strict_types=1);

/**
 * Parser for the source manual (data/manual-source.md).
 *
 * The source is a PDF→Markdown conversion of "คู่มือการปฏิบัติงาน
 * วิทยาลัยอาชีวศึกษาร้อยเอ็ด". It has no Markdown headings: structure has to be
 * recovered from the printed page furniture, which repeats for every procedure:
 *
 *   ฝ่ายบริหารทรัพยากร                 <- division, first line of the page
 *   งานบุคลากร   การประเมินข้าราชการ    <- section + procedure title
 *   | ... | ผู้รับผิดชอบ | หลักฐาน |     <- the flowchart, exported as junk tables
 *   ขั้นตอนการประเมิน...               <- the narrative
 *   1. ทำคำสั่งแต่งตั้งกรรมการ...        <- numbered steps
 *   24                                <- page-number footer
 *
 * The PDF's Thai is damaged in two systematic ways that we repair first
 * (see normalize()), otherwise nothing — search least of all — works.
 */
final class ManualParser
{
    /** The four ฝ่าย, in book order. */
    public const DIVISIONS = [
        'ฝ่ายบริหารทรัพยากร',
        'ฝ่ายแผนงานและความร่วมมือ',
        'ฝ่ายพัฒนากิจการนักเรียนนักศึกษา',
        'ฝ่ายวิชาการ',
    ];

    /**
     * Known งาน names, longest-first at match time so that e.g.
     * "งานบริหารงานทั่วไป" wins over "งานบริหาร".
     */
    public const SECTIONS = [
        // ฝ่ายบริหารทรัพยากร
        'งานบริหารงานทั่วไป', 'งานสารบรรณ', 'งานเอกสารการพิมพ์', 'งานบุคลากร',
        'งานประชาสัมพันธ์', 'งานการเงิน', 'งานการบัญชี', 'งานพัสดุ',
        'งานอาคารสถานที่', 'งานทะเบียน',
        // ฝ่ายแผนงานและความร่วมมือ
        'งานวางแผนและงบประมาณ', 'งานศูนย์ข้อมูลและสารสนเทศ', 'งานศูนย์ข้อมูลสารสนเทศ',
        'งานความร่วมมือ', 'งานวิจัย พัฒนา นวัตกรรมและสิ่งประดิษฐ์',
        'งานประกันคุณภาพและมาตรฐานการศึกษา', 'งานส่งเสริมผลิตผลการค้าและประกอบธุรกิจ',
        'งานฟาร์มและโรงงาน',
        // ฝ่ายพัฒนากิจการนักเรียนนักศึกษา
        'งานกิจกรรมนักเรียนนักศึกษา', 'งานครูที่ปรึกษา', 'งานปกครอง',
        'งานแนะแนวอาชีพและการจัดหางาน', 'งานสวัสดิการนักเรียนนักศึกษา',
        'งานโครงการพิเศษและบริการชุมชน',
        // ฝ่ายวิชาการ
        'งานพัฒนาหลักสูตรการเรียนการสอน', 'งานวัดผลและประเมินผล',
        'งานวิทยบริการและห้องสมุด', 'งานอาชีวศึกษาระบบทวิภาคี',
        'งานสื่อการเรียนการสอน', 'งานอาชีวศึกษาทวิภาคี', 'แผนกวิชา',
    ];

    /** Words the PDF font mangled beyond what the generic rules can fix. */
    private const FIXES = [
        'ฝุาย' => 'ฝ่าย', 'ปุวย' => 'ป่วย', 'แฟูม' => 'แฟ้ม', 'ผูู้' => 'ผู้',
        'เปน็' => 'เป็น', 'เบอรโ์' => 'เบอร์', 'พน นั' => 'พนัน',
        'ปูาย' => 'ป้าย', 'ขูอ' => 'ข้อ', 'ตูอง' => 'ต้อง', 'ไดู' => 'ได้',
        'นกั' => 'นัก', 'สงั' => 'สั่ง', 'ทงั้' => 'ทั้ง', 'ครงั้' => 'ครั้ง',
        'วนั' => 'วัน', 'จดั' => 'จัด', 'รบั' => 'รับ', 'ปฏบิ' => 'ปฏิบ',
        'งานักบ' => 'งานกับ', 'สนับสนนุ' => 'สนับสนุน', 'ปรบัปรุง' => 'ปรับปรุง',
    ];

    /** @var array<int,array{no:int,lines:array<int,string>}> */
    private array $pages = [];

    public function __construct(private string $text)
    {
    }

    public static function fromFile(string $path): self
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("อ่านไฟล์ต้นฉบับไม่ได้: {$path}");
        }
        return new self($raw);
    }

    /* ---------------------------------------------------------------- text */

    /**
     * Repair the two systematic PDF extraction faults, then tidy whitespace.
     *
     * 1. "ำ" is exported as a space + "า" ("ทำ" → "ท า"). Safe to reverse:
     *    Thai "า" can never legally start a word, so a space before it is
     *    always the artefact.
     * 2. The tone marks ่ and ้ are exported as the vowels ุ and ู when they
     *    sit above a following "า" ("ฝ่าย" → "ฝุาย"). Same argument: ุา / ูา
     *    is not a legal Thai sequence.
     * 3. A mark is sometimes emitted one position early, landing in front of
     *    the consonant it belongs to ("บันทึก" → "ับนทึก", "นักศึกษา" →
     *    "ันกศึกษา"). Only the orphan case is repaired — a mark with no
     *    consonant before it to sit on — because that is unambiguously wrong,
     *    whereas a mark that already has a consonant may well be correct
     *    ("ไม่รวม" must not become "ไมร่วม").
     */
    public static function normalize(string $s): string
    {
        // A damaged upload (truncated file, byte-mangling FTP transfer) leaves
        // half-finished UTF-8 sequences that MySQL rejects on insert. Drop them
        // here so every downstream string is valid UTF-8; bin/diag.php reports
        // where the damage is.
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
        }
        $s = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $s);
        $s = preg_replace('/([ก-ฮ][ัิีึืุู]?[่้๊๋]?)[ \t]+า/u', '$1ำ', $s) ?? $s;
        $s = str_replace(['ุา', 'ูา'], ['่า', '้า'], $s);

        // Orphan mark → move it onto the consonant that follows.
        $s = preg_replace('/(^|[^ก-ฮัิีึืุู็่้๊๋์])([ัิีึื็่้๊๋์])([ก-ฮ])/mu', '$1$3$2', $s) ?? $s;
        // Two stacked above-vowels: the second one belongs to the next consonant.
        $s = preg_replace('/([ัิีึื])([ัิีึื])([ก-ฮ])/u', '$1$3$2', $s) ?? $s;

        $s = strtr($s, self::FIXES);
        $s = preg_replace('/[ \t]+/u', ' ', $s) ?? $s;
        return $s;
    }

    /** Collapse a line to a comparable key (no spaces, no punctuation). */
    public static function key(string $s): string
    {
        return preg_replace('/[\s\.\-–—:;,\/()"\'“”‘’]+/u', '', self::normalize($s)) ?? $s;
    }

    /* --------------------------------------------------------------- pages */

    /**
     * Split the document on its printed page-number footers.
     *
     * @return array<int,array{no:int,lines:array<int,string>}>
     */
    public function pages(): array
    {
        if ($this->pages !== []) {
            return $this->pages;
        }

        $pages   = [];
        $buffer  = [];
        $lastNo  = 0;

        foreach (explode("\n", self::normalize($this->text)) as $line) {
            $line = rtrim($line);
            $t    = trim($line);

            $no = self::pageNumber($t);
            if ($no !== null && $no >= $lastNo - 5 && $no <= $lastNo + 30) {
                $pages[] = ['no' => $no, 'lines' => $buffer];
                $buffer  = [];
                $lastNo  = $no;
                continue;
            }
            $buffer[] = $line;
        }
        if ($buffer !== []) {
            $pages[] = ['no' => $lastNo + 1, 'lines' => $buffer];
        }

        return $this->pages = $pages;
    }

    /** Arabic or Thai numerals on a line of their own → the page number. */
    private static function pageNumber(string $t): ?int
    {
        if ($t === '' || mb_strlen($t, 'UTF-8') > 3) {
            return null;
        }
        $arabic = strtr($t, ['๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4',
                             '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9']);
        return preg_match('/^\d{1,3}$/', $arabic) ? (int) $arabic : null;
    }

    /* ---------------------------------------------------------- procedures */

    /**
     * Recover the procedure list.
     *
     * @return array<int,array{
     *     division:string, section:string, title:string,
     *     page_start:int, page_end:int, purpose:string, content:string,
     *     steps:array<int,array{no:int,detail:string}>,
     *     flow:array<int,array{responsible:string,evidence:string}>
     * }>
     */
    public function procedures(): array
    {
        $out     = [];
        $current = null;

        foreach ($this->pages() as $page) {
            $head = $this->readHeader($page['lines']);

            if ($head !== null) {
                $sameAsCurrent = $current !== null
                    && $current['division'] === $head['division']
                    && ($head['title'] === '' || self::key($current['title']) === self::key($head['title']));

                if (!$sameAsCurrent) {
                    if ($current !== null) {
                        $out[] = $current;
                    }
                    $current = [
                        'division'   => $head['division'],
                        'section'    => $head['section'],
                        'title'      => $head['title'],
                        'page_start' => $page['no'],
                        'page_end'   => $page['no'],
                        'body'       => [],
                    ];
                } elseif ($current['section'] === '' && $head['section'] !== '') {
                    $current['section'] = $head['section'];
                }
            }

            if ($current === null) {
                continue; // front matter — handled separately by frontMatter()
            }

            $current['page_end'] = $page['no'];
            foreach (array_slice($page['lines'], $head['consumed'] ?? 0) as $line) {
                $current['body'][] = $line;
            }
        }
        if ($current !== null) {
            $out[] = $current;
        }

        $procs = array_values(array_filter(array_map(
            fn(array $p): array => $this->finish($p),
            $out
        ), fn(array $p): bool => $p['title'] !== ''));

        return $this->inheritSections($procs);
    }

    /**
     * The book is ordered งาน by งาน, so a procedure whose header omitted the
     * งาน line belongs to the same งาน as the procedure printed before it.
     *
     * @param array<int,array<string,mixed>> $procs
     * @return array<int,array<string,mixed>>
     */
    private function inheritSections(array $procs): array
    {
        $last = [];
        foreach ($procs as $i => $p) {
            $div = (string) $p['division'];
            if ($p['section'] !== '') {
                $last[$div] = $p['section'];
                continue;
            }
            // A title that itself names a งาน is the best clue available.
            [$sec] = $this->splitSection((string) $p['title']);
            $procs[$i]['section'] = $sec !== '' ? $sec : ($last[$div] ?? 'งานทั่วไป');
            if ($sec !== '') {
                $last[$div] = $sec;
            }
        }
        return $procs;
    }

    /**
     * Page furniture that must never be mistaken for a งาน or a ชื่อเรื่อง.
     */
    private const HEAD_NOISE = [
        'ผู้รับผิดชอบ', 'หลักฐาน', 'ขั้นตอน', 'เริ่มต้น', 'สิ้นสุด', 'วัตถุประสงค์',
        'หน้าที่และความรับผิดชอบ', 'หน้าที่ความรับผิดชอบ', 'หน้าที่', 'ผังงาน', 'แผนภูมิ',
        'เอกสารที่เกี่ยวข้อง', 'ผู้รับผิดชอบหลักฐาน', 'งานและความรับผิดชอบ', 'ภาระงาน',
    ];

    /**
     * Read the ฝ่าย / งาน / ชื่อเรื่อง block at the top of a page.
     *
     * The three parts come in either order and any of them may be missing, so
     * the next few lines are scanned and classified rather than read
     * positionally.
     *
     * @param array<int,string> $lines
     * @return array{division:string,section:string,title:string,consumed:int}|null
     */
    private function readHeader(array $lines): ?array
    {
        $idx = null;
        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            $idx = $i;
            break;
        }
        if ($idx === null) {
            return null;
        }

        $division = $this->matchDivision(trim($lines[$idx]));
        if ($division === null) {
            return null;
        }

        $section  = '';
        $title    = '';
        $consumed = $idx + 1;

        for ($i = $idx + 1, $seen = 0; $i < count($lines) && $seen < 4; $i++) {
            $t = trim($lines[$i]);
            if ($t === '') {
                continue;
            }
            if (str_starts_with($t, '|') || $this->isNoise($t)) {
                break;
            }
            $seen++;

            // A repeated division line is page furniture, not content.
            if ($this->matchDivision($t) !== null) {
                $consumed = $i + 1;
                continue;
            }

            $t = preg_replace('/^ชื่อ(?=งาน)/u', '', $t) ?? $t;
            if (preg_match('/^ชื่อบุคลากร\s*/u', $t)) {
                $section = $section !== '' ? $section : 'งานบุคลากร';
                $t       = preg_replace('/^ชื่อบุคลากร\s*/u', '', $t) ?? $t;
            }
            if ($t === '' || $this->isNoise($t)) {
                $consumed = $i + 1;
                continue;
            }

            [$sec, $rest] = $this->splitSection($t);
            $rest = trim($rest);

            if ($sec !== '') {
                if ($section === '') {
                    $section = $sec;
                }
                if ($rest !== '' && $title === '' && !$this->isNoise($rest)) {
                    $title = $rest;
                }
            } elseif ($title === '') {
                $title = $t;
            }

            $consumed = $i + 1;

            if ($title !== '' && $section !== '') {
                break;
            }
        }

        if ($title === '' && $section !== '') {
            $title = $section; // งาน overview page
        }
        if ($title === '' || $this->isNoise($title)) {
            return null;
        }

        return ['division' => $division, 'section' => $section, 'title' => $title, 'consumed' => $consumed];
    }

    private function isNoise(string $line): bool
    {
        $key = self::key($line);
        if ($key === '' || mb_strlen($key, 'UTF-8') < 3) {
            return true;
        }
        // A numbered line is body copy from the page above, never a heading.
        if (preg_match('/^\d{1,2}\s*[\.\)]/u', trim($line))) {
            return true;
        }
        foreach (self::HEAD_NOISE as $n) {
            $nk = self::key($n);
            if ($key === $nk || (str_starts_with($key, $nk) && mb_strlen($key, 'UTF-8') <= mb_strlen($nk, 'UTF-8') + 4)) {
                return true;
            }
        }
        return false;
    }

    private function matchDivision(string $line): ?string
    {
        $key = self::key($line);
        foreach (self::DIVISIONS as $d) {
            $dk = self::key($d);
            if ($key === $dk || (str_starts_with($key, $dk) && mb_strlen($key, 'UTF-8') <= mb_strlen($dk, 'UTF-8') + 6)) {
                return $d;
            }
        }
        return null;
    }

    /** @return array{0:string,1:string} [section, remaining title] */
    private function splitSection(string $line): array
    {
        $sections = self::SECTIONS;
        usort($sections, fn(string $a, string $b): int => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        $key = self::key($line);
        foreach ($sections as $s) {
            $sk = self::key($s);
            if (str_starts_with($key, $sk)) {
                // Cut the same number of *characters* off the original line.
                $rest = $line;
                $need = mb_strlen($sk, 'UTF-8');
                $take = 0;
                for ($i = 0, $n = mb_strlen($line, 'UTF-8'); $i < $n && $need > 0; $i++) {
                    $ch = mb_substr($line, $i, 1, 'UTF-8');
                    $take++;
                    if (self::key($ch) !== '') {
                        $need--;
                    }
                }
                $rest = trim(mb_substr($line, $take, null, 'UTF-8'));
                return [$s, $rest];
            }
        }
        return ['', $line];
    }

    /**
     * Turn a collected procedure's raw body lines into structured fields.
     *
     * @param array{division:string,section:string,title:string,page_start:int,page_end:int,body:array<int,string>} $p
     */
    private function finish(array $p): array
    {
        $narrative = [];
        $flow      = [];
        $columns   = null;   // cell indices of ขั้นตอน / ผู้รับผิดชอบ / หลักฐาน

        foreach ($p['body'] as $line) {
            $t = trim($line);
            if ($t === '') {
                $narrative[] = '';
                continue;
            }
            if (str_starts_with($t, '|')) {
                $cells = $this->cells($t);
                if ($cells === null) {
                    continue;
                }
                $header = $this->headerColumns($cells);
                if ($header !== null) {
                    $columns = $header;
                    continue;
                }
                $row = $this->flowRow($cells, $columns);
                if ($row !== null) {
                    $flow[] = $row;
                }
                continue;
            }
            $narrative[] = $t;
        }

        $flow = $this->dedupeFlow($flow);
        $text = $this->paragraphs($narrative);

        return [
            'division'   => $p['division'],
            'section'    => $p['section'],
            'title'      => $this->cleanTitle($p['title']),
            'page_start' => $p['page_start'],
            'page_end'   => $p['page_end'],
            'purpose'    => $this->purpose($narrative),
            'content'    => $text,
            'steps'      => $this->steps($narrative),
            'flow'       => $flow,
        ];
    }

    private function cleanTitle(string $t): string
    {
        $t = preg_replace('/\s*\(.*?ต่อ.*?\)\s*$/u', '', $t) ?? $t;
        // Character-wise, not byte-wise: trim()'s list is bytes, and – / — share
        // their trailing bytes (0x93 / 0x94) with Thai ณ and ด, so a byte trim
        // eats the last byte of a title ending in those letters.
        $t = preg_replace('/^[\s\.\-–—:]+|[\s\.\-–—:]+$/u', '', $t) ?? $t;
        return trim(preg_replace('/\s{2,}/u', ' ', $t) ?? $t);
    }

    /** The "หน้าที่และความรับผิดชอบ" block, if the procedure has one. */
    private function purpose(array $lines): string
    {
        $buf   = [];
        $inside = false;
        foreach ($lines as $line) {
            if (!$inside) {
                if (preg_match('/^หน้าที่และความรับผิดชอบ/u', $line)) {
                    $inside = true;
                }
                continue;
            }
            if ($line === '' && $buf !== []) {
                break;
            }
            if (preg_match('/^(ขั้นตอน|ผังงาน|แผนภูมิ)/u', $line)) {
                break;
            }
            $buf[] = $line;
        }
        return trim(implode(' ', $buf));
    }

    /**
     * Numbered steps from the narrative.
     *
     * The flowchart pages leave stray numbered fragments behind, so when the
     * procedure has an explicit "ขั้นตอน…" heading only the text after it is
     * read. Continuation lines (no leading number) fold into the step above,
     * and "2. 1 …" — how the PDF renders 2.1 — becomes a sub-step.
     *
     * @param array<int,string> $lines
     * @return array<int,array{no:int,sub:int|null,detail:string}>
     */
    private function steps(array $lines): array
    {
        $start = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/^ขั้นตอน/u', $line)) {
                $start = $i + 1;
                break;
            }
        }
        if ($start !== null) {
            $lines = array_slice($lines, $start);
        }

        $steps = [];
        $open  = null;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(\d{1,2})\s*\.\s*(\d{1,2})[\s\.]+(.+)$/u', $line, $m)) {
                $steps[] = ['no' => (int) $m[1], 'sub' => (int) $m[2], 'detail' => trim($m[3])];
                $open    = array_key_last($steps);
                continue;
            }
            if (preg_match('/^(\d{1,2})\s*\.\s*(.+)$/u', $line, $m)) {
                $steps[] = ['no' => (int) $m[1], 'sub' => null, 'detail' => trim($m[2])];
                $open    = array_key_last($steps);
                continue;
            }
            if ($open !== null && !preg_match('/^(ฝ่าย|ขั้นตอน|หน้าที่และความ)/u', $line)) {
                $steps[$open]['detail'] .= ' ' . $line;
                continue;
            }
            $open = null;
        }

        foreach ($steps as $i => $s) {
            $steps[$i]['detail'] = trim(preg_replace('/\s{2,}/u', ' ', $s['detail']) ?? $s['detail']);
        }

        $steps = array_values(array_filter(
            $steps,
            fn(array $s): bool => mb_strlen($s['detail'], 'UTF-8') > 5
        ));

        return $start === null ? $this->longestRun($steps) : $steps;
    }

    /**
     * Without a "ขั้นตอน" heading to anchor on, keep only the longest
     * ascending run of top-level numbers — that is the real procedure list;
     * the rest is flowchart debris.
     *
     * @param array<int,array{no:int,sub:int|null,detail:string}> $steps
     * @return array<int,array{no:int,sub:int|null,detail:string}>
     */
    private function longestRun(array $steps): array
    {
        $best = [];
        $run  = [];
        $prev = 0;

        foreach ($steps as $s) {
            $no = $s['sub'] !== null ? $prev : $s['no'];
            if ($run !== [] && $s['sub'] === null && $no !== $prev + 1 && $no !== $prev) {
                if (count($run) > count($best)) {
                    $best = $run;
                }
                $run  = [];
                $prev = 0;
            }
            $run[] = $s;
            $prev  = $no;
        }
        return count($run) > count($best) ? $run : $best;
    }

    /**
     * Split a Markdown table row into its cells, preserving empty ones —
     * the column *position* is the only reliable signal in these tables.
     *
     * @return array<int,string>|null null for separator rows
     */
    private function cells(string $line): ?array
    {
        if (preg_match('/^\|[\s\-\|:]+\|?$/u', $line)) {
            return null;
        }
        return array_map('trim', explode('|', trim($line, '|')));
    }

    /**
     * Recognise the "ผู้รับผิดชอบ / หลักฐาน" header row and remember which
     * cell index each column sits at. The flowchart is re-emitted as many
     * small tables per page, all sharing the header's column layout.
     *
     * @param array<int,string> $cells
     * @return array{stage:?int,responsible:int,evidence:int}|null
     */
    private function headerColumns(array $cells): ?array
    {
        $responsible = null;
        $evidence    = null;
        $stage       = null;

        foreach ($cells as $i => $c) {
            $k = self::key($c);
            if ($k === self::key('ผู้รับผิดชอบ')) {
                $responsible = $i;
            } elseif ($k === self::key('หลักฐาน')) {
                $evidence = $i;
            } elseif ($k === self::key('ขั้นตอน')) {
                $stage = $i;
            }
        }

        if ($responsible === null || $evidence === null) {
            return null;
        }
        return ['stage' => $stage, 'responsible' => $responsible, 'evidence' => $evidence];
    }

    /**
     * @param array<int,string> $cells
     * @param array{stage:?int,responsible:int,evidence:int}|null $columns
     * @return array{stage:string,responsible:string,evidence:string}|null
     */
    private function flowRow(array $cells, ?array $columns): ?array
    {
        if ($columns === null) {
            return null; // no header seen yet — the row's columns are unknowable
        }

        // Read the exact header column. Neighbouring cells are *not* consulted:
        // the flowchart's arrows and captions live there and reading them in
        // produces convincing-looking nonsense.
        $pick = static function (?int $at) use ($cells): string {
            if ($at === null) {
                return '';
            }
            $c = trim($cells[$at] ?? '');
            // Bare numbering, single stray characters and rule fragments.
            if ($c === '' || preg_match('/^[\d\.\s\-]+$/u', $c) || mb_strlen(self::key($c), 'UTF-8') < 3) {
                return '';
            }
            return $c;
        };

        $stage       = $pick($columns['stage']);
        $responsible = $pick($columns['responsible']);
        $evidence    = $pick($columns['evidence']);

        if ($responsible === '' && $evidence === '') {
            return null;
        }
        return ['stage' => $stage, 'responsible' => $responsible, 'evidence' => $evidence];
    }

    /**
     * @param array<int,array{stage:string,responsible:string,evidence:string}> $flow
     * @return array<int,array{stage:string,responsible:string,evidence:string}>
     */
    private function dedupeFlow(array $flow): array
    {
        $seen = [];
        $out  = [];
        foreach ($flow as $row) {
            $k = self::key($row['stage'] . '|' . $row['responsible'] . '|' . $row['evidence']);
            if ($k === '' || isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[]    = $row;
        }
        return $out;
    }

    /** @param array<int,string> $lines */
    private function paragraphs(array $lines): string
    {
        $text = trim(implode("\n", $lines));
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        return $text;
    }

    /* -------------------------------------------------------- front matter */

    /**
     * The introductory chapters (วัตถุประสงค์, ประวัติวิทยาลัย, ระเบียบการแต่งกาย …)
     * that precede the first ฝ่าย page.
     *
     * @return array<int,array{title:string,body:string}>
     */
    public function frontMatter(): array
    {
        $chapters = [
            'วัตถุประสงค์ของการจัดทำคู่มือการปฏิบัติงาน',
            'ข้อมูลทั่วไปของสถานศึกษา',
            'ประวัติของวิทยาลัยอาชีวศึกษาร้อยเอ็ด',
            'ปรัชญา วิสัยทัศน์ พันธกิจ',
            'ระเบียบการแต่งกาย',
            'กฎระเบียบการเข้าปฏิบัติงานของลูกจ้างชั่วคราว',
        ];

        $lines = [];
        foreach ($this->pages() as $page) {
            if ($this->readHeader($page['lines']) !== null) {
                break;
            }
            foreach ($page['lines'] as $l) {
                $lines[] = trim($l);
            }
        }

        $out     = [];
        $current = null;
        foreach ($lines as $line) {
            foreach ($chapters as $c) {
                // Headings carry a printed number ("1.1 ข้อมูลทั่วไป…"), so match
                // the heading anywhere in a short line rather than at its start.
                $lk = self::key($line);
                if ($line !== '' && mb_strlen($lk, 'UTF-8') <= mb_strlen(self::key($c), 'UTF-8') + 8
                    && str_contains($lk, self::key($c))) {
                    if ($current !== null) {
                        $out[] = $current;
                    }
                    $current = ['title' => $c, 'body' => []];
                    continue 2;
                }
            }
            if ($current !== null) {
                $current['body'][] = $line;
            }
        }
        if ($current !== null) {
            $out[] = $current;
        }

        // A chapter title also appears in the table of contents, which yields a
        // stub — keep the longest body found for each title, in book order.
        $best = [];
        foreach ($out as $c) {
            $body = trim(preg_replace('/\n{3,}/u', "\n\n", implode("\n", $c['body'])) ?? '');
            if ($body === '') {
                continue;
            }
            if (!isset($best[$c['title']]) || mb_strlen($body, 'UTF-8') > mb_strlen($best[$c['title']], 'UTF-8')) {
                $best[$c['title']] = $body;
            }
        }

        $result = [];
        foreach ($chapters as $title) {
            if (isset($best[$title])) {
                $result[] = ['title' => $title, 'body' => $best[$title]];
            }
        }
        return $result;
    }
}
