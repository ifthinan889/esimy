<?php
if (!defined('ALLOWED_ACCESS')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

function createMultipleEsimOrders($formData) {
    global $pdo;
    
    try {
        // Validate required fields
        if (empty($formData['customer_name']) || empty($formData['package_code'])) {
            return ['success' => false, 'message' => 'Customer name and package code are required'];
        }
        
        // Get package details
        $package = getPackageDetails($formData['package_code']);
        if (!$package) {
            return ['success' => false, 'message' => 'Package not found: ' . $formData['package_code']];
        }
        
        // Determine if unlimited package
        $isUnlimited = isUnlimitedPackage($package);
        $count = $isUnlimited ? 1 : max(1, min(20, (int)($formData['count'] ?? 1)));
        $periodNum = $isUnlimited ? max(1, min(30, (int)($formData['period_num'] ?? 1))) : null;
        
        // Calculate pricing
        $exchangeRate = getCurrentExchangeRate();
        $basePrice = (float)$package['price_usd'];
        $volumeGB = (float)$package['volume'] / (1024 * 1024 * 1024);
        
        // Get markup configuration
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

        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $orderTokens = [];
            $orderResults = [];
            
            for ($i = 1; $i <= $count; $i++) {
                $customerName = $count > 1 ? $formData['customer_name'] . '_' . $i : $formData['customer_name'];
                
                // Generate unique token
                $token = generateSecureToken(24);
                $attempts = 0;
                while (tokenExists($token) && $attempts < 10) {
                    $token = generateSecureToken(24);
                    $attempts++;
                }
                
                if ($attempts >= 10) {
                    throw new Exception("Cannot generate unique token after 10 attempts");
                }
                
                // Prepare order data
                $orderData = [
                    'nama' => $customerName,
                    'phone' => $formData['phone'] ?? '',
                    'orderNo' => '',
                    'iccid' => '',
                    'packageCode' => $formData['package_code'],
                    'packageName' => $package['name'],
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
                
                // Insert order into database
                if (!function_exists('dbInsert')) {
                    throw new Exception("dbInsert function not found - check koneksi.php");
                }
                
                $orderId = dbInsert('esim_orders', $orderData);
                
                if (!$orderId || $orderId === false) {
                    $errorInfo = $pdo->errorInfo();
                    throw new Exception("Failed to insert order into database. PDO Error: " . ($errorInfo[2] ?? 'Unknown'));
                }
                
                $orderTokens[] = $token;
                $orderResults[] = [
                    'customerName' => $customerName,
                    'token' => $token,
                    'order_id' => $orderId
                ];
                
            }
            
            $pdo->commit();
            
            return [
                'success' => true,
                'is_multiple' => $count > 1,
                'count' => $count,
                'total_price' => $totalPriceIdr,
                'orders' => $orderResults
            ];
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Create multiple orders error: " . $e->getMessage());
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