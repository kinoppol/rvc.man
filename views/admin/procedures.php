<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $divisions
 * @var array<int,array<string,mixed>> $sections
 * @var array{division:int,section:int,status:string,q:string} $filters
 */
?>
<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
  <h1 class="page-title" style="flex:1">จัดการเรื่อง/ขั้นตอน</h1>
  <a class="btn btn--primary btn--sm" href="<?= h(url('admin/procedures/new')) ?>"><?= icon('plus', 16) ?>เพิ่มเรื่อง</a>
</div>
<p class="page-sub">พบ <?= count($rows) ?> รายการ</p>

<form class="card card--pad" method="get" action="<?= h(url('admin/procedures')) ?>" style="margin-bottom:16px">
  <div class="form-row form-row--2">
    <div class="field">
      <label for="q">ค้นหา</label>
      <input class="input" id="q" name="q" value="<?= h($filters['q']) ?>" placeholder="ชื่อเรื่องหรือเนื้อหา">
    </div>
    <div class="field">
      <label for="division">ฝ่าย</label>
      <select class="select" id="division" name="division">
        <option value="">ทุกฝ่าย</option>
        <?php foreach ($divisions as $d): ?>
          <option value="<?= (int) $d['id'] ?>" <?= $filters['division'] === (int) $d['id'] ? 'selected' : '' ?>>
            <?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-row form-row--2">
    <div class="field">
      <label for="section">งาน</label>
      <select class="select" id="section" name="section">
        <option value="">ทุกงาน</option>
        <?php foreach ($sections as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= $filters['section'] === (int) $s['id'] ? 'selected' : '' ?>>
            <?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="status">สถานะ</label>
      <select class="select" id="status" name="status">
        <option value="">ทุกสถานะ</option>
        <?php foreach (App::STATUSES as $st): ?>
          <option value="<?= h($st) ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= h($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn btn--primary btn--sm" type="submit"><?= icon('search', 16) ?>กรองข้อมูล</button>
  <a class="btn btn--sm" href="<?= h(url('admin/procedures')) ?>">ล้างตัวกรอง</a>
</form>

<?php if (!$rows): ?>
  <div class="empty"><?= icon('list', 40) ?><p>ไม่พบรายการตามเงื่อนไข</p></div>
<?php else: ?>
  <div class="table-wrap card">
    <table class="table">
      <thead>
        <tr>
          <th>ชื่อเรื่อง</th><th>ฝ่าย / งาน</th><th>ขั้นตอน</th>
          <th>หน้า</th><th>สถานะ</th><th>เปิดดู</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a style="font-weight:600" href="<?= h(url('admin/procedures/' . $r['id'] . '/edit')) ?>"><?= h($r['title']) ?></a></td>
            <td style="color:var(--muted);font-size:.82rem">
              <?= h($r['division_name']) ?><br><?= h($r['section_name']) ?>
            </td>
            <td><?= (int) $r['step_count'] ?></td>
            <td><?= $r['page_start'] ? (int) $r['page_start'] : '—' ?></td>
            <td><span class="chip <?= $r['status'] === 'เผยแพร่' ? 'chip--ok' : 'chip--warn' ?>"><?= h($r['status']) ?></span></td>
            <td><?= (int) $r['views'] ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <a class="btn btn--sm" href="<?= h(url('procedure/' . $r['id'])) ?>" title="ดู"><?= icon('eye', 15) ?></a>
                <a class="btn btn--sm" href="<?= h(url('admin/procedures/' . $r['id'] . '/edit')) ?>" title="แก้ไข"><?= icon('edit', 15) ?></a>
                <form method="post" action="<?= h(url('admin/procedures/' . $r['id'] . '/delete')) ?>"
                      data-confirm="ยืนยันการลบเรื่อง “<?= h($r['title']) ?>” ?">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--danger" type="submit" title="ลบ"><?= icon('trash', 15) ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
