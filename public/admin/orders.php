<?php
// Include required files
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../config.php';

// Include required files
try {
    require_once __DIR__ . '/../../src/includes/koneksi.php'; // Naik satu level, lalu masuk ke src/includes
    require_once __DIR__ . '/../../src/includes/functions.php';
    require_once __DIR__ . '/../../src/includes/api.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Check if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    logSecurityEvent("Unauthorized access attempt to orders page", 'warning');
    header("Location: login.php");
    exit();
}

// Handle force refresh parameters
$forceRefresh = isset($_GET['force_refresh_all']) && $_GET['force_refresh_all'] == '1';

if ($forceRefresh) {
    $shouldUpdateFromApi = true;
}

// Add missing columns if needed using PDO
try {
    $checkColumns = [
        'phone' => "ALTER TABLE esim_orders ADD COLUMN phone varchar(20) DEFAULT NULL AFTER nama",
        'status' => "ALTER TABLE esim_orders ADD COLUMN status varchar(50) DEFAULT 'UNKNOWN' AFTER esimTranNo",
        'order_usage' => "ALTER TABLE esim_orders ADD COLUMN order_usage bigint(20) DEFAULT 0 AFTER status",
        'esim_status' => "ALTER TABLE esim_orders ADD COLUMN esim_status varchar(50) DEFAULT NULL AFTER order_usage",
        'smdp_status' => "ALTER TABLE esim_orders ADD COLUMN smdp_status varchar(50) DEFAULT NULL AFTER esim_status"
    ];
    
    foreach ($checkColumns as $column => $sql) {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM esim_orders LIKE '$column'");
        if ($checkStmt->rowCount() == 0) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) {
    error_log("Error checking/adding columns: " . $e->getMessage());
}

function updateEsimDataFromApi($pdo, $orderNo, $iccid = '') {
    try {
        error_log("Force updating eSIM data for orderNo: $orderNo");
        
        $apiResult = queryEsimDetails($orderNo);
        
        // Log response untuk debug
        error_log("API Response for $orderNo: " . json_encode($apiResult));
        
        // Cek response API
        if (isset($apiResult['success']) && $apiResult['success']) {
            
            // Jika ada eSIM data
            if (isset($apiResult['obj']['esimList']) && count($apiResult['obj']['esimList']) > 0) {
                $esim = $apiResult['obj']['esimList'][0];

                $iccid_api      = $esim['iccid'] ?? $iccid;
                $esim_status    = $esim['esimStatus'] ?? 'UNKNOWN';
                $smdp_status    = $esim['smdpStatus'] ?? '';
                $order_usage    = isset($esim['orderUsage']) ? (int)$esim['orderUsage'] : 0;

                // Logika status internal
                $status_db = $esim_status; // Default pakai status dari API
                
                $stmt_check = $pdo->prepare("SELECT status FROM esim_orders WHERE orderNo = ?");
                $stmt_check->execute([$orderNo]);
                $status_now = $stmt_check->fetchColumn();
                
                if ($status_now === 'pending' && $esim_status === 'IN_USE') {
                    $status_db = 'active';
                } elseif (!empty($status_now) && $status_now !== 'UNKNOWN') {
                    $status_db = $status_now;
                }

                // Update database
                $stmt = $pdo->prepare("UPDATE esim_orders SET iccid = ?, status = ?, esim_status = ?, smdp_status = ?, order_usage = ? WHERE orderNo = ?");
                
                $result = $stmt->execute([
                    $iccid_api,
                    $status_db,
                    $esim_status,
                    $smdp_status,
                    $order_usage,
                    $orderNo
                ]);
                
                if (!$result) {
                    error_log("Update failed for orderNo: $orderNo");
                    return false;
                }
                
                error_log("Successfully updated orderNo: $orderNo with status: $esim_status");
                return true;
                
            } else {
                // API sukses tapi tidak ada data eSIM
                error_log("Order $orderNo: API sukses tapi eSIM tidak ditemukan (mungkin belum diaktivasi atau sudah expired)");
                
                // Update status jadi 'NOT_FOUND' 
                $stmt = $pdo->prepare("UPDATE esim_orders SET esim_status = 'NOT_FOUND' WHERE orderNo = ?");
                $stmt->execute([$orderNo]);
                
                return true; // Return true karena API sukses, cuma data tidak ada
            }
            
        } else {
            // API benar-benar gagal
            $errorMsg = $apiResult['errorMsg'] ?? 'Unknown API error';
            error_log("API gagal untuk order $orderNo: " . $errorMsg);
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception in updateEsimDataFromApi for $orderNo: " . $e->getMessage());
        return false;
    }
}
// PERBAIKI: Helper functions dengan status dibatalkan
if (!function_exists('getStatusClass')) {
    function getStatusClass($status) {
        $status = strtolower(str_replace('_', '-', $status));
        switch ($status) {
            case 'in-use':
            case 'active':
            case 'used-up':
            case 'depleted':
                return 'active';
            case 'pending':
            case 'new':
            case 'onboard':
                return 'pending';
            case 'expired':
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

if (!function_exists('getStatusIndonesia')) {
    function getStatusIndonesia($status) {
        $status = strtolower($status);
        switch ($status) {
            case 'in_use':
            case 'active':
            case 'used_up':
            case 'depleted':
                return '✅ Aktif';
            case 'pending':
            case 'new':
            case 'onboard':
                return '⏳ Pending';
            case 'expired':
                return '❌ Expired';
            case 'suspended':
                return '⏸️ Suspended';
            case 'cancelled':
            case 'canceled':
            case 'dibatalkan':
            case 'cancel':
                return '🚫 Dibatalkan';
            default:
                return '❓ Unknown';
        }
    }
}


// Handle API actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $orderNo = $_POST['orderNo'] ?? '';
        $iccid = $_POST['iccid'] ?? '';
        
        switch ($_POST['action']) {
            case 'query_esim':
                $ok = updateEsimDataFromApi($pdo, $orderNo, $iccid);
                if ($ok) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Data eSIM berhasil diperbarui dari API'
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Gagal memperbarui data eSIM. Lihat error log server untuk detailnya.'
                    ];
                }
                break;
                
            case 'get_esim_details':
                $orderNo = $_POST['orderNo'] ?? '';
                $iccid = $_POST['iccid'] ?? '';
                
                $apiResult = queryEsimDetails($orderNo, $iccid);
                
                if (isset($apiResult['success']) && $apiResult['success']) {
                    if (isset($apiResult['obj']['esimList']) && count($apiResult['obj']['esimList']) > 0) {
                        $esim = $apiResult['obj']['esimList'][0];
                        
                        // Update database dengan EID jika ada
                        if (isset($esim['eid']) && !empty($esim['eid'])) {
                            $stmt = $pdo->prepare("UPDATE esim_orders SET eid = ? WHERE orderNo = ?");
                            $stmt->execute([$esim['eid'], $orderNo]);
                        }
                        
                        $response = [
                            'status' => 'success',
                            'esimData' => [
                                'eid' => $esim['eid'] ?? '',
                                'esimStatus' => $esim['esimStatus'] ?? '',
                                'smdpStatus' => $esim['smdpStatus'] ?? '',
                                'orderUsage' => $esim['orderUsage'] ?? 0,
                                'totalVolume' => $esim['totalVolume'] ?? 0,
                                'expiredTime' => $esim['expiredTime'] ?? null
                            ]
                        ];
                    } else {
                        throw new Exception("Data eSIM tidak ditemukan");
                    }
                } else {
                    throw new Exception($apiResult['errorMsg'] ?? 'Gagal mengambil data dari API');
                }
                break;
                
            case 'suspend_esim':
                $result = suspendEsim($iccid);
                if (isset($result['success']) && $result['success']) {
                    $stmt = $pdo->prepare("UPDATE esim_orders SET status = 'SUSPENDED', esim_status = 'SUSPENDED' WHERE iccid = ?");
                    $stmt->execute([$iccid]);
                    $response = ['status' => 'success', 'message' => 'eSIM berhasil di-suspend'];
                } else {
                    throw new Exception($result['errorMsg'] ?? 'Gagal suspend eSIM');
                }
                break;
                
            case 'unsuspend_esim':
                $result = unsuspendEsim($iccid);
                if (isset($result['success']) && $result['success']) {
                    $stmt = $pdo->prepare("UPDATE esim_orders SET status = 'IN_USE', esim_status = 'IN_USE' WHERE iccid = ?");
                    $stmt->execute([$iccid]);
                    $response = ['status' => 'success', 'message' => 'eSIM berhasil di-unsuspend'];
                } else {
                    throw new Exception($result['errorMsg'] ?? 'Gagal unsuspend eSIM');
                }
                break;
                
            case 'revoke_esim':
                $result = revokeEsim('', $iccid);
                if (isset($result['success']) && $result['success']) {
                    $stmt = $pdo->prepare("UPDATE esim_orders SET status = 'REVOKED', esim_status = 'REVOKED' WHERE iccid = ?");
                    $stmt->execute([$iccid]);
                    $response = ['status' => 'success', 'message' => 'eSIM berhasil di-revoke (force remove)'];
                } else {
                    throw new Exception($result['errorMsg'] ?? 'Gagal revoke eSIM');
                }
                break;
                
            case 'cancel_esim':
                try {
                    // Validasi status eSIM dari database dulu
                    $stmt = $pdo->prepare("SELECT esim_status, status FROM esim_orders WHERE iccid = ?");
                    $stmt->execute([$iccid]);
                    $currentOrder = $stmt->fetch();
                    
                    if (!$currentOrder) {
                        throw new Exception('eSIM tidak ditemukan');
                    }
                    
                    $currentStatus = strtoupper($currentOrder['esim_status'] ?: $currentOrder['status'] ?: '');
                    
                    // Cek apakah eSIM sudah aktif
                    if (in_array($currentStatus, ['IN_USE', 'ACTIVE', 'USED_UP', 'DEPLETED'])) {
                        throw new Exception('eSIM yang sudah aktif tidak dapat dibatalkan');
                    }
                    
                    // Cek apakah sudah expired
                    if (in_array($currentStatus, ['EXPIRED'])) {
                        throw new Exception('eSIM yang sudah expired tidak dapat dibatalkan');
                    }
                    
                    // Cek apakah sudah dibatalkan
                    if (in_array($currentStatus, ['CANCELLED', 'CANCELED'])) {
                        throw new Exception('eSIM ini sudah dibatalkan sebelumnya');
                    }
                    
                    // Panggil API cancel
                    $result = cancelEsim($iccid);
                    
                    if (isset($result['success']) && $result['success']) {
                        // Update status di database
                        $stmt = $pdo->prepare("UPDATE esim_orders SET status = 'CANCELLED', esim_status = 'CANCELLED', updated_at = NOW() WHERE iccid = ?");
                        $stmt->execute([$iccid]);
                        
                        $response = [
                            'status' => 'success', 
                            'message' => 'eSIM berhasil dibatalkan. Status telah diperbarui.'
                        ];
                    } else {
                        throw new Exception($result['errorMsg'] ?? 'Gagal membatalkan eSIM melalui API');
                    }
                } catch (Exception $e) {
                    $response = [
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
                break;
                
            case 'send_sms':
                $message = $_POST['sms_message'] ?? '';
                if (empty($message)) {
                    throw new Exception('Pesan SMS tidak boleh kosong');
                }
            
                $result = sendSms($iccid, $message);
            
                if (isset($result['success']) && $result['success']) {
                    $response = [
                        'status' => 'success',
                        'message' => 'SMS berhasil dikirim ke eSIM'
                    ];
                } else {
                    // Kirim pesan error dari API ke frontend
                    $apiMsg = $result['errorMsg'] ?? 'Gagal mengirim SMS';
                    throw new Exception($apiMsg);
                }
                break;
                
            case 'topup_esim':
                $topUpCode = $_POST['topUpCode'] ?? '';
                $transactionId = 'TOPUP_' . time() . '_' . uniqid();
                $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
                
                if (empty($topUpCode)) {
                    throw new Exception('Top-up code tidak boleh kosong');
                }
                if ($amount <= 0) {
                    throw new Exception('Amount tidak boleh kosong atau nol');
                }
                
                $result = topUpEsim($iccid, $topUpCode, $transactionId, $amount);
                if (isset($result['success']) && $result['success']) {
                    $response = ['status' => 'success', 'message' => 'Top up berhasil'];
                } else {
                    throw new Exception($result['errorMsg'] ?? 'Gagal melakukan top up');
                }
                break;
                
            case 'get_topup_packages':
                $esimDetails = queryEsimDetails('', $iccid);
                $originalPackageCode = "";
                
                if (isset($esimDetails["success"]) && $esimDetails["success"] && 
                    isset($esimDetails["obj"]["esimList"]) && count($esimDetails["obj"]["esimList"]) > 0) {
                    
                    $esim = $esimDetails["obj"]["esimList"][0];
                    $originalPackageCode = isset($esim["packageCode"]) ? $esim["packageCode"] : "";
                    
                    $topupPackages = getPackageList("", "TOPUP", $originalPackageCode, $iccid);
                    
                    if (isset($topupPackages["success"]) && $topupPackages["success"] && 
                        isset($topupPackages["obj"]["packageList"])) {
                        $response = [
                            'status' => 'success',
                            'packages' => $topupPackages["obj"]["packageList"]
                        ];
                    } else {
                        throw new Exception("Tidak dapat mengambil paket top-up: " . ($topupPackages["errorMsg"] ?? "Unknown error"));
                    }
                } else {
                    throw new Exception("Tidak dapat mengambil detail eSIM: " . ($esimDetails["errorMsg"] ?? "Unknown error"));
                }
                break;
                
            case 'get_status':
                $esimDetails = queryEsimDetails('', $iccid);
                if (isset($esimDetails["success"]) && $esimDetails["success"] && 
                    isset($esimDetails["obj"]["esimList"]) && count($esimDetails["obj"]["esimList"]) > 0) {
                    
                    $esim = $esimDetails["obj"]["esimList"][0];
                    
                    $orderUsage = isset($esim['orderUsage']) ? (int)$esim['orderUsage'] : 0;
                    $stmt = $pdo->prepare("UPDATE esim_orders SET 
                        order_usage = ?, 
                        esim_status = ?,
                        smdp_status = ?,
                        status = ?
                        WHERE iccid = ?");
                    $stmt->execute([
                        $orderUsage,
                        $esim['esimStatus'],
                        $esim['smdpStatus'],
                        $esim['esimStatus'],
                        $iccid
                    ]);
                    
                    $response = [
                        'status' => 'success',
                        'esimStatus' => $esim['esimStatus'],
                        'smdpStatus' => $esim['smdpStatus'] ?? '',
                        'usageData' => [
                            'totalVolume' => $esim['totalVolume'] ?? 0,
                            'orderUsage' => $orderUsage,
                            'expiredTime' => $esim['expiredTime'] ?? null
                        ]
                    ];
                } else {
                    throw new Exception("Tidak dapat mengambil status eSIM: " . ($esimDetails["errorMsg"] ?? "Unknown error"));
                }
                break;
                
            case 'get_usage':
                $esimDetails = queryEsimDetails('', $iccid);
                if (isset($esimDetails["success"]) && $esimDetails["success"] && 
                    isset($esimDetails["obj"]["esimList"]) && count($esimDetails["obj"]["esimList"]) > 0) {
                    
                    $esim = $esimDetails["obj"]["esimList"][0];
                    
                    $orderUsage = isset($esim['orderUsage']) ? (int)$esim['orderUsage'] : 0;
                    $stmt = $pdo->prepare("UPDATE esim_orders SET order_usage = ?, esim_status = ? WHERE iccid = ?");
                    $stmt->execute([$orderUsage, $esim['esimStatus'], $iccid]);
                    
                    $response = [
                        'status' => 'success',
                        'usageData' => [
                            'totalVolume' => $esim['totalVolume'] ?? 0,
                            'orderUsage' => $orderUsage,
                            'expiredTime' => $esim['expiredTime'] ?? null
                        ]
                    ];
                } else {
                    throw new Exception("Tidak dapat mengambil data usage: " . ($esimDetails["errorMsg"] ?? "Unknown error"));
                }
                break;
                
            case 'update_customer':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
                $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
                
                if (empty($id) || empty($nama)) {
                    throw new Exception("ID dan nama tidak boleh kosong");
                }
                
                $stmt = $pdo->prepare("UPDATE esim_orders SET nama = ?, phone = ? WHERE id = ?");
                
                if ($stmt->execute([$nama, $phone, $id])) {
                    $response = ['status' => 'success', 'message' => 'Data pelanggan berhasil diperbarui'];
                } else {
                    throw new Exception("Gagal memperbarui data");
                }
                break;
                
            case 'force_refresh_all':
                $orderNumbers = isset($_POST['order_numbers']) ? json_decode($_POST['order_numbers'], true) : [];
                
                if (empty($orderNumbers)) {
                    throw new Exception('Tidak ada order yang ditemukan untuk di-refresh');
                }
                
                $successCount = 0;
                $totalCount = count($orderNumbers);
                
                foreach ($orderNumbers as $orderNo) {
                    if (!empty($orderNo)) {
                        // Force update dari API dengan delay untuk mencegah spam
                        if ($successCount > 0) {
                            sleep(1); // 1 detik delay antar API call
                        }
                        
                        $updated = updateEsimDataFromApi($pdo, $orderNo);
                        if ($updated) {
                            $successCount++;
                        }
                    }
                }
                
                $response = [
                    'status' => 'success',
                    'message' => "Berhasil refresh $successCount dari $totalCount data eSIM dari API"
                ];
                
                if ($successCount === 0) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Gagal refresh data dari API. Periksa koneksi internet dan API key.'
                    ];
                } elseif ($successCount < $totalCount) {
                    $response = [
                        'status' => 'warning',
                        'message' => "Hanya berhasil refresh $successCount dari $totalCount data. Beberapa order mungkin sudah tidak valid."
                    ];
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Filter and pagination using PDO
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 4; // Maksimal 4 data per halaman
$offset = ($page - 1) * $limit;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filter
$whereClause = [];
$bindParams = [];

if (!empty($searchQuery)) {
    $whereClause[] = "(nama LIKE ? OR orderNo LIKE ? OR iccid LIKE ? OR token LIKE ? OR phone LIKE ?)";
    $likeParam = "%$searchQuery%";
    $bindParams = [$likeParam, $likeParam, $likeParam, $likeParam, $likeParam];
}

$whereSQL = '';
if (!empty($whereClause)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClause);
}

// Count total records using PDO
$totalRecordsQuery = "SELECT COUNT(*) as total FROM esim_orders $whereSQL";
$totalRecords = 0;

if (!empty($bindParams)) {
    $stmt = $pdo->prepare($totalRecordsQuery);
    $stmt->execute($bindParams);
    $totalRecords = $stmt->fetchColumn();
} else {
    $stmt = $pdo->query($totalRecordsQuery);
    $totalRecords = $stmt->fetchColumn();
}

$totalPages = ceil($totalRecords / $limit);

// Get orders data dengan data usage using PDO
$ordersSql = "SELECT id, nama, phone, orderNo, iccid, packageCode, packageName, price, token, created_at, status, order_usage, esim_status, smdp_status FROM esim_orders $whereSQL ORDER BY created_at DESC LIMIT ?, ?";
$ordersData = [];

if (!empty($bindParams)) {
    $stmt = $pdo->prepare($ordersSql);
    $queryParams = $bindParams;
    $queryParams[] = $offset;
    $queryParams[] = $limit;
    $stmt->execute($queryParams);
    $ordersData = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM esim_orders ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->execute([$offset, $limit]);
    $ordersData = $stmt->fetchAll();
}

// Smart update: Only update when needed
$shouldUpdateFromApi = false;

// Update dari API hanya jika:
// 1. User sedang search
// 2. User ganti halaman
// 3. Ada data status yang kosong
if (!empty($searchQuery) || $page > 1) {
    $shouldUpdateFromApi = true;
} else {
    // Cek apakah ada order dengan status kosong
    foreach ($ordersData as $order) {
        if (empty($order['esim_status']) && empty($order['status'])) {
            $shouldUpdateFromApi = true;
            break;
        }
    }
}

if ($shouldUpdateFromApi) {
    foreach ($ordersData as $index => $order) {
        if (!empty($order['orderNo'])) {
            // Add delay untuk prevent API spam
            if ($index > 0) {
                sleep(1); // 1 detik delay antar API call
            }
            
            $updated = updateEsimDataFromApi($pdo, $order['orderNo'], $order['iccid']);
            
            if ($updated) {
                // Ambil data terbaru dari database
                $updatedQuery = "SELECT id, nama, phone, orderNo, iccid, packageCode, packageName, price, token, created_at, status, order_usage, esim_status, smdp_status FROM esim_orders WHERE orderNo = ?";
                $stmt = $pdo->prepare($updatedQuery);
                $stmt->execute([$order['orderNo']]);
                $updatedOrder = $stmt->fetch();
                
                if ($updatedOrder) {
                    $ordersData[$index] = $updatedOrder;
                }
            }
        }
    }
}

$hasData = count($ordersData) > 0;

// Get current exchange rate
$currentExchangeRate = getCurrentExchangeRate();
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar eSIM - eSIM Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/orders.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/orders.css?v=<?= time() ?>">
    <meta name="theme-color" content="#0f172a">
    <meta name="description" content="Orders management for eSIM Portal">
    
    <!-- Pass exchange rate to JavaScript -->
    <script>
    window.exchangeRate = <?= $currentExchangeRate ?>;
    console.log('Exchange rate loaded from DB:', window.exchangeRate);
    </script>
</head>
<body>

<!-- Floating Dark Mode Toggle -->
<button class="theme-toggle-floating" id="themeToggle">
    <span id="themeIcon">☀️</span>
</button>

<!-- Main Content -->
<main class="main-content">
    <!-- Orders Header - Hero Style -->
    <section class="orders-header">
        <div class="header-content">
            <h1 class="orders-title">📱 Daftar eSIM</h1>
            <p class="orders-subtitle">Kelola semua eSIM yang telah dipesan</p>
            <div class="orders-actions">
                <a href="esim.php" class="btn-primary">
                    <span class="btn-icon">📱</span>
                    <span class="btn-text">Kelola eSIM</span>
                </a>
                <button class="btn-secondary" onclick="forceRefreshCurrentPage()">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Refresh Data</span>
                </button>
            </div>
        </div>
    </section>
    
    <!-- Search Container -->
    <section class="search-container">
        <form action="" method="GET" class="search-box">
            <input type="text" name="search" class="search-input" placeholder="🔍 Cari nama, ICCID, token..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn-search">Cari</button>
        </form>
    </section>
    
    <!-- Orders Grid -->
    <section class="orders-grid">
        <?php if ($hasData): ?>
            <?php foreach ($ordersData as $order): 
                $status = !empty($order['esim_status']) ? $order['esim_status'] : (!empty($order['status']) ? $order['status'] : 'UNKNOWN');
                $statusClass = 'status-' . strtolower(str_replace('_', '-', $status));
                $statusText = getStatusIndonesia($status);
                $initial = strtoupper(substr($order['nama'], 0, 1));
                $orderJson = htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8');
                
                // Calculate usage percentage if available
                $usagePercentage = 0;
                $totalVolumeGB = 0;
                $usedVolumeGB = 0;
                
                if (!empty($order['packageName'])) {
                    // Try to extract volume from package name (e.g., "5GB")
                    if (preg_match('/(\d+(?:\.\d+)?)\s*GB/i', $order['packageName'], $matches)) {
                        $totalVolumeGB = (float)$matches[1];
                        $usedVolumeGB = round($order['order_usage'] / (1024 * 1024 * 1024), 2);
                        if ($totalVolumeGB > 0) {
                            $usagePercentage = min(100, round(($usedVolumeGB / $totalVolumeGB) * 100));
                        }
                    }
                }
                
                // Convert price to USD
                $priceUsd = $order['price'] / 10000;
            ?>
            <div class="order-card <?= $statusClass ?>">
                <div class="customer-info">
                    <div class="customer-avatar"><?= $initial ?></div>
                    <div class="customer-details">
                        <!-- PERBAIKI: Status badge dengan class yang benar -->
                        <div class="customer-name-row">
                            <div class="customer-name"><?= htmlspecialchars($order['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="status-badge <?= getStatusClass($status) ?>"><?= $statusText ?></span>
                        </div>
                        <div class="order-date"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
                        <?php if (!empty($order['phone'])): ?>
                        <div class="phone-info"><?= htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-info">
                    <div class="info-item">
                        <span class="info-label">Order No:</span>
                        <span class="info-value"><?= htmlspecialchars($order['orderNo'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if (!empty($order['iccid'])): ?>
                    <div class="info-item">
                        <span class="info-label">ICCID:</span>
                        <span class="info-value"><?= htmlspecialchars($order['iccid'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Paket:</span>
                        <span class="info-value"><?= htmlspecialchars($order['packageName'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Harga:</span>
                        <span class="info-value">$<?= number_format($priceUsd, 2) ?> (Rp <?= number_format($priceUsd * $currentExchangeRate, 0, ',', '.') ?>)</span>
                    </div>
                </div>
                
                <!--<?php if ($usagePercentage > 0): ?>-->
                <!--<div class="card-usage-section">-->
                <!--    <div class="usage-info">-->
                <!--        <span class="usage-label">Penggunaan Data:</span>-->
                <!--        <span class="usage-text"><?= $usedVolumeGB ?> GB / <?= $totalVolumeGB ?> GB</span>-->
                <!--    </div>-->
                <!--    <div class="card-usage-bar">-->
                <!--        <div class="card-usage-fill" style="width: <?= $usagePercentage ?>%; background: <?= $usagePercentage > 80 ? '#ef4444' : ($usagePercentage > 50 ? '#f59e0b' : '#10b981') ?>;"></div>-->
                <!--    </div>-->
                <!--    <div class="usage-percentage-text"><?= $usagePercentage ?>% Terpakai</div>-->
                <!--</div>-->
                <!--<?php endif; ?>-->
                
                <div class="token-container" onclick="copyToken('<?= htmlspecialchars($order['token'], ENT_QUOTES, 'UTF-8') ?>')">
                    <span class="token-text"><?= htmlspecialchars($order['token'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="copy-icon">📋</span>
                </div>
                
                <div class="quick-actions" onclick="event.stopPropagation();">
                    <button class="quick-action-btn" onclick="showEsimDetails(<?= $orderJson ?>, event)">
                        Detail eSIM
                    </button>
                    <?php if (!empty($order['iccid'])): ?>
                        <?php if (in_array($status, ['IN_USE', 'ACTIVE'])): ?>
                        <button class="quick-action-btn" onclick="refreshUsageQuick('<?= $order['iccid'] ?>', this)">
                            Refresh Usage
                        </button>
                        <?php elseif (in_array($status, ['PENDING', 'NEW', 'ONBOARD'])): ?>
                        <button class="quick-action-btn quick-cancel" onclick="cancelEsim('<?= $order['iccid'] ?>')">
                            🚫 Batalkan
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📱</div>
                <h3>Tidak ada eSIM</h3>
                <p>Belum ada eSIM yang terdaftar atau sesuai dengan pencarian Anda</p>
            </div>
        <?php endif; ?>
    </section>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <section class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($searchQuery) ?>" class="page-btn">Prev</a>
        <?php endif; ?>
        
        <?php 
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($start > 1): ?>
            <a href="?page=1&search=<?= urlencode($searchQuery) ?>" class="page-btn">1</a>
            <?php if ($start > 2): ?>
                <span class="page-dots">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($searchQuery) ?>" class="page-btn"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="page-dots">...</span>
            <?php endif; ?>
            <a href="?page=<?= $totalPages ?>&search=<?= urlencode($searchQuery) ?>" class="page-btn"><?= $totalPages ?></a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($searchQuery) ?>" class="page-btn">Next</a>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>

<!-- eSIM Modal -->
<div id="esimModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div id="esimDetailsContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- SMS Modal -->
<div id="smsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeSmsModal()">&times;</span>
        <div id="smsContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Topup Modal -->
<div id="topupModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeTopupModal()">&times;</span>
        <div id="topupContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeEditModal()">&times;</span>
        <h2>Edit Data Pelanggan</h2>
        <form id="editCustomerForm">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_nama">Nama Pelanggan</label>
                <input type="text" id="edit_nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="edit_phone">Nomor Telepon</label>
                <input type="text" id="edit_phone" name="phone">
            </div>
            <div class="form-buttons">
                <button type="button" class="btn-cancel-edit" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner"></div>
    <div class="loading-text">Memproses...</div>
</div>

<!-- Bottom Navigation - Sama seperti eSIM -->
<nav class="bottom-nav">
    <a href="/admin/dashboard" class="nav-item">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Dashboard</span>
    </a>
    <a href="/admin/orders" class="nav-item active">
        <span class="nav-icon">📦</span>
        <span class="nav-label">Orders</span>
    </a>
    <a href="/admin/esim" class="nav-item">
        <span class="nav-icon">📱</span>
        <span class="nav-label">Packages</span>
    </a>
    <a href="/admin/topup" class="nav-item">
        <span class="nav-icon">💰</span>
        <span class="nav-label">DaftarTopup</span>
    </a>
    <a href="/admin/settings" class="nav-item">
        <span class="nav-icon">⚙️</span>
        <span class="nav-label">Settings</span>
    </a>
    <a href="/admin/logout" class="nav-item">
        <span class="nav-icon">👤</span>
        <span class="nav-label">Logout</span>
    </a>
</nav>

<script src="assets/js/orders.js?v=<?= time() ?>"></script>

</body>
</html>