<?php
require_once __DIR__ . '/db.php';
require 'header.php';
$cfg = include __DIR__ . '/config.php';
$BASE_URL = rtrim($cfg['base_url'] ?? '', '/');
$u = currentUser();
// get requested listing id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
  echo '<div class="alert alert-warning">Listing not specified.</div>';
  require 'footer.php';
  exit;
}

$l = fetchListingWithCategory($pdo, $id);
if (!$l) {
  echo '<div class="alert alert-warning">Listing not found.</div>';
  require 'footer.php';
  exit;
}

// Increment views (with error handling in case views column doesn't exist yet)
try {
  $pdo->prepare('UPDATE listings SET views = views + 1 WHERE id = :id')->execute(['id' => $id]);
} catch (Exception $e) {
  // If views column doesn't exist, just continue without incrementing
  // (migration will add it, or runtime will add it later)
}

// Prevent non-admin users from viewing listings that aren't published
// BUT: Allow owners to view their own listings regardless of status
if ($l['status'] !== 'published' && !isAdmin()) {
  $isOwner = $u && isset($u['id']) && (int)$u['id'] === (int)$l['owner_id'];
  if (!$isOwner) {
    echo '<div class="alert alert-warning">Listing not found.</div>';
    require 'footer.php';
    exit;
  }
}

// build gallery array
$gallery = [];
if (!empty($l['thumbnail'])) {
  $parts = array_filter(array_map('trim', explode(',', $l['thumbnail'])));
  foreach ($parts as $p) if ($p !== '') $gallery[] = $p;
}
if (empty($gallery)) {
    $gallery[] = 'https://via.placeholder.com/1280x720?text=No+Image';
}

// category and subcategory names
$category_name = $l['parent_category_name'] ?: $l['category_name'];
$subcategory_name = $l['parent_category_name'] ? $l['category_name'] : null;

// Build map URLs
$mapSrc = '';
$mapLinkRaw = trim($l['map_link'] ?? '');
// Keep building $mapSrc for backward view, but we will no longer embed iframe. We use a dedicated button that opens Google Maps using stored coordinates where possible.
if (!empty($mapLinkRaw)) {
  if (stripos($mapLinkRaw, 'output=embed') !== false || stripos($mapLinkRaw, '/embed') !== false) {
    $mapSrc = $mapLinkRaw;
  } else {
    $mapSrc = 'https://www.google.com/maps?q=' . urlencode($mapLinkRaw) . '&output=embed';
  }
} elseif (!empty($l['latitude']) && !empty($l['longitude'])) {
  $mapSrc = 'https://www.google.com/maps?q=' . urlencode($l['latitude'] . ',' . $l['longitude']) . '&z=15&output=embed';
} else if (!empty($l['address'])) {
  $mapSrc = 'https://www.google.com/maps?q=' . urlencode($l['address']) . '&output=embed';
}

// The user requested to show a button (not an embed), using lat/lng from stored data only.
$mapsButtonUrl = '';
// Prefer an explicit map link if the user provided one; fall back to coordinates when not present.
if (!empty($mapLinkRaw)) {
  $m = $mapLinkRaw;
  // If an embed param or embed path is present, convert it to the view url
  $m = preg_replace('/[?&]output=embed/i', '', $m);
  $m = str_ireplace('/embed', '', $m);
  // If still not an obvious http(s) maps URL, try to fallback to using as a query parameter
  if (stripos($m, 'google.com/maps') === false) {
    $m = 'https://www.google.com/maps?q=' . urlencode($mapLinkRaw);
  }
  $mapsButtonUrl = $m;
} elseif (!empty($l['latitude']) && !empty($l['longitude'])) {
  $mapsButtonUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($l['latitude'] . ',' . $l['longitude']);
}
// phone & website action URLs
$phoneUrl = '';
$websiteUrl = '';
if (!empty($l['phone'])) {
    // sanitize phone for tel: using only digits and plus
    $digits = preg_replace('/[^+0-9]/', '', $l['phone']);
    if ($digits) $phoneUrl = 'tel:' . $digits;
}
if (!empty($l['website'])) {
    $ws = trim($l['website']);
    if (!preg_match('~^https?://~i', $ws)) $ws = 'http://' . $ws;
    $websiteUrl = $ws;
}

