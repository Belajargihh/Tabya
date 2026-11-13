<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Silakan isi username dan password.';
    } else {
        $res = attempt_admin_login($username, $password);
        if ($res['success']) {
            header("Location: index.php");
            exit();
        } else {
            $error = $res['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk Admin - Tabya</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>
<body style="background: var(--color-canvas); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: var(--space-4);">

  <div style="width: 100%; max-width: 400px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card); padding: var(--space-6); display: flex; flex-direction: column; gap: var(--space-6);">
    
    <!-- Header -->
    <div style="display: flex; flex-direction: column; gap: var(--space-2);">
      <div style="display: flex; align-items: center; gap: var(--space-3);">
        <span class="brand-badge" aria-hidden="true">
          <iconify-icon icon="lucide:waves" style="font-size: 18px;"></iconify-icon>
        </span>
        <div class="brand-meta">
          <span class="brand-title">Tabya</span>
          <span class="brand-sub">Panel Administrator</span>
        </div>
      </div>
      <h1 class="text-heading-sm" style="margin-top: var(--space-2); margin-bottom: 0;">Autentikasi Pengelola</h1>
      <p class="text-body-sm" style="margin: 0; color: var(--color-text-muted);">
        Akses khusus pengelola data bahasa Kampung Yenbuba.
      </p>
    </div>

    <?php if ($error): ?>
      <div style="padding: var(--space-3); background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-compact); color: var(--color-danger); font-size: 13px; display: flex; align-items: center; gap: var(--space-2);">
        <iconify-icon icon="lucide:alert-circle" style="font-size: 16px;"></iconify-icon>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: var(--space-4);">
      <div style="display: flex; flex-direction: column; gap: var(--space-1);">
        <label for="username" class="text-label-md">Username</label>
        <input 
          type="text" 
          id="username" 
          name="username" 
          class="form-input-control" 
          value="admin" 
          required 
          autofocus>
      </div>

      <div style="display: flex; flex-direction: column; gap: var(--space-1);">
        <label for="password" class="text-label-md">Password</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="form-input-control" 
          value="admin123" 
          required>
      </div>

      <button type="submit" class="btn-primary" style="width: 100%; margin-top: var(--space-2); padding: var(--space-3);">
        Masuk ke Dashboard
        <iconify-icon icon="lucide:arrow-right" style="font-size: 16px;"></iconify-icon>
      </button>

      <!-- Credential Helper -->
      <div style="padding: var(--space-3); background: var(--color-surface-muted); border: 1px solid var(--color-border); border-radius: var(--radius-compact); font-size: 12px; color: var(--color-text-muted);">
        <span style="font-weight: 600; color: var(--color-text);">Akun Default Demo:</span><br>
        Username: <code class="font-mono">admin</code> &bull; Password: <code class="font-mono">admin123</code>
      </div>
    </form>

    <div style="padding-top: var(--space-4); border-top: 1px solid var(--color-border); text-align: center;">
      <a href="../index.php" class="text-body-sm" style="color: var(--color-text); text-decoration: underline; text-underline-offset: 3px;">
        &larr; Kembali ke Beranda Publik
      </a>
    </div>

  </div>

</body>
</html>
