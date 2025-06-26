<?php
/**
 * Manual Payment Status Check Endpoint
 */
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/config.php';
include 'includes/koneksi.php';
include 'includes/functions.php';
include 'includes/midtrans.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON decode
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format']);
    exit();
}

// Sanitize and validate order_id
$orderId = isset($input['order_id']) ? trim($input['order_id']) : '';
$csrfToken = isset($input['csrf_token']) ? trim($input['csrf_token']) : '';

// Validate order_id format (assuming alphanumeric with dashes/underscores)
if (empty($orderId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $orderId) || strlen($orderId) > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID format']);
    exit();
}

// Validate CSRF token
if (!verifyCSRFToken($csrfToken)) {
    logSecurityEvent("Invalid CSRF token for order check: $orderId", 'warning');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request token']);
    exit();
}

$dbConnection = isset($pdo) ? $pdo : $conn;

try {
    error_log("Manual check requested for: " . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8'));
    
    // Update status from Midtrans with error handling
    $updated = false;
    try {
        $updated = updatePaymentStatusFromCheck($orderId, $dbConnection);
    } catch (Exception $e) {
        error_log("Failed to update payment status from Midtrans: " . $e->getMessage());
        // Continue to check current status even if update fails
    }
    
    // Get latest status from database with proper error handling
    $orderStatus = null;
    
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT status, topup_status FROM topup_orders WHERE order_id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Database prepare failed");
        }
        $stmt->execute([$orderId]);
        $orderStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->prepare("SELECT status, topup_status FROM topup_orders WHERE order_id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Database prepare failed");
        }
        $stmt->bind_param("s", $orderId);
        if (!$stmt->execute()) {
            throw new Exception("Database execute failed");
        }
        $result = $stmt->get_result();
        $orderStatus = $result->fetch_assoc();
        $stmt->close();
    }
    
    if (!$orderStatus) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }
    
    // Sanitize output data
    $paymentStatus = htmlspecialchars($orderStatus['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
    $topupStatus = htmlspecialchars($orderStatus['topup_status'] ?? 'pending', ENT_QUOTES, 'UTF-8');
    
    // Validate status values to prevent unexpected data
    $validPaymentStatuses = ['pending', 'settlement', 'failed', 'cancelled', 'expired'];
    $validTopupStatuses = ['pending', 'success', 'failed'];
    
    if (!in_array($paymentStatus, $validPaymentStatuses)) {
        $paymentStatus = 'unknown';
    }
    
    if (!in_array($topupStatus, $validTopupStatuses)) {
        $topupStatus = 'pending';
    }
    
    // Return appropriate response based on status
    if ($paymentStatus === 'settlement') {
        if ($topupStatus === 'success') {
            echo json_encode([
                'status' => 'completed',
                'payment_status' => $paymentStatus,
                'topup_status' => $topupStatus,
                'message' => 'Payment and topup completed'
            ]);
        } elseif ($topupStatus === 'failed') {
            echo json_encode([
                'status' => 'topup_failed',
                'payment_status' => $paymentStatus,
                'topup_status' => $topupStatus,
                'message' => 'Payment successful but topup failed'
            ]);
        } else {
            echo json_encode([
                'status' => 'payment_success',
                'payment_status' => $paymentStatus,
                'topup_status' => $topupStatus,
                'message' => 'Payment successful, processing topup'
            ]);
        }
    } elseif ($paymentStatus === 'failed') {
        echo json_encode([
            'status' => 'failed',
            'payment_status' => $paymentStatus,
            'topup_status' => $topupStatus,
            'message' => 'Payment failed'
        ]);
    } else {
        echo json_encode([
            'status' => 'pending',
            'payment_status' => $paymentStatus,
            'topup_status' => $topupStatus,
            'message' => 'Payment still pending',
            'updated' => $updated
        ]);
    }
    
} catch (Exception $e) {
    // Log detailed error but don't expose to user
    error_log("Check status error for order $orderId: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    
    // Generic error message for user
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to check payment status at this time'
    ]);
}
?>
