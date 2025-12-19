<?php
require_once __DIR__ . '/db.php';
require 'header.php';

$catsFlat = fetchAllCategories($pdo);
$cats = buildCategoryTree($catsFlat);
?>


<div class="filters-wrapper mb-3">
  <form id="filters-form" class="filters row g-2">
    <div class="col-auto">
      <input type="text" id="filter-search" name="search" class="form-control" placeholder="Search title or description">
    </div>
    <div class="col-auto">
      <select name="category" id="filter-category" class="form-select">
        <option value="0">All categories</option>
        <?php foreach ($catsFlat as $c): ?>
          <?php if (!$c['parent_id']): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="subcategory" id="filter-subcategory" class="form-select">
        <option value="0">-- All subcategories --</option>
      </select>
    </div>
    <div class="col-auto">
      <select id="filter-limit" class="form-select">
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="30">30</option>
        <option value="40">40</option>
        <option value="50">50</option>
      </select>
    </div>
    <div class="col-auto">
      <select id="filter-sort" class="form-select">
        <option value="latest">Latest Added</option>
        <option value="oldest">Oldest Added</option>
        <option value="name_asc">Name (A–Z)</option>
        <option value="name_desc">Name (Z–A)</option>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary" type="button" id="btn-filter">Apply</button>
    </div>
    
    <div class="col-auto ms-auto">
      <!-- Add Listing moved to floating action button -->
    </div>
  </form>
</div>

<!-- Category pills: horizontal filters rendered here -->
<div id="category-pills" class="category-pills mb-3"></div>

<!-- Homepage sections: Popular and Recent -->
<div id="homepage-sections" style="display:none;">
  <div id="popular-section" class="mb-5">
    <h4 class="mb-3" style="border-bottom: 2px solid #17A2B8; padding-bottom: 10px;">
      <i class="fa fa-fire" style="color:#17A2B8;"></i> Paling Sering Dikunjungi
    </h4>
    <div id="popular-listings" class="listings-grid"></div>
  </div>

  <hr style="border: 1px solid #ddd; margin: 40px 0;">

  <div id="recent-section">
    <h4 class="mb-3" style="border-bottom: 2px solid #17A2B8; padding-bottom: 10px;">
      <i class="fa fa-clock" style="color:#17A2B8;"></i> Baru Ditambahkan
    </h4>
    <div id="recent-listings" class="listings-grid"></div>
  </div>

  <div class="text-center my-4" id="homepage-show-all">
    <button id="show-all-listings-btn" class="btn btn-outline-primary">Lihat Semua Listing</button>
  </div>
</div>

<!-- Standard listings container (for filtered view) -->
<div id="listings-container"></div>

<!-- Sponsors Section -->
<div class="sponsors-section mt-5 pt-4 pb-4" style="background: #ffffff; border-radius: 12px;">
  <div class="container-fluid">
    <h5 class="text-center mb-4" style="font-weight: 700; color: #333; font-size: 1rem;">
      <i class="fa fa-handshake" style="color: #17A2B8; margin-right: 8px;"></i>
      Partner & Sponsor Kami
    </h5>
    
    <div class="row g-3" id="sponsors-container" style="justify-content: center;">
      <!-- Sponsors will be loaded here via JavaScript -->
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
</style>
<script>
  window.CATEGORIES_TREE = <?= json_encode($cats, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
  window.IS_HOMEPAGE = true; // Flag to indicate we're on homepage
  
  // Load sponsors (limit to first 10, show "More sponsors" for rest)
  async function loadSponsors() {
    try {
      const response = await fetch('<?= $BASE_URL ?>/api/sponsors.php?action=list');
      const result = await response.json();

      if (result.success && result.sponsors.length > 0) {
        const container = document.getElementById('sponsors-container');
        container.innerHTML = '';

        const all = result.sponsors;
        const firstBatch = all.slice(0, 10);
        const rest = all.slice(10);

        function renderSponsor(sponsor, index) {
          const col = document.createElement('div');
          col.className = 'sponsor-col col-6 col-sm-4 col-md-3 col-lg-2';
          let cardContent = '';
          if (sponsor.logo_file) {
            cardContent = `
              <img src="${sponsor.logo_file}" alt="Sponsor Logo"
                   style="max-height: 100px; max-width: 100%; object-fit: contain; transition: transform 0.3s, filter 0.3s;"
                   class="sponsor-logo">
            `;
          } else {
            cardContent = `
              <div style="height: 100px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border-radius: 8px;">
                <span style="color: #999; font-size: 0.9rem; text-align: center;">${sponsor.name}</span>
              </div>
            `;
          }

          const linkHref = sponsor.listing_id ? `<?= $BASE_URL ?>/listing_view.php?id=${sponsor.listing_id}` : '#';
          const clickHandler = sponsor.listing_id ? '' : 'event.preventDefault();';

          col.innerHTML = `
            <a href="${linkHref}" class="sponsor-card d-flex justify-content-center align-items-center"
               style="padding: 15px; cursor: ${sponsor.listing_id ? 'pointer' : 'default'}; transition: all 0.3s; border-radius: 8px; text-decoration: none; display: block;"
               onclick="${clickHandler}">
              ${cardContent}
            </a>
          `;

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
      }
    } catch (error) {
      console.error('Error loading sponsors:', error);
      const container = document.getElementById('sponsors-container');
      container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Belum ada sponsor</p></div>';
    }
  }

  // Load sponsors when page loads
  document.addEventListener('DOMContentLoaded', loadSponsors);
</script>
<?php require 'footer.php'; ?>