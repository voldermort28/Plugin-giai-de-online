<?php
// web_version/templates/admin/users/edit.php

$user_id = $_GET['id'] ?? null;
$is_editing = ($user_id !== null);

$page_title = $is_editing ? 'Sửa Người dùng' : 'Thêm Người dùng mới';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $display_name = $_POST['display_name'] ?? '';
    $role = $_POST['role'] ?? 'grader';
    $password = $_POST['password'] ?? '';

    try {
        if ($is_editing) {
            $auth->updateUser($user_id, $display_name, $role, $password);
            set_message('success', 'Cập nhật người dùng thành công.');
        } else {
            if (empty($password)) { throw new Exception("Mật khẩu là bắt buộc khi tạo người dùng mới."); }
            $auth->createUser($username, $password, $display_name, $role);
            set_message('success', 'Thêm người dùng mới thành công.');
        }
        redirect('/admin/users');
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '1062') !== false) { // Check for duplicate entry error
            set_message('error', 'Lỗi: Tên đăng nhập "' . htmlspecialchars($username) . '" đã tồn tại.');
        } else {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

$user_data = ['username' => '', 'display_name' => '', 'role' => 'grader'];

if ($is_editing) {
    $data = $db->fetch("SELECT user_id, username, display_name, role FROM users WHERE user_id = ?", [$user_id]);
    if (!$data) {
        set_message('error', 'Không tìm thấy người dùng.');
        redirect('/admin/users');
    }
    $user_data = $data;
}

// If there was a POST error, repopulate the form data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST)) {
    $user_data = array_merge($user_data, $_POST);
}

include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1><?php echo $page_title; ?></h1>
    <a href="/admin/users" class="gdv-button secondary">Quay lại danh sách</a>
</div>

<form method="POST" action="/admin/users/edit<?php echo $is_editing ? '?id=' . $user_id : ''; ?>" class="gdv-container" style="max-width: 700px; margin: 20px auto; padding: 40px; background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 12px;">
    <p>
        <label for="username">Tên đăng nhập</label>
        <input type="text" name="username" id="username" class="input" value="<?php echo htmlspecialchars($user_data['username']); ?>" <?php echo $is_editing ? 'readonly' : 'required'; ?>>
        <?php if ($is_editing): ?><small style="color: var(--gdv-text-secondary);">Không thể thay đổi tên đăng nhập.</small><?php endif; ?>
    </p>
    <p>
        <label for="display_name">Tên hiển thị</label>
        <input type="text" name="display_name" id="display_name" class="input" value="<?php echo htmlspecialchars($user_data['display_name']); ?>" required>
    </p>
    <p>
        <label for="password">Mật khẩu</label>
        <input type="password" name="password" id="password" class="input" <?php echo !$is_editing ? 'required' : ''; ?>>
        <small style="color: var(--gdv-text-secondary);">Để trống nếu không muốn thay đổi mật khẩu.</small>
    </p>
    <p>
        <label for="role">Vai trò</label>
        <select name="role" id="role" class="input">
            <option value="grader" <?php echo ($user_data['role'] === 'grader') ? 'selected' : ''; ?>>Giám khảo (Grader)</option>
            <option value="admin" <?php echo ($user_data['role'] === 'admin') ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
        </select>
    </p>

    <p class="submit" style="margin-top: 30px;"><button type="submit" class="gdv-button" style="font-size: 16px; padding: 12px 24px;">Lưu người dùng</button></p>
</form>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>