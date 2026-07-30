<?php
declare(strict_types=1);

/**
 * Re-import data/manual-source.md into an already installed database.
 * Usage: php bin/import.php [path-to-markdown]
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__);
require $root . '/app/App.php';
require $root . '/app/Database.php';
require $root . '/app/ManualParser.php';
require $root . '/app/helpers.php';
require $root . '/app/Repository.php';
require $root . '/app/Importer.php';

App::boot();

$path = $argv[1] ?? Importer::sourcePath();

try {
    $stats = (new Importer())->run($path);
} catch (Throwable $e) {
    fwrite(STDERR, 'นำเข้าไม่สำเร็จ: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

printf(
    "นำเข้าเรียบร้อย\n  ฝ่าย: %d\n  งาน: %d\n  เรื่องใหม่: %d\n  เรื่องที่อัปเดต: %d\n  ขั้นตอน: %d\n  ผังงาน: %d\n  หน้าข้อมูลทั่วไป: %d\n",
    $stats['divisions'], $stats['sections'], $stats['inserted'],
    $stats['updated'], $stats['steps'], $stats['flows'], $stats['info']
);
