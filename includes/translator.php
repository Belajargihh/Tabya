<?php
/**
 * Tabya Translation Service (Hybrid Engine)
 * English -> Bahasa Daerah Kampung Yenbuba (Biak-Beteu)
 * 
 * Sesuai Dokumen Skripsi Troce Insanadi Hermin Saleo (Bab 2 & 3):
 * 1. Exact Dataset Lookup (Klausul Bab 3.2.4 c - Akurasi 100%)
 * 2. Slot-Filling Sentence Templates (Pola Kalimat Wawancara Tabel 3.2)
 * 3. Compound Sentence & Clause Segmentation (Pemisahan Klausa Bertingkat)
 * 4. Smart N-Gram & Morfologi Biak-Beteu Composition (Penerjemahan Kalimat Majemuk)
 * 5. PyTorch Neural Machine Translation (Seq2Seq GRU + Bahdanau Attention)
 */

require_once __DIR__ . '/db.php';

function log_translation($conn, $sourceText, $translatedText, $method, $confidence, $ip, $ua) {
    $logStmt = $conn->prepare("INSERT INTO translation_logs (source_text, translated_text, translation_method, confidence_score, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("sssdds", $sourceText, $translatedText, $method, $confidence, $ip, $ua);
        $logStmt->execute();
        $logStmt->close();
    }
}

function return_out_of_scope($conn, $inputText, $ip, $ua) {
    $unrecResult = [
        'success' => true,
        'is_out_of_scope' => true,
        'source_text' => $inputText,
        'translated_text' => 'Teks berada di luar cakupan kosakata pariwisata Kampung Yenbuba.',
        'indonesia_text' => 'Teks ini tidak dapat diterjemahkan karena memuat kata-kata di luar korpus bahasa daerah Kampung Yenbuba.',
        'pronunciation' => '-',
        'notes' => 'Sistem khusus menerjemahkan kosakata dan kalimat komunikasi wisatawan di Kampung Yenbuba (salam, angka, kalimat tanya, dan kosakata desa/bahari).',
        'category' => 'sentence',
        'method' => 'Di Luar Cakupan (Out of Scope)',
        'confidence' => 0.00,
        'breakdown' => []
    ];
    log_translation($conn, $inputText, $unrecResult['translated_text'], $unrecResult['method'], $unrecResult['confidence'], $ip, $ua);
    return $unrecResult;
}

/**
 * Menghasilkan rincian struktur kata/frasa untuk kalimat percakapan dataset
 */
function get_sentence_breakdown($english, $yenbuba, $indonesia, $pronunciation, $direction = 'en_to_yen') {
    $normEn = strtolower(trim(preg_replace('/[^\w\s]/', '', $english)));
    $normEn = preg_replace('/\s+/', ' ', $normEn);

    // Pemetaan rincian gramatikal untuk kalimat-kalimat percakapan dataset
    $knownPairs = [
        'where do you live' => [
            'en' => [
                ['source' => 'Where', 'translated' => 'ro dio', 'indonesia' => 'di mana', 'pronunciation' => 'roh dee-oh'],
                ['source' => 'you live', 'translated' => 'Kwain', 'indonesia' => 'kamu tinggal', 'pronunciation' => 'Kwy-een']
            ],
            'yen' => [
                ['source' => 'Kwain', 'translated' => 'you live', 'indonesia' => 'kamu tinggal', 'pronunciation' => 'Kwy-een'],
                ['source' => 'ro dio', 'translated' => 'Where', 'indonesia' => 'di mana', 'pronunciation' => 'roh dee-oh']
            ]
        ],
        'what is your name' => [
            'en' => [
                ['source' => 'What is', 'translated' => 'beja ido yo', 'indonesia' => 'siapa / apa gerangan', 'pronunciation' => 'beh-jah ee-doh yoh'],
                ['source' => 'your name', 'translated' => 'Hno hnon', 'indonesia' => 'nama kamu', 'pronunciation' => 'Hnoh hnon']
            ],
            'yen' => [
                ['source' => 'Hno hnon', 'translated' => 'your name', 'indonesia' => 'nama kamu', 'pronunciation' => 'Hnoh hnon'],
                ['source' => 'beja ido yo', 'translated' => 'what is', 'indonesia' => 'siapa / apa gerangan', 'pronunciation' => 'beh-jah ee-doh yoh']
            ]
        ],
        'where are you from' => [
            'en' => [
                ['source' => 'Where', 'translated' => 'dio', 'indonesia' => 'dari mana', 'pronunciation' => 'dee-oh'],
                ['source' => 'are you from', 'translated' => 'Waker', 'indonesia' => 'kamu berasal', 'pronunciation' => 'Wah-ker']
            ],
            'yen' => [
                ['source' => 'Waker', 'translated' => 'are you from', 'indonesia' => 'kamu berasal', 'pronunciation' => 'Wah-ker'],
                ['source' => 'dio', 'translated' => 'where', 'indonesia' => 'dari mana', 'pronunciation' => 'dee-oh']
            ]
        ],
        'where are you from two people' => [
            'en' => [
                ['source' => 'Where', 'translated' => 'dio', 'indonesia' => 'dari mana', 'pronunciation' => 'dee-oh'],
                ['source' => 'are you two from', 'translated' => 'Mu raker', 'indonesia' => 'kalian berdua berasal', 'pronunciation' => 'Moo rah-ker']
            ],
            'yen' => [
                ['source' => 'Mu raker', 'translated' => 'you two are from', 'indonesia' => 'kalian berdua berasal', 'pronunciation' => 'Moo rah-ker'],
                ['source' => 'dio', 'translated' => 'where', 'indonesia' => 'dari mana', 'pronunciation' => 'dee-oh']
            ]
        ],
        'where are you from three people' => [
            'en' => [
                ['source' => 'Where', 'translated' => 'dio', 'indonesia' => 'dari mana', 'pronunciation' => 'dee-oh'],
                ['source' => 'are you three from', 'translated' => 'Mko raker', 'indonesia' => 'kalian bertiga berasal', 'pronunciation' => 'M-koh rah-ker']
            ],
            'yen' => [
                ['source' => 'Mko raker', 'translated' => 'you three are from', 'indonesia' => 'kalian bertiga berasal', 'pronunciation' => 'M-koh rah-ker'],
                ['source' => 'dio', 'translated' => 'where', 'indonesia' => 'dari mana', 'pronunciation' => 'dee-oh']
            ]
        ],
        'my name is soma' => [
            'en' => [
                ['source' => 'My name is', 'translated' => 'Aya ine ido', 'indonesia' => 'Nama saya adalah', 'pronunciation' => 'Ah-yah ee-neh ee-doh'],
                ['source' => 'Soma', 'translated' => 'Soma', 'indonesia' => 'Soma (nama)', 'pronunciation' => 'Soh-mah']
            ],
            'yen' => [
                ['source' => 'Aya ine ido', 'translated' => 'My name is', 'indonesia' => 'Nama saya adalah', 'pronunciation' => 'Ah-yah ee-neh ee-doh'],
                ['source' => 'Soma', 'translated' => 'Soma', 'indonesia' => 'Soma (nama)', 'pronunciation' => 'Soh-mah']
            ]
        ],
        'i live in sawinggrai' => [
            'en' => [
                ['source' => 'I live in', 'translated' => 'Ya kain ro', 'indonesia' => 'Saya tinggal di', 'pronunciation' => 'Yah kah-een roh'],
                ['source' => 'Sawinggrai', 'translated' => 'Tawinggrai', 'indonesia' => 'Sawinggrai', 'pronunciation' => 'Tah-wing-grahee']
            ],
            'yen' => [
                ['source' => 'Ya kain ro', 'translated' => 'I live in', 'indonesia' => 'Saya tinggal di', 'pronunciation' => 'Yah kah-een roh'],
                ['source' => 'Tawinggrai', 'translated' => 'Sawinggrai', 'indonesia' => 'Sawinggrai', 'pronunciation' => 'Tah-wing-grahee']
            ]
        ],
        'i am from bali' => [
            'en' => [
                ['source' => 'I am from', 'translated' => 'Ya yaker', 'indonesia' => 'Saya berasal dari', 'pronunciation' => 'Yah yah-ker'],
                ['source' => 'Bali', 'translated' => 'Bali', 'indonesia' => 'Bali', 'pronunciation' => 'Bah-lee']
            ],
            'yen' => [
                ['source' => 'Ya yaker', 'translated' => 'I am from', 'indonesia' => 'Saya berasal dari', 'pronunciation' => 'Yah yah-ker'],
                ['source' => 'Bali', 'translated' => 'Bali', 'indonesia' => 'Bali', 'pronunciation' => 'Bah-lee']
            ]
        ],
        'good morning what is your name' => [
            'en' => [
                ['source' => 'Good morning', 'translated' => 'Arwo', 'indonesia' => 'Selamat pagi', 'pronunciation' => 'Ahr-woh'],
                ['source' => 'what is your name', 'translated' => 'hno hnon beja ido yo', 'indonesia' => 'siapa nama kamu?', 'pronunciation' => 'hnoh hnon beh-jah ee-doh yoh']
            ],
            'yen' => [
                ['source' => 'Arwo', 'translated' => 'Good morning', 'indonesia' => 'Selamat pagi', 'pronunciation' => 'Ahr-woh'],
                ['source' => 'hno hnon beja ido yo', 'translated' => 'what is your name', 'indonesia' => 'siapa nama kamu?', 'pronunciation' => 'hnoh hnon beh-jah ee-doh yoh']
            ]
        ]
    ];

    if (isset($knownPairs[$normEn])) {
        $dirKey = ($direction === 'yen_to_en') ? 'yen' : 'en';
        return $knownPairs[$normEn][$dirKey];
    }

    return [
        [
            'source' => $direction === 'yen_to_en' ? $yenbuba : $english,
            'translated' => $direction === 'yen_to_en' ? $english : $yenbuba,
            'indonesia' => $indonesia,
            'pronunciation' => $pronunciation
        ]
    ];
}

