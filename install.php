<?php
declare(strict_types=1);

/**
 * Form-based installer. DESTRUCTIVE: recreates every table in sql/schema.sql.
 * Delete or protect this file on a real deployment.
 *
 * CLI fallback: php install.php   (uses the existing config/config.php)
 */

require __DIR__ . '/app/App.php';
require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/ManualParser.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/Repository.php';
require __DIR__ . '/app/Importer.php';

const CONFIG_PATH = __DIR__ . '/config/config.php';

/**
 * @param array<string,mixed> $input
 * @return array{ok:bool,messages:array<int,string>,error:?string}
 */
function run_install(array $input): array
{
    $messages = [];

    $db = [
        'host'    => trim((string) ($input['db_host'] ?? '127.0.0.1')),
        'port'    => (int) ($input['db_port'] ?? 3306),
        'name'    => trim((string) ($input['db_name'] ?? 'rvc_man')),
        'user'    => trim((string) ($input['db_user'] ?? 'root')),
        'pass'    => (string) ($input['db_pass'] ?? ''),
        'charset' => 'utf8mb4',
    ];
    $app = [
        'name'    => trim((string) ($input['app_name'] ?? 'คู่มือการปฏิบัติงาน วิทยาลัยอาชีวศึกษาร้อยเอ็ด')),
        'short'   => 'คู่มือการปฏิบัติงาน',
        'college' => 'วิทยาลัยอาชีวศึกษาร้อยเอ็ด',
        'debug'   => false,
    ];

    $adminUser = trim((string) ($input['admin_user'] ?? 'admin'));
    $adminName = trim((string) ($input['admin_name'] ?? 'ผู้ดูแลระบบ'));
    $adminPass = (string) ($input['admin_pass'] ?? '');

    if ($adminUser === '' || mb_strlen($adminPass, 'UTF-8') < 6) {
        return ['ok' => false, 'messages' => [], 'error' => 'กรุณากรอกชื่อผู้ใช้ และรหัสผ่านอย่างน้อย 6 ตัวอักษร'];
    }

    try {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], $db['port']);
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $messages[] = 'เชื่อมต่อเซิร์ฟเวอร์ฐานข้อมูลสำเร็จ';

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '', $db['name'])
        ));
        $pdo->exec('USE `' . str_replace('`', '', $db['name']) . '`');
        $messages[] = 'สร้างฐานข้อมูล ' . $db['name'] . ' แล้ว';

        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('อ่าน sql/schema.sql ไม่ได้');
        }
        $pdo->exec($schema);
        $messages[] = 'สร้างตารางทั้งหมดแล้ว';

        // Write config before anything uses App::config().
        $export = "<?php\n/**\n * Local configuration — written by install.php.\n */\nreturn "
            . var_export(['db' => $db, 'app' => $app], true) . ";\n";
        if (file_put_contents(CONFIG_PATH, $export) === false) {
            throw new RuntimeException('เขียน config/config.php ไม่ได้ — ตรวจสอบสิทธิ์ของโฟลเดอร์ config');
        }
        $messages[] = 'บันทึก config/config.php แล้ว';

        $pdo->prepare(
            'INSERT INTO users (username, name, email, position, role, password_hash)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $adminUser, $adminName, trim((string) ($input['admin_email'] ?? '')),
            'ผู้ดูแลระบบ', 'ผู้ดูแลระบบ', password_hash($adminPass, PASSWORD_DEFAULT),
        ]);
        $messages[] = 'สร้างบัญชีผู้ดูแลระบบ "' . $adminUser . '" แล้ว';

        foreach ([
            'site_name'    => $app['name'],
            'college_name' => $app['college'],
            'contact'      => '115 ถนนสุริยเดชบำรุง ตำบลในเมือง อำเภอเมือง จังหวัดร้อยเอ็ด 45000 โทร. 043-511430',
            'website'      => 'www.rvc.ac.th',
        ] as $k => $v) {
            $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)')->execute([$k, $v]);
        }

        // Reload config so App/Database point at the new database, then import.
        App::boot();
        $stats = (new Importer())->run();
        $messages[] = sprintf(
            'นำเข้าคู่มือแล้ว: %d ฝ่าย, %d งาน, %d เรื่อง, %d ขั้นตอน, %d แถวผังงาน, %d หน้าข้อมูลทั่วไป',
            $stats['divisions'], $stats['sections'], $stats['inserted'] + $stats['updated'],
            $stats['steps'], $stats['flows'], $stats['info']
        );

        @file_put_contents(__DIR__ . '/config/installed.lock', date('c'));

        return ['ok' => true, 'messages' => $messages, 'error' => null];
    } catch (Throwable $e) {
        return ['ok' => false, 'messages' => $messages, 'error' => $e->getMessage()];
    }
}

/* ------------------------------------------------------------------ CLI */

if (PHP_SAPI === 'cli') {
    App::boot();
    $cfg    = App::config('db');
    $result = run_install([
        'db_host' => $cfg['host'], 'db_port' => $cfg['port'], 'db_name' => $cfg['name'],
        'db_user' => $cfg['user'], 'db_pass' => $cfg['pass'],
        'app_name' => App::name(),
        'admin_user' => 'admin', 'admin_name' => 'ผู้ดูแลระบบ',
        'admin_email' => 'admin@rvc.ac.th', 'admin_pass' => 'admin1234',
    ]);
    foreach ($result['messages'] as $m) {
        echo '  ✓ ', $m, PHP_EOL;
    }
    if (!$result['ok']) {
        fwrite(STDERR, '  ✗ ' . $result['error'] . PHP_EOL);
        exit(1);
    }
    echo PHP_EOL, 'ติดตั้งเสร็จสิ้น — เข้าสู่ระบบด้วย admin / admin1234', PHP_EOL;
    exit(0);
}

