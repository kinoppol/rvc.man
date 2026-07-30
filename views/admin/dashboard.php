<?php
/**
 * @var array<string,int> $stats
 * @var array<int,array<string,mixed>> $divisions
 * @var array<int,array<string,mixed>> $recent
 * @var array<int,array<string,mixed>> $popular
 */
$cards = [
    ['เรื่องทั้งหมด', $stats['procedures'], 'list',  'admin/procedures'],
    ['เผยแพร่แล้ว',   $stats['published'],  'check', 'admin/procedures?status=' . rawurlencode('เผยแพร่')],
    ['งาน',           $stats['sections'],   'folder', 'admin/taxonomy'],
    ['ขั้นตอน',       $stats['steps'],      'grid',  'admin/procedures'],
    ['ผู้ใช้งาน',     $stats['users'],      'users', 'admin/users'],
    ['ยอดเปิดอ่าน',   $stats['views'],      'eye',   'admin/procedures'],
];
?>
<h1 class="page-title">ภาพรวมระบบ</h1>
<p class="page-sub">จัดการเนื้อหาคู่มือการปฏิบัติงาน</p>

<div class="grid grid--3">
  <?php foreach ($cards as [$label, $value, $ico, $link]): ?>
    <a class="tile" href="<?= h(url($link)) ?>">
      <span class="tile__icon"><?= icon($ico, 20) ?></span>
      <span>
        <span style="display:block;font-size:1.4rem;font-weight:700;line-height:1.2"><?= number_format($value) ?></span>
        <span class="tile__desc"><?= h($label) ?></span>
      </span>
    </a>
  <?php endforeach; ?>
</div>

<h2 class="section-title"><?= icon('plus', 18) ?> ทางลัด</h2>
<div class="doc-actions">
  <a class="btn btn--primary" href="<?= h(url('admin/procedures/new')) ?>"><?= icon('plus', 16) ?>เพิ่มเรื่องใหม่</a>
  <a class="btn" href="<?= h(url('admin/import')) ?>"><?= icon('refresh', 16) ?>นำเข้าจากไฟล์คู่มือ</a>
  <a class="btn" href="<?= h(url('admin/taxonomy')) ?>"><?= icon('folder', 16) ?>จัดการฝ่าย/งาน</a>
</div>

<h2 class="section-title"><?= icon('folder', 18) ?> จำนวนเรื่องแยกตามฝ่าย</h2>
<div class="card list">
  <?php foreach ($divisions as $d): ?>
    <a class="list__item" href="<?= h(url('admin/procedures?division=' . $d['id'])) ?>">
      <span class="tile__icon" style="--accent:<?= h($d['color']) ?>;width:34px;height:34px;border-radius:10px">
        <?= icon($d['icon'] ?: 'folder', 18) ?></span>
      <span class="list__body">
        <span class="list__title" style="display:block"><?= h($d['name']) ?></span>
        <span class="list__meta"><span><?= (int) $d['section_count'] ?> งาน</span></span>
      </span>
      <span class="chip"><?= (int) $d['procedure_count'] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="grid grid--2" style="margin-top:8px">
  <div>
    <h2 class="section-title"><?= icon('clock', 18) ?> แก้ไขล่าสุด</h2>
    <div class="card list">
      <?php foreach ($recent as $p): ?>
        <a class="list__item" href="<?= h(url('admin/procedures/' . $p['id'] . '/edit')) ?>">
          <span class="list__body">
            <span class="list__title" style="display:block"><?= h(excerpt($p['title'], 60)) ?></span>
            <span class="list__meta"><span><?= h(thai_date($p['updated_at'])) ?></span></span>
          </span>
          <span class="list__chev"><?= icon('edit', 16) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div>
    <h2 class="section-title"><?= icon('eye', 18) ?> เปิดดูบ่อยที่สุด</h2>
    <div class="card list">
      <?php foreach ($popular as $p): ?>
        <a class="list__item" href="<?= h(url('procedure/' . $p['id'])) ?>">
          <span class="list__body">
            <span class="list__title" style="display:block"><?= h(excerpt($p['title'], 60)) ?></span>
            <span class="list__meta"><span><?= (int) $p['views'] ?> ครั้ง</span></span>
          </span>
          <span class="list__chev"><?= icon('chev', 16) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
