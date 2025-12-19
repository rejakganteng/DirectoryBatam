<?php
require_once __DIR__ . '/auth.php';
if (!function_exists('h')) {
  function h($s) { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
}
$cfg = include __DIR__ . '/config.php';
$BASE_URL = rtrim($cfg['base_url'] ?? '', '/');
require_once __DIR__ . '/db.php';
$hs = [];
try {
  $hs = $pdo->query('SELECT * FROM header_settings WHERE id = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // If the column doesn't exist yet (migration not applied), avoid fatal error and use defaults
  $hs = [];
}
$site_title = $hs['site_title'] ?? 'Direktori Batam';
$logo_text = $hs['logo_text'] ?? 'DB';
$logo_file = $hs['logo_file'] ?? null;
$logo_bg = $hs['logo_bg_color'] ?? '#FFFFFF';
$logo_color = $hs['logo_text_color'] ?? '#17A2B8';
$navbar_bg = $hs['navbar_bg_color'] ?? '#17A2B8';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Direktori Batam</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?= $BASE_URL ?>/assets/app.css?v=<?= filemtime(__DIR__ . '/assets/app.css') ?>" rel="stylesheet">
</head>
<body class="<?= isAdmin() ? 'admin-mode' : '' ?>">
<nav class="navbar navbar-expand-lg navbar-dark" style="background:<?= h($navbar_bg) ?>; margin-bottom:24px;">
  <div class="container d-flex align-items-center justify-content-between">
  <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $BASE_URL ?>/" style="color:#ffffff; font-weight:700; font-size:1.4rem; text-decoration:none;">
    <?php if (!empty($logo_file)): ?>
      <div style="width:50px; height:50px; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:<?= h($logo_bg) ?>;">
        <img src="<?= $BASE_URL ?>/assets/uploads/<?= h($logo_file) ?>" alt="logo" style="height:100%; width:auto; display:block;">
      </div>
    <?php else: ?>
      <div style="width:50px; height:50px; background:<?= h($logo_bg) ?>; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:900; color:<?= h($logo_color) ?>; font-size:28px;"><?= h($logo_text) ?></div>
    <?php endif; ?>
    <span style="letter-spacing:-0.3px; color:#ffffff;"><?= h($site_title) ?></span>
  </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <div class="ms-auto d-flex align-items-center gap-3 flex-wrap">
      <?php if (!isLoggedIn()): ?>
        <a href="<?= $BASE_URL ?>/login.php" class="nav-link" style="color:#ffffff; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:6px; font-size:0.95rem;">
          <i class="fa fa-sign-in-alt"></i>&nbsp;Login
        </a>
        <a href="<?= $BASE_URL ?>/register.php" class="nav-link" style="color:#ffffff; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:6px; font-size:0.95rem;">
          <i class="fa fa-user-plus"></i>&nbsp;Register
        </a>
      <?php else: ?>
        <?php $u = currentUser(); ?>
        <!-- Admin links grouped -->
        <?php if ($u['role'] === 'admin'): ?>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <a class="nav-link" style="color:#ffffff; font-weight:600;" href="<?= $BASE_URL ?>/admin/dashboard_admin.php">Dashboard Admin</a>
            <a class="nav-link" style="color:#ffffff; font-weight:600;" href="<?= $BASE_URL ?>/admin/listings.php">Kelola Data</a>
            <a class="nav-link" style="color:#ffffff; font-weight:600;" href="<?= $BASE_URL ?>/admin/categories.php">Kelola Kategori</a>
            <a class="nav-link" style="color:#ffffff; font-weight:600;" href="<?= $BASE_URL ?>/admin/comments.php">Kelola Komentar</a>
            <a class="nav-link" style="color:#ffffff; font-weight:600;" href="<?= $BASE_URL ?>/admin/sponsors.php">Kelola Sponsor</a>
            <a class="nav-link" style="color:#ffffff; font-weight:600;" href="<?= $BASE_URL ?>/admin/users.php">Kelola User</a>
          </div>
        <?php endif; ?>
        <!-- Profile link with avatar or user icon -->
        <a class="nav-link d-flex align-items-center" href="<?= $BASE_URL ?>/user_profile.php?id=<?= h($u['id']) ?>" title="Profil Saya" style="color:#ffffff; text-decoration:none; padding: 0.25rem 0.5rem;">
          <?php if (!empty($u['avatar'])): ?>
            <img src="<?= $BASE_URL ?>/assets/uploads/avatars/<?= h($u['avatar']) ?>" alt="Avatar" style="width:42px; height:42px; object-fit:cover; border-radius:50%; border:2px solid rgba(255,255,255,0.2);">
          <?php else: ?>
            <i class="fa fa-user-circle" style="font-size:42px; color:#ffffff;"></i>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <?php if (isAdmin()): ?>
        <a href="<?= $BASE_URL ?>/admin/listing_create.php" class="nav-add-button ms-2" title="Tambah Listing" aria-label="Tambah Listing">
          <span class="visually-hidden">Tambah Listing</span>
        </a>
      <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<div class="container">
  <!-- navbar now simplified. Floating Add button available for admin only -->
  <?php $CURR = currentUser(); ?>
  <script>window.BASE_URL = <?= json_encode($BASE_URL) ?>; window.CURRENT_USER_ROLE = <?= json_encode($CURR['role'] ?? null) ?>; window.CURRENT_USER_ID = <?= json_encode($CURR['id'] ?? null) ?>;</script>
  <!-- Floating Action Button (FAB) - visible only to logged-in non-admin users -->
  <?php if (isLoggedIn() && !isAdmin()): ?>
    <button id="fab-add" type="button" class="fab" aria-label="Tambah" title="Tambah" onclick="tambahData();">
      <i class="fab-icon fa fa-plus" aria-hidden="true"></i>
      <span class="visually-hidden">Tambah</span>
    </button>
  <?php endif; ?>

  <?php if (isAdmin()): ?>
    <!-- Admin-specific add controls remain in admin area -->
  <?php endif; ?>