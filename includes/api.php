<?php
declare(strict_types=1);

/**
 * API Integration for eSIM Access
 * Secure implementation with error handling and logging + Auto Initialize
 */

// Prevent direct access
if (!defined('ALLOWED_ACCESS')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

// API Configuration
$API_BASE_URL = '';
$ACCESS_CODE = '';
$API_INITIALIZED = false;

/**
 * Auto-initialize API configuration from database
 */
function autoInitializeEsimApi(): bool {
    global $API_BASE_URL, $ACCESS_CODE, $API_INITIALIZED, $pdo, $conn;
    
    if ($API_INITIALIZED) {
        return true; // Already initialized
    }
    
    try {
        // Get database connection
        $dbConnection = null;
        if (isset($pdo)) {
            $dbConnection = $pdo;
        } elseif (isset($conn)) {
            $dbConnection = $conn;
        } else {
            // Try to get global connection
            global $dbConnection;
            if (isset($dbConnection)) {
                // $dbConnection is now available as a global
            }
        }
        
        if (!$dbConnection) {
            error_log("❌ Database connection not available for API initialization");
            return false;
        }
        
        // Get settings from database
        if (!function_exists('getAppSettings')) {
            error_log("❌ getAppSettings function not available");
            return false;
        }
        
        $settings = getAppSettings($dbConnection);
        
        $API_BASE_URL = $settings['esim_api_url'] ?? '';
        $ACCESS_CODE = $settings['esim_api_key'] ?? '';
        
        if (empty($API_BASE_URL) || empty($ACCESS_CODE)) {
            error_log("❌ eSIM API configuration missing - URL: '$API_BASE_URL', Key: " . (empty($ACCESS_CODE) ? 'EMPTY' : 'SET'));
            return false;
        }
        
        $API_INITIALIZED = true;
        error_log("✅ eSIM API auto-initialized successfully");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Failed to auto-initialize eSIM API: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize API configuration from database (ORIGINAL - BACKWARD COMPATIBILITY)
 */
function initializeEsimApiConfig($dbConnection): void {
    global $API_BASE_URL, $ACCESS_CODE, $API_INITIALIZED;
    
    // Get settings from database (sama seperti Midtrans)
    $settings = getAppSettings($dbConnection);
    
    $API_BASE_URL = $settings['esim_api_url'] ?? '';
    $ACCESS_CODE = $settings['esim_api_key'] ?? '';
    
    // Validation
    if (empty($API_BASE_URL) || empty($ACCESS_CODE)) {
        error_log("eSIM API configuration missing in database");
        throw new Exception('eSIM API not configured properly');
    }
    
    $API_INITIALIZED = true;
    error_log("eSIM API initialized - URL: $API_BASE_URL");
}

/**
 * Execute API request with error handling (ORIGINAL + AUTO INIT)
 */
function executeApiRequest(string $endpoint, array $postData = [], array $headers = []): array {
    global $API_BASE_URL, $ACCESS_CODE;
    
    // AUTO-INITIALIZE if not done (NEW FEATURE)
    if (empty($API_BASE_URL) || empty($ACCESS_CODE)) {
        if (!autoInitializeEsimApi()) {
            return ["success" => false, "errorMsg" => "eSIM API not initialized. Call initializeEsimApiConfig() first."];
        }
    }
    
    // Initialize cURL session
    $curl = curl_init();
    
    // Prepare headers
    $defaultHeaders = [
        'Content-Type: application/json',
        'RT-AccessCode: ' . $ACCESS_CODE
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => $API_BASE_URL . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => !empty($postData) ? json_encode($postData) : '',
        CURLOPT_HTTPHEADER => $allHeaders,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'eSIM-Portal/1.0'
    ]);
    
    // Execute request
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    // Log request for debugging
    $logData = [
        'endpoint' => $endpoint,
        'postData' => $postData,
        'httpCode' => $httpCode
    ];
    
    if ($err) {
        error_log("eSIM API Error ($endpoint): " . $err);
        $logData['error'] = $err;
        if (function_exists('logApiResponse')) {
            logApiResponse('error', $logData);
        }
        return ["success" => false, "errorMsg" => "API connection error: " . $err];
    }
    
    // Parse response
    $decodedResponse = json_decode($response, true);
    
    // Log response for debugging
    $logData['response'] = $decodedResponse;
    if (function_exists('logApiResponse')) {
        logApiResponse('response', $logData);
    }
    
    if ($httpCode >= 400) {
        error_log("eSIM API HTTP Error ($endpoint): " . $httpCode);
        return ["success" => false, "errorMsg" => "API returned error code: " . $httpCode];
    }
    
    if (!$decodedResponse) {
        error_log("eSIM API Invalid Response ($endpoint): " . $response);
        return ["success" => false, "errorMsg" => "Invalid API response format"];
    }
    
    return $decodedResponse;
}

/**
 * Get merchant balance
 * 
 * @return array API response as associative array
 */
function getMerchantBalance() {
    try {
        return executeApiRequest('/balance/query');
    } catch (Exception $e) {
        error_log("Merchant Balance API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Get package list from eSIM Access API
 * 
 * @param string $locationCode Location/country code (optional)
 * @param string $type Package type (optional)
 * @param string $packageCode Package code (optional)
 * @param string $iccid ICCID for topup packages (optional)
 * @return array API response as associative array
 */
function getPackageList($countryName = "", $type = "", $packageCode = "", $iccid = "") {
    try {
        $postData = [
            "locationCode" => $countryName,
            "type" => $type,
            "packageCode" => $packageCode,
            "iccid" => $iccid
        ];
        
        return executeApiRequest('/package/list', $postData);
    } catch (Exception $e) {
        error_log("Package List API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Create a new eSIM order (Order Profile endpoint) - FOR REGULAR PACKAGES ONLY
 * 
 * @param string $transactionId Unique transaction ID
 * @param string $packageCode Package code
 * @param int $count Number of eSIMs to order (default: 1)
 * @param int|null $price Price of the package (optional, in cents USD)
 * @return array API response as associative array
 */
function createEsimOrder($transactionId, $packageCode, $count = 1, $price = null) {
    try {
        // Validate inputs
        $transactionId = trim($transactionId);
        $packageCode = trim($packageCode);
        $count = max(1, intval($count));
        
        if (empty($transactionId) || empty($packageCode)) {
            return ["success" => false, "errorMsg" => "Transaction ID and package code are required"];
        }
        
        $packageInfo = [
            "packageCode" => $packageCode,
            "count" => $count
        ];
        
        // Add price if provided
        if ($price !== null) {
            $packageInfo["price"] = intval($price);
        }
        
        $payload = [
            "transactionId" => $transactionId,
            "packageInfoList" => [$packageInfo]
        ];
        
        // Add total amount if price is provided
        if ($price !== null) {
            $payload["amount"] = intval($price);
        }
        
        $response = executeApiRequest('/esim/order', $payload);
        
        // Additional validation of response
        if (isset($response['success']) && $response['success'] === true) {
            if (!isset($response['obj']['orderNo'])) {
                error_log("API response missing orderNo: " . json_encode($response));
                return ["success" => false, "errorMsg" => "API response missing order number"];
            }
            
            // Add esimTranNo as empty string if not present (it comes later during provisioning)
            if (!isset($response['obj']['esimTranNo'])) {
                $response['obj']['esimTranNo'] = '';
            }
            
            return [
                'success' => true,
                'obj' => $response['obj']
            ];
        }
        
        return $response;
    } catch (Exception $e) {
        error_log("Create eSIM Order API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Create dayplans order for unlimited/dayplans packages ONLY
 * Uses specific payload structure for dayplans with periodNum
 * 
 * @param string $transactionId Unique transaction ID
 * @param string $packageCode Package code
 * @param int $periodNum Period number (number of days) - REQUIRED for dayplans
 * @param int $price Price of the package (in cents USD) - REQUIRED
 * @return array API response as associative array
 */
function createDayplansOrder($transactionId, $packageCode, $periodNum, $price) {
    try {
        // Validate inputs
        $transactionId = trim($transactionId);
        $packageCode = trim($packageCode);
        $periodNum = max(1, intval($periodNum));
        $price = intval($price);
        
        if (empty($transactionId) || empty($packageCode)) {
            return ["success" => false, "errorMsg" => "Transaction ID and package code are required"];
        }
        
        if ($periodNum < 1) {
            return ["success" => false, "errorMsg" => "Period number must be at least 1"];
        }
        
        if ($price <= 0) {
            return ["success" => false, "errorMsg" => "Price must be greater than 0"];
        }
        
        // Ensure count is always 1 for dayplans
        $count = 1;
        
        // Calculate total amount (price * periodNum)
        $totalAmount = $price * $periodNum;
        
        $packageInfo = [
            "packageCode" => $packageCode,
            "count" => $count,
            "price" => $price,
            "periodNum" => $periodNum
        ];
        
        $payload = [
            "transactionId" => $transactionId,
            "amount" => $totalAmount, // Total amount = price * periodNum (count is always 1)
            "packageInfoList" => [$packageInfo]
        ];
        
        $response = executeApiRequest('/esim/order', $payload);
        
        // Additional validation of response
        if (isset($response['success']) && $response['success'] === true) {
            if (!isset($response['obj']['orderNo'])) {
                error_log("API response missing orderNo: " . json_encode($response));
                return ["success" => false, "errorMsg" => "API response missing order number"];
            }
            
            // Add esimTranNo as empty string if not present (it comes later during provisioning)
            if (!isset($response['obj']['esimTranNo'])) {
                $response['obj']['esimTranNo'] = '';
            }
            
            return [
                'success' => true,
                'obj' => $response['obj']
            ];
        }
        
        return $response;
    } catch (Exception $e) {
        error_log("Create Dayplans Order API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Query eSIM details (Query all Allocated Profiles endpoint)
 * 
 * @param string $orderNo Order number
 * @param string $iccid ICCID
 * @param string $esimTranNo eSIM transaction number
 * @param int $pageNum Page number (default: 1)
 * @param int $pageSize Page size (default: 20)
 * @return array API response as associative array
 */
function queryEsimDetails($orderNo = '', $iccid = '', $esimTranNo = '', $pageNum = 1, $pageSize = 20) {
    try {
        // Validate inputs
        $orderNo = trim($orderNo);
        $iccid = trim($iccid);
        $esimTranNo = trim($esimTranNo);
        $pageNum = max(1, intval($pageNum));
        $pageSize = max(1, intval($pageSize));
        
        $payload = [
            "orderNo" => $orderNo,
            "iccid" => $iccid,
            "esimTranNo" => $esimTranNo,
            "pager" => ["pageNum" => $pageNum, "pageSize" => $pageSize]
        ];
        
        return executeApiRequest('/esim/query', $payload);
    } catch (Exception $e) {
        error_log("Query eSIM Details API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Top up an existing eSIM with a new data package
 * 
 * @param string $iccid ICCID of the eSIM to top up
 * @param string $topUpCode Top-up package code
 * @param string $transactionId Unique transaction ID
 * @param int $amount Price of top-up package (in cents USD)
 * @return array API response as associative array
 */
function topUpEsim($iccid, $topUpCode, $transactionId, $amount) {
    try {
        // Validate inputs
        $iccid = trim($iccid);
        $topUpCode = trim($topUpCode);
        $transactionId = trim($transactionId);
        $amount = intval($amount);
        
        // TAMBAHAN: Log input parameters
        error_log("TOPUP DEBUG - Input params: ICCID=$iccid, Code=$topUpCode, TxnID=$transactionId, Amount=$amount");
        
        if (empty($iccid) || empty($topUpCode) || empty($transactionId)) {
            error_log("TOPUP ERROR: Missing required parameters");
            return ["success" => false, "errorMsg" => "ICCID, top-up code, and transaction ID are required"];
        }
        
        if ($amount <= 0) {
            error_log("TOPUP ERROR: Invalid amount: $amount");
            return ["success" => false, "errorMsg" => "Amount must be greater than 0"];
        }
        
        $payload = [
            "iccid" => $iccid,
            "packageCode" => $topUpCode,
            "transactionId" => $transactionId,
            "amount" => $amount
        ];
        
        // TAMBAHAN: Log payload yang akan dikirim
        error_log("TOPUP DEBUG - Payload: " . json_encode($payload));
        
        $response = executeApiRequest('/esim/topup', $payload);
        
        // TAMBAHAN: Log response dari API
        error_log("TOPUP DEBUG - API Response: " . json_encode($response));
        
        return $response;
    } catch (Exception $e) {
        error_log("Top Up eSIM API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Cancel an eSIM order
 */
function cancelEsim($iccid) {
    try {
        $iccid = trim($iccid);
        
        if (empty($iccid)) {
            return ["success" => false, "errorMsg" => "ICCID is required"];
        }
        
        $payload = ["iccid" => $iccid];
        return executeApiRequest('/esim/cancel', $payload);
    } catch (Exception $e) {
        error_log("Cancel eSIM API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Suspend an active eSIM profile
 */
function suspendEsim($iccid) {
    try {
        $iccid = trim($iccid);
        
        if (empty($iccid)) {
            return ["success" => false, "errorMsg" => "ICCID is required"];
        }
        
        $payload = ["iccid" => $iccid];
        return executeApiRequest('/esim/suspend', $payload);
    } catch (Exception $e) {
        error_log("Suspend eSIM API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Unsuspend (reactivate) a suspended eSIM profile
 */
function unsuspendEsim($iccid) {
    try {
        $iccid = trim($iccid);
        
        if (empty($iccid)) {
            return ["success" => false, "errorMsg" => "ICCID is required"];
        }
        
        $payload = ["iccid" => $iccid];
        return executeApiRequest('/esim/unsuspend', $payload);
    } catch (Exception $e) {
        error_log("Unsuspend eSIM API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Set webhook endpoint for receiving notifications
 */
function setWebhook($webhook) {
    try {
        $webhook = trim($webhook);
        
        if (empty($webhook) || !filter_var($webhook, FILTER_VALIDATE_URL)) {
            return ["success" => false, "errorMsg" => "Valid webhook URL is required"];
        }
        
        $payload = ["webhook" => $webhook];
        return executeApiRequest('/webhook/save', $payload);
    } catch (Exception $e) {
        error_log("Set Webhook API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Send SMS to eSIM
 */
function sendSms($iccid, $message) {
    try {
        $iccid = trim($iccid);
        $message = trim($message);
        
        if (empty($iccid)) {
            return ["success" => false, "errorMsg" => "ICCID is required"];
        }
        
        if (empty($message)) {
            return ["success" => false, "errorMsg" => "Message cannot be empty"];
        }
        
        $payload = [
            "iccid" => $iccid,
            "message" => $message
        ];
        
        return executeApiRequest('/esim/sendSms', $payload);
    } catch (Exception $e) {
        error_log("Send SMS API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

/**
 * Get package details by code
 */
function getPackageDetails($packageCode) {
    global $pdo, $conn;
    
    try {
        $packageCode = trim($packageCode);
        
        if (empty($packageCode)) {
            return null;
        }
        
        // Try PDO first
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT * FROM packages WHERE package_code = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$packageCode]);
            return $stmt->fetch();
        }
        // Fallback to mysqli
        elseif (isset($conn)) {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE package_code = ? AND is_active = 1 LIMIT 1");
            $stmt->bind_param("s", $packageCode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting package details: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if package is special (unlimited/dayplans)
 */
function isSpecialPackage($packageCode) {
    try {
        $package = getPackageDetails($packageCode);
        
        if (!$package) {
            return false;
        }
        
        $name = strtolower($package['name'] ?? '');
        $description = strtolower($package['description'] ?? '');
        $type = strtolower($package['type'] ?? '');
        
        $specialKeywords = ['unlimited', 'dayplans', 'day plans', 'daily', 'per day'];
        
        foreach ($specialKeywords as $keyword) {
            if (strpos($name, $keyword) !== false || 
                strpos($description, $keyword) !== false || 
                strpos($type, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking if package is special: " . $e->getMessage());
        return false;
    }
}

/**
 * Get list of available countries
 */
function getCountryList() {
    try {
        $response = getPackageList();
        $countries = [];
        
        if (isset($response["success"]) && $response["success"] && isset($response["obj"]["packageList"])) {
            foreach ($response["obj"]["packageList"] as $package) {
                if (isset($package["locationNetworkList"])) {
                    foreach ($package["locationNetworkList"] as $location) {
                        if (!isset($countries[$location["locationCode"]])) {
                            $countries[$location["locationCode"]] = [
                                "name" => $location["locationName"],
                                "logo" => $location["locationLogo"] ?? ""
                            ];
                        }
                    }
                }
            }
        }
        
        return $countries;
    } catch (Exception $e) {
        error_log("Error getting country list: " . $e->getMessage());
        return [];
    }
}

/**
 * Get regional packages
 */
function getRegionalPackages() {
    return getPackageList("!RG"); // Regional package code
}

/**
 * Get global packages
 */
function getGlobalPackages() {
    return getPackageList("!GL"); // Global package code
}

/**
 * Revoke (force remove) an eSIM
 */
function revokeEsim($esimTranNo, $iccid = '') {
    try {
        $esimTranNo = trim($esimTranNo);
        $iccid = trim($iccid);
        
        if (empty($esimTranNo) && empty($iccid)) {
            return ["success" => false, "errorMsg" => "Either esimTranNo or iccid must be provided"];
        }
        
        $payload = [
            "esimTranNo" => $esimTranNo,
            "iccid" => $iccid
        ];
        
        return executeApiRequest('/esim/revoke', $payload);
    } catch (Exception $e) {
        error_log("Revoke eSIM API Error: " . $e->getMessage());
        return ["success" => false, "errorMsg" => $e->getMessage()];
    }
}

// Helper function to test API
function testEsimApiConnection(): array {
    try {
        $balance = getMerchantBalance();
        
        if (isset($balance['success']) && $balance['success']) {
            return [
                'success' => true,
                'message' => 'API connection successful',
                'balance' => $balance['obj']['balance'] ?? 'Unknown'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'API connection failed: ' . ($balance['errorMsg'] ?? 'Unknown error')
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'API test failed: ' . $e->getMessage()
        ];
    }
}

// Log that API file is loaded
error_log("📁 eSIM API file loaded - ALL functions preserved + auto-initialization added");
?>