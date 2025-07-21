# eSIM Portal - MVC Structure

## 📁 Struktur Folder

```
project/
├── config.php              # Konfigurasi utama
├── index.php               # Entry point (redirect ke public/)
├── .htaccess               # Redirect ke public folder
├── src/                    # Source code (Model & includes)
│   └── includes/
│       ├── koneksi.php     # Database connection
│       ├── functions.php   # Helper functions
│       ├── api.php         # API integration
│       ├── order_functions.php
│       ├── navigation.php  # Navigation component
│       └── footer.php      # Footer component
└── public/                 # Public folder (View & Controller)
    ├── .htaccess          # URL routing
    ├── index.php          # Main controller
    ├── about.php          # About page
    ├── contact.php        # Contact page
    ├── detail.php         # eSIM detail page
    ├── topup.php          # Topup page
    ├── admin/             # Admin panel
    └── assets/            # CSS, JS, Images
        ├── css/
        ├── js/
        └── images/
```

## 🚀 Cara Menjalankan

### 1. **Setup Web Server**
- Pastikan document root mengarah ke folder project (bukan public/)
- Apache/Nginx harus bisa akses .htaccess

### 2. **URL Structure**
```
https://yourdomain.com/           → public/index.php
https://yourdomain.com/about      → public/about.php  
https://yourdomain.com/contact    → public/contact.php
https://yourdomain.com/admin/     → public/admin/
```

### 3. **Database Setup**
- Import database schema
- Update config.php dengan database credentials
- Pastikan semua tabel sudah ada

### 4. **File Permissions**
```bash
chmod 755 public/
chmod 644 public/*.php
chmod 755 public/assets/
chmod 644 config.php
```

## 🔧 Troubleshooting

### Jika halaman tidak load:
1. Cek .htaccess berfungsi: `apache2ctl -M | grep rewrite`
2. Cek error log: `tail -f /var/log/apache2/error.log`
3. Cek BASE_URL di config.php sudah benar

### Jika CSS/JS tidak load:
1. Cek path di browser developer tools
2. Pastikan file ada di public/assets/
3. Cek file permissions

### Jika database error:
1. Cek koneksi di config.php
2. Pastikan database dan tabel sudah ada
3. Cek user permissions

## 📝 Notes

- **MVC Pattern**: Model (src/), View (public/), Controller (public/index.php)
- **Security**: Semua file sensitif di luar public folder
- **Routing**: .htaccess handle URL routing
- **Assets**: CSS/JS/Images di public/assets/