<?php
// web_version/templates/admin/users/index.php

$page_title = 'Quản lý Người dùng';

$current_user_id = $auth->user()['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id_to_delete = $_POST['user_id'] ?? null;
    validate_csrf_token();
    if ($user_id_to_delete && $user_id_to_delete != $current_user_id) {
        $db->delete('users', 'user_id = ?', [$user_id_to_delete]);
        set_message('success', 'Đã xóa người dùng thành công.');
    } else {
        set_message('error', 'Bạn không thể xóa chính mình.');
    }
    redirect('/admin/users');
}

$users = $db->fetchAll("SELECT user_id, username, display_name, role FROM users ORDER BY role, username");

// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-filter-bar" style="justify-content: flex-end;">
    <div class="gdv-filter-bar__actions">
        <a href="/admin/users/edit" class="gdv-button">Thêm người dùng mới</a>
    </div>
</div>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Tên hiển thị</th>
                <th>Vai trò</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" style="text-align: center;">Chưa có người dùng nào.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                        <td><span class="gdv-status"><?php echo htmlspecialchars($user['role']); ?></span></td>
                        <td>
                            <a href="/admin/users/edit?id=<?php echo $user['user_id']; ?>" class="gdv-action-link">Sửa</a>
                            <?php if ($user['user_id'] != $current_user_id): ?>
                                <form method="POST" action="/admin/users" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="gdv-action-link" style="border:none; background:none; cursor:pointer; color: #dc3545;">Xóa</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>