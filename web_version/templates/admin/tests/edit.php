<?php
// web_version/templates/admin/tests/edit.php

$test_id = $_GET['id'] ?? null;
$is_editing = ($test_id !== null);

$page_title = $is_editing ? 'Sửa Bài kiểm tra' : 'Thêm Bài kiểm tra mới';

$test_data = ['title' => '', 'ma_de' => '', 'time_limit' => 60];

if ($is_editing) {
    $test_data = $db->fetch("SELECT * FROM tests WHERE test_id = ?", [$test_id]);
    if (!$test_data) {
        set_message('error', 'Không tìm thấy bài kiểm tra.');
        redirect('/grader/tests');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $ma_de = $_POST['ma_de'] ?? '';
    $time_limit = $_POST['time_limit'] ?? 60;
    $selected_questions = $_POST['questions'] ?? [];

    $data_to_save = ['title' => $title, 'ma_de' => $ma_de, 'time_limit' => $time_limit];

    try {
        if ($is_editing) {
            $db->update('tests', $data_to_save, 'test_id = ?', [$test_id]);
            $current_test_id = $test_id;
        } else {
            $current_test_id = $db->insert('tests', $data_to_save);
        }

        if ($current_test_id) {
            $db->delete('test_questions', 'test_id = ?', [$current_test_id]);
            foreach ($selected_questions as $q_id) {
                $db->insert('test_questions', ['test_id' => $current_test_id, 'question_id' => $q_id]);
            }
        }
        set_message('success', $is_editing ? 'Cập nhật bài kiểm tra thành công.' : 'Thêm bài kiểm tra mới thành công.');
        redirect('/grader/tests');
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            set_message('error', 'Lỗi: Mã đề "' . htmlspecialchars($ma_de) . '" đã tồn tại.');
        } else {
            set_message('error', 'Đã xảy ra lỗi khi lưu: ' . $e->getMessage());
        }
        $test_data = $data_to_save;
    }
}

$all_questions = $db->fetchAll("SELECT question_id, content, type FROM questions ORDER BY created_at DESC");
$assigned_question_ids = [];
if ($is_editing) {
    $assigned_questions_raw = $db->fetchAll("SELECT question_id FROM test_questions WHERE test_id = ?", [$test_id]);
    $assigned_question_ids = array_column($assigned_questions_raw, 'question_id');
}

// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1><?php echo $page_title; ?></h1>
    <a href="/grader/tests" class="gdv-button secondary">Quay lại danh sách</a>
</div>

<form method="POST" action="/grader/tests/edit<?php echo $is_editing ? '?id=' . $test_id : ''; ?>" class="gdv-card" style="max-width: 800px;">
    <p>
        <label for="title">Tiêu đề bài kiểm tra</label>
        <input type="text" name="title" id="title" class="input" value="<?php echo htmlspecialchars($test_data['title']); ?>" required>
    </p>
    <p>
        <label for="ma_de">Mã đề (duy nhất)</label>
        <input type="text" name="ma_de" id="ma_de" class="input" value="<?php echo htmlspecialchars($test_data['ma_de']); ?>" required>
    </p>
    <p>
        <label for="time_limit">Thời gian làm bài (phút)</label>
        <input type="number" name="time_limit" id="time_limit" class="input" value="<?php echo htmlspecialchars($test_data['time_limit']); ?>" required>
    </p>

    <hr style="margin: 30px 0;">

    <h3>Quản lý câu hỏi</h3>
    <div class="question-selector" style="max-height: 400px; overflow-y: auto; border: 1px solid var(--gdv-border); padding: 15px; border-radius: 8px;">
        <?php if (empty($all_questions)): ?>
            <p>Chưa có câu hỏi nào trong ngân hàng đề. <a href="/admin/questions/edit">Thêm câu hỏi mới</a>.</p>
        <?php else: ?>
            <table class="gdv-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Chọn</th>
                        <th>Nội dung câu hỏi</th>
                        <th style="width: 120px;">Loại</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_questions as $question): ?>
                        <tr>
                            <td><input type="checkbox" name="questions[]" value="<?php echo $question['question_id']; ?>" <?php echo in_array($question['question_id'], $assigned_question_ids) ? 'checked' : ''; ?>></td>
                            <td><?php echo htmlspecialchars(mb_substr($question['content'], 0, 100)); ?>...</td>
                            <td><span class="gdv-status"><?php echo htmlspecialchars($question['type']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <p class="submit" style="margin-top: 30px;"><button type="submit" class="gdv-button" style="font-size: 16px; padding: 12px 24px;">Lưu bài kiểm tra</button></p>
</form>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>