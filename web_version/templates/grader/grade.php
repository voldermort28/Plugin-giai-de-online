<?php
// web_version/templates/grader/grade.php

$submission_id = $_GET['submission_id'] ?? null;
if (!$submission_id) {
    set_message('error', 'Không tìm thấy bài làm.');
    redirect('/grader/dashboard');
}

$view_mode = $_GET['view_mode'] ?? 'regrade';

// Xử lý khi giám khảo nộp form chấm bài
if ($view_mode !== 'review' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade_submission') {
    $submitted_is_correct = $_POST['is_correct'] ?? [];
    $final_score = 0;
    validate_csrf_token();
    // Lấy tất cả các câu trả lời của bài làm này
    $all_answers_in_db = $db->fetchAll("SELECT answer_id, question_id FROM answers WHERE submission_id = ?", [$submission_id]);

    foreach ($all_answers_in_db as $db_answer) {
        $answer_id = $db_answer['answer_id'];
        $question_id = $db_answer['question_id'];
        
        $question_type_row = $db->fetch("SELECT type FROM questions WHERE question_id = ?", [$question_id]);
        $question_type = $question_type_row ? $question_type_row['type'] : null;

        $is_correct_status = 0; // Mặc định là sai
        if ($question_type === 'trac_nghiem') {
            // Đối với câu trắc nghiệm, giá trị is_correct được gửi lên từ một trường ẩn
            // đã được tính toán lại ở phía client để đảm bảo đúng với đáp án mới nhất.
            $is_correct_status = (isset($submitted_is_correct[$answer_id]) && $submitted_is_correct[$answer_id] == '1') ? 1 : 0;
        } else {
            // Đối với tự luận, lấy từ form
            $is_correct_status = (isset($submitted_is_correct[$answer_id]) && $submitted_is_correct[$answer_id] == '1') ? 1 : 0;
        }

        $db->update('answers', ['is_correct' => $is_correct_status], 'answer_id = ?', [$answer_id]);

        if ($is_correct_status == 1) {
            $final_score++;
        }
    }

    // Cập nhật điểm và trạng thái cho bài làm
    $db->update(
        'submissions',
        ['score' => $final_score, 'status' => 'graded'],
        'submission_id = ?',
        [$submission_id]
    );

    set_message('success', 'Chấm bài thi #' . $submission_id . ' thành công!');
    redirect('/grader/dashboard');
}

// Lấy thông tin bài làm để hiển thị
$submission = $db->fetch("SELECT s.*, t.title as test_title FROM submissions s JOIN tests t ON s.test_id = t.test_id WHERE s.submission_id = ?", [$submission_id]);
if (!$submission) {
    set_message('error', 'Không tìm thấy bài làm.');
    redirect('/grader/dashboard');
}

$answers = $db->fetchAll("SELECT a.*, q.content, q.type, q.options, q.correct_answer as model_answer FROM answers a JOIN questions q ON a.question_id = q.question_id WHERE a.submission_id = ? ORDER BY q.question_id", [$submission_id]);

$is_graded = ($submission['status'] === 'graded');
if ($view_mode === 'review') {
    $page_title = 'Xem lại bài thi #' . $submission_id;
} else {
    $page_title = $is_graded ? 'Chấm lại bài thi #' . $submission_id : 'Chấm bài thi #' . $submission_id;
}

include APP_ROOT . '/templates/partials/header.php';
?>