// fetch comments (rating summary dan comments list)
$stmt = $pdo->prepare('SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM comments WHERE listing_id = :listing_id AND status = :status AND rating IS NOT NULL');
$stmt->execute(['listing_id' => $id, 'status' => 'approved']);
$ratingData = $stmt->fetch();
$ratingSummary = ['total' => (int)$ratingData['total'], 'avg' => $ratingData['avg_rating'] !== null ? round($ratingData['avg_rating'], 2) : null];

$stmt = $pdo->prepare('SELECT * FROM comments WHERE listing_id = :listing_id AND status = :status ORDER BY created_at DESC LIMIT 50');
$stmt->execute(['listing_id' => $id, 'status' => 'approved']);
$reviews = $stmt->fetchAll();

?>
<script>
  window.CURRENT_LISTING_ID = <?= intval($l['id']) ?>;
</script>

<div class="row g-3 listing-detail">
    <div class="col-12 mb-2">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <a href="<?= $BASE_URL ?>/index.php" class="btn btn-back btn-back-modern" aria-label="Kembali ke daftar">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
          <span class="d-none d-sm-inline">Kembali ke daftar</span>
          <span class="d-inline d-sm-none">Kembali</span>
        </a>
      </div>
    </div>
  <div class="col-12 col-md-7">
    <div class="card p-0 card-listing" style="border-radius:16px; overflow:hidden;">
      <div class="main-photo">
        <img id="mainPhoto" src="<?= h($gallery[0]) ?>" class="img-fluid" alt="<?= h($l['title']) ?>" style="width:100%;height:auto;object-fit:cover;">
      </div>
      <?php if (count($gallery) > 1): ?>
      <div class="p-3 d-flex gap-2 flex-row overflow-auto align-items-center small media-grid">
        <?php foreach ($gallery as $g): ?>
          <img src="<?= h($g) ?>" class="thumb rounded" style="width:120px;cursor:pointer;height:70px;object-fit:cover;" data-src="<?= h($g) ?>">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="p-3">
        <h5 class="mb-1">Tentang</h5>
        <p class="mb-0 small-muted"><?= nl2br(h($l['description'])) ?></p>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-5">
    <div class="card p-3" style="border-radius:16px; box-shadow:0 8px 28px rgba(186,255,57,0.08);">
      <div class="d-flex align-items-start justify-content-between gap-3">
        <div class="flex-grow-1">
          <h2 class="mb-1 h4 fw-bold"><?= h($l['title']) ?></h2>
          <div class="mb-2 small-muted">
            <span class="badge-cat me-1"><?= h($category_name) ?></span>
            <?php if ($subcategory_name): ?><span class="small-muted">/ <?= h($subcategory_name) ?></span><?php endif; ?>
            <span class="ms-2">· <?= h($l['created_at']) ?></span>
            <div class="mt-2"><strong>Dipublikasikan oleh:</strong> <?= h($l['owner_name'] ?? '-') ?></div>
          </div>
        </div>
        <div class="text-end">
          <!-- Bookmark action moved to navbar icon (single button). -->
        </div>
      </div>
      <div class="rating-summary text-end small-muted">
          <?php if ($ratingSummary['avg'] !== null): ?>
            <div><strong><?= number_format($ratingSummary['avg'], 1) ?></strong> / 5</div>
            <div class="stars" title="<?= $ratingSummary['total'] ?> reviews">
              <?php $avgRound = round($ratingSummary['avg']); for ($i=1;$i<=5;$i++): ?>
                <span class="star" style="color:<?= $i <= $avgRound ? '#FF9500' : '#FFFFFF'; ?>">&#9733;</span>
              <?php endfor; ?>
            </div>
          <?php else: ?>
            <div class="small-muted">Belum ada rating</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="mb-2 small-muted">Category: <strong><?= h($category_name) ?></strong> <?= $subcategory_name ? '<span class="small">/ ' . h($subcategory_name) . '</span>' : '' ?></div>
      <hr>
      <div class="mb-3">
        <strong>Kontak</strong>
        <div class="mt-2 d-flex gap-2 flex-wrap">
          <?php if (!empty($phoneUrl)): ?><a class="contact-button phone-button" href="<?= h($phoneUrl) ?>">📞 Panggil</a><?php endif; ?>
          <?php if (!empty($websiteUrl)): ?><a class="contact-button website-button" href="<?= h($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">🌐 Website</a><?php endif; ?>
          <?php if (!empty($mapsButtonUrl)): ?>
            <a class="contact-button maps-button open-maps-button" href="<?= h($mapsButtonUrl) ?>" target="_blank" rel="noopener noreferrer">📍 Buka di Google Maps</a>
          <?php endif; ?>
        </div>
      </div>
      <p><strong>Jam operasional: </strong><?= nl2br(h($l['opening_hours'] ?? 'Belum tersedia')) ?></p>

      <hr>
      <div class="comments-section">
        <h5>Komentar</h5>
        
        <?php if (isLoggedIn()): ?>
          <form id="comment-form" class="mb-4">
            <input type="hidden" name="listing_id" value="<?= h($l['id']) ?>">
            <div class="mb-3">
              <label class="form-label">Rating (Opsional)</label>
              <div class="rating-input">
                <?php for ($i=5; $i>=1; $i--): ?>
                  <input type="radio" id="rating-<?= $i ?>" name="rating" value="<?= $i ?>" style="display:none">
                  <label for="rating-<?= $i ?>" class="star-label" style="cursor:pointer; font-size:24px; color:#ffffff; margin-right:8px; transition: color 0.2s;">&#9733;</label>
                <?php endfor; ?>
              </div>
            </div>
            <div class="mb-3">
              <label for="comment-text" class="form-label">Komentar</label>
              <textarea id="comment-text" name="comment_text" class="form-control" rows="3" placeholder="Bagikan pengalaman Anda dengan listing ini..." required></textarea>
              <small class="text-muted">Komentar Anda akan ditinjau sebelum ditampilkan.</small>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Komentar</button>
            <div id="comment-message" class="mt-2"></div>
          </form>
        <?php else: ?>
          <div class="alert alert-info mb-3">
            Silahkan <a href="<?= $BASE_URL ?>/login.php">login</a> untuk memberikan komentar dan rating.
          </div>
        <?php endif; ?>

        <div id="comments-list" class="mt-4">
          <!-- Comments will be loaded here by JavaScript -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Sponsors Section (Outside Card) -->
