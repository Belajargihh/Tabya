<?php
require_once __DIR__ . '/includes/db.php';
record_visitor('index.php');

$conn = get_db_connection();
$countQuery = $conn->query("SELECT COUNT(*) AS total FROM datasets");
$totalVocab = $countQuery ? $countQuery->fetch_assoc()['total'] : 45;

// Counts per category
$qCat = $conn->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category");
$catCounts = [];
while ($row = $qCat->fetch_assoc()) {
  $catCounts[$row['category']] = $row['count'];
}

// Popular phrases matching Tabel 3.2 & Gambar 3.8/3.11 Proposal
$popular = [
  ['id' => 'p1', 'english' => 'Eat', 'yenbuba' => 'Konan', 'category' => 'Kata'],
  ['id' => 'p2', 'english' => 'Good morning', 'yenbuba' => 'Arwo', 'category' => 'Salam'],
  ['id' => 'p3', 'english' => 'Thank you', 'yenbuba' => 'Injo', 'category' => 'Kalimat'],
  ['id' => 'p4', 'english' => 'Where do you live', 'yenbuba' => 'Kwain ro dio', 'category' => 'Kalimat'],
  ['id' => 'p5', 'english' => 'What is your name', 'yenbuba' => 'Hno hnon beja ido yo', 'category' => 'Kalimat'],
  ['id' => 'p6', 'english' => 'My name is Soma', 'yenbuba' => 'Aya ine ido Soma', 'category' => 'Kalimat'],
  ['id' => 'p7', 'english' => 'Fish', 'yenbuba' => 'Ien', 'category' => 'Kata'],
  ['id' => 'p8', 'english' => 'One', 'yenbuba' => 'Weter', 'category' => 'Angka'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tabya - Penerjemah Bahasa Daerah Kampung Yenbuba</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>

<body>

  <!-- Header Shell (Ramp Specification) -->
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
        <a href="index.php" class="nav-link" aria-current="page">Beranda</a>
        <a href="translate.php" class="nav-link">Terjemahkan</a>
        <a href="dictionary.php" class="nav-link">Kamus Kosakata</a>
        <a href="admin/login.php" class="btn-admin-cta">
          <iconify-icon icon="lucide:lock" style="font-size: 14px;"></iconify-icon>
          Masuk Admin
        </a>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container"
    style="display: flex; flex-direction: column; gap: var(--space-12); padding-top: var(--space-8); padding-bottom: var(--space-16);">

    <!-- Hero Section (from home.hero prototype) -->
    <section style="display: flex; flex-direction: column; gap: var(--space-6); max-width: 840px;">
      <div style="display: flex; alignItems: center; gap: var(--space-2);">
        <span aria-hidden="true"
          style="display: block; width: 28px; height: 4px; background: var(--color-primary); border-radius: var(--radius-compact);"></span>
        <span class="text-label-md"
          style="color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">English → Bahasa
          Yenbuba</span>
      </div>

      <h1 class="text-heading-lg" style="margin: 0; max-width: 760px; text-wrap: balance;">
        Bicara dengan warga Kampung Yenbuba, walau hanya dengan beberapa kata.
      </h1>

      <p class="text-body-lg" style="margin: 0; max-width: 62ch; color: var(--color-text-muted);">
        Ketik teks Bahasa Inggris, lalu lihat padanan Bahasa Biak Beteu yang dipakai sehari-hari di Kampung Yenbuba,
        Raja Ampat.
      </p>

      <div
        style="display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-4); margin-top: var(--space-2);">
        <a href="translate.php" class="btn-primary" style="padding: var(--space-3) var(--space-6);">
          Start Translation
          <iconify-icon icon="lucide:arrow-right" style="font-size: 16px;"></iconify-icon>
        </a>
        <a href="admin/login.php" class="text-body-sm"
          style="color: var(--color-text); text-decoration: underline; text-underline-offset: 3px;">
          Masuk sebagai admin kampung
        </a>
      </div>

      <!-- Quick Metrics Ledger (dl) -->
      <dl
        style="margin: 0; display: flex; flex-wrap: wrap; gap: var(--space-8); padding-top: var(--space-6); border-top: 1px solid var(--color-border); margin-top: var(--space-2);">
        <div style="display: flex; flex-direction: column; gap: var(--space-1);">
          <dt class="text-caption" style="color: var(--color-text-muted);">Data bahasa</dt>
          <dd class="text-heading-md font-mono" style="margin: 0;"><?= $totalVocab ?></dd>
        </div>
        <div style="display: flex; flex-direction: column; gap: var(--space-1);">
          <dt class="text-caption" style="color: var(--color-text-muted);">Kategori</dt>
          <dd class="text-heading-md font-mono" style="margin: 0;">4</dd>
        </div>
        <div style="display: flex; flex-direction: column; gap: var(--space-1);">
          <dt class="text-caption" style="color: var(--color-text-muted);">Arah terjemahan</dt>
          <dd class="text-body-md" style="margin: 0;">Satu arah, teks saja</dd>
        </div>
      </dl>
    </section>

    <!-- Categories Grid Section (from home.categories prototype) -->
    <section style="display: flex; flex-direction: column; gap: var(--space-4);">
      <div
        style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: var(--space-2);">
        <h2 class="text-heading-sm" style="margin: 0;">Kategori data yang tersedia</h2>
        <p class="text-body-sm" style="margin: 0; color: var(--color-text-muted);">Pilih kategori untuk mencoba
          contohnya</p>
      </div>

      <div class="category-grid">
        <a href="translate.php?q=Good+morning" class="home-chip">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);">
            <iconify-icon icon="lucide:hand-heart"
              style="font-size: 18px; color: var(--color-primary-text);"></iconify-icon>
            <span class="text-caption font-mono"
              style="color: var(--color-text-muted);"><?= $catCounts['greeting'] ?? 6 ?> entri</span>
          </div>
          <span class="text-heading-sm" style="font-size: var(--text-body-lg);">Salam</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">“Good morning”</span>
        </a>

        <a href="translate.php?q=Five" class="home-chip">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);">
            <iconify-icon icon="lucide:hash" style="font-size: 18px; color: var(--color-primary-text);"></iconify-icon>
            <span class="text-caption font-mono"
              style="color: var(--color-text-muted);"><?= $catCounts['number'] ?? 13 ?> entri</span>
          </div>
          <span class="text-heading-sm" style="font-size: var(--text-body-lg);">Angka</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">“Five”</span>
        </a>

        <a href="translate.php?q=Eat" class="home-chip">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);">
            <iconify-icon icon="lucide:book-open"
              style="font-size: 18px; color: var(--color-primary-text);"></iconify-icon>
            <span class="text-caption font-mono" style="color: var(--color-text-muted);"><?= $catCounts['word'] ?? 18 ?>
              entri</span>
          </div>
          <span class="text-heading-sm" style="font-size: var(--text-body-lg);">Kata</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">“Eat” (Konan)</span>
        </a>

        <a href="translate.php?q=Where+do+you+live" class="home-chip">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);">
            <iconify-icon icon="lucide:message-square"
              style="font-size: 18px; color: var(--color-primary-text);"></iconify-icon>
            <span class="text-caption font-mono"
              style="color: var(--color-text-muted);"><?= $catCounts['sentence'] ?? 9 ?> entri</span>
          </div>
          <span class="text-heading-sm" style="font-size: var(--text-body-lg);">Kalimat</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">“Where do you live?”</span>
        </a>
      </div>
    </section>

    <!-- Popular Searches Section (from home.popular prototype) -->
    <section style="display: flex; flex-direction: column; gap: var(--space-4);">
      <div
        style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: var(--space-2);">
        <h2 class="text-heading-sm" style="margin: 0;">Sering dicari wisatawan</h2>
        <span class="text-caption" style="color: var(--color-text-muted);">Klik tombol salin untuk mengambil padanan
          Yenbuba</span>
      </div>

      <div class="ledger-table-wrap">
        <div class="ledger-head">
          <span class="text-label-md">English</span>
          <span class="text-label-md">Bahasa Yenbuba</span>
          <span class="text-label-md col-category">Kategori</span>
          <span class="text-label-md" style="text-align: right;">Aksi</span>
        </div>

        <div>
          <?php foreach ($popular as $entry): ?>
            <div class="ledger-row">
              <span class="text-body-md"
                style="overflow-wrap: break-word;"><?= htmlspecialchars($entry['english']) ?></span>
              <span class="text-body-md"
                style="overflow-wrap: break-word; font-weight: var(--weight-medium);"><?= htmlspecialchars($entry['yenbuba']) ?></span>
              <span class="col-category">
                <span class="status-pill"><?= htmlspecialchars($entry['category']) ?></span>
              </span>
              <div style="text-align: right;">
                <button type="button" class="btn-secondary-quiet btn-copy"
                  data-clipboard-text="<?= htmlspecialchars($entry['yenbuba']) ?>"
                  aria-label="Salin <?= htmlspecialchars($entry['yenbuba']) ?>"
                  style="width: 32px; height: 32px; padding: 0;">
                  <iconify-icon icon="lucide:copy" style="font-size: 14px;"></iconify-icon>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- How it works (from home.how prototype) -->
    <section style="display: flex; flex-direction: column; gap: var(--space-4);">
      <h2 class="text-heading-sm" style="margin: 0;">Cara memakainya</h2>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-4);">
        <div
          style="display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-4); border-top: 2px solid var(--color-text); background: var(--color-surface); border-radius: 0 0 var(--radius-card) var(--radius-card); border-left: 1px solid var(--color-border); border-right: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
          <span class="text-caption font-mono" style="color: var(--color-text-muted);">01</span>
          <span class="text-body-md" style="font-weight: var(--weight-medium);">Tulis teks Inggris</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">Satu kata, angka, salam, atau kalimat
            pendek.</span>
        </div>

        <div
          style="display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-4); border-top: 2px solid var(--color-text); background: var(--color-surface); border-radius: 0 0 var(--radius-card) var(--radius-card); border-left: 1px solid var(--color-border); border-right: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
          <span class="text-caption font-mono" style="color: var(--color-text-muted);">02</span>
          <span class="text-body-md" style="font-weight: var(--weight-medium);">Tekan Translate</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">Sistem mencari padanan pada data bahasa
            kampung.</span>
        </div>

        <div
          style="display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-4); border-top: 2px solid var(--color-primary); background: var(--color-surface); border-radius: 0 0 var(--radius-card) var(--radius-card); border-left: 1px solid var(--color-border); border-right: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
          <span class="text-caption font-mono" style="color: var(--color-text-muted);">03</span>
          <span class="text-body-md" style="font-weight: var(--weight-medium);">Bandingkan hasil</span>
          <span class="text-body-sm" style="color: var(--color-text-muted);">Teks asli dan terjemahan tampil
            berdampingan.</span>
        </div>
      </div>

      <!-- Info Box -->
      <div
        style="display: flex; align-items: flex-start; gap: var(--space-3); padding: var(--space-4); background: var(--color-surface-muted); border: 1px solid var(--color-border); border-radius: var(--radius-card);">
        <iconify-icon icon="lucide:info"
          style="font-size: 16px; color: var(--color-text-muted); margin-top: 2px; flex-shrink: 0;"></iconify-icon>
        <p class="text-body-sm" style="margin: 0; color: var(--color-text-muted); max-width: 75ch;">
          Saat ini hanya tersedia penerjemahan teks satu arah, Inggris ke Bahasa Yenbuba.
        </p>
      </div>
    </section>

  </main>

  <!-- Footer Shell -->
  <footer class="shell-footer">
    <div class="container shell-footer__inner">
      <div style="display: flex; flex-direction: column; gap: var(--space-1);">
        <span class="text-label-md">Tabya</span>
        <span class="text-caption" style="color: var(--color-text-muted);">Kampung Yenbuba, Meos Mansar, Raja Ampat,
          Papua Barat Daya</span>
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