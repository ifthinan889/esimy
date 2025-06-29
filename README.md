# Panel eSIM - PHP Admin System

Panel eSIM is a PHP-based admin system for managing eSIMs, orders, balance top-ups, and automated payments via various payment gateways. 🚀

## index.php

Main entry point for the eSIM Store web application.

### Features
- Sets up security headers and session management.
- Loads configuration and required include files (database connection, utility functions, API).
- Handles AJAX requests for:
  - Fetching available countries, regions, and global package groupings.
  - Fetching packages by region or country.
- Provides helper functions for parsing and grouping package data.
- Renders the main HTML page for the eSIM Store, including:
  - Hero section, search and filter UI, results display, features, contact, and footer.
  - Modal dialogs for country selection, ordering, and order success.
  - Passes exchange rate and CSRF token to the frontend.

### AJAX Endpoints
- `action=get_countries`: Returns grouped country, region, and global package data.
- `action=get_packages_by_region`: Returns packages for a given region.
- `action=get_packages_by_country`: Returns packages for a given country.

### Helper Functions
- `handleGetCountries($pdo, $kurs)`: Groups packages by country, region, and global.
- `handleGetPackagesByRegion($pdo, $kurs)`: Fetches packages by region name.
- `handleGetPackagesByCountry($pdo, $kurs)`: Fetches packages by country name.
- `extractRegionPrefix($packageName)`: Extracts region prefix from package name.
- `extractGlobalPrefix($packageName)`: Extracts global prefix from package name.
- `parseCountriesFromLocation($locationName)`: Parses country names from location string.

### Security
- Uses CSRF tokens for order forms.
- Sets security-related HTTP headers.
- Sanitizes user input for AJAX endpoints.

### Frontend
- Modern, mobile-friendly UI with search, filters, and modals.
- Uses JavaScript for dynamic package loading and UI interactions.

### Dependencies
- PHP PDO for database access.
- FontAwesome, Google Fonts, and custom CSS/JS assets.

## ✨ Fitur Unggulan
- Login multi-role: admin, user, reseller # baru role admin
- Dashboard statistik penjualan & saldo
- Order eSIM otomatis & cek status real-time ke API
- Panel Settings (API key, margin, notifikasi)
- Integrasi Payment Gateway (Duitku/iPaymu/Xendit)
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

## 🗺️ Planned Features
- Integrasi Payment Gateway (Duitku/iPaymu/Xendit) belum terealisasikan

## 🤝 Kontribusi & Lisensi
Open-source, bebas dimodifikasi. Pull request & saran welcome!
