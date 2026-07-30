<?php
/**
 * @var string $sourcePath
 * @var bool $sourceExists
 * @var int $sourceSize
 * @var string|null $sourceTime
 * @var array<string,int> $stats
 */
?>
<h1 class="page-title">นำเข้าข้อมูลจากไฟล์คู่มือ</h1>
<p class="page-sub">อ่านไฟล์ต้นฉบับแล้วปรับปรุงฝ่าย งาน เรื่อง และขั้นตอนในฐานข้อมูล</p>

<div class="card card--pad" style="margin-bottom:16px">
  <div class="list__meta" style="margin-bottom:10px"><span>ไฟล์ต้นฉบับ</span></div>
  <p style="margin:0 0 6px;font-family:ui-monospace,monospace;font-size:.82rem;word-break:break-all">
    <?= h($sourcePath) ?></p>
  <?php if ($sourceExists): ?>
    <div class="chiprow">
      <span class="chip chip--ok"><?= icon('check', 14) ?>พบไฟล์</span>
      <span class="chip"><?= h(human_size($sourceSize)) ?></span>
      <span class="chip">แก้ไขล่าสุด <?= h(thai_date($sourceTime)) ?></span>
    </div>
  <?php else: ?>
    <div class="alert alert--error"><?= icon('info', 18) ?>
      <span>ไม่พบไฟล์ต้นฉบับ — วางไฟล์ Markdown ไว้ที่ <code>data/manual-source.md</code></span></div>
  <?php endif; ?>
</div>

<div class="alert alert--info">
  <?= icon('info', 18) ?>
  <span>การนำเข้าจะ <strong>ไม่ลบ</strong> ข้อมูลเดิม — เรื่องที่มีชื่อซ้ำในงานเดียวกันจะถูกปรับปรุงทับ
    ส่วนขั้นตอนและผังงานของเรื่องนั้นจะถูกสร้างใหม่ทั้งหมดจากไฟล์ต้นฉบับ</span>
</div>

<div class="card card--pad" style="margin-bottom:16px">
  <div class="grid grid--3">
    <div><b style="font-size:1.3rem"><?= number_format($stats['procedures']) ?></b><div class="tile__desc">เรื่องในระบบ</div></div>
    <div><b style="font-size:1.3rem"><?= number_format($stats['sections']) ?></b><div class="tile__desc">งาน</div></div>
    <div><b style="font-size:1.3rem"><?= number_format($stats['steps']) ?></b><div class="tile__desc">ขั้นตอน</div></div>
  </div>
</div>

<form method="post" action="<?= h(url('admin/import')) ?>"
      data-confirm="เริ่มนำเข้าข้อมูลจากไฟล์ต้นฉบับ?">
  <?= csrf_field() ?>
  <button class="btn btn--primary btn--block" type="submit" <?= $sourceExists ? '' : 'disabled' ?>>
    <?= icon('refresh', 18) ?>เริ่มนำเข้าข้อมูล
  </button>
</form>

<p style="font-size:.83rem;color:var(--muted);margin-top:14px">
  หรือรันจากบรรทัดคำสั่ง: <code>php bin/import.php</code>
</p>
