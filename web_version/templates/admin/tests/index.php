<?php
// web_version/templates/admin/tests/index.php

$page_title = 'Đề Thi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Sửa lỗi: Gọi đúng hàm verify_csrf_token() và xử lý lỗi
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Lỗi xác thực (CSRF token không hợp lệ). Vui lòng thử lại.');
        redirect('/grader/tests');
    }

    if ($action === 'delete_test') {
        $test_id_to_delete = $_POST['test_id'] ?? null;
        if ($test_id_to_delete) {
            // Sửa lỗi: Xóa TẤT CẢ các bài làm và câu trả lời liên quan
            $submissions = $db->fetchAll("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id_to_delete]);
            if (!empty($submissions)) {
                foreach ($submissions as $sub) {
                    $db->delete('answers', 'submission_id = ?', [$sub['submission_id']]);
                }
                $db->delete('submissions', 'test_id = ?', [$test_id_to_delete]);
            }
            // Xóa các bảng liên kết và đề thi
            $db->delete('test_questions', 'test_id = ?', [$test_id_to_delete]);
            $db->delete('tests', 'test_id = ?', [$test_id_to_delete]);
            set_message('success', 'Đã xóa bài kiểm tra thành công.');
        }
    } elseif ($action === 'reopen_test') {
        $test_id_to_reopen = $_POST['test_id'] ?? null;
        if ($test_id_to_reopen) {
            // Sửa lỗi: Xóa TẤT CẢ các bài làm và câu trả lời liên quan
            $submissions = $db->fetchAll("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id_to_reopen]);
            if (!empty($submissions)) {
                foreach ($submissions as $sub) {
                    $db->delete('answers', 'submission_id = ?', [$sub['submission_id']]);
                }
                $db->delete('submissions', 'test_id = ?', [$test_id_to_reopen]);
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
                // Sửa lỗi: Xóa TẤT CẢ các bài làm và câu trả lời liên quan
                $submissions = $db->fetchAll("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id]);
                if (!empty($submissions)) {
                    foreach ($submissions as $sub) {
                        $db->delete('answers', 'submission_id = ?', [$sub['submission_id']]);
                    }
                    $db->delete('submissions', 'test_id = ?', [$test_id]);
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
                // Sửa lỗi: Xóa TẤT CẢ các bài làm và câu trả lời liên quan
                $submissions = $db->fetchAll("SELECT submission_id FROM submissions WHERE test_id = ?", [$test_id]);
                if (!empty($submissions)) {
                    foreach ($submissions as $sub) {
                        $db->delete('answers', 'submission_id = ?', [$sub['submission_id']]);
                    }
                    $db->delete('submissions', 'test_id = ?', [$test_id]);
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

// Cải tiến truy vấn SQL để rõ ràng và ổn định hơn
$sql = "SELECT t.*, 
               (SELECT COUNT(s.submission_id) FROM submissions s WHERE s.test_id = t.test_id) as submission_count
        FROM tests t";
$params = [];
$where_clauses = [];

if (!empty($filter_contest)) {
    $where_clauses[] = "contest_name = ?";
    $params[] = $filter_contest;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Lọc theo trạng thái sau khi đã group
if ($filter_status === 'ready') {
    $sql .= (empty($where_clauses) ? " WHERE " : " AND ") . " NOT EXISTS (SELECT 1 FROM submissions s WHERE s.test_id = t.test_id)";
} elseif ($filter_status === 'used') {
    $sql .= (empty($where_clauses) ? " WHERE " : " AND ") . " EXISTS (SELECT 1 FROM submissions s WHERE s.test_id = t.test_id)";
}

$sql .= " ORDER BY t.created_at DESC";

$tests = $db->fetchAll($sql, $params);

// Tối ưu hóa: Đếm số lượng cho các tab trong một truy vấn duy nhất
// Cải tiến truy vấn đếm để chính xác hơn
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

<div class="gdv-filter-bar">
    <div class="gdv-filter-bar__actions">
        <a href="/admin/tests/bulk-generate" class="gdv-button secondary">Tạo hàng loạt</a>
        <a href="/grader/tests/edit" class="gdv-button">Thêm đề mới</a>
    </div>
</div>

<div class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap;">
    <div class="gdv-tabs" style="display: flex; gap: 10px;">
        <a href="?status=all&contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button <?php echo $filter_status === 'all' ? '' : 'secondary'; ?>">Tất cả (<?php echo $count_all; ?>)</a>
        <a href="?status=ready&contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button <?php echo $filter_status === 'ready' ? '' : 'secondary'; ?>">Sẵn sàng (<?php echo $count_ready; ?>)</a>
        <a href="?status=used&contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button <?php echo $filter_status === 'used' ? '' : 'secondary'; ?>">Đã dùng (<?php echo $count_used; ?>)</a>
    </div>
    <div style="display: flex; align-items: flex-end; gap: 15px;">
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
        <?php if (!empty($filter_contest)): ?>
            <a href="/admin/tests/bulk-generate?contest=<?php echo urlencode($filter_contest); ?>" class="gdv-button">Thêm đề</a>
        <?php endif; ?>
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
                    $is_used = isset($test['submission_count']) ? $test['submission_count'] > 0 : !is_null($test['submission_id']);
                    $status_class = $is_used ? 'used' : 'ready'; // 'used' or 'ready'
                    $status_text = $is_used ? 'Đã dùng' : 'Sẵn sàng'; // 'Đã dùng' or 'Sẵn sàng'
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
                            <div class="gdv-action-buttons">
                                <?php if (!$is_used): ?>
                                    <a href="/grader/tests/edit?id=<?php echo $test['test_id']; ?>" class="gdv-button small secondary" title="Sửa đề thi">Sửa</a>
                                <?php else: ?>
                                    <!-- Sửa lỗi: Chuyển sang dùng link và JS để submit form, tránh xung đột -->
                                    <a href="#" class="gdv-button small action-button" style="background-color: var(--gdv-success);" 
                                       data-action="reopen_test" data-id="<?php echo $test['test_id']; ?>" 
                                       data-confirm="Bạn có chắc chắn muốn MỞ LẠI bài kiểm tra này? Hành động này sẽ XÓA bài làm hiện tại của thí sinh.">Mở lại</a>
                                <?php endif; ?>
                                <!-- Sửa lỗi: Chuyển sang dùng link và JS để submit form, tránh xung đột -->
                                <a href="#" class="gdv-button small danger action-button" 
                                   data-action="delete_test" data-id="<?php echo $test['test_id']; ?>" 
                                   data-confirm="Bạn có chắc chắn muốn XÓA VĨNH VIỄN bài kiểm tra này?">Xóa</a>
                            </div>
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
        <form id="bulk-reopen-form" method="POST" action="/grader/tests">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="bulk_reopen">
        </form>
        <button type="button" data-form="bulk-reopen-form" class="gdv-button bulk-action-trigger" style="background-color: var(--gdv-success);">Mở lại đã chọn</button>

        <form id="bulk-delete-form" method="POST" action="/grader/tests">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="bulk_delete">
        </form>
        <button type="button" data-form="bulk-delete-form" class="gdv-button danger bulk-action-trigger">Xóa đã chọn</button>
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

            // Sửa lỗi: Reset form mà không xóa mất csrf_token
            // Xóa các input test_ids[] cũ
            bulkDeleteForm.querySelectorAll('input[name="test_ids[]"]').forEach(input => input.remove());
            bulkReopenForm.querySelectorAll('input[name="test_ids[]"]').forEach(input => input.remove());

            selectedCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'test_ids[]';
                input.value = checkbox.value;
                
                // Thêm input mới vào cả hai form
                const inputForDelete = input.cloneNode();
                const inputForReopen = input.cloneNode();
                bulkDeleteForm.appendChild(inputForDelete);
                bulkReopenForm.appendChild(inputForReopen);
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

    // Sửa lỗi: Xử lý nút Xóa/Mở lại bằng JS để đảm bảo form được submit đúng cách
    const actionButtons = document.querySelectorAll('.action-button');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const testId = this.dataset.id;
            const confirmationMessage = this.dataset.confirm;

            if (confirm(confirmationMessage)) {
                // Tạo một form động để submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/grader/tests';

                const csrfToken = document.querySelector('input[name="csrf_token"]').value;

                const inputs = {
                    'csrf_token': csrfToken,
                    'action': action,
                    'test_id': testId
                };

                for (const name in inputs) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = inputs[name];
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Sửa lỗi: Kích hoạt submit form hàng loạt bằng JS
    document.querySelectorAll('.bulk-action-trigger').forEach(button => {
        button.addEventListener('click', function() {
            const formId = this.getAttribute('data-form');
            const form = document.getElementById(formId);
            if (!form) return;

            const selectedCount = document.querySelectorAll('.test-checkbox:checked').length;
            if (selectedCount === 0) {
                alert('Vui lòng chọn ít nhất một mục.');
                return;
            }

            let confirmationMessage = '';
            if (formId === 'bulk-delete-form') {
                confirmationMessage = `Bạn có chắc chắn muốn XÓA VĨNH VIỄN ${selectedCount} mục đã chọn?`;
            } else if (formId === 'bulk-reopen-form') {
                confirmationMessage = `Bạn có chắc chắn muốn MỞ LẠI ${selectedCount} đề đã chọn? Bài làm của thí sinh sẽ bị xóa.`;
            }

            if (confirmationMessage && confirm(confirmationMessage)) {
                form.submit();
            }
        });
    });
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>