<?php
/**
 * Base layout.
 *
 * @var string      $content   rendered view HTML
 * @var string|null $title     page title
 * @var string|null $section   'public' | 'admin' | 'bare'
 * @var string|null $active    tab/nav key: home|browse|search|saved|account|admin
 * @var bool|null   $wide      drop the desktop sidebar
 */
$section = $section ?? 'public';
$active  = $active ?? '';
$wide    = $wide ?? false;
$flashes = take_flash();
$user    = Auth::user();
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#4c2a86">
<meta name="description" content="ระบบคู่มือการปฏิบัติงาน วิทยาลัยอาชีวศึกษาร้อยเอ็ด">
<title><?= h($title ? $title . ' · ' . App::config('app')['short'] : App::name()) ?></title>
<link rel="manifest" href="<?= h(url('manifest.webmanifest')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(asset('app.css')) ?>">
<script>
  // Applied before first paint so the chosen theme never flashes.
  (function () {
    try {
      var t = localStorage.getItem('rvcman-theme');
      if (t === 'dark' || t === 'light') {
        document.documentElement.setAttribute('data-theme', t);
      }
    } catch (e) {}
  })();
</script>
</head>
<body>
<div class="app-root shell">

<?php if ($section !== 'bare'): ?>
  <?= App::partial('partials/appbar', ['user' => $user, 'active' => $active]) ?>
<?php endif; ?>

<main class="main">
  <?php if ($flashes): ?>
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert--<?= h($f['type'] === 'error' ? 'error' : ($f['type'] === 'info' ? 'info' : 'success')) ?>">
        <?= icon($f['type'] === 'error' ? 'info' : 'check') ?>
        <span><?= h($f['message']) ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($section === 'bare' || $wide): ?>
    <?= $content ?>
  <?php else: ?>
    <div class="layout">
      <?= App::partial('partials/sidenav', ['section' => $section, 'active' => $active]) ?>
      <div><?= $content ?></div>
    </div>
  <?php endif; ?>
</main>

<?php if ($section !== 'bare'): ?>
  <?= App::partial('partials/tabbar', ['active' => $active, 'user' => $user]) ?>
<?php endif; ?>

</div>
<script src="<?= h(asset('app.js')) ?>" defer></script>
</body>
</html>
