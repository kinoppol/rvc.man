<?php
declare(strict_types=1);

/**
 * ตัวตรวจสอบสภาพแวดล้อมก่อน/หลังติดตั้ง — ใช้หาสาเหตุเมื่อ install.php ล้มเหลว
 * บนเซิร์ฟเวอร์จริงแต่ผ่านบน localhost.
 *
 * เรียกผ่านเว็บ: /diagnose.php   หรือ CLI: php diagnose.php
 * ลบไฟล์นี้ทิ้งเมื่อใช้เสร็จ (เช่นเดียวกับ install.php)
 */

require __DIR__ . '/app/App.php';
require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/ManualParser.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

$line = static function (string $s = ''): void {
    echo $s, PHP_EOL;
};
$ok   = static fn(string $s): string => '  [OK]   ' . $s;
$bad  = static fn(string $s): string => '  [FAIL] ' . $s;
$info = static fn(string $s): string => '  ....   ' . $s;

$line('=== 1. PHP ===');
$line($info('PHP ' . PHP_VERSION . ' / ' . PHP_SAPI));
foreach (['mbstring', 'pcre', 'pdo_mysql', 'json'] as $ext) {
    $line(extension_loaded($ext) ? $ok("extension {$ext}") : $bad("ไม่มี extension {$ext}"));
}
$line($info('PCRE ' . (defined('PCRE_VERSION') ? PCRE_VERSION : '?')
    . ' / utf8 support: ' . (preg_match('/^ก$/u', 'ก') === 1 ? 'yes' : 'NO')));
$line($info('pcre.backtrack_limit=' . ini_get('pcre.backtrack_limit')
    . ' pcre.jit=' . ini_get('pcre.jit')
    . ' memory_limit=' . ini_get('memory_limit')));
$line($info('default_charset=' . ini_get('default_charset')
    . ' mbstring.internal_encoding=' . (ini_get('mbstring.internal_encoding') ?: '(default)')));
$line();

$line('=== 2. ไฟล์ต้นฉบับ data/manual-source.md ===');
$src = __DIR__ . '/data/manual-source.md';
$raw = is_readable($src) ? (string) file_get_contents($src) : null;
if ($raw === null) {
    $line($bad('อ่านไฟล์ไม่ได้: ' . $src));
} else {
    $line($info('ขนาด ' . number_format(strlen($raw)) . ' bytes (ต้นฉบับที่ถูกต้อง = 1,092,453 bytes)'));
    $line($info('md5 ' . md5($raw) . ' (ต้นฉบับที่ถูกต้อง = 0988dfbf738ac3b2707e0328eebff846)'));
    $line(strlen($raw) === 1092453 && md5($raw) === '0988dfbf738ac3b2707e0328eebff846'
        ? $ok('ไฟล์ตรงกับต้นฉบับ')
        : $bad('ไฟล์ไม่ตรงกับต้นฉบับ — อัปโหลดใหม่แบบ binary'));

    if (mb_check_encoding($raw, 'UTF-8')) {
        $line($ok('เป็น UTF-8 ที่ถูกต้องทั้งไฟล์'));
    } else {
        $line($bad('มีไบต์ที่ไม่ใช่ UTF-8 ในไฟล์'));
        // หาไบต์เสียตัวแรกด้วยการไล่แบ่งครึ่ง
        $lo = 0;
        $hi = strlen($raw);
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            if (mb_check_encoding(substr($raw, 0, $mid), 'UTF-8')) {
                $lo = $mid + 1;
            } else {
                $hi = $mid;
            }
        }
        $at  = max(0, $lo - 1);
        $ctx = substr($raw, max(0, $at - 40), 80);
        $line($info('ไบต์เสียตัวแรกที่ offset ~' . $at . ' (บรรทัดที่ ~'
            . (substr_count(substr($raw, 0, $at), "\n") + 1) . ')'));
        $line($info('hex รอบ ๆ: ' . wordwrap(bin2hex($ctx), 64, "\n           ", true)));
    }
}
$line();

