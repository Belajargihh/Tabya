<?php
require_once __DIR__ . '/includes/db.php';
record_visitor('translate.php');

$initialQuery = trim($_GET['q'] ?? $_GET['text'] ?? '');
$initialDir = trim($_GET['dir'] ?? 'en_to_yen');
if (!in_array($initialDir, ['en_to_yen', 'yen_to_en'])) {
    $initialDir = 'en_to_yen';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Penerjemahan Teks - Tabya</title>
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
        <a href="index.php" class="nav-link">Beranda</a>
        <a href="translate.php" class="nav-link" aria-current="page">Terjemahkan</a>
        <a href="dictionary.php" class="nav-link">Kamus Kosakata</a>
        <a href="admin/login.php" class="btn-admin-cta">
          <iconify-icon icon="lucide:lock" style="font-size: 14px;"></iconify-icon>
          Masuk Admin
        </a>
      </nav>
    </div>
  </header>

  <!-- Main Split Translation Workspace -->
  <main class="container" style="display: flex; flex-direction: column; gap: var(--space-6); padding-top: var(--space-8); padding-bottom: var(--space-16); max-width: 1060px;">

    <!-- Breadcrumb & Title -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--space-3);">
      <div style="display: flex; flex-direction: column; gap: var(--space-2);">
        <div style="display: flex; align-items: center; gap: var(--space-2);">
          <span aria-hidden="true" style="display: block; width: 24px; height: 4px; background: var(--color-primary); border-radius: var(--radius-compact);"></span>
          <span class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">Workspace Penerjemah</span>
        </div>
        <h1 class="text-heading-md" style="margin: 0;">Penerjemahan Teks Langsung (Dua Arah)</h1>
      </div>
    </div>

    <!-- Swap Toolbar -->
    <div class="swap-toolbar">
      <div style="display: flex; align-items: center; gap: var(--space-2);">
        <iconify-icon icon="lucide:languages" style="font-size: 18px; color: var(--color-primary-text);"></iconify-icon>
        <span id="sourceLangTitle" class="swap-lang-tag">English</span>
      </div>

      <!-- Interactive Swap Button -->
      <button type="button" id="swapBtn" class="btn-swap-control" title="Tukar arah bahasa (English &harr; Yenbuba)">
        <iconify-icon icon="lucide:arrow-left-right" style="font-size: 16px;"></iconify-icon>
        <span id="swapBtnLabel">Tukar Bahasa (Swap)</span>
      </button>

      <div style="display: flex; align-items: center; gap: var(--space-2);">
        <span id="targetLangTitle" class="swap-lang-tag">Bahasa Yenbuba (Biak-Beteu)</span>
      </div>
    </div>

    <!-- Side-by-Side Split Workspace (Left Input, Right Output Display) -->
    <div class="result-split-grid" style="min-height: 380px;">
      
      <!-- ==============================================
           LEFT PANE: Input Textarea (Editable Source)
           ============================================== -->
      <div class="result-pane-source" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <!-- Header Source -->
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-3);">
            <span id="sourceLabelSmall" class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">
              ENGLISH (SOURCE)
            </span>
            <div style="display: flex; align-items: center; gap: var(--space-3);">
              <span id="charCount" class="text-caption font-mono" style="color: var(--color-text-muted);">0 / 300</span>
              <button type="button" id="clearBtn" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 12px; padding: 0;" title="Kosongkan teks">
                &times; Hapus
              </button>
            </div>
          </div>

          <!-- Seamless Split Textarea -->
          <textarea 
            id="mainInput" 
            class="split-textarea" 
            placeholder="Tulis teks di sini (contoh: 'Two birds', 'Good morning', 'Eat fish')..."
            maxlength="300"
            autofocus><?= htmlspecialchars($initialQuery) ?></textarea>
        </div>

        <!-- Bottom Controls inside Left Pane -->
        <div style="margin-top: var(--space-4);">
          <!-- Dynamic Quick Chips -->
          <div style="margin-bottom: var(--space-3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <span class="text-caption" style="color: var(--color-text-muted); font-size: 11px;">Contoh cepat (klik untuk langsung terjemahkan):</span>
            </div>
            <div id="quickChipsContainer" style="display: flex; flex-wrap: wrap; gap: 6px;">
              <!-- Chips injected dynamically by JS according to direction -->
            </div>
          </div>

          <!-- Action Footer -->
          <div style="display: flex; justify-content: space-between; align-items: center; pt: var(--space-3); border-top: 1px solid var(--color-border); padding-top: var(--space-3);">
            <div style="display: flex; align-items: center; gap: var(--space-3);">
              <!-- Speech-to-Text Mic Button -->
              <button type="button" id="micBtn" class="btn-secondary-quiet" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-full); border: 1px solid var(--color-border);" title="Bicara lewat mikrofon (Speech-to-Text)">
                <iconify-icon id="micIcon" icon="lucide:mic" style="font-size: 15px;"></iconify-icon>
                <span id="micLabel">Bicara (Mic)</span>
              </button>

              <span class="text-caption" style="color: var(--color-text-muted); font-size: 11px;">
                Tekan <kbd style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 1px 4px; border-radius: 3px; font-family: var(--font-mono);">Ctrl + Enter</kbd>
              </span>
            </div>

            <button type="button" id="translateBtn" class="btn-primary" style="padding: 8px 18px;">
              <span>Translate</span>
              <iconify-icon icon="lucide:arrow-right" style="font-size: 15px;"></iconify-icon>
            </button>
          </div>
        </div>
      </div>

      <!-- ==============================================
           RIGHT PANE: Output Display (Rendered Result)
           Persis Tampilan Screenshot dengan 4px Lime Decision Rail
           ============================================== -->
      <div class="result-pane-target" style="display: flex; flex-direction: column; justify-content: space-between;">
        
        <div>
          <!-- Header Target -->
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-4);">
            <span id="targetLabelSmall" class="text-label-md" style="color: var(--color-primary-text); text-transform: uppercase;">
              BAHASA YENBUBA (BIAK-BETEU)
            </span>
            <span id="targetPill" class="status-pill font-mono">Target</span>
          </div>

          <!-- Dynamic Output Content -->
          <div id="targetContentArea">
            <!-- Initial Empty State -->
            <div id="emptyState" style="padding: var(--space-8) var(--space-2); text-align: center; color: var(--color-text-muted);">
              <iconify-icon icon="lucide:sparkles" style="font-size: 28px; color: var(--color-border-strong); margin-bottom: var(--space-2); display: block; margin-inline: auto;"></iconify-icon>
              <div class="text-body-md" style="font-weight: 500; margin-bottom: 4px;">Hasil terjemahan akan tampil di sini</div>
              <div class="text-caption">Ketik kata/kalimat di sebelah kiri atau klik contoh cepat di bawah kolom input.</div>
            </div>

            <!-- Active Translation Result (Hidden until translated) -->
            <div id="resultContent" style="display: none; flex-direction: column;">
              
              <!-- Target Translated Text (Large & Bold) -->
              <div id="outTranslatedText" class="text-heading-md" style="font-size: 28px; line-height: 1.3; font-weight: 600; color: var(--color-text); margin-bottom: var(--space-2);">
                -
              </div>

              <!-- Indonesian Meaning -->
              <div id="outIndoWrap" style="margin-bottom: var(--space-2); color: var(--color-text-muted); font-size: 14px;">
                Arti Bahasa Indonesia: <strong id="outIndoText" style="color: var(--color-text);">-</strong>
              </div>

              <!-- Pronunciation with quick listen button -->
              <div id="outPronWrap" style="margin-bottom: var(--space-3); display: flex; align-items: center; justify-content: space-between; gap: var(--space-2); background: var(--color-surface-muted); padding: 8px 12px; border-radius: var(--radius-card); border: 1px solid var(--color-border);">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <iconify-icon icon="lucide:volume-2" style="font-size: 16px; color: var(--color-text-muted);"></iconify-icon>
                  <span class="text-body-sm" style="font-style: italic; color: var(--color-text-muted);">
                    Pelafalan: <strong id="outPronText" style="color: var(--color-text); font-style: normal;">-</strong>
                  </span>
                </div>
                <button type="button" id="pronSpeakBtn" class="btn-secondary-quiet" style="padding: 3px 10px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; border-radius: var(--radius-full); border: 1px solid var(--color-border);" title="Dengarkan pelafalan">
                  <iconify-icon id="pronSpeakIcon" icon="lucide:volume-2" style="font-size: 13px;"></iconify-icon>
                  <span id="pronSpeakLabel">Dengar</span>
                </button>
              </div>

              <!-- Out of Scope Warning Box -->
              <div id="outScopeBox" style="display: none; padding: var(--space-4); background: #FFFBEB; border: 1px solid #FDE68A; border-radius: var(--radius-card); margin-bottom: var(--space-3);">
                <div style="display: flex; align-items: center; gap: var(--space-2); color: #B45309; font-weight: 600; margin-bottom: var(--space-2); font-size: 14px;">
                  <iconify-icon icon="lucide:alert-triangle" style="font-size: 18px;"></iconify-icon>
                  Teks di Luar Cakupan Kosakata
                </div>
                <p style="margin: 0 0 var(--space-3) 0; font-size: 13px; color: #78350F; line-height: 1.5;">
                  Sistem Tabya saat ini dikhususkan untuk komunikasi praktis wisatawan di Kampung Yenbuba. Teks naratif bebas atau fiksi di luar kosakata kampung belum didukung.
                </p>
                <div style="font-size: 12px; color: #92400E; font-weight: 600; margin-bottom: 6px;">Contoh kalimat yang didukung dalam dataset:</div>
                <div id="outScopeChips" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
              </div>

            </div>
          </div>
        </div>

        <!-- Action Buttons at Bottom of Right Pane -->
        <div id="resultFooter" style="display: none; align-items: center; justify-content: flex-start; gap: var(--space-2); pt: var(--space-3); border-top: 1px solid var(--color-border); padding-top: var(--space-3); margin-top: var(--space-4);">
          <button type="button" id="copyResultBtn" class="btn-secondary-quiet" style="font-size: 13px; padding: 6px 14px;">
            <iconify-icon icon="lucide:copy" style="font-size: 15px;"></iconify-icon>
            <span id="copyBtnLabel">Salin Terjemahan</span>
          </button>

          <button type="button" id="speakTargetBtn" class="btn-secondary-quiet" style="font-size: 13px; padding: 6px 14px;" title="Putar audio hasil terjemahan">
            <iconify-icon id="speakTargetIcon" icon="lucide:volume-2" style="font-size: 15px;"></iconify-icon>
            <span id="speakTargetLabel">Putar Suara</span>
          </button>
        </div>

      </div>

    </div>

    <!-- Breakdown Table Area (For multi-word / compound sentences) -->
    <div id="breakdownSection" style="display: none; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card); overflow: hidden;">
      <div style="padding: var(--space-3) var(--space-4); background: var(--color-surface-muted); border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between;">
        <span class="text-label-md" style="color: var(--color-text-muted); text-transform: uppercase;">Rincian Struktur Kata & Frasa Kalimat</span>
        <span id="breakdownCount" class="text-caption font-mono" style="color: var(--color-text-muted);">0 Segmen</span>
      </div>
      <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
          <thead>
            <tr style="border-bottom: 1px solid var(--color-border); text-align: left; background: var(--color-surface);">
              <th id="thSource" style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Teks Asal</th>
              <th id="thTarget" style="padding: 10px 16px; font-weight: 600; color: var(--color-text);">Hasil Terjemahan</th>
              <th style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Arti Indonesia</th>
              <th style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Pelafalan</th>
            </tr>
          </thead>
          <tbody id="breakdownTableBody"></tbody>
        </table>
      </div>
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

  <script>
    // State
    let currentDirection = '<?= $initialDir ?>'; // 'en_to_yen' or 'yen_to_en'
    let lastTranslatedText = '';

    // Elements
    const swapBtn = document.getElementById('swapBtn');
    const sourceLangTitle = document.getElementById('sourceLangTitle');
    const targetLangTitle = document.getElementById('targetLangTitle');
    const sourceLabelSmall = document.getElementById('sourceLabelSmall');
    const targetLabelSmall = document.getElementById('targetLabelSmall');
    const mainInput = document.getElementById('mainInput');
    const charCount = document.getElementById('charCount');
    const clearBtn = document.getElementById('clearBtn');
    const translateBtn = document.getElementById('translateBtn');
    const quickChipsContainer = document.getElementById('quickChipsContainer');
    
    // Output Elements
    const emptyState = document.getElementById('emptyState');
    const resultContent = document.getElementById('resultContent');
    const outTranslatedText = document.getElementById('outTranslatedText');
    const outIndoWrap = document.getElementById('outIndoWrap');
    const outIndoText = document.getElementById('outIndoText');
    const outPronWrap = document.getElementById('outPronWrap');
    const outPronText = document.getElementById('outPronText');
    const outScopeBox = document.getElementById('outScopeBox');
    const outScopeChips = document.getElementById('outScopeChips');
    const resultFooter = document.getElementById('resultFooter');
    const copyResultBtn = document.getElementById('copyResultBtn');
    const copyBtnLabel = document.getElementById('copyBtnLabel');
    const targetPill = document.getElementById('targetPill');

    // Speech Recognition & Synthesis Elements
    const micBtn = document.getElementById('micBtn');
    const micLabel = document.getElementById('micLabel');
    const speakTargetBtn = document.getElementById('speakTargetBtn');
    const speakTargetLabel = document.getElementById('speakTargetLabel');
    const pronSpeakBtn = document.getElementById('pronSpeakBtn');
    const pronSpeakLabel = document.getElementById('pronSpeakLabel');

    // Breakdown Elements
    const breakdownSection = document.getElementById('breakdownSection');
    const breakdownTableBody = document.getElementById('breakdownTableBody');
    const breakdownCount = document.getElementById('breakdownCount');
    const thSource = document.getElementById('thSource');
    const thTarget = document.getElementById('thTarget');

    // Suggestion Chips Data
    const chipsData = {
      en_to_yen: [
        'Two birds',
        'Eat fish and rice',
        'Where do you live',
        'Good morning, what is your name?',
        'Thank you',
        'My name is Soma',
        'I live in Yenbuba',
        'One boat and two knives'
      ],
      yen_to_en: [
        'Mansi turu',
        'Konan ien mai fat',
        'Kwain ro dio',
        'Arwo, hno hnon beja ido yo?',
        'Injo',
        'Aya ine ido Soma',
        'Ya kain ro Yenbuba',
        'Wae weter mai inoesi turu'
      ]
    };

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function updateLanguageLabels() {
      if (currentDirection === 'en_to_yen') {
        sourceLangTitle.textContent = 'English';
        targetLangTitle.textContent = 'Bahasa Yenbuba (Biak-Beteu)';
        sourceLabelSmall.textContent = 'ENGLISH (SOURCE)';
        targetLabelSmall.textContent = 'BAHASA YENBUBA (BIAK-BETEU)';
        mainInput.placeholder = "Tulis teks Bahasa Inggris (contoh: 'Two birds', 'Good morning', 'Eat fish')...";
        thSource.textContent = 'English (Sumber)';
        thTarget.textContent = 'Bahasa Yenbuba (Target)';
      } else {
        sourceLangTitle.textContent = 'Bahasa Yenbuba (Biak-Beteu)';
        targetLangTitle.textContent = 'English';
        sourceLabelSmall.textContent = 'BAHASA YENBUBA (SOURCE)';
        targetLabelSmall.textContent = 'ENGLISH (TARGET)';
        mainInput.placeholder = "Tulis teks Bahasa Yenbuba (contoh: 'Mansi turu', 'Arwo', 'Konan ien')...";
        thSource.textContent = 'Bahasa Yenbuba (Sumber)';
        thTarget.textContent = 'English (Target)';
      }
      renderQuickChips();
    }

    function renderQuickChips() {
      const list = chipsData[currentDirection] || [];
      quickChipsContainer.innerHTML = '';
      list.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-secondary-quiet chip';
        btn.style.fontSize = '12px';
        btn.style.padding = '3px 8px';
        btn.textContent = item;
        btn.addEventListener('click', () => {
          mainInput.value = item;
          updateCharCount();
          autoResizeInput();
          executeTranslation(item);
        });
        quickChipsContainer.appendChild(btn);
      });
    }

    function updateCharCount() {
      const len = (mainInput.value || '').length;
      charCount.textContent = `${len} / 300`;
    }

    function executeTranslation(text) {
      text = (text !== undefined ? text : mainInput.value || '').trim();
      if (!text) {
        showEmptyState();
        return;
      }

      // Button loading indicator
      translateBtn.disabled = true;
      translateBtn.innerHTML = '<iconify-icon icon="lucide:loader-2" class="spin" style="font-size: 15px;"></iconify-icon> <span>Menerjemahkan...</span>';

      fetch('api/translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          text: text,
          direction: currentDirection
        })
      })
      .then(res => res.json())
      .then(json => {
        if (json.success && json.data) {
          renderResult(json.data);
        } else {
          alert(json.error || 'Terjadi kesalahan saat memproses terjemahan.');
        }
      })
      .catch(err => {
        console.error(err);
        alert('Gagal menghubungi server penerjemah.');
      })
      .finally(() => {
        translateBtn.disabled = false;
        translateBtn.innerHTML = '<span>Translate</span> <iconify-icon icon="lucide:arrow-right" style="font-size: 15px;"></iconify-icon>';
      });
    }

    function showEmptyState() {
      emptyState.style.display = 'block';
      resultContent.style.display = 'none';
      resultFooter.style.display = 'none';
      breakdownSection.style.display = 'none';
      targetPill.textContent = 'Target';
      targetPill.className = 'status-pill font-mono';
    }

    function renderResult(data) {
      emptyState.style.display = 'none';
      resultContent.style.display = 'flex';
      resultFooter.style.display = 'flex';

      lastTranslatedText = data.translated_text || '';
      outTranslatedText.textContent = lastTranslatedText;

      if (data.is_out_of_scope) {
        targetPill.textContent = 'Out of Scope';
        targetPill.className = 'status-pill font-mono';
        outScopeBox.style.display = 'block';
        outIndoWrap.style.display = 'none';
        outPronWrap.style.display = 'none';
        breakdownSection.style.display = 'none';

        // Render recommended chips
        const recList = currentDirection === 'en_to_yen' 
          ? ['Where do you live', 'What is your name', 'Good morning, what is your name?', 'Eat fish and rice']
          : ['Kwain ro dio', 'Hno hnon beja ido yo', 'Arwo, hno hnon beja ido yo?', 'Konan ien mai fat'];

        outScopeChips.innerHTML = '';
        recList.forEach(c => {
          const chipBtn = document.createElement('button');
          chipBtn.type = 'button';
          chipBtn.className = 'status-pill';
          chipBtn.style.cursor = 'pointer';
          chipBtn.style.color = '#78350F';
          chipBtn.style.background = '#FEF3C7';
          chipBtn.style.border = '1px solid #FDE68A';
          chipBtn.textContent = c;
          chipBtn.addEventListener('click', () => {
            mainInput.value = c;
            updateCharCount();
            executeTranslation(c);
          });
          outScopeChips.appendChild(chipBtn);
        });

      } else {
        targetPill.textContent = 'Target';
        targetPill.className = 'status-pill font-mono';
        outScopeBox.style.display = 'none';

        // Indonesian Meaning
        if (data.indonesia_text && data.indonesia_text !== '-') {
          outIndoWrap.style.display = 'block';
          outIndoText.textContent = data.indonesia_text;
        } else {
          outIndoWrap.style.display = 'none';
        }

        // Pronunciation
        if (data.pronunciation && data.pronunciation !== '-') {
          outPronWrap.style.display = 'flex';
          outPronText.textContent = data.pronunciation;
        } else {
          outPronWrap.style.display = 'none';
        }

        // Breakdown Table
        if (data.breakdown && data.breakdown.length > 1) {
          breakdownTableBody.innerHTML = '';
          data.breakdown.forEach(b => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--color-border)';
            tr.innerHTML = `
              <td style="padding: 8px 16px;">${escapeHtml(b.source)}</td>
              <td style="padding: 8px 16px; font-weight: 600; color: var(--color-primary-text);">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                  <span>${escapeHtml(b.translated)}</span>
                  <button type="button" class="btn-secondary-quiet" style="padding: 2px 6px; font-size: 11px;" onclick="playSpeech('${escapeHtml(b.translated)}', '${currentDirection === 'en_to_yen' ? 'id-ID' : 'en-US'}')" title="Dengarkan kata ini">
                    <iconify-icon icon="lucide:volume-2" style="font-size: 12px;"></iconify-icon>
                  </button>
                </div>
              </td>
              <td style="padding: 8px 16px; color: var(--color-text-muted);">${escapeHtml(b.indonesia || '-')}</td>
              <td style="padding: 8px 16px; font-style: italic; color: var(--color-text-muted);">${escapeHtml(b.pronunciation || '-')}</td>
            `;
            breakdownTableBody.appendChild(tr);
          });
          breakdownCount.textContent = `${data.breakdown.length} Segmen`;
          breakdownSection.style.display = 'block';
        } else {
          breakdownSection.style.display = 'none';
        }
      }
    }

    // Swap Direction Handler
    swapBtn.addEventListener('click', () => {
      // Toggle
      currentDirection = currentDirection === 'en_to_yen' ? 'yen_to_en' : 'en_to_yen';

      // If there is already a translation result, swap the text into the input!
      if (lastTranslatedText && !lastTranslatedText.includes('di luar cakupan')) {
        mainInput.value = lastTranslatedText;
      }
      
      updateLanguageLabels();
      updateCharCount();
      autoResizeInput();

      // If mainInput has text, translate immediately in new direction!
      if (mainInput.value.trim()) {
        executeTranslation(mainInput.value.trim());
      } else {
        showEmptyState();
      }
    });

    // Translate Button click
    translateBtn.addEventListener('click', () => {
      executeTranslation(mainInput.value);
    });

    // Clear Button
    clearBtn.addEventListener('click', () => {
      mainInput.value = '';
      updateCharCount();
      autoResizeInput();
      showEmptyState();
      mainInput.focus();
    });

    // Copy to Clipboard
    copyResultBtn.addEventListener('click', () => {
      if (!lastTranslatedText) return;
      navigator.clipboard.writeText(lastTranslatedText.trim()).then(() => {
        copyBtnLabel.textContent = 'Tersalin!';
        setTimeout(() => {
          copyBtnLabel.textContent = 'Salin Terjemahan';
        }, 1800);
      });
    });

    // ==========================================
    // TEXT-TO-SPEECH (TTS) - Web Speech Synthesis
    // ==========================================
    function playSpeech(text, lang = 'id-ID', btnElement = null, labelElement = null) {
      if (!('speechSynthesis' in window)) {
        alert('Peramban web Anda belum mendukung fitur Text-to-Speech.');
        return;
      }

      window.speechSynthesis.cancel();
      if (!text || text === '-' || text.trim() === '') return;

      const utterance = new SpeechSynthesisUtterance(text.trim());
      const voices = window.speechSynthesis.getVoices();

      // Find matching voice
      let bestVoice = voices.find(v => v.lang.toLowerCase().replace('_', '-').startsWith(lang.toLowerCase()));
      if (!bestVoice && lang.startsWith('id')) {
        bestVoice = voices.find(v => v.lang.toLowerCase().startsWith('id') || v.lang.toLowerCase().startsWith('ms'));
      }
      if (bestVoice) {
        utterance.voice = bestVoice;
      }

      utterance.lang = lang;
      utterance.rate = 0.88; // Comfortable clear pace
      utterance.pitch = 1.0;

      if (btnElement) {
        btnElement.classList.add('speaking-active');
      }
      const originalLabel = labelElement ? labelElement.textContent : '';
      if (labelElement) {
        labelElement.textContent = 'Memutar...';
      }

      const resetVoiceUI = () => {
        if (btnElement) btnElement.classList.remove('speaking-active');
        if (labelElement) labelElement.textContent = originalLabel;
      };

      utterance.onend = resetVoiceUI;
      utterance.onerror = resetVoiceUI;

      window.speechSynthesis.speak(utterance);
    }

    // Play Target Translated Sentence
    speakTargetBtn.addEventListener('click', () => {
      if (!lastTranslatedText) return;
      const targetLang = (currentDirection === 'en_to_yen') ? 'id-ID' : 'en-US';
      playSpeech(lastTranslatedText, targetLang, speakTargetBtn, speakTargetLabel);
    });

    // Play Pronunciation Guide
    pronSpeakBtn.addEventListener('click', () => {
      const pron = (outPronText.textContent || '').trim();
      if (!pron || pron === '-') return;
      playSpeech(pron, 'id-ID', pronSpeakBtn, pronSpeakLabel);
    });

    // ==========================================
    // SPEECH-TO-TEXT (STT) - Web Speech Recognition
    // ==========================================
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let isRecording = false;

    if (SpeechRecognition) {
      recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;

      recognition.onstart = () => {
        isRecording = true;
        micBtn.classList.add('mic-active');
        micLabel.textContent = 'Mendengarkan...';
      };

      recognition.onresult = (event) => {
        if (event.results && event.results[0] && event.results[0][0]) {
          const transcript = event.results[0][0].transcript;
          if (transcript) {
            mainInput.value = transcript;
            updateCharCount();
            autoResizeInput();
            executeTranslation(transcript);
          }
        }
      };

      recognition.onerror = (event) => {
        console.warn('Speech Recognition error:', event.error);
        stopRecording();
        if (event.error === 'not-allowed') {
          alert('Izin akses mikrofon belum diizinkan. Silakan aktifkan izin mikrofon di browser Anda.');
        }
      };

      recognition.onend = () => {
        stopRecording();
      };
    }

    function stopRecording() {
      isRecording = false;
      micBtn.classList.remove('mic-active');
      micLabel.textContent = 'Bicara (Mic)';
    }

    micBtn.addEventListener('click', () => {
      if (!SpeechRecognition) {
        alert('Fitur Speech-to-Text belum didukung oleh browser ini. Disarankan menggunakan Google Chrome atau Microsoft Edge.');
        return;
      }

      if (isRecording) {
        recognition.stop();
        stopRecording();
      } else {
        // Set recognition language based on current direction
        recognition.lang = (currentDirection === 'en_to_yen') ? 'en-US' : 'id-ID';
        try {
          recognition.start();
        } catch (err) {
          console.warn('Failed to start recognition:', err);
          stopRecording();
        }
      }
    });

    // Pre-load voices for TTS on browser load
    if ('speechSynthesis' in window) {
      window.speechSynthesis.onvoiceschanged = () => {
        window.speechSynthesis.getVoices();
      };
    }

    function autoResizeInput() {
      mainInput.style.height = 'auto';
      mainInput.style.height = Math.max(160, mainInput.scrollHeight) + 'px';
    }

    // Keyboard Shortcuts: Ctrl+Enter triggers translate
    mainInput.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        executeTranslation(mainInput.value);
      }
    });

    mainInput.addEventListener('input', () => {
      updateCharCount();
      autoResizeInput();
    });

    // Initial setup on load
    window.addEventListener('DOMContentLoaded', () => {
      updateLanguageLabels();
      updateCharCount();
      autoResizeInput();
      const initial = (mainInput.value || '').trim();
      if (initial) {
        executeTranslation(initial);
      } else {
        showEmptyState();
      }
    });
  </script>
</body>
</html>
