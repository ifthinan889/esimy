# Panel eSIM - PHP Admin System 🚀

Panel eSIM adalah sistem panel berbasis PHP untuk mengelola eSIM, order, topup saldo, serta pembayaran otomatis via berbagai payment gateway.

## ✨ Fitur Unggulan
- Login multi-role: admin, user, reseller # baru role admin
- Dashboard statistik penjualan & saldo
- Order eSIM otomatis & cek status real-time ke API
- Panel Settings (API key, margin, notifikasi)
- Integrasi Payment Gateway (Duitku/iPaymu/Xendit) belum terealisasikan
- QR code generator buat provisioning eSIM
- AJAX modular: update data tanpa reload
- Keamanan input & session, struktur .htaccess
- Menggunakan UnofficialAPI MitraBukalapak

## 📂 Struktur File (Singkat)
- `admin/` — semua file admin panel
- `includes/` — API, koneksi, helper, QR
- `assets/` — CSS & JS
- `logs/` — log aktivitas/error

## 🚀 Instalasi
1. Clone repo ini
2. Import database, edit `config.php`
3. Pastikan folder logs/ writable
4. Login ke panel: `http://localhost/panel/admin/`

## 📸 Screenshot
![Preview Panel Admin](https://your-image-url)

## 📝 Changelog
- Bersih-bersih file tidak terpakai
- Integrasi pembayaran bisa ganti-ganti gateway
- Struktur makin modular & scalable

## 🤝 Kontribusi & Lisensi
Open-source, bebas dimodifikasi. Pull request & saran welcome!
