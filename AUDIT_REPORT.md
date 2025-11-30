# Laporan Audit & Perbaikan Aplikasi CSSD

Dokumen ini merinci temuan dari audit kode komprehensif pada aplikasi distribusi instrumen medis CSSD. Audit ini mencakup backend (Laravel), aplikasi mobile (Ionic/Vue), dan frontend web (Vue).

## 1️⃣ Laporan Audit Rinci (Checklist Temuan & Perbaikan)

### A. Struktur Database & Eloquent

-   [x] **Relasi Model Salah:** Ditemukan relasi `trayInstruments` dan `trays` pada model `Instrument` yang saling mereferensikan diri sendiri secara tidak benar.
    -   **Perbaikan:** Relasi Eloquent pada `app/Models/Instrument.php` telah diperbaiki untuk secara akurat merepresentasikan hubungan antara baki (*tray*) dan instrumen di dalamnya.
-   [x] **Struktur Stok Tidak Efisien:** Tabel `instrument_unit_status` awalnya menggunakan `unit_id` yang bisa `null` untuk membedakan stok CSSD dan stok unit. Ini membuat *query* menjadi rumit dan logika bisnis rawan *error*.
    -   **Perbaikan:** Membuat migrasi baru (`...consolidate_instrument_status_schema.php`) untuk menambahkan kolom `location` ENUM('cssd', 'unit'). Ini menyederhanakan logika secara drastis dan membuat skema lebih eksplisit.
-   [x] **Kolom Hilang:** Tidak ada cara standar untuk mengidentifikasi apakah sebuah `instrument` adalah sebuah baki (*tray*) yang dapat berisi instrumen lain.
    -   **Perbaikan:** Membuat migrasi baru (`...add_is_tray_to_instruments_table.php`) untuk menambahkan kolom boolean `is_tray` ke tabel `instruments`.
-   [x] **Alur Stok Tidak Terdefinisi:** Skema awal tidak mendukung alur siklus hidup instrumen yang benar (`steril -> in_use -> kotor`).
    -   **Perbaikan:** Tabel `instrument_unit_status` sekarang memiliki kolom `stock_steril`, `stock_in_use`, dan `stock_kotor` yang dikelola secara ketat oleh `TransactionService` yang baru.

### B. API Backend & Logika Bisnis

-   [x] **Logika `TransactionService` Cacat:** Logika inti dalam `TransactionService` salah menangani pembaruan stok, terutama pada saat validasi dan pembatalan transaksi, yang dapat menyebabkan data tidak konsisten.
    -   **Perbaikan:** Menulis ulang `app/Services/TransactionService.php` secara total. Logika sekarang dibagi menjadi metode-metode yang jelas (`createSterilDistribution`, `createKotorPickup`, `createCssdReturn`) yang secara atomik mengelola perubahan stok sesuai dengan siklus hidup instrumen.
-   [x] **Endpoint API Tidak Konsisten:** Nama *endpoint* lama seperti `/create-steril` tidak lagi mencerminkan alur kerja multi-langkah yang diperlukan.
    -   **Perbaikan:** Memperbarui `routes/api.php` dengan *endpoint* yang lebih deskriptif seperti `/create-steril-distribution` untuk mencerminkan logika bisnis yang telah diperbarui.
-   [x] **Kurangnya Validasi Request:** `TransactionController` tidak memiliki validasi yang ketat pada data yang masuk.
    -   **Perbaikan:** Menerapkan `Validator` Laravel di `app/Http/Controllers/TransactionController.php` untuk semua *endpoint* yang menerima *input* pengguna, memastikan integritas data.
-   [x] **Parsing QR Tidak Lengkap:** `QRController` perlu diverifikasi untuk menangani semua format QR yang diperlukan.
    -   **Perbaikan:** Memperbarui `app/Http/Controllers/QRController.php` untuk secara andal mem-parsing format `UNIT:{uuid}` dan `TRANS:{uuid}`.

### C. Aplikasi Mobile (Ionic/Vue)

