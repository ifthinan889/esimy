<?php
/**
 * Helper Functions Library
 * Contains utility functions for currency, status, formatting, and eSIM operations
 * UPDATED: Full PDO compatibility
 */

// Prevent direct access
if (!defined('ALLOWED_ACCESS')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// ===========================================
// EXCHANGE RATE & CURRENCY FUNCTIONS
// ===========================================

/**
 * Fetch exchange rate data from API
 */
function fetchExchangeRateData() {
    $apiUrl = "https://api.exchangerate-api.com/v4/latest/USD";
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'eSIM-Portal/1.0'
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Failed to get data from API: $error");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("API returned HTTP code $httpCode");
    }

    $data = json_decode($response, true);
    if (!isset($data['rates']) || !is_array($data['rates'])) {
        throw new Exception("Invalid API data format");
    }

    return $data['rates'];
}

/**
 * Get app settings with type conversion (PDO ONLY)
 */
function getAppSettings(): array {
    global $pdo;
    $settings = [];
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value, setting_type FROM app_settings");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $row) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            $type = $row['setting_type'] ?? 'string';
            
            // Convert value based on type
            $settings[$key] = convertSettingValue($value, $type);
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching app settings: " . $e->getMessage());
    }
    
    return $settings;
}

/**
 * Convert setting value based on type
 */
function convertSettingValue(string $value, string $type): mixed {
    return match($type) {
        'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on']),
        'integer' => (int)$value,
        'float' => (float)$value,
        'json' => json_decode($value, true) ?? $value,
        'array' => json_decode($value, true) ?? explode(',', $value),
        default => $value // string
    };
}

/**
 * Update app setting with type validation (PDO ONLY)
 */
function updateAppSetting(string $key, mixed $value, string $type = 'string', string $description = ''): bool {
    global $pdo;
    
    try {
        // Convert value to string for storage
        $stringValue = match($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string)$value
        };
        
        // First, try to update existing record
        $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ?, setting_type = ?, description = ? WHERE setting_key = ?");
        $stmt->execute([$stringValue, $type, $description, $key]);
        
        // If no rows were affected, insert new record
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$key, $stringValue, $type, $description]);
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error updating app setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Update currency rates from API and save to database (PDO ONLY)
 */
function updateCurrencyRatesFromApi() {
    try {
        $rates = fetchExchangeRateData();
        $usdRate = $rates['IDR'] ?? null;

        if (!is_numeric($usdRate) || $usdRate <= 0) {
            throw new Exception("Invalid USD-IDR exchange rate: " . var_export($usdRate, true));
        }

        $currentDateTime = date("Y-m-d H:i:s");

        // Save to database using helper function
        $saved = dbInsert('shop_settings', [
            'setting_key' => 'exchange_rate',
            'setting_value' => $usdRate,
            'updated_at' => $currentDateTime
        ]);

        if (!$saved) {
            // Try update if insert fails
            dbQuery("UPDATE shop_settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?", 
                   [$usdRate, $currentDateTime, 'exchange_rate']);
        }

        error_log("USD exchange rate updated: $usdRate at $currentDateTime");

        return [
            'success' => true,
            'message' => "Exchange rate updated successfully",
            'rate' => $usdRate
        ];
    } catch (Exception $e) {
        error_log("Exchange rate update error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Failed to update exchange rate: " . $e->getMessage()
        ];
    }
}

/**
 * Get current USD to IDR exchange rate (PDO ONLY)
 */
function getCurrentExchangeRate() {
    $result = dbQuery("SELECT setting_value FROM shop_settings WHERE setting_key = ? LIMIT 1", 
                     ['exchange_rate'], false);
    
    if ($result && isset($result['setting_value']) && is_numeric($result['setting_value'])) {
        return (float)$result['setting_value'];
    }
    
    // If no rate found, try to update from API
    $updateResult = updateCurrencyRatesFromApi();
    if ($updateResult['success'] && isset($updateResult['rate'])) {
        return (float)$updateResult['rate'];
    }
    
    // Default fallback
    return 17500.0;
}