/**
 * Main Translation Function
 */
function translate_english_to_yenbuba($inputText) {
    $inputText = trim($inputText);
    if ($inputText === '') {
        return [
            'success' => false,
            'error' => 'Input text cannot be empty.',
            'source_text' => '',
            'translated_text' => '',
            'method' => 'None',
            'confidence' => 0
        ];
    }

    $conn = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    $normalized = strtolower($inputText);
    $normalizedClean = preg_replace('/[^\w\s]/', '', $normalized);
    $normalizedClean = preg_replace('/\s+/', ' ', trim($normalizedClean));

    // ==========================================
    // STEP 1: Exact Dataset Match Check
    // (Proposal Bab 3.2.4 c clause)
    // ==========================================
    $stmt = $conn->prepare("SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets WHERE LOWER(TRIM(english_text)) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $normalizedClean);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();

            $method = 'Dataset Match';
            $conf = 100.00;
            log_translation($conn, $inputText, $row['yenbuba_text'], $method, $conf, $ip, $ua);

            return [
                'success' => true,
                'source_text' => $inputText,
                'translated_text' => ucfirst($row['yenbuba_text']),
                'indonesia_text' => $row['indonesia_text'] ?? '',
                'pronunciation' => $row['pronunciation'] ?: '-',
                'notes' => $row['notes'] ?: 'Kosakata/kalimat tersimpan dalam dataset Kampung Yenbuba.',
                'category' => $row['category'],
                'method' => $method,
                'confidence' => 100.00,
                'breakdown' => get_sentence_breakdown($row['english_text'], $row['yenbuba_text'], $row['indonesia_text'] ?? '', $row['pronunciation'] ?: '-', 'en_to_yen')
            ];
        }
        $stmt->close();
    }

    // ==========================================
    // STEP 2: Sentence Template Patterns (Slot Filling)
    // Kalimat percakapan dinamis berbasis data autentik Tabel 3.2
    // ==========================================
    $templateResult = check_sentence_templates($conn, $normalizedClean, $inputText);
    if ($templateResult !== null) {
        log_translation($conn, $inputText, $templateResult['translated_text'], $templateResult['method'], $templateResult['confidence'], $ip, $ua);
        return $templateResult;
    }

    // ==========================================
    // STEP 3: Multi-Clause Compound Sentence Splitting
    // (Contoh: "Good morning, what is your name?" / "Hello, where do you live?")
    // ==========================================
    if (preg_match('/[,;.!?]/', $inputText) || preg_match('/\s+(and then|and)\s+/i', $inputText)) {
        $clauseResult = translate_compound_clauses($conn, $inputText);
        if ($clauseResult !== null && $clauseResult['success']) {
            log_translation($conn, $inputText, $clauseResult['translated_text'], $clauseResult['method'], $clauseResult['confidence'], $ip, $ua);
            return $clauseResult;
        }
    }

    // ==========================================
    // STEP 4: Smart Phrase & Grammar Composition
    // (Contoh: "eat fish", "two birds", "eat fish and rice", "one boat and two dogs")
    // ==========================================
    $smartResult = compose_smart_sentence($conn, $normalizedClean, $inputText);
    if ($smartResult !== null && $smartResult['match_ratio'] >= 0.60) {
        log_translation($conn, $inputText, $smartResult['translated_text'], $smartResult['method'], $smartResult['confidence'], $ip, $ua);
        return $smartResult;
    }

    // ==========================================
    // STEP 5: PyTorch NMT Seq2Seq GRU Engine
    // (Inferensi AI Deep Learning untuk variasi kalimat baru)
    // ==========================================
    $nmtResult = run_pytorch_nmt($conn, $normalizedClean, $inputText);
    if ($nmtResult !== null && $nmtResult['success']) {
        $inputWordCount = count(explode(' ', $normalizedClean));
        $outputWordCount = count(explode(' ', $nmtResult['translated_text']));

        // Jika NMT collapse menjadi 1 kata padahal input panjang
        if ($inputWordCount > 1 && $outputWordCount === 1) {
            if ($smartResult !== null && $smartResult['match_ratio'] >= 0.50) {
                log_translation($conn, $inputText, $smartResult['translated_text'], $smartResult['method'], $smartResult['confidence'], $ip, $ua);
                return $smartResult;
            }
            // Jika kata-kata di luar korpus pariwisata Yenbuba
            return return_out_of_scope($conn, $inputText, $ip, $ua);
        }

        // Jika NMT memiliki OOV terlalu tinggi pada input kalimat panjang
        if ($inputWordCount > 4 && ($smartResult === null || $smartResult['match_ratio'] < 0.35)) {
            return return_out_of_scope($conn, $inputText, $ip, $ua);
        }

        log_translation($conn, $inputText, $nmtResult['translated_text'], $nmtResult['method'], $nmtResult['confidence'], $ip, $ua);
        return $nmtResult;
    }

    // ==========================================
    // STEP 6: Evaluasi Rasio Kecocokan & Deteksi Scope (Bab 3.2.4)
    // ==========================================
    if ($smartResult !== null && $smartResult['match_ratio'] >= 0.50) {
        log_translation($conn, $inputText, $smartResult['translated_text'], $smartResult['method'], $smartResult['confidence'], $ip, $ua);
        return $smartResult;
    }

    return return_out_of_scope($conn, $inputText, $ip, $ua);
}