/* ------------------------------------------------------------------ web */

session_start();
$result = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $result = run_install($_POST);
}

$e = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old = static fn(string $k, string $d = ''): string => htmlspecialchars((string) ($_POST[$k] ?? $d), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ติดตั้งระบบคู่มือการปฏิบัติงาน</title>
<link rel="stylesheet" href="assets/app.css">
<style>
  body { background: var(--bg); }
  .wrap { max-width: 640px; margin: 0 auto; padding: 24px 16px 64px; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--shadow); }
  fieldset { border: 0; padding: 0; margin: 0 0 20px; }
  legend { font-weight: 700; margin-bottom: 10px; color: var(--primary-text); }
  label { display: block; font-size: .86rem; color: var(--muted); margin: 12px 0 4px; }
  input { width: 100%; padding: 11px 12px; border: 1px solid var(--border); border-radius: 10px;
          background: var(--surface-2); color: var(--text); font-size: 1rem; }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 520px) { .row { grid-template-columns: 1fr; } }
  .btn { width: 100%; margin-top: 20px; padding: 13px; border: 0; border-radius: 12px;
         background: var(--primary); color: var(--primary-fg); font-size: 1rem; font-weight: 600; cursor: pointer; }
  .msg { padding: 12px 14px; border-radius: 10px; margin-bottom: 8px; font-size: .92rem; }
  .ok { background: var(--ok-soft); color: var(--ok); }
  .err { background: var(--danger-soft); color: var(--danger); }
  .warn { background: var(--warn-soft); color: var(--warn); font-size: .9rem; }
</style>
</head>
<body class="app-root">
<div class="wrap">
  <h1 style="font-size:1.35rem;margin:8px 0 4px">ติดตั้งระบบคู่มือการปฏิบัติงาน</h1>
  <p style="color:var(--muted);margin:0 0 20px;font-size:.92rem">วิทยาลัยอาชีวศึกษาร้อยเอ็ด · PHP <?= PHP_VERSION ?></p>

  <?php if ($result !== null): ?>
    <div class="card" style="margin-bottom:16px">
      <?php foreach ($result['messages'] as $m): ?>
        <div class="msg ok">✓ <?= $e($m) ?></div>
      <?php endforeach; ?>
      <?php if ($result['error']): ?>
        <div class="msg err">✗ <?= $e($result['error']) ?></div>
      <?php else: ?>
        <p style="margin:16px 0 0">
          ติดตั้งเสร็จสิ้น — <a href="./" style="color:var(--primary-text)">เปิดใช้งานระบบ</a>
        </p>
        <div class="msg warn" style="margin-top:12px">
          เพื่อความปลอดภัย ควรลบไฟล์ <code>install.php</code> ออกหลังติดตั้งเสร็จ
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($result === null || !$result['ok']): ?>
  <form method="post" class="card">
    <div class="msg warn">การติดตั้งจะ <strong>ลบและสร้างตารางใหม่ทั้งหมด</strong> ข้อมูลเดิมในฐานข้อมูลนี้จะหายไป</div>

    <fieldset>
      <legend>ฐานข้อมูล MariaDB</legend>
      <div class="row">
        <div><label>โฮสต์</label><input name="db_host" value="<?= $old('db_host', '127.0.0.1') ?>"></div>
        <div><label>พอร์ต</label><input name="db_port" value="<?= $old('db_port', '3306') ?>"></div>
      </div>
      <label>ชื่อฐานข้อมูล</label><input name="db_name" value="<?= $old('db_name', 'rvc_man') ?>">
      <div class="row">
        <div><label>ผู้ใช้</label><input name="db_user" value="<?= $old('db_user', 'root') ?>"></div>
        <div><label>รหัสผ่าน</label><input type="password" name="db_pass" value="<?= $old('db_pass') ?>"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>บัญชีผู้ดูแลระบบ</legend>
      <div class="row">
        <div><label>ชื่อผู้ใช้</label><input name="admin_user" value="<?= $old('admin_user', 'admin') ?>" required></div>
        <div><label>รหัสผ่าน (อย่างน้อย 6 ตัว)</label><input type="password" name="admin_pass" required></div>
      </div>
      <label>ชื่อ-สกุล</label><input name="admin_name" value="<?= $old('admin_name', 'ผู้ดูแลระบบ') ?>">
      <label>อีเมล</label><input type="email" name="admin_email" value="<?= $old('admin_email', 'admin@rvc.ac.th') ?>">
    </fieldset>

    <fieldset>
      <legend>ชื่อระบบ</legend>
      <input name="app_name" value="<?= $old('app_name', 'คู่มือการปฏิบัติงาน วิทยาลัยอาชีวศึกษาร้อยเอ็ด') ?>">
    </fieldset>

    <button class="btn" type="submit">ติดตั้งและนำเข้าข้อมูลคู่มือ</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
