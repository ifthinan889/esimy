<?php
declare(strict_types=1); // <<< INI PALING ATAS
ob_start();

// PRODUCTION ERROR HANDLING
// Ubah bagian paling awal file test.php dan topup.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/api.php';

// ===== DEBUG AJAX REQUESTS =====
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("📨 POST request received:");
    error_log("POST data: " . print_r($_POST, true));
}

// ===== HANDLE AJAX CHECK DATABASE ONLY =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ajax_check_db_only"])) {
    error_log("🔍 Database-only check requested");
    header('Content-Type: application/json');
    
    $checkOrderId = $_POST['order_id'] ?? '';
    
    if (!empty($checkOrderId)) {
        try {
            $stmt = $pdo->prepare("
                SELECT status, paid_at, topup_processed, topup_result, expired_at
                FROM qris_payments 
                WHERE order_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$checkOrderId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                // Auto-expire check
                if ($payment['status'] === 'pending' && strtotime($payment['expired_at']) < time()) {
                    $stmt = $pdo->prepare("UPDATE qris_payments SET status = 'expired' WHERE order_id = ?");
                    $stmt->execute([$checkOrderId]);
                    $payment['status'] = 'expired';
                }
                
                $response = [
                    'success' => true,
                    'status' => $payment['status'],
                    'order_id' => $checkOrderId,
                    'paid_at' => $payment['paid_at'],
                    'topup_processed' => $payment['topup_processed'],
                    'last_check' => date('Y-m-d H:i:s')
                ];
                
                error_log("✅ DB check result: " . json_encode($response));
                echo json_encode($response);
            } else {
                echo json_encode([
                    'success' => false, 
                    'status' => 'not_found',
                    'message' => 'Order not found'
                ]);
            }
        } catch (Exception $e) {
            error_log("❌ Database check error: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Order ID required'
        ]);
    }
    exit;
}

// ✅ TAMBAH INI DI AWAL topup.php setelah require files
function validateRequiredSettings() {
    $requiredSettings = [
        'bukalapak_user_id' => 'Bukalapak User ID tidak ditemukan',
        'bukalapak_identity' => 'Bukalapak Identity tidak ditemukan',  
        'bukalapak_token' => 'Bukalapak Token tidak ditemukan',
        'qris_static_code' => 'QRIS Static Code tidak ditemukan',
        'exchange_rate_usd_idr' => 'Exchange Rate tidak ditemukan',
        'qr_generator_url' => 'QR Generator URL tidak ditemukan'
    ];
    
    $missingSettings = [];
    
    foreach ($requiredSettings as $key => $errorMsg) {
        $value = getSetting($key);
        if (empty($value)) {
            $missingSettings[] = $errorMsg;
        }
    }
    
    if (!empty($missingSettings)) {
        $error = 'Konfigurasi sistem tidak lengkap:<br>' . implode('<br>', $missingSettings);
        throw new Exception($error);
    }
}