/**
 * Pola Kalimat Slot-Filling berdasarkan Tabel 3.2
 */
function check_sentence_templates($conn, $cleanText, $originalText) {
    // 1. My name is [X] -> Aya ine ido [X] (Tabel 3.2 No. 1)
    if (preg_match('/^my name is (.+)$/i', $cleanText, $m)) {
        $name = ucfirst(trim($m[1]));
        return [
            'success' => true,
            'source_text' => $originalText,
            'translated_text' => "Aya ine ido $name",
            'indonesia_text' => "Nama saya adalah $name",
            'pronunciation' => "Ah-yah ee-neh ee-doh $name",
            'notes' => 'Pola kalimat perkenalan diri dialek Biak-Beteu Kampung Yenbuba.',
            'category' => 'sentence',
            'method' => 'Sentence Template Pattern',
            'confidence' => 99.00,
            'breakdown' => [
                ['source' => 'My name is', 'translated' => 'Aya ine ido', 'indonesia' => 'Nama saya adalah', 'pronunciation' => 'Ah-yah ee-neh ee-doh'],
                ['source' => $name, 'translated' => $name, 'indonesia' => $name, 'pronunciation' => $name]
            ]
        ];
    }

    // 2. I live in [X] -> Ya kain ro [X] (Tabel 3.2 No. 3)
    if (preg_match('/^i live in (.+)$/i', $cleanText, $m)) {
        $place = ucwords(trim($m[1]));
        return [
            'success' => true,
            'source_text' => $originalText,
            'translated_text' => "Ya kain ro $place",
            'indonesia_text' => "Saya tinggal di $place",
            'pronunciation' => "Yah kah-een roh $place",
            'notes' => 'Pola kalimat tempat tinggal dialek Biak-Beteu (ro = preposisi di).',
            'category' => 'sentence',
            'method' => 'Sentence Template Pattern',
            'confidence' => 99.00,
            'breakdown' => [
                ['source' => 'I live in', 'translated' => 'Ya kain ro', 'indonesia' => 'Saya tinggal di', 'pronunciation' => 'Yah kah-een roh'],
                ['source' => $place, 'translated' => $place, 'indonesia' => $place, 'pronunciation' => $place]
            ]
        ];
    }

    // 3. I am from [X] / I come from [X] -> Ya yaker [X] (Tabel 3.2 No. 5)
    if (preg_match('/^i (am|come) from (.+)$/i', $cleanText, $m)) {
        $origin = ucwords(trim($m[2]));
        return [
            'success' => true,
            'source_text' => $originalText,
            'translated_text' => "Ya yaker $origin",
            'indonesia_text' => "Saya berasal dari $origin",
            'pronunciation' => "Yah yah-ker $origin",
            'notes' => 'Pola kalimat asal daerah dialek Biak-Beteu Kampung Yenbuba.',
            'category' => 'sentence',
            'method' => 'Sentence Template Pattern',
            'confidence' => 99.00,
            'breakdown' => [
                ['source' => 'I am from', 'translated' => 'Ya yaker', 'indonesia' => 'Saya berasal dari', 'pronunciation' => 'Yah yah-ker'],
                ['source' => $origin, 'translated' => $origin, 'indonesia' => $origin, 'pronunciation' => $origin]
            ]
        ];
    }

    // 4. Where is / Where are [X] -> [X] yaise?
    if (preg_match('/^where (is|are)( the)? (.+)$/i', $cleanText, $m)) {
        $target = trim($m[3]);
        $sub = lookup_single_word($conn, $target);
        $targetTrans = $sub ? $sub['yenbuba_text'] : $target;
        $targetIndo = $sub ? $sub['indonesia_text'] : $target;
        $targetPron = $sub ? $sub['pronunciation'] : $target;

        return [
            'success' => true,
            'source_text' => $originalText,
            'translated_text' => ucfirst("$targetTrans yaise"),
            'indonesia_text' => "Di mana $targetIndo?",
            'pronunciation' => "$targetPron yah-ee-seh",
            'notes' => 'Pola kalimat tanya lokasi dialek Yenbuba (yaise = di mana/arah).',
            'category' => 'sentence',
            'method' => 'Sentence Pattern (Biak-Beteu)',
            'confidence' => 95.00,
            'breakdown' => [
                ['source' => $target, 'translated' => $targetTrans, 'indonesia' => $targetIndo, 'pronunciation' => $targetPron],
                ['source' => 'where is', 'translated' => 'yaise', 'indonesia' => 'di mana', 'pronunciation' => 'yah-ee-seh']
            ]
        ];
    }

    return null;
}

