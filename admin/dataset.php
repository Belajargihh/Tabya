<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$admin = get_logged_admin();
$conn = get_db_connection();

$message = '';
$messageType = '';

// Helper function to sync JSON
function sync_dataset_json($conn) {
    $res = $conn->query("SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets ORDER BY id ASC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    $jsonPath = __DIR__ . '/../python/dataset.json';
    file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Handle Export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $format = $_GET['format'] ?? 'json';
    $res = $conn->query("SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets ORDER BY id ASC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tabya_yenbuba_dataset.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Category', 'English Text', 'Yenbuba Text', 'Indonesia Text', 'Pronunciation', 'Notes']);
        foreach ($data as $d) {
            fputcsv($output, [$d['id'], $d['category'], $d['english_text'], $d['yenbuba_text'], $d['indonesia_text'] ?? '', $d['pronunciation'], $d['notes']]);
        }
        fclose($output);
        exit();
    } else {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=tabya_yenbuba_dataset.json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// 1. Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $category = trim($_POST['category'] ?? 'word');
    $english = trim($_POST['english_text'] ?? '');
    $yenbuba = trim($_POST['yenbuba_text'] ?? '');
    $indonesia = trim($_POST['indonesia_text'] ?? '');
    $pronunciation = trim($_POST['pronunciation'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($english === '' || $yenbuba === '') {
        $message = 'Teks Bahasa Inggris dan Bahasa Yenbuba wajib diisi!';
        $messageType = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO datasets (category, english_text, yenbuba_text, indonesia_text, pronunciation, notes) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $category, $english, $yenbuba, $indonesia, $pronunciation, $notes);
            if ($stmt->execute()) {
                $message = "Kosakata '$english' berhasil ditambahkan ke dataset!";
                $messageType = 'success';
                sync_dataset_json($conn);
            } else {
                $message = "Gagal menyimpan data: " . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// 2. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $category = trim($_POST['category'] ?? 'word');
    $english = trim($_POST['english_text'] ?? '');
    $yenbuba = trim($_POST['yenbuba_text'] ?? '');
    $indonesia = trim($_POST['indonesia_text'] ?? '');
    $pronunciation = trim($_POST['pronunciation'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($id <= 0 || $english === '' || $yenbuba === '') {
        $message = 'Data tidak valid untuk diubah.';
        $messageType = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE datasets SET category = ?, english_text = ?, yenbuba_text = ?, indonesia_text = ?, pronunciation = ?, notes = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssssi", $category, $english, $yenbuba, $indonesia, $pronunciation, $notes, $id);
            if ($stmt->execute()) {
                $message = "Data kosakata ID #$id berhasil diperbarui!";
                $messageType = 'success';
                sync_dataset_json($conn);
            } else {
                $message = "Gagal memperbarui: " . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// 3. Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM datasets WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Data ID #$id berhasil dihapus dari dataset.";
                $messageType = 'success';
                sync_dataset_json($conn);
            }
            $stmt->close();
        }
    }
}

// 4. Handle Seed/Batch Expansion
if (isset($_GET['action']) && $_GET['action'] === 'expand_dataset') {
    $expanded = [
        ['number', 'thirty', 'samfur kior', 'Sahm-foor kee-or', 'Angka tiga puluh'],
        ['number', 'forty', 'samfur fiak', 'Sahm-foor fee-ahk', 'Angka empat puluh'],
        ['number', 'fifty', 'samfur rim', 'Sahm-foor reem', 'Angka lima puluh'],
        ['number', 'one hundred', 'utin oser', 'Oo-tin oh-ser', 'Angka seratus'],
        ['greeting', 'how are you', 'wabe rohe', 'Wah-beh roh-heh?', 'Menanyakan kabar kawan'],
        ['greeting', 'i am fine', 'ya boi', 'Yah boh-ee', 'Menjawab kabar baik'],
        ['greeting', 'safe travels', 'fara roiso', 'Fah-rah roh-ee-soh', 'Ucapan selamat jalan / hati-hati'],
        ['sentence', 'i want to go diving', 'ya fro diving ma soren', 'Yah froh dye-ving mah soh-ren', 'Ingin menyelam di spot karang'],
        ['sentence', 'where can i rent a boat', 'wai sewa yaise', 'Wah-ee seh-wah yah-ee-seh?', 'Menanyakan sewa perahu ke pulau'],
        ['sentence', 'this food is delicious', 'yan ine kakara babor', 'Yahn ee-neh kah-kah-rah bah-bor', 'Memuji masakan ikan bakar warga'],
        ['sentence', 'do you speak english', 'wara kofen bahasa inggris ke', 'Wah-rah koh-fen bah-hah-sah ing-gris keh?', 'Menanyakan kemampuan bahasa'],
        ['sentence', 'what time is it', 'jam piro', 'Jahm pee-roh?', 'Menanyakan waktu atau jam'],
        ['word', 'island', 'meos', 'Meh-os', 'Pulau karang atau daratan'],
        ['word', 'sand', 'yen', 'Yen', 'Pasir putih pantai'],
        ['word', 'turtle', 'penu', 'Peh-noo', 'Penyu hijau yang sering terlihat'],
        ['word', 'manta ray', 'waiyer', 'Wah-ee-yer', 'Pari manta khas perairan Raja Ampat'],
        ['word', 'shark', 'manggwe', 'Mahng-gweh', 'Hiu karang sirip hitam'],
        ['phrase', 'see you later', 'kankarem radai', 'Kahn-kah-rem rah-dye', 'Sampai jumpa lagi nanti'],
        ['phrase', 'no problem', 'rohe ba', 'Roh-heh bah', 'Tidak apa-apa / santai saja'],
        ['phrase', 'excuse me', 'tabya ya', 'Tah-byah yah', 'Permisi lewat atau bicara']
    ];

    $added = 0;
    foreach ($expanded as $item) {
        $check = $conn->prepare("SELECT id FROM datasets WHERE LOWER(english_text) = ? LIMIT 1");
        $check->bind_param("s", $item[1]);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $insert = $conn->prepare("INSERT INTO datasets (category, english_text, yenbuba_text, pronunciation, notes) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $item[0], $item[1], $item[2], $item[3], $item[4]);
            $insert->execute();
            $insert->close();
            $added++;
        }
        $check->close();
    }
    sync_dataset_json($conn);
    $message = "Berhasil menambahkan $added kosakata tambahan ke dataset.";
    $messageType = 'success';
}

$search = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['cat'] ?? '');

$sql = "SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (english_text LIKE ? OR yenbuba_text LIKE ? OR notes LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
    $types = 'sss';
}

if ($filterCategory !== '' && in_array($filterCategory, ['greeting', 'number', 'sentence', 'word', 'phrase'])) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= 's';
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$datasetList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Data Bahasa - Tabya</title>
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
        <a href="dataset.php" class="sidebar-link" aria-current="page">
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

    <!-- Main Content -->
    <div style="min-width: 0; display: flex; flex-direction: column;">
      <header class="admin-header">
        <div class="admin-title-area">
          <h1>Kelola Data Bahasa (Dataset Kosakata)</h1>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
          <button type="button" class="btn-primary" onclick="openAddModal()">
            <iconify-icon icon="lucide:plus" style="font-size: 14px;"></iconify-icon>
            Tambah Kosakata
          </button>
          <a href="dataset.php?action=expand_dataset" class="btn-secondary" title="Tambah 20 data tambahan">
            <iconify-icon icon="lucide:package-plus" style="font-size: 14px;"></iconify-icon>
            +20 Kosakata
          </a>
          <a href="dataset.php?action=export&format=json" class="btn-secondary">
            <iconify-icon icon="lucide:download" style="font-size: 14px;"></iconify-icon>
            JSON
          </a>
          <a href="dataset.php?action=export&format=csv" class="btn-secondary">
            <iconify-icon icon="lucide:download" style="font-size: 14px;"></iconify-icon>
            CSV
          </a>
        </div>
      </header>

      <!-- Mobile Navigation Pills (Visible on mobile/tablet) -->
      <nav class="admin-mobile-nav" aria-label="Navigasi admin mobile">
        <a href="index.php" class="admin-mobile-tab">
          <iconify-icon icon="lucide:layout-dashboard"></iconify-icon> Dashboard
        </a>
        <a href="dataset.php" class="admin-mobile-tab active">
          <iconify-icon icon="lucide:languages"></iconify-icon> Data Bahasa
        </a>
        <a href="history.php" class="admin-mobile-tab">
          <iconify-icon icon="lucide:history"></iconify-icon> Riwayat
        </a>
        <a href="model_eval.php" class="admin-mobile-tab">
          <iconify-icon icon="lucide:cpu"></iconify-icon> Model AI
        </a>
        <a href="../index.php" target="_blank" class="admin-mobile-tab">
          <iconify-icon icon="lucide:external-link"></iconify-icon> Publik
        </a>
        <a href="logout.php" class="admin-mobile-tab danger">
          <iconify-icon icon="lucide:log-out"></iconify-icon> Keluar
        </a>
      </nav>

      <main class="admin-content">
        <?php if ($message): ?>
          <div class="alert-box <?= $messageType ?>">
            <iconify-icon icon="<?= $messageType === 'success' ? 'lucide:check-circle' : 'lucide:alert-circle' ?>" style="font-size: 16px;"></iconify-icon>
            <span><?= htmlspecialchars($message) ?></span>
          </div>
        <?php endif; ?>

        <!-- Filter & Search Panel -->
        <div class="admin-panel" style="margin-bottom: 16px;">
          <form action="dataset.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; align-items: center;">
            <input 
              type="text" 
              name="q" 
              value="<?= htmlspecialchars($search) ?>" 
              placeholder="Cari teks English / Yenbuba..." 
              class="form-control" 
              style="max-width: 280px;">

            <select name="cat" class="form-control" style="max-width: 180px; cursor: pointer;">
              <option value="">Semua Kategori</option>
              <option value="greeting" <?= $filterCategory === 'greeting' ? 'selected' : '' ?>>Greeting (Salam)</option>
              <option value="number" <?= $filterCategory === 'number' ? 'selected' : '' ?>>Number (Angka)</option>
              <option value="sentence" <?= $filterCategory === 'sentence' ? 'selected' : '' ?>>Sentence (Kalimat)</option>
              <option value="word" <?= $filterCategory === 'word' ? 'selected' : '' ?>>Word (Kata)</option>
              <option value="phrase" <?= $filterCategory === 'phrase' ? 'selected' : '' ?>>Phrase (Frasa)</option>
            </select>

            <button type="submit" class="btn-secondary">
              Filter
            </button>

            <?php if ($search || $filterCategory): ?>
              <a href="dataset.php" class="btn-secondary" style="color: var(--color-text-muted);">Reset</a>
            <?php endif; ?>

            <div style="margin-left: auto; font-size: 13px; color: var(--color-text-muted);">
              Total: <span class="font-mono" style="font-weight: 600; color: var(--color-text);"><?= count($datasetList) ?></span> baris
            </div>
          </form>
        </div>

        <!-- Dataset Table -->
        <div class="admin-panel">
          <div style="overflow-x: auto;">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width: 50px;">ID</th>
                  <th style="width: 80px;">Kategori</th>
                  <th>English (Source)</th>
                  <th>Bahasa Yenbuba (Target)</th>
                  <th>Bahasa Indonesia</th>
                  <th>Pelafalan</th>
                  <th>Catatan Konteks</th>
                  <th style="width: 110px; text-align: right;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($datasetList)): ?>
                  <tr>
                    <td colspan="8" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                      Tidak ada data yang sesuai filter pencarian.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($datasetList as $row): ?>
                    <tr>
                      <td class="font-mono" style="color: var(--color-text-muted);">#<?= $row['id'] ?></td>
                      <td>
                        <span style="font-size: 11px; padding: 2px 6px; border-radius: var(--radius-compact); background: var(--color-surface-muted); border: 1px solid var(--color-border);">
                          <?= htmlspecialchars($row['category']) ?>
                        </span>
                      </td>
                      <td><strong><?= htmlspecialchars($row['english_text']) ?></strong></td>
                      <td style="font-weight: 600;"><?= htmlspecialchars($row['yenbuba_text']) ?></td>
                      <td style="color: var(--color-text-muted); font-size: 13px;"><?= htmlspecialchars($row['indonesia_text'] ?? '-') ?></td>
                      <td style="font-style: italic; color: var(--color-text-muted); font-size: 12px;">
                        <?= htmlspecialchars($row['pronunciation'] ?: '-') ?>
                      </td>
                      <td style="font-size: 12px; color: var(--color-text-muted); max-width: 220px;">
                        <?= htmlspecialchars($row['notes'] ?: '-') ?>
                      </td>
                      <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 4px;">
                          <button 
                            type="button" 
                            class="btn-secondary" 
                            style="padding: 4px 8px; font-size: 12px;"
                            onclick='openEditModal(<?= json_encode($row) ?>)'>
                            Ubah
                          </button>
                          <a 
                            href="dataset.php?action=delete&id=<?= $row['id'] ?>" 
                            class="btn-danger" 
                            style="padding: 4px 8px; font-size: 12px;"
                            onclick="return confirm('Hapus kosakata <?= addslashes(htmlspecialchars($row['english_text'])) ?>?')">
                            Hapus
                          </a>
                        </div>
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

  <!-- Modal Add Data -->
  <div class="modal-overlay" id="addModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 style="font-size: 15px; font-weight: 600;">Tambah Data Bahasa Baru</h3>
        <button type="button" onclick="closeAddModal()" style="border: none; background: transparent; cursor: pointer; font-size: 18px;">&times;</button>
      </div>
      <form action="dataset.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="modal-body">
          <div class="form-group">
            <label for="addCategory">Kategori</label>
            <select name="category" id="addCategory" class="form-control" required>
              <option value="word">Word (Kata)</option>
              <option value="greeting">Greeting (Salam)</option>
              <option value="number">Number (Angka)</option>
              <option value="sentence">Sentence (Kalimat)</option>
              <option value="phrase">Phrase (Frasa)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="addEnglish">Teks Bahasa Inggris (English)</label>
            <input type="text" name="english_text" id="addEnglish" class="form-control" placeholder="Contoh: beautiful sea" required>
          </div>

          <div class="form-group">
            <label for="addYenbuba">Teks Bahasa Yenbuba (Biak-Beteu)</label>
            <input type="text" name="yenbuba_text" id="addYenbuba" class="form-control" placeholder="Contoh: soren kakara" required>
          </div>

          <div class="form-group">
            <label for="addIndonesia">Bahasa Indonesia (Arti Pendukung)</label>
            <input type="text" name="indonesia_text" id="addIndonesia" class="form-control" placeholder="Contoh: laut yang indah">
          </div>

          <div class="form-group">
            <label for="addPronunciation">Panduan Pelafalan (Pronunciation)</label>
            <input type="text" name="pronunciation" id="addPronunciation" class="form-control" placeholder="Contoh: Soh-ren kah-kah-rah">
          </div>

          <div class="form-group">
            <label for="addNotes">Catatan Konteks / Keterangan</label>
            <textarea name="notes" id="addNotes" class="form-control" rows="2" placeholder="Penggunaan dalam percakapan sehari-hari"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeAddModal()">Batal</button>
          <button type="submit" class="btn-primary">Simpan ke Dataset</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit Data -->
  <div class="modal-overlay" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 style="font-size: 15px; font-weight: 600;">Ubah Data Bahasa #<span id="editIdLabel"></span></h3>
        <button type="button" onclick="closeEditModal()" style="border: none; background: transparent; cursor: pointer; font-size: 18px;">&times;</button>
      </div>
      <form action="dataset.php" method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="form-group">
            <label for="editCategory">Kategori</label>
            <select name="category" id="editCategory" class="form-control" required>
              <option value="word">Word (Kata)</option>
              <option value="greeting">Greeting (Salam)</option>
              <option value="number">Number (Angka)</option>
              <option value="sentence">Sentence (Kalimat)</option>
              <option value="phrase">Phrase (Frasa)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="editEnglish">Teks Bahasa Inggris (English)</label>
            <input type="text" name="english_text" id="editEnglish" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="editYenbuba">Teks Bahasa Yenbuba (Biak-Beteu)</label>
            <input type="text" name="yenbuba_text" id="editYenbuba" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="editIndonesia">Bahasa Indonesia (Arti Pendukung)</label>
            <input type="text" name="indonesia_text" id="editIndonesia" class="form-control" placeholder="Contoh: laut yang indah">
          </div>

          <div class="form-group">
            <label for="editPronunciation">Panduan Pelafalan (Pronunciation)</label>
            <input type="text" name="pronunciation" id="editPronunciation" class="form-control">
          </div>

          <div class="form-group">
            <label for="editNotes">Catatan Konteks / Keterangan</label>
            <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeEditModal()">Batal</button>
          <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openAddModal() { document.getElementById('addModal').classList.add('active'); }
    function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }

    function openEditModal(data) {
      document.getElementById('editId').value = data.id;
      document.getElementById('editIdLabel').textContent = data.id;
      document.getElementById('editCategory').value = data.category;
      document.getElementById('editEnglish').value = data.english_text;
      document.getElementById('editYenbuba').value = data.yenbuba_text;
      document.getElementById('editIndonesia').value = data.indonesia_text || '';
      document.getElementById('editPronunciation').value = data.pronunciation || '';
      document.getElementById('editNotes').value = data.notes || '';
      document.getElementById('editModal').classList.add('active');
    }
    function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }

    window.onclick = function(e) {
      if (e.target === document.getElementById('addModal')) closeAddModal();
      if (e.target === document.getElementById('editModal')) closeEditModal();
    }
  </script>
</body>
</html>
