<?php
// web_version/templates/test/take.php

$page_title = 'Làm bài kiểm tra';
include APP_ROOT . '/templates/partials/header.php';

$ma_de = $_GET['ma_de'] ?? '';
$phone_number = $_GET['phone_number'] ?? '';
$submitter_name = $_GET['submitter_name'] ?? '';

if (empty($ma_de)) {
    set_message('error', 'Mã đề không được để trống.');
    redirect('/');
}

$test = $db->fetch("SELECT * FROM tests WHERE ma_de = ?", [$ma_de]);

if (!$test) {
    redirect('/?error=invalid_code&ma_de=' . urlencode($ma_de) . '&phone_number=' . urlencode($phone_number) . '&submitter_name=' . urlencode($submitter_name));
}

$questions = $db->fetchAll("
    SELECT q.* FROM questions q
    JOIN test_questions tq ON q.question_id = tq.question_id
    WHERE tq.test_id = ?
    ORDER BY q.question_id ASC
", [$test['test_id']]);

if (empty($questions)) {
    set_message('warning', 'Đề thi này chưa có câu hỏi nào. Vui lòng liên hệ quản trị viên.');
    redirect('/');
}
?>

<div class="lb-take-test-container gdv-container">
    <h1><?php echo htmlspecialchars($test['title']); ?></h1>
    <p>Mã đề: <strong><?php echo htmlspecialchars($test['ma_de']); ?></strong></p>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <p>Thời gian làm bài: <strong><?php echo htmlspecialchars($test['time_limit']); ?> phút</strong></p>
        <div id="test-timer" style="font-weight: bold; font-size: 1.2em; color: var(--gdv-primary);"></div>
    </div>
    <p>Thí sinh: <strong><?php echo htmlspecialchars($submitter_name); ?></strong> (SĐT: <?php echo htmlspecialchars($phone_number); ?>)</p>

    <hr>

    <form id="test-submission-form" method="POST" action="/api/ajax">
        <input type="hidden" name="action" value="submit_test">
        <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
        <input type="hidden" name="ma_de" value="<?php echo htmlspecialchars($ma_de); ?>">
        <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
        <input type="hidden" name="submitter_name" value="<?php echo htmlspecialchars($submitter_name); ?>">

        <?php foreach ($questions as $index => $question): ?>
            <div class="question-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid var(--gdv-border); border-radius: 8px;">
                <h3>Câu <?php echo $index + 1; ?>:</h3>
                <p><?php echo nl2br(htmlspecialchars($question['content'])); ?></p>

                <?php if ($question['type'] === 'trac_nghiem'): ?>
                    <?php $options = json_decode($question['options'], true); ?>
                    <?php if (!empty($options)): ?>
                        <div class="options">
                            <?php foreach ($options as $key => $option): ?>
                                <label style="display: block; margin-bottom: 5px;"><input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo htmlspecialchars($key); ?>"> <?php echo htmlspecialchars($option); ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php elseif ($question['type'] === 'tu_luan'): ?>
                    <textarea name="answers[<?php echo $question['question_id']; ?>]" rows="5" style="width: 100%; padding: 10px; border: 1px solid var(--gdv-border); border-radius: 5px;"></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="gdv-button" style="margin-top: 20px;">Nộp bài</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timerDiv = document.getElementById('test-timer');
    const testForm = document.getElementById('test-submission-form');
    const timeLimitMinutes = <?php echo intval($test['time_limit']); ?>;

    if (timerDiv && timeLimitMinutes > 0) {
        let timeLeft = timeLimitMinutes * 60;
        const timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('Hết giờ làm bài! Hệ thống sẽ tự động nộp bài của bạn.');
                const submitButton = testForm.querySelector('button[type="submit"]');
                if(submitButton) { submitButton.click(); } else { testForm.submit(); }
            } else {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDiv.textContent = 'Thời gian còn lại: ' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
        }, 1000);
    }
});
</script>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>