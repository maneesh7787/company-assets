<?php
/**
 * Security Functions
 * Handles CSRF protection, session management, and input validation
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Start secure session
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Check token expiry
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE)) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Require login
 * Redirects to login page if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Check user role
 * @param array $allowed_roles Allowed roles
 * @return bool True if user has allowed role
 */
function check_role($allowed_roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    return in_array($user_role, $allowed_roles);
}

/**
 * Require specific role
 * @param array $allowed_roles Allowed roles
 */
function require_role($allowed_roles) {
    require_login();
    
    if (!check_role($allowed_roles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

/**
 * Sanitize input
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Validate email
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array Array with 'valid' boolean and 'message' string
 */
function validate_password($password) {
    $result = ['valid' => true, 'message' => ''];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $result['valid'] = false;
        $result['message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        return $result;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one uppercase letter';
        return $result;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one lowercase letter';
        return $result;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one number';
        return $result;
    }
    
    if (!preg_match('/[@$!%*?&#]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one special character (@$!%*?&#)';
        return $result;
    }
    
    return $result;
}

/**
 * Log audit event
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $description Description of action
 */
function log_audit($conn, $user_id, $action, $description) {
    $query = "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)";
    db_query($conn, $query, "iss", [$user_id, $action, $description]);
}

/**
 * Regenerate session ID for security
 */
function regenerate_session() {
    session_regenerate_id(true);
}
?>
