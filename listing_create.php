<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require 'header.php';
// Allow only logged-in users to access listing creation. Non-logged users go to login.
if (!isLoggedIn()) {
  header('Location: login.php'); exit;
}
// include config for upload path
$config = include __DIR__ . '/config.php';
$uploadDir = $config['upload_dir'] ?? __DIR__ . '/uploads';
$uploadUrl = $config['upload_url'] ?? '/teskan/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

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
    if (!empty($map_link) && filter_var($map_link, FILTER_VALIDATE_URL) === false) {
      $errors[] = 'Invalid Google Maps link URL.';
    }
    $thumbnail = trim($_POST['thumbnail'] ?? '');
    // Attempt to parse lat/long from map link (if the user pasted a Google Maps link). Also prefer manual overrides.
    $latitude = null;
    $longitude = null;
    if (isset($_POST['latitude']) && $_POST['latitude'] !== '') {
      $latitude = trim($_POST['latitude']);
    }
    if (isset($_POST['longitude']) && $_POST['longitude'] !== '') {
      $longitude = trim($_POST['longitude']);
    }
    if (!empty($map_link) && ($latitude === null || $longitude === null)) {
      if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $map_link, $m)) {
        $latitude = $m[1]; $longitude = $m[2];
      } elseif (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $map_link, $m)) {
        $latitude = $m[1]; $longitude = $m[2];
      } elseif (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $map_link, $m)) {
        $latitude = $m[1]; $longitude = $m[2];
      }
      // normalize decimal values (in case of comma separators '1,234' vs '1.234')
      if ($latitude !== null) $latitude = str_replace(',', '.', $latitude);
      if ($longitude !== null) $longitude = str_replace(',', '.', $longitude);
    }
    // If manual override was provided, make sure they are used and trimmed
    if (isset($_POST['latitude']) && $_POST['latitude'] !== '') $latitude = str_replace(',', '.', trim($_POST['latitude']));
    if (isset($_POST['longitude']) && $_POST['longitude'] !== '') $longitude = str_replace(',', '.', trim($_POST['longitude']));
    // force non-admin submissions to 'pending' so admin must review
    if (isAdmin()) {
      $status = in_array($_POST['status'] ?? '', ['draft','pending','published','banned']) ? $_POST['status'] : 'pending';
    } else {
      $status = 'pending';
    }

    if ($title === '') $errors[] = 'Title is required.';
    if ($category_id <= 0) $errors[] = 'Category is required.';

    // handle file uploads
    $uploadedPaths = [];
    if (!empty($_FILES['images'])) {
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

    if (!empty($errors)) {
      // fallthrough to display errors
    }

    if (empty($errors)) {
      // if files were uploaded, prefer them over manual thumbnail field
      if (!empty($uploadedPaths)) {
        $thumbnail = implode(',', $uploadedPaths);
      }
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        $stmt = $pdo->prepare('INSERT INTO listings (category_id, owner_id, title, slug, description, address, phone, website, latitude, longitude, map_link, thumbnail, opening_hours, status) VALUES (:cat, :owner_id, :title,:slug,:desc,:addr,:phone,:website,:latitude,:longitude,:map_link,:thumbnail,:opening_hours,:status)');
        try {
          $stmt->execute([
            'cat' => $category_id,
            'owner_id' => (currentUser()['id'] ?? null),
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
              'cat' => $category_id,
              'owner_id' => (currentUser()['id'] ?? null),
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
        header('Location: index.php');
        exit;
    }
}
?>

<h1>Add Listing</h1>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" value="<?= h($_POST['title'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-select">
      <option value="0">-- Select --</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control"><?= h($_POST['description'] ?? '') ?></textarea>
  </div>
  <div class="mb-3 row">
    <div class="col-md-6">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" value="<?= h($_POST['address'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= h($_POST['phone'] ?? '') ?>">
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Website</label>
    <input type="url" name="website" class="form-control" value="<?= h($_POST['website'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Google Maps Link (optional)</label>
    <input type="url" name="map_link" class="form-control" value="<?= h($_POST['map_link'] ?? '') ?>" placeholder="https://maps.google.com/...">
  </div>
  <div class="mb-3">
    <label class="form-label">Jam Operasional (optional)</label>
    <textarea name="opening_hours" class="form-control" placeholder="Contoh: Senin-Jumat 09:00-17:00\nSabtu 09:00-13:00"><?= h($_POST['opening_hours'] ?? '') ?></textarea>
    <div class="form-text">Isi jam operasional atau catatan jam buka. Baris baru untuk hari berbeda.</div>
  </div>
  <div class="mb-3 row">
    <?php if (isAdmin()): ?>
    <div class="col-md-6">
      <label class="form-label">Latitude (optional override)</label>
      <input type="text" name="latitude" class="form-control" value="<?= h($_POST['latitude'] ?? '') ?>" placeholder="-6.200000">
    </div>
    <div class="col-md-6">
      <label class="form-label">Longitude (optional override)</label>
      <input type="text" name="longitude" class="form-control" value="<?= h($_POST['longitude'] ?? '') ?>" placeholder="106.816666">
    </div>
    <?php endif; ?>
  </div>
  <div class="mb-3">
    <label class="form-label file-input-label">Upload Photos (you can select multiple images)</label>
    <input type="file" id="images-create" name="images[]" class="form-control" accept="image/*" multiple>
    <div class="file-preview" id="preview-create"></div>
    <div class="form-text">Max 5MB per image. Allowed: jpeg, png, webp. If you also enter a thumbnail URL below it will be used as fallback.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Thumbnail URL (optional)</label>
    <input type="text" name="thumbnail" class="form-control" value="<?= h($_POST['thumbnail'] ?? '') ?>">
  </div>
  <?php if (isAdmin()): ?>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="draft">Draft</option>
      <option value="pending" <?= (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : 'selected' ?>>Pending</option>
      <option value="published">Published</option>
      <option value="banned">Banned</option>
    </select>
  </div>
  <?php endif; ?>
  <div class="mb-3">
    <button class="btn btn-primary">Add Listing</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?php require 'footer.php'; ?>