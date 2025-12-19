<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin();
$u = currentUser();
if (!$u) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: user_profile_edit.php'); exit; }

$name = $_POST['name'] ?? '';
$bio = $_POST['bio'] ?? '';
$birth_date = $_POST['birth_date'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$remove_flag = !empty($_POST['remove_avatar']);

// fetch existing avatar
try {
  $existing = $pdo->prepare('SELECT avatar FROM users WHERE id = ? LIMIT 1');
  $existing->execute([$u['id']]);
  $existingAvatar = $existing->fetchColumn();
} catch (PDOException $e) {
  $existingAvatar = null;
}

$avatar_file = $existingAvatar ?: null;

if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
  $f = $_FILES['avatar'];
  $tmp = $f['tmp_name'];
  $orig = $f['name'];
  $allowed = [ 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg' ];
  $mime = mime_content_type($tmp);
  if (!isset($allowed[$mime])) {
    header('Location: user_profile_edit.php?error=' . urlencode('Tipe file tidak didukung. Gunakan PNG/JPG/WebP/SVG.'));
    exit;
  }
  $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($orig));
  $fname = uniqid('avatar_', true) . '_' . $safe;
  $target = __DIR__ . '/assets/uploads/avatars/' . $fname;
  if (!move_uploaded_file($tmp, $target)) {
    header('Location: user_profile_edit.php?error=' . urlencode('Gagal menyimpan file.'));
    exit;
  }
  // delete previous avatar
  if (!empty($existingAvatar)) {
    $prev = __DIR__ . '/assets/uploads/avatars/' . basename($existingAvatar);
    if (is_file($prev)) {@unlink($prev);} // best-effort
  }
  $avatar_file = $fname;
} else {
  if ($remove_flag) {
    if (!empty($existingAvatar)) {
      $prev = __DIR__ . '/assets/uploads/avatars/' . basename($existingAvatar);
      if (is_file($prev)) {@unlink($prev);} // best-effort
    }
    $avatar_file = null;
  }
}

// update DB
try {
  $stmt = $pdo->prepare('UPDATE users SET name = :name, bio = :bio, birth_date = :birth_date, phone = :phone, address = :address, avatar = :avatar WHERE id = :id');
  $stmt->execute([
    ':name' => $name,
    ':bio' => $bio,
    ':birth_date' => !empty($birth_date) ? $birth_date : null,
    ':phone' => !empty($phone) ? $phone : null,
    ':address' => !empty($address) ? $address : null,
    ':avatar' => $avatar_file,
    ':id' => $u['id']
  ]);
  // update session so navbar reflects changes immediately
  $_SESSION['user'] = [
    'id' => $u['id'],
    'name' => $name,
    'email' => $u['email'],
    'role' => $u['role'],
    'avatar' => $avatar_file
  ];
  header('Location: user_profile.php?id=' . $u['id'] . '&updated=1');
  exit;
} catch (PDOException $e) {
  header('Location: user_profile_edit.php?error=' . urlencode($e->getMessage()));
  exit;
}
