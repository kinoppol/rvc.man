<?php
/**
 * @var array<string,mixed> $user
 * @var int $favoriteCount
 */
?>
<h1 class="page-title">บัญชีของฉัน</h1>

<div class="card card--pad" style="margin-bottom:16px">
  <div style="display:flex;align-items:center;gap:14px">
    <span class="appbar__logo" style="width:52px;height:52px;border-radius:14px;font-size:1.1rem">
      <?= h(mb_substr($user['name'], 0, 1, 'UTF-8')) ?></span>
    <div style="min-width:0">
      <div style="font-weight:700"><?= h($user['name']) ?></div>
      <div style="font-size:.83rem;color:var(--muted)"><?= h($user['username']) ?> · <?= h($user['role']) ?></div>
    </div>
  </div>
  <div class="chiprow" style="margin-top:14px">
    <a class="chip chip--outline" href="<?= h(url('saved')) ?>">บันทึกไว้ <?= (int) $favoriteCount ?> เรื่อง</a>
    <?php if (Auth::isAdmin()): ?>
      <a class="chip chip--primary" href="<?= h(url('admin')) ?>">ไปหน้าผู้ดูแลระบบ</a>
    <?php endif; ?>
  </div>
</div>

<h2 class="section-title"><?= icon('shield', 18) ?> เปลี่ยนรหัสผ่าน</h2>
<form class="card card--pad" method="post" action="<?= h(url('account/password')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="current">รหัสผ่านปัจจุบัน</label>
    <input class="input" id="current" name="current" type="password" autocomplete="current-password" required>
  </div>
  <div class="form-row form-row--2">
    <div class="field">
      <label for="new">รหัสผ่านใหม่</label>
      <input class="input" id="new" name="new" type="password" autocomplete="new-password" minlength="6" required>
    </div>
    <div class="field">
      <label for="confirm">ยืนยันรหัสผ่านใหม่</label>
      <input class="input" id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="6" required>
    </div>
  </div>
  <button class="btn btn--primary" type="submit">บันทึกรหัสผ่านใหม่</button>
</form>

<form method="post" action="<?= h(url('logout')) ?>" style="margin-top:18px">
  <?= csrf_field() ?>
  <button class="btn btn--danger btn--block" type="submit"><?= icon('logout', 18) ?>ออกจากระบบ</button>
</form>
