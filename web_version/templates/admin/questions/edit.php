<?php
// web_version/templates/admin/questions/edit.php

$question_id = $_GET['id'] ?? null;
$is_editing = ($question_id !== null);

$page_title = $is_editing ? 'Sửa Câu hỏi' : 'Thêm Câu hỏi mới';

$question_data = ['content' => '', 'type' => 'tu_luan', 'options' => json_encode(['A' => '', 'B' => '', 'C' => '', 'D' => '']), 'correct_answer' => ''];

if ($is_editing) {
    $data = $db->fetch("SELECT * FROM questions WHERE question_id = ?", [$question_id]);
    if (!$data) {
        set_message('error', 'Không tìm thấy câu hỏi.');
        redirect('/admin/questions');
    }
    $question_data = $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $type = $_POST['type'] ?? 'tu_luan';
    $correct_answer = $_POST['correct_answer'] ?? '';
    $options_post = $_POST['options'] ?? [];

    $options_json = null;
    if ($type === 'trac_nghiem') {
        $options_data = [];
        foreach ($options_post as $key => $value) {
            if (!empty(trim($value))) {
                $options_data[strtoupper($key)] = trim($value);
            }
        }
        $options_json = json_encode($options_data);
    }

    $data_to_save = ['content' => $content, 'type' => $type, 'options' => $options_json, 'correct_answer' => $correct_answer];

    if ($is_editing) {
        $db->update('questions', $data_to_save, 'question_id = ?', [$question_id]);
        set_message('success', 'Cập nhật câu hỏi thành công.');
    } else {
        $db->insert('questions', $data_to_save);
        set_message('success', 'Thêm câu hỏi mới thành công.');
    }
    redirect('/admin/questions');
}

$options_array = json_decode($question_data['options'], true) ?: ['A' => '', 'B' => '', 'C' => '', 'D' => ''];

// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1><?php echo $page_title; ?></h1>
    <a href="/admin/questions" class="gdv-button secondary">Quay lại danh sách</a>
</div>

<form id="question-form" method="POST" action="/admin/questions/edit<?php echo $is_editing ? '?id=' . $question_id : ''; ?>" class="gdv-container" style="max-width: 800px; margin: 20px auto; padding: 40px; background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 12px;">
    <p>
        <label for="content">Nội dung câu hỏi</label>
        <textarea name="content" id="content" class="input" rows="6" required><?php echo htmlspecialchars($question_data['content']); ?></textarea>
    </p>
    <p>
        <label for="type">Loại câu hỏi</label>
        <select name="type" id="question-type-select" class="input">
            <option value="tu_luan" <?php echo ($question_data['type'] === 'tu_luan') ? 'selected' : ''; ?>>Tự luận</option>
            <option value="trac_nghiem" <?php echo ($question_data['type'] === 'trac_nghiem') ? 'selected' : ''; ?>>Trắc nghiệm</option>
        </select>
    </p>

    <div id="options-container" style="<?php echo ($question_data['type'] === 'trac_nghiem') ? '' : 'display: none;'; ?>">
        <h4>Các lựa chọn</h4>
        <?php foreach ($options_array as $key => $value): ?>
        <p>
            <label for="option-<?php echo strtolower($key); ?>">Lựa chọn <?php echo $key; ?></label>
            <input type="text" name="options[<?php echo strtolower($key); ?>]" id="option-<?php echo strtolower($key); ?>" class="input" value="<?php echo htmlspecialchars($value); ?>">
        </p>
        <?php endforeach; ?>
    </div>

    <p>
        <label for="correct_answer">Đáp án đúng / Đáp án mẫu</label>
        <input type="text" name="correct_answer" id="correct_answer" class="input" value="<?php echo htmlspecialchars($question_data['correct_answer']); ?>">
        <small style="color: var(--gdv-text-secondary);">Đối với trắc nghiệm, nhập ký tự của đáp án đúng (ví dụ: A). Đối với tự luận, đây là đáp án mẫu cho giám khảo.</small>
    </p>

    <p class="submit" style="margin-top: 30px;"><button type="submit" class="gdv-button" style="font-size: 16px; padding: 12px 24px;">Lưu câu hỏi</button></p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('question-type-select');
    const optionsContainer = document.getElementById('options-container');
    typeSelect.addEventListener('change', function() {
        optionsContainer.style.display = (this.value === 'trac_nghiem') ? 'block' : 'none';
    });
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>