/**
 * Penerjemahan Klausa Majemuk (Compound Sentences)
 * Memisahkan kalimat berdasarkan koma, titik koma, titik, tanda tanya, atau kata sambung "and"
 */
function translate_compound_clauses($conn, $text) {
    // Split clauses by punctuation
    $parts = preg_split('/([,;.!?]+|\s+and\s+)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    if (count($parts) <= 1) {
        return null;
    }

    $translatedClauses = [];
    $indonesiaClauses = [];
    $pronunciations = [];
    $breakdown = [];
    $totalConf = 0;
    $validClauses = 0;

    foreach ($parts as $part) {
        $trimmed = trim($part);
        if ($trimmed === '') continue;

        // If it's punctuation
        if (preg_match('/^[,;.!?]+$/', $trimmed)) {
            $translatedClauses[] = $trimmed;
            $indonesiaClauses[] = $trimmed;
            continue;
        }

        // If it's conjunction "and"
        if (strcasecmp($trimmed, 'and') === 0) {
            $translatedClauses[] = 'mai';
            $indonesiaClauses[] = 'dan';
            $pronunciations[] = 'mah-ee';
            $breakdown[] = [
                'source' => 'and',
                'translated' => 'mai',
                'indonesia' => 'dan',
                'pronunciation' => 'mah-ee'
            ];
            continue;
        }

        // Translate the sub-clause
        $cleanSub = preg_replace('/[^\w\s]/', '', strtolower($trimmed));
        $cleanSub = preg_replace('/\s+/', ' ', trim($cleanSub));

        // 1. Direct dataset lookup for sub-clause
        $subStmt = $conn->prepare("SELECT yenbuba_text, indonesia_text, pronunciation FROM datasets WHERE LOWER(TRIM(english_text)) = ? LIMIT 1");
        $found = false;
        if ($subStmt) {
            $subStmt->bind_param("s", $cleanSub);
            $subStmt->execute();
            $res = $subStmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $translatedClauses[] = $row['yenbuba_text'];
                $indonesiaClauses[] = $row['indonesia_text'] ?? $trimmed;
                if (!empty($row['pronunciation'])) $pronunciations[] = $row['pronunciation'];
                $breakdown[] = [
                    'source' => $trimmed,
                    'translated' => $row['yenbuba_text'],
                    'indonesia' => $row['indonesia_text'] ?? '',
                    'pronunciation' => $row['pronunciation'] ?: '-'
                ];
                $totalConf += 100;
                $validClauses++;
                $found = true;
            }
            $subStmt->close();
        }

        // 2. Check template for sub-clause
        if (!$found) {
            $tpl = check_sentence_templates($conn, $cleanSub, $trimmed);
            if ($tpl !== null) {
                $translatedClauses[] = $tpl['translated_text'];
                $indonesiaClauses[] = $tpl['indonesia_text'];
                if (!empty($tpl['pronunciation'])) $pronunciations[] = $tpl['pronunciation'];
                foreach ($tpl['breakdown'] as $b) $breakdown[] = $b;
                $totalConf += 98;
                $validClauses++;
                $found = true;
            }
        }

        // 3. Fallback to smart composer for sub-clause (hanya bila minimal 50% kata dikenal)
        if (!$found) {
            $comp = compose_smart_sentence($conn, $cleanSub, $trimmed);
            if ($comp !== null && $comp['match_ratio'] >= 0.50) {
                $translatedClauses[] = $comp['translated_text'];
                $indonesiaClauses[] = $comp['indonesia_text'];
                if (!empty($comp['pronunciation'])) $pronunciations[] = $comp['pronunciation'];
                foreach ($comp['breakdown'] as $b) $breakdown[] = $b;
                $totalConf += $comp['confidence'];
                $validClauses++;
                $found = true;
            }
        }

        if (!$found) {
            $translatedClauses[] = $trimmed;
            $indonesiaClauses[] = $trimmed;
            $totalConf += 0;
            $unmatchedClauses = ($unmatchedClauses ?? 0) + 1;
            $validClauses++;
        }
    }

    if ($validClauses === 0) return null;

    // Jika lebih dari separuh klausa tidak dikenal, batalkan segmentasi agar tidak campur aduk
    if (!empty($unmatchedClauses) && ($unmatchedClauses / $validClauses) > 0.40) {
        return null;
    }

    // Join clauses nicely handling punctuation
    $finalTrans = '';
    foreach ($translatedClauses as $tc) {
        if (preg_match('/^[,;.!?]+$/', $tc)) {
            $finalTrans .= $tc . ' ';
        } else {
            $finalTrans .= ($finalTrans !== '' && !preg_match('/[\s]$/', $finalTrans) ? ' ' : '') . $tc;
        }
    }

    $finalIndo = '';
    foreach ($indonesiaClauses as $ic) {
        if (preg_match('/^[,;.!?]+$/', $ic)) {
            $finalIndo .= $ic . ' ';
        } else {
            $finalIndo .= ($finalIndo !== '' && !preg_match('/[\s]$/', $finalIndo) ? ' ' : '') . $ic;
        }
    }

    $avgConf = round($totalConf / max(1, $validClauses), 1);

    return [
        'success' => true,
        'source_text' => $text,
        'translated_text' => ucfirst(trim($finalTrans)),
        'indonesia_text' => ucfirst(trim($finalIndo)),
        'pronunciation' => !empty($pronunciations) ? implode(' · ', array_unique($pronunciations)) : '-',
        'notes' => 'Penerjemahan klausa majemuk gabungan dialek Biak-Beteu Kampung Yenbuba.',
        'category' => 'sentence',
        'method' => 'Compound Sentence Segmentation',
        'confidence' => $avgConf,
        'breakdown' => $breakdown
    ];
}

/**
 * Smart N-Gram & Sentence Composer
 * Menerjemahkan kalimat majemuk, frasa kata benda, angka, dan kata kerja
 */
