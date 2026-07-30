<?php
/** @var array<int,array<string,mixed>> $items */
?>
<h1 class="page-title">รายการที่บันทึกไว้</h1>
<p class="page-sub">เรื่องที่คุณกดบันทึกไว้เพื่อเปิดดูได้เร็วขึ้น</p>

<?php if (!$items): ?>
  <div class="empty">
    <?= icon('bookmark', 40) ?>
    <p>ยังไม่มีรายการที่บันทึกไว้</p>
    <p style="font-size:.85rem">เปิดเรื่องที่ต้องการแล้วกดปุ่ม “บันทึกไว้”</p>
    <a class="btn btn--primary btn--sm" style="margin-top:12px" href="<?= h(url('divisions')) ?>">เลือกหมวดงาน</a>
  </div>
<?php else: ?>
  <div class="card list">
    <?php foreach ($items as $p): ?>
      <a class="list__item" href="<?= h(url('procedure/' . $p['id'])) ?>">
        <?= icon('bookmark', 18) ?>
        <span class="list__body">
          <span class="list__title" style="display:block"><?= h($p['title']) ?></span>
          <span class="list__meta">
            <span><?= h($p['division_name']) ?></span><span><?= h($p['section_name']) ?></span>
          </span>
        </span>
        <span class="list__chev"><?= icon('chev', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
