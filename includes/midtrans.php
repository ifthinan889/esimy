<?php
/**
 * Midtrans Core API Implementation
 * Documentation: https://docs.midtrans.com/en/core-api/overview
 */

require_once 'includes/functions.php';

class MidtransAPI {
    private $serverKey;
    private $isProduction;
    private $baseUrl;
    
    public function __construct($serverKey, $isProduction = true) {
        $this->serverKey = $serverKey;
        $this->isProduction = $isProduction;
        $this->baseUrl = $isProduction 
            ? 'https://api.midtrans.com/v2/' 
            : 'https://api.sandbox.midtrans.com/v2/';
    }
    
    /**
     * Create payment transaction
     */
    public function createTransaction($params) {
        $url = $this->baseUrl . 'charge';
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Enhanced logging
        error_log("Midtrans API - URL: $url");
        error_log("Midtrans API - HTTP Code: $httpCode");
        error_log("Midtrans API - Raw Response: " . substr($response, 0, 1000));
        error_log("Midtrans API - CURL Info: " . json_encode($info));
        
        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }
        
        if (empty($response)) {
            throw new Exception("Empty response from Midtrans (HTTP $httpCode)");
        }
        
        // Check if response is HTML (error page)
        if (strpos(trim($response), '<') === 0) {
            throw new Exception("Received HTML response instead of JSON (HTTP $httpCode): " . substr(strip_tags($response), 0, 200));
        }
        
        $result = json_decode($response, true);
        
        if ($result === null) {
            $jsonError = json_last_error_msg();
            throw new Exception("Invalid JSON response: $jsonError. Raw response: " . substr($response, 0, 500));
        }
        
        if ($httpCode >= 400) {
            $errorMessage = "HTTP $httpCode: ";
            
            if (isset($result['error_messages']) && is_array($result['error_messages'])) {
                $errorMessage .= implode(', ', $result['error_messages']);
            } elseif (isset($result['status_message'])) {
                $errorMessage .= $result['status_message'];
            } else {
                $errorMessage .= 'Unknown error';
            }
            
            throw new Exception($errorMessage);
        }
        
        return $result;
    }
    
    /**
     * Get transaction status
     */
    public function getTransactionStatus($orderId) {
        $url = $this->baseUrl . $orderId . '/status';
        
        $headers = [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Cancel transaction
     */
    public function cancelTransaction($orderId) {
        $url = $this->baseUrl . $orderId . '/cancel';
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        return json_decode($response, true);
    }
}

/**
 * Cancel payment di Midtrans
 * 
 * @param string $orderId Order ID
 * @param string $serverKey Midtrans server key
 * @param bool $isProduction Production mode
 * @return array Response
 */
function cancelMidtransPayment($orderId, $serverKey, $isProduction = true) {
    try {
        $baseUrl = $isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $url = $baseUrl . '/v2/' . $orderId . '/cancel';
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            error_log("Midtrans cancel error: " . $err);
            return ['success' => true, 'message' => $err];
        }
        
        $result = json_decode($response, true);
        
        error_log("Midtrans cancel response for $orderId: " . json_encode($result));
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $result];
        } else {
            return ['success' => true, 'message' => $result['status_message'] ?? 'Cancel failed', 'data' => $result];
        }
        
    } catch (Exception $e) {
        error_log("Midtrans cancel exception: " . $e->getMessage());
        return ['success' => true, 'message' => $e->getMessage()];
    }
}

/**
 * Extract VA number dari Midtrans response
 */
function extractVaNumber($midtransResponse, $paymentMethod) {
    // Hanya untuk bank transfer
    if (!in_array($paymentMethod, ['bca', 'bni', 'bri', 'mandiri', 'permata'])) {
        return null;
    }
    
    if (!isset($midtransResponse['va_numbers']) || empty($midtransResponse['va_numbers'])) {
        return null;
    }
    
    // Cari VA number yang sesuai dengan bank
    foreach ($midtransResponse['va_numbers'] as $va) {
        if (isset($va['bank']) && $va['bank'] === $paymentMethod) {
            return $va['va_number'] ?? null;
        }
    }
    
    // Fallback ke VA pertama jika tidak ketemu
    return $midtransResponse['va_numbers'][0]['va_number'] ?? null;
}

/**
 * Extract QR string dari Midtrans response
 */
