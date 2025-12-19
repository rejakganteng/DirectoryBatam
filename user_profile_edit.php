<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin();
if (!isLoggedIn()) { header('Location: login.php'); exit; }
$u = currentUser();
$BASE_URL = rtrim((include __DIR__ . '/config.php')['base_url'] ?? '', '/');
function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// fetch fresh user row
$stmt = $pdo->prepare('SELECT id, name, email, avatar, bio, birth_date, phone, address FROM users WHERE id = ?');
$stmt->execute([$u['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: index.php'); exit; }

require __DIR__ . '/header.php';
?>
<div class="container" style="max-width: 800px; padding-top: 40px; padding-bottom: 40px;">
  <div class="row">
    <div class="col-12">
      <div class="card p-4" style="border-radius:16px; box-shadow:0 6px 20px rgba(2,6,23,0.06);">
        <h3 class="mb-4" style="font-weight:700; color:#0f172a;">Edit Profile</h3>
        <?php if (!empty($_GET['updated'])): ?>
          <div class="alert alert-success" style="border-radius:8px;">Perubahan profile berhasil disimpan.</div>
        <?php elseif (!empty($_GET['error'])): ?>
          <div class="alert alert-danger" style="border-radius:8px;">Terjadi kesalahan: <?= h(urldecode($_GET['error'])) ?></div>
        <?php endif; ?>

        <form action="<?= $BASE_URL ?>/user_profile_action.php" method="post" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" style="font-weight:600;">Nama</label>
              <input type="text" name="name" class="form-control" style="border-radius:8px;" value="<?= h($user['name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" style="font-weight:600;">Tanggal Lahir</label>
              <input type="date" name="birth_date" class="form-control" style="border-radius:8px;" value="<?= h($user['birth_date'] ?? '') ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" style="font-weight:600;">No. Telepon</label>
              <input type="tel" name="phone" class="form-control" style="border-radius:8px;" placeholder="+62..." value="<?= h($user['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" style="font-weight:600;">Email</label>
              <input type="email" name="email" class="form-control" style="border-radius:8px;" value="<?= h($user['email']) ?>" readonly>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-weight:600;">Alamat</label>
            <textarea name="address" class="form-control" rows="3" style="border-radius:8px;" placeholder="Alamat lengkap"><?= h($user['address'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-weight:600;">Bio</label>
            <textarea name="bio" class="form-control" rows="4" style="border-radius:8px;" placeholder="Ceritakan tentang diri Anda"><?= h($user['bio']) ?></textarea>
          </div>
          <div class="mb-4">
            <label class="form-label" style="font-weight:600;">Avatar</label>
            <input type="file" name="avatar" class="form-control" style="border-radius:8px;" accept="image/*">
            <?php if (!empty($user['avatar'])): ?>
              <div class="mt-3">
                <img src="<?= $BASE_URL ?>/assets/uploads/avatars/<?= h($user['avatar']) ?>" style="height:80px; width:80px; object-fit:cover; border-radius:8px; border:2px solid #17A2B8;" alt="Avatar">
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                <label class="form-check-label" for="remove_avatar" style="font-weight:500;">Hapus avatar saat ini</label>
              </div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" style="font-weight:600; padding:10px 24px;">Simpan Perubahan</button>
            <a href="<?= $BASE_URL ?>/user_profile.php" class="btn btn-outline-secondary" style="font-weight:600; padding:10px 24px;">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
