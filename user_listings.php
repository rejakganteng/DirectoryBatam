<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require 'header.php';

requireLogin();
$user = currentUser();

// Fetch listings owned by this user with owner info
$stmt = $pdo->prepare('SELECT l.*, c.name AS category_name, p.name AS parent_category_name, u.name AS owner_name FROM listings l LEFT JOIN categories c ON l.category_id = c.id LEFT JOIN categories p ON c.parent_id = p.id LEFT JOIN users u ON l.owner_id = u.id WHERE l.owner_id = :owner_id ORDER BY l.created_at DESC');
$stmt->execute(['owner_id' => $user['id']]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [
  'total' => count($listings),
  'published' => 0,
  'pending' => 0,
  'draft' => 0,
  'banned' => 0,
  'total_views' => 0,
  'total_comments' => 0,
  'total_rating' => 0
];

// Note: Views counting logic assumes views are incremented by viewing the listing
// We cannot easily distinguish owner views from other views with current schema
// so we'll display all views but ideally you'd want to track this separately

foreach ($listings as $l) {
  $stats[$l['status']]++;
  $stats['total_views'] += (int)$l['views'];
  
  // Get comment count and rating for this listing
  $stmtComment = $pdo->prepare('SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM comments WHERE listing_id = :listing_id AND status = :status');
  $stmtComment->execute(['listing_id' => $l['id'], 'status' => 'approved']);
  $commentData = $stmtComment->fetch();
  $stats['total_comments'] += (int)$commentData['cnt'];
  if ($commentData['avg_rating']) {
    $stats['total_rating'] += round($commentData['avg_rating'], 1);
  }
}

?>
<div class="row mt-4">
  <div class="col-12">
    <div class="d-flex align-items-center gap-3 mb-3">
      <a href="<?= $BASE_URL ?>/" class="btn btn-outline-secondary" style="color:#0f172a; background:#ffffff; border-color:#0f172a; width: 44px; height: auto; padding: 8px 10px; display: flex; align-items: center; justify-content: center;" title="Kembali" aria-label="Kembali">
        <i class="fa fa-arrow-left" style="font-size: 16px;"></i>
        <span class="visually-hidden">Kembali</span>
      </a>
      <h3 style="margin: 0;"><i class="fa fa-history" style="color: #17A2B8; margin-right: 10px;"></i>Riwayat Listing Saya</h3>
    </div>
    <p class="text-muted">Menampilkan semua listing yang Anda tambahkan. Anda dapat melihat status dan detail listing dari sini.</p>
  </div>
</div>

<!-- Dashboard Stats -->
<div class="row g-3 mt-2 mb-4">
  <div class="col-12 col-sm-6 col-md-3">
    <div class="card p-3" style="border-radius:12px; border-left: 4px solid #17A2B8;">
      <div class="small text-muted">Total Listing</div>
      <div class="h4 mb-0" style="font-weight: 700; color: #0f172a;"><?= $stats['total'] ?></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-md-3">
    <div class="card p-3" style="border-radius:12px; border-left: 4px solid #28a745;">
      <div class="small text-muted">Disetujui (Published)</div>
      <div class="h4 mb-0" style="font-weight: 700; color: #28a745;"><?= $stats['published'] ?></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-md-3">
    <div class="card p-3" style="border-radius:12px; border-left: 4px solid #ffc107;">
      <div class="small text-muted">Pending</div>
      <div class="h4 mb-0" style="font-weight: 700; color: #ffc107;"><?= $stats['pending'] ?></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-md-3">
    <div class="card p-3" style="border-radius:12px; border-left: 4px solid #dc3545;">
      <div class="small text-muted">Draft</div>
      <div class="h4 mb-0" style="font-weight: 700; color: #6c757d;"><?= $stats['draft'] ?></div>
    </div>
  </div>
</div>

<!-- Overall Stats -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-4">
    <div class="card p-3" style="border-radius:12px; background: #f8f9fa;">
      <div class="small text-muted"><i class="fa fa-eye" style="margin-right: 6px;"></i>Total Kunjungan</div>
      <div class="h5 mb-0" style="font-weight: 700;"><?= number_format($stats['total_views']) ?></div>
    </div>
  </div>
  <div class="col-12 col-sm-4">
    <div class="card p-3" style="border-radius:12px; background: #f8f9fa;">
      <div class="small text-muted"><i class="fa fa-comments" style="margin-right: 6px;"></i>Total Komentar</div>
      <div class="h5 mb-0" style="font-weight: 700;"><?= $stats['total_comments'] ?></div>
    </div>
  </div>
  <div class="col-12 col-sm-4">
    <div class="card p-3" style="border-radius:12px; background: #f8f9fa;">
      <div class="small text-muted"><i class="fa fa-star" style="margin-right: 6px; color: #FF9500;"></i>Rata-rata Rating</div>
      <div class="h5 mb-0" style="font-weight: 700;"><?= $stats['total_comments'] > 0 ? number_format($stats['total_rating'] / count($listings), 2) : 'N/A' ?></div>
    </div>
  </div>
</div>


<div class="row g-3 mt-2">
  <?php if (!$listings || count($listings) === 0): ?>
    <div class="col-12">
      <div class="alert alert-info">Anda belum menambahkan listing apapun. <a href="<?= $BASE_URL ?>/listing_create.php">Tambah Listing</a></div>
    </div>
  <?php else: ?>
    <?php foreach ($listings as $l): ?>
      <?php
        $thumb = !empty($l['thumbnail']) ? explode(',', $l['thumbnail'])[0] : 'https://via.placeholder.com/640x360?text=No+Image';
        $category_name = $l['parent_category_name'] ?: $l['category_name'];
        $status = $l['status'] ?? 'draft';
        $curr = currentUser();
        $isOwner = $curr && isset($curr['id']) && ((int)$curr['id'] === (int)$l['owner_id']);
        
        // Get comment count and rating for this listing
        $stmtStats = $pdo->prepare('SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM comments WHERE listing_id = :listing_id AND status = :status');
        $stmtStats->execute(['listing_id' => $l['id'], 'status' => 'approved']);
        $listingStats = $stmtStats->fetch();
        $commentCount = (int)$listingStats['cnt'];
        $avgRating = $listingStats['avg_rating'] ? round($listingStats['avg_rating'], 1) : 0;
      ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-listing">
          <a class="card-link d-block text-decoration-none text-reset" href="<?= $BASE_URL ?>/listing_view.php?id=<?= (int)$l['id'] ?>">
            <img src="<?= h($thumb) ?>" alt="<?= h($l['title']) ?>">
            <div class="card-body p-3">
              <h5 class="card-title mb-1"><?= h($l['title']) ?></h5>
              <div class="card-meta mb-2"><span class="badge-cat"><?= h($category_name) ?></span></div>
              <p class="description-clamp mb-2"><?= h($l['description'] ?: '') ?></p>
              <p class="small-muted mb-0">Dipublikasikan oleh: <strong><?= h($l['owner_name'] ?? '-') ?></strong><br>Status: <strong><?= h($status) ?></strong> · Dibuat: <?= h($l['created_at']) ?></p>
            </div>
          </a>
          <div class="card-footer py-2" style="background: #f8f9fa;">
            <div class="row g-2 text-center small">
              <div class="col-4">
                <i class="fa fa-eye" style="margin-right: 4px; color: #6c757d;"></i>
                <div style="font-weight: 600;"><?= number_format($l['views'] ?? 0) ?></div>
                <div class="text-muted" style="font-size: 0.8rem;">Views</div>
              </div>
              <div class="col-4">
                <i class="fa fa-comments" style="margin-right: 4px; color: #6c757d;"></i>
                <div style="font-weight: 600;"><?= $commentCount ?></div>
                <div class="text-muted" style="font-size: 0.8rem;">Komentar</div>
              </div>
              <div class="col-4">
                <i class="fa fa-star" style="margin-right: 4px; color: #FF9500;"></i>
                <div style="font-weight: 600;"><?= $avgRating > 0 ? $avgRating : '-' ?></div>
                <div class="text-muted" style="font-size: 0.8rem;">Rating</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