function extractQrString($midtransResponse, $paymentMethod) {
    // Untuk QRIS
    if ($paymentMethod === 'qris' && isset($midtransResponse['qr_string'])) {
        return $midtransResponse['qr_string'];
    }
    
    // Untuk GoPay dan ShopeePay yang menggunakan QR
    if (in_array($paymentMethod, ['gopay', 'shopeepay'])) {
        if (isset($midtransResponse['actions'])) {
            foreach ($midtransResponse['actions'] as $action) {
                if (isset($action['name']) && $action['name'] === 'generate-qr-code') {
                    return $action['url'] ?? null;
                }
            }
        }
    }
    
    return null;
}

/**
 * Manual check payment status from Midtrans API
 */
function checkMidtransPaymentStatus($orderId, $serverKey, $isProduction = true) {
    $baseUrl = $isProduction 
        ? 'https://api.midtrans.com/v2/' 
        : 'https://api.sandbox.midtrans.com/v2/';
    
    $url = $baseUrl . $orderId . '/status';
    
    $headers = [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("Manual check for $orderId - HTTP: $httpCode, Response: $response");
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

/**
 * Update payment status dari manual check
 */
function updatePaymentStatusFromCheck($orderId, $dbConnection) {
    try {
        // Get settings
        $settings = getAppSettings($dbConnection);
        $serverKey = $settings['midtrans_server_key'] ?? '';
        $isProduction = ($settings['midtrans_is_production'] ?? '0') === '1';
        
        if (empty($serverKey)) {
            error_log("No server key for manual check");
            return true;
        }
        
        // Check current status in DB first
        if (isset($GLOBALS['pdo'])) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT status FROM topup_orders WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $dbConnection->prepare("SELECT status FROM topup_orders WHERE order_id = ?");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentOrder = $result->fetch_assoc();
            $stmt->close();
        }
        
        if (!$currentOrder) {
            error_log("Order not found: $orderId");
            return true;
        }
        
        // Skip if already settled
        if ($currentOrder['status'] === 'settlement') {
            error_log("Order already settled: $orderId");
            return true;
        }
        
        // Check status from Midtrans
        $midtransStatus = checkMidtransPaymentStatus($orderId, $serverKey, $isProduction);
        
        if (!$midtransStatus) {
            error_log("Failed to get status from Midtrans: $orderId");
            return true;
        }
        
        $transactionStatus = $midtransStatus['transaction_status'] ?? '';
        $fraudStatus = $midtransStatus['fraud_status'] ?? '';
        
        // Map status
        $finalStatus = 'pending';
        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                $finalStatus = 'settlement';
            }
        } elseif ($transactionStatus === 'settlement') {
            $finalStatus = 'settlement';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire', 'failure'])) {
            $finalStatus = 'failed';
        }
        
        error_log("Status update for $orderId: $transactionStatus -> $finalStatus");
        
        // Update database only if status changed
        if ($finalStatus !== $currentOrder['status']) {
            if (isset($GLOBALS['pdo'])) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE topup_orders SET 
                    status = ?, 
                    midtrans_response = ?, 
                    paid_at = CASE WHEN ? = 'settlement' THEN NOW() ELSE paid_at END,
                    updated_at = NOW() 
                    WHERE order_id = ?");
                $stmt->execute([$finalStatus, json_encode($midtransStatus), $finalStatus, $orderId]);
            } else {
                $stmt = $dbConnection->prepare("UPDATE topup_orders SET 
                    status = ?, 
                    midtrans_response = ?, 
                    paid_at = CASE WHEN ? = 'settlement' THEN NOW() ELSE paid_at END,
                    updated_at = NOW() 
                    WHERE order_id = ?");
                $responseJson = json_encode($midtransStatus);
                $stmt->bind_param("ssss", $finalStatus, $responseJson, $finalStatus, $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            error_log("Database updated for $orderId: $finalStatus");
            
            // If payment successful, trigger topup
            if ($finalStatus === 'settlement') {
                processTopupAfterPayment($orderId, $dbConnection);
            }
            
            return true;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error in manual check: " . $e->getMessage());
        return true;
    }
}

/**
 * Process topup after payment success
 */
function processTopupAfterPayment($orderId, $dbConnection) {
    try {
        // Get order details
        if (isset($GLOBALS['pdo'])) {
            $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM topup_orders WHERE order_id = ? AND status = 'settlement'");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $dbConnection->prepare("SELECT * FROM topup_orders WHERE order_id = ? AND status = 'settlement'");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
        }
        
        if (!$order || ($order['topup_status'] ?? 'pending') === 'success') {
            return; // Already processed or not found
        }
        
        // Set topup status to processing
        if (isset($GLOBALS['pdo'])) {
            $stmt = $GLOBALS['pdo']->prepare("UPDATE topup_orders SET topup_status = 'processing' WHERE order_id = ?");
            $stmt->execute([$orderId]);
        } else {
            $stmt = $dbConnection->prepare("UPDATE topup_orders SET topup_status = 'processing' WHERE order_id = ?");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Calculate amount in cents
        $amountCents = (int)($order['original_price_usd'] * 100);
        
        // Call topup API
        $topupResult = topUpEsim(
            $order['iccid'],
            $order['package_code'],
            $orderId,
            $amountCents
        );
        
        if (isset($topupResult['success']) && $topupResult['success']) {
            // Success
            if (isset($GLOBALS['pdo'])) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE topup_orders SET topup_status = 'success', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $stmt->execute([json_encode($topupResult), $orderId]);
            } else {
                $stmt = $dbConnection->prepare("UPDATE topup_orders SET topup_status = 'success', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $topupJson = json_encode($topupResult);
                $stmt->bind_param("ss", $topupJson, $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            error_log("Topup successful for $orderId");
        } else {
            // Failed
            $errorMsg = $topupResult['errorMsg'] ?? 'Unknown error';
            if (isset($GLOBALS['pdo'])) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE topup_orders SET topup_status = 'failed', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $stmt->execute([json_encode($topupResult), $orderId]);
            } else {
                $stmt = $dbConnection->prepare("UPDATE topup_orders SET topup_status = 'failed', topup_response = ?, updated_at = NOW() WHERE order_id = ?");
                $topupJson = json_encode($topupResult);
                $stmt->bind_param("ss", $topupJson, $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            error_log("Topup failed for $orderId: $errorMsg");
        }
        
    } catch (Exception $e) {
        error_log("Error processing topup for $orderId: " . $e->getMessage());
    }
}


function createMidtransPayment($packageInfo, $order, $paymentMethod, $markupConfig, $exchangeRate, $pdo) {
    try {
        // Get Midtrans settings
        $settings = getAppSettings($pdo);
        $serverKey = $settings['midtrans_server_key'] ?? '';
        $isProduction = ($settings['midtrans_is_production'] ?? '0') === '1';
        
        error_log("Midtrans Config: ServerKey=" . (!empty($serverKey) ? "SET" : "EMPTY") . ", Production=" . ($isProduction ? "YES" : "NO"));
        
        if (empty($serverKey)) {
            throw new Exception("Midtrans server key not configured");
        }

        // Calculate pricing
        $volumeGB = round((float)$packageInfo["volume"] / (1024**3), 1);
        $originalPriceUsd = (float)$packageInfo["price"] / 10000;
        $finalPrice = calculateFinalPrice($originalPriceUsd, $exchangeRate, $volumeGB, $markupConfig);
        
        // Get payment method fee
        $adminFee = getPaymentMethodFee($paymentMethod, $pdo);
        $totalAmount = $finalPrice + $adminFee;
        
        error_log("Payment calculation: Price=$finalPrice, Fee=$adminFee, Total=$totalAmount");
        
        // Generate unique order ID
        $orderId = 'TOPUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        
        // Prepare customer details
        $customerDetails = [
            'first_name' => $order['nama'],
            'email' => $order['email'] ?? 'customer@esim.com',
            'phone' => $order['phone'] ?? '081234567890'
        ];
        
        // Prepare item details
        $itemDetails = [
            [
                'id' => $packageInfo['packageCode'],
                'price' => $finalPrice,
                'quantity' => 1,
                'name' => $packageInfo['name'] . ' (' . $volumeGB . 'GB)'
            ]
        ];
        
        // Add admin fee as separate item if > 0
        if ($adminFee > 0) {
            $itemDetails[] = [
                'id' => 'admin_fee',
                'price' => $adminFee,
                'quantity' => 1,
                'name' => 'Biaya Admin ' . getPaymentMethodName($paymentMethod, $pdo)
            ];
        }
        
        // Prepare transaction parameters
        $transactionParams = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $totalAmount
            ],
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails
        ];
        
        // Add payment type specific parameters
        $transactionParams = addPaymentTypeParams($transactionParams, $paymentMethod);
        
        error_log("Midtrans Request: " . json_encode($transactionParams));
        
        // Create Midtrans API instance
        $midtrans = new MidtransAPI($serverKey, $isProduction);
        
        // Save order to database first
        $stmt = $pdo->prepare("INSERT INTO topup_orders 
            (order_id, customer_name, customer_email, customer_phone, iccid, esim_tran_no, 
             package_code, package_name, package_data_gb, package_duration, package_duration_unit,
             original_price_usd, exchange_rate, markup_amount, gross_amount, payment_method, status, topup_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
        
        $markupAmount = getTieredMarkup($volumeGB, $markupConfig);
        $duration = (int)($packageInfo["duration"] ?? 30);
        $durationUnit = $packageInfo["durationUnit"] ?? 'day';
        
        $success = $stmt->execute([
            $orderId,
            $order['nama'],
            $customerDetails['email'],
            $customerDetails['phone'],
            $order['iccid'],
            $order['esimTranNo'] ?? '',
            $packageInfo['packageCode'],
            $packageInfo['name'],
            $volumeGB,
            $duration,
            $durationUnit,
            $originalPriceUsd,
            $exchangeRate,
            $markupAmount,
            $totalAmount,
            $paymentMethod
        ]);
        
        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Failed to save order: " . $errorInfo[2]);
        }
        
        // Create transaction with Midtrans
        $midtransResponse = $midtrans->createTransaction($transactionParams);
        
        error_log("Midtrans Response: " . json_encode($midtransResponse));
        
        // Extract payment data
        $vaNumber = extractVaNumber($midtransResponse, $paymentMethod);
        $paymentUrl = getPaymentUrl($midtransResponse, $paymentMethod);
        $qrString = extractQrString($midtransResponse, $paymentMethod);
        $midtransToken = $midtransResponse['token'] ?? null;
        $responseJson = json_encode($midtransResponse);
        
        // Update order dengan payment data
        $updateStmt = $pdo->prepare("UPDATE topup_orders SET 
            midtrans_token = ?, 
            va_number = ?, 
            payment_url = ?, 
            qr_string = ?,
            payment_type = ?,
            midtrans_response = ?,
            updated_at = NOW()
            WHERE order_id = ?");
        
        $updateStmt->execute([
            $midtransToken,
            $vaNumber,
            $paymentUrl,
            $qrString,
            $paymentMethod,
            $responseJson,
            $orderId
        ]);
        
        error_log("Payment created successfully: $orderId");
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'midtrans_response' => $midtransResponse,
            'payment_url' => $paymentUrl,
            'va_number' => $vaNumber,
            'qr_string' => $qrString,
            'total_amount' => $totalAmount
        ];
        
    } catch (Exception $e) {
        error_log("Midtrans payment creation error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'success' => true,
            'message' => $e->getMessage()
        ];
    }
}


/**
 * Add payment type specific parameters
 */
function addPaymentTypeParams($params, $paymentMethod) {
    switch ($paymentMethod) {
        case 'bca':
        case 'bni':
        case 'bri':
        case 'mandiri':
        case 'permata':
            $params['payment_type'] = 'bank_transfer';
            $params['bank_transfer'] = ['bank' => $paymentMethod];
            break;
            
        case 'gopay':
            $params['payment_type'] = 'gopay';
            $params['gopay'] = [
                'enable_callback' => true,
                'callback_url' => getBaseUrl() . '/payment_callback.php'
            ];
            break;
            
        case 'shopeepay':
            $params['payment_type'] = 'shopeepay';
            $params['shopeepay'] = [
                'callback_url' => getBaseUrl() . '/payment_callback.php'
            ];
            break;
            
        case 'qris':
            $params['payment_type'] = 'qris';
            $params['qris'] = [
                'acquirer' => 'gopay'
            ];
            break;
            
        case 'indomaret':
        case 'alfamart':
            $params['payment_type'] = 'cstore';
            $params['cstore'] = ['store' => $paymentMethod];
            break;
            
        default:
            // Default to bank transfer BCA
            $params['payment_type'] = 'bank_transfer';
            $params['bank_transfer'] = ['bank' => 'bca'];
    }
    
    return $params;
}

/**
 * Get payment URL from Midtrans response
 */
function getPaymentUrl($midtransResponse, $paymentMethod) {
    // Untuk redirect URL langsung
    if (isset($midtransResponse['redirect_url'])) {
        return $midtransResponse['redirect_url'];
    }
    
    // Untuk actions-based payment methods
    if (isset($midtransResponse['actions']) && !empty($midtransResponse['actions'])) {
        foreach ($midtransResponse['actions'] as $action) {
            // Prioritas untuk deeplink
            if (isset($action['name']) && $action['name'] === 'deeplink-redirect' && isset($action['url'])) {
                return $action['url'];
            }
        }
        
        // Fallback ke action pertama yang ada URL
        foreach ($midtransResponse['actions'] as $action) {
            if (isset($action['url'])) {
                return $action['url'];
            }
        }
    }
    
    // Untuk deeplink redirect URL
    if (isset($midtransResponse['deeplink_redirect_url'])) {
        return $midtransResponse['deeplink_redirect_url'];
    }
    
    // Khusus untuk payment method tertentu
    switch ($paymentMethod) {
        case 'gopay':
            return $midtransResponse['actions'][0]['url'] ?? null;
        case 'shopeepay':
            return $midtransResponse['actions'][0]['url'] ?? null;
        case 'qris':
            return $midtransResponse['actions'][0]['url'] ?? null;
        default:
            return null;
    }
}

/**
 * Get payment method fee
 */
function getPaymentMethodFee($paymentMethodCode, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT admin_fee FROM payment_methods WHERE code = ? AND is_active = 1");
        $stmt->execute([$paymentMethodCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return (int)$result['admin_fee'];
        }
    } catch (Exception $e) {
        error_log("Error getting payment method fee: " . $e->getMessage());
    }
    
    // Default fees
    $defaultFees = [
        'bca' => 4000, 'bni' => 4000, 'bri' => 4000, 'mandiri' => 4000, 'permata' => 4000,
        'gopay' => 2500, 'shopeepay' => 2500, 'qris' => 2500,
        'indomaret' => 5000, 'alfamart' => 5000
    ];
    
    return $defaultFees[$paymentMethodCode] ?? 0;
}

/**
 * Get payment method name
 */
function getPaymentMethodName($paymentMethodCode, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM payment_methods WHERE code = ?");
        $stmt->execute([$paymentMethodCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['name'];
        }
    } catch (Exception $e) {
        error_log("Error getting payment method name: " . $e->getMessage());
    }
    
    return ucfirst($paymentMethodCode);
}

/**
 * Get active payment methods from Midtrans
 */
function getActiveMidtransPaymentMethods($serverKey, $isProduction = true) {
    $baseUrl = $isProduction 
        ? 'https://api.midtrans.com/v1/' 
        : 'https://api.sandbox.midtrans.com/v1/';
    
    $url = $baseUrl . 'payment_methods';
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return parseActiveMidtransPaymentMethods($data);
    }
    
    // Fallback to default methods if API fails
    return getDefaultPaymentMethods();
}

/**
 * Parse Midtrans payment methods response
 */
function parseActiveMidtransPaymentMethods($data) {
    $activeMethods = [];
    
    if (!isset($data['payment_methods']) || !is_array($data['payment_methods'])) {
        return getDefaultPaymentMethods();
    }
    
    foreach ($data['payment_methods'] as $category) {
        if (!isset($category['methods']) || !is_array($category['methods'])) {
            continue;
        }
        
        foreach ($category['methods'] as $method) {
            if (!isset($method['code']) || !isset($method['name'])) {
                continue;
            }
            
            $code = $method['code'];
            $name = $method['name'];
            $isActive = $method['status'] ?? 'active';
            
            if ($isActive !== 'active') {
                continue;
            }
            
            // Map Midtrans codes to our format
            $mappedMethod = mapMidtransPaymentMethod($code, $name);
            if ($mappedMethod) {
                $activeMethods[$mappedMethod['code']] = $mappedMethod;
            }
        }
    }
    
    // Sort by priority
    uasort($activeMethods, function($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });
    
    return $activeMethods;
}

/**
 * Map Midtrans payment method to our format
 */
function mapMidtransPaymentMethod($midtransCode, $midtransName) {
    $mapping = [
        // Virtual Accounts
        'bca_va' => [
            'code' => 'bca',
            'name' => 'BCA Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 1
        ],
        'bni_va' => [
            'code' => 'bni',
            'name' => 'BNI Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 2
        ],
        'bri_va' => [
            'code' => 'bri',
            'name' => 'BRI Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 3
        ],
        'mandiri_va' => [
            'code' => 'mandiri',
            'name' => 'Mandiri Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 4
        ],
        'permata_va' => [
            'code' => 'permata',
            'name' => 'Permata Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 5
        ],
        
        // E-Wallets
        'gopay' => [
            'code' => 'gopay',
            'name' => 'GoPay',
            'icon' => '💚',
            'fee' => 2000,
            'priority' => 6
        ],
        'shopeepay' => [
            'code' => 'shopeepay',
            'name' => 'ShopeePay',
            'icon' => '🧡',
            'fee' => 2000,
            'priority' => 7
        ],
        'dana' => [
            'code' => 'dana',
            'name' => 'DANA',
            'icon' => '💙',
            'fee' => 2000,
            'priority' => 8
        ],
        'ovo' => [
            'code' => 'ovo',
            'name' => 'OVO',
            'icon' => '💜',
            'fee' => 2000,
            'priority' => 9
        ],
        'linkaja' => [
            'code' => 'linkaja',
            'name' => 'LinkAja',
            'icon' => '❤️',
            'fee' => 2000,
            'priority' => 10
        ],
        
        // QRIS
        'qris' => [
            'code' => 'qris',
            'name' => 'QRIS',
            'icon' => '📱',
            'fee' => 2000,
            'priority' => 11
        ],
        
        // Convenience Stores
        'indomaret' => [
            'code' => 'indomaret',
            'name' => 'Indomaret',
            'icon' => '🏪',
            'fee' => 5000,
            'priority' => 12
        ],
        'alfamart' => [
            'code' => 'alfamart',
            'name' => 'Alfamart',
            'icon' => '🏪',
            'fee' => 5000,
            'priority' => 13
        ]
    ];
    
    // Direct mapping
    if (isset($mapping[$midtransCode])) {
        return $mapping[$midtransCode];
    }
    
    // Partial matching
    foreach ($mapping as $key => $config) {
        if (strpos($midtransCode, $key) !== true || 
            strpos(strtolower($midtransName), $key) !== true) {
            return $config;
        }
    }
    
    return null;
}

/**
 * Get default payment methods (fallback)
 */
function getDefaultPaymentMethods() {
    return [
        'bca' => [
            'code' => 'bca',
            'name' => 'BCA Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 1
        ],
        'bni' => [
            'code' => 'bni',
            'name' => 'BNI Virtual Account',
            'icon' => '🏦',
            'fee' => 4000,
            'priority' => 2
        ],
        'gopay' => [
            'code' => 'gopay',
            'name' => 'GoPay',
            'icon' => '💚',
            'fee' => 2000,
            'priority' => 6
        ],
        'qris' => [
            'code' => 'qris',
            'name' => 'QRIS',
            'icon' => '📱',
            'fee' => 2000,
            'priority' => 11
        ]
    ];
}

/**
 * Cache active payment methods
 */
function getCachedActiveMidtransPaymentMethods($serverKey, $isProduction = true) {
    $cacheKey = 'midtrans_payment_methods_' . md5($serverKey);
    $cacheFile = sys_get_temp_dir() . '/' . $cacheKey . '.json';
    $cacheExpiry = 3600; // 1 hour
    
    // Check if cache exists and is valid
    if (file_exists($cacheFile)) {
        $cacheTime = filemtime($cacheFile);
        if ((time() - $cacheTime) < $cacheExpiry) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && is_array($cached)) {
                return $cached;
            }
        }
    }
    
    // Get fresh data
    $methods = getActiveMidtransPaymentMethods($serverKey, $isProduction);
    
    // Save to cache
    file_put_contents($cacheFile, json_encode($methods));
    
    return $methods;
}


/**
 * Get app settings with PDO
 */
// function getAppSettings($pdo) {
//     $settings = [];
//     try {
//         $query = "SELECT setting_key, setting_value FROM app_settings";
//         $stmt = $pdo->prepare($query);
//         $stmt->execute();
//         $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
//         foreach ($results as $row) {
//             $settings[$row['setting_key']] = $row['setting_value'];
//         }
//     } catch (Exception $e) {
//         error_log("Error fetching app settings: " . $e->getMessage());
//     }
//     return $settings;
// }

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . $host . $path, '/');
}
?>
