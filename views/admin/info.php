<?php
/**
 * @var array<int,array<string,mixed>> $pages
 * @var array<string,mixed>|null $edit
 */
?>
<h1 class="page-title">หน้าข้อมูลทั่วไป</h1>
<p class="page-sub">บทนำของคู่มือ เช่น วัตถุประสงค์ ประวัติวิทยาลัย ระเบียบการแต่งกาย</p>

<div class="card list" style="margin-bottom:16px">
  <?php foreach ($pages as $p): ?>
    <div class="list__item">
      <span class="list__body">
        <a class="list__title" style="display:block" href="<?= h(url('info/' . $p['id'])) ?>"><?= h($p['title']) ?></a>
        <span class="list__meta">
          <span>ลำดับ <?= (int) $p['sort_order'] ?></span>
          <span><?= mb_strlen((string) $p['body'], 'UTF-8') ?> ตัวอักษร</span>
          <?php if (!$p['is_active']): ?><span>ปิดใช้งาน</span><?php endif; ?>
        </span>
      </span>
      <a class="btn btn--sm" href="<?= h(url('admin/info?edit=' . $p['id'])) ?>"><?= icon('edit', 15) ?></a>
      <form method="post" action="<?= h(url('admin/info/' . $p['id'] . '/delete')) ?>"
            data-confirm="ลบหน้า “<?= h($p['title']) ?>” ?">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--danger" type="submit"><?= icon('trash', 15) ?></button>
      </form>
    </div>
  <?php endforeach; ?>
  <?php if (!$pages): ?>
    <div style="padding:16px;color:var(--muted)">ยังไม่มีหน้าข้อมูล</div>
  <?php endif; ?>
</div>

<h2 class="section-title"><?= icon($edit ? 'edit' : 'plus', 18) ?><?= $edit ? 'แก้ไขหน้า' : 'เพิ่มหน้าใหม่' ?></h2>
<form class="card card--pad" method="post" action="<?= h(url('admin/info/save')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

  <div class="field">
    <label for="title">ชื่อหน้า *</label>
    <input class="input" id="title" name="title" required value="<?= h($edit['title'] ?? '') ?>">
  </div>
  <div class="field">
    <label for="body">เนื้อหา (เว้นบรรทัดว่างเพื่อขึ้นย่อหน้าใหม่)</label>
    <textarea class="textarea" id="body" name="body" style="min-height:260px"><?= h($edit['body'] ?? '') ?></textarea>
  </div>
  <div class="form-row form-row--2">
    <div class="field">
      <label for="sort_order">ลำดับ</label>
      <input class="input" id="sort_order" name="sort_order" type="number" value="<?= (int) ($edit['sort_order'] ?? 0) ?>">
    </div>
    <div class="field">
      <label>สถานะ</label>
      <label style="display:flex;gap:8px;align-items:center;min-height:44px">
        <input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>>
        <span>แสดงบนหน้าเว็บ</span>
      </label>
    </div>
  </div>
  <div class="doc-actions">
    <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?>บันทึก</button>
    <?php if ($edit): ?><a class="btn" href="<?= h(url('admin/info')) ?>">ยกเลิก</a><?php endif; ?>
  </div>
</form>