-   [x] **Implementasi Pemindai QR Buruk:** `QRScannerMobile.vue` memiliki implementasi ganda yang membingungkan (ZXing dan MLKit), tidak secara eksplisit memilih kamera belakang, dan rawan *error*.
    -   **Perbaikan:** Menulis ulang `mobile/src/components/QRScannerMobile.vue` dari awal, menggunakan Capacitor BarcodeScanner (MLKit) secara eksklusif, yang lebih cepat, lebih andal, dan secara *default* menggunakan kamera yang tepat. Logika izin kamera juga telah ditambahkan.
-   [x] **Dukungan Offline Tidak Lengkap:** Layanan `api.ts` hanya memiliki dukungan *offline* parsial untuk beberapa permintaan `POST` dan tidak memiliki antrian yang kuat.
    -   **Perbaikan:** Merekayasa ulang `mobile/src/services/api.ts` untuk mengimplementasikan antrian *offline* yang kuat. Semua permintaan yang gagal karena masalah jaringan sekarang disimpan dan disinkronkan secara otomatis saat koneksi kembali, mencegah kehilangan data.
-   [x] **Integrasi Halaman:** Halaman `ScanUnit.vue` perlu disesuaikan dengan komponen pemindai dan *endpoint* API yang baru.
    -   **Perbaikan:** Memperbarui `mobile/src/views/ScanUnit.vue` untuk berintegrasi dengan mulus dengan `QRScannerMobile` yang baru dan menangani alur pemindaian dengan lebih baik.

### D. Frontend Web (Vue)

-   [x] **Data Dasbor Tidak Sinkron:** `Dashboard.vue` tidak akan menampilkan data dengan benar karena perubahan besar pada struktur data stok di *backend*.
    -   **Perbaikan:** Memperbarui `frontend-web/src/views/Dashboard.vue` untuk mengambil dan menampilkan statistik baru, termasuk pemisahan yang jelas antara inventaris CSSD dan Unit.
-   [x] **Fungsionalitas CRUD:** Halaman manajemen unit dan instrumen perlu dipastikan berfungsi setelah perubahan *backend*.
    -   **Perbaikan:** Merombak `frontend-web/src/views/Units.vue` untuk memastikan semua operasi CRUD, pembuatan QR *code*, dan penanganan status berfungsi dengan benar sesuai dengan API yang baru.

### E. Keamanan & Praktik Terbaik

-   [x] **Tidak Ada Kontrol Akses Berbasis Peran:** Kerentanan keamanan kritis di mana setiap pengguna yang diautentikasi dapat mencoba mengakses *endpoint* apa pun.
    -   **Perbaikan:** Membuat *middleware* baru `app/Http/Middleware/CheckRole.php` dan menerapkannya pada `routes/api.php` untuk mengamankan *endpoint* berdasarkan peran pengguna (admin, validator, operator).
-   [x] **Kurangnya Rate Limiting Spesifik:** Batasan permintaan API bersifat umum dan tidak cukup untuk melindungi *endpoint* yang sensitif seperti pemindaian atau pembuatan transaksi.
    -   **Perbaikan:** Mengonfigurasi *rate limiter* khusus (`scan`, `transaction`) di `app/Providers/RouteServiceProvider.php` untuk mencegah penyalahgunaan.

## 2️⃣ Rekomendasi Perbaikan Tambahan

-   **Backend:** Untuk proyek yang lebih besar, pertimbangkan untuk menggunakan Laravel's Form Requests untuk memisahkan logika validasi dari *controller*. Implementasikan *event* dan *listener* untuk tugas-tugas *post-transaction* (misalnya, mengirim notifikasi) untuk memisahkan tanggung jawab.
-   **Frontend/Mobile:** Gunakan *library* manajemen status terpusat (seperti Pinia) untuk mengelola status global (misalnya, pengguna yang masuk, status koneksi) agar tidak perlu meneruskannya melalui *props* atau mengambilnya berulang kali.
-   **Mobile:** Sediakan umpan balik visual yang lebih jelas kepada pengguna saat aplikasi berada dalam mode *offline* dan saat sinkronisasi data sedang berlangsung di latar belakang.

---

Audit ini telah mengidentifikasi dan memperbaiki masalah-masalah kritis di seluruh tumpukan aplikasi. Dengan perbaikan ini, proyek sekarang memiliki fondasi yang jauh lebih stabil, aman, dan andal, serta siap untuk kompilasi dan penggunaan di dunia nyata.
