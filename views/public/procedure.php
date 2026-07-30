<?php
/**
 * @var array<string,mixed> $procedure
 * @var array{prev:?array<string,mixed>,next:?array<string,mixed>} $neighbours
 * @var bool $isFavorite
 */
$steps = $procedure['steps'];
$flows = $procedure['flows'];
// The source flowchart rarely labels its own boxes; drop the column when empty.
$hasStage = (bool) array_filter($flows, fn(array $f): bool => trim((string) $f['stage']) !== '');
?>
<nav class="crumbs" aria-label="เส้นทาง">
  <a href="<?= h(url('')) ?>">หน้าแรก</a><?= icon('chev', 14) ?>
  <a href="<?= h(url('division/' . $procedure['division_id'])) ?>"><?= h($procedure['division_name']) ?></a>
  <?= icon('chev', 14) ?>
  <a href="<?= h(url('section/' . $procedure['section_id'])) ?>"><?= h($procedure['section_name']) ?></a>
</nav>

<article class="doc-head">
  <div class="chiprow">
    <span class="chip chip--primary"><?= h($procedure['section_name']) ?></span>
    <?php if ($procedure['page_start']): ?>
      <span class="chip">คู่มือหน้า <?= (int) $procedure['page_start'] ?><?= $procedure['page_end'] > $procedure['page_start'] ? '–' . (int) $procedure['page_end'] : '' ?></span>
    <?php endif; ?>
    <span class="chip"><?= (int) $procedure['views'] ?> ครั้ง</span>
    <?php if ($procedure['status'] !== 'เผยแพร่'): ?>
      <span class="chip chip--warn"><?= h($procedure['status']) ?></span>
    <?php endif; ?>
  </div>

  <h1 class="doc-title"><?= h($procedure['title']) ?></h1>

  <div class="doc-actions no-print">
    <form method="post" action="<?= h(url('procedure/' . $procedure['id'] . '/favorite')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn--sm <?= $isFavorite ? 'btn--primary' : '' ?>" type="submit">
        <?= icon('bookmark', 16) ?><?= $isFavorite ? 'บันทึกแล้ว' : 'บันทึกไว้' ?>
      </button>
    </form>
    <button class="btn btn--sm" type="button" data-action="print"><?= icon('print', 16) ?>พิมพ์</button>
    <button class="btn btn--sm" type="button" data-action="share"
            data-title="<?= h($procedure['title']) ?>"><?= icon('download', 16) ?>แชร์</button>
    <?php if (Auth::isAdmin()): ?>
      <a class="btn btn--sm" href="<?= h(url('admin/procedures/' . $procedure['id'] . '/edit')) ?>">
        <?= icon('edit', 16) ?>แก้ไข</a>
    <?php endif; ?>
  </div>
</article>

<?php if (trim((string) $procedure['purpose']) !== ''): ?>
  <section class="panel">
    <div class="panel__head"><?= icon('info', 18) ?><h2>หน้าที่และความรับผิดชอบ</h2></div>
    <div class="panel__body"><?= paragraphs($procedure['purpose']) ?></div>
  </section>
<?php endif; ?>

<?php if ($steps): ?>
  <section class="panel">
    <div class="panel__head"><?= icon('list', 18) ?><h2>ขั้นตอนการปฏิบัติงาน</h2>
      <span class="chip" style="margin-inline-start:auto"><?= count($steps) ?> ขั้นตอน</span></div>
    <div class="panel__body">
      <ol class="steps">
        <?php foreach ($steps as $s): ?>
          <li data-no="<?= h($s['sub_no'] !== null ? $s['step_no'] . '.' . $s['sub_no'] : (string) $s['step_no']) ?>"
              class="<?= $s['sub_no'] !== null ? 'is-sub' : '' ?>">
            <div class="step-text"><?= h($s['detail']) ?></div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
<?php endif; ?>

<?php if ($flows): ?>
  <section class="panel">
    <div class="panel__head"><?= icon('users', 18) ?><h2>ผู้รับผิดชอบและหลักฐานอ้างอิง</h2></div>
    <div class="panel__body">
      <div class="flowlist">
        <div class="flowlist__head">
          <?php if ($hasStage): ?><div>ขั้นตอน</div><?php endif; ?>
          <div>ผู้รับผิดชอบ</div><div>หลักฐาน / เอกสาร</div>
        </div>
        <?php foreach ($flows as $i => $f): ?>
          <div class="flowlist__row">
            <?php if ($hasStage): ?>
              <div>
                <div class="flowlist__label">ขั้นตอนที่ <?= $i + 1 ?></div>
                <div class="flowlist__value"><?= h($f['stage'] ?: '—') ?></div>
              </div>
            <?php endif; ?>
            <div>
              <div class="flowlist__label">ผู้รับผิดชอบ</div>
              <div class="flowlist__value"><?= h($f['responsible'] ?: '—') ?></div>
            </div>
            <div>
              <div class="flowlist__label">หลักฐาน / เอกสาร</div>
              <div class="flowlist__value"><?= h($f['evidence'] ?: '—') ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($procedure['attachments']): ?>
  <section class="panel">
    <div class="panel__head"><?= icon('file', 18) ?><h2>เอกสารแนบ</h2></div>
    <div class="card list">
      <?php foreach ($procedure['attachments'] as $a): ?>
        <a class="list__item" href="<?= h(url('attachment/' . $a['id'])) ?>">
          <?= icon('download', 18) ?>
          <span class="list__body">
            <span class="list__title" style="display:block"><?= h($a['original_name']) ?></span>
            <span class="list__meta"><span><?= h(human_size((int) $a['size_bytes'])) ?></span></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php if (!$steps && trim((string) $procedure['content']) !== ''): ?>
  <section class="panel">
    <div class="panel__head"><?= icon('file', 18) ?><h2>รายละเอียด</h2></div>
    <div class="panel__body"><?= paragraphs($procedure['content']) ?></div>
  </section>
<?php elseif ($steps && trim((string) $procedure['content']) !== ''): ?>
  <details class="panel no-print">
    <summary class="btn btn--sm" style="width:fit-content"><?= icon('file', 16) ?>ดูข้อความต้นฉบับจากคู่มือ</summary>
    <div class="panel__body" style="margin-top:10px;font-size:.88rem;color:var(--muted)">
      <?= paragraphs($procedure['content']) ?>
    </div>
  </details>
<?php endif; ?>

<nav class="doc-actions no-print" style="margin-top:24px">
  <?php if ($neighbours['prev']): ?>
    <a class="btn btn--sm" href="<?= h(url('procedure/' . $neighbours['prev']['id'])) ?>">
      <?= icon('back', 16) ?><?= h(excerpt($neighbours['prev']['title'], 26)) ?></a>
  <?php endif; ?>
  <?php if ($neighbours['next']): ?>
    <a class="btn btn--sm" style="margin-inline-start:auto"
       href="<?= h(url('procedure/' . $neighbours['next']['id'])) ?>">
      <?= h(excerpt($neighbours['next']['title'], 26)) ?><?= icon('chev', 16) ?></a>
  <?php endif; ?>
</nav>
