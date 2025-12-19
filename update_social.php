<?php
// update_social.php - Update social media settings
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$facebook = $_POST['facebook'] ?? '';
$instagram = $_POST['instagram'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';

$stmt = $pdo->prepare("UPDATE footer SET facebook = ?, instagram = ?, whatsapp = ? WHERE id = 1");
$stmt->execute([$facebook, $instagram, $whatsapp]);

header('Location: dashboard_admin.php?tab=settings');
?>
