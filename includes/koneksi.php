<?php
// Prevent direct access
if (!defined('ALLOWED_ACCESS')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

// Database connection configuration
$host = DB_HOST ?? "localhost";
$user = DB_USER ?? "hanisyaa_esim_db";
$pass = DB_PASS ?? "Solokota10.";
$dbname = DB_NAME ?? "hanisyaa_esim_db";

// Error handling
try {
    // Create PDO connection with error handling
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // For backward compatibility with mysqli code
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Check mysqli connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error but don't expose details
    error_log("Database connection error: " . $e->getMessage());
    
    // Return a generic error for production
    if (basename($_SERVER['PHP_SELF']) !== 'error.php') {
        header("Location: error.php?code=500&message=" . urlencode("Database connection error"));
        exit();
    } else {
        // Prevent redirect loop if already on error page
        exit("A database error occurred. Please try again later.");
    }
}

/**
 * Execute a PDO prepared statement safely
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @param bool $fetchAll Whether to fetch all results or just one
 * @return mixed Query results or false on failure
 */
function dbQuery($sql, $params = [], $fetchAll = true) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (stripos($sql, 'SELECT') === 0) {
            return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage() . " - SQL: " . $sql);
        return false;
    }
}

/**
 * Insert data into a table
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|bool Last insert ID or false on failure
 */
function dbInsert($table, $data) {
    global $pdo;
    
    try {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Database insert error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update data in a table
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value to update
 * @param array $where Associative array of column => value for WHERE clause
 * @return bool Success or failure
 */
function dbUpdate($table, $data, $where) {
    global $pdo;
    
    try {
        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE " . $table . " SET " . implode(', ', $setParts) . 
               " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Database update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete data from a table
 * 
 * @param string $table Table name
 * @param array $where Associative array of column => value for WHERE clause
 * @return bool Success or failure
 */
function dbDelete($table, $where) {
    global $pdo;
    
    try {
        $whereParts = [];
        $params = [];
        
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql = "DELETE FROM " . $table . " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Database delete error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get markup configuration from database
 */
function getMarkupConfig() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute(['markup_config']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['setting_value'])) {
            $markupData = json_decode($result['setting_value'], true);
            if (is_array($markupData) && !empty($markupData)) {
                return $markupData;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting markup config: " . $e->getMessage());
    }
    
    // Fallback default
    return [
        ['limit' => 0.5, 'markup' => 5000],
        ['limit' => 1, 'markup' => 8000],
        ['limit' => 2, 'markup' => 12000],
        ['limit' => 5, 'markup' => 20000],
        ['limit' => 10, 'markup' => 30000]
    ];
}

/**
 * Update markup configuration
 */
function updateMarkupConfig($markupConfig) {
    global $pdo;
    
    try {
        $jsonConfig = json_encode($markupConfig);
        $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute(['markup_config', $jsonConfig]);
    } catch (Exception $e) {
        error_log("Error updating markup config: " . $e->getMessage());
        return false;
    }
}