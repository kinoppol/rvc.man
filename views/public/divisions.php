<?php
/**
 * @var array<int,array<string,mixed>> $divisions
 * @var array<int,array<string,mixed>> $infoPages
 */
?>
<h1 class="page-title">หมวดงานทั้งหมด</h1>
<p class="page-sub">เลือกฝ่ายเพื่อดูรายการงานและขั้นตอนการปฏิบัติงาน</p>

<div class="grid grid--2">
  <?php foreach ($divisions as $d): ?>
    <a class="tile" href="<?= h(url('division/' . $d['id'])) ?>" style="--accent:<?= h($d['color']) ?>">
      <span class="tile__icon"><?= icon($d['icon'] ?: 'folder', 22) ?></span>
      <span style="min-width:0">
        <span class="tile__name" style="display:block"><?= h($d['name']) ?></span>
        <span class="tile__desc" style="display:block"><?= h($d['description']) ?></span>
        <span class="tile__meta">
          <span><?= (int) $d['section_count'] ?> งาน</span>
          <span><?= (int) $d['procedure_count'] ?> เรื่อง</span>
        </span>
      </span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($infoPages): ?>
  <h2 class="section-title"><?= icon('info', 18) ?> ข้อมูลทั่วไปของวิทยาลัย</h2>
  <div class="card list">
    <?php foreach ($infoPages as $p): ?>
      <a class="list__item" href="<?= h(url('info/' . $p['id'])) ?>">
        <span class="list__body"><span class="list__title"><?= h($p['title']) ?></span></span>
        <span class="list__chev"><?= icon('chev', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
