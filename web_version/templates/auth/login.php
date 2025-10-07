<?php
// web_version/templates/auth/login.php

$page_title = 'Đăng nhập';

// Nếu đã đăng nhập, chuyển hướng đi ngay lập tức
if ($auth->check()) {
    redirect($auth->hasRole('admin') ? '/admin/tests' : '/grader/dashboard');
}

// Xử lý form đăng nhập khi có POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        set_message('success', 'Đăng nhập thành công!');
        redirect($auth->hasRole('admin') ? '/admin/tests' : '/grader/dashboard');
    } else {
        set_message('error', 'Tên đăng nhập hoặc mật khẩu không đúng.');
        // Luôn redirect sau POST, kể cả khi lỗi
        redirect('/login');
    }
}
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="lb-login-form">
    <form id="loginform" method="POST" action="/login" class="gdv-card" style="max-width: 400px;">
        <h2 style="text-align: center;">Đăng nhập</h2>
        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input type="text" name="username" id="username" class="input" value="" size="20" required>
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" name="password" id="password" class="input" value="" size="20" required>
        </div>
        <div class="form-group"><button type="submit" class="gdv-button" style="width: 100%;">Đăng nhập</button></div>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>