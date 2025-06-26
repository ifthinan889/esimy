<?php
// Secure admin login with protection against brute force attacks
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../config.php';

// Include database connection
try {
    include '../includes/koneksi.php';
} catch (Exception $e) {
    error_log("Failed to include database connection: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$errorMessage = "";
$username = "";

// Process login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent("CSRF token validation failed in admin login", 'warning');
        $errorMessage = "Invalid session. Please try again.";
    } else {
        // Implement rate limiting for login attempts
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = time();
        }
        
        // Check if too many attempts
        if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 300) {
            logSecurityEvent("Too many login attempts - IP: " . $_SERVER['REMOTE_ADDR'], 'warning');
            $errorMessage = "Too many login attempts. Please try again after 5 minutes.";
            
            // Add a small delay to slow down brute force attacks
            sleep(2);
        } else {
            // Reset counter if 5 minutes have passed
            if ((time() - $_SESSION['last_attempt_time']) > 300) {
                $_SESSION['login_attempts'] = 0;
            }
            
            // Update attempt counter
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            
            // Sanitize inputs
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $password = $_POST['password'] ?? '';
            
            // Validate inputs
            if (empty($username) || empty($password)) {
                $errorMessage = "Username dan password harus diisi!";
            } else {
                // Check if using PDO or mysqli
                if (isset($pdo)) {
                    // Using PDO
                    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Login successful
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        
                        // Reset login attempts
                        $_SESSION['login_attempts'] = 0;
                        
                        // Regenerate session ID after successful login
                        session_regenerate_id(true);
                        
                        // Log successful login
                        logSecurityEvent("Admin login successful: " . $username, 'info');
                        
                        // Redirect to dashboard
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        // Check if admin table exists but using default credentials
                        if ($username === "admin" && $password === "admin123") {
                            // Default credentials - check if admin table is empty
                            $checkStmt = $pdo->query("SELECT COUNT(*) FROM admins");
                            $adminCount = $checkStmt->fetchColumn();
                            
                            if ($adminCount == 0) {
                                // Allow default login if no admins exist
                                $_SESSION['admin_logged_in'] = true;
                                $_SESSION['admin_username'] = $username;
                                
                                // Create admin user with secure password
                                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                $insertStmt = $pdo->prepare("INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, NOW())");
                                $insertStmt->execute([$username, $hashedPassword]);
                                
                                // Log default login
                                logSecurityEvent("Admin login with default credentials - created admin user", 'warning');
                                
                                // Regenerate session ID
                                session_regenerate_id(true);
                                
                                header("Location: dashboard.php");
                                exit();
                            }
                        }
                        
                        // Login failed
                        $errorMessage = "Username atau password salah!";
                        logSecurityEvent("Failed admin login attempt for username: " . $username, 'warning');
                    }
                } else {
                    // Using mysqli
                    $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password_hash'])) {
                            // Login successful
                            $_SESSION['admin_logged_in'] = true;
                            $_SESSION['admin_id'] = $user['id'];
                            $_SESSION['admin_username'] = $user['username'];
                            
                            // Reset login attempts
                            $_SESSION['login_attempts'] = 0;
                            
                            // Regenerate session ID after successful login
                            session_regenerate_id(true);
                            
                            // Log successful login
                            logSecurityEvent("Admin login successful: " . $username, 'info');
                            
                            // Redirect to dashboard
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $errorMessage = "Username atau password salah!";
                            logSecurityEvent("Failed admin login attempt for username: " . $username, 'warning');
                        }
                    } else {
                        // Check if admin table exists but using default credentials
                        if ($username === "admin" && $password === "admin123") {
                            // Default credentials - check if admin table is empty
                            $checkResult = $conn->query("SELECT COUNT(*) as count FROM admins");
                            $adminCount = $checkResult->fetch_assoc()['count'];
                            
                            if ($adminCount == 0) {
                                // Allow default login if no admins exist
                                $_SESSION['admin_logged_in'] = true;
                                $_SESSION['admin_username'] = $username;
                                
                                // Create admin user with secure password
                                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                $insertStmt = $conn->prepare("INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, NOW())");
                                $insertStmt->bind_param("ss", $username, $hashedPassword);
                                $insertStmt->execute();
                                
                                // Log default login
                                logSecurityEvent("Admin login with default credentials - created admin user", 'warning');
                                
                                // Regenerate session ID
                                session_regenerate_id(true);
                                
                                header("Location: dashboard.php");
                                exit();
                            }
                        }
                        
                        $errorMessage = "Username atau password salah!";
                        logSecurityEvent("Failed admin login attempt for username: " . $username, 'warning');
                    }
                    
                    $stmt->close();
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Admin - eSIM Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <meta name="theme-color" content="#4361ee">
    <meta name="description" content="Admin login for eSIM Portal">
    <!-- Security headers -->
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src https://fonts.gstatic.com; img-src 'self' data:;">
</head>
<body style="background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh;">

<div class="login-container">
    <div class="login-logo">
        <img src="assets/images/logo.png" alt="eSIM Portal Logo">
    </div>
    
    <h1 class="login-title">Login Admin</h1>
    
    <?php if ($errorMessage): ?>
    <div class="error-message">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>
    
    <form class="login-form" method="POST" action="" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="login-button">Login</button>
    </form>
    
    <a href="../index.php" class="back-link">Kembali ke Halaman Utama</a>
</div>

<script>
// Simple form validation
document.querySelector('.login-form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        e.preventDefault();
        alert('Username dan password harus diisi!');
    }
});
</script>

</body>
</html>