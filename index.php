<?php
// Secure index page with modern UI
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Get site settings from database
$siteName = 'eSIM Nusantara';
$siteTagline = 'Fast & Secure';
$siteDescription = 'Internet cepat tanpa ribet! Aktivasi instan dengan QR code. Coverage luas di seluruh Nusantara dan dunia. No more kartu fisik, no more drama! ✨';

try {
    include 'includes/koneksi.php';
    include 'includes/functions.php';
    
    // Get site settings using PDO
    $siteSettings = dbQuery("SELECT setting_key, setting_value FROM shop_settings WHERE setting_key IN ('shop_name', 'shop_tagline', 'shop_description')");
    
    foreach ($siteSettings as $setting) {
        if ($setting['setting_key'] === 'shop_name' && !empty($setting['setting_value'])) {
            $siteName = htmlspecialchars($setting['setting_value'], ENT_QUOTES, 'UTF-8');
        } elseif ($setting['setting_key'] === 'shop_tagline' && !empty($setting['setting_value'])) {
            $siteTagline = htmlspecialchars($setting['setting_value'], ENT_QUOTES, 'UTF-8');
        } elseif ($setting['setting_key'] === 'shop_description' && !empty($setting['setting_value'])) {
            $siteDescription = htmlspecialchars($setting['setting_value'], ENT_QUOTES, 'UTF-8');
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching site settings: " . $e->getMessage());
    // Continue with default values
}

// Get featured packages using PDO
$featuredPackages = [];
try {
    $packages = dbQuery("SELECT id, name, description, volume, duration, duration_unit, price_usd, selling_price 
                        FROM packages 
                        WHERE is_active = 1 
                        ORDER BY selling_price ASC 
                        LIMIT 3");
    
    foreach ($packages as $row) {
        // Sanitize data
        $row['name'] = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        $row['description'] = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
        
        // Format volume to GB
        $volumeBytes = (int)$row['volume'];
        $volumeGB = round($volumeBytes / (1024 * 1024 * 1024), 1);
        $row['volume_formatted'] = $volumeGB . ' GB';
        
        // Format duration
        $duration = (int)$row['duration'];
        $durationUnit = strtolower($row['duration_unit']);
        if ($durationUnit === 'day') {
            $row['duration_formatted'] = $duration > 1 ? $duration . ' Hari' : '1 Hari';
        } elseif ($durationUnit === 'month') {
            $row['duration_formatted'] = $duration > 1 ? $duration . ' Bulan' : '1 Bulan';
        } else {
            $row['duration_formatted'] = $duration . ' ' . ucfirst($durationUnit);
        }
        
        // Format price
        $row['price_formatted'] = 'Rp ' . number_format($row['selling_price'], 0, ',', '.');
        
        $featuredPackages[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Error fetching featured packages: " . $e->getMessage());
    // Continue with empty array
}

// Get contact information using PDO
$whatsappNumber = '6281325525646';
$shopeeUrl = 'https://shopee.co.id/hanisyaa.syore';

try {
    $contactSettings = dbQuery("SELECT setting_key, setting_value FROM shop_settings WHERE setting_key IN ('whatsapp_number', 'shopee_url')");
    
    foreach ($contactSettings as $setting) {
        if ($setting['setting_key'] === 'whatsapp_number' && !empty($setting['setting_value'])) {
            $whatsappNumber = preg_replace('/[^0-9]/', '', $setting['setting_value']);
        } elseif ($setting['setting_key'] === 'shopee_url' && !empty($setting['setting_value'])) {
            $shopeeUrl = filter_var($setting['setting_value'], FILTER_VALIDATE_URL) ? 
                         $setting['setting_value'] : 'https://shopee.co.id/hanisyaa.syore';
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching contact information: " . $e->getMessage());
    // Continue with default values
}

// Sanitize all variables for output
$siteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
$siteTagline = htmlspecialchars($siteTagline, ENT_QUOTES, 'UTF-8');
$siteDescription = htmlspecialchars($siteDescription, ENT_QUOTES, 'UTF-8');
$whatsappNumber = htmlspecialchars($whatsappNumber, ENT_QUOTES, 'UTF-8');
$shopeeUrl = htmlspecialchars($shopeeUrl, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteName ?> 🇮🇩 - <?= $siteTagline ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="<?= $siteDescription ?>">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <div class="logo">🇮🇩 <?= $siteName ?></div>
                <ul class="nav-menu">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#info">Info</a></li>
                    <li><a href="#plans">Paket</a></li>
                    <li><a href="#contact">Kontak</a></li>
                </ul>
                <a href="<?= $shopeeUrl ?>" class="cta-btn">Pesan Now! 🔥</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">🔥 Trending di Indonesia</div>
                <h1><?= $siteName ?> 🇮🇩</h1>
                <div class="tagline"><?= $siteTagline ?></div>
                <p><?= $siteDescription ?></p>
                <div class="hero-buttons">
                    <a href="<?= $shopeeUrl ?>" class="btn-primary">🛒 Beli Sekarang</a>
                    <a href="https://wa.me/<?= $whatsappNumber ?>" class="btn-secondary">💬 Chat Dulu</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Section -->
    <section id="info" class="info-section">
        <div class="container">
            <div class="info-content">
                <h2 class="section-title">Detail Layanan 📋</h2>
                <p class="section-subtitle">Semua yang perlu kamu tahu tentang eSIM kami</p>
                
                <div class="info-grid">
                    <div class="info-card">
                        <h3>📡 Jaringan</h3>
                        <p><strong>✅ Telkomsel LTE/5G</strong><br>
                        Jaringan terkuat dan tercepat di Indonesia dengan coverage terluas</p>
                    </div>
                    
                    <div class="info-card">
                        <h3>📱 Jenis Paket</h3>
                        <p><strong>Data Internet Saja</strong><br>
                        <span style="color: #ffd700;">❌ TIDAK BISA TELEPON & SMS</span><br>
                        Fokus untuk kebutuhan internet kamu</p>
                    </div>
                    
                    <div class="info-card">
                        <h3>🔒 Verifikasi (eKYC)</h3>
                        <p><strong>❌ Tidak Diperlukan</strong><br>
                        Langsung pakai tanpa ribet verifikasi identitas</p>
                    </div>
                    
                    <div class="info-card">
                        <h3>🔄 Isi Ulang</h3>
                        <p><strong>✅ BISA</strong><br>
                        Paket habis? Tinggal isi ulang lagi, gampang!</p>
                    </div>
                </div>

                <div class="highlight-box">
                    <h3>⏰ Masa Berlaku</h3>
                    <p><strong>Dimulai saat eSIM terhubung ke jaringan di area cakupan.</strong><br>
                    Jika diaktifkan di luar cakupan, eSIM akan aktif saat tiba di lokasi yang mendukung.<br>
                    <em>*eSIM berlaku sesuai judul paket, bukan berarti semua wilayah tercover</em></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Plans -->
    <section id="plans" class="plans">
        <div class="container">
            <div class="plans-content">
                <h2 class="section-title">Pilih Paket Favoritmu! 🎯</h2>
                <div class="plans-grid">
                    <?php if (!empty($featuredPackages)): ?>
                        <?php foreach ($featuredPackages as $index => $package): 
                            $isPopular = $index === 1; // Make the middle package popular
                        ?>
                            <div class="plan-card <?= $isPopular ? 'popular' : '' ?>">
                                <?php if ($isPopular): ?>
                                    <div class="popular-badge">🔥 Paling Laris</div>
                                <?php endif; ?>
                                <div class="plan-emoji"><?= $isPopular ? '⭐' : ($index === 0 ? '🏃‍♂️' : '👑') ?></div>
                                <div class="plan-price"><?= $package['price_formatted'] ?></div>
                                <div class="plan-period"><?= $package['volume_formatted'] ?> / <?= $package['duration_formatted'] ?></div>
                                <ul class="plan-features">
                                    <li>✅ <?= $package['volume_formatted'] ?> Data Berkecepatan Tinggi</li>
                                    <li>✅ Berlaku <?= $package['duration_formatted'] ?></li>
                                    <li>✅ Telkomsel LTE/5G</li>
                                    <li>✅ Aktivasi Instan</li>
                                    <li>✅ Bisa Isi Ulang</li>
                                    <?php if ($isPopular || $index === 2): ?>
                                        <li>✅ Support 24/7</li>
                                    <?php endif; ?>
                                    <?php if ($index === 2): ?>
                                        <li>✅ Priority Network</li>
                                    <?php endif; ?>
                                </ul>
                                <a href="<?= $shopeeUrl ?>" class="plan-btn">Pilih Paket</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default packages if none found in database -->
                        <div class="plan-card">
                            <div class="plan-emoji">🏃‍♂️</div>
                            <div class="plan-price">Rp 25.000</div>
                            <div class="plan-period">1GB / 7 Hari</div>
                            <ul class="plan-features">
                                <li>✅ 1GB Data Berkecepatan Tinggi</li>
                                <li>✅ Berlaku 7 Hari</li>
                                <li>✅ Telkomsel LTE/5G</li>
                                <li>✅ Aktivasi Instan</li>
                                <li>✅ Bisa Isi Ulang</li>
                            </ul>
                            <a href="<?= $shopeeUrl ?>" class="plan-btn">Pilih Paket</a>
                        </div>
                        
                        <div class="plan-card popular">
                            <div class="popular-badge">🔥 Paling Laris</div>
                            <div class="plan-emoji">⭐</div>
                            <div class="plan-price">Rp 75.000</div>
                            <div class="plan-period">5GB / 30 Hari</div>
                            <ul class="plan-features">
                                <li>✅ 5GB Data Berkecepatan Tinggi</li>
                                <li>✅ Berlaku 30 Hari</li>
                                <li>✅ Telkomsel LTE/5G</li>
                                <li>✅ Aktivasi Instan</li>
                                <li>✅ Bisa Isi Ulang</li>
                                <li>✅ Support 24/7</li>
                            </ul>
                            <a href="<?= $shopeeUrl ?>" class="plan-btn">Pilih Paket</a>
                        </div>
                        
                        <div class="plan-card">
                            <div class="plan-emoji">👑</div>
                            <div class="plan-price">Rp 150.000</div>
                            <div class="plan-period">10GB / 30 Hari</div>
                            <ul class="plan-features">
                                <li>✅ 10GB Data Berkecepatan Tinggi</li>
                                <li>✅ Berlaku 30 Hari</li>
                                <li>✅ Telkomsel LTE/5G</li>
                                <li>✅ Aktivasi Instan</li>
                                <li>✅ Bisa Isi Ulang</li>
                                <li>✅ Support 24/7</li>
                                <li>✅ Priority Network</li>
                            </ul>
                            <a href="<?= $shopeeUrl ?>" class="plan-btn">Pilih Paket</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-content">
                <h2>Ready to Go Digital? 🚀</h2>
                <p>Dapatkan eSIM kamu sekarang dan nikmati internet cepat & aman di mana aja! No drama, no ribet! 💯</p>
                <div class="contact-buttons">
                    <a href="<?= $shopeeUrl ?>" class="shopee-btn">🛒 Order di Shopee</a>
                    <a href="https://wa.me/<?= $whatsappNumber ?>" class="whatsapp-btn">💬 Chat WhatsApp</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?= $siteName ?> 🇮🇩 - <?= $siteTagline ?> Connection for Gen Z Indonesia</p>
        </div>
    </footer>

    <script src="assets/js/index.js"></script>
</body>
</html>