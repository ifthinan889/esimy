<?php
/**
 * Master Configuration File
 * All core settings, security functions, and constants
 */

// Prevent direct access
if (!defined('ALLOWED_ACCESS')) {
    define('ALLOWED_ACCESS', true);
}

// Security settings
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hanisyaa_esim_db');
define('DB_PASS', 'Solokota10.');
define('DB_NAME', 'hanisyaa_esim_db');

// Payment Gateway Constants
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-X3UsCuFOja1mcjgletqsbL0v');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-Z5R-UZvpYchyArSC');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Auto-detect BASE_URL
$isCLI = (php_sapi_name() === 'cli');
if ($isCLI) {
    $protocol = 'http://';
    $host = 'localhost';
    $baseDir = '';
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseDir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
}
define('BASE_URL', $protocol . $host . ($baseDir ? '/' . $baseDir : ''));

// ===========================================
// UTILITY FUNCTIONS
// ===========================================

function asset($path) {
    return rtrim(BASE_URL, '/') . '/assets/' . ltrim($path, '/');
}

function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// ===========================================
// INPUT VALIDATION FUNCTIONS
// ===========================================

function validateInput($input, $type = 'string', $options = []) {
    switch($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
        case 'int':
            $min = $options['min'] ?? PHP_INT_MIN;
            $max = $options['max'] ?? PHP_INT_MAX;
            return filter_var($input, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => $min, 'max_range' => $max]
            ]) !== false;
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) !== false;
        case 'alphanumeric':
            return preg_match('/^[a-zA-Z0-9]+$/', $input) === 1;
        case 'phone':
            return preg_match('/^[0-9+\-\s()]+$/', $input) === 1;
        // TAMBAHAN UNTUK TOPUP.PHP:
        case 'token':
            return preg_match('/^[A-Z0-9]{8,20}$/', $input) ? $input : '';
        case 'iccid':
            return preg_match('/^[0-9]{15,22}$/', $input) ? $input : '';
        case 'order_id':
            return preg_match('/^[A-Z0-9\-]{10,50}$/', $input) ? $input : '';
        case 'status':
            return in_array($input, ['sukses', 'gagal', 'pending']) ? $input : '';
        default:
            return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

// ===========================================
// ENCRYPTION FUNCTIONS
// ===========================================

function encryptData($data, $key = null) {
    if ($key === null) {
        $key = hash('sha256', $_SERVER['SERVER_NAME'] . 'secure_salt_' . date('Ym'), true);
    }
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . base64_encode($iv));
}

function decryptData($encryptedData, $key = null) {
    if ($key === null) {
        $key = hash('sha256', $_SERVER['SERVER_NAME'] . 'secure_salt_' . date('Ym'), true);
    }
    try {
        list($encrypted, $ivBase64) = explode('::', base64_decode($encryptedData), 2);
        $iv = base64_decode($ivBase64);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        return json_decode($decrypted, true);
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return null;
    }
}

// ===========================================
// CSRF PROTECTION
// ===========================================

function generateCSRFToken() {
    // Pastikan session sudah dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time'] > 3600)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    // Pastikan session sudah dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        error_log("CSRF Token not found in session");
        return false;
    }
    
    if (!$token || empty($token)) {
        error_log("Empty CSRF token in request");
        return false;
    }
    
    $result = hash_equals($_SESSION['csrf_token'], $token);
    if (!$result) {
        error_log("CSRF token mismatch. Expected: " . $_SESSION['csrf_token'] . ", Got: " . $token);
    }
    
    return $result;
}

// ===========================================
// LOGGING FUNCTIONS
// ===========================================

function logSecurityEvent($message, $level = 'info') {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/security_' . date('Y-m-d') . '.log';
    $logEntry = date('Y-m-d H:i:s') . " [$level] " . 
                "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . " - " . 
                $message . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    chmod($logFile, 0600);
}

function logDetailAccess($token, $action, $details = '') {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/detail_access_' . date('Y-m-d') . '.log';
    $tokenShort = substr($token, 0, 8) . '...';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100);
    
    $logEntry = date('Y-m-d H:i:s') . " [INFO] " . 
                "Token: $tokenShort | Action: $action | IP: $ip | " .
                "UA: $userAgent | Details: $details\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    chmod($logFile, 0600);
}

