<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
if (!isset($BASE_URL)) {
    $cfg = include __DIR__ . '/config.php';
    $BASE_URL = rtrim($cfg['base_url'] ?? '', '/');
}

$footer = $pdo->query('SELECT * FROM footer WHERE id = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
// Fallbacks
$about = $footer['about'] ?? 'Direktori lokal yang memuat tempat wisata, kuliner, layanan, dan bisnis lokal di Batam. Temukan tempat terbaik dengan mudah.';
$address = $footer['address'] ?? 'Batam, Kepulauan Riau';
$phone = $footer['phone'] ?? '0778-123456';
$email = $footer['email'] ?? 'info@example.com';
$facebook = $footer['facebook'] ?? '';
$instagram = $footer['instagram'] ?? '';
$whatsapp = $footer['whatsapp'] ?? '';
$copyright_text = $footer['copyright_text'] ?? ('© ' . date('Y') . ' Direktori Batam');
?>
  </div><!-- container -->

  <footer class="site-footer mt-5 bg-white">
    <style>
      .site-footer {
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
      }
      .footer-section h5, .footer-section h6 {
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #1a1a2e;
      }
      .footer-section a {
        color: #333333;
        text-decoration: none;
        transition: color 0.3s ease;
        font-size: 0.95rem;
      }
      .footer-section a:hover {
        color: #17A2B8;
      }
      .footer-section ul li {
        padding: 0.35rem 0;
      }
      .footer-divider {
        background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
        height: 1px;
        margin: 2rem 0;
      }
      .social-icons {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
      }
      /* Center heading and icons in social section */
      .footer-section:has(.social-icons) {
        text-align: center;
      }
      .footer-section:has(.social-icons) h6 {
        margin-bottom: 1.5rem;
      }
      .social-icons a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: transparent;
        border: 1px solid rgba(23,162,184,0.14);
        border-radius: 50%;
        transition: background 0.18s ease, transform 0.12s ease, color 0.18s ease;
        color: #17A2B8;
      }
      .social-icons a svg {
        width: 18px;
        height: 18px;
        display: block;
        fill: currentColor;
      }
      .social-icons a:hover {
        background: #17A2B8;
        color: white;
        transform: translateY(-2px);
      }
      .footer-contact-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        color: #333333;
      }
      .footer-contact-item i {
        color: #17A2B8;
        margin-right: 12px;
        width: 20px;
      }
      .footer-copyright {
        border-top: 1px solid #e5e7eb;
        padding-top: 2rem;
        text-align: center;
        color: #666666;
        font-size: 0.9rem;
      }
      .about-text {
        color: #555555;
      }
    </style>

    <div class="container py-5">
      <div class="row gy-4">
        <!-- About Section -->
        <div class="col-12 col-md-3 footer-section">
          <h5 class="mb-3">
            <i class="fa fa-map-marker-alt" style="color: #17A2B8; margin-right: 8px;"></i>
            Direktori Batam
          </h5>
          <p class="small mb-0 about-text"><?= h($about) ?></p>
        </div>

        <!-- Quick Links -->
        <div class="col-12 col-md-3 footer-section">
          <h6 class="mb-3">Link Cepat</h6>
          <ul class="list-unstyled small">
            <li><a href="<?= $BASE_URL ?>/index.php"><i class="fa fa-chevron-right" style="width: 12px;"></i> Listings</a></li>
            <li><a href="<?= $BASE_URL ?>/login.php"><i class="fa fa-chevron-right" style="width: 12px;"></i> Login</a></li>
            <li><a href="<?= $BASE_URL ?>/register.php"><i class="fa fa-chevron-right" style="width: 12px;"></i> Daftar</a></li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div class="col-12 col-md-2 footer-section">
          <h6 class="mb-3">Kontak</h6>
          <div class="footer-contact-item">
            <i class="fa fa-envelope"></i>
            <a href="mailto:<?= h($email) ?>"><?= h($email) ?></a>
          </div>
          <div class="footer-contact-item">
            <i class="fa fa-phone"></i>
            <a href="tel:<?= h($phone) ?>"><?= h($phone) ?></a>
          </div>
          <div class="footer-contact-item">
            <i class="fa fa-map-pin"></i>
            <span><?= h($address) ?></span>
          </div>
        </div>

        <!-- Social Media -->
        <div class="col-12 col-md-2 footer-section">
          <h6 class="mb-3">Ikuti Kami</h6>
          <div class="social-icons" aria-hidden="false">
                <?php if ($facebook): ?>
                  <a href="<?= h($facebook) ?>" target="_blank" title="Facebook" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" role="img">
                      <path d="M22 12.07C22 6.48 17.52 2 11.93 2S2 6.48 2 12.07c0 4.99 3.66 9.12 8.44 9.93v-7.03H8.08v-2.9h2.36V9.41c0-2.34 1.39-3.63 3.52-3.63 1.02 0 2.08.18 2.08.18v2.29h-1.17c-1.16 0-1.52.72-1.52 1.46v1.76h2.59l-.41 2.9h-2.18V22c4.78-.81 8.44-4.94 8.44-9.93z" />
                    </svg>
                  </a>
                <?php endif; ?>
                <?php if ($instagram): ?>
                  <a href="<?= h($instagram) ?>" target="_blank" title="Instagram" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" role="img">
                      <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm5 6.2A4.8 4.8 0 1 0 16.8 13 4.8 4.8 0 0 0 12 8.2zm6.4-2.6a1.1 1.1 0 1 0 1.1 1.1 1.1 1.1 0 0 0-1.1-1.1zM12 10.4A1.6 1.6 0 1 1 10.4 12 1.6 1.6 0 0 1 12 10.4z" />
                    </svg>
                  </a>
                <?php endif; ?>
                <?php if ($whatsapp): ?>
                  <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/','',$whatsapp)) ?>" target="_blank" title="WhatsApp" aria-label="WhatsApp">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" role="img">
                      <path d="M20.52 3.48A11.86 11.86 0 0 0 12 0C5.37 0 .01 5.37.01 12a11.7 11.7 0 0 0 1.6 6L0 24l6-1.6A11.89 11.89 0 0 0 12 24c6.63 0 12-5.37 12-12 0-1.97-.46-3.83-1.48-5.52zM12 21.5a9.47 9.47 0 0 1-4.85-1.36l-.35-.21-3.6.96.96-3.52-.22-.36A9.47 9.47 0 1 1 21.5 12 9.46 9.46 0 0 1 12 21.5zM17.2 14.1c-.3-.15-1.76-.86-2.04-.96-.27-.1-.47-.15-.67.15s-.77.96-.95 1.15c-.17.2-.34.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.48-1.74-1.65-2.04-.17-.3-.02-.46.13-.61.13-.13.3-.34.46-.51.16-.17.21-.3.33-.5.11-.2.05-.38-.02-.53-.07-.15-.67-1.6-.92-2.2-.24-.57-.49-.49-.67-.5-.17-.01-.38-.01-.58-.01s-.53.08-.81.38c-.27.3-1.04 1.02-1.04 2.48s1.06 2.88 1.21 3.08c.15.2 2.09 3.2 5.07 4.49 2.99 1.29 2.99.86 3.53.81.53-.05 1.72-.7 1.97-1.37.25-.67.25-1.24.18-1.37-.07-.13-.27-.2-.57-.35z" />
                    </svg>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="footer-divider"></div>

      <div class="footer-copyright">
        <p class="mb-1"><?= h($copyright_text) ?></p>
        <p class="small">Built with <i class="fa fa-heart" style="color: #17A2B8;"></i> for Batam</p>
      </div>
    </div>
  </footer>

  <!-- scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $BASE_URL ?>/assets/app.js?v=<?= filemtime(__DIR__ . '/assets/app.js') ?>"></script>
</body>
</html>
