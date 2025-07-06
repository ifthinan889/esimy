<?php
// Secure QR code generator
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../config.php';

// Validate and sanitize input
$data = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_STRING);

// If data is empty or invalid, show error image
if (empty($data)) {
    header('Content-Type: image/png');
    $errorImg = imagecreatetruecolor(300, 300);
    $bgColor = imagecolorallocate($errorImg, 255, 255, 255);
    $textColor = imagecolorallocate($errorImg, 255, 0, 0);
    imagefill($errorImg, 0, 0, $bgColor);
    imagestring($errorImg, 5, 50, 140, "Error: No QR data provided", $textColor);
    imagepng($errorImg);
    imagedestroy($errorImg);
    exit;
}

// Rate limiting to prevent abuse
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['qr_requests'])) {
    $_SESSION['qr_requests'] = 1;
    $_SESSION['qr_request_time'] = time();
} else {
    if (time() - $_SESSION['qr_request_time'] < 60) { // 1 minute window
        $_SESSION['qr_requests']++;
        if ($_SESSION['qr_requests'] > 20) { // Max 20 requests per minute
            header('Content-Type: image/png');
            $errorImg = imagecreatetruecolor(300, 300);
            $bgColor = imagecolorallocate($errorImg, 255, 255, 255);
            $textColor = imagecolorallocate($errorImg, 255, 0, 0);
            imagefill($errorImg, 0, 0, $bgColor);
            imagestring($errorImg, 5, 50, 140, "Error: Rate limit exceeded", $textColor);
            imagepng($errorImg);
            imagedestroy($errorImg);
            exit;
        }
    } else {
        // Reset counter after 1 minute
        $_SESSION['qr_requests'] = 1;
        $_SESSION['qr_request_time'] = time();
    }
}

// Path to logo
$logoPath = __DIR__ . "/../assets/images/logo_resize.png";

// Create QR Code using API with secure connection
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);

// Set up cURL with security options
$ch = curl_init($qrUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true, // Disable SSL verification for local testing only
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_USERAGENT => 'eSIM-Portal/1.0'
]);

$qrImageData = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// If failed to get QR code from API
if (!$qrImageData || $httpCode !== 200) {
    error_log("QR API error: " . $error . ", HTTP code: " . $httpCode);
    header('Content-Type: image/png');
    $errorImg = imagecreatetruecolor(300, 300);
    $bgColor = imagecolorallocate($errorImg, 255, 255, 255);
    $textColor = imagecolorallocate($errorImg, 255, 0, 0);
    imagefill($errorImg, 0, 0, $bgColor);
    imagestring($errorImg, 5, 50, 140, "Error: Failed to generate QR", $textColor);
    imagepng($errorImg);
    imagedestroy($errorImg);
    exit;
}

// Create image from string data
$qrImage = @imagecreatefromstring($qrImageData);

// If failed to create image
if (!$qrImage) {
    header('Content-Type: image/png');
    $errorImg = imagecreatetruecolor(300, 300);
    $bgColor = imagecolorallocate($errorImg, 255, 255, 255);
    $textColor = imagecolorallocate($errorImg, 255, 0, 0);
    imagefill($errorImg, 0, 0, $bgColor);
    imagestring($errorImg, 5, 50, 140, "Error: Invalid image data", $textColor);
    imagepng($errorImg);
    imagedestroy($errorImg);
    exit;
}

// Check if logo file exists and is readable
if (file_exists($logoPath) && is_readable($logoPath)) {
    $logo = @imagecreatefrompng($logoPath);
    
    if ($logo) {
        // Preserve transparency
        imagesavealpha($logo, true);
        
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        
        // Logo size should be no more than 30% of QR code
        $newLogoWidth = $qrWidth * 0.3;
        $newLogoHeight = $logoHeight * ($newLogoWidth / $logoWidth);
        
        // Create resized logo with transparency support
        $resizedLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);
        
        // Enable alpha blending
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        
        // Resize logo
        imagecopyresampled($resizedLogo, $logo, 0, 0, 0, 0, $newLogoWidth, $newLogoHeight, $logoWidth, $logoHeight);
        
        // Calculate center position
        $logoX = ($qrWidth - $newLogoWidth) / 2;
        $logoY = ($qrHeight - $newLogoHeight) / 2;
        
        // Create white background for logo
        $white = imagecolorallocate($qrImage, 255, 255, 255);
        imagefilledrectangle($qrImage, $logoX, $logoY, $logoX + $newLogoWidth, $logoY + $newLogoHeight, $white);
        
        // Add logo to QR code
        imagecopy($qrImage, $resizedLogo, $logoX, $logoY, 0, 0, $newLogoWidth, $newLogoHeight);
        
        // Clean up
        imagedestroy($resizedLogo);
        imagedestroy($logo);
    }
}

// Set cache control headers
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Output image
imagepng($qrImage);
imagedestroy($qrImage);
?>