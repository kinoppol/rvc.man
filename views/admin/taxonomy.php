<?php
/**
 * @var array<int,array<string,mixed>> $divisions
 * @var array<int,array<string,mixed>> $sections
 */
?>
<h1 class="page-title">ฝ่ายและงาน</h1>
<p class="page-sub">โครงสร้างหมวดหมู่ของคู่มือ — ลบฝ่ายหรืองานจะลบเรื่องภายในทั้งหมดด้วย</p>

<h2 class="section-title"><?= icon('folder', 18) ?> ฝ่าย</h2>
<div class="card list" style="margin-bottom:14px">
  <?php foreach ($divisions as $d): ?>
    <details class="list__item" style="display:block">
      <summary style="display:flex;align-items:center;gap:12px;cursor:pointer;min-height:32px">
        <span class="tile__icon" style="--accent:<?= h($d['color']) ?>;width:32px;height:32px;border-radius:9px">
          <?= icon($d['icon'] ?: 'folder', 16) ?></span>
        <span class="list__body">
          <span class="list__title" style="display:block"><?= h($d['name']) ?></span>
          <span class="list__meta">
            <span><?= (int) $d['section_count'] ?> งาน</span>
            <span><?= (int) $d['procedure_count'] ?> เรื่อง</span>
            <?php if (!$d['is_active']): ?><span>ปิดใช้งาน</span><?php endif; ?>
          </span>
        </span>
      </summary>

      <form method="post" action="<?= h(url('admin/divisions/save')) ?>" style="padding:14px 0 4px">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
        <div class="form-row form-row--2">
          <div class="field"><label>ชื่อฝ่าย</label>
            <input class="input" name="name" value="<?= h($d['name']) ?>" required></div>
          <div class="field"><label>ชื่อย่อ</label>
            <input class="input" name="short_name" value="<?= h($d['short_name']) ?>"></div>
        </div>
        <div class="field"><label>คำอธิบาย</label>
          <input class="input" name="description" value="<?= h($d['description']) ?>"></div>
        <div class="form-row form-row--2">
          <div class="field"><label>ลำดับ</label>
            <input class="input" name="sort_order" type="number" value="<?= (int) $d['sort_order'] ?>"></div>
          <div class="field"><label>สถานะ</label>
            <label style="display:flex;gap:8px;align-items:center;min-height:44px">
              <input type="checkbox" name="is_active" value="1" <?= $d['is_active'] ? 'checked' : '' ?>>
              <span>เปิดใช้งาน</span></label></div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn--primary btn--sm" type="submit">บันทึก</button>
        </div>
      </form>
      <form method="post" action="<?= h(url('admin/divisions/' . $d['id'] . '/delete')) ?>"
            data-confirm="ลบฝ่าย “<?= h($d['name']) ?>” พร้อมงานและเรื่องทั้งหมดภายใน?">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--danger" type="submit"><?= icon('trash', 15) ?>ลบฝ่ายนี้</button>
      </form>
    </details>
  <?php endforeach; ?>
</div>

<details class="card card--pad" style="margin-bottom:22px">
  <summary style="cursor:pointer;font-weight:600">+ เพิ่มฝ่ายใหม่</summary>
  <form method="post" action="<?= h(url('admin/divisions/save')) ?>" style="margin-top:14px">
    <?= csrf_field() ?>
    <div class="form-row form-row--2">
      <div class="field"><label>ชื่อฝ่าย *</label><input class="input" name="name" required></div>
      <div class="field"><label>ชื่อย่อ</label><input class="input" name="short_name"></div>
    </div>
    <div class="field"><label>คำอธิบาย</label><input class="input" name="description"></div>
    <input type="hidden" name="is_active" value="1">
    <button class="btn btn--primary btn--sm" type="submit"><?= icon('plus', 16) ?>เพิ่มฝ่าย</button>
  </form>
</details>

<h2 class="section-title"><?= icon('list', 18) ?> งาน</h2>
<div class="card list" style="margin-bottom:14px">
  <?php foreach ($sections as $s): ?>
    <div class="list__item" style="flex-wrap:wrap;gap:8px">
      <form method="post" action="<?= h(url('admin/sections/save')) ?>"
            style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;flex:1;min-width:0">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
        <input type="hidden" name="is_active" value="1">
        <input class="input" name="name" value="<?= h($s['name']) ?>"
               style="min-height:38px;flex:2 1 220px" aria-label="ชื่องาน">
        <select class="select" name="division_id" style="min-height:38px;flex:1 1 160px" aria-label="ฝ่าย">
          <?php foreach ($divisions as $d): ?>
            <option value="<?= (int) $d['id'] ?>" <?= (int) $s['division_id'] === (int) $d['id'] ? 'selected' : '' ?>>
              <?= h($d['short_name'] ?: $d['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input class="input" name="sort_order" type="number" value="<?= (int) $s['sort_order'] ?>"
               style="min-height:38px;width:74px" aria-label="ลำดับ">
        <span class="chip"><?= (int) $s['procedure_count'] ?> เรื่อง</span>
        <button class="btn btn--sm btn--primary" type="submit" title="บันทึก"><?= icon('check', 15) ?></button>
      </form>
      <form method="post" action="<?= h(url('admin/sections/' . $s['id'] . '/delete')) ?>"
            data-confirm="ลบงาน “<?= h($s['name']) ?>” พร้อมเรื่องทั้งหมดภายใน?">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--danger" type="submit" title="ลบ"><?= icon('trash', 15) ?></button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<details class="card card--pad">
  <summary style="cursor:pointer;font-weight:600">+ เพิ่มงานใหม่</summary>
  <form method="post" action="<?= h(url('admin/sections/save')) ?>" style="margin-top:14px">
    <?= csrf_field() ?>
    <div class="form-row form-row--2">
      <div class="field"><label>ชื่องาน *</label><input class="input" name="name" required></div>
      <div class="field"><label>ฝ่าย *</label>
        <select class="select" name="division_id" required>
          <?php foreach ($divisions as $d): ?>
            <option value="<?= (int) $d['id'] ?>"><?= h($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <input type="hidden" name="is_active" value="1">
    <button class="btn btn--primary btn--sm" type="submit"><?= icon('plus', 16) ?>เพิ่มงาน</button>
  </form>
</details>
