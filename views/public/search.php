<?php
/**
 * @var string $q
 * @var array<int,array<string,mixed>> $rows
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<int,array<string,mixed>> $divisions
 * @var int $divisionFilter
 * @var array<int,string> $suggestions
 */
?>
<h1 class="page-title">ค้นหาคู่มือ</h1>

<form class="searchbar" action="<?= h(url('search')) ?>" method="get" role="search">
  <span class="searchbar__icon"><?= icon('search') ?></span>
  <input type="search" name="q" value="<?= h($q) ?>" autofocus enterkeyhint="search"
         placeholder="พิมพ์คำค้น เช่น การลา, เบิกเงิน, ทะเบียน" aria-label="คำค้นหา">
  <button class="searchbar__go" type="submit">ค้นหา</button>
  <?php if ($divisionFilter): ?><input type="hidden" name="division" value="<?= (int) $divisionFilter ?>"><?php endif; ?>
</form>

<div class="chiprow" style="margin:12px 0">
  <a class="chip <?= $divisionFilter ? '' : 'chip--primary' ?>"
     href="<?= h(url('search?' . http_build_query(['q' => $q]))) ?>">ทุกฝ่าย</a>
  <?php foreach ($divisions as $d): ?>
    <a class="chip <?= $divisionFilter === (int) $d['id'] ? 'chip--primary' : '' ?>"
       href="<?= h(url('search?' . http_build_query(['q' => $q, 'division' => $d['id']]))) ?>">
      <?= h($d['short_name'] ?: $d['name']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($q === ''): ?>
  <?php if ($suggestions): ?>
    <h2 class="section-title"><?= icon('clock', 18) ?> คำค้นยอดนิยม</h2>
    <div class="chiprow">
      <?php foreach ($suggestions as $s): ?>
        <a class="chip chip--outline" href="<?= h(url('search?q=' . rawurlencode($s))) ?>"><?= h($s) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="empty"><?= icon('search', 40) ?><p>พิมพ์คำค้นเพื่อค้นหาขั้นตอนการปฏิบัติงาน</p></div>
<?php elseif (!$rows): ?>
  <div class="empty">
    <?= icon('search', 40) ?>
    <p>ไม่พบเรื่องที่ตรงกับ “<?= h($q) ?>”</p>
    <p style="font-size:.85rem">ลองใช้คำสั้นลง หรือค้นด้วยคำอื่น</p>
  </div>
<?php else: ?>
  <p class="page-sub">พบ <?= (int) $total ?> เรื่อง<?= $pages > 1 ? ' · หน้า ' . $page . '/' . $pages : '' ?></p>
  <div class="card list">
    <?php foreach ($rows as $r): ?>
      <a class="list__item" href="<?= h(url('procedure/' . $r['id'])) ?>">
        <span class="list__body">
          <span class="list__title" style="display:block"><?= highlight($r['title'], $q) ?></span>
          <span class="list__meta">
            <span><?= h($r['division_name']) ?></span><span><?= h($r['section_name']) ?></span>
          </span>
          <?php $snippet = excerpt($r['purpose'] ?: $r['content'], 130); ?>
          <?php if ($snippet !== ''): ?>
            <span style="display:block;font-size:.83rem;color:var(--muted);margin-top:4px">
              <?= highlight($snippet, $q) ?></span>
          <?php endif; ?>
        </span>
        <span class="list__chev"><?= icon('chev', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="หน้าผลลัพธ์">
      <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
          <span class="is-active"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= h(url('search?' . http_build_query(array_filter(['q' => $q, 'division' => $divisionFilter ?: null, 'page' => $i])))) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
