<?php
/**
 * Bottom navigation — the primary navigation on phones.
 *
 * @var string $active
 * @var array<string,mixed>|null $user
 */
$tabs = [
    ['home',   'หน้าแรก',   'home',     ''],
    ['browse', 'หมวดงาน',  'grid',     'divisions'],
    ['search', 'ค้นหา',     'search',   'search'],
    ['saved',  'บันทึกไว้', 'bookmark', 'saved'],
    ['account', $user ? 'บัญชี' : 'เข้าสู่ระบบ', 'user', $user ? 'account' : 'login'],
];
?>
<nav class="tabbar" aria-label="เมนูหลัก">
  <?php foreach ($tabs as [$key, $label, $ico, $path]): ?>
    <a class="tabbar__item <?= $active === $key ? 'is-active' : '' ?>" href="<?= h(url($path)) ?>">
      <?= icon($ico, 22) ?><span><?= h($label) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
