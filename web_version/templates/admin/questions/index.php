<?php
// web_version/templates/admin/questions/index.php

$page_title = 'Quản lý Câu hỏi';
$page_title = 'Câu Hỏi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    $question_id_to_delete = $_POST['question_id'] ?? null;
    if ($question_id_to_delete) {
        $db->delete('test_questions', 'question_id = ?', [$question_id_to_delete]);
        $db->delete('questions', 'question_id = ?', [$question_id_to_delete]);
        set_message('success', 'Đã xóa câu hỏi thành công.');
        redirect('/admin/questions');
    }
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

// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1><?php echo $page_title; ?></h1>
    <div>
        <a href="/admin/import" class="gdv-button secondary">Nhập từ file</a>
        <a href="/admin/questions/edit" class="gdv-button">Thêm câu hỏi mới</a>
    </div>
</div>

<div class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px;">
    <form method="GET" action="/admin/questions" style="display: flex; gap: 15px; align-items: flex-end;">
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
    </form>
</div>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nội dung</th>
                <th>Loại</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($questions)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 2rem;">Không tìm thấy câu hỏi nào phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($questions as $question): ?>
                    <tr>
                        <td><?php echo $question['question_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars(mb_substr($question['content'], 0, 100)); ?>...</strong></td>
                        <td><span class="gdv-status <?php echo htmlspecialchars($question['type']); ?>"><?php echo htmlspecialchars($question['type']); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($question['created_at'])); ?></td>
                        <td>
                            <a href="/admin/questions/edit?id=<?php echo $question['question_id']; ?>" class="gdv-action-link">Sửa</a>
                            <form method="POST" action="/admin/questions" style="display:inline-block; margin-left: 1rem;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">
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

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>