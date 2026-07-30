<?php
/** @var array<string,mixed> $page */
?>
<nav class="crumbs" aria-label="เส้นทาง">
  <a href="<?= h(url('')) ?>">หน้าแรก</a><?= icon('chev', 14) ?>
  <span>ข้อมูลทั่วไป</span>
</nav>

<h1 class="page-title"><?= h($page['title']) ?></h1>

<div class="panel">
  <div class="panel__body"><?= paragraphs($page['body']) ?></div>
</div>

<div class="doc-actions no-print" style="margin-top:16px">
  <button class="btn btn--sm" type="button" data-action="print"><?= icon('print', 16) ?>พิมพ์</button>
</div>
