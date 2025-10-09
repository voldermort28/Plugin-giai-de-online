<?php
// web_version/templates/admin/tests/bulk-generate.php

$page_title = 'Tạo Đề thi Hàng loạt';

// Xử lý form khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_generate_submit'])) {
    // Lấy và làm sạch dữ liệu
    $contest_name_select = $_POST['contest_name_select'] ?? '';
    if ($contest_name_select === '__new__') {
        $contest_name = trim(htmlspecialchars($_POST['contest_name_new'], ENT_QUOTES, 'UTF-8'));
    } else {
        $contest_name = trim(htmlspecialchars($contest_name_select, ENT_QUOTES, 'UTF-8'));
    }

    $num_tests = intval($_POST['num_tests']);
    $num_questions = intval($_POST['num_questions']);
    $time_limit = intval($_POST['time_limit']);
    $question_pool_ids = $_POST['questions'] ?? [];

    // Validation
    if (empty($contest_name) || $num_tests <= 0 || $num_questions <= 0 || empty($question_pool_ids) || $num_questions > count($question_pool_ids)) {
        set_message('error', 'Lỗi: Vui lòng điền đầy đủ thông tin. Số câu hỏi mỗi đề phải nhỏ hơn hoặc bằng tổng số câu trong ngân hàng đã chọn.');
    } else {
        $generated_count = 0;
        $batch_id = uniqid(); // ID duy nhất cho đợt tạo đề này
        
        // Lấy số thứ tự đề thi lớn nhất hiện có của cuộc thi để đánh số tiếp
        $last_test_number_row = $db->fetch("SELECT COUNT(test_id) as count FROM tests WHERE contest_name = ?", [$contest_name]);
        $start_index = ($last_test_number_row && $last_test_number_row['count'] > 0) ? intval($last_test_number_row['count']) : 0;

        for ($i = 0; $i < $num_tests; $i++) {
            shuffle($question_pool_ids);
            $random_questions_for_test = array_slice($question_pool_ids, 0, $num_questions);

            $test_title = $contest_name . ' - Đề #' . ($start_index + $i + 1);
            $ma_de = strtoupper(substr(md5($batch_id . '-' . $i), 0, 8));

            $test_data = [
                'title' => $test_title,
                'contest_name' => $contest_name,
                'ma_de' => $ma_de,
                'time_limit' => $time_limit
            ];

            try {
                $new_test_id = $db->insert('tests', $test_data);
                if ($new_test_id) {
                    // Gán câu hỏi cho đề thi vừa tạo
                    foreach ($random_questions_for_test as $q_id) {
                        $db->insert('test_questions', ['test_id' => $new_test_id, 'question_id' => $q_id]);
                    }
                    $generated_count++;
                }
            } catch (Exception $e) {
                // Bỏ qua nếu có lỗi (ví dụ mã đề trùng) và tiếp tục vòng lặp
                continue;
            }
        }
        set_message('success', "Hoàn tất! Đã tạo thành công {$generated_count} / {$num_tests} bài kiểm tra cho cuộc thi '{$contest_name}'.");
        redirect('/grader/tests');
    }
}

$existing_contests = $db->fetchAll("SELECT DISTINCT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY contest_name");
$preselected_contest = $_GET['contest'] ?? '';

$all_questions = $db->fetchAll("SELECT question_id, content, type FROM questions ORDER BY created_at DESC");

// Include header sau khi tất cả logic đã được xử lý
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1><?php echo $page_title; ?></h1>
    <a href="/grader/tests" class="gdv-button secondary">Quay lại danh sách</a>
</div>

<form method="POST" action="/admin/tests/bulk-generate" class="gdv-card" style="max-width: 800px;">
    <p class="gdv-description">Sử dụng công cụ này để tạo hàng loạt các đề thi riêng lẻ từ một ngân hàng câu hỏi do bạn chỉ định.</p>
    
    <div class="form-group">
        <label for="contest_name_select">Chọn Cuộc thi hoặc Tạo mới</label>
        <select id="contest_name_select" name="contest_name_select" class="input" required>
            <option value="">-- Vui lòng chọn --</option>
            <?php foreach ($existing_contests as $contest): ?>
                <option value="<?php echo htmlspecialchars($contest['contest_name']); ?>" <?php echo ($preselected_contest === $contest['contest_name']) ? 'selected' : ''; ?>>
                    Thêm vào: <?php echo htmlspecialchars($contest['contest_name']); ?>
                </option>
            <?php endforeach; ?>
            <option value="__new__">Tạo cuộc thi mới...</option>
        </select>
    </div>
    <div class="form-group" id="new-contest-group" style="display: none;">
        <label for="contest_name_new">Tên Cuộc thi mới</label>
        <input type="text" id="contest_name_new" name="contest_name_new" class="input">
    </div>
    <div class="form-group">
        <label for="num_tests">Số lượng đề thi muốn tạo</label>
        <input type="number" id="num_tests" name="num_tests" class="input" required style="max-width: 200px;">
    </div>
    <div class="form-group">
        <label for="num_questions">Số lượng câu hỏi trong 1 đề</label>
        <input type="number" id="num_questions" name="num_questions" class="input" required style="max-width: 200px;">
    </div>
    <div class="form-group">
        <label for="time_limit">Thời gian làm bài (phút)</label>
        <input type="number" id="time_limit" name="time_limit" class="input" value="60" required style="max-width: 200px;">
    </div>

    <hr style="margin: 30px 0;">

    <h3>Ngân hàng câu hỏi</h3>
    <p class="gdv-description">Chọn tất cả các câu hỏi bạn muốn đưa vào vòng quay ngẫu nhiên.</p>
    
    <div class="question-selector" style="max-height: 400px; overflow-y: auto; border: 1px solid var(--gdv-border); padding: 15px; border-radius: 8px;">
        <?php if (empty($all_questions)): ?>
            <p>Chưa có câu hỏi nào trong ngân hàng đề. <a href="/admin/questions/edit" class="gdv-action-link">Thêm câu hỏi mới</a>.</p>
        <?php else: ?>
            <table class="gdv-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="select-all-questions"></th>
                        <th>Nội dung câu hỏi</th>
                        <th style="width: 120px;">Loại</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_questions as $question): ?>
                        <tr>
                            <td><input type="checkbox" name="questions[]" value="<?php echo $question['question_id']; ?>" class="question-checkbox"></td>
                            <td><?php echo htmlspecialchars(mb_substr($question['content'], 0, 100)); ?>...</td>
                            <td><span class="gdv-status <?php echo htmlspecialchars($question['type']); ?>"><?php echo htmlspecialchars($question['type']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <p class="submit" style="margin-top: 30px; text-align: right;"><button type="submit" name="bulk_generate_submit" class="gdv-button" style="font-size: 16px; padding: 12px 24px;">Bắt đầu tạo</button></p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('select-all-questions').addEventListener('change', function(e) {
        document.querySelectorAll('.question-checkbox').forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    const contestSelect = document.getElementById('contest_name_select');
    const newContestGroup = document.getElementById('new-contest-group');
    const newContestInput = document.getElementById('contest_name_new');

    function toggleNewContestInput() {
        if (contestSelect.value === '__new__') {
            newContestGroup.style.display = 'block';
            newContestInput.required = true;
        } else {
            newContestGroup.style.display = 'none';
            newContestInput.required = false;
        }
    }
    contestSelect.addEventListener('change', toggleNewContestInput);
    toggleNewContestInput(); // Run on page load
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>