// ===========================================
// SECURITY MIDDLEWARE
// ===========================================

function securityMiddleware() {
    // Block dangerous user agents
    $dangerousUserAgents = ['sqlmap', 'nikto', 'nessus', 'acunetix', 'metasploit'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    foreach ($dangerousUserAgents as $agent) {
        if (stripos($userAgent, $agent) !== false) {
            logSecurityEvent("Blocked malicious user agent: $userAgent", 'critical');
            http_response_code(403);
            include 'error.php';
            exit();
        }
    }
    
    // Validate URL path to prevent path traversal
    $requestURI = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestURI, '../') !== false || strpos($requestURI, '..\\') !== false) {
        logSecurityEvent("Path traversal attempt detected: $requestURI", 'critical');
        http_response_code(403);
        include 'error.php';
        exit();
    }
    
    // Rate limiting
    if (!isset($_SESSION['request_count'])) {
        $_SESSION['request_count'] = 1;
        $_SESSION['request_time'] = time();
    } else {
        $timeDiff = time() - $_SESSION['request_time'];
        if ($timeDiff < 1) {
            $_SESSION['request_count']++;
            if ($_SESSION['request_count'] > 20) {
                logSecurityEvent("Rate limit exceeded", 'warning');
                http_response_code(429);
                echo json_encode(['status' => 'error', 'message' => 'Too many requests']);
                exit();
            }
        } else {
            $_SESSION['request_count'] = 1;
            $_SESSION['request_time'] = time();
        }
    }
}

function setSecurityHeaders() {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    
    $cspHeader = "Content-Security-Policy: ";
    $cspHeader .= "default-src 'self'; ";
    $cspHeader .= "script-src 'self' 'unsafe-inline' blob: https://cdnjs.cloudflare.com https://code.jquery.com https://app.midtrans.com https://app.sandbox.midtrans.com https://www.googletagmanager.com; ";
    // $cspHeader .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/; ";
    $cspHeader .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; ";
    $cspHeader .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; ";
    $cspHeader .= "img-src 'self' data: blob: https://images.unsplash.com https://cdn-icons-png.flaticon.com https://p.qrsim.net https://api.qrserver.com; ";
    $cspHeader .= "connect-src 'self' https://*.midtrans.com https://www.google-analytics.com https://*.google-analytics.com https://*.googletagmanager.com; ";
    $cspHeader .= "frame-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com; ";
    $cspHeader .= "object-src 'none'; ";
    
    header($cspHeader);
}

// ===========================================
// RATE LIMITING SYSTEM
// ===========================================

function checkRateLimit($identifier, $maxRequests = 20, $timeWindow = 60) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    if (($now - $data['start']) > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    if ($data['count'] >= $maxRequests) {
        logSecurityEvent("Rate limit exceeded for: $identifier", 'warning');
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// ===========================================
// SESSION CONFIGURATION
// ===========================================

if (!$isCLI) {
    // Secure session settings
    // ini_set('session.use_cookies', 1);
    // ini_set('session.use_only_cookies', 1);
    // ini_set('session.cookie_httponly', 1);
    // ini_set('session.use_strict_mode', 1);
    // ini_set('session.cookie_lifetime', 0);
    // ini_set('session.gc_maxlifetime', 1800);

    // if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    //     ini_set('session.cookie_secure', 1);
    // }
    
    // // Start session - DIPINDAH KE ATAS
    // if (session_status() === PHP_SESSION_NONE) {
    //     session_start();
    // }
    
    // Run security middleware and set headers
    securityMiddleware();
    setSecurityHeaders();
}

// ===========================================
// ACCESS PROTECTION
// ===========================================

if (!$isCLI && basename($_SERVER['SCRIPT_FILENAME']) !== 'index.php' && !defined('ALLOWED_ACCESS')) {
    $logFile = __DIR__ . '/logs/unauthorized_access.log';
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " - Unauthorized access attempt\n" .
                "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n" .
                "URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n" .
                "Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'Unknown') . "\n\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    header('Location: error.php?code=403&message=' . urlencode('Akses Ditolak'));
    exit();
}
?>