/**
 * Convert USD to IDR
 */
function convertUsdToIdr($usdAmount) {
    $usdAmount = max(0, (float)$usdAmount);
    $exchangeRate = getCurrentExchangeRate();
    return $usdAmount * $exchangeRate;
}

/**
 * Convert IDR to USD
 */
function convertIdrToUsd($idrAmount) {
    $idrAmount = max(0, (float)$idrAmount);
    $exchangeRate = max(1, getCurrentExchangeRate());
    return $idrAmount / $exchangeRate;
}

/**
 * Format currency amount
 */
function formatCurrency($amount, $currency = 'USD') {
    $amount = (float)$amount;
    
    return ($currency === 'IDR') 
        ? 'Rp ' . number_format($amount, 0, ',', '.')
        : '$' . number_format($amount, 2, '.', ',');
}

// ===========================================
// PRICING & MARKUP FUNCTIONS
// ===========================================

/**
 * Calculate final price with markup
 */
function calculateFinalPrice($basePrice, $exchangeRate, $volumeGB, $tieredMarkupConfig) {
    $basePrice = max(0, (float)$basePrice);
    $exchangeRate = max(0, (float)$exchangeRate);
    $volumeGB = max(0, (float)$volumeGB);
    
    $markupValue = getTieredMarkup($volumeGB, $tieredMarkupConfig);
    $priceIdr = round($basePrice * $exchangeRate);
    $finalPriceIdr = $priceIdr + $markupValue;
    
    error_log("Price calculation - USD: $basePrice, Rate: $exchangeRate, IDR: $priceIdr, Markup: $markupValue, Final: $finalPriceIdr");
    
    return $finalPriceIdr;
}

/**
 * Get tiered markup based on data volume
 */
function getTieredMarkup($volumeGB, $tieredMarkupConfig) {
    $volumeGB = max(0, (float)$volumeGB);
    
    if (!is_array($tieredMarkupConfig) || empty($tieredMarkupConfig)) {
        return 10000; // Default markup
    }
    
    foreach ($tieredMarkupConfig as $tier) {
        if (isset($tier['limit'], $tier['markup']) && $volumeGB <= (float)$tier['limit']) {
            return (float)$tier['markup'];
        }
    }
    
    // Return highest tier markup if volume exceeds all tiers
    $lastTier = end($tieredMarkupConfig);
    return isset($lastTier['markup']) ? (float)$lastTier['markup'] : 10000;
}

/**
 * Calculate markup based on type
 */
function calculateMarkup($basePrice, $markupType, $markupValue) {
    $basePrice = (float)$basePrice;
    $markupValue = (float)$markupValue;
    
    if ($markupType === 'percentage') {
        return $basePrice * (1 + ($markupValue / 100));
    }
    
    $markupUsd = convertIdrToUsd($markupValue);
    return $basePrice + $markupUsd;
}

// ===========================================
// STATUS FUNCTIONS
// ===========================================

/**
 * Status mappings and configurations
 */
class StatusConfig {
    public static function getIndonesianMapping() {
        return [
            'CREATE' => 'Dibuat',
            'PAYING' => 'Pembayaran',
            'PAID' => 'Dibayar',
            'GETTING_RESOURCE' => 'Mempersiapkan',
            'GOT_RESOURCE' => 'Siap Dipasang',
            'IN_USE' => 'Digunakan',
            'USED_UP' => 'Habis',
            'UNUSED_EXPIRED' => 'Kadaluarsa',
            'USED_EXPIRED' => 'Kadaluarsa',
            'CANCEL' => 'Dibatalkan',
            'SUSPENDED' => 'Digunakan',
            'REVOKE' => 'Dicabut',
            'ENABLED' => 'Terpasang',
            'ACTIVE' => 'Aktif',
            'NEW' => 'Baru',
            'ONBOARD' => 'Siap Dipasang',
            'DEPLETED' => 'Kuota Habis',
            'DELETED' => 'Dihapus',
            'RELEASED' => 'Belum Dipasang',
            'DISABLED' => 'Dinonaktifkan'
        ];
    }

