<?php
error_reporting(0);
ini_set('display_errors', '0');

// Secure admin dashboard
define('ALLOWED_ACCESS', true);

// Session setup SELALU sebelum output atau include lain!
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
if (session_status() === PHP_SESSION_NONE) session_start();

// BARU include config, supaya function2 tersedia
require_once __DIR__ . '/../config.php';

securityMiddleware();
setSecurityHeaders();

// Check if not logged in, redirect to login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    logSecurityEvent("Unauthorized access attempt to admin/esim.php", 'warning');
    header("Location: login.php");
    exit();
}

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_session_regenerate']) || (time() - $_SESSION['last_session_regenerate']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_session_regenerate'] = time();
}

// Include required files with error handling
try {
    include '../includes/koneksi.php';
    include '../includes/api.php';
    include '../includes/functions.php';
} catch (Exception $e) {
    error_log("Failed to include required files in admin/esim.php: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Handle balance request with proper error handling
if (isset($_GET['action']) && $_GET['action'] === 'get_balance') {
    try {
        $balanceResponse = getMerchantBalance();
        
        if ($balanceResponse && isset($balanceResponse['success']) && $balanceResponse['success']) {
            $balance = $balanceResponse['obj']['balance'] ?? 0;
            echo json_encode(['success' => true, 'balance' => (float)$balance]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch balance: ' . ($balanceResponse['errorMsg'] ?? 'Unknown error')]);
        }
    } catch (Exception $e) {
        error_log("Error fetching balance: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'System error while fetching balance']);
    }
    exit;
}

// Handle packages request with proper error handling
if (isset($_GET['action']) && $_GET['action'] === 'get_packages') {
    try {
        // Get exchange rate from shop_settings
        $kurs = 17500; // fallback default
        
        if (isset($pdo)) {
            $kursStmt = $pdo->prepare("SELECT setting_value FROM shop_settings WHERE setting_key IN ('kurs_usd_idr', 'exchange_rate') ORDER BY FIELD(setting_key, 'exchange_rate', 'kurs_usd_idr') LIMIT 1");
            $kursStmt->execute();
            $kursRow = $kursStmt->fetch();
            if ($kursRow) {
                $kurs = (float)$kursRow['setting_value'];
            }
        } else {
            $kursQuery = $conn->prepare("SELECT setting_value FROM shop_settings WHERE setting_key IN ('kurs_usd_idr', 'exchange_rate') ORDER BY FIELD(setting_key, 'exchange_rate', 'kurs_usd_idr') LIMIT 1");
            $kursQuery->execute();
            $kursResult = $kursQuery->get_result();
            if ($kursResult && $kursResult->num_rows > 0) {
                $kursRow = $kursResult->fetch_assoc();
                $kurs = (float)$kursRow['setting_value'];
            }
            $kursQuery->close();
        }

        // If no exchange rate found, use getCurrentExchangeRate function
        if ($kurs <= 0) {
            $kurs = getCurrentExchangeRate();
        }

        // Get all packages from packages table
        $packages = [];
        
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT * FROM packages WHERE is_active = 1 ORDER BY type ASC, name ASC");
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                // Add IDR price
                $row['price_idr'] = round((float)$row['price_usd'] * $kurs / 10000);
                $packages[] = $row;
            }
        } else {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE is_active = 1 ORDER BY type ASC, name ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Add IDR price
                    $row['price_idr'] = round((float)$row['price_usd'] * $kurs / 10000);
                    $packages[] = $row;
                }
            }
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'packages' => $packages]);
    } catch (Exception $e) {
        error_log("Error fetching packages: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'System error while fetching packages']);
    }
    exit;
}

// Get current exchange rate
try {
    $kurs = getCurrentExchangeRate();
} catch (Exception $e) {
    error_log("Error getting exchange rate: " . $e->getMessage());
    $kurs = 17500; // fallback default
}

