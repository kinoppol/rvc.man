<?php /** @var string|null $error */ ?>
<div style="max-width:420px;margin:24px auto">
  <div style="text-align:center;margin-bottom:22px">
    <div class="appbar__logo" style="width:56px;height:56px;border-radius:16px;font-size:1rem;margin:0 auto 12px">RVC</div>
    <h1 class="page-title" style="margin:0">เข้าสู่ระบบ</h1>
    <p class="page-sub" style="margin:4px 0 0">คู่มือการปฏิบัติงาน วิทยาลัยอาชีวศึกษาร้อยเอ็ด</p>
  </div>

  <form class="card card--pad" method="post" action="<?= h(url('login')) ?>">
    <?= csrf_field() ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert--error"><?= icon('info', 18) ?><span><?= h($error) ?></span></div>
    <?php endif; ?>

    <div class="field">
      <label for="username">ชื่อผู้ใช้</label>
      <input class="input" id="username" name="username" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">รหัสผ่าน</label>
      <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
    </div>

    <button class="btn btn--primary btn--block" type="submit">เข้าสู่ระบบ</button>
    <p style="text-align:center;margin:16px 0 0;font-size:.85rem;color:var(--muted)">
      อ่านคู่มือได้โดยไม่ต้องเข้าสู่ระบบ · <a style="color:var(--primary-text)" href="<?= h(url('')) ?>">กลับหน้าแรก</a>
    </p>
  </form>
</div>
