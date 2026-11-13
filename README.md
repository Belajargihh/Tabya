# Tabya - English to Yenbuba Translation System
**Sistem Penerjemah Teks Bahasa Inggris ke Bahasa Daerah Kampung Yenbuba (Bahasa Biak sub-dialek Beteu, Raja Ampat)**

---

## 📌 1. Gambaran Umum Proyek
**Tabya** adalah aplikasi penerjemah berbasis web yang dirancang untuk mengatasi kendala komunikasi antara wisatawan mancanegara dan masyarakat lokal di **Kampung Yenbuba, Pulau Mansuar, Distrik Meos Mansar, Kabupaten Raja Ampat, Papua Barat Daya**.

Kata *"Tabya"* (atau *Tabea*) adalah salam penghormatan dan persaudaraan khas masyarakat Biak/Raja Ampat saat menyambut tamu.

---

## 🚀 2. Alamat Akses di Laragon
Sistem telah terhubung langsung dengan Laragon Apache & MySQL:
- **Beranda Publik:** [http://localhost/tabya/](http://localhost/tabya/)
- **Halaman Penerjemahan:** [http://localhost/tabya/translate.php](http://localhost/tabya/translate.php)
- **Halaman Hasil:** [http://localhost/tabya/result.php](http://localhost/tabya/result.php)
- **Kamus Kosakata Interaktif:** [http://localhost/tabya/dictionary.php](http://localhost/tabya/dictionary.php)
- **Portal Admin:** [http://localhost/tabya/admin/login.php](http://localhost/tabya/admin/login.php)
  - **Username:** `admin`
  - **Password:** `admin123`

---

## 🛠️ 3. Arsitektur & Tech Stack
- **Design System:** Mengadopsi **Ramp Foundation** dari DesainPakeAI (Canvas `#F7F7F2`, Ledger Ink `#0C0A08`, Decision Accent Acid Lime `#E4F222`, 4px decision rail, 1px hairline border, Inter & Geist Mono typography, zero drop-shadow).
- **Frontend:** HTML5 semantik, CSS3 murni tanpa framework bloated, Vanilla JavaScript responsif.
- **Backend Web:** PHP 8.3 & Database MySQL 8.4 (`tabya_db`).
- **Mesin Penerjemah AI:** Python 3.14 + **PyTorch 2.10** dengan arsitektur **Seq2Seq RNN-GRU + Bahdanau Attention Mechanism**.
- **Evaluasi Akademik Bab 4:** **NLTK 3.9** untuk kalkulasi **BLEU Score** (BLEU-1 hingga BLEU-4), matriks uji **Black Box Testing** (Test Case 1–6), dan instrumen kuesioner **SUS (System Usability Scale)** 10 butir pertanyaan.
- **Dataset Autentik:** 48 entri kosakata autentik bersumber langsung dari wawancara Bab 3 Tabel 3.2 (Bapak Oscar Mambrasar & Bapak Yohanes Sauyai) serta sketsa mockup Bab 3 Gambar 3.8/3.11 ("Eat" &rarr; "Konan", "Welcome" &rarr; "Jou Suba").

---

## 💡 4. Solusi Masalah Teknis Dataset vs Algoritma (Bab 3.2.4 Poin c)
Di proposal skripsi terdapat catatan kritis: model *deep learning* NMT tidak dapat dilatih murni jika data hanya puluhan baris. Sistem Tabya memecahkan masalah ini dengan pendekatan **Hybrid Translator**:
1. **Pencocokan Presisi (Dataset Lookup):** Sesuai klausul Bab 3.2.4 (c), jika teks cocok dengan kosakata/kalimat di database, sistem mengembalikan hasil dengan akurasi 100% serta panduan pelafalan fonetik.
2. **Mesin Neural Machine Translation (Seq2Seq GRU):** Untuk frasa dan kalimat dinamis, sistem menjalankan model inferensi PyTorch.
3. **Sinkronisasi Otomatis:** Saat admin menambah kosakata baru lewat CRUD di web, sistem dapat melakukan *re-training* instan hanya dengan 1 klik tombol di dashboard admin!

---

## 📊 5. Menu & Fitur Lengkap
### A. Hak Akses Publik (Wisatawan & Warga Lokal)
1. **Home / Beranda:** Hero sambutan, tombol *Start Translation*, budaya Yenbuba, dan perkenalan dialek Biak-Beteu.
2. **Form Penerjemahan:** Form input teks Bahasa Inggris, chip saran cepat (Greetings, Numbers, Tourist Inquiries), dan tombol *Translate*.
3. **Halaman Hasil:** Tampilan perbandingan teks asli (Inggris) vs terjemahan (Yenbuba), fonetik pelafalan, catatan konteks, tombol salin, dan tombol *Back to Translation*.
4. **Kamus Kosakata:** Eksplorasi seluruh kosakata dengan filter kategori dan pencarian kata kunci.

### B. Hak Akses Administrator
1. **Login:** Autentikasi aman (`password_hash`).
2. **Dashboard:** Statistik lengkap (*Total Language Data*, *Total Users*, dan *Translation History*), grafik distribusi kategori, dan log terbaru.
3. **Kelola Data Bahasa (CRUD):** Tambah, lihat, ubah, dan hapus kosakata/kalimat dengan kategori (*Word*, *Greeting*, *Number*, *Sentence*, *Phrase*). Dilengkapi fitur ekspor JSON/CSV dan ekspansi dataset.
4. **Riwayat Terjemahan:** Log seluruh pencarian pengunjung beserta IP, metode mesin, dan skor akurasi.
5. **Evaluasi AI & BLEU Score:** Pengujian benchmark BLEU-1 hingga BLEU-4 dengan tabel siap kutip untuk **Bab 4 Skripsi**, panduan *Black Box Testing*, dan kuesioner *SUS (System Usability Scale)*.

---

## ⚙️ 6. Panduan Menjalankan Sistem

### Langkah 1: Persiapan Database
1. Nyalakan Laragon / XAMPP (Apache & MySQL).
2. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Buat database baru bernama `tabya_db`.
4. Import file SQL: `database/tabya_db.sql`.

### Langkah 2: Persiapan Dependensi Python
Buka terminal di folder project, jalankan:
```bash
pip install -r requirements.txt
```

### Langkah 3: Akses Web Lokal
Buka browser dan kunjungi:
```text
http://localhost/tabya/
```

### Langkah 4: Cara Meng-online-kan (Demo Sidang / Cloudflare Tunnel)
Cukup double-click file:
```text
ONLINE_KAN_TABYA.bat
```
Tunggu beberapa detik, sistem akan menampilkan link publik HTTPS resmi (contoh: `https://xxxx.trycloudflare.com`) yang bisa dibuka langsung dari HP atau laptop dosen penguji.

