<?php
declare(strict_types=1);
// Production mode - hide errors
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/config.php';

// Include semua yang diperlukan dalam urutan yang benar
include 'includes/koneksi.php';
include 'includes/functions.php';  // Harus sebelum api.php
include 'includes/api.php';
include 'includes/midtrans.php';

// ✅ INITIALIZE eSIM API FROM DATABASE
try {
    initializeEsimApiConfig($pdo);
    error_log("✅ eSIM API initialized successfully");
} catch (Exception $e) {
    // Log error securely without exposing details
    error_log("API initialization failed: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Service Error</title></head><body>';
    echo '<h1>Service Temporarily Unavailable</h1>';
    echo '<p>Please try again later. If the problem persists, contact support.</p>';
    echo '</body></html>';
    exit;
}

// Enhanced input validation and sanitization
$token = validateInput($_GET['token'] ?? '', 'token');
$iccid = validateInput($_GET['iccid'] ?? '', 'iccid');
$orderId = validateInput($_GET['order_id'] ?? '', 'order_id');
$statusParam = validateInput($_GET['status'] ?? '', 'status');

// Initialize variables
$order = null;
$topupPackages = [];
$paymentMethods = [];
$paymentResult = null;
$error = '';
$success = '';
$currentStep = 'select_package';

// Configuration
$exchangeRate = getCurrentExchangeRate();
$markupConfig = getMarkupConfig(); // Use function from koneksi.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Stronger CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';
    
    if (empty($sessionToken) || empty($postToken) || !hash_equals($sessionToken, $postToken)) {
        http_response_code(403);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['status'=>'error', 'message'=>'Security validation failed']);
        } else {
            die('Security validation failed. Please refresh the page.');
        }
        exit();
    }
}

// Rate limiting for security
function checkRateLimit($action = 'general') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    $file = sys_get_temp_dir() . "/{$key}.json";
    
    $limits = [
        'payment' => ['count' => 3, 'window' => 300],
        'status_check' => ['count' => 30, 'window' => 60],
        'general' => ['count' => 100, 'window' => 300]
    ];
    
    $limit = $limits[$action] ?? $limits['general'];
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && $data['count'] >= $limit['count'] && (time() - $data['time']) < $limit['window']) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded']);
            exit();
        }
    }
    
    $currentData = [
        'count' => ($data['count'] ?? 0) + 1,
        'time' => $data['time'] ?? time()
    ];
    
    if ((time() - $currentData['time']) >= $limit['window']) {
        $currentData = ['count' => 1, 'time' => time()];
    }
    
    file_put_contents($file, json_encode($currentData));
}

// Apply rate limiting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_payment'])) {
        checkRateLimit('payment');
    } elseif (isset($_POST['ajax_check_status'])) {
        checkRateLimit('status_check');
    }
}

