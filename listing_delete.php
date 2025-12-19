<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
// Allow deletion by admins or by the owner of the listing
// include config path for uploads
$config = include __DIR__ . '/config.php';
$uploadDir = $config['upload_dir'] ?? __DIR__ . '/uploads';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    // Check ownership or admin rights
    $stmtCheck = $pdo->prepare('SELECT owner_id, thumbnail FROM listings WHERE id = :id');
    $stmtCheck->execute(['id' => $id]);
    $rowCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if (!$rowCheck) { header('Location: index.php'); exit; }
    $canDelete = false;
    if (isAdmin()) $canDelete = true;
    else {
        $u = currentUser();
        if ($u && isset($u['id']) && (int)$u['id'] === (int)$rowCheck['owner_id']) $canDelete = true;
    }
    if (!$canDelete) { header('Location: index.php'); exit; }
    // delete local images associated
    $row = $rowCheck;
    // delete local images associated
    if ($row && !empty($row['thumbnail'])) {
        $oldParts = array_filter(array_map('trim', explode(',', $row['thumbnail'])));
        foreach ($oldParts as $op) {
            if (strpos($op, '/uploads/') !== false || (isset($config['upload_url']) && strpos($op, $config['upload_url']) !== false)) {
                $file = rtrim($uploadDir, '\\/') . DIRECTORY_SEPARATOR . basename($op);
                if (file_exists($file)) @unlink($file);
            }
        }
    }
    $stmt = $pdo->prepare('DELETE FROM listings WHERE id = :id');
    $stmt->execute(['id' => $id]);
    // Redirect owner back to their history, admin back to index
    if (!isAdmin()) {
        header('Location: user_listings.php');
    } else {
        header('Location: index.php');
    }
    exit;

}
