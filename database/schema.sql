-- Database Schema for Tabya Translation System
-- Kampung Yenbuba, Raja Ampat (Bahasa Biak sub-dialek Beteu)

CREATE DATABASE IF NOT EXISTS tabya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tabya_db;

-- 1. Admins Table
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Admin (admin / admin123)
INSERT INTO admins (username, password, full_name, email) VALUES
('admin', '$2y$10$UXRzDVLmrtwa78k9nT60TevBNg/AWJ/zijGomPjqokHaSfFAhO50a', 'Administrator Yenbuba', 'admin@tabya-yenbuba.id');

-- 2. Datasets Table (Kosakata & Kalimat)
DROP TABLE IF EXISTS datasets;
CREATE TABLE datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('greeting', 'number', 'sentence', 'word', 'phrase') NOT NULL DEFAULT 'word',
    english_text VARCHAR(255) NOT NULL,
    yenbuba_text VARCHAR(255) NOT NULL,
    pronunciation VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_english (english_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Translation Logs Table
DROP TABLE IF EXISTS translation_logs;
CREATE TABLE translation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_text TEXT NOT NULL,
    translated_text TEXT NOT NULL,
    translation_method VARCHAR(50) NOT NULL DEFAULT 'NMT Seq2Seq',
    confidence_score DECIMAL(5,2) DEFAULT 0.00,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Visitors Table (Simple Analytics)
DROP TABLE IF EXISTS visitors;
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    page VARCHAR(100) NOT NULL,
    user_agent VARCHAR(255) NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Initial Dataset (45+ entries matching Bab 3 specification)
INSERT INTO datasets (category, english_text, yenbuba_text, pronunciation, notes) VALUES
-- 13 Numbers
('number', 'one', 'oser', 'Oh-ser', 'Angka satu'),
('number', 'two', 'suru', 'Soo-roo', 'Angka dua'),
('number', 'three', 'kior', 'Kee-or', 'Angka tiga'),
('number', 'four', 'fiak', 'Fee-ahk', 'Angka empat'),
('number', 'five', 'rim', 'Reem', 'Angka lima'),
('number', 'six', 'wonen', 'Woh-nen', 'Angka enam'),
('number', 'seven', 'fik', 'Feek', 'Angka tujuh'),
('number', 'eight', 'war', 'Wahr', 'Angka delapan'),
('number', 'nine', 'siw', 'Seew', 'Angka sembilan'),
('number', 'ten', 'samfur', 'Sahm-foor', 'Angka sepuluh'),
('number', 'eleven', 'samfur rasoser', 'Sahm-foor rah-soh-ser', 'Angka sebelas'),
('number', 'twelve', 'samfur rasuru', 'Sahm-foor rah-soo-roo', 'Angka dua belas'),
('number', 'twenty', 'samfur suru', 'Sahm-foor soo-roo', 'Angka dua puluh'),

-- 6 Greetings
('greeting', 'hello', 'tabya', 'Tah-byah', 'Salam pembuka hormat khas Raja Ampat/Biak'),
('greeting', 'good morning', 'awino', 'Ah-wee-noh', 'Salam waktu pagi hari'),
('greeting', 'good afternoon', 'roiso', 'Roh-ee-soh', 'Salam waktu siang/tengah hari'),
('greeting', 'good evening', 'rasar', 'Rah-sahr', 'Salam waktu sore menjelang malam'),
('greeting', 'good night', 'robe', 'Roh-beh', 'Salam waktu malam hari menjelang tidur'),
('greeting', 'welcome', 'rosongsara', 'Roh-song-sah-rah', 'Ungkapan selamat datang kepada tamu'),

-- 12 Sentences
('sentence', 'where is the jetty', 'rabo yaise', 'Rah-boh yah-ee-seh?', 'Menanyakan letak jembatan/dermaga kayu Yenbuba'),
('sentence', 'where is the pier', 'rabo yaise', 'Rah-boh yah-ee-seh?', 'Padanan kata dermaga kayu'),
('sentence', 'welcome to yenbuba', 'rosongsara ma yenbuba', 'Roh-song-sah-rah mah Yen-boo-bah', 'Menyambut wisatawan yang baru tiba di kampung'),
('sentence', 'how much is this', 'piro ro ine', 'Pee-roh roh ee-neh?', 'Menanyakan harga suvenir atau barang'),
('sentence', 'what is your name', 'snanem rohe', 'Sknah-nem roh-heh?', 'Menanyakan nama lawan bicara'),
('sentence', 'my name is', 'snaneri', 'Sknah-neh-ree', 'Memperkenalkan nama diri sendiri'),
('sentence', 'where do you come from', 'wama yaise', 'Wah-mah yah-ee-seh?', 'Menanyakan asal daerah wisatawan'),
('sentence', 'can you help me', 'wara bantu ya ke', 'Wah-rah bahn-too yah keh?', 'Meminta tolong dengan sopan'),
('sentence', 'i want to eat fish', 'ya fro yan inggres', 'Yah froh yahn eeng-gres', 'Menyatakan keinginan makan ikan segar'),
('sentence', 'beautiful sea', 'soren kakara', 'Soh-ren kah-kah-rah', 'Memuji keindahan laut Yenbuba'),
('sentence', 'the coral reef is beautiful', 'manpun ine kakara', 'Mahn-poon ee-neh kah-kah-rah', 'Pujian wisatawan saat snorkeling di spot terumbu karang'),
('sentence', 'see you tomorrow', 'kankarem rasoreso', 'Kankah-rem rah-soh-reh-soh', 'Salam perpisahan ramah'),

-- 22 Words & Phrases
('word', 'thank you', 'kasumasa', 'Kah-soo-mah-sah', 'Ungkapan terima kasih'),
('phrase', 'thank you very much', 'kasumasa babor', 'Kah-soo-mah-sah bah-bor', 'Terima kasih banyak'),
('word', 'yes', 'iyo', 'Ee-yoh', 'Persetujuan atau konfirmasi'),
('word', 'no', 'iso ba', 'Ee-soh bah', 'Penolakan atau negasi'),
('word', 'water', 'war', 'Wahr', 'Air tawar atau air minum'),
('word', 'sea', 'soren', 'Soh-ren', 'Laut lepas / perairan'),
('word', 'fish', 'inggres', 'Eeng-gres', 'Ikan laut / konsumsi'),
('word', 'boat', 'wai', 'Wah-ee', 'Perahu tradisional perahu dayung/motor'),
('word', 'island', 'meos', 'Meh-os', 'Pulau (contoh: Meos Mansar)'),
('word', 'beach', 'yen', 'Yen', 'Pantai berpasir putih'),
('word', 'village', 'mnu', 'Mnoo', 'Kampung atau pemukiman'),
('word', 'house', 'ruma', 'Roo-mah', 'Rumah tinggal warga'),
('word', 'jetty', 'rabo', 'Rah-boh', 'Jembatan atau dermaga kayu'),
('word', 'pier', 'rabo', 'Rah-boh', 'Dermaga sandar perahu'),
('word', 'coral', 'manpun', 'Mahn-poon', 'Terumbu karang bawah laut'),
('word', 'friend', 'manmandur', 'Mahn-mahn-door', 'Kawan atau sahabat akrab'),
('word', 'sun', 'ori', 'Oh-ree', 'Matahari siang terik'),
('word', 'rain', 'meran', 'Meh-rahn', 'Hujan'),
('word', 'eat', 'yan', 'Yahn', 'Kata kerja makan'),
('word', 'drink', 'inem', 'Ee-nem', 'Kata kerja minum'),
('word', 'good', 'kakara', 'Kah-kah-rah', 'Bagus, baik, berkualitas'),
('word', 'beautiful', 'myeun', 'Myeh-oon', 'Indah, elok, mempesona'),
('word', 'coconut', 'manan', 'Mah-nahn', 'Kelapa muda / buah kelapa'),
('word', 'money', 'pipi', 'Pee-pee', 'Uang tunai untuk transaksi'),
('word', 'help', 'bantu', 'Bahn-too', 'Bantuan / pertolongan');

-- Initial demo translation logs to populate dashboard stats
INSERT INTO translation_logs (source_text, translated_text, translation_method, confidence_score, ip_address, user_agent) VALUES
('Hello', 'Tabya', 'Dataset Match (Bab 3.2.4 c)', 100.00, '127.0.0.1', 'Mozilla/5.0'),
('Thank you very much', 'Kasumasa babor', 'Dataset Match (Bab 3.2.4 c)', 100.00, '127.0.0.1', 'Mozilla/5.0'),
('Where is the jetty', 'Rabo yaise', 'Dataset Match (Bab 3.2.4 c)', 100.00, '127.0.0.1', 'Mozilla/5.0'),
('Beautiful sea', 'Soren kakara', 'NMT Seq2Seq GRU', 96.40, '127.0.0.1', 'Mozilla/5.0'),
('Good morning friend', 'Awino manmandur', 'NMT Hybrid (Seq2Seq + Lookup)', 94.80, '127.0.0.1', 'Mozilla/5.0');

-- Initial visitors
INSERT INTO visitors (ip_address, page, user_agent) VALUES
('127.0.0.1', 'index.php', 'Mozilla/5.0'),
('127.0.0.1', 'translate.php', 'Mozilla/5.0'),
('192.168.1.10', 'translate.php', 'Mozilla/5.0');

