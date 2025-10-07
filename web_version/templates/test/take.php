<?php
// web_version/templates/test/take.php

// 1. Lấy và xác thực thông tin đầu vào
$ma_de = $_GET['ma_de'] ?? '';
$phone_number = $_GET['phone_number'] ?? '';
$submitter_name = $_GET['submitter_name'] ?? '';

if (empty($ma_de) || empty($phone_number) || empty($submitter_name)) {
    redirect('/'); // Chuyển hướng nếu thiếu thông tin
}

// 2. Tìm bài thi dựa trên mã đề
$test = $db->fetch("SELECT * FROM tests WHERE ma_de = ?", [$ma_de]);
if (!$test) {
    redirect('/?error=invalid_code&ma_de=' . urlencode($ma_de)); // Chuyển hướng nếu mã đề không hợp lệ
}

// 3. Tạo một lượt làm bài mới
$submission_id = $db->insert('submissions', [
    'test_id' => $test['test_id'],
    'contestant_name' => $submitter_name,
    'contestant_phone' => $phone_number,
    'status' => 'in_progress',
    'submission_time' => date('Y-m-d H:i:s')
]);

// 4. Lấy danh sách câu hỏi cho bài thi
$questions = $db->fetchAll("
    SELECT q.* FROM questions q
    JOIN test_questions tq ON q.question_id = tq.question_id
    WHERE tq.test_id = ?
", [$test['test_id']]);

$page_title = 'Làm bài thi: ' . htmlspecialchars($test['title']);
include APP_ROOT . '/templates/partials/header.php';
?>

<style>
    /* Custom styles for the test-taking page */
    .test-question-card {
        background: var(--gdv-white);
        border: 1px solid var(--gdv-border);
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
    }
    .test-question-card h4 {
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
    }
    .test-options label {
        display: block;
        background: #F9FAFB;
        border: 1px solid var(--gdv-border);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        cursor: pointer;
        transition: border-color 0.2s, background-color 0.2s;
    }
    .test-options label:hover {
        background-color: #F3F4F6;
    }
    .test-options input[type="radio"] {
        margin-right: 0.75rem;
    }
</style>

<div id="lb-test-timer" data-time="<?php echo intval($test['time_limit']); ?>"></div>

<div class="lb-take-test-container">
    <div class="gdv-header">
        <h1><?php echo htmlspecialchars($test['title']); ?></h1>
        <p class="gdv-description">Thí sinh: <?php echo htmlspecialchars($submitter_name); ?></p>
    </div>

    <form id="test-submission-form">
        <input type="hidden" name="action" value="submit_test">
        <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">

        <?php foreach ($questions as $index => $question): ?>
            <div class="test-question-card">
                <h4>Câu <?php echo $index + 1; ?>: <?php echo nl2br(htmlspecialchars($question['content'])); ?></h4>
                <?php if ($question['type'] === 'trac_nghiem'): 
                    $options = json_decode($question['options'], true);
                ?>
                    <div class="test-options">
                        <?php foreach ($options as $key => $value): ?>
                            <label><input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo $key; ?>"> <?php echo htmlspecialchars($value); ?></label>
                        <?php endforeach; ?>
                    </div>
                <?php else: // tu_luan ?>
                    <textarea name="answers[<?php echo $question['question_id']; ?>]" class="input" rows="5" placeholder="Nhập câu trả lời của bạn..."></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <button type="submit" class="gdv-button" style="padding: 1rem 2.5rem; font-size: 1rem;">Nộp bài</button>
        </div>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>