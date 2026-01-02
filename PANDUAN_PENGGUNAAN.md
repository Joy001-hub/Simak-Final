# 📘 Panduan Penggunaan SIMAK
## Sistem Informasi Manajemen Kavling

---

## 📋 Daftar Isi

1. [Instalasi Aplikasi](#1-instalasi-aplikasi)
2. [Aktivasi Lisensi](#2-aktivasi-lisensi)
3. [Mengenal Tampilan Utama](#3-mengenal-tampilan-utama)
4. [Setup Awal](#4-setup-awal)
5. [Mengelola Penjualan](#5-mengelola-penjualan)
6. [Fitur Dashboard](#6-fitur-dashboard)
7. [Manajemen Data](#7-manajemen-data)

---

## 1. Instalasi Aplikasi

### Persyaratan Sistem
- **Sistem Operasi**: Windows 10 atau lebih baru
- **RAM**: Minimal 4GB
- **Penyimpanan**: 500MB ruang kosong

### Langkah Instalasi

1. **Download Installer**
   - Dapatkan file installer `SIMAK-Setup.exe` dari penjual resmi

2. **Jalankan Installer**
   - Klik kanan pada file installer
   - Pilih **"Run as Administrator"**
   - Ikuti wizard instalasi hingga selesai

3. **Buka Aplikasi**
   - Cari **"SIMAK"** di menu Start
   - Atau klik shortcut di Desktop

---

## 2. Aktivasi Lisensi

Sebelum menggunakan aplikasi, Anda harus mengaktifkan lisensi terlebih dahulu.

### Langkah Aktivasi

1. **Buka Aplikasi**
   - Saat pertama kali dibuka, Anda akan melihat halaman **Aktivasi Lisensi**

2. **Masukkan Kredensial**
   | Field | Keterangan |
   |-------|------------|
   | **Email** | Email yang terdaftar saat pembelian |
   | **Password** | Password akun Anda |
   | **License Key** | Kode lisensi (format: `LISENSI-XXXX-XXXX`) |

3. **Klik "Aktifkan Lisensi"**
   - Tunggu proses verifikasi
   - Jika berhasil, Anda akan diarahkan ke Dashboard

> ⚠️ **Catatan**: Satu lisensi hanya bisa diaktifkan di satu perangkat. Jika ingin pindah perangkat, gunakan tombol **"Reset Lisensi"**.

---

## 3. Mengenal Tampilan Utama

### Sidebar Menu

| Menu | Fungsi |
|------|--------|
| **Dashboard** | Ringkasan bisnis, grafik penjualan, KPI |
| **Penjualan** | Kelola transaksi penjualan kavling |
| **Projects** | Kelola proyek perumahan/kavling |
| **Kavling** | Kelola unit kavling yang tersedia |
| **Buyer** | Data pembeli/pelanggan |
| **Marketing** | Data tim sales/marketing |
| **Manajemen Data** | Backup, restore, dan demo data |
| **Profil Perusahaan** | Pengaturan informasi perusahaan |

---

## 4. Setup Awal

Sebelum memulai, lakukan setup data dasar berikut:

### 4.1 Setup Profil Perusahaan

1. Klik menu **Profil Perusahaan**
2. Isi informasi perusahaan:
   - Nama perusahaan
   - Alamat
   - Nomor telepon
   - Upload logo perusahaan
3. Klik **Simpan**

### 4.2 Tambah Project

1. Klik menu **Projects** → **Tambah Project**
2. Isi nama proyek dan detail lokasi
3. Klik **Simpan**

### 4.3 Tambah Kavling

1. Klik menu **Kavling** → **Tambah Kavling**
2. Pilih Project
3. Isi informasi:
   - Nomor Blok
   - Luas Tanah (m²)
   - Harga Dasar
4. Klik **Simpan**

### 4.4 Tambah Data Buyer

1. Klik menu **Buyer** → **Tambah Buyer**
2. Isi data pelanggan:
   - Nama lengkap
   - Nomor HP
   - Alamat
   - NIK (opsional)
3. Klik **Simpan**

### 4.5 Tambah Tim Marketing

1. Klik menu **Marketing** → **Tambah Marketing**
2. Isi nama sales/marketing
3. Klik **Simpan**

---

## 5. Mengelola Penjualan

### 5.1 Membuat Penjualan Baru

1. Klik menu **Penjualan** → **Tambah Penjualan**
2. Isi data penjualan:

| Field | Keterangan |
|-------|------------|
| **Kavling** | Pilih kavling yang tersedia |
| **Pelanggan** | Pilih pembeli |
| **Sales** | Pilih marketing (opsional) |
| **Metode Pembayaran** | Cash Keras / Cicilan |
| **Tanggal Penjualan** | Tanggal transaksi |

3. **Untuk Metode Cicilan**:
   - Isi jumlah DP (Down Payment)
   - Tentukan tenor (jumlah bulan cicilan)
   - Sistem akan menghitung angsuran per bulan

4. Klik **Simpan**

### 5.2 Melihat Detail Penjualan

1. Klik menu **Penjualan**
2. Klik salah satu baris penjualan untuk melihat detail
3. Di halaman detail, Anda dapat:
   - Melihat informasi transaksi lengkap
   - Melihat jadwal pembayaran
   - Mencatat pembayaran baru
   - Mencetak kwitansi

### 5.3 Mencatat Pembayaran

1. Buka detail penjualan
2. Scroll ke bagian **Pembayaran**
3. Klik **Tambah Pembayaran**
4. Isi:
   - Tanggal pembayaran
   - Jumlah bayar
   - Keterangan (opsional)
5. Klik **Simpan**

### 5.4 Batalkan Penjualan

1. Buka detail penjualan
2. Klik tombol **Batalkan Penjualan**
3. Pilih alasan pembatalan:
   - **Hapus** - Pembeli batal, kavling kembali tersedia
   - **Refund** - Pembeli batal dengan pengembalian dana
   - **Oper Kredit** - Pindah tangan ke pembeli baru

### 5.5 Filter & Cari Penjualan

- Gunakan filter **Metode Bayar** untuk menyaring Cash/Cicilan
- Gunakan filter **Status** untuk menyaring Aktif/Lunas/Batal
- Gunakan kotak pencarian untuk mencari berdasarkan nama buyer atau kavling

---

## 6. Fitur Dashboard

Dashboard menampilkan ringkasan bisnis secara real-time.

### 6.1 Kartu Ringkasan

| Kartu | Keterangan |
|-------|------------|
| **Penerimaan Periode Ini** | Total uang masuk pada periode filter |
| **Total DP Diterima** | Total down payment yang diterima |
| **Total Penjualan** | Jumlah unit terjual |
| **Total Penjualan Batal** | Jumlah penjualan yang dibatalkan |
| **Total Piutang (Global)** | Sisa tagihan dari semua penjualan aktif |
| **Nilai Persediaan Kavling** | Total nilai kavling yang masih tersedia |

### 6.2 Grafik

- **Tren Penjualan** - Grafik batang penjualan bulanan dengan proyeksi AI
- **Performa Tim Marketing** - Perbandingan performa sales
- **Penjualan per Proyek** - Distribusi penjualan tiap proyek
- **Nilai Persediaan per Proyek** - Komposisi stok kavling

### 6.3 Filter Periode

Klik tombol periode untuk menyaring data:
- **Minggu Ini**
- **Bulan Ini**
- **Tahun Ini**
- **Tahun Lalu**
- **Semua** (semua data)
- **Kustom** (pilih rentang tanggal)

### 6.4 Notifikasi Penagihan

Jika ada tagihan jatuh tempo, ikon lonceng akan menampilkan badge merah. Klik untuk melihat daftar pelanggan yang perlu ditagih.

---

## 7. Manajemen Data

### 7.1 Muat Data Demo

Untuk mencoba fitur aplikasi dengan data contoh:

1. Klik menu **Manajemen Data**
2. Klik **Muat Data Demo**
3. Konfirmasi dengan klik **Ya**

> ⚠️ **Peringatan**: Ini akan menghapus semua data yang ada!

### 7.2 Backup Data

1. Klik menu **Manajemen Data**
2. Klik **Backup Data**
3. File backup akan otomatis ter-download

### 7.3 Restore Data

1. Klik menu **Manajemen Data**
2. Klik **Restore Data**
3. Pilih file backup (`.json`)
4. Konfirmasi untuk memulihkan

### 7.4 Reset Data

Untuk menghapus semua data dan memulai dari awal:

1. Klik menu **Manajemen Data**
2. Klik **Reset Data**
3. Konfirmasi dengan klik **Ya**

> ⚠️ **Peringatan**: Tindakan ini tidak dapat dibatalkan!

---

## ❓ FAQ (Pertanyaan Umum)

### Q: Bagaimana jika lisensi tidak bisa diaktifkan?
**A**: Pastikan:
- Koneksi internet aktif
- Email dan password benar
- License key belum digunakan di perangkat lain

### Q: Bagaimana cara pindah perangkat?
**A**: Gunakan tombol **"Reset Lisensi"** di halaman aktivasi dengan kredensial yang sama.

### Q: Data saya hilang, apa yang harus dilakukan?
**A**: Jika Anda memiliki file backup, gunakan fitur **Restore Data**. Jika tidak, hubungi support.

---

## 📞 Dukungan

Jika mengalami kendala, hubungi kami
---

*Dokumen ini dibuat untuk SIMAK versi 1.0*
*Terakhir diperbarui: Desember 2024*
