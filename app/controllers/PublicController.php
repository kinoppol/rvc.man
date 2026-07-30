<?php
declare(strict_types=1);

/**
 * The manual itself: browse, read, search, bookmark.
 */
final class PublicController
{
    private Repository $repo;

    public function __construct()
    {
        $this->repo = new Repository();
    }

    public function home(): void
    {
        App::render('public/home', [
            'divisions'   => $this->repo->divisions(),
            'popular'     => $this->repo->popular(5),
            'recent'      => $this->repo->recentlyUpdated(5),
            'suggestions' => $this->repo->popularSearches(6),
            'stats'       => $this->repo->stats(),
        ], ['title' => null, 'active' => 'home']);
    }

    public function divisions(): void
    {
        App::render('public/divisions', [
            'divisions' => $this->repo->divisions(),
            'infoPages' => $this->repo->infoPages(),
        ], ['title' => 'หมวดงานทั้งหมด', 'active' => 'browse']);
    }

    /** @param array{id:int} $params */
    public function division(array $params): void
    {
        $division = $this->repo->division($params['id']);
        if (!$division) {
            $this->notFound();
            return;
        }

        $groups = [];
        foreach ($this->repo->sections($params['id']) as $section) {
            $groups[] = [
                'section'    => $section,
                'procedures' => $this->repo->proceduresOf((int) $section['id']),
            ];
        }

        App::render('public/division', [
            'division' => $division,
            'groups'   => $groups,
        ], ['title' => $division['name'], 'active' => 'division-' . $division['id']]);
    }

    /** @param array{id:int} $params */
    public function section(array $params): void
    {
        $section = $this->repo->section($params['id']);
        if (!$section) {
            $this->notFound();
            return;
        }

        App::render('public/section', [
            'section_row' => $section,
            'procedures'  => $this->repo->proceduresOf($params['id']),
        ], ['title' => $section['name'], 'active' => 'division-' . $section['division_id']]);
    }

    /** @param array{id:int} $params */
    public function procedure(array $params): void
    {
        $procedure = $this->repo->procedure($params['id']);
        if (!$procedure || ($procedure['status'] !== 'เผยแพร่' && !Auth::isAdmin())) {
            $this->notFound();
            return;
        }

        // One view per procedure per session, so a reload is not a new read.
        $seen = $_SESSION['_seen'] ?? [];
        if (!in_array($params['id'], $seen, true)) {
            $this->repo->countView($params['id']);
            $seen[] = $params['id'];
            $_SESSION['_seen'] = array_slice($seen, -60);
        }

        App::render('public/procedure', [
            'procedure'  => $procedure,
            'neighbours' => $this->repo->neighbours($procedure),
            'isFavorite' => Auth::check() && $this->repo->isFavorite((int) Auth::id(), $params['id']),
        ], ['title' => $procedure['title'], 'active' => 'division-' . $procedure['division_id']]);
    }

    public function search(): void
    {
        $q        = trim((string) ($_GET['q'] ?? ''));
        $division = (int) ($_GET['division'] ?? 0);
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 20;

        $rows = [];
        $total = 0;
        if ($q !== '') {
            $result = $this->repo->search($q, ['division' => $division], $perPage, ($page - 1) * $perPage);
            $rows   = $result['rows'];
            $total  = $result['total'];
            if ($page === 1) {
                $this->repo->logSearch($q, $total);
            }
        }

        App::render('public/search', [
            'q'              => $q,
            'rows'           => $rows,
            'total'          => $total,
            'page'           => $page,
            'pages'          => (int) ceil($total / $perPage),
            'divisions'      => $this->repo->divisions(),
            'divisionFilter' => $division,
            'suggestions'    => $this->repo->popularSearches(8),
        ], ['title' => $q !== '' ? 'ค้นหา: ' . $q : 'ค้นหา', 'active' => 'search']);
    }

    /** @param array{id:int} $params */
    public function info(array $params): void
    {
        $page = $this->repo->infoPage($params['id']);
        if (!$page || (int) $page['is_active'] !== 1) {
            $this->notFound();
            return;
        }
        App::render('public/info', ['page' => $page], [
            'title' => $page['title'], 'active' => 'info-' . $page['id'],
        ]);
    }

    public function saved(): void
    {
        Auth::requireLogin();
        App::render('public/saved', [
            'items' => $this->repo->favorites((int) Auth::id()),
        ], ['title' => 'บันทึกไว้', 'active' => 'saved']);
    }

    /** @param array{id:int} $params */
    public function toggleFavorite(array $params): void
    {
        verify_csrf();
        Auth::requireLogin();
        $saved = $this->repo->toggleFavorite((int) Auth::id(), $params['id']);
        flash($saved ? 'บันทึกเรื่องนี้ไว้แล้ว' : 'นำออกจากรายการที่บันทึกไว้แล้ว');
        redirect_back('procedure/' . $params['id']);
    }

    /** @param array{id:int} $params */
    public function attachment(array $params): void
    {
        $file = $this->repo->attachment($params['id']);
        if (!$file) {
            $this->notFound();
            return;
        }
        $path = dirname(__DIR__, 2) . '/storage/uploads/' . $file['stored_name'];
        if (!is_file($path)) {
            $this->notFound();
            return;
        }

        header('Content-Type: ' . ($file['mime'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($file['original_name']));
        readfile($path);
        exit;
    }

    public function notFound(): void
    {
        http_response_code(404);
        App::render('public/not_found', [], ['title' => 'ไม่พบหน้าที่ต้องการ', 'active' => '']);
    }
}
