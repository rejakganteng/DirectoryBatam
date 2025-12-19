<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require 'header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') $errors[] = 'Full Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password2) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // unique email
        if (getUserByEmail($pdo, $email)) {
            $errors[] = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $id = createUser($pdo, $name, $email, $hash, 'user', $birth_date, $phone, $address);
            // auto login
            $user = getUserById($pdo, $id);
            loginUser($user);
            header('Location: index.php');
            exit;
        }
    }
}
?>

<div class="auth-container">
  <div class="auth-card register-card">
    <div class="auth-header">
      <h1>Join Us</h1>
      <p>Create your account</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
          <?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="Your name" required value="<?= h($_POST['name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?= h($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="birth_date" class="form-control" value="<?= h($_POST['birth_date'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-control" placeholder="+62..." value="<?= h($_POST['phone'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" placeholder="Your address" rows="3"><?= h($_POST['address'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password2" class="form-control" placeholder="Confirm your password" required>
      </div>

      <button type="submit" class="btn btn-auth-primary btn-block mb-3">Register</button>

      <div class="auth-footer">
        <p>Already have an account? <a href="login.php" class="auth-link">Login here</a></p>
        <p><a href="index.php" class="auth-link">Back to home</a></p>
      </div>
    </form>
  </div>
</div>

<?php require 'footer.php'; ?>