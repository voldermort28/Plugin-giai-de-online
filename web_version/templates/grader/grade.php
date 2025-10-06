<?php
// web_version/templates/grader/grade.php

$page_title = 'Chấm bài chi tiết';
include APP_ROOT . '/templates/partials/header.php';

$submission_id = $_GET['id'] ?? null;

if (!$submission_id) {
    set_message('error', 'Không tìm thấy ID bài nộp.');
    redirect('/grader/dashboard');
}

$submission = $db->fetch("
    SELECT s.*, t.title as test_title, t.ma_de 
    FROM submissions s 
    JOIN tests t ON s.test_id = t.test_id 
    WHERE s.submission_id = ?
", [$submission_id]);

if (!$submission) {
    set_message('error', 'Bài nộp không tồn tại.');
    redirect('/grader/dashboard');
}

$questions = $db->fetchAll("
    SELECT q.* FROM questions q
    JOIN test_questions tq ON q.question_id = tq.question_id
    WHERE tq.test_id = ?
    ORDER BY q.question_id ASC
", [$submission['test_id']]);

$answers_raw = $db->fetchAll("SELECT * FROM answers WHERE submission_id = ?", [$submission_id]);
$answers = [];
foreach ($answers_raw as $answer) {
    $answers[$answer['question_id']] = $answer;
}

$grader = $auth->user();
?>

<div class="gdv-header">
    <div>
        <h1>Chấm bài: <?php echo htmlspecialchars($submission['test_title']); ?></h1>
        <p class="gdv-description">
            Thí sinh: <strong><?php echo htmlspecialchars($submission['contestant_name']); ?></strong> | 
            SĐT: <strong><?php echo htmlspecialchars($submission['contestant_phone']); ?></strong> | 
            Nộp lúc: <strong><?php echo date('d/m/Y H:i', strtotime($submission['submission_time'])); ?></strong>
        </p>
    </div>
    <a href="/grader/dashboard" class="gdv-button secondary">Quay lại Dashboard</a>
</div>

<form id="grading-form" method="POST" action="/api/ajax">
    <input type="hidden" name="action" value="save_grade">
    <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
    <input type="hidden" name="grader_id" value="<?php echo $grader['user_id']; ?>">

    <?php foreach ($questions as $index => $question): ?>
        <div class="question-item" style="margin-bottom: 20px; padding: 20px; border: 1px solid var(--gdv-border); border-radius: 12px; background: #fff;">
            <h3>Câu <?php echo $index + 1; ?>:</h3>
            <p style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?php echo nl2br(htmlspecialchars($question['content'])); ?></p>
            
            <?php if ($question['correct_answer']): ?>
                <p><strong>Đáp án mẫu:</strong> <span style="color: var(--gdv-status-ready-text);"><?php echo htmlspecialchars($question['correct_answer']); ?></span></p>
            <?php endif; ?>

            <hr style="margin: 15px 0;">

            <h4>Bài làm của thí sinh:</h4>
            <div style="background: var(--gdv-status-submitted-bg); color: #856404; padding: 15px; border-radius: 5px; min-height: 50px;">
                <?php
                    $student_answer = $answers[$question['question_id']]['answer_content'] ?? '<em>Không trả lời</em>';
                    echo nl2br(htmlspecialchars($student_answer));
                ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="grading-summary" style="padding: 20px; background: var(--gdv-white); border-radius: 12px; margin-top: 30px; border: 1px solid var(--gdv-border);">
        <h3>Tổng kết điểm</h3>
        <p>
            <label for="score"><strong>Nhập tổng điểm:</strong></label>
            <input type="text" name="score" id="score" value="<?php echo htmlspecialchars($submission['score'] ?? ''); ?>" class="input" style="max-width: 200px; margin-top: 5px;" required>
        </p>
        <button type="submit" class="gdv-button" style="font-size: 16px; padding: 12px 24px;">Lưu điểm</button>
    </div>
</form>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>