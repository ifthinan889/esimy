<?php
// File: sync_packages.php
// Script sync packages dari eSIM Access API ke database existing

require_once __DIR__ . '/config.php';
include_once 'includes/koneksi.php';
include_once 'includes/functions.php';
include_once 'includes/api.php';

// Fungsi log
function writeLog($message) {
    $logFile = __DIR__ . '/logs/sync_packages.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

// Sync packages
function syncPackages($pdo, $filter, $typeName) {
    writeLog("🔄 Syncing $typeName packages with filter: '$filter'");
    
    try {
        $packages = getPackageList($filter);
        
        if (!$packages["success"] || !isset($packages["obj"]["packageList"])) {
            writeLog("❌ Failed to fetch $typeName packages from API");
            return [0, 0];
        }
        
        $packageList = $packages["obj"]["packageList"];
        writeLog("📦 Found " . count($packageList) . " $typeName packages from API");
        
        $synced = 0;
        $failed = 0;
        
        foreach ($packageList as $package) {
            try {
                $packageCode = $package["packageCode"];
                
                // Ambil data langsung dari API
                $name = $package["name"] ?? '';
                $description = $package["description"] ?? '';
                $volume = intval($package["volume"] ?? 0);
                $duration = intval($package["duration"] ?? 0);
                $durationUnit = $package["durationUnit"] ?? 'DAY';
                $priceUSD = intval($package["price"] ?? 0); // Harga wholesale dalam cents
                $retailPrice = intval($package["retailPrice"] ?? 0); // Harga retail dalam cents dari API
                $speed = $package["speed"] ?? '';
                $ipExport = $package["ipExport"] ?? '';
                $supportTopupType = intval($package["supportTopUpType"] ?? 1);
                $fupPolicy = $package["fupPolicy"] ?? '';
                $dataType = intval($package["dataType"] ?? 1);
                
                // Location handling
                $locationCode = '';
                $locationName = '';
                
                if ($typeName === 'local') {
                    $locationCode = $package["location"] ?? '';
                    // Ambil location name dari locationNetworkList
                    if (isset($package["locationNetworkList"]) && is_array($package["locationNetworkList"])) {
                        $locations = [];
                        foreach ($package["locationNetworkList"] as $loc) {
                            if (!empty($loc["locationName"])) {
                                $locations[] = $loc["locationName"];
                            }
                        }
                        $locationName = implode(', ', $locations);
                    }
                } else {
                    $locationCode = $filter; // !RG atau !GL
                    // Ambil semua location names untuk regional/global
                    if (isset($package["locationNetworkList"]) && is_array($package["locationNetworkList"])) {
                        $locations = [];
                        foreach ($package["locationNetworkList"] as $loc) {
                            if (!empty($loc["locationName"])) {
                                $locations[] = $loc["locationName"];
                            }
                        }
                        $locationName = implode(', ', $locations);
                    }
                }
                
                // Cek apakah package sudah ada
                $stmt = $pdo->prepare("SELECT id FROM packages WHERE package_code = ?");
                $stmt->execute([$packageCode]);
                $exists = $stmt->fetch();
                
                if ($exists) {
                    // Update existing package
                    $sql = "UPDATE packages SET 
                            name = ?, description = ?, location_code = ?, location_name = ?,
                            volume = ?, duration = ?, duration_unit = ?, price_usd = ?, selling_price = ?,
                            speed = ?, type = ?, ip_export = ?, support_topup_type = ?,
                            fup_policy = ?, updated_at = NOW()
                            WHERE package_code = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute([
                        $name, $description, $locationCode, $locationName,
                        $volume, $duration, $durationUnit, $priceUSD, $retailPrice,
                        $speed, strtoupper($typeName), $ipExport, $supportTopupType,
                        $fupPolicy, $packageCode
                    ]);
                    
                    if ($success) {
                        $synced++;
                        // Log unlimited packages
                        if ($dataType === 2 || ($supportTopupType === 1 && !empty($fupPolicy))) {
                            writeLog("🔄 Updated unlimited package: $packageCode");
                        }
                    } else {
                        writeLog("❌ Failed to update: $packageCode");
                        $failed++;
                    }
                } else {
                    // Insert new package
                    $sql = "INSERT INTO packages (
                            package_code, name, description, location_code, location_name,
                            volume, duration, duration_unit, price_usd, selling_price,
                            speed, type, ip_export, is_active, support_topup_type,
                            fup_policy, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())";
                    
                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute([
                        $packageCode, $name, $description, $locationCode, $locationName,
                        $volume, $duration, $durationUnit, $priceUSD, $retailPrice,
                        $speed, strtoupper($typeName), $ipExport, $supportTopupType,
                        $fupPolicy
                    ]);
                    
                    if ($success) {
                        $synced++;
                        // Log unlimited packages
                        if ($dataType === 2 || ($supportTopupType === 1 && !empty($fupPolicy))) {
                            writeLog("➕ Added unlimited package: $packageCode");
                        }
                    } else {
                        writeLog("❌ Failed to insert: $packageCode");
                        $failed++;
                    }
                }
                
            } catch (Exception $e) {
                writeLog("❌ Error processing $packageCode: " . $e->getMessage());
                $failed++;
            }
        }
        
        writeLog("✅ $typeName sync complete: $synced synced, $failed failed");
        return [$synced, $failed];
        
    } catch (Exception $e) {
        writeLog("❌ Error syncing $typeName: " . $e->getMessage());
        return [0, 1];
    }
}

// MAIN EXECUTION
writeLog("🚀 Starting eSIM package sync...");

try {
    $totalSynced = 0;
    $totalFailed = 0;
    
    // Sync LOCAL packages
    list($localSynced, $localFailed) = syncPackages($pdo, "", "local");
    $totalSynced += $localSynced;
    $totalFailed += $localFailed;
    
    // Sync REGIONAL packages  
    list($regionalSynced, $regionalFailed) = syncPackages($pdo, "!RG", "regional");
    $totalSynced += $regionalSynced;
    $totalFailed += $regionalFailed;
    
    // Sync GLOBAL packages
    list($globalSynced, $globalFailed) = syncPackages($pdo, "!GL", "global");
    $totalSynced += $globalSynced;
    $totalFailed += $globalFailed;
    
    // Final summary
    writeLog("🎉 SYNC COMPLETE!");
    writeLog("📊 Total: $totalSynced synced, $totalFailed failed");
    writeLog("📈 Local: $localSynced/$localFailed | Regional: $regionalSynced/$regionalFailed | Global: $globalSynced/$globalFailed");
    
    // Tampilkan statistik database
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM packages WHERE is_active = 1 GROUP BY type");
    writeLog("📋 Database statistics:");
    while ($row = $stmt->fetch()) {
        writeLog("   {$row['type']}: {$row['count']} packages");
    }
    
    // Tampilkan unlimited packages count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM packages WHERE (support_topup_type = 1 AND fup_policy != '') OR fup_policy LIKE '%unlimited%' OR fup_policy LIKE '%daily%'");
    $unlimitedCount = $stmt->fetchColumn();
    writeLog("🔄 Unlimited packages detected: $unlimitedCount");
    
} catch (Exception $e) {
    writeLog("💥 FATAL ERROR: " . $e->getMessage());
    exit("💥 Sync failed\n");
}

writeLog("✨ Sync finished successfully!");
?>