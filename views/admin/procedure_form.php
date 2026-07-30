<?php
/**
 * @var array<string,mixed>|null $procedure
 * @var array<int,array<string,mixed>> $divisions
 * @var array<int,array<string,mixed>> $sections
 */
$isEdit = $procedure !== null;
$action = $isEdit ? url('admin/procedures/' . $procedure['id'] . '/edit') : url('admin/procedures/new');
$steps  = $procedure['steps'] ?? [];
$flows  = $procedure['flows'] ?? [];
?>
<nav class="crumbs">
  <a href="<?= h(url('admin')) ?>">ผู้ดูแลระบบ</a><?= icon('chev', 14) ?>
  <a href="<?= h(url('admin/procedures')) ?>">จัดการเรื่อง</a><?= icon('chev', 14) ?>
  <span><?= $isEdit ? 'แก้ไข' : 'เพิ่มใหม่' ?></span>
</nav>

<h1 class="page-title"><?= $isEdit ? 'แก้ไขเรื่อง' : 'เพิ่มเรื่องใหม่' ?></h1>

<form method="post" action="<?= h($action) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="card card--pad" style="margin-bottom:16px">
    <div class="field">
      <label for="title">ชื่อเรื่อง *</label>
      <input class="input" id="title" name="title" required
             value="<?= h($procedure['title'] ?? '') ?>" placeholder="เช่น การขออนุญาตไปราชการ">
    </div>

    <div class="form-row form-row--2">
      <div class="field">
        <label for="section_id">งานที่สังกัด *</label>
        <select class="select" id="section_id" name="section_id" required>
          <?php foreach ($divisions as $d): ?>
            <optgroup label="<?= h($d['name']) ?>">
              <?php foreach ($sections as $s): ?>
                <?php if ((int) $s['division_id'] !== (int) $d['id']) { continue; } ?>
                <option value="<?= (int) $s['id'] ?>"
                  <?= (int) ($procedure['section_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                  <?= h($s['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="status">สถานะ</label>
        <select class="select" id="status" name="status">
          <?php foreach (App::STATUSES as $st): ?>
            <option value="<?= h($st) ?>" <?= ($procedure['status'] ?? 'เผยแพร่') === $st ? 'selected' : '' ?>>
              <?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label for="purpose">หน้าที่และความรับผิดชอบ</label>
      <textarea class="textarea" id="purpose" name="purpose"
                placeholder="สรุปวัตถุประสงค์และขอบเขตความรับผิดชอบของงานนี้"><?= h($procedure['purpose'] ?? '') ?></textarea>
    </div>

    <div class="form-row form-row--2">
      <div class="field">
        <label for="page_start">หน้าในคู่มือ (เริ่ม)</label>
        <input class="input" id="page_start" name="page_start" type="number" min="0"
               value="<?= (int) ($procedure['page_start'] ?? 0) ?>">
      </div>
      <div class="field">
        <label for="page_end">หน้าในคู่มือ (สิ้นสุด)</label>
        <input class="input" id="page_end" name="page_end" type="number" min="0"
               value="<?= (int) ($procedure['page_end'] ?? 0) ?>">
      </div>
    </div>
    <div class="form-row form-row--2">
      <div class="field">
        <label for="code">รหัสเอกสาร</label>
        <input class="input" id="code" name="code" value="<?= h($procedure['code'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="sort_order">ลำดับการแสดง</label>
        <input class="input" id="sort_order" name="sort_order" type="number"
               value="<?= (int) ($procedure['sort_order'] ?? 0) ?>">
      </div>
    </div>
  </div>

  <h2 class="section-title"><?= icon('list', 18) ?> ขั้นตอนการปฏิบัติงาน</h2>
  <div class="card card--pad" style="margin-bottom:16px">
    <div data-repeat="steps">
      <?php foreach ($steps as $i => $s): ?>
        <div class="form-row" data-repeat-item
             style="grid-template-columns:70px 70px 1fr auto;align-items:end;margin-bottom:10px">
          <div class="field" style="margin:0">
            <label>ข้อ</label>
            <input class="input" name="step_no[]" type="number" min="1" value="<?= (int) $s['step_no'] ?>">
          </div>
          <div class="field" style="margin:0">
            <label>ข้อย่อย</label>
            <input class="input" name="step_sub[]" type="number" min="1" value="<?= h((string) ($s['sub_no'] ?? '')) ?>">
          </div>
          <div class="field" style="margin:0">
            <label>รายละเอียด</label>
            <textarea class="textarea" name="step_detail[]" style="min-height:64px"><?= h($s['detail']) ?></textarea>
          </div>
          <button class="btn btn--sm btn--danger" type="button" data-repeat-remove><?= icon('trash', 15) ?></button>
        </div>
      <?php endforeach; ?>
      <template data-repeat-template>
        <div class="form-row" data-repeat-item
             style="grid-template-columns:70px 70px 1fr auto;align-items:end;margin-bottom:10px">
          <div class="field" style="margin:0">
            <label>ข้อ</label><input class="input" name="step_no[]" type="number" min="1" value="">
          </div>
          <div class="field" style="margin:0">
            <label>ข้อย่อย</label><input class="input" name="step_sub[]" type="number" min="1" value="">
          </div>
          <div class="field" style="margin:0">
            <label>รายละเอียด</label><textarea class="textarea" name="step_detail[]" style="min-height:64px"></textarea>
          </div>
          <button class="btn btn--sm btn--danger" type="button" data-repeat-remove><?= icon('trash', 15) ?></button>
        </div>
      </template>
    </div>
    <button class="btn btn--sm" type="button" data-repeat-add="steps"><?= icon('plus', 16) ?>เพิ่มขั้นตอน</button>
  </div>

  <h2 class="section-title"><?= icon('users', 18) ?> ผู้รับผิดชอบและหลักฐาน</h2>
  <div class="card card--pad" style="margin-bottom:16px">
    <div data-repeat="flows">
      <?php foreach ($flows as $f): ?>
        <div class="form-row" data-repeat-item
             style="grid-template-columns:1fr 1fr 1fr auto;align-items:end;margin-bottom:10px">
          <div class="field" style="margin:0">
            <label>ขั้นตอน</label>
            <input class="input" name="flow_stage[]" value="<?= h($f['stage']) ?>">
          </div>
          <div class="field" style="margin:0">
            <label>ผู้รับผิดชอบ</label>
            <input class="input" name="flow_responsible[]" value="<?= h($f['responsible']) ?>">
          </div>
          <div class="field" style="margin:0">
            <label>หลักฐาน / เอกสาร</label>
            <input class="input" name="flow_evidence[]" value="<?= h($f['evidence']) ?>">
          </div>
          <button class="btn btn--sm btn--danger" type="button" data-repeat-remove><?= icon('trash', 15) ?></button>
        </div>
      <?php endforeach; ?>
      <template data-repeat-template>
        <div class="form-row" data-repeat-item
             style="grid-template-columns:1fr 1fr 1fr auto;align-items:end;margin-bottom:10px">
          <div class="field" style="margin:0">
            <label>ขั้นตอน</label><input class="input" name="flow_stage[]">
          </div>
          <div class="field" style="margin:0">
            <label>ผู้รับผิดชอบ</label><input class="input" name="flow_responsible[]">
          </div>
          <div class="field" style="margin:0">
            <label>หลักฐาน / เอกสาร</label><input class="input" name="flow_evidence[]">
          </div>
          <button class="btn btn--sm btn--danger" type="button" data-repeat-remove><?= icon('trash', 15) ?></button>
        </div>
      </template>
    </div>
    <button class="btn btn--sm" type="button" data-repeat-add="flows"><?= icon('plus', 16) ?>เพิ่มแถว</button>
  </div>

  <h2 class="section-title"><?= icon('file', 18) ?> เนื้อหาต้นฉบับและไฟล์แนบ</h2>
  <div class="card card--pad" style="margin-bottom:16px">
    <div class="field">
      <label for="content">ข้อความจากคู่มือ (แสดงเป็นรายละเอียดเพิ่มเติม)</label>
      <textarea class="textarea" id="content" name="content" style="min-height:180px"><?= h($procedure['content'] ?? '') ?></textarea>
    </div>

    <?php if ($isEdit && $procedure['attachments']): ?>
      <div class="list" style="margin-bottom:12px">
        <?php foreach ($procedure['attachments'] as $a): ?>
          <div class="list__item">
            <?= icon('file', 18) ?>
            <span class="list__body">
              <a class="list__title" href="<?= h(url('attachment/' . $a['id'])) ?>"><?= h($a['original_name']) ?></a>
              <span class="list__meta"><span><?= h(human_size((int) $a['size_bytes'])) ?></span></span>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
      <p style="font-size:.8rem;color:var(--muted);margin:0 0 12px">
        ลบไฟล์แนบได้จากปุ่มด้านล่างหลังบันทึกฟอร์มนี้
      </p>
    <?php endif; ?>

    <div class="field">
      <label for="attachments">แนบไฟล์ (pdf, doc, xls, ppt, รูปภาพ)</label>
      <input class="input" id="attachments" name="attachments[]" type="file" multiple
             accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
    </div>
  </div>

  <div class="doc-actions">
    <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?>บันทึก</button>
    <a class="btn" href="<?= h(url('admin/procedures')) ?>">ยกเลิก</a>
    <?php if ($isEdit): ?>
      <a class="btn" href="<?= h(url('procedure/' . $procedure['id'])) ?>"><?= icon('eye', 16) ?>ดูหน้าจริง</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($isEdit && $procedure['attachments']): ?>
  <h2 class="section-title"><?= icon('trash', 18) ?> ลบไฟล์แนบ</h2>
  <div class="card list">
    <?php foreach ($procedure['attachments'] as $a): ?>
      <div class="list__item">
        <span class="list__body"><span class="list__title"><?= h($a['original_name']) ?></span></span>
        <form method="post" action="<?= h(url('admin/attachments/' . $a['id'] . '/delete')) ?>"
              data-confirm="ยืนยันการลบไฟล์นี้?">
          <?= csrf_field() ?>
          <button class="btn btn--sm btn--danger" type="submit"><?= icon('trash', 15) ?>ลบ</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
