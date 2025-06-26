<?php
// Secure error page with modern UI
// Prevent direct access
if (!defined('ALLOWED_ACCESS') && !isset($token)) {
    // Log unauthorized access attempt
    error_log("Unauthorized direct access to error.php from " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown IP'));
    
    // Get error code and message from URL parameters if available
    $errorCode = filter_input(INPUT_GET, 'code', FILTER_VALIDATE_INT) ?: 403;
    
    // FIXED: Proper null handling for trim()
    $rawMessage = filter_input(INPUT_GET, 'message', FILTER_UNSAFE_RAW) ?? '';
    $errorMessage = !empty($rawMessage) ? htmlspecialchars(strip_tags(trim($rawMessage)), ENT_QUOTES, 'UTF-8') : 'Akses Ditolak';
} else {
    // Default error for token-related issues
    $errorCode = 404;
    $errorMessage = 'Token Tidak Valid';
}

// Set appropriate HTTP status code
http_response_code(intval($errorCode));
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $errorCode ?> - eSIM Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/error.css">
    <meta name="theme-color" content="#667eea">
</head>
<body>
    <div class="error-container">
        <img src="https://cdn-icons-png.flaticon.com/512/564/564619.png" alt="Error" class="error-img">
        <h1 class="error-title">❌ Oops! <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="error-message">
            <?php if ($errorCode == 404): ?>
                Sepertinya token yang kamu masukkan tidak valid. Pastikan tokennya benar ya! 🔍
            <?php elseif ($errorCode == 403): ?>
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            <?php else: ?>
                Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi nanti.
            <?php endif; ?>
        </p>
        <a class="error-btn" href="index.php">🔙 Kembali ke Halaman Utama</a>
    </div>
</body>
</html>