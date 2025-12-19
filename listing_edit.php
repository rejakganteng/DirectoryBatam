<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require 'header.php';

// Require login
requireLogin();

// include config for upload handling
$config = include __DIR__ . '/config.php';
$uploadDir = $config['upload_dir'] ?? __DIR__ . '/uploads';
$uploadUrl = $config['upload_url'] ?? '/teskan/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$listing = null;
if ($id) $listing = fetchListing($pdo, $id);

if (!$listing) {
    echo '<div class="alert alert-warning">Listing not found.</div>';
    require 'footer.php';
    exit;
}

// Permission: only admin or the owner can edit
$current = currentUser();
if (!isAdmin()) {
  if (!$current || (int)$current['id'] !== (int)$listing['owner_id']) {
    echo '<div class="alert alert-danger">Anda tidak memiliki izin untuk mengedit listing ini.</div>';
    require 'footer.php';
    exit;
  }
}

$cats = fetchAllCategories($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $map_link = trim($_POST['map_link'] ?? '');
    $opening_hours = trim($_POST['opening_hours'] ?? '');
    // Prefer manual overrides, if any
    $latitude = null;
    $longitude = null;
    if (isset($_POST['latitude']) && $_POST['latitude'] !== '') $latitude = trim($_POST['latitude']);
    if (isset($_POST['longitude']) && $_POST['longitude'] !== '') $longitude = trim($_POST['longitude']);
    if (!empty($map_link) && ($latitude === null || $longitude === null)) {
      if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $map_link, $m)) {
        $latitude = $m[1]; $longitude = $m[2];
      } elseif (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $map_link, $m)) {
        $latitude = $m[1]; $longitude = $m[2];
      } elseif (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $map_link, $m)) {
        $latitude = $m[1]; $longitude = $m[2];
      }
      if ($latitude !== null) $latitude = str_replace(',', '.', $latitude);
      if ($longitude !== null) $longitude = str_replace(',', '.', $longitude);
    }
    // If manual override provided, make sure they are used
    if (isset($_POST['latitude']) && $_POST['latitude'] !== '') $latitude = str_replace(',', '.', trim($_POST['latitude']));
    if (isset($_POST['longitude']) && $_POST['longitude'] !== '') $longitude = str_replace(',', '.', trim($_POST['longitude']));
    if (!empty($map_link) && filter_var($map_link, FILTER_VALIDATE_URL) === false) {
      $errors[] = 'Invalid Google Maps link URL.';
    }
    $thumbnail = trim($_POST['thumbnail'] ?? '');
    // Only admins may set arbitrary status. Regular owners' edits will be set to 'pending' for review.
    $requested_status = in_array($_POST['status'] ?? '', ['draft','pending','published','banned']) ? $_POST['status'] : 'pending';
    if (isAdmin()) {
      $status = $requested_status;
    } else {
      $status = 'pending';
    }

    if ($title === '') $errors[] = 'Title is required.';
    if ($category_id <= 0) $errors[] = 'Category is required.';

    // handle image uploads (replace existing images if new ones provided)
    $uploadedPaths = [];
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
      for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        $err = $_FILES['images']['error'][$i];
        if ($err === UPLOAD_ERR_OK) {
          $tmp = $_FILES['images']['tmp_name'][$i];
          $name = $_FILES['images']['name'][$i];
          $size = $_FILES['images']['size'][$i];
          if ($size > 5 * 1024 * 1024) { $errors[] = 'File too large (max 5MB).'; break; }
          $info = @getimagesize($tmp);
          if (!$info) { $errors[] = 'Invalid image file.'; break; }
          $allowed = ['image/jpeg','image/png','image/webp'];
          if (!in_array($info['mime'], $allowed)) { $errors[] = 'Unsupported image format.'; break; }
          $ext = pathinfo($name, PATHINFO_EXTENSION);
          $safe = preg_replace('/[^a-z0-9\-_.]/i', '-', pathinfo($name, PATHINFO_FILENAME));
          $fname = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);
          $target = rtrim($uploadDir, '\\/') . DIRECTORY_SEPARATOR . $fname;
          if (move_uploaded_file($tmp, $target)) {
            $uploadedPaths[] = rtrim($uploadUrl, '/') . '/' . $fname;
          }
        }
      }
    }

    if (empty($errors)) {
      if (!empty($uploadedPaths)) {
        // delete old local files
        if (!empty($listing['thumbnail'])) {
          $oldParts = array_filter(array_map('trim', explode(',', $listing['thumbnail'])));
          foreach ($oldParts as $op) {
            // only local files stored under uploads
            if (strpos($op, $uploadUrl) !== false || strpos($op, '/uploads/') !== false) {
              $fileName = basename($op);
              $filePath = rtrim($uploadDir, '\\/') . DIRECTORY_SEPARATOR . $fileName;
              if (file_exists($filePath)) @unlink($filePath);
            }
          }
        }
        $thumbnail = implode(',', $uploadedPaths);
      }
      $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
      $stmt = $pdo->prepare('UPDATE listings SET category_id=:cat, title=:title, slug=:slug, description=:desc, address=:addr, phone=:phone, website=:website, latitude=:latitude, longitude=:longitude, map_link=:map_link, thumbnail=:thumbnail, opening_hours=:opening_hours, status=:status, updated_at = NOW() WHERE id=:id');
      try {
        $stmt->execute([
          'id' => $id,
          'cat' => $category_id,
          'title' => $title,
          'slug' => $slug,
          'desc' => $description,
          'addr' => $address,
          'phone' => $phone,
          'website' => $website,
          'latitude' => $latitude,
          'longitude' => $longitude,
          'map_link' => $map_link,
          'thumbnail' => $thumbnail,
          'opening_hours' => $opening_hours,
          'status' => $status,
        ]);
      } catch (PDOException $e) {
        if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
          $pdo->exec("ALTER TABLE listings ADD COLUMN opening_hours VARCHAR(500) DEFAULT NULL AFTER thumbnail");
          $stmt->execute([
            'id' => $id,
            'cat' => $category_id,
            'title' => $title,
            'slug' => $slug,
            'desc' => $description,
            'addr' => $address,
            'phone' => $phone,
            'website' => $website,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'map_link' => $map_link,
            'thumbnail' => $thumbnail,
            'opening_hours' => $opening_hours,
            'status' => $status,
          ]);
        } else {
          throw $e;
        }
      }
      // After editing, send owners back to their history; admins back to admin listings
      if (isAdmin()) {
        header('Location: index.php');
      } else {
        header('Location: user_listings.php');
      }
      exit;
    }
}
?>

