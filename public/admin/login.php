<?php
define('ALLOWED_ACCESS', true);
// KODE YANG BENAR
require_once __DIR__ . '/../../config.php';

try {
    require_once __DIR__ . '/../../src/includes/koneksi.php'; // Naik satu level, lalu masuk ke src/includes
    require_once __DIR__ . '/../../src/includes/functions.php';;
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

$errorMessage = "";
$username = "";

// Process login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent("CSRF token validation failed", 'warning');
        $errorMessage = "Invalid session. Please try again.";
    } else {
        $clientId = $_SERVER['REMOTE_ADDR'] . '_admin_login';
        if (!checkRateLimit($clientId, 5, 300)) {
            $errorMessage = "Too many login attempts. Please try again after 5 minutes.";
            sleep(2);
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $errorMessage = "Username dan password harus diisi!";
            } else {
                $loginResult = attemptLogin($username, $password);
                
                if ($loginResult['success']) {
                    // Set semua data session seperti biasa
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $loginResult['user_id'];
                    $_SESSION['admin_username'] = $loginResult['username'];
                    
                    session_regenerate_id(true);
                    logSecurityEvent("Admin login successful: " . $username, 'info');
                    
                    // ✅ FIX: Paksa tulis data session ke disk sebelum redirect
                    session_write_close();
                    
                    // Baru lakukan redirect
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $errorMessage = $loginResult['message'];
                    logSecurityEvent("Failed login: " . $username, 'warning');
                }
            }
        }
    }
}

function attemptLogin($username, $password) {
    global $pdo; // Asumsikan Anda standarisasi menggunakan PDO

    try {
        // 1. Cari admin berdasarkan username
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Jika admin ditemukan di database
        if ($admin) {
            // Verifikasi password-nya
            if (password_verify($password, $admin['password_hash'])) {
                // Jika password benar, login berhasil
                return ['success' => true, 'user_id' => $admin['id'], 'username' => $admin['username']];
            } else {
                // Jika password salah, langsung gagalkan
                return ['success' => false, 'message' => 'Username atau password salah!'];
            }
        }

        // 3. Jika admin TIDAK ditemukan, cek apakah ini setup pertama kali
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
        $adminCount = $stmt->fetchColumn();

        if ($adminCount == 0 && $username === "admin" && $password === "admin123") {
            // Buat admin baru
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, NOW())");
            if ($stmt->execute([$username, $hash])) {
                // Login berhasil setelah admin dibuat
                return ['success' => true, 'user_id' => $pdo->lastInsertId(), 'username' => $username];
            }
        }
        
        // 4. Jika semua kondisi di atas tidak terpenuhi, berarti login gagal
        return ['success' => false, 'message' => 'Username atau password salah!'];

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
    }
}

function isFirstRun() {
    global $pdo, $conn;
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins");
            $stmt->execute();
            return $stmt->fetch()['count'] == 0;
        } elseif (isset($conn)) {
            $result = $conn->query("SELECT COUNT(*) as count FROM admins");
            return $result->fetch_assoc()['count'] == 0;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Admin - eSIM Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login.css?v=<?= filemtime('../assets/css/login.css') ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="description" content="Admin login for eSIM Portal">
</head>
<body>
    <div class="bg-animation">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
    </div>

    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i class="fas fa-moon theme-icon"></i>
    </button>

    <div class="login-wrapper">
        <div class="login-container" id="loginContainer">
            <div class="login-header">
                <div class="login-logo">
                    <div class="logo-container">
                        <i class="fas fa-shield-alt logo-icon"></i>
                    </div>
                    <h1 class="login-title">eSIM Portal</h1>
                    <p class="login-subtitle">Admin Dashboard</p>
                </div>
            </div>

            <?php if (isFirstRun()): ?>
            <div class="alert alert-info" id="firstRunAlert">
                <div class="alert-content">
                    <i class="fas fa-info-circle alert-icon"></i>
                    <span class="alert-message">Setup awal: Gunakan admin/admin123 untuk login pertama kali</span>
                    <button class="alert-close" onclick="closeAlert('firstRunAlert')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
            <div class="alert alert-error" id="errorAlert">
                <div class="alert-content">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <span class="alert-message"><?= htmlspecialchars($errorMessage) ?></span>
                    <button class="alert-close" onclick="closeAlert('errorAlert')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="" autocomplete="off" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user label-icon"></i>
                        Username
                    </label>
                    <div class="input-container">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-input" 
                               value="<?= htmlspecialchars($username) ?>" 
                               placeholder="<?= isFirstRun() ? 'admin' : 'Masukkan username' ?>"
                               required
                               autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock label-icon"></i>
                        Password
                    </label>
                    <div class="input-container">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="<?= isFirstRun() ? 'admin123' : 'Masukkan password' ?>"
                               required
                               autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" id="rememberMe">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">Remember me</span>
                    </label>
                    <a href="#" class="forgot-password" onclick="showForgotPassword()">
                        Lupa password?
                    </a>
                </div>
                
                <button type="submit" class="login-button" id="loginButton">
                    <span class="button-text">
                        <i class="fas fa-sign-in-alt button-icon"></i>
                        <?= isFirstRun() ? 'Setup Admin' : 'Login' ?>
                    </span>
                    <div class="button-loader">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>

            <div class="login-footer">
                <a href="../index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Halaman Utama
                </a>
                <div class="footer-info">
                    <p class="version-info">v2.0.0</p>
                    <p class="copyright">© <?= date('Y') ?> eSIM Portal</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="forgotPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Lupa Password?</h3>
                <button class="modal-close" onclick="closeModal('forgotPasswordModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Untuk reset password admin, silakan hubungi administrator sistem atau reset melalui database.</p>
            </div>
        </div>
    </div>

    <script src="../assets/js/login.js?v=<?= filemtime('../assets/js/login.js') ?>"></script>
    <script>
        function closeAlert(id) {
            const el = document.getElementById(id);
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-20px)';
                setTimeout(() => el.remove(), 300);
            }
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function showForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.add('show');
        }
    </script>
</body>
</html>