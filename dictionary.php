<?php
require_once __DIR__ . '/includes/db.php';
record_visitor('dictionary.php');

$conn = get_db_connection();

$search = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['cat'] ?? '');

$sql = "SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (english_text LIKE ? OR yenbuba_text LIKE ? OR indonesia_text LIKE ? OR notes LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if ($filterCategory !== '' && in_array($filterCategory, ['greeting', 'number', 'sentence', 'word', 'phrase'])) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= 's';
}

$sql .= " ORDER BY category ASC, english_text ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$countRes = $conn->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category");
$catCounts = [];
$totalRows = 0;
while ($row = $countRes->fetch_assoc()) {
    $catCounts[$row['category']] = $row['count'];
    $totalRows += $row['count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kamus Kosakata - Tabya</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>
<body>

  <!-- Header Shell -->
  <header class="shell-header">
    <div class="container shell-header__inner">
      <a href="index.php" class="brand-link">
        <span class="brand-badge" aria-hidden="true">
          <iconify-icon icon="lucide:waves" style="font-size: 18px;"></iconify-icon>
        </span>
        <div class="brand-meta">
          <span class="brand-title">Tabya</span>
          <span class="brand-sub">Penerjemah Bahasa Kampung Yenbuba</span>
        </div>
      </a>

      <nav aria-label="Navigasi utama" class="shell-nav">
        <a href="index.php" class="nav-link">Beranda</a>
        <a href="translate.php" class="nav-link">Terjemahkan</a>
        <a href="dictionary.php" class="nav-link" aria-current="page">Kamus Kosakata</a>
        <a href="admin/login.php" class="btn-admin-cta">
          <iconify-icon icon="lucide:lock" style="font-size: 14px;"></iconify-icon>
          Masuk Admin
        </a>
      </nav>
    </div>
  </header>

  <!-- Main Dictionary Container -->
  <main class="container" style="display: flex; flex-direction: column; gap: var(--space-8); padding-top: var(--space-8); padding-bottom: var(--space-16);">

    <!-- Header Section -->
    <div style="display: flex; flex-direction: column; gap: var(--space-2);">
      <div style="display: flex; align-items: center; gap: var(--space-2);">
        <span aria-hidden="true" style="display: block; width: 24px; height: 4px; background: var(--color-primary); border-radius: var(--radius-compact);"></span>
        <span class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">Perbendaharaan Kata</span>
      </div>
      <h1 class="text-heading-md" style="margin: 0;">Kamus Kosakata Bahasa Daerah Yenbuba</h1>
      <p class="text-body-sm" style="margin: 0; color: var(--color-text-muted);">
        Dataset kosakata dan kalimat Bahasa Biak sub-dialek Beteu yang dituturkan di Kampung Yenbuba.
      </p>
    </div>

    <!-- Search & Filter Bar -->
    <form action="dictionary.php" method="GET" class="dict-filter-form" style="display: flex; flex-wrap: wrap; gap: var(--space-3); padding: var(--space-4); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card); align-items: center;">
      <div style="flex: 1; min-width: 240px; position: relative;">
        <input 
          type="text" 
          name="q" 
          value="<?= htmlspecialchars($search) ?>" 
          placeholder="Cari kata Bahasa Inggris atau Yenbuba..." 
          class="form-input-control"
          style="padding-left: 36px;">
        <iconify-icon icon="lucide:search" style="position: absolute; left: 12px; top: 12px; font-size: 16px; color: var(--color-text-muted);"></iconify-icon>
      </div>

      <select name="cat" class="form-input-control" style="width: auto; min-width: 180px; cursor: pointer;">
        <option value="">Semua Kategori (<?= $totalRows ?>)</option>
        <option value="greeting" <?= $filterCategory === 'greeting' ? 'selected' : '' ?>>Salam / Greeting (<?= $catCounts['greeting'] ?? 0 ?>)</option>
        <option value="number" <?= $filterCategory === 'number' ? 'selected' : '' ?>>Angka / Number (<?= $catCounts['number'] ?? 0 ?>)</option>
        <option value="sentence" <?= $filterCategory === 'sentence' ? 'selected' : '' ?>>Kalimat / Sentence (<?= $catCounts['sentence'] ?? 0 ?>)</option>
        <option value="word" <?= $filterCategory === 'word' ? 'selected' : '' ?>>Kata / Word (<?= $catCounts['word'] ?? 0 ?>)</option>
        <option value="phrase" <?= $filterCategory === 'phrase' ? 'selected' : '' ?>>Frasa / Phrase (<?= $catCounts['phrase'] ?? 0 ?>)</option>
      </select>

      <button type="submit" class="btn-primary" style="padding: 10px 18px;">
        Filter
      </button>

      <?php if ($search !== '' || $filterCategory !== ''): ?>
        <a href="dictionary.php" class="text-body-sm" style="color: var(--color-text-muted); text-decoration: underline; padding: 6px;">
          Reset
        </a>
      <?php endif; ?>
    </form>

    <!-- Meta Info -->
    <div style="display: flex; justify-content: space-between; align-items: baseline;">
      <span class="text-body-sm" style="color: var(--color-text-muted);">
        Menampilkan <strong><?= count($items) ?></strong> entri kosakata
      </span>
      <span class="text-caption font-mono" style="color: var(--color-text-muted);">
        Total dataset: <?= $totalRows ?>
      </span>
    </div>

    <!-- Dictionary Data Container -->
    <?php if (empty($items)): ?>
      <div style="padding: var(--space-8); text-align: center; color: var(--color-text-muted); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card);">
        Kosakata tidak ditemukan. Silakan ganti kata kunci atau pilih kategori lain.
      </div>
    <?php else: ?>
      <!-- LEDGER TABLE (Protected with Horizontal Scroll on Narrow Screens) -->
      <div class="ledger-table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <div style="min-width: 680px;">
          <div style="display: grid; grid-template-columns: 90px 1.2fr 1.3fr 1.1fr 80px; gap: 16px; padding: 12px 16px; background: var(--color-surface-muted); border-bottom: 1px solid var(--color-border);">
            <span class="text-label-md font-mono">Kategori</span>
            <span class="text-label-md">Bahasa Inggris (Source)</span>
            <span class="text-label-md">Bahasa Yenbuba (Target)</span>
            <span class="text-label-md">Pelafalan</span>
            <span class="text-label-md" style="text-align: right;">Coba</span>
          </div>

          <div>
            <?php foreach ($items as $item): ?>
              <div style="display: grid; grid-template-columns: 90px 1.2fr 1.3fr 1.1fr 80px; gap: 16px; align-items: center; padding: 12px 16px; border-bottom: 1px solid var(--color-border); background: var(--color-surface);">
                <div>
                  <span class="status-pill"><?= htmlspecialchars($item['category']) ?></span>
                </div>
                <div>
                  <span class="text-body-md" style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($item['english_text']) ?></span>
                </div>
                <div>
                  <div class="text-body-md" style="font-weight: 700; color: var(--color-text);"><?= htmlspecialchars($item['yenbuba_text']) ?></div>
                  <?php if (!empty($item['indonesia_text'])): ?>
                    <div class="text-caption" style="color: var(--color-text-muted); font-size: 11px; margin-top: 2px;">
                      Arti: <?= htmlspecialchars($item['indonesia_text']) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div>
                  <span class="text-caption font-mono" style="color: var(--color-text-muted);">
                    <?= htmlspecialchars($item['pronunciation'] ?: '-') ?>
                  </span>
                </div>
                <div style="text-align: right;">
                  <a href="translate.php?q=<?= urlencode($item['english_text']) ?>" class="btn-secondary-quiet" style="font-size: 12px; padding: 5px 10px;">
                    Coba &rarr;
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </main>

  <!-- Footer Shell -->
  <footer class="shell-footer">
    <div class="container shell-footer__inner">
      <div style="display: flex; flex-direction: column; gap: var(--space-1);">
        <span class="text-label-md">Tabya</span>
        <span class="text-caption" style="color: var(--color-text-muted);">Kampung Yenbuba, Raja Ampat, Papua Barat Daya</span>
      </div>

      <div style="display: flex; align-items: center; gap: var(--space-6); flex-wrap: wrap;">
        <a href="index.php" class="text-body-sm" style="color: var(--color-text-muted);">Beranda</a>
        <a href="translate.php" class="text-body-sm" style="color: var(--color-text-muted);">Terjemahkan</a>
        <a href="dictionary.php" class="text-body-sm" style="color: var(--color-text-muted);">Kamus Kosakata</a>
        <a href="admin/login.php" class="text-body-sm" style="color: var(--color-text-muted);">Masuk Admin</a>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
