<?php
if (!defined('ALLOWED_ACCESS')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

function createMultipleEsimOrders($formData) {
    global $pdo;
    
    try {
        error_log("=== CREATE MULTIPLE ORDERS DEBUG ===");
        error_log("Form data: " . json_encode($formData));
        
        if (empty($formData['customer_name']) || empty($formData['package_code'])) {
            return ['success' => false, 'message' => 'Customer name and package code are required'];
        }
        
        // Gunakan getPackageDetails() yang sudah ada di api.php
        $package = getPackageDetails($formData['package_code']);
        if (!$package) {
            return ['success' => false, 'message' => 'Package not found: ' . $formData['package_code']];
        }
        
        error_log("Package found: " . $package['nama']);
        
        // Determine unlimited
        $isUnlimited = isUnlimitedPackage($package);
        $count = $isUnlimited ? 1 : max(1, min(20, (int)($formData['count'] ?? 1)));
        $periodNum = $isUnlimited ? max(1, min(30, (int)($formData['period_num'] ?? 1))) : null;
        
        // Calculate pricing - gunakan functions yang udah ada
        $exchangeRate = getCurrentExchangeRate();
        $basePrice = (float)$package['price_usd'];
        $volumeGB = (float)$package['volume'] / (1024 * 1024 * 1024);
        
        // Gunakan markup config sederhana
        $markupConfig = [
            ['limit' => 1, 'markup' => 5000],
            ['limit' => 5, 'markup' => 10000], 
            ['limit' => 10, 'markup' => 15000],
            ['limit' => 999, 'markup' => 20000]
        ];
        
        $singlePriceIdr = calculateFinalPrice($basePrice, $exchangeRate, $volumeGB, $markupConfig);
        if ($isUnlimited && $periodNum) {
            $singlePriceIdr *= $periodNum;
        }
        
        $totalPriceIdr = $singlePriceIdr * $count;
        
        error_log("Pricing calculated: single={$singlePriceIdr}, total={$totalPriceIdr}, count={$count}");
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $orderTokens = [];
            $orderResults = [];
            
            for ($i = 1; $i <= $count; $i++) {
                $customerName = $count > 1 ? $formData['customer_name'] . '_' . $i : $formData['customer_name'];
                
                error_log("Creating order {$i}/{$count} for: {$customerName}");
                
                // Generate token - gunakan dari config.php
                $token = generateSecureToken(24);
                $attempts = 0;
                while (tokenExists($token) && $attempts < 10) {
                    $token = generateSecureToken(24);
                    $attempts++;
                }
                
                if ($attempts >= 10) {
                    throw new Exception("Cannot generate unique token after 10 attempts");
                }
                
                error_log("Generated token: {$token}");
                
                // Insert order - gunakan dbInsert yang udah ada
                $orderData = [
                    'nama' => $customerName,
                    'phone' => $formData['phone'] ?? '',
                    'orderNo' => '',
                    'iccid' => '',
                    'packageCode' => $formData['package_code'],
                    'packageName' => $package['nama'],
                    'price' => $singlePriceIdr,
                    'token' => $token,
                    'created_at' => date('Y-m-d H:i:s'),
                    'esimTranNo' => '',
                    'status' => 'pending_payment',
                    'last_status_check' => date('Y-m-d H:i:s'),
                    'activation_date' => null,
                    'expiry_date' => null,
                    'smdp_status' => '',
                    'esim_status' => '',
                    'order_usage' => 0,
                    'eid' => '',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                error_log("About to insert order data: " . json_encode($orderData));
                
                // Check if dbInsert function exists
                if (!function_exists('dbInsert')) {
                    throw new Exception("dbInsert function not found - check koneksi.php");
                }
                
                $orderId = dbInsert('esim_orders', $orderData);
                
                error_log("dbInsert result: " . var_export($orderId, true));
                
                if (!$orderId || $orderId === false) {
                    // Try to get PDO error info
                    $errorInfo = $pdo->errorInfo();
                    error_log("PDO Error Info: " . json_encode($errorInfo));
                    
                    throw new Exception("Failed to insert order into database. PDO Error: " . ($errorInfo[2] ?? 'Unknown'));
                }
                
                $orderTokens[] = $token;
                $orderResults[] = [
                    'customerName' => $customerName,
                    'token' => $token,
                    'order_id' => $orderId
                ];
                
                error_log("Order {$i} created successfully with ID: {$orderId}");
            }
            
            // Create payment - dummy for now
            $paymentOrderId = 'ESIM_' . date('YmdHis') . '_' . rand(10000, 99999);
            
            $pdo->commit();
            error_log("Transaction committed successfully");
            
            return [
                'success' => true,
                'is_multiple' => $count > 1,
                'count' => $count,
                'total_price' => $totalPriceIdr,
                'orders' => $orderResults
            ];
            
        } catch (Exception $e) {
            $pdo->rollback();
            error_log("Transaction rollback due to: " . $e->getMessage());
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Create multiple orders error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['success' => false, 'message' => 'Order creation failed: ' . $e->getMessage()];
    }
}

function isUnlimitedPackage($package) {
    $supportTopUpType = (int)($package['support_topup_type'] ?? 0);
    $fupPolicy = trim($package['fup_policy'] ?? '');
    return $supportTopUpType === 1 && !empty($fupPolicy);
}

function tokenExists($token) {
    global $pdo;
    try {
        $result = dbQuery("SELECT COUNT(*) as count FROM esim_orders WHERE token = ?", [$token], false);
        return $result ? $result['count'] > 0 : false;
    } catch (Exception $e) {
        error_log("Error checking token existence: " . $e->getMessage());
        return false;
    }
}
?>