<?php
// web_version/core/functions.php

function redirect($url, $status = 302) {
    header("Location: " . $url, true, $status);
    exit();
}

function set_message($type, $message) {
    // Store a single message as an array containing its type and text.
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
}

function get_message() {
    // Retrieve the single message and then clear it.
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

function has_message() {
    // Check if the single message exists.
    return !empty($_SESSION['message']);
}

/**
 * Tạo và lưu trữ CSRF token trong session nếu chưa tồn tại.
 * @return string CSRF token.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * In ra một thẻ input hidden chứa CSRF token.
 */
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Xác thực CSRF token được gửi từ form.
 * @param string $token Token được gửi từ form.
 * @return bool True nếu hợp lệ, False nếu không.
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}