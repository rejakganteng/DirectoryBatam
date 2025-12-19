<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin(); // Ensure user is logged in
function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
$BASE_URL = rtrim((include __DIR__ . '/config.php')['base_url'] ?? '', '/');

// Determine profile id: ?id= or current user
$id = isset($_GET['id']) ? (int)$_GET['id'] : currentUser()['id'];

// fetch user
$stmt = $pdo->prepare('SELECT id, name, role, email, created_at, avatar, bio, birth_date, phone, address FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
  http_response_code(404);
  echo "User not found";
  exit;
}

// fetch user's listings
$listStmt = $pdo->prepare('SELECT id, title, slug, thumbnail, created_at, status FROM listings WHERE owner_id = ? AND status = "published" ORDER BY created_at DESC LIMIT 12');
$listStmt->execute([$id]);
$listings = $listStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>
<div class="container" style="max-width: 900px; padding-top: 40px; padding-bottom: 40px;">
  <!-- Header Section -->
  <div class="text-center mb-5">
    <?php if (!empty($user['avatar'])): ?>
      <img src="<?= $BASE_URL ?>/assets/uploads/avatars/<?= h($user['avatar']) ?>" alt="Avatar" style="width:150px; height:150px; object-fit:cover; border-radius:50%; border: 4px solid #17A2B8;">
    <?php else: ?>
      <div style="width:150px; height:150px; border-radius:50%; background:#e9ecef; display:flex; align-items:center; justify-content:center; font-size:48px; font-weight:700; color:#6c757d; margin:0 auto; border: 4px solid #17A2B8;">
        <?= h(substr($user['name'],0,1) ?: 'U') ?>
      </div>
    <?php endif; ?>
    <h2 class="mt-4" style="font-weight:700;"><?= h($user['name']) ?></h2>
    <p class="text-muted" style="margin-bottom: 2rem;">Bergabung sejak <?= date('d M Y', strtotime($user['created_at'])) ?></p>
  </div>

  <!-- Info & Actions Row -->
  <div class="row mb-5">
    <div class="col-md-6">
      <div class="card p-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h6 style="font-weight:700; margin-bottom: 1rem;">Informasi Kontak</h6>
        <p class="mb-2">
          <i class="fa fa-envelope" style="color:#17A2B8; width:24px;"></i>
          <span><?= h($user['email']) ?></span>
        </p>
        <?php if (!empty($user['phone'])): ?>
        <p class="mb-2">
          <i class="fa fa-phone" style="color:#17A2B8; width:24px;"></i>
          <span><?= h($user['phone']) ?></span>
        </p>
        <?php endif; ?>
        <?php if (!empty($user['birth_date'])): ?>
        <p class="mb-2">
          <i class="fa fa-birthday-cake" style="color:#17A2B8; width:24px;"></i>
          <span><?= date('d M Y', strtotime($user['birth_date'])) ?></span>
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php if (isLoggedIn() && currentUser()['id'] == $user['id']): ?>
    <div class="col-md-6">
      <div class="d-grid gap-2">
        <a href="<?= $BASE_URL ?>/user_profile_edit.php" class="btn btn-primary" style="font-weight:600;"><i class="fa fa-edit" style="margin-right: 8px;"></i>Edit Profile</a>
        <a href="<?= $BASE_URL ?>/user_change_password.php" class="btn btn-outline-warning" style="font-weight:600;"><i class="fa fa-key" style="margin-right: 8px;"></i>Ganti Password</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bio Section -->
  <div class="card p-4 mb-5" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h5 style="font-weight:700; margin-bottom: 1.5rem;">Tentang</h5>
    <p class="text-muted" style="line-height: 1.6; font-size: 1.02rem;"><?= nl2br(h($user['bio'] ?? 'Belum ada bio.')) ?></p>
  </div>

  <!-- Address Section -->
  <?php if (!empty($user['address'])): ?>
  <div class="card p-4 mb-5" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h5 style="font-weight:700; margin-bottom: 1.5rem;">Alamat</h5>
    <p class="text-muted" style="line-height: 1.6; font-size: 1.02rem;"><i class="fa fa-map-marker-alt" style="color:#17A2B8; margin-right: 8px;"></i><?= nl2br(h($user['address'])) ?></p>
  </div>
  <?php endif; ?>

  <!-- Additional Actions -->
  <?php if (isLoggedIn() && currentUser()['id'] == $user['id']): ?>
  <div class="text-center" style="padding-top: 1.5rem; border-top: 1px solid #dee2e6;">
    <a href="<?= $BASE_URL ?>/user_listings.php" class="btn btn-outline-secondary me-2"><i class="fa fa-history" style="margin-right: 6px;"></i>Riwayat Saya</a>
    <a href="<?= $BASE_URL ?>/logout.php" class="btn btn-outline-danger"><i class="fa fa-sign-out-alt" style="margin-right: 6px;"></i>Logout</a>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
