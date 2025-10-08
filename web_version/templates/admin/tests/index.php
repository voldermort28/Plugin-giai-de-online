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

// Lấy các tham số lọc, mặc định lọc theo cuộc thi mới nhất
$filter_contest = $_GET['contest'] ?? null;
if ($filter_contest === null && !empty($contests)) {
    // Tìm cuộc thi mới nhất nếu không có filter nào được chọn
    $latest_contest = $db->fetch("SELECT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY test_id DESC LIMIT 1");
    if ($latest_contest) {
        $filter_contest = $latest_contest['contest_name'];
    }
}

$filter_status = $_GET['status'] ?? 'ready'; // Mặc định là 'Sẵn Sàng'

$sql = "SELECT t.*, s.submission_id 
        FROM tests t 
        LEFT JOIN submissions s ON t.test_id = s.test_id";
$params = [];
$where_clauses = [];

if (!empty($filter_contest)) {
    $where_clauses[] = "t.contest_name = ?";
    $params[] = $filter_contest;
}

if ($filter_status === 'ready') {
    // Sẵn sàng: Chưa có submission nào
    $where_clauses[] = "s.submission_id IS NULL";
} elseif ($filter_status === 'used') {
    // Đã dùng: Có ít nhất 1 submission
    $where_clauses[] = "s.submission_id IS NOT NULL";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " GROUP BY t.test_id ORDER BY t.created_at DESC";

$tests = $db->fetchAll($sql, $params);

// Tối ưu hóa: Đếm số lượng cho các tab trong một truy vấn duy nhất
$count_sql = "
    SELECT
        COUNT(DISTINCT t.test_id) AS total,
        COUNT(DISTINCT CASE WHEN s.submission_id IS NULL THEN t.test_id END) AS ready,
        COUNT(DISTINCT CASE WHEN s.submission_id IS NOT NULL THEN t.test_id END) AS used
    FROM tests t
    LEFT JOIN submissions s ON t.test_id = s.test_id
";
$counts_result = $db->fetch($count_sql);
$count_all = $counts_result['total'] ?? 0;
$count_ready = $counts_result['ready'] ?? 0;
$count_used = $counts_result['used'] ?? 0;


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

<div class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div class="gdv-tabs" style="display: flex; gap: 10px;">
        <a href="?status=all&contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button <?php echo $filter_status === 'all' ? '' : 'secondary'; ?>">Tất cả (<?php echo $count_all; ?>)</a>
        <a href="?status=ready&contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button <?php echo $filter_status === 'ready' ? '' : 'secondary'; ?>">Sẵn sàng (<?php echo $count_ready; ?>)</a>
        <a href="?status=used&contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button <?php echo $filter_status === 'used' ? '' : 'secondary'; ?>">Đã dùng (<?php echo $count_used; ?>)</a>
    </div>
    <div>
        <form method="GET" action="/grader/tests" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
            <div style="flex-grow: 1;">
                <label for="contest">Lọc theo cuộc thi</label>
                <select id="contest" name="contest" class="input" onchange="this.form.submit()">
                    <option value="">Tất cả các cuộc thi</option>
                    <?php foreach ($contests as $contest): ?>
                        <option value="<?php echo htmlspecialchars($contest['contest_name']); ?>" <?php echo ($filter_contest === $contest['contest_name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($contest['contest_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        </div>
</div>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã đề</th>
                <th>Tình trạng</th>
                <th>Tiêu đề</th>
                <th>Tên cuộc thi</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tests)): ?>
                <tr><td colspan="7" style="text-align: center; padding: 2rem;">Không có bài kiểm tra nào phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($tests as $test): 
                    $is_used = !is_null($test['submission_id']);
                    $status_class = $is_used ? 'used' : 'ready';
                    $status_text = $is_used ? 'Đã dùng' : 'Sẵn sàng';
                ?>
                    <tr class="<?php echo $status_class; ?>">
                        <td><?php echo $test['test_id']; ?></td>
                        <td>
                            <code><?php echo htmlspecialchars($test['ma_de']); ?></code>
                            <button class="gdv-button secondary copy-ma-de" data-code="<?php echo htmlspecialchars($test['ma_de']); ?>" style="padding: 2px 8px; font-size: 12px; margin-left: 5px;">Copy</button>
                        </td>
                        <td><span class="gdv-status <?php echo $status_class === 'used' ? 'error' : 'ready'; ?>"><?php echo $status_text; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($test['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($test['contest_name'] ?? '—'); ?></td>
                        <td>
                            <?php if (!$is_used): // Chỉ cho phép sửa khi đề chưa được sử dụng ?>
                                <a href="/grader/tests/edit?id=<?php echo $test['test_id']; ?>" class="gdv-action-link">Sửa</a>
                            <?php else: ?>
                                <span class="gdv-action-link" style="color: var(--gdv-text-secondary); cursor: not-allowed;" title="Không thể sửa đề đã được sử dụng">Sửa</span>
                            <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-ma-de').forEach(button => {
        button.addEventListener('click', function() {
            const codeToCopy = this.getAttribute('data-code');
            navigator.clipboard.writeText(codeToCopy).then(() => {
                const originalText = this.innerText;
                this.innerText = 'Đã chép!';
                setTimeout(() => {
                    this.innerText = originalText;
                }, 1500);
            });
        });
    });
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>