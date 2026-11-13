<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$admin = get_logged_admin();
$conn = get_db_connection();

// 1. Total Language Data
$qData = $conn->query("SELECT COUNT(*) AS total FROM datasets");
$totalLanguageData = $qData ? $qData->fetch_assoc()['total'] : 0;

// 2. Total Users / Visitors
$qUsers = $conn->query("SELECT COUNT(DISTINCT ip_address) AS total_users, COUNT(*) AS total_hits FROM visitors");
$userData = $qUsers ? $qUsers->fetch_assoc() : ['total_users' => 0, 'total_hits' => 0];
$totalUsers = max($userData['total_users'], 1);

// 3. Translation History Count
$qTrans = $conn->query("SELECT COUNT(*) AS total FROM translation_logs");
$totalTranslations = $qTrans ? $qTrans->fetch_assoc()['total'] : 0;

// Category distribution
$qCat = $conn->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category");
$catStats = [];
while ($row = $qCat->fetch_assoc()) {
    $catStats[$row['category']] = $row['count'];
}

// Recent translation logs
$qRecent = $conn->query("SELECT id, source_text, translated_text, translation_method, confidence_score, created_at FROM translation_logs ORDER BY id DESC LIMIT 6");
$recentLogs = $qRecent ? $qRecent->fetch_all(MYSQLI_ASSOC) : [];

