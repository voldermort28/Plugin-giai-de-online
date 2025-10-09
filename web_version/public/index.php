<?php
// web_version/public/index.php

session_start();

// Định nghĩa đường dẫn gốc của ứng dụng
define('APP_ROOT', dirname(__DIR__));

// Nạp file cấu hình và các hàm cốt lõi
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/core/functions.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Auth.php';

// Khởi tạo đối tượng Database và Auth
$db = new Database($pdo); // $pdo được định nghĩa trong config/database.php
$auth = new Auth($db);

// Lấy đường dẫn yêu cầu từ URL, loại bỏ query string
$request_uri = strtok($_SERVER["REQUEST_URI"], '?');

// Simple Router
$routes = [
    '/' => ['file' => APP_ROOT . '/templates/test/entry.php', 'auth' => false],
    '/test' => ['file' => APP_ROOT . '/templates/test/take.php', 'auth' => false],
    '/test/confirm' => ['file' => APP_ROOT . '/templates/test/confirm.php', 'auth' => false],
    '/login' => ['file' => APP_ROOT . '/templates/auth/login.php', 'auth' => false],
    '/leaderboard' => ['file' => APP_ROOT . '/templates/partials/leaderboard.php', 'auth' => false],
    '/logout' => ['file' => APP_ROOT . '/templates/auth/logout.php', 'auth' => true],
    '/grader/dashboard' => ['file' => APP_ROOT . '/templates/grader/dashboard.php', 'auth' => true, 'role' => 'grader'],
    '/grader/grade' => ['file' => APP_ROOT . '/templates/grader/grade.php', 'auth' => true, 'role' => 'grader'],
    '/api/ajax' => ['file' => APP_ROOT . '/core/ajax.php', 'auth' => false],
    // Admin Routes
    '/grader/tests' => ['file' => APP_ROOT . '/templates/admin/tests/index.php', 'auth' => true, 'role' => 'grader'], // Đổi từ /admin/tests
    '/admin/tests/bulk-generate' => ['file' => APP_ROOT . '/templates/admin/tests/bulk-generate.php', 'auth' => true, 'role' => 'admin'],
    '/grader/tests/edit' => ['file' => APP_ROOT . '/templates/admin/tests/edit.php', 'auth' => true, 'role' => 'grader'], // Đổi từ /admin/tests/edit
    '/admin/contestants' => ['file' => APP_ROOT . '/templates/partials/index.php', 'auth' => true, 'role' => 'grader'],
    '/admin/contestants/view' => ['file' => APP_ROOT . '/templates/partials/view.php', 'auth' => true, 'role' => 'grader'],
    '/admin/questions' => ['file' => APP_ROOT . '/templates/admin/questions/index.php', 'auth' => true, 'role' => 'admin'],
    '/admin/questions/edit' => ['file' => APP_ROOT . '/templates/admin/questions/edit.php', 'auth' => true, 'role' => 'admin'],
    '/admin/users' => ['file' => APP_ROOT . '/templates/admin/users/index.php', 'auth' => true, 'role' => 'admin'],
    '/admin/users/edit' => ['file' => APP_ROOT . '/templates/admin/users/edit.php', 'auth' => true, 'role' => 'admin'],
    '/admin/leaderboard' => ['file' => APP_ROOT . '/templates/admin/leaderboard/index.php', 'auth' => true, 'role' => 'admin'],
    '/admin/import' => ['file' => APP_ROOT . '/templates/admin/import/index.php', 'auth' => true, 'role' => 'admin'],
];

/**
 * Hàm render template và truyền các biến cần thiết vào.
 * Điều này giải quyết vấn đề scope của biến một cách triệt để.
 * @param string $file Đường dẫn đến file template.
 * @param array $data Các biến cần truyền vào, ví dụ ['db' => $db, 'auth' => $auth].
 */
function render_template($file, $data = []) {
    // Giải nén mảng thành các biến riêng lẻ (e.g., $data['db'] trở thành $db)
    extract($data);
    require_once $file;
}

// Xử lý route
if (isset($routes[$request_uri])) {
    $route = $routes[$request_uri];
    if ($route['auth'] && !$auth->check()) {
        redirect('/login');
    }
    if (isset($route['role'])) {
        $user_role = $auth->user()['role'] ?? '';
        $required_role = $route['role'];
        if (($required_role === 'admin' && $user_role !== 'admin') ||
            ($required_role === 'grader' && !in_array($user_role, ['admin', 'grader']))) {
            set_message('error', 'Bạn không có quyền truy cập trang này.');
            redirect('/');
        }
    }
    // Sử dụng hàm render_template thay vì require_once trực tiếp
    // Truyền các đối tượng cốt lõi vào mọi template
    render_template($route['file'], ['db' => $db, 'auth' => $auth]);
} else {
    http_response_code(404);
    echo '<h1>404 Not Found</h1><p>Trang bạn tìm kiếm không tồn tại.</p>';
}