<style>
    .answer-block { margin-bottom: 1rem; padding: 1rem; border-radius: 8px; border: 1px solid var(--gdv-border); }
    .answer-block strong { color: var(--gdv-text-secondary); font-size: 0.875rem; display: block; margin-bottom: 0.5rem; }
    .answer-block.student-answer { background-color: #F9FAFB; }
    .answer-block.model-answer.correct { background-color: var(--gdv-success-bg); border-color: #A7F3D0; }
    .answer-block.model-answer.incorrect { background-color: var(--gdv-error-bg); border-color: #FECACA; }
    .grading-tickbox { margin-top: 1rem; padding: 1rem; background-color: var(--gdv-warning-bg); border: 1px solid #FDE68A; border-radius: 8px; }
</style>

<div class="gdv-header">
    <div>
        <h1><?php echo $page_title; ?></h1>
        <?php if ($view_mode === 'review'): ?>
            <p class="gdv-description"><em>(Bạn đang ở chế độ xem lại. Không thể chỉnh sửa.)</em></p>
        <?php elseif ($is_graded): ?>
            <p class="gdv-description"><em>(Bạn đang ở chế độ chấm lại. Thay đổi sẽ được lưu khi bạn nhấn "Cập nhật điểm".)</em></p>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($view_mode === 'review'): ?>
            <a href="<?php echo htmlspecialchars(add_query_arg('view_mode', 'regrade')); ?>" class="gdv-button">Chấm lại bài này</a>
        <?php endif; ?>
        <a href="/grader/dashboard" class="gdv-button secondary">&larr; Quay lại Dashboard</a>
    </div>
</div>

<div class="gdv-card" style="max-width: 900px;">
    <p><strong>Bài thi:</strong> <?php echo htmlspecialchars($submission['test_title']); ?></p>
    <p><strong>Nhân viên:</strong> <?php echo htmlspecialchars($submission['contestant_name']); ?></p>
    <p><strong>Thời gian nộp:</strong> <?php echo date('d/m/Y, H:i', strtotime($submission['submission_time'])); ?></p>
</div>

<?php if ($view_mode !== 'review'): // Chỉ hiển thị form nếu không phải chế độ xem lại ?>
    <form method="POST" action="/grader/grade?submission_id=<?php echo $submission_id; ?>">
        <input type="hidden" name="action" value="grade_submission">
        <?php csrf_field(); ?>
<?php endif; ?>

    <?php foreach ($answers as $index => $answer): ?>
        <div class="gdv-card" style="max-width: 900px; margin-top: 20px;">
            <h4>Câu <?php echo $index + 1; ?>: <?php echo nl2br(htmlspecialchars($answer['content'])); ?></h4>

            <div class="answer-block student-answer">
                <strong>Câu trả lời của nhân viên:</strong>
                <div>
                    <?php
                    if ($answer['type'] === 'trac_nghiem') {
                        $options = json_decode($answer['options'], true);
                        $student_choice = $answer['user_answer'];
                        echo htmlspecialchars($student_choice . '. ' . ($options[$student_choice] ?? 'Không có lựa chọn'));
                    } else {
                        echo nl2br(htmlspecialchars($answer['user_answer']));
                    }
                    ?>
                </div>
            </div>

            <?php if ($answer['type'] === 'trac_nghiem'): 
                // Sửa lỗi: Luôn so sánh lại với đáp án mới nhất, không phụ thuộc vào DB
                // Điều này đảm bảo khi admin sửa đáp án, kết quả chấm lại sẽ được cập nhật
                $clean_user_answer = preg_replace('/[^A-Z]/', '', strtoupper($answer['user_answer']));
                $clean_correct_answer = preg_replace('/[^A-Z]/', '', strtoupper($answer['model_answer']));
                $is_correct = (!empty($clean_user_answer) && $clean_user_answer === $clean_correct_answer);

            ?>
                <div class="answer-block model-answer <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                    <strong>Đáp án đúng:</strong> <?php echo htmlspecialchars($answer['model_answer']); ?> -
                    <span style="font-weight: 500;"><?php echo $is_correct ? 'Nhân viên trả lời Đúng' : 'Nhân viên trả lời Sai'; ?></span>
                </div>
                <?php if ($view_mode !== 'review'): ?>
                    <!-- Trong chế độ chấm, giá trị is_correct được gửi đi một cách ẩn -->
                    <input type="hidden" name="is_correct[<?php echo $answer['answer_id']; ?>]" value="<?php echo $is_correct ? '1' : '0'; ?>">
                <?php endif; ?>
            <?php else: // tu_luan ?>
                <div class="answer-block model-answer">
                    <strong>Đáp án mẫu:</strong>
                    <div><?php echo nl2br(htmlspecialchars($answer['model_answer'])); ?></div>
                </div>
                <?php if ($view_mode !== 'review'): ?>
                    <div class="grading-tickbox">
                        <label>
                            <input type="checkbox" name="is_correct[<?php echo $answer['answer_id']; ?>]" value="1" <?php echo ($answer['is_correct'] == 1) ? 'checked' : ''; ?>>
                            <strong>Đánh dấu câu trả lời này là Đúng</strong>
                        </label>
                    </div>
                <?php else: 
                    // Trong chế độ xem lại, chỉ hiển thị kết quả
                    $result_text = '<span class="result-incorrect">Nhân viên trả lời Sai</span>';
                    $result_box_class = 'incorrect';
                    if ($answer['is_correct'] == 1) {
                        $result_text = '<span class="result-correct">Nhân viên trả lời Đúng</span>';
                        $result_box_class = 'correct';
                    }
                ?>
                    <div class="answer-block model-answer <?php echo $result_box_class; ?>"><?php echo $result_text; ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php if ($view_mode !== 'review'): ?>
        <div style="max-width: 900px; margin: 20px auto; text-align: right;">
            <button type="submit" class="gdv-button" style="padding: 1rem 2.5rem; font-size: 1rem;">
                <?php echo $is_graded ? 'Cập nhật điểm' : 'Hoàn tất chấm bài'; ?>
            </button>
        </div>
    </form>
<?php endif; ?>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>