    public static function getColorMapping() {
        return [
            'CREATE' => ['bg' => 'rgba(59, 130, 246, 0.2)', 'text' => '#1d4ed8'],
            'PAYING' => ['bg' => 'rgba(245, 158, 11, 0.2)', 'text' => '#b45309'],
            'PAID' => ['bg' => 'rgba(34, 197, 94, 0.2)', 'text' => '#15803d'],
            'GETTING_RESOURCE' => ['bg' => 'rgba(59, 130, 246, 0.2)', 'text' => '#1d4ed8'],
            'GOT_RESOURCE' => ['bg' => 'rgba(34, 197, 94, 0.2)', 'text' => '#15803d'],
            'IN_USE' => ['bg' => 'rgba(34, 197, 94, 0.2)', 'text' => '#15803d'],
            'USED_UP' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'text' => '#b91c1c'],
            'UNUSED_EXPIRED' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'text' => '#b91c1c'],
            'USED_EXPIRED' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'text' => '#b91c1c'],
            'CANCEL' => ['bg' => 'rgba(100, 116, 139, 0.2)', 'text' => '#475569'],
            'SUSPENDED' => ['bg' => 'rgba(245, 158, 11, 0.2)', 'text' => '#b45309'],
            'REVOKE' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'text' => '#b91c1c'],
            'ENABLED' => ['bg' => 'rgba(56, 189, 248, 0.2)', 'text' => '#0284c7'],
            'ACTIVE' => ['bg' => 'rgba(74, 222, 128, 0.2)', 'text' => '#16a34a'],
            'NEW' => ['bg' => 'rgba(147, 197, 253, 0.2)', 'text' => '#1e40af'],
            'ONBOARD' => ['bg' => 'rgba(252, 211, 77, 0.2)', 'text' => '#b45309'],
            'DEPLETED' => ['bg' => 'rgba(252, 165, 165, 0.2)', 'text' => '#b91c1c'],
            'RELEASED' => ['bg' => 'rgba(234, 179, 8, 0.2)', 'text' => '#a16207'],
            'DISABLED' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'text' => '#b91c1c'],
            'DELETED' => ['bg' => 'rgba(100, 116, 139, 0.2)', 'text' => '#475569']
        ];
    }

    public static function getEmojiMapping() {
        return [
            'NEW' => '🆕',
            'ONBOARD' => '📲',
            'IN_USE' => '✅',
            'DEPLETED' => '⚠️',
            'USED_UP' => '⚠️',
            'ENABLED' => '✅',
            'ACTIVE' => '✅',
            'GOT_RESOURCE' => '📲',
            'RELEASED' => '🔄',
            'REVOKE' => '⛔',
            'DELETED' => '❌',
            'SUSPENDED' => '⏸️',
            'CANCEL' => '🛑',
            'DISABLED' => '🚫'
        ];
    }
}

/**
 * Get SMDP status in Indonesian
 */
function getSmdpStatusIndonesia($smdpStatus) {
    $mapping = [
        'RELEASED' => 'Belum Dipasang',
        'ENABLED' => 'Terpasang',
        'DISABLED' => 'Dinonaktifkan',
        'DELETED' => 'Dihapus',
        'DOWNLOADED' => 'Terunduh',
        'INSTALLED' => 'Terinstal'
    ];
    
    return $mapping[strtoupper($smdpStatus)] ?? $smdpStatus;
}

/**
 * Get SMDP status color
 */
