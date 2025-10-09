<?php
// web_version/templates/admin/questions/index.php

$page_title = 'Quản lý Câu hỏi';
$page_title = 'Câu Hỏi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Lỗi xác thực (CSRF token không hợp lệ). Vui lòng thử lại.');
        redirect('/admin/questions');
    }
    $question_id_to_delete = $_POST['question_id'] ?? null;
    if ($question_id_to_delete) {
        $db->delete('test_questions', 'question_id = ?', [$question_id_to_delete]);
        $db->delete('questions', 'question_id = ?', [$question_id_to_delete]);
        set_message('success', 'Đã xóa câu hỏi thành công.');
        redirect('/admin/questions');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete_questions') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Lỗi xác thực (CSRF token không hợp lệ). Vui lòng thử lại.');
        redirect('/admin/questions');
    }
    $question_ids = $_POST['question_ids'] ?? [];
    foreach ($question_ids as $q_id) {
        $db->delete('test_questions', 'question_id = ?', [$q_id]);
        $db->delete('questions', 'question_id = ?', [$q_id]);
    }
    set_message('success', 'Đã xóa thành công ' . count($question_ids) . ' câu hỏi.');
    redirect('/admin/questions');
}

// Lấy các tham số tìm kiếm và lọc từ URL
$search_term = $_GET['search_term'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 15;
$offset = ($current_page - 1) * $items_per_page;

// Xây dựng truy vấn động
$count_sql = "SELECT COUNT(*) FROM questions";
$sql = "SELECT * FROM questions";
$params = [];
$where_clauses = [];

if (!empty($search_term)) {
    $where_clauses[] = "content LIKE ?";
    $params[] = '%' . $search_term . '%';
}

if (!empty($filter_type)) {
    $where_clauses[] = "type = ?";
    $params[] = $filter_type;
}

if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
    $sql .= $where_sql;
    $count_sql .= $where_sql;
}

// Lấy tổng số bản ghi để phân trang
$count_result = $db->fetch($count_sql, $params);
// Lấy giá trị đầu tiên từ mảng kết quả (chính là giá trị COUNT(*))
$total_items = $count_result ? array_values($count_result)[0] : 0;
$total_pages = ceil($total_items / $items_per_page);

// Chèn trực tiếp giá trị LIMIT và OFFSET vào SQL vì PDO có thể không hỗ trợ bind chúng.
// Các biến này đã được ép kiểu (int) nên an toàn.
$sql .= " ORDER BY created_at DESC LIMIT " . intval($items_per_page) . " OFFSET " . intval($offset);
$questions = $db->fetchAll($sql, $params); // Chỉ truyền các tham số của WHERE

// Tối ưu hóa: Lấy thông tin sử dụng câu hỏi trong một truy vấn
$tests_by_question = [];
if (!empty($questions)) {
    $question_ids = array_column($questions, 'question_id');
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));

    $test_associations = $db->fetchAll(
        "SELECT tq.question_id, t.title 
         FROM test_questions tq 
         JOIN tests t ON tq.test_id = t.test_id 
         WHERE tq.question_id IN ($placeholders)",
        $question_ids
    );

    foreach ($test_associations as $assoc) {
        $tests_by_question[$assoc['question_id']][] = $assoc['title'];
    }
}
// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-filter-bar">
    <div class="gdv-filter-bar__actions">
        <a href="/admin/import" class="gdv-button secondary">Nhập từ file</a>
        <a href="/admin/questions/edit" class="gdv-button">Thêm câu hỏi mới</a>
    </div>
</div>

<form method="GET" action="/admin/questions" class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px;">
    <div style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex-grow: 1;">
                <label for="search_term">Tìm kiếm theo nội dung</label>
                <input type="search" id="search_term" name="search_term" class="input" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Nhập từ khóa...">
            </div>
            <div style="flex-basis: 200px;">
                <label for="filter_type">Lọc theo loại</label>
                <select id="filter_type" name="filter_type" class="input">
                    <option value="">Tất cả các loại</option>
                    <option value="trac_nghiem" <?php echo ($filter_type === 'trac_nghiem') ? 'selected' : ''; ?>>Trắc nghiệm</option>
                    <option value="tu_luan" <?php echo ($filter_type === 'tu_luan') ? 'selected' : ''; ?>>Tự luận</option>
                </select>
            </div>
            <button type="submit" class="gdv-button">Lọc</button>
    </div>
