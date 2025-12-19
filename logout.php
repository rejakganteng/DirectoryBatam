<?php
require_once __DIR__ . '/auth.php';
logoutUser();
if (!isset($_GET['ajax'])) {
  header('Location: index.php');
}
exit;
