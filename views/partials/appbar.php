<?php
/**
 * @var array<string,mixed>|null $user
 * @var string $active
 */
?>
<header class="appbar">
  <a class="appbar__brand" href="<?= h(url('')) ?>">
    <span class="appbar__logo">RVC</span>
    <span style="min-width:0">
      <span class="appbar__title" style="display:block">คู่มือการปฏิบัติงาน</span>
      <span class="appbar__sub">วิทยาลัยอาชีวศึกษาร้อยเอ็ด</span>
    </span>
  </a>

  <div class="appbar__actions">
    <a class="iconbtn" href="<?= h(url('search')) ?>" aria-label="ค้นหา" title="ค้นหา"><?= icon('search') ?></a>
    <button class="iconbtn" type="button" data-action="toggle-theme" aria-label="สลับโหมดสว่าง/มืด" title="สลับโหมดแสดงผล">
      <span data-theme-glyph><?= icon('eye') ?></span>
    </button>
    <?php if ($user): ?>
      <?php if (Auth::isAdmin()): ?>
        <a class="iconbtn <?= $active === 'admin' ? 'is-on' : '' ?>" href="<?= h(url('admin')) ?>"
           aria-label="ผู้ดูแลระบบ" title="ผู้ดูแลระบบ"><?= icon('shield') ?></a>
      <?php endif; ?>
      <a class="iconbtn" href="<?= h(url('account')) ?>" aria-label="บัญชีของฉัน" title="<?= h($user['name']) ?>"><?= icon('user') ?></a>
    <?php else: ?>
      <a class="iconbtn" href="<?= h(url('login')) ?>" aria-label="เข้าสู่ระบบ" title="เข้าสู่ระบบ"><?= icon('user') ?></a>
    <?php endif; ?>
  </div>
</header>
