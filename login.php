<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require 'header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($email === '' || $password === '') $errors[] = 'Email and password are required.';
  if (empty($errors)) {
    $user = getUserByEmail($pdo, $email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
      $errors[] = 'Invalid credentials.';
    } else {
      loginUser($user);
      // redirect based on role
      if ($user['role'] === 'admin') header('Location: admin/dashboard_admin.php');
      else header('Location: index.php');
      exit;
    }
  }
}
?>
<div class="auth-container">
  <div class="auth-card login-card">
    <div class="auth-header">
      <h1>Welcome Back</h1>
      <p>Login to your account</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
          <?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
      </div>

      <button type="submit" class="btn btn-auth-primary btn-block mb-3">Login</button>
      
      <div class="auth-footer">
        <p>Don't have an account? <a href="register.php" class="auth-link">Register here</a></p>
        <p><a href="index.php" class="auth-link">Back to home</a></p>
      </div>
    </form>
  </div>
</div>

<?php require 'footer.php'; ?>
