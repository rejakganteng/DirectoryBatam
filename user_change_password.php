<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin();
if (!isLoggedIn()) { header('Location: login.php'); exit; }
$u = currentUser();
$BASE_URL = rtrim((include __DIR__ . '/config.php')['base_url'] ?? '', '/');
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
}

require __DIR__ . '/header.php';
?>
<div class="container">
  <div class="row mt-4">
    <div class="col-md-6 offset-md-3">
      <div class="card p-4">
        <h3>Ganti Password</h3>
        <?php if (!empty($_GET['updated'])): ?>
          <div id="change-success-alert" class="alert alert-success">Password berhasil diubah.</div>
        <?php elseif (!empty($_GET['error'])): ?>
          <div class="alert alert-danger">Terjadi kesalahan: <?= h(urldecode($_GET['error'])) ?></div>
        <?php endif; ?>

        <form action="<?= $BASE_URL ?>/user_change_password_action.php" method="post">
          <div class="mb-3">
            <label class="form-label">Password Lama</label>
            <input type="password" name="old_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
            <small class="text-muted">Minimal 6 karakter</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
          </div>

          <button class="btn btn-primary">Ganti Password</button>
          <a href="<?= $BASE_URL ?>/user_profile.php?id=<?= h($u['id']) ?>" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    const s = document.getElementById('change-success-alert');
    if (!s) return;
    setTimeout(function(){
      s.style.transition = 'opacity 0.5s ease';
      s.style.opacity = '0';
      setTimeout(function(){ if (s && s.parentNode) s.parentNode.removeChild(s); }, 500);
    }, 3000);
  })();
</script>

<?php require __DIR__ . '/footer.php'; ?>
