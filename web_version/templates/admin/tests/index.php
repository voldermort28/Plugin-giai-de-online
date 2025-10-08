<?php
// web_version/templates/admin/tests/index.php

$page_title = 'Đề Thi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_test') {
        $test_id_to_delete = $_POST['test_id'] ?? null;
        if ($test_id_to_delete) {
            $db->delete('test_questions', 'test_id = ?', [$test_id_to_delete]);
            $db->delete('tests', 'test_id = ?', [$test_id_to_delete]);
            set_message('success', 'Đã xóa bài kiểm tra thành công.');
        }
    } elseif ($action === 'reopen_test') {
        $test_id_to_reopen = $_POST['test_id'] ?? null;
        if ($test_id_to_reopen) {
            $submission = $db->fetch("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id_to_reopen]);
            if ($submission) {
                $db->delete('answers', 'submission_id = ?', [$submission['submission_id']]);
                $db->delete('submissions', 'submission_id = ?', [$submission['submission_id']]);
                set_message('success', 'Đã mở lại bài kiểm tra. Mã đề này bây giờ có thể được sử dụng lại.');
            } else {
                set_message('error', 'Không tìm thấy bài làm nào được liên kết với mã đề này để mở lại.');
            }
        }
    } elseif ($action === 'bulk_delete') {
        $test_ids_to_delete = $_POST['test_ids'] ?? [];
        if (!empty($test_ids_to_delete)) {
            $deleted_count = 0;
            foreach ($test_ids_to_delete as $test_id) {
                $test_id = intval($test_id);
                // Để an toàn, hãy kiểm tra xem bài làm có tồn tại không trước khi xóa
                $submission = $db->fetch("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id]);
                if ($submission) {
                    $db->delete('answers', 'submission_id = ?', [$submission['submission_id']]);
                    $db->delete('submissions', 'submission_id = ?', [$submission['submission_id']]);
                }
                $db->delete('test_questions', 'test_id = ?', [$test_id]);
                $db->delete('tests', 'test_id = ?', [$test_id]);
                $deleted_count++;
            }
            set_message('success', "Đã xóa thành công {$deleted_count} bài kiểm tra.");
        }
    } elseif ($action === 'bulk_reopen') {
        $test_ids_to_reopen = $_POST['test_ids'] ?? [];
        if (!empty($test_ids_to_reopen)) {
            $reopened_count = 0;
            foreach ($test_ids_to_reopen as $test_id) {
                $test_id = intval($test_id);
                $submission = $db->fetch("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id]);
                if ($submission) {
                    $db->delete('answers', 'submission_id = ?', [$submission['submission_id']]);
                    $db->delete('submissions', 'submission_id = ?', [$submission['submission_id']]);
                    $reopened_count++;
                }
            }
            set_message('success', "Đã mở lại thành công {$reopened_count} bài kiểm tra.");
        }
    }

    redirect('/grader/tests');
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
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <div>
        <a href="/admin/tests/bulk-generate" class="gdv-button secondary">Tạo hàng loạt</a>
        <a href="/grader/tests/edit" class="gdv-button">Thêm đề mới</a>
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
                <th style="width: 50px;"><input type="checkbox" id="select-all-tests"></th>
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
                        <td><input type="checkbox" class="test-checkbox" name="test_ids[]" value="<?php echo $test['test_id']; ?>"></td>
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
                                <span class="gdv-action-link" style="color: var(--gdv-text-secondary); cursor: not-allowed;" title="Không thể sửa đề đã được sử dụng. Hãy mở lại đề nếu muốn sửa.">Sửa</span>
                                <form method="POST" action="/grader/tests" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc chắn muốn MỞ LẠI bài kiểm tra này? Hành động này sẽ XÓA bài làm hiện tại của thí sinh.');">
                                    <input type="hidden" name="action" value="reopen_test">
                                    <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                    <button type="submit" class="gdv-action-link" style="border:none; background:none; cursor:pointer; color: var(--gdv-success);">Mở lại</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="/grader/tests" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN bài kiểm tra này?');">
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

<!-- Bulk Actions Bar -->
<div class="gdv-bulk-actions" id="bulk-actions-bar">
    <span id="bulk-actions-count">Đã chọn 0 mục</span>
    <div style="display: flex; gap: 10px;">
        <form id="bulk-reopen-form" method="POST" action="/grader/tests" onsubmit="return confirm('Mở lại các đề đã chọn? Bài làm của thí sinh sẽ bị xóa.');">
            <input type="hidden" name="action" value="bulk_reopen">
        </form>
        <button type="submit" form="bulk-reopen-form" class="gdv-button" style="background-color: var(--gdv-success);">Mở lại đã chọn</button>

        <form id="bulk-delete-form" method="POST" action="/grader/tests" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN các mục đã chọn?');">
            <input type="hidden" name="action" value="bulk_delete">
        </form>
        <button type="submit" form="bulk-delete-form" class="gdv-button danger">Xóa đã chọn</button>
    </div>
    <button type="button" id="bulk-actions-cancel" class="gdv-button secondary">Hủy</button>
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

    // Bulk actions logic
    const selectAllCheckbox = document.getElementById('select-all-tests');
    const itemCheckboxes = document.querySelectorAll('.test-checkbox');
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const bulkActionsCount = document.getElementById('bulk-actions-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');
    const bulkReopenForm = document.getElementById('bulk-reopen-form');
    const cancelBulkActions = document.getElementById('bulk-actions-cancel');

    function updateBulkActionsBar() {
        const selectedCheckboxes = document.querySelectorAll('.test-checkbox:checked');
        const count = selectedCheckboxes.length;

        if (count > 0) {
            bulkActionsCount.textContent = `Đã chọn ${count} mục`;
            bulkActionsBar.classList.add('visible');

            // Cập nhật form xóa hàng loạt
            bulkDeleteForm.innerHTML = '<input type="hidden" name="action" value="bulk_delete">'; // Reset form
            bulkReopenForm.innerHTML = '<input type="hidden" name="action" value="bulk_reopen">'; // Reset form

            selectedCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'test_ids[]';
                input.value = checkbox.value;
                bulkDeleteForm.appendChild(input.cloneNode());
                bulkReopenForm.appendChild(input.cloneNode());
            });
        } else {
            bulkActionsBar.classList.remove('visible');
        }
    }

    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBulkActionsBar();
    });

    itemCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateBulkActionsBar));

    cancelBulkActions.addEventListener('click', function() {
        selectAllCheckbox.checked = false;
        itemCheckboxes.forEach(checkbox => checkbox.checked = false);
        updateBulkActionsBar();
    });
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>