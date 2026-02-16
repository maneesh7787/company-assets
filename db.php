<?php
/**
 * Database Connection Handler
 * Provides secure database connection using mysqli
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    error_log("Database Connection Error: " . mysqli_connect_error());
    die("Database connection failed. Please contact administrator.");
}

// Set charset to prevent SQL injection via encoding
mysqli_set_charset($conn, "utf8mb4");

/**
 * Execute a prepared statement query
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types (i, d, s, b)
 * @param array $params Parameters to bind
 * @return mysqli_result|bool Query result
 */
function db_query($conn, $query, $types = "", $params = []) {
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Query Preparation Error: " . mysqli_error($conn));
        return false;
    }
    
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Query Execution Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    // For INSERT/UPDATE/DELETE queries
    if (!$result) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected_rows;
    }
    
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Get last insert ID
 * @param mysqli $conn Database connection
 * @return int Last insert ID
 */
function db_insert_id($conn) {
    return mysqli_insert_id($conn);
}

/**
 * Escape output to prevent XSS
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