<h1>Edit Listing</h1>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" value="<?= h($_POST['title'] ?? $listing['title']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-select">
      <option value="0">-- Select --</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ((isset($_POST['category_id']) ? (int)$_POST['category_id'] : (int)$listing['category_id']) === (int)$c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control"><?= h($_POST['description'] ?? $listing['description']) ?></textarea>
  </div>
  <div class="mb-3 row">
    <div class="col-md-6">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" value="<?= h($_POST['address'] ?? $listing['address']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= h($_POST['phone'] ?? $listing['phone']) ?>">
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Website</label>
    <input type="url" name="website" class="form-control" value="<?= h($_POST['website'] ?? $listing['website']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Google Maps Link (optional)</label>
    <input type="url" name="map_link" class="form-control" value="<?= h($_POST['map_link'] ?? $listing['map_link']) ?>" placeholder="https://maps.google.com/...">
  </div>
  <div class="mb-3">
    <label class="form-label">Jam Operasional (optional)</label>
    <textarea name="opening_hours" class="form-control" placeholder="Contoh: Senin-Jumat 09:00-17:00\nSabtu 09:00-13:00"><?= h($_POST['opening_hours'] ?? $listing['opening_hours'] ?? '') ?></textarea>
    <div class="form-text">Isi jam operasional atau catatan jam buka. Baris baru untuk hari berbeda.</div>
  </div>
  <div class="mb-3 row">
    <div class="col-md-6">
      <label class="form-label">Latitude (override, optional)</label>
      <input type="text" name="latitude" class="form-control" value="<?= h($_POST['latitude'] ?? $listing['latitude']) ?>" placeholder="-6.200000">
    </div>
    <div class="col-md-6">
      <label class="form-label">Longitude (override, optional)</label>
      <input type="text" name="longitude" class="form-control" value="<?= h($_POST['longitude'] ?? $listing['longitude']) ?>" placeholder="106.816666">
    </div>
  </div>
  <?php $existingThumbs = array_filter(array_map('trim', explode(',', $listing['thumbnail']))); ?>
  <?php if (!empty($existingThumbs)): ?>
  <div class="mb-3">
    <label class="form-label">Current images</label>
    <div class="file-preview" id="current-images">
      <?php foreach ($existingThumbs as $et): ?>
        <img src="<?= h($et) ?>" alt="thumb">
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <div class="mb-3">
    <label class="form-label file-input-label">Replace Photos (upload to replace current images)</label>
    <input type="file" id="images-edit" name="images[]" class="form-control" accept="image/*" multiple>
    <div class="file-preview" id="preview-edit"></div>
    <div class="form-text">If you upload new images, existing image files (if local) will be deleted and updated.<br>Max 5MB per image. Allowed: jpeg, png, webp.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Thumbnail URL (optional - used if no uploads made)</label>
    <input type="text" name="thumbnail" class="form-control" value="<?= h($_POST['thumbnail'] ?? $listing['thumbnail']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <?php $current_status = $_POST['status'] ?? $listing['status']; ?>
      <option value="draft" <?= $current_status === 'draft' ? 'selected' : '' ?>>Draft</option>
      <option value="pending" <?= $current_status === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="published" <?= $current_status === 'published' ? 'selected' : '' ?>>Published</option>
      <option value="banned" <?= $current_status === 'banned' ? 'selected' : '' ?>>Banned</option>
    </select>
  </div>
  <div class="mb-3">
    <button class="btn btn-primary">Save Listing</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?php require 'footer.php'; ?>