$line('=== 3. ผลการแยกโครงสร้าง (parser) ===');
if ($raw !== null) {
    try {
        $parser = new ManualParser($raw);
        $procs  = $parser->procedures();
        $divs   = [];
        $secs   = [];
        $steps  = 0;
        $badStr = [];
        foreach ($procs as $i => $p) {
            $divs[$p['division']] = true;
            $secs[$p['division'] . '|' . $p['section']] = true;
            $steps += count($p['steps']);
            foreach (['division', 'section', 'title', 'purpose', 'content'] as $f) {
                if (!mb_check_encoding((string) $p[$f], 'UTF-8')) {
                    $badStr[] = "เรื่องที่ {$i} field {$f} หน้า {$p['page_start']}";
                }
            }
        }
        $line($info(sprintf('%d ฝ่าย / %d งาน / %d เรื่อง / %d ขั้นตอน (คาดหวัง 4 / 27 / 122 / ~1354)',
            count($divs), count($secs), count($procs), $steps)));
        $line($badStr === []
            ? $ok('ทุกข้อความที่จะบันทึกเป็น UTF-8 ที่ถูกต้อง')
            : $bad('พบข้อความ UTF-8 เสีย: ' . implode(', ', array_slice($badStr, 0, 10))));
    } catch (Throwable $e) {
        $line($bad('parser ล้มเหลว: ' . $e->getMessage()));
    }
}
$line();

$line('=== 4. ฐานข้อมูล ===');
try {
    App::boot();
    $cfg = App::config('db');
    $line($info(sprintf('%s:%d db=%s user=%s charset=%s',
        $cfg['host'], $cfg['port'], $cfg['name'], $cfg['user'], $cfg['charset'] ?? '(ไม่ได้ตั้ง)')));
    $line(($cfg['charset'] ?? '') === 'utf8mb4'
        ? $ok('config charset = utf8mb4')
        : $bad('config charset ไม่ใช่ utf8mb4 — แก้ config/config.php'));

    $pdo = Database::pdo();
    $line($info('MariaDB/MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn()));
    foreach ($pdo->query("SHOW VARIABLES WHERE Variable_name IN
        ('character_set_client','character_set_connection','character_set_results',
         'character_set_server','character_set_database','collation_connection','sql_mode')") as $r) {
        $line($info($r['Variable_name'] . ' = ' . $r['Value']));
    }

    $cols = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND CHARACTER_SET_NAME IS NOT NULL
          AND CHARACTER_SET_NAME <> 'utf8mb4'")->fetchAll();
    $line($cols === []
        ? $ok('ทุกคอลัมน์ข้อความเป็น utf8mb4')
        : $bad('คอลัมน์ที่ไม่ใช่ utf8mb4: ' . implode(', ', array_map(
            static fn(array $c): string => "{$c['TABLE_NAME']}.{$c['COLUMN_NAME']}={$c['CHARACTER_SET_NAME']}",
            $cols))));

    // เขียน–อ่านข้อความไทยจริงผ่าน prepared statement
    $pdo->exec('CREATE TEMPORARY TABLE _diag_utf8 (v VARCHAR(64)) '
        . 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $probe = 'ทดสอบภาษาไทย ๙';
    $pdo->prepare('INSERT INTO _diag_utf8 (v) VALUES (?)')->execute([$probe]);
    $back = (string) $pdo->query('SELECT v FROM _diag_utf8')->fetchColumn();
    $line($back === $probe
        ? $ok('เขียน/อ่านข้อความไทยผ่าน PDO ได้ถูกต้อง')
        : $bad('ข้อความไทยเพี้ยนเมื่อ round-trip: ' . bin2hex($back)));
} catch (Throwable $e) {
    $line($bad('ฐานข้อมูล: ' . $e->getMessage()));
}
$line();
$line('เสร็จสิ้น — ลบ diagnose.php ทิ้งเมื่อใช้เสร็จ');
