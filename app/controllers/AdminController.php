<?php
declare(strict_types=1);

/**
 * /admin/* — admin role only (enforced by the route table in index.php).
 */
final class AdminController
{
    private Repository $repo;

    public function __construct()
    {
        $this->repo = new Repository();
    }

    /* ------------------------------------------------------------ overview */

    public function dashboard(): void
    {
        App::render('admin/dashboard', [
            'stats'     => $this->repo->stats(),
            'divisions' => $this->repo->divisions(false),
            'recent'    => $this->repo->recentlyUpdated(8),
            'popular'   => $this->repo->popular(8),
        ], ['title' => 'ผู้ดูแลระบบ', 'section' => 'admin', 'active' => 'admin']);
    }

    /* ---------------------------------------------------------- procedures */

    public function procedures(): void
    {
        $filters = [
            'division' => (int) ($_GET['division'] ?? 0),
            'section'  => (int) ($_GET['section'] ?? 0),
            'status'   => (string) ($_GET['status'] ?? ''),
            'q'        => trim((string) ($_GET['q'] ?? '')),
        ];

        App::render('admin/procedures', [
            'rows'      => $this->repo->adminProcedures($filters),
            'divisions' => $this->repo->divisions(false),
            'sections'  => $this->repo->sections($filters['division'] ?: null),
            'filters'   => $filters,
        ], ['title' => 'จัดการเรื่อง', 'section' => 'admin', 'active' => 'admin/procedures']);
    }

    /** @param array{id?:int} $params */
    public function procedureForm(array $params = []): void
    {
        $procedure = null;
        if (!empty($params['id'])) {
            $procedure = $this->repo->procedure($params['id']);
            if (!$procedure) {
                (new PublicController())->notFound();
                return;
            }
        }

        App::render('admin/procedure_form', [
            'procedure' => $procedure,
            'divisions' => $this->repo->divisions(false),
            'sections'  => $this->repo->sections(),
        ], [
            'title'   => $procedure ? 'แก้ไข: ' . $procedure['title'] : 'เพิ่มเรื่องใหม่',
            'section' => 'admin',
            'active'  => 'admin/procedures',
            'wide'    => true,
        ]);
    }

    /** @param array{id?:int} $params */
    public function saveProcedure(array $params = []): void
    {
        verify_csrf();

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash('กรุณากรอกชื่อเรื่อง', 'error');
            redirect_back('admin/procedures');
        }

        $steps = [];
        foreach ((array) ($_POST['step_detail'] ?? []) as $i => $detail) {
            $detail = trim((string) $detail);
            if ($detail === '') {
                continue;
            }
            $sub     = trim((string) (($_POST['step_sub'][$i] ?? '')));
            $steps[] = [
                'no'     => (int) ($_POST['step_no'][$i] ?? count($steps) + 1),
                'sub'    => $sub === '' ? null : (int) $sub,
                'detail' => $detail,
            ];
        }

        $flows = [];
        foreach ((array) ($_POST['flow_responsible'] ?? []) as $i => $responsible) {
            $flows[] = [
                'stage'       => trim((string) ($_POST['flow_stage'][$i] ?? '')),
                'responsible' => trim((string) $responsible),
                'evidence'    => trim((string) ($_POST['flow_evidence'][$i] ?? '')),
            ];
        }

        $sectionId  = (int) ($_POST['section_id'] ?? 0);
        $section    = $this->repo->section($sectionId);
        if (!$section) {
            flash('กรุณาเลือกงานที่สังกัด', 'error');
            redirect_back('admin/procedures');
        }

        $id = $this->repo->saveProcedure([
            'id'          => $params['id'] ?? null,
            'division_id' => (int) $section['division_id'],
            'section_id'  => $sectionId,
            'code'        => trim((string) ($_POST['code'] ?? '')),
            'title'       => $title,
            'purpose'     => trim((string) ($_POST['purpose'] ?? '')),
            'content'     => trim((string) ($_POST['content'] ?? '')),
            'page_start'  => (int) ($_POST['page_start'] ?? 0),
            'page_end'    => (int) ($_POST['page_end'] ?? 0),
            'status'      => (string) ($_POST['status'] ?? 'เผยแพร่'),
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'updated_by'  => Auth::id(),
            'steps'       => $steps,
            'flows'       => $flows,
        ]);

        $this->storeUploads($id);

