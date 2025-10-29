<?php
// core/functions.php

// --- Các hàm tiện ích cơ bản (nếu chưa có) ---
// Hàm đặt thông báo vào session
if (!function_exists('set_message')) {
    function set_message($type, $text) {
        $_SESSION['message'] = ['type' => $type, 'text' => $text];
    }
}

// Hàm kiểm tra có thông báo hay không
if (!function_exists('has_message')) {
    function has_message() {
        return isset($_SESSION['message']);
    }
}

// Hàm lấy thông báo và xóa khỏi session
if (!function_exists('get_message')) {
    function get_message() {
        $message = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);
        return $message;
    }
}

// Hàm chuyển hướng trang
if (!function_exists('redirect')) {
    function redirect($path) {
        header("Location: " . $path);
        exit();
    }
}

// --- Các hàm bảo vệ CSRF ---
// Tạo token CSRF mới nếu chưa có trong session
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Xác minh token CSRF
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        // Sử dụng hash_equals để ngăn chặn tấn công thời gian (timing attacks)
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Hiển thị trường ẩn chứa token CSRF trong form
if (!function_exists('csrf_field')) {
    function csrf_field() {
        echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
    }
}

// Hàm kiểm tra và xác thực token CSRF từ POST request
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token() {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            set_message('error', 'Lỗi xác thực (CSRF token không hợp lệ). Vui lòng thử lại.');
            redirect('/'); // Chuyển hướng về trang chủ hoặc trang an toàn
        }
    }
}