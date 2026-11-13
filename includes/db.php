<?php
/**
 * Tabya Database Connection Helper
 * Connects to MySQL tabya_db
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tabya_db');

function get_db_connection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/**
 * Log Visitor Analytics
 */
function record_visitor($page = 'index.php') {
    $conn = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    $stmt = $conn->prepare("INSERT INTO visitors (ip_address, page, user_agent) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $ip, $page, $ua);
        $stmt->execute();
        $stmt->close();
    }
}