function compose_smart_sentence($conn, $cleanText, $originalText) {
    $words = explode(' ', $cleanText);
    if (empty($words)) return null;

    $translatedWords = [];
    $indonesiaWords = [];
    $pronunciations = [];
    $breakdown = [];
    $matchedCount = 0;
    $totalWords = count($words);

    // Common Biak Grammatical Particles
    $grammarMap = [
        'and' => ['yen' => 'mai', 'id' => 'dan', 'pron' => 'mah-ee'],
        'or' => ['yen' => 'ke', 'id' => 'atau', 'pron' => 'keh'],
        'i' => ['yen' => 'ya', 'id' => 'saya', 'pron' => 'yah'],
        'you' => ['yen' => 'wa', 'id' => 'kamu', 'pron' => 'wah'],
        'in' => ['yen' => 'ro', 'id' => 'di', 'pron' => 'roh'],
        'at' => ['yen' => 'ro', 'id' => 'di', 'pron' => 'roh'],
        'to' => ['yen' => 'ma', 'id' => 'ke', 'pron' => 'mah'],
        'with' => ['yen' => 'kuker', 'id' => 'bersama/dengan', 'pron' => 'koo-ker'],
        'yes' => ['yen' => 'o', 'id' => 'ya', 'pron' => 'oh'],
        'no' => ['yen' => 'ba', 'id' => 'tidak/bukan', 'pron' => 'bah'],
        'not' => ['yen' => 'ba', 'id' => 'tidak', 'pron' => 'bah'],
        'good' => ['yen' => 'kakara', 'id' => 'baik/bagus', 'pron' => 'kah-kah-rah'],
        'beautiful' => ['yen' => 'kakara', 'id' => 'indah', 'pron' => 'kah-kah-rah'],
        'big' => ['yen' => 'babor', 'id' => 'besar', 'pron' => 'bah-bor'],
        'small' => ['yen' => 'kasun', 'id' => 'kecil', 'pron' => 'kah-soon']
    ];

    // Omit copulas & articles in Biak
    $skipWords = ['is', 'are', 'am', 'was', 'were', 'the', 'a', 'an'];

    $i = 0;
    while ($i < $totalWords) {
        $matched = false;

        // Try 3-word phrase
        if ($i + 2 < $totalWords) {
            $phrase3 = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
            $sub = lookup_single_word($conn, $phrase3);
            if ($sub) {
                $translatedWords[] = $sub['yenbuba_text'];
                $indonesiaWords[] = $sub['indonesia_text'] ?? $phrase3;
                if (!empty($sub['pronunciation'])) $pronunciations[] = $sub['pronunciation'];
                $breakdown[] = [
                    'source' => $phrase3,
                    'translated' => $sub['yenbuba_text'],
                    'indonesia' => $sub['indonesia_text'] ?? '',
                    'pronunciation' => $sub['pronunciation'] ?: '-'
                ];
                $matchedCount += 3;
                $i += 3;
                continue;
            }
        }

        // Try 2-word phrase (misal: "good morning", "see you", "two people", "eat fish")
        if ($i + 1 < $totalWords) {
            $phrase2 = $words[$i] . ' ' . $words[$i + 1];
            $sub = lookup_single_word($conn, $phrase2);
            if ($sub) {
                $translatedWords[] = $sub['yenbuba_text'];
                $indonesiaWords[] = $sub['indonesia_text'] ?? $phrase2;
                if (!empty($sub['pronunciation'])) $pronunciations[] = $sub['pronunciation'];
                $breakdown[] = [
                    'source' => $phrase2,
                    'translated' => $sub['yenbuba_text'],
                    'indonesia' => $sub['indonesia_text'] ?? '',
                    'pronunciation' => $sub['pronunciation'] ?: '-'
                ];
                $matchedCount += 2;
                $i += 2;
                continue;
            }

            // Check Pattern: [NUMBER] [NOUN] -> Biak: [NOUN] [NUMBER] or [NUMBER] [NOUN]
            $numSub = lookup_single_word($conn, $words[$i]);
            $nounSub = lookup_single_word($conn, $words[$i + 1]);
            if ($numSub && $nounSub && $numSub['category'] === 'number') {
                $transPhrase = $nounSub['yenbuba_text'] . ' ' . $numSub['yenbuba_text'];
                $indoPhrase = ($numSub['indonesia_text'] ?: $words[$i]) . ' ' . ($nounSub['indonesia_text'] ?: $words[$i+1]);
                $pronPhrase = ($nounSub['pronunciation'] ?: $nounSub['yenbuba_text']) . ' ' . ($numSub['pronunciation'] ?: $numSub['yenbuba_text']);

                $translatedWords[] = $transPhrase;
                $indonesiaWords[] = $indoPhrase;
                $pronunciations[] = $pronPhrase;
                $breakdown[] = [
                    'source' => $phrase2,
                    'translated' => $transPhrase,
                    'indonesia' => $indoPhrase,
                    'pronunciation' => $pronPhrase
                ];
                $matchedCount += 2;
                $i += 2;
                continue;
            }
        }

        // Single word processing
        $curr = $words[$i];

        // Skip English grammatical articles
        if (in_array($curr, $skipWords)) {
            $i++;
            continue;
        }

        // Check Grammar Map
        if (isset($grammarMap[$curr])) {
            $g = $grammarMap[$curr];
            $translatedWords[] = $g['yen'];
            $indonesiaWords[] = $g['id'];
            $pronunciations[] = $g['pron'];
            $breakdown[] = [
                'source' => $curr,
                'translated' => $g['yen'],
                'indonesia' => $g['id'],
                'pronunciation' => $g['pron']
            ];
            $matchedCount++;
            $i++;
            continue;
        }

        // Single word lookup in datasets
        $sub = lookup_single_word($conn, $curr);
        if ($sub) {
            $translatedWords[] = $sub['yenbuba_text'];
            $indonesiaWords[] = $sub['indonesia_text'] ?? $curr;
            if (!empty($sub['pronunciation'])) $pronunciations[] = $sub['pronunciation'];
            $breakdown[] = [
                'source' => $curr,
                'translated' => $sub['yenbuba_text'],
                'indonesia' => $sub['indonesia_text'] ?? '',
                'pronunciation' => $sub['pronunciation'] ?: '-'
            ];
            $matchedCount++;
        } else {
            // Unmatched token kept as-is (e.g. proper names)
            $translatedWords[] = ucfirst($curr);
            $indonesiaWords[] = ucfirst($curr);
            $breakdown[] = [
                'source' => $curr,
                'translated' => ucfirst($curr),
                'indonesia' => ucfirst($curr),
                'pronunciation' => '-'
            ];
        }

        $i++;
    }

    if (empty($translatedWords)) return null;

    $finalText = ucfirst(implode(' ', $translatedWords));
    $finalIndo = ucfirst(implode(' ', $indonesiaWords));
    $ratio = $totalWords > 0 ? ($matchedCount / $totalWords) : 0;
    $confidence = round(max(50.0, min(97.0, $ratio * 96.0)), 1);

    return [
        'success' => true,
        'source_text' => $originalText,
        'translated_text' => $finalText,
        'indonesia_text' => $finalIndo,
        'pronunciation' => !empty($pronunciations) ? implode(' · ', array_unique($pronunciations)) : '-',
        'notes' => 'Penyusunan kalimat majemuk berbasis leksikon dan kaidah dialek Biak-Beteu.',
        'category' => 'sentence',
        'method' => 'Smart Sentence Composition (Biak-Beteu)',
        'confidence' => $confidence,
        'match_ratio' => $ratio,
        'matched_count' => $matchedCount,
        'breakdown' => $breakdown
    ];
}

