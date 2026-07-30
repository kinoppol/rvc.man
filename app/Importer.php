<?php
declare(strict_types=1);

/**
 * Loads data/manual-source.md into the database.
 *
 * Idempotent: ฝ่าย/งาน are matched by name and procedures by (งาน, ชื่อเรื่อง),
 * so re-running refreshes the imported text without duplicating rows or
 * touching anything an admin added by hand.
 */
final class Importer
{
    public const SOURCE = 'data/manual-source.md';

    /** Short labels + icons for the four ฝ่าย. */
    private const DIVISION_META = [
        'ฝ่ายบริหารทรัพยากร'              => ['บริหารทรัพยากร', 'folder', 'สารบรรณ บุคลากร การเงิน พัสดุ อาคารสถานที่ ทะเบียน'],
        'ฝ่ายแผนงานและความร่วมมือ'        => ['แผนงานฯ', 'grid', 'แผนงบประมาณ ข้อมูลสารสนเทศ ความร่วมมือ วิจัย ประกันคุณภาพ'],
        'ฝ่ายพัฒนากิจการนักเรียนนักศึกษา' => ['พัฒนากิจการฯ', 'users', 'กิจกรรม ครูที่ปรึกษา ปกครอง แนะแนว สวัสดิการ โครงการพิเศษ'],
        'ฝ่ายวิชาการ'                     => ['วิชาการ', 'file', 'หลักสูตร วัดผล วิทยบริการ ทวิภาคี สื่อการเรียนการสอน'],
    ];

    private Repository $repo;
    private PDO $db;

    public function __construct()
    {
        $this->repo = new Repository();
        $this->db   = Database::pdo();
    }

    public static function sourcePath(): string
    {
        return dirname(__DIR__) . '/' . self::SOURCE;
    }

    /**
     * @return array{divisions:int,sections:int,inserted:int,updated:int,steps:int,flows:int,info:int}
     */
    public function run(?string $path = null): array
    {
        $path ??= self::sourcePath();
        if (!is_file($path)) {
            throw new RuntimeException('ไม่พบไฟล์ต้นฉบับ: ' . $path);
        }

        $parser = ManualParser::fromFile($path);
        $procs  = $parser->procedures();
        $front  = $parser->frontMatter();

        $stats = ['divisions' => 0, 'sections' => 0, 'inserted' => 0, 'updated' => 0,
                  'steps' => 0, 'flows' => 0, 'info' => 0];

        $divisionIds = [];
        foreach (ManualParser::DIVISIONS as $i => $name) {
            $divisionIds[$name] = $this->divisionId($name, $i);
            $stats['divisions']++;
        }

        $sectionIds = [];
        $sectionSeq = [];
        foreach ($procs as $p) {
            $divId = $divisionIds[$p['division']] ?? null;
            if ($divId === null) {
                continue;
            }
            $key = $divId . '|' . $p['section'];
            if (!isset($sectionIds[$key])) {
                $sectionSeq[$divId] = ($sectionSeq[$divId] ?? 0) + 1;
                $sectionIds[$key]   = $this->sectionId($divId, $p['section'], $sectionSeq[$divId]);
                $stats['sections']++;
            }
        }

        $order = [];
        foreach ($procs as $p) {
            $divId = $divisionIds[$p['division']] ?? null;
            if ($divId === null) {
                continue;
            }
            $secId = $sectionIds[$divId . '|' . $p['section']];
            $order[$secId] = ($order[$secId] ?? 0) + 1;

            $existing = $this->findProcedure($secId, $p['title']);
            $id = $this->repo->saveProcedure([
                'id'          => $existing,
                'division_id' => $divId,
                'section_id'  => $secId,
                'code'        => sprintf('P%03d', $p['page_start']),
                'title'       => mb_substr($p['title'], 0, 300, 'UTF-8'),
                'purpose'     => $p['purpose'],
                'content'     => $p['content'],
                'page_start'  => $p['page_start'],
                'page_end'    => $p['page_end'],
                'status'      => 'เผยแพร่',
                'sort_order'  => $order[$secId],
                'steps'       => $p['steps'],
                'flows'       => $p['flow'],
            ]);

            $existing ? $stats['updated']++ : $stats['inserted']++;
            $stats['steps'] += count($p['steps']);
            $stats['flows'] += count($p['flow']);
            unset($id);
        }

        foreach ($front as $i => $chapter) {
            $stmt = $this->db->prepare('SELECT id FROM info_pages WHERE title = ? LIMIT 1');
            $stmt->execute([$chapter['title']]);
            $id = $stmt->fetchColumn();
            $this->repo->saveInfoPage($id ? (int) $id : null, $chapter['title'], $chapter['body'], $i + 1, true);
            $stats['info']++;
        }

        return $stats;
    }

    private function divisionId(string $name, int $index): int
    {
        $stmt = $this->db->prepare('SELECT id FROM divisions WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        [$short, $icon, $desc] = self::DIVISION_META[$name] ?? [$name, 'folder', ''];
        $this->db->prepare(
            'INSERT INTO divisions (name, short_name, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, $short, $desc, $icon, $index + 1]);

        return (int) $this->db->lastInsertId();
    }

    private function sectionId(int $divisionId, string $name, int $order): int
    {
        $stmt = $this->db->prepare('SELECT id FROM sections WHERE division_id = ? AND name = ? LIMIT 1');
        $stmt->execute([$divisionId, $name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $this->db->prepare(
            'INSERT INTO sections (division_id, name, sort_order) VALUES (?, ?, ?)'
        )->execute([$divisionId, mb_substr($name, 0, 180, 'UTF-8'), $order]);

        return (int) $this->db->lastInsertId();
    }

    private function findProcedure(int $sectionId, string $title): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM procedures WHERE section_id = ? AND title = ? LIMIT 1'
        );
        $stmt->execute([$sectionId, mb_substr($title, 0, 300, 'UTF-8')]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }
}
