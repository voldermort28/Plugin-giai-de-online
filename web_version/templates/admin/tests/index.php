<?php
// web_version/templates/admin/tests/index.php

$page_title = 'Quản lý Bài kiểm tra';
include APP_ROOT . '/templates/partials/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_test') {
    $test_id_to_delete = $_POST['test_id'] ?? null;
    if ($test_id_to_delete) {
        $db->delete('test_questions', 'test_id = ?', [$test_id_to_delete]);
        $db->delete('tests', 'test_id = ?', [$test_id_to_delete]);
        set_message('success', 'Đã xóa bài kiểm tra thành công.');
        redirect('/admin/tests');
    }
}

$tests = $db->fetchAll("SELECT * FROM tests ORDER BY created_at DESC");
?>

<div class="gdv-header">
    <h1>Quản lý Bài kiểm tra</h1>
    <a href="/admin/tests/edit" class="gdv-button">Thêm bài kiểm tra mới</a>
</div>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tiêu đề</th>
                <th>Mã đề</th>
                <th>Thời gian (phút)</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tests)): ?>
                <tr><td colspan="6" style="text-align: center;">Chưa có bài kiểm tra nào.</td></tr>
            <?php else: ?>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?php echo $test['test_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($test['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($test['ma_de']); ?></td>
                        <td><?php echo htmlspecialchars($test['time_limit']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($test['created_at'])); ?></td>
                        <td>
                            <a href="/admin/tests/edit?id=<?php echo $test['test_id']; ?>" class="gdv-action-link">Sửa</a>
                            <form method="POST" action="/admin/tests" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài kiểm tra này?');">
                                <input type="hidden" name="action" value="delete_test"><input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                <button type="submit" class="gdv-action-link" style="border:none; background:none; cursor:pointer; color: #dc3545;">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>