function getSmdpStatusColor($smdpStatus) {
    $colors = [
        'RELEASED' => ['bg' => 'rgba(234, 179, 8, 0.2)', 'text' => '#a16207'],
        'ENABLED' => ['bg' => 'rgba(34, 197, 94, 0.2)', 'text' => '#15803d'],
        'DISABLED' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'text' => '#b91c1c'],
        'DELETED' => ['bg' => 'rgba(100, 116, 139, 0.2)', 'text' => '#475569'],
        'DOWNLOADED' => ['bg' => 'rgba(59, 130, 246, 0.2)', 'text' => '#1d4ed8'],
        'INSTALLED' => ['bg' => 'rgba(168, 85, 247, 0.2)', 'text' => '#7c3aed']
    ];
    
    return $colors[strtoupper($smdpStatus)] ?? ['bg' => 'rgba(100, 116, 139, 0.2)', 'text' => '#475569'];
}

/**
 * Get status in Indonesian
 */
function getStatusIndonesia($status) {
    $mapping = StatusConfig::getIndonesianMapping();
    return $mapping[$status] ?? $status;
}

// PERBAIKI: Helper functions dengan status dibatalkan
if (!function_exists('getStatusClass')) {
    function getStatusClass($status) {
        $status = strtolower(str_replace('_', '-', $status));
        switch ($status) {
            case 'in-use':
            case 'active':
                return 'active';
            case 'pending':
            case 'new':
            case 'onboard':
                return 'pending';
            case 'expired':
            case 'used-up':
            case 'depleted':
                return 'expired';
            case 'suspended':
                return 'suspended';
            case 'cancelled':
            case 'canceled':
            case 'dibatalkan':
            case 'cancel':
                return 'cancelled';
            default:
                return 'unknown';
        }
    }
}

/**
 * Get status color
 */
function getStatusColor($status) {
    $mapping = StatusConfig::getColorMapping();
    return $mapping[$status] ?? ['bg' => 'rgba(100, 116, 139, 0.2)', 'text' => '#475569'];
}

/**
 * Get status emoji
 */
function getStatusEmoji($status) {
    $mapping = StatusConfig::getEmojiMapping();
    return $mapping[$status] ?? '❓';
}

/**
 * Get combined eSIM status
 */
function getEsimCombinedStatus($smdpStatus, $esimStatus, $orderUsage = 0, $eid = "") {
    $smdpStatus = strtoupper(trim($smdpStatus));
    $esimStatus = strtoupper(trim($esimStatus));
    
    // Status determination logic
    if ($smdpStatus == 'RELEASED' && $esimStatus == 'GOT_RESOURCE' && $orderUsage == 0 && empty($eid)) {
        return 'NEW';
    }
    
    if ($smdpStatus == 'ENABLED' && in_array($esimStatus, ['IN_USE', 'GOT_RESOURCE']) && $orderUsage == 0 && !empty($eid)) {
        return 'ONBOARD';
    }
    
    if (in_array($smdpStatus, ['ENABLED', 'DISABLED']) && $esimStatus == 'IN_USE' && $orderUsage > 0 && !empty($eid)) {
        return 'IN_USE';
    }
    
    if (in_array($smdpStatus, ['ENABLED', 'DISABLED']) && $esimStatus == 'USED_UP' && $orderUsage > 0 && !empty($eid)) {
        return 'DEPLETED';
    }
    
    if ($smdpStatus == 'DELETED' && in_array($esimStatus, ['USED_UP', 'IN_USE']) && $orderUsage > 0 && !empty($eid)) {
        return 'DELETED';
    }
    
    // Fallback to esimStatus
    if (in_array($esimStatus, ['IN_USE', 'ACTIVE', 'ENABLED'])) return 'IN_USE';
    if ($esimStatus == 'USED_UP') return 'DEPLETED';
    if ($esimStatus == 'GOT_RESOURCE') return 'NEW';
    
    return $esimStatus;
}

// ===========================================
// ESIM OPERATIONS (PDO ONLY)
// ===========================================

/**
 * Update eSIM status in database (PDO ONLY)
 */