// AJAX STATUS CHECK - PDO ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_check_status'])) {
    header('Content-Type: application/json');
    
    $checkOrderId = trim($_POST['order_id'] ?? '');
    
    if (empty($checkOrderId)) {
        echo json_encode(['status' => 'error', 'message' => 'Order ID required']);
        exit();
    }
    
    try {
        // Check current status in DB - PDO ONLY
        $currentOrder = dbQuery("SELECT status, topup_status FROM topup_orders WHERE order_id = ?", [$checkOrderId], false);
        
        if (!$currentOrder) {
            echo json_encode(['status' => 'error', 'message' => 'Order not found']);
            exit();
        }
        
        $paymentStatus = $currentOrder['status'];
        $topupStatus = $currentOrder['topup_status'] ?? 'pending';
        
        // If still pending, check Midtrans
        if ($paymentStatus === 'pending') {
            $settings = getAppSettings();
            $serverKey = $settings['midtrans_server_key'] ?? '';
            $isProduction = ($settings['midtrans_is_production'] ?? '0') === '1';
            
            if (!empty($serverKey)) {
                // Check Midtrans status
                $midtransStatus = checkMidtransPaymentStatus($checkOrderId, $serverKey, $isProduction);
                
                if ($midtransStatus) {
                    $transactionStatus = $midtransStatus['transaction_status'] ?? '';
                    
                    if ($transactionStatus === 'settlement') {
                        // Update payment status - PDO ONLY
                        dbQuery("UPDATE topup_orders SET status = 'settlement', paid_at = NOW() WHERE order_id = ?", [$checkOrderId]);
                        $paymentStatus = 'settlement';
                    }
                }
            }
        }
        
        // If payment settled but topup not done, do topup
        if ($paymentStatus === 'settlement' && $topupStatus === 'pending') {
            // Get order details for topup - PDO ONLY
            $orderDetails = dbQuery("SELECT * FROM topup_orders WHERE order_id = ?", [$checkOrderId], false);
            
            if ($orderDetails) {
                // Set topup processing - PDO ONLY
                dbQuery("UPDATE topup_orders SET topup_status = 'processing' WHERE order_id = ?", [$checkOrderId]);
                
                // Call topup API
                $amountCents = (int)($orderDetails['original_price_usd'] * 10000);
                $topupResult = topUpEsim(
                    $orderDetails['iccid'],
                    $orderDetails['package_code'],
                    $checkOrderId,
                    $amountCents
                );
                
                if (isset($topupResult['success']) && $topupResult['success']) {
                    // Topup success - PDO ONLY
                    dbQuery("UPDATE topup_orders SET topup_status = 'success', topup_response = ? WHERE order_id = ?", 
                           [json_encode($topupResult), $checkOrderId]);
                    $topupStatus = 'success';
                } else {
                    // Topup failed - PDO ONLY
                    dbQuery("UPDATE topup_orders SET topup_status = 'failed', topup_response = ? WHERE order_id = ?", 
                           [json_encode($topupResult), $checkOrderId]);
                    $topupStatus = 'failed';
                }
            }
        }
        
        // Return final status
        if ($paymentStatus === 'settlement' && $topupStatus === 'success') {
            echo json_encode([
                'status' => 'redirect',
                'url' => 'topup.php?order_id=' . $checkOrderId . '&status=sukses'
            ]);
        } elseif ($paymentStatus === 'settlement' && $topupStatus === 'failed') {
            echo json_encode([
                'status' => 'redirect',
                'url' => 'topup.php?order_id=' . $checkOrderId . '&status=gagal'
            ]);
        } elseif ($paymentStatus === 'failed') {
            echo json_encode([
                'status' => 'redirect',
                'url' => 'topup.php?order_id=' . $checkOrderId . '&status=gagal'
            ]);
        } else {
            echo json_encode([
                'status' => 'pending',
                'message' => 'Payment or topup still processing...'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("AJAX check error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit();
}

// CANCEL PAYMENT HANDLER - PDO ONLY
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_payment"])) {
    $cancelOrderId = trim($_POST['order_id'] ?? '');
    
    if (!empty($cancelOrderId)) {
        try {
            // Get order info - PDO ONLY
            $orderInfo = dbQuery("SELECT iccid, status FROM topup_orders WHERE order_id = ?", [$cancelOrderId], false);
            
            if ($orderInfo) {
                $cancelIccid = $orderInfo['iccid'];
                $currentStatus = $orderInfo['status'];
                
                // Get token from esim_orders - PDO ONLY
                $esimOrder = dbQuery("SELECT token FROM esim_orders WHERE iccid = ? ORDER BY created_at DESC LIMIT 1", 
                                   [$cancelIccid], false);
                
                $cancelToken = $esimOrder['token'] ?? $token;
                
                // CANCEL in Midtrans if status is still pending
                if ($currentStatus === 'pending') {
                    $settings = getAppSettings();
                    $serverKey = $settings['midtrans_server_key'] ?? '';
                    $isProduction = ($settings['midtrans_is_production'] ?? '0') === '1';
                    
                    if (!empty($serverKey)) {
                        $cancelResult = cancelMidtransPayment($cancelOrderId, $serverKey, $isProduction);
                        error_log("Midtrans cancel result for $cancelOrderId: " . json_encode($cancelResult));
                    }
                }
                
                // Update status to cancelled - PDO ONLY
                dbQuery("UPDATE topup_orders SET status = 'cancelled', updated_at = NOW() WHERE order_id = ?", [$cancelOrderId]);
                
                error_log("Order $cancelOrderId cancelled successfully, redirecting to token: $cancelToken, iccid: $cancelIccid");
                
                // Redirect
                header("Location: topup.php?token=" . urlencode($cancelToken) . "&iccid=" . urlencode($cancelIccid));
                exit();
                
            } else {
                throw new Exception("Order not found");
            }
            
        } catch (Exception $e) {
            error_log("Cancel payment error: " . $e->getMessage());
            header("Location: topup.php?token=$token&iccid=$iccid");
            exit();
        }
    }
}

// GET ORDER FROM TOKEN + ICCID - PDO ONLY
if (!empty($token) && !empty($iccid) && empty($order) && empty($orderId)) {
    try {
        $order = dbQuery("SELECT * FROM esim_orders WHERE token = ? AND iccid = ? ORDER BY created_at DESC LIMIT 1", 
                        [$token, $iccid], false);
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        // Get packages and payment methods
        $apiResult = getPackageList("", "TOPUP", $order['packageCode'], $iccid);
        if (isset($apiResult["success"]) && $apiResult["success"] && isset($apiResult["obj"]["packageList"])) {
            $topupPackages = $apiResult["obj"]["packageList"];
            usort($topupPackages, function($a, $b) {
                $volumeA = (float)$a["volume"] / (1024**3);
                $volumeB = (float)$b["volume"] / (1024**3);
                return $volumeA <=> $volumeB;
            });
        }
        
        // Get active payment methods
        $settings = getAppSettings();
        $serverKey = $settings['midtrans_server_key'] ?? '';
        $isProduction = ($settings['midtrans_is_production'] ?? '0') === '1';
        
        if (!empty($serverKey)) {
            $paymentMethods = getCachedActiveMidtransPaymentMethods($serverKey, $isProduction);
        } else {
            $paymentMethods = getDefaultPaymentMethods();
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// CREATE PAYMENT HANDLER  
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_payment"])) {
    try {
        $selectedPackageCode = trim($_POST['package_code'] ?? '');
        $selectedPaymentMethod = trim($_POST['payment_method'] ?? '');
        
        if (empty($selectedPackageCode) || empty($selectedPaymentMethod)) {
            throw new Exception("Please select package and payment method");
        }
        
        $selectedPackage = null;
        foreach ($topupPackages as $pkg) {
            if ($pkg['packageCode'] === $selectedPackageCode) {
                $selectedPackage = $pkg;
                break;
            }
        }
        
        if (!$selectedPackage) {
            throw new Exception("Package not found");
        }
        
        $paymentResult = createMidtransPayment(
            $selectedPackage,
            $order,
            $selectedPaymentMethod,
            $markupConfig,
            $exchangeRate,
            $pdo
        );
        
        if ($paymentResult['success']) {
            header("Location: topup.php?order_id=" . urlencode($paymentResult['order_id']) . "&status=pending");
            exit();
        } else {
            throw new Exception($paymentResult['message'] ?? 'Payment creation failed');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// GET PAYMENT STATUS BY ORDER ID - PDO ONLY
if (!empty($orderId)) {
    try {
        $paymentOrder = dbQuery("SELECT * FROM topup_orders WHERE order_id = ?", [$orderId], false);
        
        if ($paymentOrder) {
            $paymentResult = [
                'success' => true,
                'order_id' => $paymentOrder['order_id'],
                'va_number' => $paymentOrder['va_number'],
                'payment_url' => $paymentOrder['payment_url'],
                'qr_string' => $paymentOrder['qr_string'],
                'total_amount' => $paymentOrder['gross_amount'],
                'payment_method' => $paymentOrder['payment_type'] ?? $paymentOrder['payment_method'],
                'status' => $paymentOrder['status'],
                'topup_status' => $paymentOrder['topup_status'] ?? 'pending',
                'package_name' => $paymentOrder['package_name']
            ];
            
            // Status protection logic
            $dbPaymentStatus = $paymentOrder['status'];
            $dbTopupStatus = $paymentOrder['topup_status'] ?? 'pending';
            
            if ($dbTopupStatus === 'failed') {
                $currentStep = 'topup_failed';
            } elseif ($dbPaymentStatus === 'settlement' && $dbTopupStatus === 'success') {
                $currentStep = 'topup_success';
            } elseif ($dbPaymentStatus === 'failed' || $dbPaymentStatus === 'cancelled') {
                $currentStep = 'topup_failed';
            } elseif ($dbPaymentStatus === 'pending' || ($dbPaymentStatus === 'settlement' && $dbTopupStatus === 'pending')) {
                $currentStep = 'payment_pending';
            } else {
                $currentStep = 'payment_pending';
            }
            
            // Status URL validation
            if ($statusParam === 'sukses' && $dbTopupStatus !== 'success') {
                $redirectStatus = ($dbTopupStatus === 'failed') ? 'gagal' : 'pending';
                header("Location: topup.php?order_id=" . urlencode($orderId) . "&status=" . $redirectStatus);
                exit();
            }
            
            if ($statusParam === 'gagal' && $dbTopupStatus === 'success') {
                header("Location: topup.php?order_id=" . urlencode($orderId) . "&status=sukses");
                exit();
            }
            
            error_log("Order access: {$orderId} - DB Status: {$dbPaymentStatus}/{$dbTopupStatus} - URL Status: {$statusParam} - Final Step: {$currentStep}");
            
        } else {
            throw new Exception("Order not found");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✨ eSIM Topup</title>
    <link rel="stylesheet" href="assets/css/topup.css">
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

        <?php if ($currentStep === 'select_package'): ?>
        <!-- PACKAGE SELECTION -->
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr((string)($order['nama'] ?? ''), 0, 2)) ?></div>
            <div class="user-info">
                <h2 class="user-name"><?= htmlspecialchars($order['nama']) ?></h2>
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
                    <?php foreach ($topupPackages as $package): ?>
                        <?php
                        $volumeGB = round((float)$package["volume"] / (1024**3), 1);
                        $originalPriceUsd = (float)$package["price"] / 10000;
                        $finalPrice = calculateFinalPrice($originalPriceUsd, $exchangeRate, $volumeGB, $markupConfig);
                        ?>
                        <label class="package-card">
                            <input type="radio" name="package_code" value="<?= $package['packageCode'] ?>">
                            <div class="package-content">
                                <div class="package-size"><?= $volumeGB ?> GB</div>
                                <div class="package-name"><?= htmlspecialchars($package['name']) ?></div>
                                <div class="package-price">Rp <?= number_format($finalPrice, 0, ',', '.') ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="payment-grid">
                    <div class="payment-group">
                        <h3>Payment Method</h3>
                        <div class="payment-methods">
                            <?php foreach ($paymentMethods as $code => $method): ?>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="<?= $code ?>">
                                <div class="payment-content">
                                    <div class="payment-icon"><?= $method['icon'] ?></div>
                                    <div class="payment-info">
                                        <div class="payment-name"><?= $method['name'] ?></div>
                                        <div class="payment-fee">Fee: Rp <?= number_format($method['fee'], 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Payment</button>
                </div>
            </form>
        </div>

        <?php elseif ($currentStep === 'payment_pending'): ?>
        <!-- PAYMENT PENDING -->
        <div class="payment-complete">
            <div class="payment-status pending">
                <div class="status-icon">⏳</div>
                <h2>Payment Pending</h2>
                <p>Complete your payment below</p>
            </div>
            
            <div class="order-info">
                <div class="info-row">
                    <span>Order ID:</span>
                    <span><?= htmlspecialchars($paymentResult['order_id']) ?></span>
                </div>
                <div class="info-row">
                    <span>Amount:</span>
                    <span>Rp <?= number_format((float)$paymentResult['total_amount'], 0, ',', '.') ?></span>
                </div>
            </div>

            <?php if (!empty($paymentResult['va_number'])): ?>
            <div class="payment-method-card">
                <h3>🏦 Virtual Account</h3>
                <div class="va-number">
                    <span class="va-digits"><?= $paymentResult['va_number'] ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($paymentResult['payment_url'])): ?>
            <div class="payment-method-card">
                <h3>📱 QR Payment</h3>
                <div class="payment-button-container">
                    <a href="<?= htmlspecialchars($paymentResult['payment_url']) ?>" target="_blank" class="payment-btn-large">
                        Open Payment
                    </a>
                </div>
                
                <?php if (!empty($paymentResult['qr_string'])): ?>
                <div class="qr-section">
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($paymentResult['qr_string']) ?>" alt="QR Code">
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Cancel Payment -->
            <div class="cancel-payment-section">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="order_id" value="<?= $paymentResult['order_id'] ?>">
                    <input type="hidden" name="cancel_payment" value="1">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel payment?')">
                        Cancel & Choose Another Package
                    </button>
                </form>
            </div>

            <!-- Status Checker -->
            <div class="status-checker">
                <div class="status-check-header">
                    <h3>🔄 Payment Status</h3>
                    <div class="auto-refresh">
                        <span id="statusText">Checking payment status...</span>
                        <div class="refresh-timer" id="refreshTimer">30</div>
                    </div>
                </div>
                
                <button onclick="checkPaymentStatus()" class="btn btn-secondary" id="checkStatusBtn">
                    Check Status Now
                </button>
            </div>
        </div>

        <?php elseif ($currentStep === 'topup_success'): ?>
        <!-- SUCCESS -->
        <div class="payment-complete">
            <div class="payment-status paid">
                <div class="status-icon">🎉</div>
                <h2>Topup Successful!</h2>
                <p>Your eSIM has been topped up successfully</p>
            </div>
            
            <div class="success-details">
                <div class="success-card">
                    <h3>✅ Topup Complete</h3>
                    <div class="success-info">
                        <div class="info-row">
                            <span>Package:</span>
                            <span><?= htmlspecialchars($paymentResult['package_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span>Order ID:</span>
                            <span><?= htmlspecialchars($paymentResult['order_id']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="detail.php?token=<?= htmlspecialchars($token) ?>" class="btn btn-primary">View eSIM Details</a>
                <a href="topup.php?token=<?= htmlspecialchars($token) ?>&iccid=<?= htmlspecialchars($iccid) ?>" class="btn btn-secondary">Add More Data</a>
            </div>
        </div>

        <?php elseif ($currentStep === 'topup_failed'): ?>
        <!-- FAILED - SIMPLE VERSION TANPA DB CHANGES -->
        <div class="payment-complete">
            <div class="payment-status" style="background: linear-gradient(135deg, #ef4444, #ff6b6b);">
                <div class="status-icon">⚠️</div>
                <h2>Topup Process Failed</h2>
                <p>Your payment was successful, but the topup process encountered an issue.</p>
            </div>
            
            <div class="failure-card">
                <h3>❌ Topup Status: PROCESSING FAILED</h3>
                <div class="failure-explanation">
                    <div class="explanation-header">
                        <p>What happened?</p>
                    </div>
                    
                    <div class="status-steps">
                        <div class="status-step success">
                            <div class="step-icon">✅</div>
                            <div class="step-text">Your payment was successfully processed</div>
                        </div>
                        
                        <div class="status-step failed">
                            <div class="step-icon">❌</div>
                            <div class="step-text">The eSIM topup process failed due to technical issues</div>
                        </div>
                        
                        <div class="status-step processing">
                            <div class="step-icon">🔄</div>
                            <div class="step-text">Our team will process this manually within 1-24 hours</div>
                        </div>
                    </div>
                    
                    <div class="reassurance-message">
                        <div class="message-icon">💡</div>
                        <div class="message-text">
                            <strong>No action needed from you!</strong> We'll automatically retry the topup process.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <!-- TOMBOL BANTUAN WA - GANTI NOMOR SESUAI KEBUTUHAN -->
                <a href="https://wa.me/6281325525646?text=Hi%2C%20butuh%20bantuan%20order%20<?= urlencode($paymentResult['order_id']) ?>%20-%20payment%20sukses%20tapi%20topup%20gagal.%20Mohon%20diproses%20manual.%20Terima%20kasih." 
                target="_blank" 
                class="btn btn-success">
                    📱 Contact Support WhatsApp
                </a>
                
                <!-- TOMBOL KEMBALI SIMPLE -->
                <?php if (!empty($token)): ?>
                    <a href="detail.php?token=<?= htmlspecialchars($token) ?>" class="btn btn-secondary">
                        🔙 Back to eSIM Details
                    </a>
                <?php elseif (!empty($iccid)): ?>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        🔙 Go Back
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-secondary">
                        🏠 Back to Home
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- INFO BANTUAN -->
            <div class="support-info">
                <div class="info-card">
                    <h4>🆘 Need Immediate Help?</h4>
                    <div class="support-channels">
                        <div class="support-item">
                            <strong>📱 WhatsApp:</strong> +62 813-2552-5646
                        </div>
                        <div class="support-item">
                            <strong>📧 Email:</strong> support@hanisyaastore.com
                        </div>
                    </div>
                    <div class="support-note">
                        <p><small>💡 <strong>Tip:</strong> Include your Order ID when contacting support for faster assistance.</small></p>
                    </div>
                </div>
            </div>
            
            <!-- STATUS REFRESH WARNING -->
            <div class="warning-info">
                <div class="warning-card">
                    <p>⚠️ <strong>Note:</strong> This order has failed and cannot be changed. Please contact support if you believe this is an error.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> eSIM Portal</p>
        </footer>
    </div>

    <script>
        window.topupData = {
            currentStep: '<?= $currentStep ?>',
            orderId: '<?= $paymentResult['order_id'] ?? '' ?>',
            csrf_token: '<?= $csrf_token ?>'
        };
    </script>
    <script src="assets/js/topup.js"></script>
</body>
</html>
