<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$admin = get_logged_admin();
$conn = get_db_connection();

$message = '';
$messageType = '';

// Handle Delete Log
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM translation_logs WHERE id = $id");
        $message = "Catatan riwayat #$id berhasil dihapus.";
        $messageType = 'success';
    }
}

// Handle Clear All Logs
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    $conn->query("TRUNCATE TABLE translation_logs");
    $message = "Seluruh riwayat terjemahan telah dibersihkan.";
    $messageType = 'success';
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT id, source_text, translated_text, translation_method, confidence_score, ip_address, created_at FROM translation_logs WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (source_text LIKE ? OR translated_text LIKE ? OR translation_method LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
    $types = 'sss';
}

$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Terjemahan - Tabya</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>
<body>

  <div class="admin-shell-body">
    <!-- Sidebar -->
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
        <a href="index.php" class="sidebar-link">
          <iconify-icon icon="lucide:layout-dashboard" style="font-size: 16px;"></iconify-icon>
          Dashboard
        </a>
        <a href="dataset.php" class="sidebar-link">
          <iconify-icon icon="lucide:languages" style="font-size: 16px;"></iconify-icon>
          Kelola Data Bahasa
        </a>
        <a href="history.php" class="sidebar-link" aria-current="page">
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

    <!-- Main Content -->
    <div style="min-width: 0; display: flex; flex-direction: column;">
      <header class="admin-header">
        <div class="admin-title-area">
          <h1>Riwayat Terjemahan (Translation History)</h1>
        </div>
        <div style="display: flex; gap: 8px;">
          <a href="history.php?action=clear_all" class="btn-danger" onclick="return confirm('Kosongkan semua riwayat terjemahan?')">
            <iconify-icon icon="lucide:trash-2" style="font-size: 14px;"></iconify-icon>
            Kosongkan Riwayat
          </a>
        </div>
      </header>

      <main class="admin-content">
        <?php if ($message): ?>
          <div class="alert-box <?= $messageType ?>">
            <iconify-icon icon="lucide:check-circle" style="font-size: 16px;"></iconify-icon>
            <span><?= htmlspecialchars($message) ?></span>
          </div>
        <?php endif; ?>

        <!-- Search Panel -->
        <div class="admin-panel" style="margin-bottom: 16px;">
          <form action="history.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; align-items: center;">
            <input 
              type="text" 
              name="q" 
              value="<?= htmlspecialchars($search) ?>" 
              placeholder="Cari kata kunci query atau metode..." 
              class="form-control" 
              style="max-width: 320px;">

            <button type="submit" class="btn-secondary">
              Cari Log
            </button>

            <?php if ($search): ?>
              <a href="history.php" class="btn-secondary" style="color: var(--color-text-muted);">Reset</a>
            <?php endif; ?>

            <div style="margin-left: auto; font-size: 13px; color: var(--color-text-muted);">
              Menampilkan <span class="font-mono" style="font-weight: 600; color: var(--color-text);"><?= count($logs) ?></span> entri terbaru
            </div>
          </form>
        </div>

        <!-- Logs Table -->
        <div class="admin-panel">
          <div style="overflow-x: auto;">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width: 50px;">ID</th>
                  <th>English (Source)</th>
                  <th>Bahasa Yenbuba (Target)</th>
                  <th>Metode Mesin</th>
                  <th style="text-align: right;">Akurasi</th>
                  <th>IP Address</th>
                  <th style="text-align: right;">Waktu</th>
                  <th style="width: 50px; text-align: right;">Hapus</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($logs)): ?>
                  <tr>
                    <td colspan="8" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                      Belum ada log terjemahan yang dicatat.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($logs as $row): ?>
                    <tr>
                      <td class="font-mono" style="color: var(--color-text-muted);">#<?= $row['id'] ?></td>
                      <td><strong><?= htmlspecialchars($row['source_text']) ?></strong></td>
                      <td style="font-weight: 600;"><?= htmlspecialchars($row['translated_text']) ?></td>
                      <td>
                        <span style="font-size: 11px; padding: 2px 6px; border-radius: var(--radius-compact); background: var(--color-surface-muted); border: 1px solid var(--color-border);">
                          <?= htmlspecialchars($row['translation_method']) ?>
                        </span>
                      </td>
                      <td class="font-mono" style="text-align: right;"><?= number_format($row['confidence_score'], 1) ?>%</td>
                      <td class="font-mono" style="font-size: 12px; color: var(--color-text-muted);"><?= htmlspecialchars($row['ip_address'] ?? '127.0.0.1') ?></td>
                      <td class="font-mono" style="font-size: 12px; color: var(--color-text-muted); text-align: right;"><?= htmlspecialchars($row['created_at']) ?></td>
                      <td style="text-align: right;">
                        <a 
                          href="history.php?action=delete&id=<?= $row['id'] ?>" 
                          class="btn-danger" 
                          style="padding: 2px 6px; font-size: 12px;"
                          onclick="return confirm('Hapus log #<?= $row['id'] ?>?')">
                          &times;
                        </a>
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
