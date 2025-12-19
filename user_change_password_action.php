<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin();
$u = currentUser();
if (!$u) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: user_change_password.php'); exit; }

$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate
if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
  header('Location: user_change_password.php?error=' . urlencode('Semua field harus diisi.'));
  exit;
}

if (strlen($new_password) < 6) {
  header('Location: user_change_password.php?error=' . urlencode('Password baru minimal 6 karakter.'));
  exit;
}

if ($new_password !== $confirm_password) {
  header('Location: user_change_password.php?error=' . urlencode('Password baru dan konfirmasi tidak cocok.'));
  exit;
}

// Fetch user password hash
try {
  $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
  $stmt->execute([$u['id']]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    header('Location: user_change_password.php?error=' . urlencode('User tidak ditemukan.'));
    exit;
  }
  $hash = $row['password_hash'];
} catch (PDOException $e) {
  header('Location: user_change_password.php?error=' . urlencode('Error: ' . $e->getMessage()));
  exit;
}

// Verify old password
if (!password_verify($old_password, $hash)) {
  header('Location: user_change_password.php?error=' . urlencode('Password lama tidak sesuai.'));
  exit;
}

// Hash new password
$new_hash = password_hash($new_password, PASSWORD_BCRYPT);

// Update DB
try {
  $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
  $stmt->execute([
    ':hash' => $new_hash,
    ':id' => $u['id']
  ]);
  header('Location: user_change_password.php?updated=1');
  exit;
} catch (PDOException $e) {
  header('Location: user_change_password.php?error=' . urlencode('Error: ' . $e->getMessage()));
  exit;
}