// ✅ PANGGIL DI AWAL
try {
    validateRequiredSettings();
} catch (Exception $e) {
    $error = $e->getMessage();
    // Display error page and exit
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Configuration Error</title></head>
    <body>
        <div style="text-align:center; margin-top:50px;">
            <h2>⚠️ System Configuration Error</h2>
            <p><?= $error ?></p>
            <p><small>Please contact administrator</small></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

class QRISPaymentGateway {
    private $expiredMinutes;
    private $staticQRIS; 
    
    public function __construct() {
        $this->expiredMinutes = getSetting('qris_expired_minutes', 3);
        $this->staticQRIS = getSetting('qris_static_code');
        
        if (!$this->staticQRIS) {
            throw new Exception('QRIS static code not configured in database');
        }
    }
    
    // ✅ PERBAIKI FUNCTION INI - LEBIH ROBUST
    // ✅ UPDATE method generateUniqueCode di class QRISPaymentGateway
    public function generateUniqueCode($pdo, $csrfToken = null) {
        // ✅ PRIORITAS: Pakai consistent code dulu
        if (!empty($csrfToken)) {
            $hash = md5($csrfToken . date('Y-m-d H'));
            $baseCode = (hexdec(substr($hash, 0, 6)) % 900) + 100;
            
            // Cek availability
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM qris_payments 
                WHERE unique_code = ? 
                AND status = 'pending' 
                AND expired_at > NOW()
            ");
            $stmt->execute([$baseCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                error_log("✅ Using consistent code: $baseCode");
                return $baseCode;
            } else {
                // Try dengan offset
                for ($offset = 1; $offset <= 50; $offset++) {
                    $newCode = $baseCode + $offset;
                    if ($newCode > 999) $newCode = ($newCode % 900) + 100;
                    
                    $stmt->execute([$newCode]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] == 0) {
                        error_log("✅ Using consistent code with offset: $newCode (base: $baseCode + $offset)");
                        return $newCode;
                    }
                }
            }
        }
        
        // ✅ FALLBACK: Random generation (existing logic)
        $maxAttempts = 50;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $attempt++;
            $candidateCode = mt_rand(100, 999);
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM qris_payments 
                WHERE unique_code = ? 
                AND status = 'pending' 
                AND expired_at > NOW()
            ");
            $stmt->execute([$candidateCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                error_log("✅ Generated random unique code: $candidateCode on attempt $attempt");
                return $candidateCode;
            }
        }
        
        // Final fallback
        $timestampCode = (int)(microtime(true) * 1000) % 900 + 100;
        error_log("⚠️ Using timestamp fallback: $timestampCode");
        return $timestampCode;
    }
    
    // ✅ TAMBAH FUNCTION BARU UNTUK CLEANUP EXPIRED CODES
    public function cleanupExpiredCodes($pdo) {
        try {
            $stmt = $pdo->prepare("
                UPDATE qris_payments 
                SET status = 'expired' 
                WHERE status = 'pending' 
                AND expired_at <= NOW()
            ");
            $stmt->execute();
            
            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                error_log("🧹 Cleaned up $affectedRows expired payment codes");
            }
            
            return $affectedRows;
        } catch (Exception $e) {
            error_log("❌ Error cleaning up expired codes: " . $e->getMessage());
            return 0;
        }
    }
    
    // ✅ UPDATE method generateDynamicQRIS di class QRISPaymentGateway
    // ✅ PERBAIKI method generateDynamicQRIS - konsisten dengan create_payment
    public function generateDynamicQRIS($pdo, $orderId, $baseAmount, $priceUsdCent, $description = '', $csrfToken = null, $packageCode = '') {
        // Cleanup expired codes
        $this->cleanupExpiredCodes($pdo);
        
        // Generate unique code
        $uniqueCode = $this->generateUniqueCode($pdo, $csrfToken);
        $finalAmount = $baseAmount + $uniqueCode;
        
        $qrisCode = $this->createDynamicQRIS($finalAmount);
        $qrCodeUrl = "https://hanisyaastore.com/includes/generate_qr.php?data=" . urlencode($qrisCode);
        
        $createdAt = date('Y-m-d H:i:s');
        $expiredAt = date('Y-m-d H:i:s', strtotime("+{$this->expiredMinutes} minutes"));
        
        // ✅ INSERT dengan price_usd dalam cent
        $maxInsertAttempts = 3;
        $insertAttempt = 0;
        
        while ($insertAttempt < $maxInsertAttempts) {
            $insertAttempt++;
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO qris_payments 
                      (order_id, base_amount, price_usd, unique_code, final_amount, qris_code, qr_code_url, status, description, package_code, created_at, expired_at, last_checked)
                    VALUES 
                      (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $orderId,           // order_id
                    $baseAmount,        // base_amount (IDR)
                    $priceUsdCent,      // price_usd (DALAM CENT - langsung dari API)
                    $uniqueCode,        // unique_code
                    $finalAmount,       // final_amount (IDR + unique code)
                    $qrisCode,          // qris_code
                    $qrCodeUrl,         // qr_code_url
                    $description,       // description
                    $packageCode,       // package_code
                    $createdAt,         // created_at
                    $expiredAt          // expired_at
                ]);
                
                break; // Success
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false && $insertAttempt < $maxInsertAttempts) {
                    error_log("⚠️ Duplicate unique code detected, regenerating...");
                    $uniqueCode = $this->generateUniqueCode($pdo, $csrfToken);
                    $finalAmount = $baseAmount + $uniqueCode;
                    $qrisCode = $this->createDynamicQRIS($finalAmount);
                    $qrCodeUrl = "https://hanisyaastore.com/includes/generate_qr.php?data=" . urlencode($qrisCode);
                } else {
                    throw $e;
                }
            }
        }
        
        return [
            'success'      => true,
            'order_id'     => $orderId,
            'base_amount'  => $baseAmount,
            'price_usd_cent' => $priceUsdCent,
            'unique_code'  => $uniqueCode,
            'final_amount' => $finalAmount,
            'qris_code'    => $qrisCode,
            'qr_code_url'  => $qrCodeUrl,
            'status'       => 'pending',
            'description'  => $description,
            'package_code' => $packageCode,
            'created_at'   => $createdAt,
            'expired_at'   => $expiredAt,
            'paid_at'      => null
        ];
    }

    // Function lainnya tetap sama...
    private function generateCRC16($str) {
        $crc = 0xFFFF;
        $len = strlen($str);
        for ($c = 0; $c < $len; $c++) {
            $crc ^= ord($str[$c]) << 8;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
                $crc &= 0xFFFF;
            }
        }
        $hex = strtoupper(dechex($crc));
        return str_pad($hex, 4, "0", STR_PAD_LEFT);
    }
    
    public function createDynamicQRIS($amount) {
        $amountStr = (string)$amount;
        $qris = substr($this->staticQRIS, 0, -4);
        $step1 = str_replace("010211", "010212", $qris);
        $step2 = explode("5802ID", $step1);
        $amountField = "54" . sprintf("%02d", strlen($amountStr)) . $amountStr;
        $fix = trim($step2[0]) . $amountField . "5802ID" . trim($step2[1]);
        $fix .= $this->generateCRC16($fix);
        return $fix;
    }
    
    public function getTransactionByOrderId($pdo, $orderId) {
        $stmt = $pdo->prepare("SELECT * FROM qris_payments WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateStatusExpired($pdo) {
        return $this->cleanupExpiredCodes($pdo);
    }
    
    public function getPendingPayments($pdo) {
        $stmt = $pdo->prepare("
            SELECT order_id, final_amount, created_at, last_checked 
            FROM qris_payments 
            WHERE status = 'pending' AND expired_at > NOW()
            ORDER BY created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ===== INISIALISASI VARIABEL =====
// ✅ Tambah ini di awal topup.php (5 menit setup)
function secureInput($input, $type = 'string') {
    $input = trim($input);
    
    switch($type) {
        case 'order_id':
            return preg_replace('/[^a-zA-Z0-9\-_]/', '', $input);
        case 'token':
            return preg_replace('/[^A-Z0-9]/', '', $input);
        case 'iccid':
            return preg_replace('/[^0-9]/', '', $input);
        default:
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

// ✅ Use di semua input
$token = secureInput($_GET['token'] ?? '', 'token');
$iccid = secureInput($_GET['iccid'] ?? '', 'iccid');
$orderId = secureInput($_GET['order_id'] ?? '', 'order_id');
$error    = '';
$order    = null;
$topupPackages = [];
$paymentResult = null;
$currentStep   = 'select_package';

$exchangeRate  = getCurrentExchangeRate();
$markupConfig  = getMarkupConfig();

if (session_status() === PHP_SESSION_NONE) session_start();
$csrf_token = generateCSRFToken();

// Generate kode unik preview yang konsisten dengan payment
try {
    $qris = new QRISPaymentGateway();
    $uniqueCodePreview = $qris->generateUniqueCode($pdo, $csrf_token);
} catch (Exception $e) {
    $hash = md5($csrf_token);
    $uniqueCodePreview = (hexdec(substr($hash, 0, 6)) % 900) + 100;
}

// ===== HANDLE CANCEL PAYMENT ===== (Ganti yang ini)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_payment"])) {
    $cancelOrderId = $_POST['order_id'] ?? '';
    if (!empty($cancelOrderId)) {
        // Update status ke cancelled
        $stmt = $pdo->prepare("UPDATE qris_payments SET status = 'cancelled' WHERE order_id = ? AND status = 'pending'");
        $stmt->execute([$cancelOrderId]);
        
        // ✅ FIXED - Extract ICCID dari order untuk redirect yang benar
        $stmt = $pdo->prepare("SELECT description FROM qris_payments WHERE order_id = ?");
        $stmt->execute([$cancelOrderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment && preg_match('/Topup eSIM (\d+)/', $payment['description'], $matches)) {
            $extractedIccid = $matches[1];
            
            // Get token dari esim_orders
            $stmt = $pdo->prepare("SELECT token FROM esim_orders WHERE iccid = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$extractedIccid]);
            $esimOrder = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($esimOrder) {
                // Redirect ke halaman pilih package
                header("Location: topup.php?token=" . urlencode($esimOrder['token']) . "&iccid=" . urlencode($extractedIccid));
                exit;
            }
        }
        
        // Fallback jika ga bisa extract - pake parameter yang ada
        if (!empty($token) && !empty($iccid)) {
            header("Location: topup.php?token=" . urlencode($token) . "&iccid=" . urlencode($iccid));
        } else {
            header("Location: topup.php");
        }
        exit;
    }
}

// ===== PROSES QRIS PAYMENT STATUS =====
if (!empty($orderId)) {
    try {
        $qris = new QRISPaymentGateway();
        $trx = $qris->getTransactionByOrderId($pdo, $orderId);

        if ($trx) {
            $qris->updateStatusExpired($pdo);
            $trx = $qris->getTransactionByOrderId($pdo, $orderId);

            $paymentResult = [
                'success'      => true,
                'order_id'     => $trx['order_id'],
                'base_amount'  => $trx['base_amount'],
                'unique_code'  => $trx['unique_code'],
                'total_amount' => $trx['final_amount'],
                'qris_code'    => $trx['qris_code'],
                'qr_code_url'  => $trx['qr_code_url'],
                'status'       => $trx['status'],
                'expired_at'   => $trx['expired_at'],
                'paid_at'      => $trx['paid_at'],
                'description'  => $trx['description'],
            ];

            switch ($trx['status']) {
                case 'paid':
                    $currentStep = 'topup_success';
                    break;
                case 'pending':
                    $currentStep = 'payment_pending';
                    break;
                case 'expired':
                    $currentStep = 'payment_expired';
                    break;
                case 'cancelled':
                    $currentStep = 'payment_cancelled';
                    break;
                default:
                    $currentStep = 'topup_failed';
                    break;
            }

            if (!empty($trx['description']) && preg_match('/Topup eSIM (\d+)/', $trx['description'], $matches)) {
                $extractedIccid = $matches[1];
                $order = dbQuery("SELECT * FROM esim_orders WHERE iccid = ? ORDER BY created_at DESC LIMIT 1", [$extractedIccid], false);
                if ($order) {
                    $iccid = $extractedIccid;
                    $token = $order['token'] ?? '';
                }
            }
        } else {
            $error = "Order tidak ditemukan!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// ===== AMBIL DATA ORDER ESIM =====
if (!empty($token) && !empty($iccid) && empty($orderId)) {
    try {
        $order = dbQuery("SELECT * FROM esim_orders WHERE token = ? AND iccid = ? ORDER BY created_at DESC LIMIT 1", [$token, $iccid], false);
        if ($order) {
            $apiResult = getPackageList("", "TOPUP", $order['packageCode'], $iccid);
            if (isset($apiResult["success"]) && $apiResult["success"] && isset($apiResult["obj"]["packageList"])) {
                $topupPackages = $apiResult["obj"]["packageList"];
                usort($topupPackages, function($a, $b) {
                    $volumeA = (float)$a["volume"] / (1024**3);
                    $volumeB = (float)$b["volume"] / (1024**3);
                    return $volumeA <=> $volumeB;
                });
            }
        } else {
            $error = 'Order tidak ditemukan!';
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// ✅ GANTI SELURUH BAGIAN create_payment (sekitar baris 450)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_payment"])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $error = 'Request tidak valid - silakan refresh halaman';
    } else {
        try {
            $selectedPackageCode = trim($_POST['package_code'] ?? '');
            if (empty($selectedPackageCode)) throw new Exception("Pilih paket terlebih dahulu.");

            // Cari package
            $selectedPackage = null;
            foreach ($topupPackages as $pkg) {
                if ($pkg['packageCode'] === $selectedPackageCode) {
                    $selectedPackage = $pkg;
                    break;
                }
            }
            if (!$selectedPackage) throw new Exception("Paket tidak ditemukan.");

            $volumeGB = round((float)$selectedPackage["volume"] / (1024**3), 1);
            $originalPriceUsd = (float)$selectedPackage["price"] / 10000; // Untuk kalkulasi display
            $finalPrice = calculateFinalPrice($originalPriceUsd, $exchangeRate, $volumeGB, $markupConfig);

            $orderIdBaru = 'TOPUP_' . date('ymdHis') . '_' . strtoupper(substr(uniqid(), -6));
            $desc = "Topup eSIM {$order['iccid']} - {$selectedPackage['name']} ({$volumeGB}GB)";

            // ✅ PRICE USD DALAM CENT (langsung dari API tanpa dibagi)
            $priceUsdCent = (int)$selectedPackage['price']; // Simpan dalam cent
            
            // ✅ GUNAKAN QRISPaymentGateway method yang sudah diperbaiki
            $qris = new QRISPaymentGateway();
            $paymentData = $qris->generateDynamicQRIS(
                $pdo,
                $orderIdBaru,
                $finalPrice,
                $priceUsdCent,
                $desc,
                $csrf_token,
                $selectedPackageCode
            );

            if ($paymentData['success']) {
                // Redirect ke halaman payment pending
                header("Location: topup.php?order_id=" . urlencode($orderIdBaru) . "&status=pending");
                exit();
            } else {
                throw new Exception("Gagal membuat payment");
            }

        } catch (Exception $e) {
            error_log("Payment creation error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#8B5CF6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>✨ eSIM Topup</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/topup.css?v=<?= file_exists('assets/css/topup.css') ? filemtime('assets/css/topup.css') : time() ?>">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">✨ eSIM Topup</div>
            <button class="theme-toggle" id="themeToggle">🌙</button>
        </header>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($order) && empty($orderId)): ?>
        <div class="alert alert-warning">Order tidak ditemukan!</div>
        <?php endif; ?>

        <?php if ($currentStep === 'select_package' && !empty($order)): ?>
        <!-- PACKAGE SELECTION -->
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr((string)($order['nama'] ?? ''), 0, 2)) ?></div>
            <div class="user-info">
                <h2 class="user-name"><?= htmlspecialchars($order['nama'] ?? 'NO-NAME') ?></h2>
                <div class="user-iccid">
                    <span>ICCID: <?= htmlspecialchars($iccid) ?></span>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-header">
                <h2 class="section-title">📦 Choose Package</h2>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="create_payment" value="1">
                
                <div class="package-grid">
                    <?php foreach ($topupPackages as $index => $package): ?>
                        <?php
                        $volumeGB = round((float)$package["volume"] / (1024**3), 1);
                        $originalPriceUsd = (float)$package["price"] / 10000;
                        $finalPrice = calculateFinalPrice($originalPriceUsd, $exchangeRate, $volumeGB, $markupConfig);
                        
                        // Simple icon logic
                        $icon = 'fas fa-wifi';
                        if ($volumeGB >= 5) {
                            $icon = 'fas fa-signal';
                        } elseif ($volumeGB >= 2) {
                            $icon = 'fas fa-wifi';
                        } else {
                            $icon = 'fas fa-mobile-alt';
                        }
                        ?>
                        <label class="package-card">
                            <input type="radio" name="package_code" value="<?= $package['packageCode'] ?>" 
                                data-price="<?= $finalPrice ?>" onchange="updateTotal()">
                            <div class="package-content">
                                <i class="package-icon <?= $icon ?>"></i>
                                <div class="package-size"><?= $volumeGB ?> GB</div>
                                <div class="package-name"><?= htmlspecialchars($package['name']) ?></div>
                                <div class="package-price">
                                    Rp <?= number_format((float) $finalPrice, 0, ',', '.') ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="payment-grid">
                    <div class="payment-group">
                        <h3>Payment Method</h3>
                        <div class="payment-methods">
                            <div class="payment-method selected">
                                <div class="payment-content">
                                    <div class="payment-icon">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">QRIS Payment</div>
                                        <div class="payment-fee">Kode Unik: +<?= $uniqueCodePreview ?></div>
                                        <div class="payment-total" id="paymentTotal">
                                            <i class="fas fa-calculator"></i>
                                            Total: Pilih paket dulu
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="payment_method" value="qris">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i>
                        <span class="btn-text">Lanjutkan Pembayaran</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($currentStep === 'payment_pending'): ?>
        <!-- PAYMENT PENDING -->
        <div class="payment-complete">
            <div class="payment-status pending">
                <div class="status-icon">⏳</div>
                <h2>Menunggu Pembayaran (QRIS)</h2>
                <p>Silakan scan QR di bawah & transfer sesuai nominal.</p>
                <div class="auto-check-info">
                    <div class="check-status" id="checkStatus">Node.js worker checking payment automatically...</div>
                    <div class="next-check">Next database check in: <span id="countdown">10</span>s</div>
                </div>
            </div>
            <div class="order-info">
                <div class="info-row"><span>Order ID:</span><span><?= htmlspecialchars($paymentResult['order_id']) ?></span></div>
                <div class="info-row"><span>Nominal Paket:</span><span>Rp <?= number_format((float)($paymentResult['base_amount']), 0, ',', '.') ?></span></div>
                <div class="info-row"><span>Kode Unik:</span><span>+<?= $paymentResult['unique_code'] ?></span></div>
                <div class="info-row"><span><b>Total Bayar:</b></span>
                    <span class="total-amount">Rp <?= number_format((int) $paymentResult['total_amount'], 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="payment-method-card">
                <h3>Scan QRIS:</h3>
                <div class="qr-container">
                    <img src="<?= $paymentResult['qr_code_url'] ?>" alt="QRIS" class="qr-image">
                </div>
                <div class="qr-info">
                    Scan pakai aplikasi e-wallet (GoPay, DANA, OVO, dll)<br>
                    <small>QR hanya berlaku <span id="expiredTimer"></span> menit</small>
                </div>
            </div>
            <button type="button" class="btn btn-success" onclick="manualCheck()">
                <i class="fas fa-sync-alt"></i>
                Cek Status Pembayaran
            </button>
            <div class="cancel-payment-section" style="margin-top:20px;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="order_id" value="<?= $paymentResult['order_id'] ?>">
                    <input type="hidden" name="cancel_payment" value="1">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel payment?')">
                        <i class="fas fa-times"></i>
                        Batalkan
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($currentStep === 'topup_success'): ?>
        <!-- SUCCESS -->
        <div class="payment-complete">
            <div class="payment-status paid">
                <div class="status-icon">🎉</div>
                <h2>Pembayaran Berhasil!</h2>
                <p>Topup eSIM telah berhasil diproses</p>
            </div>
            
            <div class="success-details">
                <div class="success-card">
                    <h3>✅ Payment Complete</h3>
                    <div class="success-info">
                        <div class="info-row">
                            <span>Order ID:</span>
                            <span><?= htmlspecialchars($paymentResult['order_id']) ?></span>
                        </div>
                        <div class="info-row">
                            <span>Total Bayar:</span>
                            <span>Rp <?= number_format((int) $paymentResult['total_amount'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                
                <?php 
                // ✅ SHOW TOPUP RESULT
                $stmt = $pdo->prepare("SELECT topup_processed, topup_result, esim_transaction_id FROM qris_payments WHERE order_id = ?");
                $stmt->execute([$paymentResult['order_id']]);
                $topupInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($topupInfo && $topupInfo['topup_processed'] == 1):
                    $topupResult = json_decode($topupInfo['topup_result'], true);
                ?>
                <div class="success-card">
                    <?php if (isset($topupResult['success']) && $topupResult['success']): ?>
                        <h3>📊 Topup Successful</h3>
                        <div class="success-info">
                            <div class="info-row">
                                <span>Topup Status:</span>
                                <span style="color: var(--success-color);">✅ Completed</span>
                            </div>
                            <?php if ($topupInfo['esim_transaction_id']): ?>
                            <div class="info-row">
                                <span>Topup Transaction:</span>
                                <span><?= htmlspecialchars($topupInfo['esim_transaction_id']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- ✅ UBAH BAGIAN INI - SEMBUNYIKAN ERROR DETAIL -->
                        <h3>⚠️ Topup Processing</h3>
                        <div class="success-info">
                            <div class="info-row">
                                <span>Topup Status:</span>
                                <span style="color: #ff9800;">⏳ Pending Manual Process</span>
                            </div>
                        </div>
                        <p style="text-align: center; margin: 20px 0;">
                            <i class="fas fa-info-circle" style="color: #2196F3;"></i><br>
                            <strong>Pembayaran berhasil!</strong><br>
                            Topup sedang diproses manual oleh admin kami.<br>
                            <small>Estimasi: 1-24 jam</small>
                        </p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="success-card">
                    <h3>⏳ Processing Topup</h3>
                    <p>Topup sedang diproses otomatis. Refresh halaman ini dalam beberapa detik.</p>
                    <button onclick="window.location.reload()" class="btn btn-secondary">🔄 Refresh</button>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <?php 
                // ✅ SIMPLIFIKASI LOGIC - JIKA ADA MASALAH TOPUP = CONTACT ADMIN
                $hasTopupIssue = false;
                if ($topupInfo && $topupInfo['topup_processed'] == 1) {
                    $topupResult = json_decode($topupInfo['topup_result'], true);
                    $hasTopupIssue = !isset($topupResult['success']) || !$topupResult['success'];
                } else {
                    $hasTopupIssue = true; // Belum diproses = ada issue
                }
                ?>
                
                <?php if ($hasTopupIssue): ?>
                    <!-- ✅ HANYA TAMPILKAN BUTTON WA UNTUK ADMIN -->
                    <a href="https://wa.me/6281325525646?text=Halo%20admin%2C%20saya%20sudah%20bayar%20topup%20eSIM%20dengan%20Order%20ID%3A%20<?= urlencode($paymentResult['order_id']) ?>%20tapi%20topup%20belum%20masuk.%20Mohon%20diproses%20manual.%20Terima%20kasih." 
                    class="btn btn-success" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                        Contact Admin for Manual Topup
                    </a>
                    
                    <?php if (!empty($token)): ?>
                    <a href="detail.php?token=<?= htmlspecialchars($token) ?>" class="btn btn-secondary">
                        <i class="fas fa-eye"></i>
                        Check eSIM Status
                    </a>
                    <?php else: ?>
                    <!-- <a href="index.php" class="btn btn-secondary">Back to Home</a> -->
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- ✅ JIKA TOPUP SUKSES - TAMPILKAN NORMAL BUTTONS -->
                    <?php if (!empty($token)): ?>
                    <a href="detail.php?token=<?= htmlspecialchars($token) ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        View eSIM Details
                    </a>
                    <a href="topup.php?token=<?= htmlspecialchars($token) ?>&iccid=<?= htmlspecialchars($iccid) ?>" class="btn btn-secondary">
                        <i class="fas fa-plus"></i>
                        Add More Data
                    </a>
                    <?php else: ?>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($currentStep === 'payment_expired'): ?>
        <!-- EXPIRED -->
        <div class="payment-complete">
            <div class="payment-status expired">
                <div class="status-icon">⏰</div>
                <h2>QRIS Kadaluarsa</h2>
                <p>Kode pembayaran telah kadaluarsa setelah 3 menit.</p>
            </div>
            
            <div class="action-buttons">
                <?php if (!empty($token) && !empty($iccid)): ?>
                <a href="topup.php?token=<?= htmlspecialchars($token) ?>&iccid=<?= htmlspecialchars($iccid) ?>" class="btn btn-primary">Generate QRIS Baru</a>
                <?php else: ?>
                <a href="index.php" class="btn btn-primary">Back to Home</a>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- FALLBACK -->
        <div class="alert alert-warning"><br>
            <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
        <?php endif; ?>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> eSIM Portal - Powered by Mimint</p>
        </footer>
    </div>

    <script>
        window.topupData = {
            currentStep: '<?= $currentStep ?>',
            orderId: '<?= $paymentResult['order_id'] ?? '' ?>',
            csrf_token: '<?= $csrf_token ?>',
            uniqueCode: <?= $uniqueCodePreview ?>,
            expiredAt: '<?= $paymentResult['expired_at'] ?? '' ?>'
        };
    </script>
    <script src="assets/js/topup.js?v=<?= file_exists('assets/js/topup.js') ? filemtime('assets/js/topup.js') : time() ?>"></script>
</body>
</html>