</form>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th style="width: 50px;"><input type="checkbox" id="select-all-questions"></th>
                <th>ID</th>
                <th>Nội dung</th>
                <th>Loại</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($questions)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 2rem;">Không tìm thấy câu hỏi nào phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($questions as $question): 
                    // Chuyển đổi tên loại câu hỏi để hiển thị
                    $display_type = 'Không xác định';
                    if ($question['type'] === 'trac_nghiem') $display_type = 'Trắc nghiệm';
                    if ($question['type'] === 'tu_luan') $display_type = 'Tự luận';
                    $full_content = htmlspecialchars(strip_tags($question['content']));
                    $short_content = htmlspecialchars($question['content']);
                    // Chỉ cắt ngắn và thêm "..." nếu nội dung dài hơn 80 ký tự
                    if (mb_strlen($question['content']) > 80) {
                        $short_content = htmlspecialchars(mb_substr($question['content'], 0, 80)) . '...';
                    }

                    $used_in_tests = $tests_by_question[$question['question_id']] ?? [];
                    $usage_count = count($used_in_tests);
                ?>
                    <tr>
                        <td><input type="checkbox" class="question-checkbox" name="question_ids[]" value="<?php echo $question['question_id']; ?>"></td>
                        <td><?php echo $question['question_id']; ?></td>
                        <td title="<?php echo $full_content; // Tooltip luôn hiển thị nội dung đầy đủ ?>">
                            <strong><?php echo $short_content; ?></strong>
                        </td>
                        <td><span class="gdv-status <?php echo htmlspecialchars($question['type']); ?>"><?php echo htmlspecialchars($display_type); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($question['created_at'])); ?></td>
                        <td>
                            <a href="/admin/questions/edit?id=<?php echo $question['question_id']; ?>" class="gdv-action-link">Sửa</a>
                            <?php if ($usage_count > 0): ?>
                                <a href="#" class="gdv-action-link view-usage" data-question-content="<?php echo htmlspecialchars(mb_substr($question['content'], 0, 80)); ?>..." data-tests='<?php echo json_encode($used_in_tests); ?>' style="margin-left: 1rem;">Sử dụng (<?php echo $usage_count; ?>)</a>
                            <?php endif; ?>
                            <form method="POST" action="/admin/questions" style="display:inline-block; margin-left: 1rem;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_question"><input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
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
        <form id="bulk-delete-form" method="POST" action="/admin/questions" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN các câu hỏi đã chọn?');">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="bulk_delete_questions">
            <!-- Hidden inputs for IDs will be added by JS -->
        </form>
        <button type="submit" form="bulk-delete-form" class="gdv-button danger">Xóa đã chọn</button>
    </div>
    <button type="button" id="bulk-actions-cancel" class="gdv-button secondary">Hủy</button>
</div>

<!-- Modal for viewing question usage -->
<div id="usage-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10001; justify-content: center; align-items: center;">
    <div id="usage-modal-content" class="gdv-card" style="width: 90%; max-width: 600px; margin: 0;">
        <h3 id="usage-modal-title" style="margin-top: 0;">Câu hỏi đang được sử dụng trong:</h3>
        <p id="usage-modal-question-content" style="font-style: italic; color: var(--gdv-text-secondary);"></p>
        <ul id="usage-modal-list" style="max-height: 300px; overflow-y: auto; padding-left: 20px;">
            <!-- Test list will be populated by JS -->
        </ul>
        <div style="text-align: right; margin-top: 20px;">
            <button id="usage-modal-close" class="gdv-button secondary">Đóng</button>
        </div>
    </div>
</div>

<div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
    <p>Hiển thị <?php echo count($questions); ?> trên tổng số <?php echo $total_items; ?> câu hỏi.</p>
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            // Giữ lại các tham số tìm kiếm khi chuyển trang
            $query_params = http_build_query(['search_term' => $search_term, 'filter_type' => $filter_type]);
            ?>
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>&<?php echo $query_params; ?>" class="gdv-button secondary">&laquo; Trang trước</a>
            <?php endif; ?>

            <span style="margin: 0 10px;">Trang <?php echo $current_page; ?> / <?php echo $total_pages; ?></span>

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>&<?php echo $query_params; ?>" class="gdv-button secondary">Trang sau &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk actions logic
    const selectAllCheckbox = document.getElementById('select-all-questions');
    const itemCheckboxes = document.querySelectorAll('.question-checkbox');
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const bulkActionsCount = document.getElementById('bulk-actions-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');
    const cancelBulkActions = document.getElementById('bulk-actions-cancel');

    function updateBulkActionsBar() {
        const selectedCheckboxes = document.querySelectorAll('.question-checkbox:checked');
        const count = selectedCheckboxes.length;

        if (count > 0) {
            bulkActionsCount.textContent = `Đã chọn ${count} mục`;
            bulkActionsBar.classList.add('visible');

            // Clear previous hidden inputs
            bulkDeleteForm.querySelectorAll('input[name="question_ids[]"]').forEach(input => input.remove());

            // Add new hidden inputs for selected items
            selectedCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'question_ids[]';
                input.value = checkbox.value;
                bulkDeleteForm.appendChild(input);
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
    cancelBulkActions.addEventListener('click', () => itemCheckboxes.forEach(cb => cb.click())); // Simulate clicks to trigger update

    // Usage modal logic
    const usageModalOverlay = document.getElementById('usage-modal-overlay');
    const usageModalTitle = document.getElementById('usage-modal-title');
    const usageModalQuestionContent = document.getElementById('usage-modal-question-content');
    const usageModalList = document.getElementById('usage-modal-list');
    const usageModalClose = document.getElementById('usage-modal-close');

    document.querySelectorAll('.view-usage').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tests = JSON.parse(this.getAttribute('data-tests'));
            const questionContent = this.getAttribute('data-question-content');

            usageModalQuestionContent.textContent = `Nội dung: "${questionContent}"`;
            usageModalList.innerHTML = ''; // Clear previous list

            if (tests.length > 0) {
                tests.forEach(testTitle => {
                    const li = document.createElement('li');
                    li.textContent = testTitle;
                    usageModalList.appendChild(li);
                });
            }
            usageModalOverlay.style.display = 'flex';
        });
    });

    usageModalClose.addEventListener('click', () => usageModalOverlay.style.display = 'none');
    usageModalOverlay.addEventListener('click', (e) => { if (e.target === usageModalOverlay) usageModalOverlay.style.display = 'none'; });
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>