function updateEsimStatus($iccid, $apiStatus, $smdpStatus = null, $orderUsage = 0, $eid = "") {
    $combinedStatus = getEsimCombinedStatus($smdpStatus, $apiStatus, $orderUsage, $eid);
    $dbStatus = mapCombinedStatusToDb($combinedStatus);
    $currentTime = date('Y-m-d H:i:s');
    
    // Check existing activation date
    $existing = dbQuery("SELECT activation_date FROM esim_orders WHERE iccid = ?", [$iccid], false);
    $activationDate = ($dbStatus === 'active' && empty($existing['activation_date'])) ? $currentTime : null;
    
    $updateData = [
        'status' => $dbStatus,
        'last_status_check' => $currentTime,
        'smdp_status' => $smdpStatus,
        'esim_status' => $apiStatus,
        'order_usage' => $orderUsage,
        'eid' => $eid
    ];
    
    if ($activationDate) {
        $updateData['activation_date'] = $activationDate;
    }
    
    return dbUpdate('esim_orders', $updateData, ['iccid' => $iccid]);
}

/**
 * Map combined status to database status
 */
function mapCombinedStatusToDb($combinedStatus) {
    $mapping = [
        'IN_USE' => 'active',
        'DEPLETED' => 'used_up',
        'USED_UP' => 'used_up',
        'UNUSED_EXPIRED' => 'expired',
        'USED_EXPIRED' => 'expired',
        'DELETED' => 'deleted',
        'CANCEL' => 'cancelled',
        'NEW' => 'new',
        'ONBOARD' => 'onboard'
    ];
    
    return $mapping[$combinedStatus] ?? 'pending';
}

/**
 * Get eSIM status from database (PDO ONLY)
 */
function getStatus($iccid) {
    $row = dbQuery("SELECT status, smdp_status, esim_status, order_usage, eid FROM esim_orders WHERE iccid = ?", 
                   [$iccid], false);
    
    if ($row) {
        $row['combined_status'] = getEsimCombinedStatus(
            $row['smdp_status'],
            $row['esim_status'],
            $row['order_usage'],
            $row['eid']
        );
    }
    
    return $row;
}

/**
 * Refresh eSIM status from API (PDO ONLY)
 */
