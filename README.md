# Panel eSIM - PHP Admin System (v2)

Panel eSIM adalah sistem panel berbasis PHP yang memudahkan admin, reseller, maupun user untuk mengelola eSIM, memonitor penjualan, topup saldo, membuat order, serta terintegrasi dengan berbagai payment gateway (siap migrasi dari Midtrans ke Duitku/iPaymu/Xendit).

Fitur Utama

* Sistem login multi-role: Admin (siap ekspansi untuk user/reseller)
* Dashboard interaktif: Statistik order, saldo, dan aktivitas real-time
* Manajemen order eSIM: Buat, cek, update, suspend, dan topup otomatis via API
* Manajemen eSIM: Monitoring status, kuota, aktivasi, dan revoke eSIM langsung ke provider
* Integrasi Payment Gateway: Siap migrasi dari Midtrans ke Duitku/iPaymu/Xendit
* Webhook: Update otomatis status order/eSIM dari API eksternal
* QR Code Generator: Untuk provisioning eSIM ke device
* Panel Settings: Atur API key, margin, notifikasi, dsb. langsung via halaman admin
* AJAX Modular: Hampir semua aksi update tanpa reload, bikin panel ringan & modern
* Keamanan: Validasi, sanitasi, proteksi session, struktur .htaccess

Struktur File

admin/
dashboard.php         # Dashboard statistik
orders.php            # Manajemen order
esim.php              # Data & status eSIM
settings.php          # Pengaturan sistem
topup.php             # Pengelolaan topup user/reseller
login.php             # Login admin
logout.php            # Logout admin
index.php             # Halaman utama admin
assets/               # CSS & JS per modul admin

includes/
koneksi.php           # Koneksi database MySQL (PDO)
api.php               # Wrapper komunikasi API eSIM
functions.php         # Helper & utility
generate\_qr.php       # Generate QR code eSIM

config.php              # Konfigurasi utama (DB, API, dsb.)
detail.php              # Detail eSIM/order
topup.php               # Endpoint topup saldo
error.php               # Handler error
index.php               # Landing utama / redirect

assets/                 # Asset statis global (CSS, JS, images)
logs/                   # Folder log (pastikan writable)

NB:

* File legacy/payment gateway lama (midtrans.php, apimbl.php) sudah tidak dipakai
* File helper CSS/JS tidak aktif sudah dibersihkan
* Struktur makin modular & gampang di-maintain

Cara Instalasi

1. Clone repo ke web server lokal
   git clone [https://github.com/bocil69/panel.git](https://github.com/bocil69/panel.git)
2. Buat database MySQL, import SQL jika ada
3. Edit config.php:

   * Isi konfigurasi database, API key, endpoint payment gateway baru
4. Pastikan folder logs/ & admin/error\_log writable
5. Akses panel via browser
   [http://localhost/panel/admin/](http://localhost/panel/admin/) (login admin)
   Endpoint lain sesuai kebutuhan

Cara Kerja & Workflow

* Semua request ke API (order, query, topup) terpusat di includes/api.php
* Pembayaran: siap integrasi Duitku/iPaymu/Xendit (tidak lagi via midtrans.php)
* Status order & eSIM auto-update via webhook/API
* Admin bisa cek, ubah, suspend, topup eSIM langsung dari panel
* Role login siap dikembangkan (admin/user/reseller)
* QR code otomatis untuk aktivasi eSIM di device user

Changelog (v2)

* Modularisasi admin panel: Settings, topup, orders, dashboard, dsb. jadi file terpisah
* Migrasi payment gateway: Integrasi Midtrans dihapus, siap pakai Duitku/iPaymu/Xendit
* AJAX everywhere: Hampir semua proses update tanpa reload, lebih responsif
* Bersih-bersih file: File helper, payment, CSS/JS, dan log lama yang tidak dipakai sudah dihapus
* Security improved: Struktur .htaccess, validasi, sanitasi, dan proteksi session lebih kuat
* Assets rapi: CSS & JS dipisah sesuai fitur, assets tidak tercecer

Catatan Pengembangan

* File .htaccess WAJIB untuk proteksi path sensitif (admin, includes, dsb.)
* Input sudah divalidasi/sanitasi, lanjutkan hardening XSS & CSRF untuk production
* Struktur kini siap untuk penambahan role baru, notifikasi, dan fitur-fitur berikutnya

Kontribusi & License

Pull request, ide, dan saran sangat diterima!
Project ini open-source dan bebas dikembangkan sesuai kebutuhan.

Dibuat oleh bocil69 | Kontak via GitHub