<?php
// bookmarks.php - Handle bookmark operations
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

// Check if already bookmarked
$stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND listing_id = ?");
$stmt->execute([$user_id, $listing_id]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Already bookmarked']);
} else {
    $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, listing_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $listing_id]);
    echo json_encode(['success' => true, 'message' => 'Bookmarked']);
}
?>