function refreshStatus($iccid, $esimTranNo = '') {
    if (empty($iccid)) return false;
    
    try {
        // This function should be implemented in your API integration file
        $esimDetails = queryEsimDetails('', $iccid, $esimTranNo);
        
        if (isset($esimDetails["success"], $esimDetails["obj"]["esimList"][0]) && $esimDetails["success"]) {
            $esim = $esimDetails["obj"]["esimList"][0];
            return updateEsimStatus(
                $iccid,
                $esim['esimStatus'],
                $esim['smdpStatus'] ?? null,
                intval($esim['orderUsage'] ?? 0),
                $esim['eid'] ?? ""
            );
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error refreshing status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get status statistics (PDO ONLY)
 */
function getStatusStatistics() {
    $stats = [];
    $results = dbQuery("SELECT status, COUNT(*) as count FROM esim_orders GROUP BY status");
    
    foreach ($results as $row) {
        $stats[$row['status']] = $row['count'];
    }
    
    return $stats;
}

// ===========================================
// FORMATTING & UTILITY FUNCTIONS
// ===========================================

/**
 * Generate secure token
 */
function generateToken($length = 12) {
    $bytes = random_bytes($length);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $token = '';
    
    for ($i = 0; $i < $length; $i++) {
        $token .= $chars[ord($bytes[$i]) % strlen($chars)];
    }
    
    return $token;
}

/**
 * Format bytes to human-readable format
 */
function formatBytes($bytes, $precision = 2) {
    $bytes = max(0, (int)$bytes);
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    if ($bytes === 0) return '0 B';
    
    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $value = $bytes / pow(1024, $pow);
    return round($value, $precision) . ' ' . $units[$pow];
}

/**
 * Format date in Indonesian format
 */
function formatTanggal($date, $withTime = true) {
    if (empty($date)) return '-';
    
    $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    try {
        $tanggal = new DateTime($date, new DateTimeZone('UTC'));
        $tanggal->setTimezone(new DateTimeZone('Asia/Jakarta'));
        
        $format = $tanggal->format('d') . ' ' . 
                  $bulan[$tanggal->format('n') - 1] . ' ' . 
                  $tanggal->format('Y');
        $hour = (int)$tanggal->format('H');
                  
        if ($withTime) {
            // Emoji berdasarkan waktu
            $timeEmoji = '';
            $timePeriod = '';
            
            if ($hour >= 5 && $hour < 10) {
                $timeEmoji = '🌅';
                $timePeriod = 'Pagi';
            } elseif ($hour >= 10 && $hour < 15) {
                $timeEmoji = '☀️';
                $timePeriod = 'Siang';
            } elseif ($hour >= 15 && $hour < 18) {
                $timeEmoji = '🌤️';
                $timePeriod = 'Sore';
            } elseif ($hour >= 18 && $hour < 22) {
                $timeEmoji = '🌆';
                $timePeriod = 'Malam';
            } else {
                $timeEmoji = '🌙';
                $timePeriod = 'Dini Hari';
            }
            
            $format .= ' ' . $tanggal->format('H:i') . ' ' . $timeEmoji . ' ' . $timePeriod;
        }
        
        return $format;
    } catch (Exception $e) {
        error_log("Date format error: " . $e->getMessage());
        return $date;
    }
}

/**
 * Calculate remaining days before expiry
 */
function hitungSisaHari($expiredTime) {
    if (empty($expiredTime)) return 0;
    
    try {
        $expiredDate = new DateTime($expiredTime);
        $now = new DateTime();
        $interval = $now->diff($expiredDate);
        return $interval->invert ? 0 : $interval->days;
    } catch (Exception $e) {
        error_log("Error calculating remaining days: " . $e->getMessage());
        return 0;
    }
}

// ===========================================
// VALIDATION & SANITIZATION
// ===========================================

/**
 * Sanitize output for HTML
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    
    if (is_string($data)) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Validate and sanitize phone number
 */
function sanitizePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (empty($phone)) return '';
    
    if ($phone[0] !== '+') {
        if ($phone[0] === '0') {
            $phone = '+62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) === '62') {
            $phone = '+' . $phone;
        } else {
            $phone = '+62' . $phone;
        }
    }
    
    return $phone;
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Escape JSON for HTML output
 */
function safeJsonEncode($data) {
    return htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
}

// ===========================================
// JAVASCRIPT GENERATION
// ===========================================

/**
 * Generate JavaScript mappings for frontend
 */
function generateStatusJsMappings() {
    $statusMap = StatusConfig::getIndonesianMapping();
    $colors = StatusConfig::getColorMapping();

    return "
    <script>
    const statusMappings = " . json_encode($statusMap) . ";
    const statusColors = " . json_encode($colors) . ";
    
    function getStatusIndonesia(status) {
        return statusMappings[status] || status;
    }
    
    function getStatusColor(status) {
        return statusColors[status] || statusColors['UNKNOWN'] || {bg: 'rgba(100, 116, 139, 0.2)', text: '#475569'};
    }
    
    function formatBytes(bytes) {
        if (!bytes) return '0 B';
        bytes = parseInt(bytes);
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        if (bytes === 0) return '0 B';
        const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + ' ' + sizes[i];
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    </script>";
}

// ===========================================
// LOGGING & DEBUGGING
// ===========================================

/**
 * Log API response for debugging
 */
function logApiResponse($actionType, $response) {
    $logDir = __DIR__ . '/../logs';
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
   $logFile = $logDir . '/api_' . date('Y-m-d') . '.log';
   $timestamp = date('Y-m-d H:i:s');
   $logData = "[$timestamp] $actionType: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
   
   file_put_contents($logFile, $logData, FILE_APPEND);
}

/**
* Force refresh exchange rate
*/
function forceRefreshExchangeRate() {
   // Clear cache if exists
   $cachePath = __DIR__ . '/../cache/exchange_rate.json';
   if (file_exists($cachePath)) {
       @unlink($cachePath);
   }
   
   // Clear opcache if available
   if (function_exists('opcache_reset')) {
       opcache_reset();
   }
   
   return updateCurrencyRatesFromApi();
}

// ===========================================
// ADDITIONAL PDO HELPER FUNCTIONS
// ===========================================

/**
* Get single setting value (PDO ONLY)
*/
function getSetting($key, $default = null) {
   $result = dbQuery("SELECT setting_value, setting_type FROM app_settings WHERE setting_key = ? LIMIT 1", 
                    [$key], false);
   
   if ($result) {
       return convertSettingValue($result['setting_value'], $result['setting_type'] ?? 'string');
   }
   
   return $default;
}

/**
* Check if eSIM exists in database (PDO ONLY)
*/
function esimExists($iccid) {
   $result = dbQuery("SELECT COUNT(*) as count FROM esim_orders WHERE iccid = ?", [$iccid], false);
   return $result ? $result['count'] > 0 : false;
}

/**
* Get eSIM order by token (PDO ONLY)
*/
function getEsimByToken($token) {
   return dbQuery("SELECT * FROM esim_orders WHERE token = ? LIMIT 1", [$token], false);
}

/**
* Get eSIM order by ICCID (PDO ONLY)
*/
function getEsimByIccid($iccid) {
   return dbQuery("SELECT * FROM esim_orders WHERE iccid = ? LIMIT 1", [$iccid], false);
}

/**
* Get eSIM order by Order Number (PDO ONLY)
*/
function getEsimByOrderNo($orderNo) {
   return dbQuery("SELECT * FROM esim_orders WHERE orderNo = ? LIMIT 1", [$orderNo], false);
}

/**
* Update eSIM order data (PDO ONLY)
*/
function updateEsimOrder($iccid, $updateData) {
   $updateData['updated_at'] = date('Y-m-d H:i:s');
   return dbUpdate('esim_orders', $updateData, ['iccid' => $iccid]);
}

/**
* Log user activity (PDO ONLY)
*/
function logUserActivity($action, $details = '', $userId = null, $ipAddress = null) {
   $logData = [
       'user_id' => $userId,
       'action' => $action,
       'details' => $details,
       'ip_address' => $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
       'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
       'created_at' => date('Y-m-d H:i:s')
   ];
   
   return dbInsert('user_activity_logs', $logData);
}

/**
* Get order statistics (PDO ONLY)
*/
function getOrderStatistics($dateFrom = null, $dateTo = null) {
   $whereClause = "WHERE 1=1";
   $params = [];
   
   if ($dateFrom) {
       $whereClause .= " AND created_at >= ?";
       $params[] = $dateFrom;
   }
   
   if ($dateTo) {
       $whereClause .= " AND created_at <= ?";
       $params[] = $dateTo;
   }
   
   $stats = [];
   
   // Total orders
   $result = dbQuery("SELECT COUNT(*) as total FROM esim_orders $whereClause", $params, false);
   $stats['total_orders'] = $result['total'] ?? 0;
   
   // Orders by status
   $statusResults = dbQuery("SELECT status, COUNT(*) as count FROM esim_orders $whereClause GROUP BY status", $params);
   $stats['by_status'] = [];
   foreach ($statusResults as $row) {
       $stats['by_status'][$row['status']] = $row['count'];
   }
   
   // Total revenue
   $result = dbQuery("SELECT SUM(final_price) as revenue FROM esim_orders $whereClause AND status NOT IN ('cancelled', 'failed')", $params, false);
   $stats['total_revenue'] = $result['revenue'] ?? 0;
   
   return $stats;
}

/**
* Clean old logs (PDO ONLY)
*/
function cleanOldLogs($daysToKeep = 30) {
   $cutoffDate = date('Y-m-d', strtotime("-$daysToKeep days"));
   
   $deletedCount = 0;
   
   // Clean user activity logs
   $result = dbQuery("DELETE FROM user_activity_logs WHERE created_at < ?", [$cutoffDate]);
   if ($result) $deletedCount++;
   
   // Clean file-based logs
   $logDir = __DIR__ . '/../logs';
   if (is_dir($logDir)) {
       $files = glob($logDir . '/*.log');
       foreach ($files as $file) {
           if (filemtime($file) < strtotime("-$daysToKeep days")) {
               if (unlink($file)) {
                   $deletedCount++;
               }
           }
       }
   }
   
   return $deletedCount;
}

/**
* Validate and format phone number with country code (PDO ONLY)
*/
function formatPhoneNumber($phone, $countryCode = '+62') {
   // Remove all non-numeric characters except +
   $phone = preg_replace('/[^0-9+]/', '', $phone);
   
   if (empty($phone)) return '';
   
   // Remove leading + if present
   $phone = ltrim($phone, '+');
   
   // Handle Indonesian numbers
   if ($countryCode === '+62') {
       if (substr($phone, 0, 2) === '62') {
           return '+' . $phone;
       } elseif (substr($phone, 0, 1) === '0') {
           return '+62' . substr($phone, 1);
       } else {
           return '+62' . $phone;
       }
   }
   
   // For other countries, just add the country code
   if (!str_starts_with($phone, ltrim($countryCode, '+'))) {
       return $countryCode . $phone;
   }
   
   return '+' . $phone;
}

/**
* Generate QR code URL for eSIM activation
*/
function generateQrCodeUrl($activationCode, $size = 300) {
   $baseUrl = "https://api.qrserver.com/v1/create-qr-code/";
   $params = http_build_query([
       'size' => $size . 'x' . $size,
       'data' => $activationCode,
       'format' => 'png',
       'ecc' => 'M'
   ]);
   
   return $baseUrl . '?' . $params;
}

/**
* Validate eSIM activation code format
*/
function validateActivationCode($code) {
   // Check if it's in LPA format: LPA:1$smdp.address$activation.code
   if (preg_match('/^LPA:1\$[^$]+\$[^$]+$/', $code)) {
       return true;
   }
   
   // Check if it's in simple AC format: $smdp.address$activation.code
   if (substr_count($code, '$') >= 2) {
       return true;
   }
   
   return false;
}

/**
* Parse activation code components
*/
function parseActivationCode($code) {
   $components = [
       'full_code' => $code,
       'smdp_address' => '',
       'activation_code' => '',
       'confirmation_code' => ''
   ];
   
   // Remove LPA:1$ prefix if present
   $code = preg_replace('/^LPA:1\$/', '', $code);
   
   $parts = explode('$', $code);
   
   if (count($parts) >= 2) {
       $components['smdp_address'] = $parts[0] ?? '';
       $components['activation_code'] = $parts[1] ?? '';
       $components['confirmation_code'] = $parts[2] ?? '';
   }
   
   return $components;
}

/**
* Check if database connection is healthy (PDO ONLY)
*/
function isDatabaseHealthy() {
   global $pdo;
   
   try {
       $stmt = $pdo->query("SELECT 1");
       return $stmt !== false;
   } catch (PDOException $e) {
       error_log("Database health check failed: " . $e->getMessage());
       return false;
   }
}

/**
* Get system health status
*/
function getSystemHealth() {
   $health = [
       'database' => isDatabaseHealthy(),
       'php_version' => PHP_VERSION,
       'memory_usage' => memory_get_usage(true),
       'memory_peak' => memory_get_peak_usage(true),
       'disk_free' => disk_free_space(__DIR__),
       'timestamp' => date('Y-m-d H:i:s')
   ];
   
   $health['overall'] = $health['database'] && $health['disk_free'] > 100 * 1024 * 1024; // 100MB minimum
   
   return $health;
}

?>