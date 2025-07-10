<?php
/**
 * eSIM Detail Page - Modern & Mobile Friendly
 * Secure detail page with enhanced UX/UI
 */

define('ALLOWED_ACCESS', true);

// Include core files
require_once __DIR__ . '/../config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
try {
    require_once __DIR__ . '/../src/includes/koneksi.php'; // Naik satu level, lalu masuk ke src/includes
    require_once __DIR__ . '/../src/includes/functions.php';
    require_once __DIR__ . '/../src/includes/api.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    include 'error.php';
    exit();
}

// ===========================================
// SECURITY & VALIDATION FUNCTIONS
// ===========================================

/**
 * Enhanced rate limiting for detail page
 */
function checkDetailRateLimit($token) {
    $key = 'detail_' . substr($token, 0, 8);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $timeWindow = 300; // 5 minutes
    $maxRequests = 20;  // Increased for better UX
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['time'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    // Check rate limit
    if ($data['count'] >= $maxRequests) {
        logSecurityEvent("Detail page rate limit exceeded for token: " . substr($token, 0, 8), 'warning');
        
        http_response_code(429);
        showRateLimitError();
        exit();
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Show user-friendly rate limit error
 */
function showRateLimitError() {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Terlalu Banyak Permintaan</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: "Poppins", sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                padding: 20px;
            }
            .container { 
                max-width: 400px; 
                background: white; 
                padding: 40px 30px; 
                border-radius: 20px; 
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
            }
            .emoji { font-size: 64px; margin-bottom: 20px; animation: pulse 2s infinite; }
            @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
            h2 { color: #333; margin-bottom: 15px; font-weight: 600; }
            p { color: #666; line-height: 1.6; margin-bottom: 15px; }
            .btn { 
                display: inline-block; 
                padding: 12px 30px; 
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white; 
                text-decoration: none; 
                border-radius: 25px; 
                margin-top: 20px;
                font-weight: 500;
                transition: transform 0.2s;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="emoji">⏱️</div>
            <h2>Terlalu Banyak Permintaan</h2>
            <p>Untuk keamanan, akses dibatasi maksimal 20 kali dalam 5 menit.</p>
            <p>Silakan tunggu beberapa menit sebelum mencoba lagi.</p>
            <a href="https://wa.me/6281325525646" class="btn">💬 Hubungi Support</a>
        </div>
    </body>
    </html>';
}

/**
 * Validate API response
 */
function validateApiResponse($data, $orderNo) {
    if (!isset($data["success"]) || $data["success"] !== true) {
        error_log("API validation failed for order: $orderNo");
        return false;
    }
    
    if (!isset($data["obj"]["esimList"][0])) {
        error_log("API esimList empty for order: $orderNo");
        return false;
    }
    
    $esim = $data["obj"]["esimList"][0];
    
    // Check required fields
    if (!isset($esim['smdpStatus'], $esim['esimStatus'])) {
        error_log("API missing required fields for order: $orderNo");
        return false;
    }
    
    // Validate AC format if present
    if (isset($esim['ac']) && !empty($esim['ac'])) {
        if (substr_count($esim['ac'], '$') < 2) {
            error_log("API invalid AC format for order: $orderNo");
            return false;
        }
    }
    
    return true;
}

// ===========================================
// MAIN LOGIC
// ===========================================

// Validate and sanitize token input (menggunakan cara lama yang sudah terbukti bekerja)
$token = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW);
$token = htmlspecialchars(strip_tags(trim($token)), ENT_QUOTES, 'UTF-8');

if (empty($token) || !ctype_alnum($token)) {
    error_log("Invalid or empty token provided: " . ($token ?? 'null'));
    include 'error.php';
    exit();
}

// Apply rate limiting
checkDetailRateLimit($token);

// Get order data from database (menggunakan cara yang sudah ada sebelumnya)
// Ganti bagian database query di detail.php:
try {
    $row = dbQuery("SELECT nama, phone, orderNo, iccid FROM esim_orders WHERE token = ? LIMIT 1", [$token], false);
    
    if (!$row) {
        error_log("Token not found in database: " . $token);
        include 'error.php';
        exit();
    }
    
    $nama = htmlspecialchars($row["nama"], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($row["phone"] ?? '', ENT_QUOTES, 'UTF-8');
    $orderNo = htmlspecialchars($row["orderNo"], ENT_QUOTES, 'UTF-8');
    $iccid = htmlspecialchars($row["iccid"], ENT_QUOTES, 'UTF-8');
    
} catch (Exception $e) {
    error_log("Database error in detail.php: " . $e->getMessage());
    include 'error.php';
    exit();
}

// Log access (jika fungsi ada)
if (function_exists('logDetailAccess')) {
    logDetailAccess($token, 'view', "Order: $orderNo");
}

// ===========================================
// API CALL & DATA PROCESSING
// ===========================================

try {
    $data = queryEsimDetails($orderNo, $iccid);
    
    if (!validateApiResponse($data, $orderNo)) {
        throw new Exception("Invalid API response");
    }
    
    $esim = $data["obj"]["esimList"][0];
    
} catch (Exception $e) {
    error_log("API error for order $orderNo: " . $e->getMessage());
    $data = ["success" => false];
    include 'error.php';
    exit();
}

// ===========================================
// PROCESS ESIM DATA
// ===========================================

// Initialize variables with safe defaults
$sisaKuotaGB = 0;
$totalKuotaGB = 0;
$expiredTime = "";
$sisaHari = 0;
$persentaseExpired = 0;
$qrCodeUrl = "";
$smdpStatus = "";
$esimStatus = "";
$ac = "";
$ca = "";
$smdpAddress = "";
$activationCode = "";
$statusIndonesia = "Tidak Diketahui";
$statusColor = ['bg' => 'rgba(100, 116, 139, 0.2)', 'text' => '#475569'];
$kuotaHabis = false;
$iosLink = "";

// Process API response safely
$smdpStatus = htmlspecialchars($esim["smdpStatus"] ?? '', ENT_QUOTES, 'UTF-8');
$esimStatus = htmlspecialchars($esim["esimStatus"] ?? '', ENT_QUOTES, 'UTF-8');
$statusIndonesia = htmlspecialchars(getStatusIndonesia($esimStatus), ENT_QUOTES, 'UTF-8');
$statusColor = getStatusColor($esimStatus);

$ac = htmlspecialchars($esim["ac"] ?? '', ENT_QUOTES, 'UTF-8');
$orderUsage = isset($esim["orderUsage"]) ? round($esim["orderUsage"] / (1024**3), 2) : 0;

// Parse AC string to get SM-DP+ address and activation code
$acParts = explode('$', $ac);
$smdpAddress = htmlspecialchars($acParts[1] ?? '', ENT_QUOTES, 'UTF-8');
$activationCode = htmlspecialchars($acParts[2] ?? '', ENT_QUOTES, 'UTF-8');

$ca = $ac;
$expiredTime = htmlspecialchars(formatTanggal($esim["expiredTime"] ?? ''), ENT_QUOTES, 'UTF-8');

// Generate iOS 17.4+ link
if (!empty($ac)) {
    $iosLink = 'https://esimsetup.apple.com/esim_qrcode_provisioning?carddata=' . urlencode($ca);
}

// Check if it's a new eSIM
$isNewEsim = ($esimStatus === "GOT_RESOURCE");

if ($isNewEsim) {
    $qrCodeUrl = "/../src/includes/generate_qr.php?data=" . urlencode($ca);
} else {
    $totalKuotaGB = isset($esim["totalVolume"]) ? round($esim["totalVolume"] / (1024**3), 2) : 0;
    $sisaKuotaGB = max(0, $totalKuotaGB - $orderUsage);

    if ($sisaKuotaGB < 0.7) {
        $kuotaHabis = true;
        $sisaKuotaGB = 0;
        $statusIndonesia = "Kuota Habis";
        $statusColor = getStatusColor('USED_UP');
    }

    $sisaHari = hitungSisaHari($esim["expiredTime"] ?? '');
    $totalHari = $esim["totalDuration"] ?? 90;
    $persentaseExpired = $totalHari > 0 ? round(($sisaHari / $totalHari) * 100, 2) : 0;
}

// Generate WhatsApp token
$waToken = urlencode($token);

// Update database status if functions exist
if (function_exists('updateEsimStatus')) {
    updateEsimStatus($iccid, $esimStatus, $smdpStatus, isset($esim["orderUsage"]) ? intval($esim["orderUsage"]) : 0, $esim["eid"] ?? "");
}

?>

<!DOCTYPE html>
<html lang="id" data-theme="light" data-esim-status="<?= $esimStatus ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSIM Portal - <?= $nama ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/detail.css?v=<?= filemtime('assets/css/detail.css') ?>">
    
    <!-- Meta tags -->
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="eSIM Portal - Detail eSIM dan status penggunaan untuk <?= $nama ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/detail.css" as="style">
    <?php if ($isNewEsim && $qrCodeUrl): ?>
    <link rel="preload" href="<?= $qrCodeUrl ?>" as="image">
    <?php endif; ?>
</head>
<body class="<?= $isNewEsim ? 'new-esim' : ($kuotaHabis ? 'quota-depleted' : 'active-esim') ?>">

<div class="container">
    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="eSIM Portal" class="logo">
        </div>
    </header>

<!-- User Info Card -->
<div class="user-card">
    <div class="user-info">
        <h1 class="user-name"><?= $nama ?></h1>
        
        <?php if ($smdpStatus === 'DELETED'): ?>
            <!-- Status DELETED - Tampilan Lama + Notifikasi -->
            <div class="status-badge deleted-status" style="background: <?= $statusColor['bg'] ?>; color: <?= $statusColor['text'] ?>;">
                <span class="status-emoji"><?= getStatusEmoji($esimStatus) ?></span>
            </div>
            
            <!-- Notifikasi Khusus untuk DELETED -->
            <div class="deleted-notice">
                <i class="fas fa-info-circle"></i>
                <span>eSIM sudah dihapus oleh pembeli</span>
            </div>
        <?php else: ?>
            <!-- Status Normal - Hanya Emoji -->
            <div class="status-badge" style="background: <?= $statusColor['bg'] ?>; color: <?= $statusColor['text'] ?>;">
                <span class="status-emoji"><?= getStatusEmoji($esimStatus) ?></span>
            </div>
            
            <!-- SMDP Status -->
            <?php if (!empty($smdpStatus)): ?>
            <div class="smdp-status-badge" style="background: <?= getSmdpStatusColor($smdpStatus)['bg'] ?>; color: <?= getSmdpStatusColor($smdpStatus)['text'] ?>;">
                <span class="smdp-label">eSIM</span>
                <span class="smdp-text"><?= getSmdpStatusIndonesia($smdpStatus) ?></span>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="status-description">
        <?php
        if ($smdpStatus === 'DELETED') {
            echo "eSIM telah dihapus dari perangkat pembeli. Data tidak dapat dipulihkan.";
        } elseif ($kuotaHabis) {
            echo "Kuota eSIM sudah habis. Isi ulang untuk melanjutkan penggunaan.";
        } elseif ($isNewEsim) {
            echo "eSIM siap dipasang. Scan QR code atau gunakan kode aktivasi manual.";
        } else {
            echo "eSIM aktif dan siap digunakan. Pastikan roaming diaktifkan.";
        }
        ?>
    </div>
</div>

<!-- Content based on status -->
<?php if ($smdpStatus === 'DELETED'): ?>
    <!-- Deleted eSIM - Information Only -->
    <div class="deleted-section">
        <div class="info-card">
            <div class="info-header">
                <h3 class="info-title">
                    <i class="fas fa-trash-alt"></i>
                    Informasi eSIM yang Dihapus
                </h3>
            </div>
            
            <!-- ICCID Info -->
            <div class="iccid-card" onclick="copyToClipboard('<?= $iccid ?>')">
                <i class="fas fa-sim-card"></i>
                <div class="iccid-info">
                    <span class="iccid-label">ICCID</span>
                    <span class="iccid-value"><?= $iccid ?></span>
                </div>
                <i class="fas fa-copy copy-icon"></i>
            </div>
            
            <div class="deleted-info">
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?= $statusIndonesia ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Waktu Dihapus:</span>
                    <span class="info-value"><?= $expiredTime ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Penggunaan:</span>
                    <span class="info-value"><?= formatBytes($orderUsage * (1024**3)) ?></span>
                </div>
            </div>
            
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Perhatian:</strong> eSIM ini sudah dihapus secara permanen dari perangkat dan tidak dapat dipulihkan.
            </div>
        </div>
    </div>

<?php elseif ($isNewEsim): ?>
    <!-- New eSIM - Installation Section -->
    <div class="installation-section">
        <div class="iccid-card" onclick="copyToClipboard('<?= $iccid ?>')">
            <i class="fas fa-sim-card"></i>
            <div class="iccid-info">
                <span class="iccid-label">ICCID</span>
                <span class="iccid-value"><?= $iccid ?></span>
            </div>
            <i class="fas fa-copy copy-icon"></i>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Penting:</strong> Setelah eSIM terpasang, wajib aktifkan roaming di pengaturan seluler.
        </div>
        
        <!-- QR Code Section -->
        <div class="qr-section">
            <h3 class="section-title">
                <i class="fas fa-qrcode"></i>
                Pasang dengan QR Code
            </h3>
            
            <div class="qr-container">
                <img src="<?= $qrCodeUrl ?>" alt="QR Code eSIM" class="qr-image" loading="lazy">
                
                <?php if ($iosLink): ?>
                <div class="ios-link-container">
                    <p class="ios-note">Untuk iPhone iOS 17.4+:</p>
                    <a href="<?= $iosLink ?>" class="btn btn-ios">
                        <i class="fab fa-apple"></i>
                        Pasang Otomatis di iOS
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Manual Activation Section -->
        <div class="activation-section">
            <h3 class="section-title">
                <i class="fas fa-key"></i>
                Kode Aktivasi Manual
            </h3>
            
            <div class="activation-tabs">
                <button class="tab-btn active" data-tab="ios">
                    <i class="fab fa-apple"></i> iOS
                </button>
                <button class="tab-btn" data-tab="android">
                    <i class="fab fa-android"></i> Android
                </button>
            </div>
            
            <div class="tab-content">
                <!-- iOS Tab -->
                <div class="tab-pane active" id="ios-tab">
                    <div class="code-field" onclick="copyToClipboard('<?= $smdpAddress ?>')">
                        <label class="code-label">SM-DP+ Address</label>
                        <div class="code-value"><?= $smdpAddress ?></div>
                        <i class="fas fa-copy"></i>
                    </div>
                    
                    <div class="code-field" onclick="copyToClipboard('<?= $activationCode ?>')">
                        <label class="code-label">Activation Code</label>
                        <div class="code-value"><?= $activationCode ?></div>
                        <i class="fas fa-copy"></i>
                    </div>
                </div>
                
                <!-- Android Tab -->
                <div class="tab-pane" id="android-tab">
                    <div class="code-field" onclick="copyToClipboard('LPA:1$<?= $smdpAddress ?>$<?= $activationCode ?>')">
                        <label class="code-label">Kode Aktivasi Android</label>
                        <div class="code-value android-code">LPA:1$<?= $smdpAddress ?>$<?= $activationCode ?></div>
                        <i class="fas fa-copy"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- ICCID Info -->
    <div class="iccid-card" onclick="copyToClipboard('<?= $iccid ?>')">
        <i class="fas fa-sim-card"></i>
        <div class="iccid-info">
            <span class="iccid-label">ICCID</span>
            <span class="iccid-value"><?= $iccid ?></span>
        </div>
        <i class="fas fa-copy copy-icon"></i>
    </div>
    <!-- Active eSIM - Usage Information -->
    <div class="usage-section">
        <!-- Data Usage -->
        <div class="usage-card">
            <div class="usage-header">
                <h3 class="usage-title">
                    <i class="fas fa-chart-pie"></i>
                    Penggunaan Data
                </h3>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $totalKuotaGB > 0 ? min(($sisaKuotaGB / $totalKuotaGB) * 100, 100) : 0 ?>%"></div>
                </div>
                <div class="progress-info">
                    <span class="remaining">
                        <strong><?= formatBytes($sisaKuotaGB * (1024**3)) ?></strong> tersisa
                    </span>
                    <span class="total">
                        dari <strong><?= formatBytes($totalKuotaGB * (1024**3)) ?></strong>
                    </span>
                </div>
            </div>
        </div>
            <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if ($smdpStatus !== 'DELETED' && !$isNewEsim): ?>
            <a href="topup.php?token=<?= $waToken ?>&iccid=<?= urlencode($iccid) ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                Isi Ulang Kuota
            </a>
            <?php endif; ?>
        </div>
        <!-- Time Remaining -->
        <div class="usage-card">
            <div class="usage-header">
                <h3 class="usage-title">
                    <i class="fas fa-clock"></i>
                    Masa Aktif
                </h3>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar time-progress">
                    <div class="progress-fill" style="width: <?= max(min($persentaseExpired, 100), 0) ?>%"></div>
                </div>
                <div class="progress-info">
                    <span class="remaining">
                        <strong><?= $sisaHari ?> hari</strong> tersisa
                    </span>
                    <span class="expiry">
                        Berakhir: <strong><?= $expiredTime ?></strong>
                    </span>
                </div>
            </div>
        </div>
        
        <?php if ($kuotaHabis): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Kuota Habis!</strong> Isi ulang kuota untuk melanjutkan penggunaan.
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
   
    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> eSIM Portal - Powered by Mimint</p>
    </footer>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span class="toast-message">Berhasil disalin ke clipboard!</span>
</div>

<!-- Data Notice Modal -->
<div id="dataModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-info-circle"></i> Informasi Penting</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>Data kuota memiliki jeda pembaruan hingga 24 jam.</strong></p>
            <p>Informasi penggunaan aktual mungkin berbeda dari yang ditampilkan di sini.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeModal()">Mengerti</button>
        </div>
    </div>
</div>

<script src="assets/js/detail.js?v=<?= filemtime('assets/js/detail.js') ?>"></script>
<?php if (function_exists('generateStatusJsMappings')): ?>
<?= generateStatusJsMappings() ?>
<?php endif; ?>

<script>
window.esimData = {
    status: '<?= $esimStatus ?>',
    isNew: <?= $isNewEsim ? 'true' : 'false' ?>,
    quotaDepleted: <?= $kuotaHabis ? 'true' : 'false' ?>,
    token: '<?= $token ?>'
};
</script>

</body>
</html>