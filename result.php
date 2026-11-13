<?php
require_once __DIR__ . '/includes/translator.php';
record_visitor('result.php');

$sourceText = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceText = $_POST['english_text'] ?? '';
} elseif (isset($_GET['text'])) {
    $sourceText = $_GET['text'];
}

$sourceText = trim($sourceText);
if ($sourceText === '') {
    header("Location: translate.php");
    exit();
}

$result = translate_english_to_yenbuba($sourceText);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hasil Terjemahan - Tabya</title>
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
        <a href="dictionary.php" class="nav-link">Kamus Kosakata</a>
        <a href="admin/login.php" class="btn-admin-cta">
          <iconify-icon icon="lucide:lock" style="font-size: 14px;"></iconify-icon>
          Masuk Admin
        </a>
      </nav>
    </div>
  </header>

  <!-- Main Result Container -->
  <main class="container" style="display: flex; flex-direction: column; gap: var(--space-8); padding-top: var(--space-8); padding-bottom: var(--space-16); max-width: 920px;">

    <!-- Breadcrumb header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--space-3);">
      <div style="display: flex; flex-direction: column; gap: var(--space-2);">
        <div style="display: flex; align-items: center; gap: var(--space-2);">
          <span aria-hidden="true" style="display: block; width: 24px; height: 4px; background: var(--color-primary); border-radius: var(--radius-compact);"></span>
          <span class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">Hasil Penerjemahan</span>
        </div>
        <h1 class="text-heading-md" style="margin: 0;">Perbandingan Teks Asli & Terjemahan</h1>
      </div>

      <div style="display: flex; gap: var(--space-2); align-items: center;">
        <span class="status-pill primary font-mono">
          <?= htmlspecialchars($result['method']) ?>
        </span>
        <span class="status-pill positive font-mono">
          <?= number_format($result['confidence'], 1) ?>% Match
        </span>
      </div>
    </div>

    <!-- Comparative Ledger Card (Ramp Split View) -->
    <div class="result-split-grid">
      <!-- Left: Source Text -->
      <div class="result-pane-source">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-4);">
          <span class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">English (Source)</span>
          <span class="text-caption font-mono" style="color: var(--color-text-muted);">
            <?= strlen($result['source_text']) ?> chars
          </span>
        </div>

        <div class="text-heading-sm" style="font-size: 20px; line-height: 1.4; color: var(--color-text);">
          "<?= htmlspecialchars($result['source_text']) ?>"
        </div>
      </div>

      <!-- Right: Target Text (with 4px Lime Decision Rail) -->
      <div class="result-pane-target">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-4);">
          <span class="text-label-md" style="color: var(--color-primary-text); text-transform: uppercase;">Bahasa Yenbuba (Biak-Beteu)</span>
          <span class="status-pill font-mono"><?= !empty($result['is_out_of_scope']) ? 'Out of Scope' : 'Target' ?></span>
        </div>

        <?php if (!empty($result['is_out_of_scope'])): ?>
          <div style="padding: var(--space-4); background: #FFFBEB; border: 1px solid #FDE68A; border-radius: var(--radius-card); margin-bottom: var(--space-3);">
            <div style="display: flex; align-items: center; gap: var(--space-2); color: #B45309; font-weight: 600; margin-bottom: var(--space-2); font-size: 15px;">
              <iconify-icon icon="lucide:alert-triangle" style="font-size: 20px;"></iconify-icon>
              Teks di Luar Batasan Masalah Skripsi
            </div>
            <p style="margin: 0 0 var(--space-3) 0; font-size: 13px; color: #78350F; line-height: 1.5;">
              Sesuai <strong>Bab 1.4 (Batasan Masalah)</strong> & <strong>Bab 3.2.4 Poin a</strong> pada Proposal Skripsi, sistem ini khusus dibangun untuk kosakata dan kalimat komunikasi wisatawan di Kampung Yenbuba (Raja Ampat). Teks naratif fiksi atau umum berbahasa Inggris di luar korpus lokal tidak dapat diterjemahkan.
            </p>
            <div style="font-size: 12px; color: #92400E; font-weight: 600; margin-bottom: 6px;">Contoh kalimat yang didukung dalam dataset skripsi:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <a href="result.php?text=Where+do+you+live" class="status-pill" style="text-decoration: none; color: #78350F; background: #FEF3C7; border: 1px solid #FDE68A;">Where do you live?</a>
              <a href="result.php?text=What+is+your+name" class="status-pill" style="text-decoration: none; color: #78350F; background: #FEF3C7; border: 1px solid #FDE68A;">What is your name?</a>
              <a href="result.php?text=Good+morning%2C+my+name+is+Soma" class="status-pill" style="text-decoration: none; color: #78350F; background: #FEF3C7; border: 1px solid #FDE68A;">Good morning, my name is Soma</a>
              <a href="result.php?text=Eat+fish+and+rice" class="status-pill" style="text-decoration: none; color: #78350F; background: #FEF3C7; border: 1px solid #FDE68A;">Eat fish and rice</a>
            </div>
          </div>
        <?php else: ?>
          <div class="text-heading-md" style="font-size: 26px; line-height: 1.3; font-weight: var(--weight-semibold); color: var(--color-text); margin-bottom: var(--space-2);">
            <?= htmlspecialchars($result['translated_text']) ?>
          </div>

          <?php if (!empty($result['indonesia_text']) && $result['indonesia_text'] !== '-'): ?>
            <div style="margin-bottom: var(--space-2); color: var(--color-text-muted); font-size: 13px;">
              Arti Bahasa Indonesia: <strong style="color: var(--color-text);"><?= htmlspecialchars($result['indonesia_text']) ?></strong>
            </div>
          <?php endif; ?>

          <?php if (!empty($result['pronunciation']) && $result['pronunciation'] !== '-'): ?>
            <div style="margin-bottom: var(--space-3); display: flex; align-items: center; gap: var(--space-2);">
              <iconify-icon icon="lucide:volume-2" style="font-size: 16px; color: var(--color-text-muted);"></iconify-icon>
              <span class="text-body-sm" style="font-style: italic; color: var(--color-text-muted);">
                Pelafalan: <strong><?= htmlspecialchars($result['pronunciation']) ?></strong>
              </span>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($result['breakdown']) && count($result['breakdown']) > 1): ?>
      <!-- Rincian Kata/Klausa untuk Kalimat Majemuk -->
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card); overflow: hidden;">
        <div style="padding: var(--space-3) var(--space-4); background: var(--color-surface-muted); border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between;">
          <span class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">Rincian Struktur Kata & Frasa Kalimat</span>
          <span class="text-caption font-mono" style="color: var(--color-text-muted);"><?= count($result['breakdown']) ?> Segmen</span>
        </div>
        <div class="table-responsive">
          <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
              <tr style="border-bottom: 1px solid var(--color-border); text-align: left; background: var(--color-surface);">
                <th style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Kata / Frasa Inggris</th>
                <th style="padding: 10px 16px; font-weight: 600; color: var(--color-text);">Bahasa Yenbuba</th>
                <th style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Arti Indonesia</th>
                <th style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Pelafalan</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($result['breakdown'] as $b): ?>
                <tr style="border-bottom: 1px solid var(--color-border);">
                  <td style="padding: 8px 16px;"><?= htmlspecialchars($b['source']) ?></td>
                  <td style="padding: 8px 16px; font-weight: 600; color: var(--color-primary-text);"><?= htmlspecialchars($b['translated']) ?></td>
                  <td style="padding: 8px 16px; color: var(--color-text-muted);"><?= htmlspecialchars($b['indonesia'] ?? '-') ?></td>
                  <td style="padding: 8px 16px; font-style: italic; color: var(--color-text-muted);"><?= htmlspecialchars($b['pronunciation'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- Action Bar (Buttons) -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
      <a href="translate.php" class="btn-secondary">
        <iconify-icon icon="lucide:arrow-left" style="font-size: 14px;"></iconify-icon>
        Back to Translation
      </a>

      <div style="display: flex; gap: var(--space-3); align-items: center;">
        <button 
          type="button" 
          class="btn-secondary-quiet btn-copy" 
          data-clipboard-text="<?= htmlspecialchars($result['translated_text']) ?>">
          <iconify-icon icon="lucide:copy" style="font-size: 14px;"></iconify-icon>
          Salin Hasil
        </button>

        <a href="translate.php" class="btn-primary">
          Terjemahkan Kalimat Lain
          <iconify-icon icon="lucide:arrow-right" style="font-size: 14px;"></iconify-icon>
        </a>
      </div>
    </div>

    <!-- Technical Note (Bab 3.2.4 c clause) -->
    <div style="display: flex; align-items: flex-start; gap: var(--space-3); padding: var(--space-4); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card);">
      <iconify-icon icon="lucide:file-text" style="font-size: 16px; color: var(--color-text-muted); margin-top: 2px; flex-shrink: 0;"></iconify-icon>
      <p class="text-body-sm" style="margin: 0; color: var(--color-text-muted); line-height: 1.5;">
        <strong>Klausul Bab 3.2.4 (c) Proposal:</strong> 
        Proses pencocokan data dilakukan secara presisi terhadap dataset kosakata Kampung Yenbuba yang terdaftar di database MySQL. Kalimat baru atau variasi kalimat dialirkan ke model PyTorch Seq2Seq (Encoder-Decoder GRU).
      </p>
    </div>

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
