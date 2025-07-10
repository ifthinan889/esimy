<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Jalankan: php bulk_update_esim_cli.php [--delay=0.8] [--filter=PENDING,UNKNOWN]
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../config.php';
include '../includes/koneksi.php';
include '../includes/functions.php';
include '../includes/api.php';

function updateEsimDataFromApi($pdo, $orderNo, $iccid = '') {
    try {
        error_log("Force updating eSIM data for orderNo: $orderNo");
        
        $apiResult = queryEsimDetails($orderNo);
        error_log("API Response for $orderNo: " . json_encode($apiResult));
        
        if (isset($apiResult['success']) && $apiResult['success']) {
            if (isset($apiResult['obj']['esimList']) && count($apiResult['obj']['esimList']) > 0) {
                $esim = $apiResult['obj']['esimList'][0];

                $iccid_api   = $esim['iccid'] ?? $iccid;
                $esim_status = $esim['esimStatus'] ?? 'UNKNOWN';
                $smdp_status = $esim['smdpStatus'] ?? '';
                $order_usage = isset($esim['orderUsage']) ? (int)$esim['orderUsage'] : 0;

                $status_db = $esim_status;
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
                    $iccid_api, $status_db, $esim_status, $smdp_status, $order_usage, $orderNo
                ]);

                if (!$result) {
                    error_log("Update failed for orderNo: $orderNo");
                    return false;
                }

                // **Hapus jika status USED_EXPIRED**
                $statusToDelete = ['USED_EXPIRED', 'CANCEL'];
                if (in_array(strtoupper($esim_status), $statusToDelete)) {
                    $del = $pdo->prepare("DELETE FROM esim_orders WHERE orderNo = ?");
                    $del->execute([$orderNo]);
                    error_log("Order $orderNo status $esim_status, DELETED from DB.");
                    echo "DELETED ($esim_status)\n";
                } else {
                    error_log("Successfully updated orderNo: $orderNo with status: $esim_status");
                }
                return true;

            } else {
                // API sukses tapi tidak ada data eSIM
                error_log("Order $orderNo: API sukses tapi eSIM tidak ditemukan (mungkin belum diaktivasi atau sudah expired)");
                $stmt = $pdo->prepare("UPDATE esim_orders SET esim_status = 'NOT_FOUND' WHERE orderNo = ?");
                $stmt->execute([$orderNo]);
                return true;
            }

        } else {
            $errorMsg = $apiResult['errorMsg'] ?? 'Unknown API error';
            error_log("API gagal untuk order $orderNo: " . $errorMsg);
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception in updateEsimDataFromApi for $orderNo: " . $e->getMessage());
        return false;
    }
}


// --- Parameter CLI ---
$delay = 0.13; // default: 0.8 detik antar request
$statusFilter = [];
foreach ($argv as $arg) {
    if (preg_match('/^--delay=([\d.]+)/', $arg, $m)) $delay = floatval($m[1]);
    if (preg_match('/^--filter=([\w,]+)/', $arg, $m)) $statusFilter = explode(',', $m[1]);
}
echo "eSIM CLI Mass Update -- delay $delay detik antar update\n";
if ($statusFilter) echo "Filter status: ".implode(',',$statusFilter)."\n";

// --- Ambil Semua Data ---
$where = '';
$params = [];
if (!empty($statusFilter)) {
    $in = implode(',', array_fill(0, count($statusFilter), '?'));
    $where = "WHERE (status IN ($in) OR esim_status IN ($in))";
    $params = array_merge($statusFilter, $statusFilter);
}
$sql = "SELECT orderNo, iccid FROM esim_orders $where ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($orders);

if ($total < 1) {
    echo "No eSIM orders found with that filter!\n";
    exit(0);
}

echo "Akan update $total data eSIM dari API...\n";
$logFile = __DIR__.'/bulk_update_esim_cli.log';
file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] MULAI update $total eSIM\n", FILE_APPEND);

$success = 0; $fail = 0;
foreach ($orders as $idx=>$order) {
    $no = $idx+1;
    $orderNo = $order['orderNo'];
    $iccid = $order['iccid'];
    echo "[$no/$total] Update orderNo: $orderNo ... ";
    try {
        $ok = updateEsimDataFromApi($pdo, $orderNo, $iccid);
        if ($ok) {
            echo "OK\n"; $success++;
        } else {
            echo "FAILED\n"; $fail++;
            file_put_contents($logFile, "FAIL: $orderNo\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()."\n"; $fail++;
        file_put_contents($logFile, "ERROR $orderNo: ".$e->getMessage()."\n", FILE_APPEND);
    }
    if ($no < $total) usleep($delay * 1000000); // Jeda antar request (detik)
}

echo "\nSelesai. Sukses: $success | Gagal: $fail\n";
file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] SELESAI. Sukses: $success | Gagal: $fail\n", FILE_APPEND);
exit(0);
