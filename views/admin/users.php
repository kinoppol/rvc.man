<?php
/**
 * @var array<int,array<string,mixed>> $users
 * @var array<string,mixed>|null $edit
 */
?>
<h1 class="page-title">ผู้ใช้งาน</h1>
<p class="page-sub">อ่านคู่มือได้โดยไม่ต้องเข้าสู่ระบบ — บัญชีใช้สำหรับบันทึกรายการและการดูแลระบบ</p>

<div class="card list" style="margin-bottom:16px">
  <?php foreach ($users as $u): ?>
    <div class="list__item">
      <span class="appbar__logo" style="width:34px;height:34px;border-radius:10px;font-size:.85rem">
        <?= h(mb_substr($u['name'], 0, 1, 'UTF-8')) ?></span>
      <span class="list__body">
        <span class="list__title" style="display:block"><?= h($u['name']) ?></span>
        <span class="list__meta">
          <span><?= h($u['username']) ?></span>
          <span><?= h($u['role']) ?></span>
          <?php if ($u['last_login_at']): ?><span>เข้าล่าสุด <?= h(thai_date($u['last_login_at'])) ?></span><?php endif; ?>
        </span>
      </span>
      <span class="chip <?= $u['is_active'] ? 'chip--ok' : 'chip--danger' ?>">
        <?= $u['is_active'] ? 'ใช้งาน' : 'ระงับ' ?></span>
      <a class="btn btn--sm" href="<?= h(url('admin/users?edit=' . $u['id'])) ?>"><?= icon('edit', 15) ?></a>
      <?php if ((int) $u['id'] !== Auth::id()): ?>
        <form method="post" action="<?= h(url('admin/users/' . $u['id'] . '/delete')) ?>"
              data-confirm="ลบผู้ใช้ “<?= h($u['name']) ?>” ?">
          <?= csrf_field() ?>
          <button class="btn btn--sm btn--danger" type="submit"><?= icon('trash', 15) ?></button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<h2 class="section-title"><?= icon($edit ? 'edit' : 'plus', 18) ?><?= $edit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?></h2>
<form class="card card--pad" method="post" action="<?= h(url('admin/users/save')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

  <div class="form-row form-row--2">
    <div class="field">
      <label for="username">ชื่อผู้ใช้ *</label>
      <input class="input" id="username" name="username" required value="<?= h($edit['username'] ?? '') ?>"
             <?= $edit ? 'readonly' : '' ?>>
    </div>
    <div class="field">
      <label for="name">ชื่อ-สกุล *</label>
      <input class="input" id="name" name="name" required value="<?= h($edit['name'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row form-row--2">
    <div class="field">
      <label for="email">อีเมล</label>
      <input class="input" id="email" name="email" type="email" value="<?= h($edit['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="position">ตำแหน่ง / งานที่สังกัด</label>
      <input class="input" id="position" name="position" value="<?= h($edit['position'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row form-row--2">
    <div class="field">
      <label for="role">บทบาท</label>
      <select class="select" id="role" name="role">
        <?php foreach (App::ROLES as $r): ?>
          <option value="<?= h($r) ?>" <?= ($edit['role'] ?? '') === $r ? 'selected' : '' ?>><?= h($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="password">รหัสผ่าน<?= $edit ? ' (เว้นว่างหากไม่เปลี่ยน)' : ' *' ?></label>
      <input class="input" id="password" name="password" type="password" autocomplete="new-password"
             <?= $edit ? '' : 'required' ?>>
    </div>
  </div>
  <div class="field">
    <label style="display:flex;gap:8px;align-items:center;min-height:44px">
      <input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>>
      <span>เปิดใช้งานบัญชี</span>
    </label>
  </div>

  <div class="doc-actions">
    <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?>บันทึก</button>
    <?php if ($edit): ?><a class="btn" href="<?= h(url('admin/users')) ?>">ยกเลิก</a><?php endif; ?>
  </div>
</form>
