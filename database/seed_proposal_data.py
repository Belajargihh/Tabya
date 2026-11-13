"""
Seed Exact Authentic Dataset from Proposal Skripsi Troce Insanadi Hermin Saleo
Bab 3, Tabel 3.2 (Terjemahan Bahasa) & Gambar 3.8 / 3.11
"""

import subprocess
import json
import os

items = [
    # Data Angka (13 data)
    ('number', 'One', 'Weter', 'Satu', 'Weh-ter', 'Tabel 3.2 Data Angka No. 1'),
    ('number', 'Two', 'Turu', 'Dua', 'Too-roo', 'Tabel 3.2 Data Angka No. 2'),
    ('number', 'Three', 'Kior', 'Tiga', 'Kee-or', 'Tabel 3.2 Data Angka No. 3'),
    ('number', 'Four', 'Fiak', 'Empat', 'Fee-ahk', 'Tabel 3.2 Data Angka No. 4'),
    ('number', 'Five', 'Rim', 'Lima', 'Reem', 'Tabel 3.2 Data Angka No. 5'),
    ('number', 'Six', 'Wonem', 'Enam', 'Woh-nem', 'Tabel 3.2 Data Angka No. 6'),
    ('number', 'Seven', 'Fik', 'Tujuh', 'Feek', 'Tabel 3.2 Data Angka No. 7'),
    ('number', 'Eight', 'War', 'Delapan', 'Wahr', 'Tabel 3.2 Data Angka No. 8'),
    ('number', 'Nine', 'Siu', 'Sembilan', 'See-oo', 'Tabel 3.2 Data Angka No. 9'),
    ('number', 'Ten', 'Tanfur', 'Sepuluh', 'Tahn-foor', 'Tabel 3.2 Data Angka No. 10'),
    ('number', 'Eleven', 'Tanfur weter mai weter', 'Sebelas', 'Tahn-foor weh-ter may weh-ter', 'Tabel 3.2 Data Angka No. 11'),
    ('number', 'Twelve', 'Tanfur weter mai turu', 'Dua belas', 'Tahn-foor weh-ter may too-roo', 'Tabel 3.2 Data Angka No. 12'),
    ('number', 'Thirteen', 'Tanfur weter mai kior', 'Tiga belas', 'Tahn-foor weh-ter may kee-or', 'Tabel 3.2 Data Angka No. 13'),

    # Data Salam (5 data)
    ('greeting', 'Hello', 'Tabya', 'Halo / Permisi / Selamat malam', 'Tah-byah', 'Tabel 3.2 Data Salam No. 1'),
    ('greeting', 'Excuse me', 'Tabya', 'Permisi', 'Tah-byah', 'Tabel 3.2 Data Salam No. 1'),
    ('greeting', 'Good morning', 'Arwo', 'Selamat pagi', 'Ahr-woh', 'Tabel 3.2 Data Salam No. 2'),
    ('greeting', 'Good afternoon', 'Arkoek', 'Selamat siang', 'Ahr-koh-ek', 'Tabel 3.2 Data Salam No. 3'),
    ('greeting', 'Good evening', 'Mandira', 'Selamat sore', 'Mahn-dee-rah', 'Tabel 3.2 Data Salam No. 4'),
    ('greeting', 'Good night', 'Rou', 'Selamat malam', 'Roh-oo', 'Tabel 3.2 Data Salam No. 5'),

    # Data Kalimat (9 data)
    ('sentence', 'My name is Soma', 'Aya ine ido Soma', 'Nama saya Soma', 'Ah-yah ee-neh ee-doh Soh-mah', 'Tabel 3.2 Data Kalimat No. 1'),
    ('sentence', 'What is your name', 'Hno hnon beja ido yo', 'Siapa nama kamu?', 'Hnoh hnon beh-jah ee-doh yoh', 'Tabel 3.2 Data Kalimat No. 2'),
    ('sentence', 'I live in Sawinggrai', 'Ya kain ro Tawinggrai', 'Saya tinggal di Sawinggrai', 'Yah kyn roh Tah-weeng-grah-ee', 'Tabel 3.2 Data Kalimat No. 3'),
    ('sentence', 'Where do you live', 'Kwain ro dio', 'Kamu tinggal di mana?', 'Kwy-een roh dee-oh', 'Tabel 3.2 Data Kalimat No. 4'),
    ('sentence', 'I am from Bali', 'Ya yaker Bali', 'Saya berasal dari Bali', 'Yah yah-ker Bah-lee', 'Tabel 3.2 Data Kalimat No. 5'),
    ('sentence', 'Where are you from', 'Waker dio', 'Kamu berasal dari mana?', 'Wah-ker dee-oh', 'Tabel 3.2 Data Kalimat No. 6'),
    ('sentence', 'Thank you', 'Injo', 'Terima kasih', 'Een-joh', 'Tabel 3.2 Data Kalimat No. 7'),
    ('sentence', 'Where are you from two people', 'Mu raker dio', 'Kalian berdua berasal dari mana?', 'Moo rah-ker dee-oh', 'Tabel 3.2 Data Kalimat No. 8'),
    ('sentence', 'Where are you from three people', 'Mko raker dio', 'Kalian berasal dari mana?', 'M-koh rah-ker dee-oh', 'Tabel 3.2 Data Kalimat No. 9'),

    # Data Kata (18 data)
    ('word', 'Bird', 'Man', 'Burung', 'Mahn', 'Tabel 3.2 Data Kata No. 1'),
    ('word', 'Birds', 'Mansi', 'Burung-burung', 'Mahn-see', 'Tabel 3.2 Data Kata No. 2'),
    ('word', 'Plate', 'Ben', 'Piring', 'Ben', 'Tabel 3.2 Data Kata No. 3'),
    ('word', 'Plates', 'Bensi', 'Piring-piring', 'Ben-see', 'Tabel 3.2 Data Kata No. 4'),
    ('word', 'Child', 'Mukunmawa', 'Anak', 'Moo-koon-mah-wah', 'Tabel 3.2 Data Kata No. 5'),
    ('word', 'Children', 'Mukanmawasi', 'Anak-anak', 'Moo-kahn-mah-wah-see', 'Tabel 3.2 Data Kata No. 6'),
    ('word', 'Fish', 'Ien', 'Ikan', 'Ee-en', 'Tabel 3.2 Data Kata No. 7'),
    ('word', 'Rice', 'Fat', 'Nasi', 'Faht', 'Tabel 3.2 Data Kata No. 8'),
    ('word', 'Spoon', 'Asok', 'Sendok', 'Ah-sok', 'Tabel 3.2 Data Kata No. 9'),
    ('word', 'Knife', 'Inoe', 'Pisau', 'Ee-noh-eh', 'Tabel 3.2 Data Kata No. 10'),
    ('word', 'Sea', 'Laut', 'Laut', 'Lah-oot', 'Tabel 3.2 Data Kata No. 11'),
    ('word', 'Boat', 'Wae', 'Perahu', 'Wah-eh', 'Tabel 3.2 Data Kata No. 12'),
    ('word', 'House', 'Rum', 'Rumah', 'Room', 'Tabel 3.2 Data Kata No. 13'),
    ('word', 'Dog', 'Nofan', 'Anjing', 'Noh-fahn', 'Tabel 3.2 Data Kata No. 14'),
    ('word', 'Cat', 'Mau', 'Kucing', 'Mah-oo', 'Tabel 3.2 Data Kata No. 15'),
    ('word', 'Sand', 'Yeyen', 'Pasir', 'Yeh-yen', 'Tabel 3.2 Data Kata No. 16'),
    ('word', 'Bridge', 'Abor', 'Jembatan', 'Ah-bor', 'Tabel 3.2 Data Kata No. 17'),
    ('word', 'Water', 'Wayer', 'Air', 'Wah-yer', 'Tabel 3.2 Data Kata No. 18'),

    # Mockup & Test Case items (Gambar 3.8, 3.11, Test Case 5 & 6)
    ('word', 'Eat', 'Konan', 'Makan', 'Koh-nahn', 'Sesuai Gambar 3.8, 3.11 & Test Case 5-6 Bab 3'),
    ('word', 'Welcome', 'Jou Suba', 'Selamat datang', 'Jow Soo-bah', 'Sesuai Gambar 3.8 Sketsa Kelola Data Bahasa')
]

# Generate SQL script
sql_lines = [
    "USE tabya_db;",
    "TRUNCATE TABLE datasets;"
]

for cat, eng, yen, ind, pron, note in items:
    eng_esc = eng.replace("'", "''")
    yen_esc = yen.replace("'", "''")
    ind_esc = ind.replace("'", "''")
    pron_esc = pron.replace("'", "''")
    note_esc = note.replace("'", "''")
    sql_lines.append(f"INSERT INTO datasets (category, english_text, yenbuba_text, indonesia_text, pronunciation, notes) VALUES ('{cat}', '{eng_esc}', '{yen_esc}', '{ind_esc}', '{pron_esc}', '{note_esc}');")

sql_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'seed_proposal.sql')
with open(sql_file, 'w', encoding='utf-8') as f:
    f.write("\n".join(sql_lines))

print(f"Generated {len(items)} items in {sql_file}")

