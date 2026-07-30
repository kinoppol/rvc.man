<?php
declare(strict_types=1);

/**
 * Dry-run the parser and print what it found — used to tune ManualParser
 * before importing. Usage: php bin/parse-check.php [--full]
 */
require dirname(__DIR__) . '/app/ManualParser.php';

$parser = ManualParser::fromFile(dirname(__DIR__) . '/data/manual-source.md');
$pages  = $parser->pages();
$procs  = $parser->procedures();
$front  = $parser->frontMatter();

printf("pages: %d | procedures: %d | front-matter chapters: %d\n\n", count($pages), count($procs), count($front));

$byDivision = [];
foreach ($procs as $p) {
    $byDivision[$p['division']][$p['section']][] = $p;
}
foreach ($byDivision as $div => $sections) {
    printf("== %s (%d งาน)\n", $div, count($sections));
    foreach ($sections as $sec => $list) {
        printf("   - %-45s %d เรื่อง\n", $sec, count($list));
    }
}

echo "\n--- sample ---\n";
foreach (array_slice($procs, 0, 400) as $i => $p) {
    if ($i % 40 !== 0) {
        continue;
    }
    printf(
        "\n[%d] p.%d-%d %s / %s\n    %s\n    purpose: %s\n    steps: %d, flow: %d\n",
        $i, $p['page_start'], $p['page_end'], $p['division'], $p['section'], $p['title'],
        mb_substr($p['purpose'], 0, 90, 'UTF-8') ?: '(none)',
        count($p['steps']), count($p['flow'])
    );
    foreach (array_slice($p['steps'], 0, 3) as $s) {
        printf("      %d. %s\n", $s['no'], mb_substr($s['detail'], 0, 90, 'UTF-8'));
    }
}

printf(
    "\nwith purpose: %d | with steps: %d | with flow: %d | empty content: %d\n",
    count(array_filter($procs, fn(array $p): bool => $p['purpose'] !== '')),
    count(array_filter($procs, fn(array $p): bool => $p['steps'] !== [])),
    count(array_filter($procs, fn(array $p): bool => $p['flow'] !== [])),
    count(array_filter($procs, fn(array $p): bool => trim($p['content']) === ''))
);

$noSteps = array_filter($procs, fn(array $p): bool => $p['steps'] === []);
printf("\nprocedures with no steps: %d\n", count($noSteps));
foreach (array_slice($noSteps, 0, 15) as $p) {
    printf("   p.%-4d %s / %s\n", $p['page_start'], $p['section'], $p['title']);
}

foreach ($front as $c) {
    printf("\nFRONT: %s (%d chars)\n", $c['title'], mb_strlen($c['body'], 'UTF-8'));
}