<!-- Sponsors Section (Homepage-style client loader) -->
<div class="sponsors-section mt-5 pt-4 pb-4" style="background: #ffffff; border-radius: 12px;">
  <div class="container">
    <h5 class="text-center mb-4" style="font-weight: 700; color: #333; font-size: 1rem;">
      <i class="fa fa-handshake" style="color: #17A2B8; margin-right: 8px;"></i>
      Partner & Sponsor Kami
    </h5>
    
    <div class="row g-3" id="sponsors-container" style="justify-content: center;">
      <div class="col-12 text-center">
        <p class="text-muted">Loading sponsors...</p>
      </div>
    </div>
  </div>
</div>

<!-- Sponsor responsive tweaks: 5 columns per row on large screens -->
<style>
  @media (min-width: 992px) {
    .sponsor-col { flex: 0 0 20%; max-width: 20%; }
  }
  @media (min-width: 768px) and (max-width: 991.98px) {
    .sponsor-col { flex: 0 0 25%; max-width: 25%; }
  }

  /* Comments and sponsors tweaks for listing detail */
  .comments-section .comment-item { box-sizing: border-box; word-wrap: break-word; }
  .comments-section #comments-list { display: block; }
  .sponsors-section .row { align-items: center; }
</style>

<script>
  // Load sponsors (reuse homepage logic so layout matches exactly)
  async function loadSponsorsOnDetail() {
    try {
      const response = await fetch('<?= $BASE_URL ?>/api/sponsors.php?action=list');
      const result = await response.json();
      const container = document.getElementById('sponsors-container');
      if (!container) return;
      container.innerHTML = '';
      if (result.success && result.sponsors.length > 0) {
        // show first 10 sponsors, add "More sponsors" button if more exist
        const all = result.sponsors;
        const firstBatch = all.slice(0, 10);
        const rest = all.slice(10);

        function renderSponsor(sponsor, index) {
          const col = document.createElement('div');
            col.className = 'sponsor-col col-6 col-sm-4 col-md-3 col-lg-2';
          let cardContent = '';
          if (sponsor.logo_file) {
            cardContent = `\n              <img src="${sponsor.logo_file}" alt="Sponsor Logo"\n                   style="max-height: 100px; max-width: 100%; object-fit: contain; transition: transform 0.3s, filter 0.3s;"\n                   class="sponsor-logo">\n            `;
          } else {
            cardContent = `\n              <div style="height: 100px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border-radius: 8px;">\n                <span style="color: #999; font-size: 0.9rem; text-align: center;">${sponsor.name}</span>\n              </div>\n            `;
          }
          const linkHref = sponsor.listing_id ? `<?= $BASE_URL ?>/listing_view.php?id=${sponsor.listing_id}` : '#';
          const clickHandler = sponsor.listing_id ? '' : 'event.preventDefault();';
          col.innerHTML = `\n            <a href="${linkHref}" class="sponsor-card d-flex justify-content-center align-items-center" \n                 style="padding: 15px; cursor: ${sponsor.listing_id ? 'pointer' : 'default'}; transition: all 0.3s; border-radius: 8px; text-decoration: none; display: block;"\n                 onclick="${clickHandler}">\n              ${cardContent}\n            </a>\n          `;
          const card = col.querySelector('.sponsor-card');
          if (card) {
            card.addEventListener('mouseenter', function() {
              const logo = this.querySelector('.sponsor-logo');
              if (logo) {
                logo.style.transform = 'scale(1.1)';
                logo.style.filter = 'drop-shadow(0 4px 8px rgba(186, 255, 57, 0.3))';
              }
            });
            card.addEventListener('mouseleave', function() {
              const logo = this.querySelector('.sponsor-logo');
              if (logo) {
                logo.style.transform = 'scale(1)';
                logo.style.filter = 'drop-shadow(0 0 0 rgba(186, 255, 57, 0))';
              }
            });
          }
          return col;
        }

        // render first batch
        firstBatch.forEach((s, idx) => container.appendChild(renderSponsor(s, idx)));

        // if more, show a button to reveal the rest
        if (rest.length > 0) {
          const btnCol = document.createElement('div');
          btnCol.className = 'col-12 text-center mt-2';
          const btn = document.createElement('button');
          btn.className = 'btn btn-outline-secondary';
          btn.textContent = `More sponsors (${rest.length})`;
          btn.addEventListener('click', function() {
            rest.forEach((s, idx) => container.insertBefore(renderSponsor(s, firstBatch.length + idx), btnCol));
            btnCol.remove();
          });
          btnCol.appendChild(btn);
          container.appendChild(btnCol);
        }
      } else {
        container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Belum ada sponsor</p></div>';
      }
    } catch (error) {
      console.error('Error loading sponsors:', error);
      const container = document.getElementById('sponsors-container');
      if (container) container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Belum ada sponsor</p></div>';
    }
  }
  document.addEventListener('DOMContentLoaded', loadSponsorsOnDetail);