// Check AI Model Status
$metaFile = __DIR__ . '/../python/training_meta.json';
$modelStatus = 'Aktif (Seq2Seq GRU)';
$modelLoss = '0.0004';
if (file_exists($metaFile)) {
    $meta = json_decode(file_get_contents($metaFile), true);
    if (!empty($meta['final_loss'])) {
        $modelLoss = $meta['final_loss'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Tabya</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>
<body>

  <div class="admin-shell-body">
    <!-- Sidebar (from yenbuba-shell prototype) -->
    <aside class="admin-sidebar">
      <a href="index.php" class="sidebar-brand">
        <span class="brand-icon-sq" aria-hidden="true">
          <iconify-icon icon="lucide:waves" style="font-size: 18px;"></iconify-icon>
        </span>
        <div style="display: flex; flex-direction: column;">
          <span style="font-size: 13px; font-weight: 600;">Tabya</span>
          <span style="font-size: 11px; color: var(--color-text-muted);">Panel Admin</span>
        </div>
      </a>

      <nav aria-label="Navigasi admin" class="sidebar-nav">
        <a href="index.php" class="sidebar-link" aria-current="page">
          <iconify-icon icon="lucide:layout-dashboard" style="font-size: 16px;"></iconify-icon>
          Dashboard
        </a>
        <a href="dataset.php" class="sidebar-link">
          <iconify-icon icon="lucide:languages" style="font-size: 16px;"></iconify-icon>
          Kelola Data Bahasa
        </a>
        <a href="history.php" class="sidebar-link">
          <iconify-icon icon="lucide:history" style="font-size: 16px;"></iconify-icon>
          Riwayat Terjemahan
        </a>
        <a href="model_eval.php" class="sidebar-link">
          <iconify-icon icon="lucide:cpu" style="font-size: 16px;"></iconify-icon>
          Model AI & BLEU Score
        </a>
        <a href="../index.php" target="_blank" class="sidebar-link">
          <iconify-icon icon="lucide:external-link" style="font-size: 16px;"></iconify-icon>
          Buka Halaman Publik
        </a>
      </nav>

      <div class="sidebar-footer">
        <span>Masuk sebagai <strong><?= htmlspecialchars($admin['username']) ?></strong></span>
        <a href="logout.php" style="color: var(--color-danger); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
          <iconify-icon icon="lucide:log-out" style="font-size: 14px;"></iconify-icon>
          Keluar
        </a>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div style="min-width: 0; display: flex; flex-direction: column;">
      <header class="admin-header">
        <div class="admin-title-area">
          <h1>Dashboard Operasional</h1>
        </div>
        <span style="font-size: 12px; color: var(--color-text-muted);">Signed in as admin</span>
      </header>

      <main class="admin-content">
        <!-- 3 Required Stats Cards + NMT State -->
        <div class="stat-ledger-grid">
          <div class="stat-ledger-card">
            <span class="stat-label">Total Language Data</span>
            <span class="stat-value"><?= $totalLanguageData ?></span>
            <span class="stat-meta">Kosakata / Kalimat terdaftar</span>
          </div>

          <div class="stat-ledger-card">
            <span class="stat-label">Total Users / Visitors</span>
            <span class="stat-value"><?= $totalUsers ?></span>
            <span class="stat-meta"><?= $userData['total_hits'] ?? 0 ?> Total sesi kunjungan</span>
          </div>

          <div class="stat-ledger-card">
            <span class="stat-label">Translation History</span>
            <span class="stat-value"><?= $totalTranslations ?></span>
            <span class="stat-meta">Permintaan terjemahan dicatat</span>
          </div>

          <div class="stat-ledger-card" style="border-left: 3px solid var(--color-primary);">
            <span class="stat-label">Model AI (NMT)</span>
            <span class="stat-value" style="font-size: 18px; line-height: 1.4;"><?= $modelStatus ?></span>
            <span class="stat-meta font-mono">Loss: <?= $modelLoss ?> (Seq2Seq GRU)</span>
          </div>
        </div>

        <!-- Category Distribution & Action Bar -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
          <div class="admin-panel" style="margin-bottom: 0;">
            <div class="panel-head">
              <h2>Distribusi Kategori Bahasa</h2>
              <a href="dataset.php" class="btn-secondary" style="font-size: 12px; padding: 4px 8px;">Kelola Data &rarr;</a>
            </div>
            <div style="padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 12px;">
              <div style="padding: 12px; background: var(--color-surface-muted); border-radius: var(--radius-compact); border: 1px solid var(--color-border);">
                <span class="stat-label">Salam</span>
                <div class="stat-value" style="font-size: 20px;"><?= $catStats['greeting'] ?? 0 ?></div>
              </div>
              <div style="padding: 12px; background: var(--color-surface-muted); border-radius: var(--radius-compact); border: 1px solid var(--color-border);">
                <span class="stat-label">Angka</span>
                <div class="stat-value" style="font-size: 20px;"><?= $catStats['number'] ?? 0 ?></div>
              </div>
              <div style="padding: 12px; background: var(--color-surface-muted); border-radius: var(--radius-compact); border: 1px solid var(--color-border);">
                <span class="stat-label">Kalimat</span>
                <div class="stat-value" style="font-size: 20px;"><?= $catStats['sentence'] ?? 0 ?></div>
              </div>
              <div style="padding: 12px; background: var(--color-surface-muted); border-radius: var(--radius-compact); border: 1px solid var(--color-border);">
                <span class="stat-label">Kata</span>
                <div class="stat-value" style="font-size: 20px;"><?= $catStats['word'] ?? 0 ?></div>
              </div>
            </div>
          </div>

          <div class="admin-panel" style="margin-bottom: 0; display: flex; flex-direction: column;">
            <div class="panel-head">
              <h2>Aksi Cepat</h2>
            </div>
            <div style="padding: 16px; display: flex; flex-direction: column; gap: 8px; flex: 1; justify-content: center;">
              <a href="dataset.php?action=add" class="btn-primary" style="justify-content: center;">
                <iconify-icon icon="lucide:plus" style="font-size: 14px;"></iconify-icon>
                Tambah Kosakata Baru
              </a>
              <a href="model_eval.php" class="btn-secondary" style="justify-content: center;">
                <iconify-icon icon="lucide:bar-chart-2" style="font-size: 14px;"></iconify-icon>
                Uji BLEU Score (Bab 4)
              </a>
              <a href="dataset.php?action=export&format=json" class="btn-secondary" style="justify-content: center;">
                <iconify-icon icon="lucide:download" style="font-size: 14px;"></iconify-icon>
                Export Dataset (JSON)
              </a>
            </div>
          </div>
        </div>

        <!-- Recent Translation Logs -->
        <div class="admin-panel">
          <div class="panel-head">
            <h2>Riwayat Terjemahan Terbaru (Translation History)</h2>
            <a href="history.php" class="btn-secondary" style="font-size: 12px; padding: 4px 8px;">Lihat Semua &rarr;</a>
          </div>
          <div style="overflow-x: auto;">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width: 50px;">ID</th>
                  <th>English (Source)</th>
                  <th>Bahasa Yenbuba (Target)</th>
                  <th>Metode Mesin</th>
                  <th style="text-align: right;">Akurasi</th>
                  <th style="text-align: right;">Waktu</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentLogs)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                      Belum ada riwayat aktivitas terjemahan.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentLogs as $log): ?>
                    <tr>
                      <td class="font-mono" style="color: var(--color-text-muted);">#<?= $log['id'] ?></td>
                      <td><strong><?= htmlspecialchars($log['source_text']) ?></strong></td>
                      <td style="font-weight: 600;"><?= htmlspecialchars($log['translated_text']) ?></td>
                      <td>
                        <span style="font-size: 11px; padding: 2px 6px; border-radius: var(--radius-compact); background: var(--color-surface-muted); border: 1px solid var(--color-border);">
                          <?= htmlspecialchars($log['translation_method']) ?>
                        </span>
                      </td>
                      <td class="font-mono" style="text-align: right;"><?= number_format($log['confidence_score'], 1) ?>%</td>
                      <td class="font-mono" style="font-size: 12px; color: var(--color-text-muted); text-align: right;">
                        <?= htmlspecialchars($log['created_at']) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>
  </div>

</body>
</html>
