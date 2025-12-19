<?php
// bookmarks_remove.php - Remove bookmark
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$listing_id = $_POST['listing_id'] ?? null;

if (!$listing_id) {
    echo json_encode(['success' => false, 'message' => 'Listing ID required']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND listing_id = ?");
$stmt->execute([$user_id, $listing_id]);

echo json_encode(['success' => true, 'message' => 'Bookmark removed']);
?>