</script>

<script>
  // Sponsor hover effects
  document.querySelectorAll('.sponsor-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
      const logo = this.querySelector('.sponsor-logo');
      if (logo) {
        logo.style.transform = 'scale(1.1)';
        logo.style.filter = 'drop-shadow(0 4px 8px rgba(186, 255, 57, 0.3))';
      }
    });
    card.addEventListener('mouseleave', function() {
      const logo = this.querySelector('.sponsor-logo');
      if (logo) {
        logo.style.transform = 'scale(1)';
        logo.style.filter = 'drop-shadow(0 0px 0px rgba(0, 0, 0, 0))';
      }
    });
  });

  const BASE_URL = '<?php echo $BASE_URL; ?>';
  const listingId = <?= (int)$l['id']; ?>;

  // Load comments on page load
  function loadComments() {
    fetch(`${BASE_URL}/api/comments.php?action=list&listing_id=${listingId}&status=approved`)
      .then(r => r.json())
      .then(result => {
        if (!result.success) {
          document.getElementById('comments-list').innerHTML = '<p class="text-muted">Gagal memuat komentar</p>';
          return;
        }

        const comments = result.data;
        if (comments.length === 0) {
          document.getElementById('comments-list').innerHTML = '<p class="text-muted">Belum ada komentar untuk listing ini.</p>';
          return;
        }

        const html = comments.map(c => `
          <div class="comment-item mb-3 p-3" style="border-radius: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <strong>${escapeHtml(c.name || '-')}</strong>
                <br>
                <small class="text-muted">${new Date(c.created_at).toLocaleDateString('id-ID', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                })}</small>
              </div>
              ${c.rating ? `<div class="badge bg-warning text-dark">${c.rating}★</div>` : ''}
            </div>
            <p class="mb-0">${escapeHtml(c.comment_text).replace(/\n/g, '<br>')}</p>
          </div>
        `).join('');

        document.getElementById('comments-list').innerHTML = html;
      })
      .catch(err => {
        console.error('Error loading comments:', err);
        document.getElementById('comments-list').innerHTML = '<p class="text-muted">Gagal memuat komentar</p>';
      });
  }

  // Submit comment
  const commentForm = document.getElementById('comment-form');
  if (commentForm) {
    commentForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const messageDiv = document.getElementById('comment-message');
      const rating = document.querySelector('input[name="rating"]:checked')?.value || null;
      const comment_text = document.getElementById('comment-text').value.trim();

      if (!comment_text) {
        messageDiv.innerHTML = '<div class="alert alert-warning">Komentar tidak boleh kosong</div>';
        return;
      }

      try {
        const response = await fetch(`${BASE_URL}/api/comments.php?action=create`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            listing_id: listingId,
            comment_text: comment_text,
            rating: rating ? parseInt(rating) : null
          })
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
          messageDiv.innerHTML = '<div class="alert alert-success">Komentar Anda telah dikirim dan menunggu persetujuan admin.</div>';
          document.getElementById('comment-text').value = '';
          document.querySelectorAll('input[name="rating"]').forEach(r => r.checked = false);
          
          setTimeout(() => {
            loadComments();
          }, 1000);
        } else {
          messageDiv.innerHTML = `<div class="alert alert-danger">Error: ${escapeHtml(result.error)}</div>`;
        }
      } catch (error) {
        console.error('Error submitting comment:', error);
        messageDiv.innerHTML = '<div class="alert alert-danger">Gagal mengirim komentar. Silakan coba lagi.</div>';
      }
    });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Load comments on page load
  loadComments();

  // Rating stars visual feedback
  const ratingLabels = document.querySelectorAll('.rating-input .star-label');
  ratingLabels.forEach((label, idx) => {
    label.addEventListener('mouseenter', () => {
      ratingLabels.forEach((l, i) => {
        l.style.color = i >= (4 - idx) ? '#FF9500' : '#ffffff';
      });
    });
  });

  document.querySelector('.rating-input')?.addEventListener('mouseleave', () => {
    const checked = document.querySelector('input[name="rating"]:checked');
    ratingLabels.forEach((l, i) => {
      if (checked) {
        const checkedValue = parseInt(checked.value);
        l.style.color = i >= (5 - checkedValue) ? '#FF9500' : '#ffffff';
      } else {
        l.style.color = '#ffffff';
      }
    });
  });

  // Update star colors when selected
  document.querySelectorAll('input[name="rating"]').forEach(input => {
    input.addEventListener('change', () => {
      const value = parseInt(input.value);
      ratingLabels.forEach((l, i) => {
        l.style.color = i >= (5 - value) ? '#FF9500' : '#ffffff';
      });
    });
  });
</script>

<!-- Bookmark action moved to navbar; per-page 'Simpan' removed. -->

<?php require 'footer.php'; ?>
