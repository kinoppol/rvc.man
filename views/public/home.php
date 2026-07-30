<?php
/**
 * @var array<int,array<string,mixed>> $divisions
 * @var array<int,array<string,mixed>> $popular
 * @var array<int,array<string,mixed>> $recent
 * @var array<int,string> $suggestions
 * @var array<string,int> $stats
 */
?>
<section class="hero">
  <h1>คู่มือการปฏิบัติงาน</h1>
  <p>วิทยาลัยอาชีวศึกษาร้อยเอ็ด · ค้นหาขั้นตอนการปฏิบัติงานได้ทุกที่ ทุกอุปกรณ์</p>

  <form class="searchbar" action="<?= h(url('search')) ?>" method="get" role="search">
    <span class="searchbar__icon"><?= icon('search') ?></span>
    <input type="search" name="q" placeholder="ค้นหาเรื่อง เช่น การเบิกเงิน, ลาป่วย, ครูที่ปรึกษา"
           enterkeyhint="search" aria-label="ค้นหาคู่มือ">
    <button class="searchbar__go" type="submit">ค้นหา</button>
  </form>

  <div class="statrow">
    <div class="stat"><b><?= (int) $stats['published'] ?></b><span>เรื่อง</span></div>
    <div class="stat"><b><?= (int) $stats['sections'] ?></b><span>งาน</span></div>
    <div class="stat"><b><?= (int) $stats['steps'] ?></b><span>ขั้นตอน</span></div>
  </div>
</section>

<?php if ($suggestions): ?>
  <div class="chiprow" style="margin-bottom:6px">
    <?php foreach ($suggestions as $s): ?>
      <a class="chip chip--outline" href="<?= h(url('search?q=' . rawurlencode($s))) ?>"><?= h($s) ?></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2 class="section-title"><?= icon('grid', 18) ?> ฝ่ายงาน
  <a class="more" href="<?= h(url('divisions')) ?>">ดูทั้งหมด</a>
</h2>

<div class="grid grid--2">
  <?php foreach ($divisions as $d): ?>
    <a class="tile" href="<?= h(url('division/' . $d['id'])) ?>" style="--accent:<?= h($d['color']) ?>">
      <span class="tile__icon"><?= icon($d['icon'] ?: 'folder', 22) ?></span>
      <span style="min-width:0">
        <span class="tile__name" style="display:block"><?= h($d['name']) ?></span>
        <span class="tile__desc" style="display:block"><?= h(excerpt($d['description'], 70)) ?></span>
        <span class="tile__meta">
          <span><?= (int) $d['section_count'] ?> งาน</span>
          <span><?= (int) $d['procedure_count'] ?> เรื่อง</span>
        </span>
      </span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($popular): ?>
  <h2 class="section-title"><?= icon('eye', 18) ?> เรื่องที่เปิดดูบ่อย</h2>
  <div class="card list">
    <?php foreach ($popular as $i => $p): ?>
      <a class="list__item" href="<?= h(url('procedure/' . $p['id'])) ?>">
        <span class="list__num"><?= $i + 1 ?></span>
        <span class="list__body">
          <span class="list__title" style="display:block"><?= h($p['title']) ?></span>
          <span class="list__meta"><span><?= h($p['section_name']) ?></span><span><?= (int) $p['views'] ?> ครั้ง</span></span>
        </span>
        <span class="list__chev"><?= icon('chev', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($recent): ?>
  <h2 class="section-title"><?= icon('clock', 18) ?> ปรับปรุงล่าสุด</h2>
  <div class="card list">
    <?php foreach ($recent as $p): ?>
      <a class="list__item" href="<?= h(url('procedure/' . $p['id'])) ?>">
        <span class="list__body">
          <span class="list__title" style="display:block"><?= h($p['title']) ?></span>
          <span class="list__meta">
            <span><?= h($p['division_name']) ?></span>
            <span><?= h(thai_date($p['updated_at'])) ?></span>
          </span>
        </span>
        <span class="list__chev"><?= icon('chev', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
