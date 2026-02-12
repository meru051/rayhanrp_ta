BOT TELEGRAM UNTUK PEMBERITAHUAN JADWAL DAN PENGINGAT TUGAS

DISUSUN OLEH:
RAYHAN RIZKY PRATAMA
XI RPL B
SMK NEGERI 2 CIMAHI 
2026

1. Judul dan Penjelasan Judul Tugas Akhir
Judul:
Bot Telegram Untuk Pemberitahuan Jadwal Dan Pengingat Tugas
Penjelasan:
sebuah sistem berbasis bot Telegram yang berfungsi sebagai mekanisme pemberitahuan dan pengingat untuk jadwal kegiatan dan pekerjaan/tugas. Sistem ditujukan untuk lingkungan pendidikan atau organisasi kecil agar anggota selalu mendapat notifikasi tentang jadwal, tenggat tugas, serta riwayat pengingat.
Tujuan umum:
•	Mengurangi kelewatan menghadiri kegiatan dan terlambat mengumpulkan tugas.
•	Mengotomasi pengiriman notifikasi via Telegram sesuai jadwal dan aturan pengingat.
Manfaat:
•	Mempermudah admin/guru/pengurus untuk menyebarkan jadwal.
•	Memberi pengguna pengingat otomatis dan arsip notifikasi.

2. Fungsi dan Batasan Masalah
Fungsi Sistem:
1. Manajemen pengguna: pengguna masuk/menggunakan bot berdasarkan NIS yang telah ada.
2. Manajemen jadwal: membuat, mengubah, menghapus jadwal acara/kelas/meeting.
3. Manajemen tugas: membuat tugas/PR dengan tanggal tenggat, penugasan ke pengguna atau grup.
4. Pengingat otomatis: mengirimkan notifikasi pengingat pada waktu yang ditentukan (mis. 24 jam, 1 jam sebelum, atau custom).
5. Notifikasi manual: admin/admin-guru dapat mengirim pengumuman manual ke satu atau beberapa pengguna/grup.
6. Riwayat notifikasi: menyimpan log notifikasi yang telah dikirim.
7. Preferensi pengguna: pengguna dapat mengatur preferensi pengingat (on/off, waktu pengingat, snooze).

Batasan Masalah:
1. Sistem hanya menggunakan Telegram sebagai saluran notifikasi (tidak mengirim email atau SMS).
2. Tidak menangani pesan multimedia kompleks selain teks dan tombol interaktif dasar.
3. Tidak mengimplementasikan enkripsi end-to-end khusus di luar keamanan Telegram.
4. Server Lokal,  Di-host di server rumahan dengan spesifikasi terbatas.

3. Desain ERD (Entity Relationship Diagram)

Entitas dan Atribut:
1. akun – id_akun, username, password, role, created_at
2. siswa – id_siswa, akun_id, nis, nama, kelas, jurusan, email
3. guru – id_guru, akun_id, nip, nama, email
4. konseling – id_konseling, siswa_id, guru_id, jenis, tanggal, jam_mulai, jam_selesai, status, alasan, hasil, created_at
5. chat – id_chat, pengirim_akun_id, penerima_akun_id, pesan, waktu
6. log_aktivitas – id_log, akun_id, aktivitas, waktu



Diagram Relasi:
 






4. Relasi Antar Tabel	
1.	user — grup: satu pengguna (mis. admin) bisa membuat banyak grup; setiap grup punya dibuat_oleh yang merujuk ke pengguna pembuat.
2.	grup — jadwal: satu grup dapat memiliki banyak jadwal; tiap jadwal milik satu grup.
3.	jadwal — tugas: sebuah jadwal dapat menjadi konteks untuk beberapa tugas (opsional). Tugas juga bisa dibuat tanpa terkait jadwal.
4.	users — tasks (dibuat_untuk): tugas dapat ditugaskan ke satu pengguna. Jika ingin mendukung multi-penugasan, tambahkan tabel junction task_assignments(task_id, user_id).
5.	user — notifikasi: setiap notifikasi yang dikirim dicatat per pengguna, sehingga dapat ditelusuri riwayatnya.
6.	jadwal/tugas — notifikasi: notifikasi dapat berasal dari jadwal atau tugas; oleh karena itu kolom foreign key di notifications boleh NULL tergantung sumber notifikasi.
7.	template_pengingat — notifikasi: template yang dipakai untuk membentuk pesan notifikasi.
8.	users— prefrensi_user: menyimpan preferensi pengingat per pengguna (mis. waktu default pengingat, snooze, timezone).






 
5. Fitur dan Hak Akses 
Fitur	Deskripsi singkat	Admin	Guru/Koordinator	Siswa/Anggota
Manajemen Pengguna	Tambah/hapus/update data pengguna, atur role	✓	✓ (terbatas)	✗
Buat/ubah/hapus Grup	Membuat grup kelas/kelompok	✓	✓	✗
Buat/ubah/hapus Jadwal	Menetapkan jadwal kelas/meeting	✓	✓	✗
Buat/ubah/hapus Tugas	Menugaskan tugas, mengatur due date	✓	✓	✗ (kecuali submit)
Lihat Jadwal dan Tugas	Melihat jadwal dan tugas yang relevan	✓	✓	✓
Kirim Pengumuman Manual	Mengirim pesan broadcast ke grup atau pengguna tertentu	✓	✓	✗
Pengingat Otomatis	Bot mengirim pengingat otomatis sesuai pengaturan (24h/1h/custom)	✓	✓	✓
Preferensi Pengingat (user-level)	Pengguna mengatur apakah akan menerima pengingat dan intervalnya	✓	✓	✓
Riwayat Notifikasi	Melihat log notifikasi yang pernah dikirim	✓	✓	✗ (terbatas)
Snooze/Delay Pengingat	Menunda pengingat untuk beberapa menit	✓	✓	✓
Integrasi CSV Import (Users/Jadwal)	Import data dari CSV untuk mass upload	✓	✓ (terbatas)	✗

Keterangan: ✓ = hak akses diberikan, ✗ = tidak ada akses. 'Terbatas' berarti akses dengan pembatasan fungsi.