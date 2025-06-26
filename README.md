# Panel eSIM - PHP Admin System

Panel eSIM adalah sistem panel berbasis PHP yang memungkinkan admin/reseller/user untuk mengelola eSIM, memonitor penjualan, topup saldo, membuat order, serta integrasi penuh dengan API eSIM dan pembayaran otomatis via Midtrans.

## Fitur Utama

- Sistem login dengan role admin (siap ditambah user/reseller)
- Dashboard admin: ringkasan statistik order, saldo, aktivitas
- Manajemen order eSIM: buat, ubah, cek status, proses otomatis via API
- Manajemen eSIM: cek status, topup, suspend, revoke, detail profile
- Integrasi Midtrans untuk pembayaran/topup otomatis
- Webhook: update otomatis status order/eSIM dari API
- Cek status order/eSIM real-time langsung ke API
- Fitur generate QR code untuk provisioning eSIM
- Struktur modular dan mudah dikembangkan
- Keamanan dasar: validasi, sanitasi, proteksi session, .htaccess

## Struktur File Utama

admin/
  dashboard.php         # Dashboard admin, statistik, ringkasan
  orders.php            # Manajemen order eSIM
  esim.php              # Manajemen detail eSIM
  login.php             # Login admin
  logout.php            # Logout admin
  index.php             # Landing page admin
  assets/               # Asset statis admin (css, js, images)

includes/
  koneksi.php           # Koneksi database MySQL
  api.php               # Wrapper komunikasi API eSIM
  midtrans.php          # Wrapper pembayaran Midtrans
  functions.php         # Helper, utility
  generate_qr.php       # Generate QR code untuk eSIM

check_status.php        # Endpoint cek status eSIM/order
config.php              # Konfigurasi utama (API, DB, dsb)
error.php               # Error handling
topup.php               # Proses topup saldo eSIM
webhook.php             # Handler webhook otomatis dari API eSIM
detail.php              # Detail profile eSIM/order
index.php               # Landing utama / redirect

logs/                   # Folder log
assets/                 # Asset statis global
README.md               # Dokumentasi project

## Cara Instalasi

1. Clone repo ke web server lokal
   git clone https://github.com/bocil69/panel.git

2. Buat database MySQL, import file SQL jika ada

3. Edit config.php:
   - Isi konfigurasi database, API key, endpoint

4. Pastikan folder logs/ dan admin/error_log writable

5. Akses panel via browser:
   http://localhost/panel/admin/ (login admin)
   Endpoint lain bisa diakses sesuai kebutuhan

## Cara Kerja & Workflow

- Semua request ke API (create order, query, topup) lewat includes/api.php
- Pembayaran otomatis via includes/midtrans.php
- Webhook update status eSIM/order ke database tanpa manual refresh
- Admin bisa cek, ubah, suspend, topup eSIM lewat panel
- Role login bisa dikembangkan untuk user/reseller
- QR code otomatis untuk aktivasi eSIM di device

## Catatan Pengembangan

- Gunakan file .htaccess untuk keamanan path sensitif
- Input sudah divalidasi & disanitasi, tapi tetap perkuat XSS & CSRF di production
- Struktur modular, gampang upgrade (misal: tambah role user/reseller, notifikasi dsb)

## Kontribusi & License

Pull request, ide, dan saran sangat diterima.  
Project ini open-source dan bisa dipakai/diubah sesuai kebutuhan.

---

Dibuat oleh bocil69 | Kontak via GitHub  
