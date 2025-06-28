<?php
error_reporting(0);
ini_set('display_errors', '0');
define('ALLOWED_ACCESS', true);

// Session setup
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

securityMiddleware();
setSecurityHeaders();

try {
    include '../includes/koneksi.php';
    include '../includes/functions.php';
    include '../includes/api.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    logSecurityEvent("Unauthorized access attempt to topup monitor", 'warning');
    header("Location: login.php");
    exit();
}

// Regenerate session ID
if (!isset($_SESSION['last_session_regenerate']) || (time() - $_SESSION['last_session_regenerate']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_session_regenerate'] = time();
}

$successMessage = "";
$errorMessage = "";

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($statusFilter && $statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    switch ($dateFilter) {
        case 'today':
            $whereConditions[] = "DATE(created_at) = CURDATE()";
            break;
        case 'yesterday':
            $whereConditions[] = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if ($searchQuery) {
    $whereConditions[] = "(order_id LIKE ? OR description LIKE ? OR esim_transaction_id LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Get total count
try {
    $countSql = "SELECT COUNT(*) as total FROM qris_payments $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    $totalPages = ceil($totalOrders / $limit);
} catch (Exception $e) {
    error_log("Error getting count: " . $e->getMessage());
    $totalOrders = 0;
    $totalPages = 1;
}

// Get topup orders
try {
    $sql = "SELECT * FROM qris_payments $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([...$params, $limit, $offset]);
    $topupOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching topup orders: " . $e->getMessage());
    $topupOrders = [];
}

// Get statistics
try {
    $statsQuery = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_orders,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(CASE WHEN status = 'paid' THEN final_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN topup_processed = 1 THEN 1 ELSE 0 END) as processed_topups,
            SUM(CASE WHEN topup_processed = 1 AND JSON_EXTRACT(topup_result, '$.success') = true THEN 1 ELSE 0 END) as successful_topups
        FROM qris_payments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $statsStmt = $pdo->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
    $stats = [
        'total_orders' => 0,
        'paid_orders' => 0,
        'pending_orders' => 0,
        'expired_orders' => 0,
        'cancelled_orders' => 0,
        'total_revenue' => 0,
        'processed_topups' => 0,
        'successful_topups' => 0
    ];
}

// Handle actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent("CSRF token validation failed in topup monitor", 'warning');
        $errorMessage = "Invalid session. Please try again.";
    } else {
        $action = $_POST["action"] ?? '';
        
        switch ($action) {
            case "retry_topup":
                try {
                    $orderId = $_POST['order_id'] ?? '';
                    if (empty($orderId)) {
                        throw new Exception("Order ID is required");
                    }
                    
                    // Get payment details
                    $stmt = $pdo->prepare("SELECT * FROM qris_payments WHERE order_id = ? AND status = 'paid'");
                    $stmt->execute([$orderId]);
                    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$payment) {
                        throw new Exception("Payment not found or not paid");
                    }
                    
                    // Reset topup processed flag
                    $stmt = $pdo->prepare("UPDATE qris_payments SET topup_processed = 0, topup_result = NULL WHERE order_id = ?");
                    $stmt->execute([$orderId]);
                    
                    $successMessage = "✅ Topup retry initiated for order: $orderId";
                    
                } catch (Exception $e) {
                    error_log("Error retrying topup: " . $e->getMessage());
                    $errorMessage = "❌ Error: " . $e->getMessage();
                }
                break;
                
            case "cancel_order":
                try {
                    $orderId = $_POST['order_id'] ?? '';
                    if (empty($orderId)) {
                        throw new Exception("Order ID is required");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE qris_payments SET status = 'cancelled' WHERE order_id = ? AND status = 'pending'");
                    $stmt->execute([$orderId]);
                    
                    if ($stmt->rowCount() > 0) {
                        $successMessage = "✅ Order cancelled: $orderId";
                    } else {
                        throw new Exception("Order not found or cannot be cancelled");
                    }
                    
                } catch (Exception $e) {
                    error_log("Error cancelling order: " . $e->getMessage());
                    $errorMessage = "❌ Error: " . $e->getMessage();
                }
                break;
            // ✅ TAMBAH INI - Manual Topup Process
            case "manual_topup_success":
                try {
                    $orderId = $_POST['order_id'] ?? '';
                    $notes = trim($_POST['notes'] ?? '');
                    
                    if (empty($orderId)) {
                        throw new Exception("Order ID is required");
                    }
                    
                    // Get payment details
                    $stmt = $pdo->prepare("SELECT * FROM qris_payments WHERE order_id = ? AND status = 'paid'");
                    $stmt->execute([$orderId]);
                    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$payment) {
                        throw new Exception("Payment not found or not paid");
                    }
                    
                    // Create manual success result
                    $manualResult = [
                        'success' => true,
                        'manual_process' => true,
                        'processed_by' => 'admin_manual',
                        'processed_at' => date('Y-m-d H:i:s'),
                        'notes' => $notes,
                        'method' => 'manual_adjustment'
                    ];
                    
                    // Update topup as successful
                    $stmt = $pdo->prepare("
                        UPDATE qris_payments 
                        SET topup_processed = 1, 
                            topup_result = ?,
                            esim_transaction_id = ?
                        WHERE order_id = ?
                    ");
                    $manualTxnId = 'MANUAL_' . $orderId . '_' . time();
                    $stmt->execute([
                        json_encode($manualResult),
                        $manualTxnId,
                        $orderId
                    ]);
                    
                    $successMessage = "✅ Order marked as successfully topped up: $orderId";
                    
                } catch (Exception $e) {
                    error_log("Error manual topup success: " . $e->getMessage());
                    $errorMessage = "❌ Error: " . $e->getMessage();
                }
                break;
                
            // ✅ TAMBAH INI - Get Topup Packages for Modal
            case "get_topup_packages":
                header('Content-Type: application/json');
                try {
                    $orderId = $_POST['order_id'] ?? '';
                    if (empty($orderId)) {
                        throw new Exception("Order ID is required");
                    }
                    
                    // Get payment details
                    $stmt = $pdo->prepare("SELECT description FROM qris_payments WHERE order_id = ?");
                    $stmt->execute([$orderId]);
                    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$payment) {
                        throw new Exception("Payment not found");
                    }
                    
                    // Extract ICCID from description
                    if (!preg_match('/Topup eSIM (\d+)/', $payment['description'], $matches)) {
                        throw new Exception("Cannot extract ICCID from description");
                    }
                    
                    $iccid = $matches[1];
                    
                    // Get eSIM details first
                    $esimDetails = queryEsimDetails('', $iccid);
                    $originalPackageCode = "";
                    
                    if (isset($esimDetails["success"]) && $esimDetails["success"] && 
                        isset($esimDetails["obj"]["esimList"]) && count($esimDetails["obj"]["esimList"]) > 0) {
                        
                        $esim = $esimDetails["obj"]["esimList"][0];
                        $originalPackageCode = isset($esim["packageCode"]) ? $esim["packageCode"] : "";
                        
                        // Get topup packages
                        $topupPackages = getPackageList("", "TOPUP", $originalPackageCode, $iccid);
                        
                        if (isset($topupPackages["success"]) && $topupPackages["success"] && 
                            isset($topupPackages["obj"]["packageList"])) {
                            
                            echo json_encode([
                                'success' => true,
                                'packages' => $topupPackages["obj"]["packageList"],
                                'iccid' => $iccid
                            ]);
                        } else {
                            throw new Exception("Cannot get topup packages: " . ($topupPackages["errorMsg"] ?? "Unknown error"));
                        }
                    } else {
                        throw new Exception("Cannot get eSIM details: " . ($esimDetails["errorMsg"] ?? "Unknown error"));
                    }
                    
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                exit;
                break;
                
            // ✅ TAMBAH INI - Execute Manual Topup
            case "execute_topup":
                header('Content-Type: application/json');
                try {
                    $orderId = $_POST['order_id'] ?? '';
                    $topUpCode = $_POST['topUpCode'] ?? '';
                    $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
                    
                    if (empty($orderId) || empty($topUpCode) || $amount <= 0) {
                        throw new Exception('Missing required parameters');
                    }
                    
                    // Get payment details
                    $stmt = $pdo->prepare("SELECT description FROM qris_payments WHERE order_id = ?");
                    $stmt->execute([$orderId]);
                    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$payment) {
                        throw new Exception("Payment not found");
                    }
                    
                    // Extract ICCID
                    if (!preg_match('/Topup eSIM (\d+)/', $payment['description'], $matches)) {
                        throw new Exception("Cannot extract ICCID from description");
                    }
                    
                    $iccid = $matches[1];
                    $transactionId = 'MANUAL_' . $orderId . '_' . time();
                    
                    // Execute topup
                    $result = topUpEsim($iccid, $topUpCode, $transactionId, $amount);
                    
                    if (isset($result['success']) && $result['success']) {
                        // Update database with success
                        $stmt = $pdo->prepare("
                            UPDATE qris_payments 
                            SET topup_processed = 1, 
                                topup_result = ?,
                                esim_transaction_id = ?
                            WHERE order_id = ?
                        ");
                        
                        $topupResult = [
                            'success' => true,
                            'manual_process' => true,
                            'processed_by' => 'admin_manual',
                            'processed_at' => date('Y-m-d H:i:s'),
                            'topup_code' => $topUpCode,
                            'amount' => $amount,
                            'api_result' => $result
                        ];
                        
                        $stmt->execute([
                            json_encode($topupResult),
                            $transactionId,
                            $orderId
                        ]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Topup berhasil dieksekusi',
                            'transaction_id' => $transactionId
                        ]);
                    } else {
                        throw new Exception($result['errorMsg'] ?? 'Gagal melakukan topup');
                    }
                    
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                exit;
                break;
        }
    }
}

$csrf_token = generateCSRFToken();

// Helper function for status styling
function getStatusClass($status) {
    switch ($status) {
        case 'paid': return 'success';
        case 'pending': return 'warning';
        case 'expired': return 'danger';
        case 'cancelled': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'paid': return '✅';
        case 'pending': return '⏳';
        case 'expired': return '⏰';
        case 'cancelled': return '❌';
        default: return '❓';
    }
}

function formatRelativeTime($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Topup Monitor - eSIM Portal Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/topup.css?v=<?= filemtime('assets/js/topup.css') ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Topup orders monitoring for eSIM Portal">
</head>
<body>

<!-- Dark Mode Toggle -->
<button class="theme-toggle-floating" id="themeToggle">
    <span id="themeIcon">🌙</span>
</button>

<!-- Main Content -->
<main class="main-content">
    <!-- Header -->
    <section class="topup-header">
        <div class="header-content">
            <h1 class="topup-title">📊 Topup Monitor</h1>
            <p class="topup-subtitle">Monitor and manage QRIS topup orders & transactions</p>
            <div class="header-actions">
                <button class="btn-primary" onclick="refreshData()">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Refresh</span>
                </button>
                <button class="btn-secondary" onclick="exportData()">
                    <span class="btn-icon">📊</span>
                    <span class="btn-text">Export</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Messages -->
    <?php if ($successMessage): ?>
    <div class="message success">
        <span class="message-icon">✅</span>
        <span class="message-text"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="message-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
    <div class="message error">
        <span class="message-icon">❌</span>
        <span class="message-text"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="message-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-trend">Last 30 days</div>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['paid_orders']) ?></div>
                <div class="stat-label">Paid Orders</div>
                <div class="stat-trend">Revenue: Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></div>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['pending_orders']) ?></div>
                <div class="stat-label">Pending Orders</div>
                <div class="stat-trend">Awaiting payment</div>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">🔄</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['successful_topups']) ?></div>
                <div class="stat-label">Successful Topups</div>
                <div class="stat-trend">of <?= number_format($stats['processed_topups']) ?> processed</div>
            </div>
        </div>
    </section>

    <!-- Filters and Search -->
    <section class="filters-section">
        <div class="filters-header">
            <h3>📋 Filter & Search</h3>
            <div class="filter-actions">
                <button class="btn-secondary" onclick="clearFilters()">
                    <span class="btn-icon">🗑️</span>
                    <span class="btn-text">Clear</span>
                </button>
            </div>
        </div>
        
        <form method="GET" class="filters-form" id="filtersForm">
            <div class="filter-group">
                <label for="search">🔍 Search</label>
                <input type="text" id="search" name="search" 
                    value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" 
                    placeholder="Search by order ID, description, transaction ID...">
            </div>
            
            <div class="filter-group">
                <label for="status">📊 Status</label>
                <select id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>✅ Paid</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>⏰ Expired</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date">📅 Date Range</label>
                <select id="date" name="date">
                    <option value="">All Time</option>
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= $dateFilter === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 days</option>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn-primary">
                    <span class="btn-icon">🔍</span>
                    <span class="btn-text">Apply Filters</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Orders Table -->
    <section class="orders-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">📋</span>
                Topup Orders
                <span class="results-count">(<?= number_format($totalOrders) ?> results)</span>
            </h2>
        </div>
        
        <?php if (empty($topupOrders)): ?>
        <div class="empty-state">
            <div class="empty-content">
                <div class="empty-icon">📊</div>
                <div class="empty-text">No topup orders found</div>
                <div class="empty-subtext">Try adjusting your filters or check back later</div>
            </div>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order Details</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Topup Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topupOrders as $order): ?>
                    <?php
                    $topupResult = null;
                    $topupSuccess = false;
                    if ($order['topup_result']) {
                        $topupResult = json_decode($order['topup_result'], true);
                        $topupSuccess = isset($topupResult['success']) && $topupResult['success'];
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="order-details">
                                <div class="order-id"><?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="order-description"><?= htmlspecialchars($order['description'] ?: 'No description', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($order['esim_transaction_id']): ?>
                                <div class="transaction-id">TXN: <?= htmlspecialchars($order['esim_transaction_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="amount-details">
                                <div class="final-amount">Rp <?= number_format($order['final_amount'], 0, ',', '.') ?></div>
                                <div class="amount-breakdown">
                                    Base: Rp <?= number_format($order['base_amount'], 0, ',', '.') ?>
                                    + <?= $order['unique_code'] ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?= getStatusClass($order['status']) ?>">
                                <?= getStatusIcon($order['status']) ?> <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="topup-status">
                                <?php if ($order['topup_processed'] == 1): ?>
                                    <?php if ($topupSuccess): ?>
                                        <span class="topup-badge success">✅ Success</span>
                                    <?php else: ?>
                                        <span class="topup-badge error">❌ Failed</span>
                                        <?php if (isset($topupResult['error']) || isset($topupResult['errorMsg'])): ?>
                                        <div class="error-detail"><?= htmlspecialchars($topupResult['error'] ?? $topupResult['errorMsg'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php elseif ($order['status'] === 'paid'): ?>
                                    <span class="topup-badge warning">⏳ Processing</span>
                                <?php else: ?>
                                    <span class="topup-badge secondary">➖ Not processed</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="time-details">
                                <div class="created-time"><?= formatRelativeTime($order['created_at']) ?></div>
                                <div class="created-date"><?= date('M j, H:i', strtotime($order['created_at'])) ?></div>
                                <?php if ($order['paid_at']): ?>
                                <div class="paid-time">Paid: <?= formatRelativeTime($order['paid_at']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($order['status'] === 'paid' && (!$order['topup_processed'] || !$topupSuccess)): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="action" value="retry_topup">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn-action success" title="Retry Topup">
                                        🔄
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this order?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn-action danger" title="Cancel Order">
                                        ❌
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <button class="btn-action info" onclick="showOrderDetails('<?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?>')" title="View Details">
                                    👁️
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Showing <?= number_format($offset + 1) ?> - <?= number_format(min($offset + $limit, $totalOrders)) ?> of <?= number_format($totalOrders) ?> orders
            </div>
            
            <div class="pagination-buttons">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn-pagination">
                    ← Previous
                </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="btn-pagination <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn-pagination">
                    Next →
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </section>
</main>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Order Details</h3>
            <button class="modal-close" onclick="closeOrderModal()">×</button>
        </div>
        <div class="modal-body" id="orderModalBody">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

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
    <a href="esim.php" class="nav-item">
        <span class="nav-icon">📱</span>
        <span class="nav-label">Packages</span>
    </a>
    <a href="topup.php" class="nav-item active">
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

<script>
// Pass PHP data to JavaScript
window.topupData = <?= json_encode([
    'orders' => $topupOrders,
    'csrf_token' => $csrf_token
]) ?>;
</script>
<script src="assets/js/topup.js?v=<?= filemtime('assets/js/topup.js') ?>"></script>
</body>
</html>