<?php
declare(strict_types=1);

/**
 * Every SQL statement in the app lives here.
 */
final class Repository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    /* ================================================================ ฝ่าย */

    /** @return array<int,array<string,mixed>> */
    public function divisions(bool $activeOnly = true): array
    {
        $sql = 'SELECT d.*,
                       (SELECT COUNT(*) FROM sections s WHERE s.division_id = d.id) AS section_count,
                       (SELECT COUNT(*) FROM procedures p
                          WHERE p.division_id = d.id AND p.status = ?) AS procedure_count
                  FROM divisions d';
        if ($activeOnly) {
            $sql .= ' WHERE d.is_active = 1';
        }
        $sql .= ' ORDER BY d.sort_order, d.id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['เผยแพร่']);

        $rows = $stmt->fetchAll();
        foreach ($rows as $i => $row) {
            $rows[$i]['color'] = division_color($i);
        }
        return $rows;
    }

    /** @return array<string,mixed>|null */
    public function division(int $id): ?array
    {
        foreach ($this->divisions(false) as $d) {
            if ((int) $d['id'] === $id) {
                return $d;
            }
        }
        return null;
    }

    /* ================================================================= งาน */

    /** @return array<int,array<string,mixed>> */
    public function sections(?int $divisionId = null): array
    {
        $sql = 'SELECT s.*, d.name AS division_name,
                       (SELECT COUNT(*) FROM procedures p
                          WHERE p.section_id = s.id AND p.status = ?) AS procedure_count
                  FROM sections s
                  JOIN divisions d ON d.id = s.division_id';
        $args = ['เผยแพร่'];
        if ($divisionId !== null) {
            $sql   .= ' WHERE s.division_id = ?';
            $args[] = $divisionId;
        }
        $sql .= ' ORDER BY d.sort_order, s.sort_order, s.id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function section(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, d.name AS division_name, d.id AS division_id
               FROM sections s JOIN divisions d ON d.id = s.division_id
              WHERE s.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /* ========================================================== procedures */

    /** @return array<int,array<string,mixed>> */
    public function proceduresOf(int $sectionId, bool $publishedOnly = true): array
    {
        $sql  = 'SELECT * FROM procedures WHERE section_id = ?';
        $args = [$sectionId];
        if ($publishedOnly) {
            $sql   .= ' AND status = ?';
            $args[] = 'เผยแพร่';
        }
        $sql .= ' ORDER BY sort_order, page_start, id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function procedure(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS section_name, d.name AS division_name, d.id AS division_id
               FROM procedures p
               JOIN sections s ON s.id = p.section_id
               JOIN divisions d ON d.id = p.division_id
              WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['steps']       = $this->steps($id);
        $row['flows']       = $this->flows($id);
        $row['attachments'] = $this->attachments($id);
        return $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function steps(int $procedureId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM procedure_steps WHERE procedure_id = ? ORDER BY sort_order, id'
        );
        $stmt->execute([$procedureId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function flows(int $procedureId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM procedure_flows WHERE procedure_id = ? ORDER BY sort_order, id'
        );
        $stmt->execute([$procedureId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function attachments(int $procedureId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM attachments WHERE procedure_id = ? ORDER BY id'
        );
        $stmt->execute([$procedureId]);
        return $stmt->fetchAll();
    }

    /** Neighbouring procedures inside the same งาน, for prev/next links. */
    /** @return array{prev:?array<string,mixed>,next:?array<string,mixed>} */
    public function neighbours(array $procedure): array
    {
        $list = $this->proceduresOf((int) $procedure['section_id']);
        $prev = $next = null;
        foreach ($list as $i => $p) {
            if ((int) $p['id'] === (int) $procedure['id']) {
                $prev = $list[$i - 1] ?? null;
                $next = $list[$i + 1] ?? null;
                break;
            }
        }
        return ['prev' => $prev, 'next' => $next];
    }

    public function countView(int $id): void
    {
        $this->db->prepare('UPDATE procedures SET views = views + 1 WHERE id = ?')->execute([$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function popular(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.title, p.views, s.name AS section_name, d.name AS division_name
               FROM procedures p
               JOIN sections s ON s.id = p.section_id
               JOIN divisions d ON d.id = p.division_id
              WHERE p.status = ? AND p.views > 0
              ORDER BY p.views DESC, p.id
              LIMIT ' . max(1, $limit)
        );
        $stmt->execute(['เผยแพร่']);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function recentlyUpdated(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.title, p.updated_at, s.name AS section_name, d.name AS division_name
               FROM procedures p
               JOIN sections s ON s.id = p.section_id
               JOIN divisions d ON d.id = p.division_id
              WHERE p.status = ?
              ORDER BY p.updated_at DESC, p.id DESC
              LIMIT ' . max(1, $limit)
        );
        $stmt->execute(['เผยแพร่']);
        return $stmt->fetchAll();
    }

    /* ============================================================== search */

    /**
     * Thai text has no word breaks, so both the stored haystack and the query
     * are squeezed to bare characters (ManualParser::key) and matched with
     * LIKE. Every whitespace-separated term must match (AND).
     *
     * @param array{division?:int,section?:int} $filters
     * @return array{rows:array<int,array<string,mixed>>,total:int}
     */
    public function search(string $term, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = ['p.status = ?'];
        $args  = ['เผยแพร่'];

        foreach (preg_split('/\s+/u', trim($term)) ?: [] as $word) {
            $key = ManualParser::key($word);
            if ($key === '') {
                continue;
            }
            $where[] = 'p.search_text LIKE ?';
            $args[]  = '%' . $key . '%';
        }
        if (!empty($filters['division'])) {
            $where[] = 'p.division_id = ?';
            $args[]  = (int) $filters['division'];
        }
        if (!empty($filters['section'])) {
            $where[] = 'p.section_id = ?';
            $args[]  = (int) $filters['section'];
        }

        $clause = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM procedures p WHERE {$clause}");
        $count->execute($args);
        $total = (int) $count->fetchColumn();

        // Title hits rank above body-only hits.
        $titleKey = ManualParser::key($term);
        $sql = "SELECT p.id, p.title, p.purpose, p.content, p.views, p.page_start,
                       s.name AS section_name, s.id AS section_id,
                       d.name AS division_name, d.id AS division_id,
                       (p.search_text LIKE ?) AS any_hit,
                       (CASE WHEN ? <> '' AND p.title LIKE ? THEN 1 ELSE 0 END) AS title_hit
                  FROM procedures p
                  JOIN sections s ON s.id = p.section_id
                  JOIN divisions d ON d.id = p.division_id
                 WHERE {$clause}
                 ORDER BY title_hit DESC, p.views DESC, p.id
                 LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(
            ['%' . $titleKey . '%', $titleKey, '%' . trim($term) . '%'],
            $args
        ));

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public function logSearch(string $term, int $hits): void
    {
        $term = mb_substr(trim($term), 0, 150, 'UTF-8');
        if ($term === '') {
            return;
        }
        $this->db->prepare('INSERT INTO search_logs (term, hits) VALUES (?, ?)')->execute([$term, $hits]);
    }

    /** @return array<int,string> */
    public function popularSearches(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT term FROM search_logs
              WHERE hits > 0
              GROUP BY term
              ORDER BY COUNT(*) DESC, MAX(searched_at) DESC
              LIMIT ' . max(1, $limit)
        );
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'term');
    }

    /* =========================================================== favorites */

    /** @return array<int,array<string,mixed>> */
    public function favorites(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.title, f.created_at, s.name AS section_name, d.name AS division_name
               FROM favorites f
               JOIN procedures p ON p.id = f.procedure_id
               JOIN sections s ON s.id = p.section_id
               JOIN divisions d ON d.id = p.division_id
              WHERE f.user_id = ?
              ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function isFavorite(int $userId, int $procedureId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND procedure_id = ?');
        $stmt->execute([$userId, $procedureId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return bool the new state (true = saved) */
    public function toggleFavorite(int $userId, int $procedureId): bool
    {
        if ($this->isFavorite($userId, $procedureId)) {
            $this->db->prepare('DELETE FROM favorites WHERE user_id = ? AND procedure_id = ?')
                ->execute([$userId, $procedureId]);
            return false;
        }
        $this->db->prepare('INSERT INTO favorites (user_id, procedure_id) VALUES (?, ?)')
            ->execute([$userId, $procedureId]);
        return true;
    }

    /* =========================================================== info pages */

    /** @return array<int,array<string,mixed>> */
    public function infoPages(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM info_pages';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, id';
        return $this->db->query($sql)->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function infoPage(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM info_pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function saveInfoPage(?int $id, string $title, string $body, int $sortOrder, bool $active): int
    {
        if ($id) {
            $this->db->prepare(
                'UPDATE info_pages SET title = ?, body = ?, sort_order = ?, is_active = ? WHERE id = ?'
            )->execute([$title, $body, $sortOrder, (int) $active, $id]);
            return $id;
        }
        $this->db->prepare(
            'INSERT INTO info_pages (title, body, sort_order, is_active) VALUES (?, ?, ?, ?)'
        )->execute([$title, $body, $sortOrder, (int) $active]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteInfoPage(int $id): void
    {
        $this->db->prepare('DELETE FROM info_pages WHERE id = ?')->execute([$id]);
    }

    /* ================================================================ write */

    /**
     * Insert or update a procedure together with its steps and flow rows.
     *
     * @param array{
     *   id?:int, division_id:int, section_id:int, code?:string, title:string,
     *   purpose?:string, content?:string, page_start?:int, page_end?:int,
     *   status?:string, sort_order?:int, updated_by?:int|null,
     *   steps?:array<int,array{no:int,sub:int|null,detail:string}>,
     *   flows?:array<int,array{responsible:string,evidence:string}>
     * } $data
     */
    public function saveProcedure(array $data): int
    {
        $steps = $data['steps'] ?? null;
        $flows = $data['flows'] ?? null;

        $fields = [
            'division_id' => (int) $data['division_id'],
            'section_id'  => (int) $data['section_id'],
            'code'        => (string) ($data['code'] ?? ''),
            'title'       => trim((string) $data['title']),
            'purpose'     => (string) ($data['purpose'] ?? ''),
            'content'     => (string) ($data['content'] ?? ''),
            'page_start'  => (int) ($data['page_start'] ?? 0),
            'page_end'    => (int) ($data['page_end'] ?? 0),
            'status'      => in_array($data['status'] ?? '', App::STATUSES, true) ? $data['status'] : 'เผยแพร่',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'updated_by'  => $data['updated_by'] ?? null,
        ];
        $fields['search_text'] = $this->buildSearchText($fields, $steps ?? [], $flows ?? []);

        $this->db->beginTransaction();
        try {
            if (!empty($data['id'])) {
                $id  = (int) $data['id'];
                $set = implode(', ', array_map(fn(string $c): string => "{$c} = :{$c}", array_keys($fields)));
                $stmt = $this->db->prepare("UPDATE procedures SET {$set} WHERE id = :id");
                $stmt->execute($fields + ['id' => $id]);
            } else {
                $cols = implode(', ', array_keys($fields));
                $marks = implode(', ', array_map(fn(string $c): string => ':' . $c, array_keys($fields)));
                $this->db->prepare("INSERT INTO procedures ({$cols}) VALUES ({$marks})")->execute($fields);
                $id = (int) $this->db->lastInsertId();
            }

            if ($steps !== null) {
                $this->db->prepare('DELETE FROM procedure_steps WHERE procedure_id = ?')->execute([$id]);
                $stmt = $this->db->prepare(
                    'INSERT INTO procedure_steps (procedure_id, step_no, sub_no, detail, sort_order)
                     VALUES (?, ?, ?, ?, ?)'
                );
                foreach (array_values($steps) as $i => $s) {
                    $detail = trim((string) $s['detail']);
                    if ($detail === '') {
                        continue;
                    }
                    $stmt->execute([$id, (int) ($s['no'] ?? $i + 1), $s['sub'] ?? null, $detail, $i]);
                }
            }

            if ($flows !== null) {
                $this->db->prepare('DELETE FROM procedure_flows WHERE procedure_id = ?')->execute([$id]);
                $stmt = $this->db->prepare(
                    'INSERT INTO procedure_flows (procedure_id, stage, responsible, evidence, sort_order)
                     VALUES (?, ?, ?, ?, ?)'
                );
                foreach (array_values($flows) as $i => $f) {
                    $s = trim((string) ($f['stage'] ?? ''));
                    $r = trim((string) ($f['responsible'] ?? ''));
                    $e = trim((string) ($f['evidence'] ?? ''));
                    if ($s === '' && $r === '' && $e === '') {
                        continue;
                    }
                    $stmt->execute([
                        $id,
                        mb_substr($s, 0, 400, 'UTF-8'),
                        mb_substr($r, 0, 255, 'UTF-8'),
                        mb_substr($e, 0, 500, 'UTF-8'),
                        $i,
                    ]);
                }
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<int,array<string,mixed>> $steps @param array<int,array<string,mixed>> $flows */
    private function buildSearchText(array $fields, array $steps, array $flows): string
    {
        $parts = [$fields['title'], $fields['purpose'], $fields['content']];
        foreach ($steps as $s) {
            $parts[] = (string) ($s['detail'] ?? '');
        }
        foreach ($flows as $f) {
            $parts[] = (string) ($f['stage'] ?? '') . ' '
                . (string) ($f['responsible'] ?? '') . ' '
                . (string) ($f['evidence'] ?? '');
        }
        return ManualParser::key(implode(' ', $parts));
    }

    public function deleteProcedure(int $id): void
    {
        $this->db->prepare('DELETE FROM procedures WHERE id = ?')->execute([$id]);
    }

    public function saveDivision(?int $id, string $name, string $shortName, string $description, int $sortOrder, bool $active): int
    {
        if ($id) {
            $this->db->prepare(
                'UPDATE divisions SET name = ?, short_name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?'
            )->execute([$name, $shortName, $description, $sortOrder, (int) $active, $id]);
            return $id;
        }
        $this->db->prepare(
            'INSERT INTO divisions (name, short_name, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, $shortName, $description, $sortOrder, (int) $active]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteDivision(int $id): void
    {
        $this->db->prepare('DELETE FROM divisions WHERE id = ?')->execute([$id]);
    }

    public function saveSection(?int $id, int $divisionId, string $name, string $description, int $sortOrder, bool $active): int
    {
        if ($id) {
            $this->db->prepare(
                'UPDATE sections SET division_id = ?, name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?'
            )->execute([$divisionId, $name, $description, $sortOrder, (int) $active, $id]);
            // Keep procedures' denormalised division in step with their งาน.
            $this->db->prepare('UPDATE procedures SET division_id = ? WHERE section_id = ?')
                ->execute([$divisionId, $id]);
            return $id;
        }
        $this->db->prepare(
            'INSERT INTO sections (division_id, name, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
        )->execute([$divisionId, $name, $description, $sortOrder, (int) $active]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteSection(int $id): void
    {
        $this->db->prepare('DELETE FROM sections WHERE id = ?')->execute([$id]);
    }

    /* ========================================================== attachments */

    public function addAttachment(int $procedureId, string $stored, string $original, string $mime, int $size): void
    {
        $this->db->prepare(
            'INSERT INTO attachments (procedure_id, stored_name, original_name, mime, size_bytes)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$procedureId, $stored, $original, $mime, $size]);
    }

    /** @return array<string,mixed>|null */
    public function attachment(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM attachments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function deleteAttachment(int $id): void
    {
        $this->db->prepare('DELETE FROM attachments WHERE id = ?')->execute([$id]);
    }

    /* ================================================================ users */

    /** @return array<int,array<string,mixed>> */
    public function users(): array
    {
        return $this->db->query('SELECT * FROM users ORDER BY role, name')->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function user(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function usernameTaken(string $username, ?int $exceptId = null): bool
    {
        $sql  = 'SELECT 1 FROM users WHERE username = ?';
        $args = [$username];
        if ($exceptId) {
            $sql   .= ' AND id <> ?';
            $args[] = $exceptId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return (bool) $stmt->fetchColumn();
    }

    public function createUser(string $username, string $name, string $email, string $position, string $role, string $password): int
    {
        $this->db->prepare(
            'INSERT INTO users (username, name, email, position, role, password_hash)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$username, $name, $email, $position, $role, password_hash($password, PASSWORD_DEFAULT)]);
        return (int) $this->db->lastInsertId();
    }

    public function updateUser(int $id, string $name, string $email, string $position, string $role, bool $active): void
    {
        $this->db->prepare(
            'UPDATE users SET name = ?, email = ?, position = ?, role = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $email, $position, $role, (int) $active, $id]);
    }

    public function setPassword(int $id, string $password): void
    {
        $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function deleteUser(int $id): void
    {
        $this->db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    /* ============================================================= settings */

    /** @return array<string,string> */
    public function settings(): array
    {
        $out = [];
        foreach ($this->db->query('SELECT name, value FROM settings')->fetchAll() as $row) {
            $out[$row['name']] = (string) $row['value'];
        }
        return $out;
    }

    public function setSetting(string $name, string $value): void
    {
        $this->db->prepare(
            'INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        )->execute([$name, $value]);
    }

    /* ================================================================ stats */

    /** @return array<string,int> */
    public function stats(): array
    {
        return [
            'divisions'  => (int) $this->db->query('SELECT COUNT(*) FROM divisions')->fetchColumn(),
            'sections'   => (int) $this->db->query('SELECT COUNT(*) FROM sections')->fetchColumn(),
            'procedures' => (int) $this->db->query('SELECT COUNT(*) FROM procedures')->fetchColumn(),
            'published'  => (int) $this->db->query("SELECT COUNT(*) FROM procedures WHERE status = 'เผยแพร่'")->fetchColumn(),
            'steps'      => (int) $this->db->query('SELECT COUNT(*) FROM procedure_steps')->fetchColumn(),
            'users'      => (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'views'      => (int) $this->db->query('SELECT COALESCE(SUM(views),0) FROM procedures')->fetchColumn(),
        ];
    }

    /** Admin listing with optional filters. @return array<int,array<string,mixed>> */
    public function adminProcedures(array $filters = []): array
    {
        $where = ['1=1'];
        $args  = [];
        if (!empty($filters['division'])) {
            $where[] = 'p.division_id = ?';
            $args[]  = (int) $filters['division'];
        }
        if (!empty($filters['section'])) {
            $where[] = 'p.section_id = ?';
            $args[]  = (int) $filters['section'];
        }
        if (!empty($filters['q'])) {
            $where[] = 'p.search_text LIKE ?';
            $args[]  = '%' . ManualParser::key((string) $filters['q']) . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.status = ?';
            $args[]  = (string) $filters['status'];
        }

        $sql = 'SELECT p.*, s.name AS section_name, d.name AS division_name,
                       (SELECT COUNT(*) FROM procedure_steps st WHERE st.procedure_id = p.id) AS step_count
                  FROM procedures p
                  JOIN sections s ON s.id = p.section_id
                  JOIN divisions d ON d.id = p.division_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY d.sort_order, s.sort_order, p.sort_order, p.id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }
}
