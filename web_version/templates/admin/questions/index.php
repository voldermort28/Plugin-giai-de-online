<?php
// web_version/templates/admin/questions/index.php

$page_title = 'Quản lý Câu hỏi';
include APP_ROOT . '/templates/partials/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    $question_id_to_delete = $_POST['question_id'] ?? null;
    if ($question_id_to_delete) {
        $db->delete('test_questions', 'question_id = ?', [$question_id_to_delete]);
        $db->delete('questions', 'question_id = ?', [$question_id_to_delete]);
        set_message('success', 'Đã xóa câu hỏi thành công.');
        redirect('/admin/questions');
    }
}

$questions = $db->fetchAll("SELECT * FROM questions ORDER BY created_at DESC");
?>

<div class="gdv-header">
    <h1>Ngân hàng câu hỏi</h1>
    <a href="/admin/questions/edit" class="gdv-button">Thêm câu hỏi mới</a>
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
                <tr><td colspan="5" style="text-align: center;">Chưa có câu hỏi nào.</td></tr>
            <?php else: ?>
                <?php foreach ($questions as $question): ?>
                    <tr>
                        <td><?php echo $question['question_id']; ?></td>
                        <td style="max-width: 400px; white-space: normal;"><?php echo htmlspecialchars(mb_substr($question['content'], 0, 150)) . '...'; ?></td>
                        <td><span class="gdv-status"><?php echo htmlspecialchars($question['type']); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($question['created_at'])); ?></td>
                        <td>
                            <a href="/admin/questions/edit?id=<?php echo $question['question_id']; ?>" class="gdv-action-link">Sửa</a>
                            <form method="POST" action="/admin/questions" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">
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

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>