<?php
/**
 * Azure Sands Resort - Database Configuration
 * XAMPP / MySQL connection settings
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'azure_sands_resort');
define('DB_CHARSET', 'utf8mb4');

// If your browser shows http://localhost/azure-sands-resort/
define('SITE_URL', 'http://localhost/azure-sands-resort');

// If your browser shows http://localhost:8080/azure-sands-resort/
define('SITE_URL', 'http://localhost:8080/azure-sands-resort');

// If your browser shows http://127.0.0.1/azure-sands-resort/
define('SITE_URL', 'http://127.0.0.1/azure-sands-resort');
define('SITE_NAME', 'Azure Sands Resort');
define('UPLOADS_PATH', __DIR__ . '/../uploads/');
define('UPLOADS_URL', SITE_URL . '/uploads/');

// Session configuration
define('SESSION_NAME', 'azure_sands_session');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days

/**
 * Get PDO database connection
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed. Please check your XAMPP MySQL server.']));
        }
    }
    return $pdo;
}

/**
 * Start a secure session
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $sessionName = SESSION_NAME;
        $secure      = isset($_SERVER['HTTPS']);
        $httponly    = true;
        ini_set('session.use_only_cookies', 1);
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            SESSION_LIFETIME,
            $cookieParams['path'],
            $cookieParams['domain'],
            $secure,
            $httponly
        );
        session_name($sessionName);
        session_start();
        // NOTE: Do NOT call session_regenerate_id() here — it runs on every request
        // including AJAX calls and would invalidate the CSRF token mid-session.
        // Regeneration is handled explicitly in auth.php on login/logout only.
    }
}

/**
 * Generate a CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate unique reference number
 */
function generateRef(string $prefix = 'REF'): string {
    return $prefix . strtoupper(substr(uniqid(), -8)) . rand(10, 99);
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