/**
 * Helper Lookup Kata/Frasa Tunggal dari MySQL
 */
function lookup_single_word($conn, $term) {
    $term = strtolower(trim($term));
    $stmt = $conn->prepare("SELECT yenbuba_text, indonesia_text, pronunciation, category FROM datasets WHERE LOWER(TRIM(english_text)) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
    }

    // Stemming kata jamak bahasa Inggris (Morphological lemmatizer)
    $singulars = [];
    if (preg_match('/ves$/', $term)) {
        $singulars[] = preg_replace('/ves$/', 'fe', $term);
        $singulars[] = preg_replace('/ves$/', 'f', $term);
    }
    if (preg_match('/ies$/', $term)) {
        $singulars[] = preg_replace('/ies$/', 'y', $term);
    }
    if (preg_match('/es$/', $term)) {
        $singulars[] = preg_replace('/es$/', '', $term);
    }
    if (preg_match('/s$/', $term)) {
        $singulars[] = preg_replace('/s$/', '', $term);
    }

    foreach ($singulars as $sing) {
        $stmt = $conn->prepare("SELECT yenbuba_text, indonesia_text, pronunciation, category FROM datasets WHERE LOWER(TRIM(english_text)) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $sing);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                // Pola jamak dialek Biak-Beteu (Tabel 3.2: Man -> Mansi, Ben -> Bensi)
                $yenPlural = $row['yenbuba_text'];
                if (!preg_match('/si$/i', $yenPlural)) {
                    $yenPlural .= 'si';
                }
                return [
                    'yenbuba_text' => $yenPlural,
                    'indonesia_text' => $row['indonesia_text'] . ' (jamak)',
                    'pronunciation' => $row['pronunciation'] ? $row['pronunciation'] . '-see' : '-',
                    'category' => $row['category']
                ];
            }
            $stmt->close();
        }
    }

    return null;
}

/**
 * Eksekusi Mesin PyTorch NMT Seq2Seq
 */
function run_pytorch_nmt($conn, $cleanText, $originalText) {
    $pythonPath = 'python';
    $scriptPath = realpath(__DIR__ . '/../python/translate.py');

    if (!$scriptPath || !file_exists($scriptPath)) {
        return null;
    }

    $escapedInput = escapeshellarg($cleanText);
    $command = "$pythonPath \"$scriptPath\" $escapedInput 2>&1";
    $output = shell_exec($command);

    if (!$output) return null;

    $jsonStart = strpos($output, '{');
    if ($jsonStart === false) return null;

    $jsonStr = substr($output, $jsonStart);
    $data = json_decode($jsonStr, true);

    if (!is_array($data) || empty($data['success']) || empty($data['translated_text'])) {
        return null;
    }

    $translated = trim($data['translated_text']);

    // Lookup pronunciation for words in translation
    $transTokens = explode(' ', $translated);
    $pronunciations = [];
    foreach ($transTokens as $t) {
        $pStmt = $conn->prepare("SELECT pronunciation FROM datasets WHERE LOWER(yenbuba_text) LIKE ? LIMIT 1");
        if ($pStmt) {
            $likeTerm = "%$t%";
            $pStmt->bind_param("s", $likeTerm);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                if (!empty($pRow['pronunciation'])) {
                    $pronunciations[] = $pRow['pronunciation'];
                }
            }
            $pStmt->close();
        }
    }

    $conf = floatval($data['confidence'] ?? 90.00);

    return [
        'success' => true,
        'source_text' => $originalText,
        'translated_text' => ucfirst($translated),
        'indonesia_text' => '-',
        'pronunciation' => !empty($pronunciations) ? implode(' · ', array_unique($pronunciations)) : 'Pedoman intonasi Biak-Beteu',
        'notes' => 'Diterjemahkan melalui model Neural Machine Translation (Seq2Seq GRU + Attention).',
        'category' => 'sentence',
        'method' => 'NMT (Seq2Seq GRU + Attention)',
        'confidence' => $conf,
        'breakdown' => []
    ];
}

/**
 * Unified Translation Gateway (Supports Bidirectional: EN -> YEN or YEN -> EN)
 */
function translate_text($inputText, $direction = 'en_to_yen') {
    if ($direction === 'yen_to_en') {
        return translate_yenbuba_to_english($inputText);
    }
    return translate_english_to_yenbuba($inputText);
}

/**
 * Penerjemahan Dua Arah: Bahasa Yenbuba (Biak-Beteu) -> Bahasa Inggris
 */