// Process order for regular packages with proper validation and error handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order_esim') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token");
        }
        
        // Validate and sanitize inputs
        $customerName = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $customerName = htmlspecialchars(strip_tags(trim($customerName)), ENT_QUOTES, 'UTF-8');
        
        $packageCode = filter_input(INPUT_POST, 'package_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $packageCode = htmlspecialchars(strip_tags(trim($packageCode)), ENT_QUOTES, 'UTF-8');
        
        $count = filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10, 'default' => 1]]);

        if (empty($customerName) || empty($packageCode)) {
            throw new Exception("Data tidak lengkap. Nama pelanggan dan kode paket harus diisi.");
        }

        // Get package price from database
        $priceUsdCents = null;
        
        if (isset($pdo)) {
            $priceStmt = $pdo->prepare("SELECT price_usd FROM packages WHERE package_code = ? LIMIT 1");
            $priceStmt->execute([$packageCode]);
            $priceRow = $priceStmt->fetch();
            if ($priceRow) {
                $priceUsdCents = (int)$priceRow['price_usd']; // Price already in cents
            }
        } else {
            $priceStmt = $conn->prepare("SELECT price_usd FROM packages WHERE package_code = ? LIMIT 1");
            $priceStmt->bind_param("s", $packageCode);
            $priceStmt->execute();
            $priceResult = $priceStmt->get_result();
            if ($priceResult && $priceResult->num_rows > 0) {
                $priceRow = $priceResult->fetch_assoc();
                $priceUsdCents = (int)$priceRow['price_usd']; // Price already in cents
            }
            $priceStmt->close();
        }

        // If package not found in database, try to get it from API directly
        if (!$priceUsdCents) {
            // Get package from API
            $packageList = getPackageList("", "", $packageCode);
            if (isset($packageList['success']) && $packageList['success'] && 
                isset($packageList['obj']['packageList']) && 
                !empty($packageList['obj']['packageList'])) {
                
                foreach ($packageList['obj']['packageList'] as $pkg) {
                    if ($pkg['packageCode'] === $packageCode) {
                        $priceUsdCents = (int)$pkg['price'];
                        break;
                    }
                }
            }
            
            // If still not found, throw error
            if (!$priceUsdCents) {
                throw new Exception("Paket tidak ditemukan atau harga tidak valid.");
            }
        }

        $results = [];
        
        // Process multiple orders if count > 1
        for ($i = 1; $i <= $count; $i++) {
            // Generate unique names for multiple orders
            $currentCustomerName = $count > 1 ? $customerName . '_' . $i : $customerName;
            
            // Generate secure transaction ID
            $transactionId = 'ORD_' . date('ymdHis') . '_' . strtoupper(bin2hex(random_bytes(3))) . '_' . $i;
            
            // Generate secure token
            $token = strtoupper(bin2hex(random_bytes(6))); // 12 character hex

            // Log order details for debugging
            error_log("=== REGULAR ORDER DEBUG ===");
            error_log("Package Code: " . $packageCode);
            error_log("Count: " . $count);
            error_log("Current Order: " . $i);
            error_log("Customer Name: " . $currentCustomerName);
            error_log("Price USD Cents: " . $priceUsdCents);
            error_log("Transaction ID: " . $transactionId);
            error_log("=== END REGULAR DEBUG ===");

            // Prepare API request payload
            $packageInfo = [
                "packageCode" => $packageCode,
                "count" => 1,
                "price" => $priceUsdCents
            ];
            
            $amount = $priceUsdCents;
            
            // Send order to API with error handling
            $apiResponse = createEsimOrder($transactionId, $packageCode, 1, $priceUsdCents);

            if (!isset($apiResponse['success']) || $apiResponse['success'] !== true) {
                throw new Exception("Gagal order: " . ($apiResponse['errorMsg'] ?? 'Unknown error'));
            }

            // Validate API response
            if (!isset($apiResponse['obj']) || !isset($apiResponse['obj']['orderNo'])) {
                throw new Exception("Gagal order: API response missing required fields");
            }

            // Extract main data from response
            $orderNo = $apiResponse['obj']['orderNo'] ?? '';
            $esimTranNo = $apiResponse['obj']['esimTranNo'] ?? ''; // This might be empty initially
            $priceIdr = $priceUsdCents ? round($priceUsdCents * $kurs / 10000) : 0;

            // Try to get ICCID immediately (without long loop)
            $iccid = null;
            $packageName = "";
            sleep(2); // Short wait
            
            $detailResponse = queryEsimDetails($orderNo, '', $esimTranNo);
            if (isset($detailResponse['success']) && $detailResponse['success'] && 
                isset($detailResponse['obj']['esimList']) && 
                !empty($detailResponse['obj']['esimList'])) {
                
                $esimData = $detailResponse['obj']['esimList'][0];
                $iccid = $esimData['iccid'] ?? null;
                
                // Get package name from API response
                if (isset($esimData['packageList']) && !empty($esimData['packageList'])) {
                    $packageName = $esimData['packageList'][0]['packageName'] ?? "";
                }
            }

            // Save to database with or without esimTranNo
            if (isset($pdo)) {
                if ($iccid) {
                    $stmt = $pdo->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, iccid, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())");
                    $stmt->execute([$currentCustomerName, $orderNo, $esimTranNo ?: null, $iccid, $packageCode, $packageName, $priceIdr, $token]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'provisioning', NOW())");
                    $stmt->execute([$currentCustomerName, $orderNo, $esimTranNo ?: null, $packageCode, $packageName, $priceIdr, $token]);
                }
            } else {
                if ($iccid) {
                    // If ICCID is available, save with ready status
                    $stmt = $conn->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, iccid, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())");
                    $stmt->bind_param("ssssssds", $currentCustomerName, $orderNo, $esimTranNo, $iccid, $packageCode, $packageName, $priceIdr, $token);
                } else {
                    // If ICCID not yet available, save with provisioning status
                    $stmt = $conn->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'provisioning', NOW())");
                    $stmt->bind_param("sssssds", $currentCustomerName, $orderNo, $esimTranNo, $packageCode, $packageName, $priceIdr, $token);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menyimpan data: " . $stmt->error);
                }
                $stmt->close();
            }

            $results[] = [
                'customerName' => $currentCustomerName,
                'token' => $token,
                'orderNo' => $orderNo,
                'iccid' => $iccid,
                'provisioning' => !$iccid
            ];
        }

        $message = count($results) > 1 ? 
            count($results) . ' eSIM berhasil dipesan!' : 
            'eSIM berhasil dipesan!';

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'results' => $results
        ]);
        exit;
    } catch (Exception $e) {
        error_log("Order error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Process order for unlimited/dayplans packages with proper validation and error handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order_unlimited') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token");
        }
        
        // Validate and sanitize inputs
        $customerName = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $customerName = htmlspecialchars(strip_tags(trim($customerName)), ENT_QUOTES, 'UTF-8');
        
        $packageCode = filter_input(INPUT_POST, 'package_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $packageCode = htmlspecialchars(strip_tags(trim($packageCode)), ENT_QUOTES, 'UTF-8');
        
        $periodNum = filter_input(INPUT_POST, 'period_num', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 30, 'default' => 1]]);

        if (empty($customerName) || empty($packageCode) || $periodNum < 1) {
            throw new Exception("Data tidak lengkap atau periode tidak valid.");
        }

        // Generate secure transaction ID and token
        $transactionId = 'UNL_' . date('ymdHis') . '_' . strtoupper(bin2hex(random_bytes(3)));
        $token = strtoupper(bin2hex(random_bytes(6))); // 12 character hex

        // Get package price from database
        $priceUsdCents = null;
        $packageInfo = null;
        
        if (isset($pdo)) {
            $packageStmt = $pdo->prepare("SELECT * FROM packages WHERE package_code = ? LIMIT 1");
            $packageStmt->execute([$packageCode]);
            $packageInfo = $packageStmt->fetch();
            if ($packageInfo) {
                $priceUsdCents = (int)$packageInfo['price_usd']; // Price already in cents
            }
        } else {
            $packageStmt = $conn->prepare("SELECT * FROM packages WHERE package_code = ? LIMIT 1");
            $packageStmt->bind_param("s", $packageCode);
            $packageStmt->execute();
            $packageResult = $packageStmt->get_result();
            if ($packageResult && $packageResult->num_rows > 0) {
                $packageInfo = $packageResult->fetch_assoc();
                $priceUsdCents = (int)$packageInfo['price_usd']; // Price already in cents
            }
            $packageStmt->close();
        }

        // If package not found in database, try to get it from API directly
        if (!$priceUsdCents) {
            // Get package from API
            $packageList = getPackageList("", "", $packageCode);
            if (isset($packageList['success']) && $packageList['success'] && 
                isset($packageList['obj']['packageList']) && 
                !empty($packageList['obj']['packageList'])) {
                
                foreach ($packageList['obj']['packageList'] as $pkg) {
                    if ($pkg['packageCode'] === $packageCode) {
                        $priceUsdCents = (int)$pkg['price'];
                        $packageInfo = $pkg;
                        break;
                    }
                }
            }
            
            // If still not found, throw error
            if (!$priceUsdCents) {
                throw new Exception("Paket tidak ditemukan atau harga tidak valid.");
            }
        }

        // Calculate total price in IDR for database (periodNum × price × kurs)
        $totalPriceIdr = round($periodNum * ($priceUsdCents / 10000) * $kurs);

        // Log order details for debugging
        error_log("=== DAYPLANS ORDER DEBUG ===");
        error_log("Package Code: " . $packageCode);
        error_log("Package Name: " . ($packageInfo['name'] ?? 'Unknown'));
        error_log("FUP Policy: " . ($packageInfo['fup_policy'] ?? 'None'));
        error_log("Period Num (Days): " . $periodNum);
        error_log("Price USD Cents (per day): " . $priceUsdCents);
        error_log("Total Price IDR: " . $totalPriceIdr);
        error_log("Transaction ID: " . $transactionId);
        error_log("=== END DAYPLANS DEBUG ===");

        // Send order to API with proper payload
        $apiResponse = createDayplansOrder($transactionId, $packageCode, $periodNum, $priceUsdCents);

        if (!isset($apiResponse['success']) || $apiResponse['success'] !== true) {
            throw new Exception("Gagal order unlimited: " . ($apiResponse['errorMsg'] ?? 'Unknown error'));
        }

        // Validate API response
        if (!isset($apiResponse['obj']) || !isset($apiResponse['obj']['orderNo'])) {
            throw new Exception("Gagal order: API response missing required fields");
        }

        // Extract main data from response
        $orderNo = $apiResponse['obj']['orderNo'] ?? '';
        $esimTranNo = $apiResponse['obj']['esimTranNo'] ?? '';

        // Try to get ICCID immediately (without long loop)
        $iccid = null;
        $packageName = "";
        sleep(2); // Short wait
        
        $detailResponse = queryEsimDetails($orderNo, '', $esimTranNo);
        if (isset($detailResponse['success']) && $detailResponse['success'] && 
            isset($detailResponse['obj']['esimList']) && 
            !empty($detailResponse['obj']['esimList'])) {
            
            $esimData = $detailResponse['obj']['esimList'][0];
            $iccid = $esimData['iccid'] ?? null;
            
            // Get package name from API response
            if (isset($esimData['packageList']) && !empty($esimData['packageList'])) {
                $packageName = $esimData['packageList'][0]['packageName'] ?? "";
            }
        }

        // Save to database with or without ICCID
        if (isset($pdo)) {
            if ($iccid) {
                // If ICCID is available, save with ready status
                $stmt = $pdo->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, iccid, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())");
                $stmt->execute([$customerName, $orderNo, $esimTranNo, $iccid, $packageCode, $packageName, $totalPriceIdr, $token]);
            } else {
                // If ICCID not yet available, save with provisioning status
                $stmt = $pdo->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'provisioning', NOW())");
                $stmt->execute([$customerName, $orderNo, $esimTranNo, $packageCode, $packageName, $totalPriceIdr, $token]);
            }
        } else {
            if ($iccid) {
                // If ICCID is available, save with ready status
                $stmt = $conn->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, iccid, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())");
                $stmt->bind_param("ssssssds", $customerName, $orderNo, $esimTranNo, $iccid, $packageCode, $packageName, $totalPriceIdr, $token);
            } else {
                // If ICCID not yet available, save with provisioning status
                $stmt = $conn->prepare("INSERT INTO esim_orders (nama, orderNo, esimTranNo, packageCode, packageName, price, token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'provisioning', NOW())");
                $stmt->bind_param("sssssds", $customerName, $orderNo, $esimTranNo, $packageCode, $packageName, $totalPriceIdr, $token);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan data: " . $stmt->error);
            }
            $stmt->close();
        }

        $message = $iccid ? 'Paket unlimited berhasil dipesan dan siap digunakan!' : 'Paket unlimited berhasil dipesan! Sedang dalam proses provisioning...';

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'results' => [[
                'customerName' => $customerName,
                'token' => $token,
                'orderNo' => $orderNo,
                'iccid' => $iccid,
                'provisioning' => !$iccid
            ]]
        ]);
        exit;
    } catch (Exception $e) {
        error_log("Unlimited order error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✨ eSIM Store - Modern & Trendy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/esim-style.css">
    <link rel="stylesheet" href="assets/css/esim-style.css?v=<?= filemtime('assets/css/esim-style.css') ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Modern eSIM store with trendy Gen Z design">
    <!-- Security headers -->
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    
    <!-- Pass exchange rate to JavaScript -->
    <script>
    window.exchangeRate = <?= $kurs ?>;
    console.log('Exchange rate loaded from DB:', window.exchangeRate);
    </script>
</head>
<body>

<!-- Floating Balance Display -->
<div class="balance-floating" id="balanceDisplay">
    <div class="balance-amount" id="balanceAmount">$0.00</div>
    <div class="balance-status" id="balanceStatus">Loading...</div>
</div>

<!-- Floating Dark Mode Toggle -->
<button class="theme-toggle-floating" id="themeToggle">
    <span id="themeIcon">🌙</span>
</button>

<!-- Main Content -->
<main class="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <p></p>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="search-container">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search countries or codes (e.g., Indonesia, ID, Singapore, SG)" oninput="filterPackages()">
                <button class="search-clear" id="searchClear" style="display: none;">✕</button>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="filter-header">
            <h3 class="filter-title">
                <span class="filter-icon">🎛️</span>
                Filters & Settings
            </h3>
            <button class="filter-toggle-btn" onclick="toggleFilters()">
                <span id="filterToggleIcon">👀</span>
                <span id="filterToggleText">Show Filters</span>
            </button>
        </div>
        
        <div class="filter-content" id="filterContent" style="display: none;">
            <!-- Location Type Filter -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <span class="filter-group-icon">📍</span>
                    Location Type
                </h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" id="countryBtn" onclick="setLocationType('country')">
                        <span class="filter-btn-icon">🏳️</span>
                        <span class="filter-btn-text">Country</span>
                        <span class="filter-btn-count" id="countryCount">0</span>
                    </button>
                    <button class="filter-btn" id="regionalBtn" onclick="setLocationType('regional')">
                        <span class="filter-btn-icon">🌏</span>
                        <span class="filter-btn-text">Regional</span>
                        <span class="filter-btn-count" id="regionalCount">0</span>
                    </button>
                    <button class="filter-btn" id="globalBtn" onclick="setLocationType('global')">
                        <span class="filter-btn-icon">🌍</span>
                        <span class="filter-btn-text">Global</span>
                        <span class="filter-btn-count" id="globalCount">0</span>
                    </button>
                </div>
            </div>

            <!-- Package Type Filter -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <span class="filter-group-icon">📦</span>
                    Package Type
                </h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" id="regularBtn" onclick="setPackageType('regular')">
                        <span class="filter-btn-icon">📱</span>
                        <span class="filter-btn-text">Regular</span>
                        <span class="filter-btn-count" id="regularCount">0</span>
                        <span class="filter-btn-badge">TopUp</span>
                    </button>
                    <button class="filter-btn" id="unlimitedBtn" onclick="setPackageType('unlimited')">
                        <span class="filter-btn-icon">♾️</span>
                        <span class="filter-btn-text">Unlimited</span>
                        <span class="filter-btn-count" id="unlimitedCount">0</span>
                        <span class="filter-btn-badge">Dayplans</span>
                    </button>
                </div>
            </div>

            <!-- TikTok Filter -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <span class="filter-group-icon">🎵</span>
                    TikTok Support
                </h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" id="allTikTokBtn" onclick="setTikTokFilter('all')">
                        <span class="filter-btn-icon">📱</span>
                        <span class="filter-btn-text">All</span>
                        <span class="filter-btn-count" id="allTikTokCount">0</span>
                    </button>
                    <button class="filter-btn" id="tiktokSupportedBtn" onclick="setTikTokFilter('supported')">
                        <span class="filter-btn-icon">✅</span>
                        <span class="filter-btn-text">Supported</span>
                        <span class="filter-btn-count" id="tiktokSupportedCount">0</span>
                    </button>
                    <button class="filter-btn" id="tiktokNotSupportedBtn" onclick="setTikTokFilter('not-supported')">
                        <span class="filter-btn-icon">❌</span>
                        <span class="filter-btn-text">Not Supported</span>
                        <span class="filter-btn-count" id="tiktokNotSupportedCount">0</span>
                    </button>
                </div>
            </div>

            <!-- Sort Options -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <span class="filter-group-icon">📊</span>
                    Sort Results
                </h4>
                <div class="sort-controls">
                    <select id="sortOrder" class="sort-select" onchange="filterPackages()">
                        <option value="relevance">🎯 Most Relevant</option>
                        <option value="volume-asc">📊 Data: Small → Large</option>
                        <option value="volume-desc">📊 Data: Large → Small</option>
                        <option value="price-asc">💰 Price: Low → High</option>
                        <option value="price-desc">💰 Price: High → Low</option>
                        <option value="name-asc">🔤 Name: A → Z</option>
                        <option value="name-desc">🔤 Name: Z → A</option>
                    </select>
                    <button class="reset-btn" onclick="resetAllFilters()">
                        <span class="reset-icon">🔄</span>
                        Reset All
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Results Section -->
    <section class="results-section">
        <div id="packagesList" class="packages-grid" style="display: none;">
            <!-- Packages will be populated here by JavaScript -->
        </div>
        
        <div id="noResults" class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3 class="empty-title">Find Your Perfect eSIM</h3>
            <p class="empty-description">Use the filters above to discover packages that match your needs</p>
            <p class="empty-hint">Start by selecting a location type and typing a country name</p>
        </div>
    </section>
</main>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <a href="dashboard.php" class="nav-item">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Dashboard</span>
    </a>
    <a href="orders.php" class="nav-item">
        <span class="nav-icon">📦</span>
        <span class="nav-label">Orders</span>
    </a>
    <a href="esim.php" class="nav-item activate">
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

<!-- Order Modal -->
<div id="orderModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('orderModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="orderModalTitle" class="modal-title">
                <span class="modal-icon">✨</span>
                Order eSIM
            </h3>
            <button class="modal-close" onclick="closeModal('orderModal')">
                <span>✕</span>
            </button>
        </div>
        
        <form id="orderForm" class="order-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="order_esim" id="orderAction">
            <input type="hidden" name="package_code" id="orderPackageCode">
            
            <div class="package-details" id="orderPackageDetails">
                <!-- Package details will be populated here -->
            </div>
            
            <div class="form-group">
                <label for="customerName" class="form-label">
                    <span class="label-icon">👤</span>
                    Customer Name
                </label>
                <input type="text" id="customerName" name="customer_name" class="form-input" required pattern="[A-Za-z0-9_\- ]{3,50}" title="Name must be 3-50 characters and can contain letters, numbers, spaces, hyphens and underscores">
            </div>
            
            <div class="form-group" id="countGroup">
                <label for="orderCount" class="form-label">
                    <span class="label-icon">🔢</span>
                    Quantity
                </label>
                <input type="number" id="orderCount" name="count" value="1" min="1" max="10" class="form-input">
                <div class="form-hint" id="countHint" style="display: none;">
                    <span>💡</span>
                    Multiple orders will be named: name_1, name_2, etc.
                </div>
            </div>
            
            <div class="form-group" id="periodGroup" style="display: none;">
                <label for="periodNum" class="form-label">
                    <span class="label-icon">📅</span>
                    Number of Days
                </label>
                <input type="number" id="periodNum" name="period_num" value="1" min="1" max="30" class="form-input">
            </div>
            
            <button type="submit" class="submit-btn" id="orderSubmitBtn">
                <span class="btn-icon">✨</span>
                <span class="btn-text">Order Now</span>
            </button>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('successModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">🎉</span>
                Order Successful!
            </h3>
            <button class="modal-close" onclick="closeModal('successModal')">
                <span>✕</span>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="successMessage" class="success-message">
                <p>Your eSIM has been ordered successfully!</p>
            </div>
            
        <!-- Untuk single link -->
        <div id="singleLinkContainer" class="link-container">
            <input type="text" id="tokenLink" class="link-input" readonly>
            <button onclick="copyTokenLink()" class="copy-btn">
                <span class="copy-icon">📋</span>
                Copy Link
            </button>
        </div>

        <!-- Untuk multiple links -->
        <div id="multipleLinkContainer" class="multiple-links" style="display: none;">
            <div class="links-header">
                <h4>📋 Order Links</h4>
                <button onclick="copyAllLinks()" class="copy-all-btn">
                    <span class="copy-icon">📋</span>
                    Copy All Links
                </button>
            </div>
            <div id="linksList" class="links-list">
                <!-- Multiple links will be populated here -->
            </div>
        </div>
            
            <div id="provisioningNote" class="provisioning-note" style="display: none;">
                <div class="warning-card">
                    <div class="warning-icon">⏳</div>
                    <div class="warning-content">
                        <h4>Processing in Progress</h4>
                        <p>Your eSIM is being processed. Refresh the detail page in a few minutes to see the latest status.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Country Modal -->
<div id="countryModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('countryModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">🌍</span>
                Available Countries
            </h3>
            <button class="modal-close" onclick="closeModal('countryModal')">
                <span>✕</span>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="country-search">
                <input type="text" id="countrySearchInput" class="country-search-input" placeholder="Search countries...">
            </div>
            <div id="countryList" class="country-list">
                <!-- Countries will be populated here -->
            </div>
        </div>
    </div>
</div>
<script src="assets/js/esim.js?v=<?= filemtime('assets/js/esim.js') ?>"></script>
</body>
</html>