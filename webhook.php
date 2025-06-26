<?php
/**
 * Midtrans Payment Webhook Handler
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/config.php';
include 'includes/koneksi.php';
include 'includes/functions.php';
include 'includes/api.php';

// Get database connection
$dbConnection = isset($pdo) ? $pdo : $conn;

// Get raw POST data
$json = file_get_contents('php://input');
$notification = json_decode($json, true);

error_log("Midtrans Webhook received: " . $json);

if (empty($notification)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid notification']);
    exit();
}

try {
    // Get Midtrans settings
    $settings = getAppSettings($dbConnection);
    $serverKey = $settings['midtrans_server_key'] ?? '';
    $isProduction = ($settings['midtrans_is_production'] ?? '0') === '1';
    
    if (empty($serverKey)) {
        throw new Exception("Midtrans server key not configured");
    }
    
    // Verify signature
    $orderId = $notification['order_id'] ?? '';
    $statusCode = $notification['status_code'] ?? '';
    $grossAmount = $notification['gross_amount'] ?? '';
    $signatureKey = $notification['signature_key'] ?? '';
    
    $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
    
    if ($signatureKey !== $mySignature) {
        error_log("Invalid signature: Expected $mySignature, Got $signatureKey");
        throw new Exception("Invalid signature");
    }
    
    // Get transaction status from notification
    $transactionStatus = $notification['transaction_status'] ?? '';
    $paymentType = $notification['payment_type'] ?? '';
    $fraudStatus = $notification['fraud_status'] ?? '';
    
    error_log("Processing webhook for order: $orderId, status: $transactionStatus");
    
    // Determine final status
    $finalStatus = 'pending';
    if ($transactionStatus == 'capture') {
        if ($fraudStatus == 'challenge') {
            $finalStatus = 'challenge';
        } else if ($fraudStatus == 'accept') {
            $finalStatus = 'settlement';
        }
    } else if ($transactionStatus == 'settlement') {
        $finalStatus = 'settlement';
    } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
        $finalStatus = 'failure';
    } else if ($transactionStatus == 'pending') {
        $finalStatus = 'pending';
    }
    
    // Update database
    if (isset($pdo)) {
        $stmt = $pdo->prepare("UPDATE topup_orders SET 
            status = ?, 
            payment_type = ?, 
            midtrans_response = ?, 
            paid_at = CASE WHEN ? = 'settlement' THEN NOW() ELSE paid_at END,
            updated_at = NOW() 
            WHERE order_id = ?");
        $updateResult = $stmt->execute([$finalStatus, $paymentType, $json, $finalStatus, $orderId]);
    } else {
        $stmt = $conn->prepare("UPDATE topup_orders SET 
            status = ?, 
            payment_type = ?, 
            midtrans_response = ?, 
            paid_at = CASE WHEN ? = 'settlement' THEN NOW() ELSE paid_at END,
            updated_at = NOW() 
            WHERE order_id = ?");
        $stmt->bind_param("sssss", $finalStatus, $paymentType, $json, $finalStatus, $orderId);
        $updateResult = $stmt->execute();
        $stmt->close();
    }
    
    if (!$updateResult) {
        throw new Exception("Failed to update order status");
    }
    
    error_log("Order $orderId status updated to: $finalStatus");
    
    // If payment successful, trigger topup process
    if ($finalStatus === 'settlement') {
        processTopupAfterPayment($orderId, $dbConnection);
    }
    
    // Send success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification processed successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Process topup after successful payment
 */
function processTopupAfterPayment($orderId, $dbConnection) {
    try {
        // Get order details
        if (isset($GLOBALS['pdo'])) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM topup_orders WHERE order_id = ? AND status = 'settlement' AND topup_status != 'success'");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $dbConnection->prepare("SELECT * FROM topup_orders WHERE order_id = ? AND status = 'settlement' AND topup_status != 'success'");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
        }
        
        if (!$order) {
            error_log("Order not found or already processed: $orderId");
            return;
        }
        
        // Calculate amount for API
        $originalPriceUsd = $order['original_price_usd'];
        $amountCents = (int)($originalPriceUsd * 100);
        
        // Call topup API
        $topupResult = topUpEsim(
            $order['iccid'],
            $order['package_code'],
            $orderId,
            $amountCents
        );
        
        if (isset($topupResult['success']) && $topupResult['success']) {
            // Update topup status to success
            if (isset($GLOBALS['pdo'])) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE topup_orders SET topup_status = 'success', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $stmt->execute([json_encode($topupResult), $orderId]);
            } else {
                $stmt = $dbConnection->prepare("UPDATE topup_orders SET topup_status = 'success', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $topupResponseJson = json_encode($topupResult);
                $stmt->bind_param("ss", $topupResponseJson, $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            error_log("Topup completed successfully for order: $orderId");
            
        } else {
            // Update topup status to failed
            $errorMsg = $topupResult['errorMsg'] ?? 'Unknown error';
            if (isset($GLOBALS['pdo'])) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE topup_orders SET topup_status = 'failed', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $stmt->execute([json_encode($topupResult), $orderId]);
            } else {
                $stmt = $dbConnection->prepare("UPDATE topup_orders SET topup_status = 'failed', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $topupResponseJson = json_encode($topupResult);
                $stmt->bind_param("ss", $topupResponseJson, $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            error_log("Topup failed for order $orderId: $errorMsg");
        }
        
    } catch (Exception $e) {
        error_log("Error processing topup for order $orderId: " . $e->getMessage());
    }
}
?>
