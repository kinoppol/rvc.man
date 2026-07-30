<?php
/**
 * Copy to config/config.php (gitignored) or let install.php write it.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'rvc_man',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'    => 'คู่มือการปฏิบัติงาน วิทยาลัยอาชีวศึกษาร้อยเอ็ด',
        'short'   => 'คู่มือการปฏิบัติงาน',
        'college' => 'วิทยาลัยอาชีวศึกษาร้อยเอ็ด',
        'debug'   => false,
    ],
];
