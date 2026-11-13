<?php
/**
 * Tabya Admin Authentication Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

function get_logged_admin() {
    if (!is_admin_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'full_name' => $_SESSION['admin_fullname'] ?? 'Admin',
        'email' => $_SESSION['admin_email'] ?? ''
    ];
}

function attempt_admin_login($username, $password) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM admins WHERE username = ? LIMIT 1");
    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error.'];
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['admin_fullname'] = $row['full_name'];
            $_SESSION['admin_email'] = $row['email'];
            $stmt->close();
            return ['success' => true];
        }
    }
    $stmt->close();
    return ['success' => false, 'error' => 'Username atau password salah.'];
}

function admin_logout() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_fullname']);
    unset($_SESSION['admin_email']);
    session_destroy();
}