        flash('บันทึกเรื่อง “' . $title . '” เรียบร้อยแล้ว');
        redirect('admin/procedures/' . $id . '/edit');
    }

    /** @param array{id:int} $params */
    public function deleteProcedure(array $params): void
    {
        verify_csrf();
        $this->repo->deleteProcedure($params['id']);
        flash('ลบเรื่องเรียบร้อยแล้ว');
        redirect('admin/procedures');
    }

    private function storeUploads(int $procedureId): void
    {
        $files = $_FILES['attachments'] ?? null;
        if (!$files || !is_array($files['name'])) {
            return;
        }

        $dir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

        foreach ($files['name'] as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                flash('ข้ามไฟล์ที่ไม่รองรับ: ' . $name, 'error');
                continue;
            }
            $stored = bin2hex(random_bytes(12)) . '.' . $ext;
            if (!move_uploaded_file($files['tmp_name'][$i], $dir . '/' . $stored)) {
                continue;
            }
            $this->repo->addAttachment(
                $procedureId,
                $stored,
                mb_substr((string) $name, 0, 255, 'UTF-8'),
                (string) ($files['type'][$i] ?? ''),
                (int) ($files['size'][$i] ?? 0)
            );
        }
    }

    /** @param array{id:int} $params */
    public function deleteAttachment(array $params): void
    {
        verify_csrf();
        $file = $this->repo->attachment($params['id']);
        if ($file) {
            @unlink(dirname(__DIR__, 2) . '/storage/uploads/' . $file['stored_name']);
            $this->repo->deleteAttachment($params['id']);
            flash('ลบไฟล์แนบแล้ว');
        }
        redirect_back('admin/procedures');
    }

    /* ------------------------------------------------------------ taxonomy */

    public function taxonomy(): void
    {
        App::render('admin/taxonomy', [
            'divisions' => $this->repo->divisions(false),
            'sections'  => $this->repo->sections(),
        ], ['title' => 'ฝ่ายและงาน', 'section' => 'admin', 'active' => 'admin/taxonomy']);
    }

    public function saveDivision(): void
    {
        verify_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('กรุณากรอกชื่อฝ่าย', 'error');
            redirect('admin/taxonomy');
        }
        $this->repo->saveDivision(
            (int) ($_POST['id'] ?? 0) ?: null,
            $name,
            trim((string) ($_POST['short_name'] ?? '')),
            trim((string) ($_POST['description'] ?? '')),
            (int) ($_POST['sort_order'] ?? 0),
            !empty($_POST['is_active'])
        );
        flash('บันทึกฝ่ายเรียบร้อยแล้ว');
        redirect('admin/taxonomy');
    }

    /** @param array{id:int} $params */
    public function deleteDivision(array $params): void
    {
        verify_csrf();
        $this->repo->deleteDivision($params['id']);
        flash('ลบฝ่ายและข้อมูลภายในทั้งหมดแล้ว');
        redirect('admin/taxonomy');
    }

    public function saveSection(): void
    {
        verify_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $div  = (int) ($_POST['division_id'] ?? 0);
        if ($name === '' || $div === 0) {
            flash('กรุณาเลือกฝ่ายและกรอกชื่องาน', 'error');
            redirect('admin/taxonomy');
        }
        $this->repo->saveSection(
            (int) ($_POST['id'] ?? 0) ?: null,
            $div,
            $name,
            trim((string) ($_POST['description'] ?? '')),
            (int) ($_POST['sort_order'] ?? 0),
            !empty($_POST['is_active'])
        );
        flash('บันทึกงานเรียบร้อยแล้ว');
        redirect('admin/taxonomy');
    }

    /** @param array{id:int} $params */
    public function deleteSection(array $params): void
    {
        verify_csrf();
        $this->repo->deleteSection($params['id']);
        flash('ลบงานและเรื่องภายในทั้งหมดแล้ว');
        redirect('admin/taxonomy');
    }

    /* --------------------------------------------------------- info pages */

    public function info(): void
    {
        App::render('admin/info', [
            'pages' => $this->repo->infoPages(false),
            'edit'  => isset($_GET['edit']) ? $this->repo->infoPage((int) $_GET['edit']) : null,
        ], ['title' => 'หน้าข้อมูลทั่วไป', 'section' => 'admin', 'active' => 'admin/info']);
    }

    public function saveInfo(): void
    {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash('กรุณากรอกชื่อหน้า', 'error');
            redirect('admin/info');
        }
        $this->repo->saveInfoPage(
            (int) ($_POST['id'] ?? 0) ?: null,
            $title,
            (string) ($_POST['body'] ?? ''),
            (int) ($_POST['sort_order'] ?? 0),
            !empty($_POST['is_active'])
        );
        flash('บันทึกหน้าข้อมูลเรียบร้อยแล้ว');
        redirect('admin/info');
    }

    /** @param array{id:int} $params */
    public function deleteInfo(array $params): void
    {
        verify_csrf();
        $this->repo->deleteInfoPage($params['id']);
        flash('ลบหน้าข้อมูลแล้ว');
        redirect('admin/info');
    }

    /* --------------------------------------------------------------- users */

    public function users(): void
    {
        App::render('admin/users', [
            'users' => $this->repo->users(),
            'edit'  => isset($_GET['edit']) ? $this->repo->user((int) $_GET['edit']) : null,
        ], ['title' => 'ผู้ใช้งาน', 'section' => 'admin', 'active' => 'admin/users']);
    }

    public function saveUser(): void
    {
        verify_csrf();

        $id       = (int) ($_POST['id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $name     = trim((string) ($_POST['name'] ?? ''));
        $role     = in_array($_POST['role'] ?? '', App::ROLES, true) ? (string) $_POST['role'] : 'เจ้าหน้าที่';
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $name === '') {
            flash('กรุณากรอกชื่อผู้ใช้และชื่อ-สกุล', 'error');
            redirect('admin/users');
        }
        if ($this->repo->usernameTaken($username, $id ?: null)) {
            flash('ชื่อผู้ใช้นี้ถูกใช้แล้ว', 'error');
            redirect('admin/users');
        }

        if ($id) {
            $this->repo->updateUser(
                $id, $name,
                trim((string) ($_POST['email'] ?? '')),
                trim((string) ($_POST['position'] ?? '')),
                $role,
                !empty($_POST['is_active'])
            );
            if ($password !== '') {
                if (mb_strlen($password, 'UTF-8') < 6) {
                    flash('รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร', 'error');
                    redirect('admin/users');
                }
                $this->repo->setPassword($id, $password);
            }
            flash('อัปเดตข้อมูลผู้ใช้แล้ว');
        } else {
            if (mb_strlen($password, 'UTF-8') < 6) {
                flash('รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร', 'error');
                redirect('admin/users');
            }
            $this->repo->createUser(
                $username, $name,
                trim((string) ($_POST['email'] ?? '')),
                trim((string) ($_POST['position'] ?? '')),
                $role, $password
            );
            flash('เพิ่มผู้ใช้ใหม่แล้ว');
        }

        redirect('admin/users');
    }

    /** @param array{id:int} $params */
    public function deleteUser(array $params): void
    {
        verify_csrf();
        if ($params['id'] === Auth::id()) {
            flash('ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้', 'error');
            redirect('admin/users');
        }
        $this->repo->deleteUser($params['id']);
        flash('ลบผู้ใช้แล้ว');
        redirect('admin/users');
    }

    /* -------------------------------------------------------------- import */

    public function importForm(): void
    {
        $path = Importer::sourcePath();
        App::render('admin/import', [
            'sourcePath'   => $path,
            'sourceExists' => is_file($path),
            'sourceSize'   => is_file($path) ? (int) filesize($path) : 0,
            'sourceTime'   => is_file($path) ? date('Y-m-d H:i:s', (int) filemtime($path)) : null,
            'stats'        => $this->repo->stats(),
        ], ['title' => 'นำเข้าข้อมูล', 'section' => 'admin', 'active' => 'admin/import']);
    }

    public function runImport(): void
    {
        verify_csrf();
        set_time_limit(180);

        try {
            $stats = (new Importer())->run();
            flash(sprintf(
                'นำเข้าสำเร็จ — เพิ่มใหม่ %d เรื่อง, อัปเดต %d เรื่อง, %d ขั้นตอน, %d แถวผังงาน',
                $stats['inserted'], $stats['updated'], $stats['steps'], $stats['flows']
            ));
        } catch (Throwable $e) {
            flash('นำเข้าไม่สำเร็จ: ' . $e->getMessage(), 'error');
        }

        redirect('admin/import');
    }
}
