<?php
// web_version/templates/admin/tests/index.php

$page_title = 'Quản lý Bài kiểm tra';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_test') {
    $test_id_to_delete = $_POST['test_id'] ?? null;
    if ($test_id_to_delete) {
        $db->delete('test_questions', 'test_id = ?', [$test_id_to_delete]);
        $db->delete('tests', 'test_id = ?', [$test_id_to_delete]);
        set_message('success', 'Đã xóa bài kiểm tra thành công.');
        redirect('/grader/tests');
    }
}

// Lấy danh sách các cuộc thi để lọc
$contests = $db->fetchAll("SELECT DISTINCT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY contest_name");

$filter_contest = $_GET['contest'] ?? '';

$sql = "SELECT * FROM tests";
$params = [];
if (!empty($filter_contest)) {
    $sql .= " WHERE contest_name = ?";
    $params[] = $filter_contest;
}
$sql .= " ORDER BY created_at DESC";

$tests = $db->fetchAll($sql, $params);

// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1>Quản lý Bài kiểm tra</h1>
    <div>
        <a href="/admin/tests/bulk-generate" class="gdv-button secondary">Tạo đề hàng loạt</a>
        <a href="/grader/tests/edit" class="gdv-button">Thêm bài kiểm tra mới</a>
    </div>
</div>

<div class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px;">
    <form method="GET" action="/grader/tests" style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex-grow: 1;">
            <label for="contest">Lọc theo cuộc thi</label>
            <select id="contest" name="contest" class="input" onchange="this.form.submit()">
                <option value="">Tất cả các đề thi</option>
                <?php foreach ($contests as $contest): ?>
                    <option value="<?php echo htmlspecialchars($contest['contest_name']); ?>" <?php echo ($filter_contest === $contest['contest_name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($contest['contest_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên cuộc thi</th>
                <th>Tiêu đề</th>
                <th>Mã đề</th>
                <th>Thời gian (phút)</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tests)): ?>
                <tr><td colspan="7" style="text-align: center; padding: 2rem;">Không có bài kiểm tra nào phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?php echo $test['test_id']; ?></td>
                        <td><?php echo htmlspecialchars($test['contest_name'] ?? '—'); ?></td>
                        <td><strong><?php echo htmlspecialchars($test['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($test['ma_de']); ?></td>
                        <td><?php echo htmlspecialchars($test['time_limit']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($test['created_at'])); ?></td>
                        <td>
                            <a href="/grader/tests/edit?id=<?php echo $test['test_id']; ?>" class="gdv-action-link">Sửa</a>
                            <form method="POST" action="/grader/tests" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài kiểm tra này?');">
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