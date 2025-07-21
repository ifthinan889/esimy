<?php
error_reporting(0);
ini_set('display_errors', '0');
// Secure admin dashboard
define('ALLOWED_ACCESS', true);
// KODE YANG BENAR
require_once __DIR__ . '/../../config.php';

// Include required files
try {
    require_once __DIR__ . '/../../src/includes/koneksi.php'; // Naik satu level, lalu masuk ke src/includes
    require_once __DIR__ . '/../../src/includes/functions.php';
    require_once __DIR__ . '/../../src/includes/api.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Check if not logged in, redirect to login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    logSecurityEvent("Unauthorized access attempt to admin dashboard", 'warning');
    header("Location: login.php");
    exit();
}

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_session_regenerate']) || (time() - $_SESSION['last_session_regenerate']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_session_regenerate'] = time();
}

// Get statistics for dashboard using PDO
try {
    // Total orders
    $totalOrdersStmt = $pdo->query("SELECT COUNT(*) as total FROM esim_orders");
    $totalOrders = $totalOrdersStmt->fetchColumn();
    
    // eSIM Aktif - menggunakan kolom esim_status dan status
    $activeOrdersStmt = $pdo->query("
        SELECT COUNT(*) as total FROM esim_orders 
        WHERE esim_status IN ('IN_USE','USED_UP','SUSPENDED')
    ");
    $activeOrders = $activeOrdersStmt->fetchColumn();
    
    // eSIM Pending - status yang masih dalam proses
    $pendingOrdersStmt = $pdo->query("
        SELECT COUNT(*) as total FROM esim_orders 
        WHERE esim_status = 'GOT_RESOURCE'
    ");
    $pendingOrders = $pendingOrdersStmt->fetchColumn();
    
    // eSIM Cancelled/Failed - yang dibatalkan atau gagal
    $cancelledOrdersStmt = $pdo->query("
        SELECT COUNT(*) as total FROM esim_orders 
        WHERE esim_status IN ('cancel', 'CANCEL')
    ");
    $cancelledOrders = $cancelledOrdersStmt->fetchColumn();
    
    // eSIM Expired/Depleted - sudah habis atau kadaluarsa
    $expiredOrdersStmt = $pdo->query("
        SELECT COUNT(*) as total FROM esim_orders 
        WHERE esim_status IN ('USED_EXPIRED', 'EXPIRED')
    ");
    $expiredOrders = $expiredOrdersStmt->fetchColumn();
    
    // Calculate revenue dalam IDR (price sudah dalam IDR berdasarkan data)
    $totalRevenueStmt = $pdo->query("
        SELECT SUM(price) as total FROM esim_orders 
        WHERE (esim_status IN ('IN_USE', 'GOT_RESOURCE') OR status IN ('SUSPENDED'))
        AND price > 0
    ");
    $totalRevenueIDR = $totalRevenueStmt->fetchColumn() ?? 0;
    
    // Latest orders
    $latestOrdersStmt = $pdo->query("SELECT * FROM esim_orders ORDER BY created_at DESC LIMIT 8");
    $latestOrders = $latestOrdersStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $totalOrders = 0;
    $activeOrders = 0;
    $pendingOrders = 0;
    $cancelledOrders = 0;
    $expiredOrders = 0;
    $totalRevenueIDR = 0;
    $latestOrders = [];
}

// Process settings form submission
$successMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent("CSRF token validation failed in admin dashboard", 'warning');
        $errorMessage = "Invalid session. Please try again.";
    } else {
        if ($_POST["action"] == "save_settings") {
            try {
                foreach ($_POST as $key => $value) {
                    if ($key != "action" && $key != "csrf_token") {
                        // Sanitize input
                        $sanitizedKey = htmlspecialchars(strip_tags(trim($key)), ENT_QUOTES, 'UTF-8');
                        $sanitizedValue = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
                        
                        $stmt = $pdo->prepare("INSERT INTO shop_settings (setting_key, setting_value, updated_at) 
                                            VALUES (?, ?, NOW()) 
                                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
                        $stmt->execute([$sanitizedKey, $sanitizedValue]);
                    }
                }
                
                $successMessage = "✅ Pengaturan berhasil disimpan!";
                
                // Refresh settings
                $shopSettings = [];
                $settingsStmt = $pdo->query("SELECT * FROM shop_settings");
                while ($row = $settingsStmt->fetch()) {
                    $shopSettings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (Exception $e) {
                error_log("Error saving settings: " . $e->getMessage());
                $successMessage = "❌ Error: " . $e->getMessage();
            }
        } elseif ($_POST["action"] == "refresh_exchange_rate") {
            $result = updateCurrencyRatesFromApi();
            if ($result['success']) {
                $successMessage = "✅ Kurs berhasil diperbarui: " . number_format($result['rate'], 0, ',', '.') . " IDR per USD";
            } else {
                $successMessage = "❌ " . $result['message'];
            }
        }
    }
}
$currentExchangeRate = getCurrentExchangeRate();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow"> <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Admin - eSIM Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboards.css?v=<?= filemtime('../assets/css/dashboards.css') ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Admin dashboard for eSIM Portal">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
</head>
<body>
<div id="dashboard-data" data-exchange-rate="<?= htmlspecialchars($currentExchangeRate, ENT_QUOTES, 'UTF-8') ?>"></div>
<!-- Floating Dark Mode Toggle -->
<button class="theme-toggle-floating" id="themeToggle">
    <span id="themeIcon">🌙</span>
</button>

<!-- Main Content -->
<main class="main-content">
    <!-- Dashboard Header - Hero Style -->
    <section class="dashboard-header">
        <div class="header-content">
            <h1 class="dashboard-title">✨ Admin Dashboard</h1>
            <p class="dashboard-subtitle">Kelola eSIM portal dengan style yang keren!</p>
            <div class="dashboard-actions">
                <a href="esim.php" class="btn-primary">
                    <span class="btn-icon">📱</span>
                    <span class="btn-text">Kelola eSIM</span>
                </a>
                <a href="orders.php" class="btn-secondary">
                    <span class="btn-icon">📋</span>
                    <span class="btn-text">Lihat Orders</span>
                </a>
            </div>
        </div>
    </section>

    <?php if ($successMessage): ?>
    <div class="message success">
        <span class="message-icon">✅</span>
        <span class="message-text"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="message-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
    <?php endif; ?>

    <!-- Stats Grid dengan card tambahan untuk cancelled -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📱</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                <div class="stat-label">Total eSIM</div>
                <div class="stat-trend">📈 All time</div>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($activeOrders) ?></div>
                <div class="stat-label">eSIM Aktif</div>
                <div class="stat-trend">🎯 Available</div>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($pendingOrders) ?></div>
                <div class="stat-label">eSIM Pending</div>
                <div class="stat-trend">🔄 In progress</div>
            </div>
        </div>
        
        <!-- Card baru untuk eSIM yang dibatalkan -->
        <div class="stat-card danger">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($cancelledOrders) ?></div>
                <div class="stat-label">eSIM Dibatalkan</div>
                <div class="stat-trend">🚫 Cancelled</div>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value">Rp <?= number_format($totalRevenueIDR, 0, ',', '.') ?></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-trend">💵 Indonesian Rupiah</div>
            </div>
        </div>
        
        <!-- Card tambahan untuk eSIM yang expired -->
        <div class="stat-card expired">
            <div class="stat-icon">⏰</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($expiredOrders) ?></div>
                <div class="stat-label">eSIM Expired</div>
                <div class="stat-trend">📅 Used up</div>
            </div>
        </div>
    </section>

    <!-- Exchange Rate Card -->
    <section class="exchange-rate-card">
        <div class="exchange-header">
            <div class="exchange-info">
                <h3>💱 Exchange Rate</h3>
                <div class="exchange-rate">1 USD = Rp <?= number_format($currentExchangeRate, 0, ',', '.') ?></div>
                <p class="last-update">Last updated: <?= date('d M Y H:i') ?> WIB</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="refresh_exchange_rate">
                <button type="submit" class="btn-refresh-rate">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Refresh</span>
                </button>
            </form>
        </div>
    </section>

    <!-- Recent Orders Section -->
    <section class="dashboard-section" id="orders">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">📋</span>
                Recent Orders
            </h2>
            <a href="orders.php" class="section-action">
                View All <span class="action-arrow">→</span>
            </a>
        </div>
        
        <div class="table-container">
            <div class="table-responsive">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($latestOrders)): ?>
                            <?php foreach ($latestOrders as $order): 
                                $status = $order['esim_status'] ?? $order['status'] ?? 'UNKNOWN';
                                $statusClass = getStatusClass($status);
                                $statusText = getStatusIndonesia($status);
                                $priceUsd = ($order['price'] ?? 0) / 10000;
                                
                                // Sanitize data for output
                                $orderNama = htmlspecialchars($order['nama'], ENT_QUOTES, 'UTF-8');
                                $orderPhone = htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                                $orderPackageName = htmlspecialchars($order['packageName'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                                $orderPackageCode = htmlspecialchars($order['packageCode'] ?? '', ENT_QUOTES, 'UTF-8');
                                $orderToken = htmlspecialchars($order['token'], ENT_QUOTES, 'UTF-8');
                                $orderCreatedAt = htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8');
                            ?>
                                 <tr>
                                     <td>
                                         <div class="customer-info">
                                             <div class="customer-avatar"><?= strtoupper(substr($orderNama, 0, 1)) ?></div>
                                             <div class="customer-details">
                                                 <div class="customer-name"><?= $orderNama ?></div>
                                                 <?php if (!empty($orderPhone)): ?>
                                                 <div class="customer-phone"><?= $orderPhone ?></div>
                                                 <?php endif; ?>
                                             </div>
                                         </div>
                                     </td>
                                     <td>
                                         <div class="package-info">
                                             <div class="package-name"><?= $orderPackageName ?></div>
                                             <?php if (!empty($orderPackageCode)): ?>
                                             <div class="package-code"><?= $orderPackageCode ?></div>
                                             <?php endif; ?>
                                         </div>
                                     </td>
                                     <td>
                                         <span class="status-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                                     </td>
                                     <td>
                                         <div class="price-info">
                                             <div class="price-usd">$<?= number_format($priceUsd, 2) ?></div>
                                             <div class="price-idr">~Rp <?= number_format($priceUsd * $currentExchangeRate, 0, ',', '.') ?></div>
                                         </div>
                                     </td>
                                     <td>
                                         <div class="date-info">
                                             <div class="date"><?= date('d M Y', strtotime($orderCreatedAt)) ?></div>
                                             <div class="time"><?= date('H:i', strtotime($orderCreatedAt)) ?></div>
                                         </div>
                                     </td>
                                     <td>
                                         <a href="/detail.php?token=<?= $orderToken ?>" class="btn-view">
                                             <span>👁️</span> Detail
                                         </a>
                                     </td>
                                 </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-content">
                                            <div class="empty-icon">📱</div>
                                            <div class="empty-text">No recent orders</div>
                                            <div class="empty-subtext">New orders will appear here</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Settings Section -->
    <section id="settings" class="dashboard-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">⚙️</span>
                Settings
            </h2>
        </div>
        
        <form method="POST" action="" class="settings-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="save_settings">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="shop_name">
                        <span class="label-icon">🏪</span>
                        Shop Name
                    </label>
                    <input type="text" id="shop_name" name="shop_name" 
                        value="<?= htmlspecialchars($shopSettings['shop_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                        placeholder="Enter shop name">
                </div>
                
                <div class="form-group">
                    <label for="admin_email">
                        <span class="label-icon">📧</span>
                        Admin Email
                    </label>
                    <input type="email" id="admin_email" name="admin_email" 
                        value="<?= htmlspecialchars($shopSettings['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="admin@example.com">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="shop_description">
                    <span class="label-icon">📝</span>
                    Shop Description
                </label>
                <textarea id="shop_description" name="shop_description" 
                    placeholder="Describe your shop..."><?= htmlspecialchars($shopSettings['shop_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="currency">
                        <span class="label-icon">💱</span>
                        Currency
                    </label>
                    <select id="currency" name="currency">
                        <option value="USD" <?= ($shopSettings['currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                        <option value="IDR" <?= ($shopSettings['currency'] ?? '') == 'IDR' ? 'selected' : '' ?>>IDR - Indonesian Rupiah</option>
                        <option value="EUR" <?= ($shopSettings['currency'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                        <option value="SGD" <?= ($shopSettings['currency'] ?? '') == 'SGD' ? 'selected' : '' ?>>SGD - Singapore Dollar</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="markup_percentage">
                        <span class="label-icon">📈</span>
                        Markup Percentage (%)
                    </label>
                    <input type="number" id="markup_percentage" name="markup_percentage" 
                        value="<?= htmlspecialchars($shopSettings['markup_percentage'] ?? '10', ENT_QUOTES, 'UTF-8') ?>" 
                        min="0" max="100" placeholder="10">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="payment_methods">
                    <span class="label-icon">💳</span>
                    Payment Methods
                </label>
                <input type="text" id="payment_methods" name="payment_methods" 
                    value="<?= htmlspecialchars($shopSettings['payment_methods'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                    placeholder="bank_transfer,credit_card,paypal">
                <span class="form-help">Separate with commas (e.g: bank_transfer,credit_card,paypal)</span>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <span class="btn-icon">💾</span>
                    <span class="btn-text">Save Settings</span>
                </button>
            </div>
        </form>
    </section>
</main>

<!-- Bottom Navigation - Sama seperti eSIM -->
<nav class="bottom-nav">
    <a href="dashboard.php" class="nav-item">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Dashboard</span>
    </a>
    <a href="orders.php" class="nav-item">
        <span class="nav-icon">📦</span>
        <span class="nav-label">Orders</span>
    </a>
    <a href="esim.php" class="nav-item">
        <span class="nav-icon">📱</span>
        <span class="nav-label">Packages</span>
    </a>
    <a href="topup.php" class="nav-item">
        <span class="nav-icon">💰</span>
        <span class="nav-label">DaftarTopup</span>
    </a>
    <a href="settings.php" class="nav-item">
        <span class="nav-icon">⚙️</span>
        <span class="nav-label">Settings</span>
    </a>
    <a href="logout.php" class="nav-item">
        <span class="nav-icon">👤</span>
        <span class="nav-label">Logout</span>
    </a>
</nav>

<!-- Dashboard JavaScript yang sudah diselaraskan - PATH DIPERBAIKI -->
<script src="../assets/js/dashboards.js?v=<?= filemtime('../assets/js/dashboards.js') ?>"></script>
</body>
</html>
