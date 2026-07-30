<?php
/**
 * Desktop-only side navigation (hidden under 1000px — phones use the tabbar).
 *
 * @var string $section 'public' | 'admin'
 * @var string $active
 */
$repo = new Repository();
?>
<aside class="sidenav" aria-label="เมนูด้านข้าง">
  <?php if ($section === 'admin'): ?>
    <div class="sidenav__group">ผู้ดูแลระบบ</div>
    <?php
    $adminLinks = [
        ['admin',            'ภาพรวม',        'grid'],
        ['admin/procedures', 'เรื่อง/ขั้นตอน', 'list'],
        ['admin/taxonomy',   'ฝ่ายและงาน',     'folder'],
        ['admin/info',       'หน้าข้อมูลทั่วไป', 'info'],
        ['admin/users',      'ผู้ใช้งาน',      'users'],
        ['admin/import',     'นำเข้าข้อมูล',   'refresh'],
    ];
    foreach ($adminLinks as [$path, $label, $ico]): ?>
      <a class="sidenav__item <?= $active === $path ? 'is-active' : '' ?>" href="<?= h(url($path)) ?>">
        <?= icon($ico, 18) ?><span><?= h($label) ?></span>
      </a>
    <?php endforeach; ?>
    <div class="sidenav__group">ส่วนผู้ใช้</div>
    <a class="sidenav__item" href="<?= h(url('')) ?>"><?= icon('home', 18) ?><span>กลับหน้าคู่มือ</span></a>
  <?php else: ?>
    <div class="sidenav__group">เมนู</div>
    <a class="sidenav__item <?= $active === 'home' ? 'is-active' : '' ?>" href="<?= h(url('')) ?>">
      <?= icon('home', 18) ?><span>หน้าแรก</span></a>
    <a class="sidenav__item <?= $active === 'search' ? 'is-active' : '' ?>" href="<?= h(url('search')) ?>">
      <?= icon('search', 18) ?><span>ค้นหา</span></a>
    <a class="sidenav__item <?= $active === 'saved' ? 'is-active' : '' ?>" href="<?= h(url('saved')) ?>">
      <?= icon('bookmark', 18) ?><span>บันทึกไว้</span></a>

    <div class="sidenav__group">ฝ่าย</div>
    <?php foreach ($repo->divisions() as $d): ?>
      <a class="sidenav__item <?= $active === 'division-' . $d['id'] ? 'is-active' : '' ?>"
         href="<?= h(url('division/' . $d['id'])) ?>">
        <?= icon($d['icon'] ?: 'folder', 18) ?>
        <span style="min-width:0"><?= h($d['short_name'] ?: $d['name']) ?></span>
        <span class="sidenav__count"><?= (int) $d['procedure_count'] ?></span>
      </a>
    <?php endforeach; ?>

    <?php $infos = $repo->infoPages(); ?>
    <?php if ($infos): ?>
      <div class="sidenav__group">ข้อมูลทั่วไป</div>
      <?php foreach ($infos as $p): ?>
        <a class="sidenav__item <?= $active === 'info-' . $p['id'] ? 'is-active' : '' ?>"
           href="<?= h(url('info/' . $p['id'])) ?>">
          <?= icon('info', 18) ?><span><?= h(excerpt($p['title'], 34)) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</aside>
