<?php
/**
 * @var array<string,mixed> $section_row
 * @var array<int,array<string,mixed>> $procedures
 */
?>
<nav class="crumbs" aria-label="เส้นทาง">
  <a href="<?= h(url('')) ?>">หน้าแรก</a><?= icon('chev', 14) ?>
  <a href="<?= h(url('division/' . $section_row['division_id'])) ?>"><?= h($section_row['division_name']) ?></a>
  <?= icon('chev', 14) ?><span><?= h($section_row['name']) ?></span>
</nav>

<h1 class="page-title"><?= h($section_row['name']) ?></h1>
<p class="page-sub"><?= h($section_row['division_name']) ?> · <?= count($procedures) ?> เรื่อง</p>

<?php if (!$procedures): ?>
  <div class="empty"><?= icon('file', 40) ?><p>ยังไม่มีเรื่องในงานนี้</p></div>
<?php else: ?>
  <div class="card list">
    <?php foreach ($procedures as $i => $p): ?>
      <a class="list__item" href="<?= h(url('procedure/' . $p['id'])) ?>">
        <span class="list__num"><?= $i + 1 ?></span>
        <span class="list__body">
          <span class="list__title" style="display:block"><?= h($p['title']) ?></span>
          <span class="list__meta">
            <?php if ($p['page_start']): ?><span>หน้า <?= (int) $p['page_start'] ?></span><?php endif; ?>
            <span><?= (int) $p['views'] ?> ครั้ง</span>
          </span>
        </span>
        <span class="list__chev"><?= icon('chev', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
