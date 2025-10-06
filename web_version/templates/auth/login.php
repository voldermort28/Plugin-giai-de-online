<?php
// web_version/templates/auth/login.php

$page_title = 'Đăng nhập';
include APP_ROOT . '/templates/partials/header.php';

// Xử lý form đăng nhập khi có POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$auth->login($username, $password)) {
        set_message('error', 'Tên đăng nhập hoặc mật khẩu không đúng.');
    } else {
        set_message('success', 'Đăng nhập thành công!');
        // Cải tiến: Chuyển hướng dựa trên vai trò
        redirect(has_role('admin') ? '/admin/tests' : '/grader/dashboard');
    }
}

// Nếu đã đăng nhập, chuyển hướng đi
if ($auth->check()) {
    redirect(has_role('admin') ? '/admin/tests' : '/grader/dashboard');
}
?>

<div class="lb-login-form gdv-container">
    <form id="loginform" method="POST" action="/login">
        <h2>Đăng nhập Giám khảo</h2>
        <p>
            <label for="username">Tên đăng nhập</label>
            <input type="text" name="username" id="username" class="input" value="" size="20" required>
        </p>
        <p>
            <label for="password">Mật khẩu</label>
            <input type="password" name="password" id="password" class="input" value="" size="20" required>
        </p>
        <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="gdv-button" value="Đăng nhập"></p>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>