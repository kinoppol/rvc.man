<?php
/**
 * @var array<string,mixed> $division
 * @var array<int,array{section:array<string,mixed>,procedures:array<int,array<string,mixed>>}> $groups
 */
?>
<nav class="crumbs" aria-label="เส้นทาง">
  <a href="<?= h(url('')) ?>">หน้าแรก</a><?= icon('chev', 14) ?>
  <a href="<?= h(url('divisions')) ?>">หมวดงาน</a><?= icon('chev', 14) ?>
  <span><?= h($division['short_name'] ?: $division['name']) ?></span>
</nav>

<h1 class="page-title"><?= h($division['name']) ?></h1>
<p class="page-sub"><?= h($division['description']) ?></p>

<?php if (!$groups): ?>
  <div class="empty"><?= icon('folder', 40) ?><p>ยังไม่มีข้อมูลในฝ่ายนี้</p></div>
<?php endif; ?>

<?php foreach ($groups as $i => $g): ?>
  <details class="acc" <?= $i === 0 ? 'open' : '' ?>>
    <summary class="acc__head">
      <span class="list__num"><?= $i + 1 ?></span>
      <h3><?= h($g['section']['name']) ?></h3>
      <span class="chip"><?= count($g['procedures']) ?></span>
      <span class="chev"><?= icon('chev', 18) ?></span>
    </summary>
    <div class="acc__body list">
      <?php foreach ($g['procedures'] as $p): ?>
        <a class="list__item" href="<?= h(url('procedure/' . $p['id'])) ?>">
          <span class="list__body">
            <span class="list__title" style="display:block"><?= h($p['title']) ?></span>
            <?php if ($p['purpose']): ?>
              <span class="list__meta"><span><?= h(excerpt($p['purpose'], 80)) ?></span></span>
            <?php endif; ?>
          </span>
          <span class="list__chev"><?= icon('chev', 18) ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$g['procedures']): ?>
        <div style="padding:14px 16px;color:var(--muted);font-size:.88rem">ยังไม่มีเรื่องในงานนี้</div>
      <?php endif; ?>
    </div>
  </details>
<?php endforeach; ?>