function translate_yenbuba_to_english($inputText) {
    $inputText = trim($inputText);
    if ($inputText === '') {
        return [
            'success' => false,
            'error' => 'Input text cannot be empty.',
            'source_text' => '',
            'translated_text' => '',
            'method' => 'None',
            'confidence' => 0
        ];
    }

    $conn = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    $normalized = strtolower($inputText);
    $normalizedClean = preg_replace('/[^\w\s]/', '', $normalized);
    $normalizedClean = preg_replace('/\s+/', ' ', trim($normalizedClean));

    // ==========================================
    // STEP 1: Exact Dataset Match (Yenbuba -> English)
    // ==========================================
    $stmt = $conn->prepare("SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets WHERE LOWER(TRIM(yenbuba_text)) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $normalizedClean);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            $method = 'Dataset Match (Biak -> Eng)';
            $conf = 100.00;
            log_translation($conn, $inputText, $row['english_text'], $method, $conf, $ip, $ua);

            return [
                'success' => true,
                'source_text' => $inputText,
                'translated_text' => ucfirst($row['english_text']),
                'indonesia_text' => $row['indonesia_text'] ?? '',
                'pronunciation' => $row['pronunciation'] ?: '-',
                'notes' => $row['notes'] ?: 'Kosakata/kalimat tersimpan dalam dataset Kampung Yenbuba.',
                'category' => $row['category'],
                'method' => $method,
                'confidence' => 100.00,
                'breakdown' => get_sentence_breakdown($row['english_text'], $row['yenbuba_text'], $row['indonesia_text'] ?? '', $row['pronunciation'] ?: '-', 'yen_to_en')
            ];
        }
        $stmt->close();
    }

    // ==========================================
    // STEP 2: Yenbuba Sentence Templates (Slot-Filling)
    // ==========================================
    if (preg_match('/^aya ine ido (.+)$/i', $normalizedClean, $m)) {
        $name = ucfirst(trim($m[1]));
        return [
            'success' => true,
            'source_text' => $inputText,
            'translated_text' => "My name is $name",
            'indonesia_text' => "Nama saya adalah $name",
            'pronunciation' => "Ah-yah ee-neh ee-doh $name",
            'notes' => 'Pola kalimat perkenalan dialek Biak-Beteu.',
            'category' => 'sentence',
            'method' => 'Sentence Pattern (Biak -> Eng)',
            'confidence' => 99.00,
            'breakdown' => [
                ['source' => 'Aya ine ido', 'translated' => 'My name is', 'indonesia' => 'Nama saya adalah', 'pronunciation' => 'Ah-yah ee-neh ee-doh'],
                ['source' => $name, 'translated' => $name, 'indonesia' => $name, 'pronunciation' => $name]
            ]
        ];
    }

    if (preg_match('/^ya kain ro (.+)$/i', $normalizedClean, $m)) {
        $place = ucwords(trim($m[1]));
        return [
            'success' => true,
            'source_text' => $inputText,
            'translated_text' => "I live in $place",
            'indonesia_text' => "Saya tinggal di $place",
            'pronunciation' => "Yah kah-een roh $place",
            'notes' => 'Pola kalimat tempat tinggal dialek Biak-Beteu.',
            'category' => 'sentence',
            'method' => 'Sentence Pattern (Biak -> Eng)',
            'confidence' => 99.00,
            'breakdown' => [
                ['source' => 'Ya kain ro', 'translated' => 'I live in', 'indonesia' => 'Saya tinggal di', 'pronunciation' => 'Yah kah-een roh'],
                ['source' => $place, 'translated' => $place, 'indonesia' => $place, 'pronunciation' => $place]
            ]
        ];
    }

    if (preg_match('/^ya yaker (.+)$/i', $normalizedClean, $m)) {
        $origin = ucwords(trim($m[1]));
        return [
            'success' => true,
            'source_text' => $inputText,
            'translated_text' => "I am from $origin",
            'indonesia_text' => "Saya berasal dari $origin",
            'pronunciation' => "Yah yah-ker $origin",
            'notes' => 'Pola kalimat asal daerah dialek Biak-Beteu.',
            'category' => 'sentence',
            'method' => 'Sentence Pattern (Biak -> Eng)',
            'confidence' => 99.00,
            'breakdown' => [
                ['source' => 'Ya yaker', 'translated' => 'I am from', 'indonesia' => 'Saya berasal dari', 'pronunciation' => 'Yah yah-ker'],
                ['source' => $origin, 'translated' => $origin, 'indonesia' => $origin, 'pronunciation' => $origin]
            ]
        ];
    }

    if (preg_match('/^ya konan (.+)$/i', $normalizedClean, $m)) {
        $foodTerm = trim($m[1]);
        $sub = lookup_single_yenbuba_word($conn, $foodTerm);
        $engFood = $sub ? $sub['english_text'] : $foodTerm;
        $indoFood = $sub ? $sub['indonesia_text'] : $foodTerm;
        return [
            'success' => true,
            'source_text' => $inputText,
            'translated_text' => ucfirst("I eat $engFood"),
            'indonesia_text' => "Saya makan $indoFood",
            'pronunciation' => "Yah koh-nahn " . ($sub ? $sub['pronunciation'] : $foodTerm),
            'notes' => 'Pola kata kerja tindakan dialek Biak-Beteu.',
            'category' => 'sentence',
            'method' => 'Sentence Pattern (Biak -> Eng)',
            'confidence' => 96.00,
            'breakdown' => [
                ['source' => 'Ya konan', 'translated' => 'I eat', 'indonesia' => 'Saya makan', 'pronunciation' => 'Yah koh-nahn'],
                ['source' => $foodTerm, 'translated' => $engFood, 'indonesia' => $indoFood, 'pronunciation' => $sub ? $sub['pronunciation'] : '-']
            ]
        ];
    }

    // ==========================================
    // STEP 3: Clause Splitting (Biak -> Eng)
    // ==========================================
    if (preg_match('/[,;.!?]/', $inputText) || preg_match('/\s+mai\s+/i', $inputText)) {
        $parts = preg_split('/([,;.!?]+|\s+mai\s+)/i', $inputText, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (count($parts) > 1) {
            $translatedParts = [];
            $indonesiaParts = [];
            $pronunciations = [];
            $breakdown = [];
            $matched = 0;
            $total = 0;

            foreach ($parts as $p) {
                $trimmed = trim($p);
                if ($trimmed === '') continue;

                if (preg_match('/^[,;.!?]+$/', $trimmed)) {
                    $translatedParts[] = $trimmed;
                    $indonesiaParts[] = $trimmed;
                    continue;
                }

                if (strcasecmp($trimmed, 'mai') === 0) {
                    $translatedParts[] = 'and';
                    $indonesiaParts[] = 'dan';
                    $pronunciations[] = 'mah-ee';
                    $breakdown[] = ['source' => 'mai', 'translated' => 'and', 'indonesia' => 'dan', 'pronunciation' => 'mah-ee'];
                    continue;
                }

                $sub = translate_yenbuba_to_english($trimmed);
                if ($sub && empty($sub['is_out_of_scope'])) {
                    $translatedParts[] = $sub['translated_text'];
                    $indonesiaParts[] = $sub['indonesia_text'];
                    if (!empty($sub['pronunciation']) && $sub['pronunciation'] !== '-') $pronunciations[] = $sub['pronunciation'];
                    foreach ($sub['breakdown'] as $b) $breakdown[] = $b;
                    $matched++;
                } else {
                    $translatedParts[] = $trimmed;
                    $indonesiaParts[] = $trimmed;
                }
                $total++;
            }

            if ($total > 0 && ($matched / $total) >= 0.50) {
                $finalEng = ucfirst(trim(implode(' ', $translatedParts)));
                $finalIndo = ucfirst(trim(implode(' ', $indonesiaParts)));
                $finalEng = preg_replace('/\s+([,;.!?])/', '$1', $finalEng);
                $finalIndo = preg_replace('/\s+([,;.!?])/', '$1', $finalIndo);

                return [
                    'success' => true,
                    'source_text' => $inputText,
                    'translated_text' => $finalEng,
                    'indonesia_text' => $finalIndo,
                    'pronunciation' => !empty($pronunciations) ? implode(' · ', array_unique($pronunciations)) : '-',
                    'notes' => 'Penerjemahan klausa majemuk Bahasa Yenbuba ke Bahasa Inggris.',
                    'category' => 'sentence',
                    'method' => 'Compound Clause (Biak -> Eng)',
                    'confidence' => 96.00,
                    'breakdown' => $breakdown
                ];
            }
        }
    }

    // ==========================================
    // STEP 4: Smart Biak -> English N-Gram Composition
    // (Handles "Mansi Turu" -> "Two birds", "Konan Ien" -> "Eat fish")
    // ==========================================
    $words = explode(' ', $normalizedClean);
    $totalWords = count($words);
    $translatedWords = [];
    $indonesiaWords = [];
    $pronunciations = [];
    $breakdown = [];
    $matchedCount = 0;

    $i = 0;
    while ($i < $totalWords) {
        // Check pattern: [NOUN] [NUMBER] (Biak grammar e.g. "Mansi turu" -> English: "Two birds")
        if ($i + 1 < $totalWords) {
            $w1 = lookup_single_yenbuba_word($conn, $words[$i]);
            $w2 = lookup_single_yenbuba_word($conn, $words[$i + 1]);

            if ($w1 && $w2 && $w2['category'] === 'number') {
                $flippedEng = ucfirst($w2['english_text']) . ' ' . strtolower($w1['english_text']);
                $flippedIndo = $w2['indonesia_text'] . ' ' . strtolower($w1['indonesia_text']);
                $pron = ($w1['pronunciation'] ?: $words[$i]) . ' ' . ($w2['pronunciation'] ?: $words[$i+1]);

                $translatedWords[] = $flippedEng;
                $indonesiaWords[] = $flippedIndo;
                $pronunciations[] = $pron;
                $breakdown[] = [
                    'source' => $words[$i] . ' ' . $words[$i+1],
                    'translated' => $flippedEng,
                    'indonesia' => $flippedIndo,
                    'pronunciation' => $pron
                ];
                $matchedCount += 2;
                $i += 2;
                continue;
            }

            // Check 2-word phrase
            $p2 = $words[$i] . ' ' . $words[$i + 1];
            $sub2 = lookup_single_yenbuba_word($conn, $p2);
            if ($sub2) {
                $translatedWords[] = $sub2['english_text'];
                $indonesiaWords[] = $sub2['indonesia_text'] ?? $p2;
                if (!empty($sub2['pronunciation'])) $pronunciations[] = $sub2['pronunciation'];
                $breakdown[] = [
                    'source' => $p2,
                    'translated' => $sub2['english_text'],
                    'indonesia' => $sub2['indonesia_text'] ?? '',
                    'pronunciation' => $sub2['pronunciation'] ?: '-'
                ];
                $matchedCount += 2;
                $i += 2;
                continue;
            }
        }

        $curr = $words[$i];

        // Conjunctions & Particles
        if ($curr === 'mai') {
            $translatedWords[] = 'and';
            $indonesiaWords[] = 'dan';
            $pronunciations[] = 'mah-ee';
            $breakdown[] = ['source' => 'mai', 'translated' => 'and', 'indonesia' => 'dan', 'pronunciation' => 'mah-ee'];
            $matchedCount++;
            $i++;
            continue;
        }
        if ($curr === 'ro') {
            $translatedWords[] = 'in';
            $indonesiaWords[] = 'di';
            $pronunciations[] = 'roh';
            $breakdown[] = ['source' => 'ro', 'translated' => 'in', 'indonesia' => 'di', 'pronunciation' => 'roh'];
            $matchedCount++;
            $i++;
            continue;
        }

        $sub = lookup_single_yenbuba_word($conn, $curr);
        if ($sub) {
            $translatedWords[] = strtolower($sub['english_text']);
            $indonesiaWords[] = strtolower($sub['indonesia_text'] ?? $curr);
            if (!empty($sub['pronunciation'])) $pronunciations[] = $sub['pronunciation'];
            $breakdown[] = [
                'source' => $curr,
                'translated' => $sub['english_text'],
                'indonesia' => $sub['indonesia_text'] ?? '',
                'pronunciation' => $sub['pronunciation'] ?: '-'
            ];
            $matchedCount++;
        } else {
            $translatedWords[] = ucfirst($curr);
            $indonesiaWords[] = ucfirst($curr);
            $breakdown[] = [
                'source' => $curr,
                'translated' => ucfirst($curr),
                'indonesia' => ucfirst($curr),
                'pronunciation' => '-'
            ];
        }
        $i++;
    }

    $ratio = $totalWords > 0 ? ($matchedCount / $totalWords) : 0;
    if ($ratio >= 0.50) {
        $finalEng = ucfirst(implode(' ', $translatedWords));
        $finalIndo = ucfirst(implode(' ', $indonesiaWords));
        $conf = round(max(50.0, min(97.0, $ratio * 96.0)), 1);

        return [
            'success' => true,
            'source_text' => $inputText,
            'translated_text' => $finalEng,
            'indonesia_text' => $finalIndo,
            'pronunciation' => !empty($pronunciations) ? implode(' · ', array_unique($pronunciations)) : '-',
            'notes' => 'Penerjemahan Bahasa Yenbuba (Biak-Beteu) ke Bahasa Inggris.',
            'category' => 'sentence',
            'method' => 'Smart Composition (Biak -> Eng)',
            'confidence' => $conf,
            'breakdown' => $breakdown
        ];
    }

    return [
        'success' => true,
        'is_out_of_scope' => true,
        'source_text' => $inputText,
        'translated_text' => 'Teks berada di luar cakupan kosakata Bahasa Daerah Kampung Yenbuba.',
        'indonesia_text' => 'Kata-kata dalam teks ini tidak ditemukan dalam dataset bahasa daerah setempat.',
        'pronunciation' => '-',
        'notes' => 'Sesuai Bab 1.4 Batasan Masalah, sistem hanya menerjemahkan kosakata dan kalimat komunikasi pariwisata Kampung Yenbuba.',
        'category' => 'sentence',
        'method' => 'Di Luar Cakupan (Out of Scope)',
        'confidence' => 0.00,
        'breakdown' => []
    ];
}

/**
 * Helper Lookup Kata/Frasa Tunggal berdasarkan Bahasa Yenbuba
 */
function lookup_single_yenbuba_word($conn, $term) {
    $term = strtolower(trim($term));
    $stmt = $conn->prepare("SELECT english_text, yenbuba_text, indonesia_text, pronunciation, category FROM datasets WHERE LOWER(TRIM(yenbuba_text)) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
    }
    return null;
}
