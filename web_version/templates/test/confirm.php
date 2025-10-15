<?php
// web_version/templates/test/confirm.php

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$ma_de = $_POST['ma_de'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';

// Kiểm tra thông tin đầu vào
if (empty($ma_de) || empty($phone_number)) {
    redirect('/?error=missing_info&ma_de=' . urlencode($ma_de) . '&phone_number=' . urlencode($phone_number));
}

// Kiểm tra mã đề
$test = $db->fetch("SELECT test_id, title FROM tests WHERE ma_de = ?", [$ma_de]);

// Sửa lỗi: Kiểm tra sự tồn tại của BẤT KỲ bài làm nào (kể cả 'in_progress')
// để ngăn chặn việc một mã đề được sử dụng lại khi bài làm trước đó chưa hoàn tất.
$existing_submission = $test ? $db->fetch("SELECT submission_id FROM submissions WHERE test_id = ? LIMIT 1", [$test['test_id']]) : null;

if (!$test || $existing_submission) {
    redirect('/?error=invalid_code&ma_de=' . urlencode($ma_de) . '&phone_number=' . urlencode($phone_number));
}

// Kiểm tra số điện thoại
$contestant_row = $db->fetch(
    "SELECT contestant_name FROM submissions WHERE contestant_phone = ? AND contestant_name IS NOT NULL AND contestant_name != '' ORDER BY submission_id DESC LIMIT 1",
    [$phone_number]
);

$submitter_name = $contestant_row['contestant_name'] ?? '';
$is_new_contestant = empty($submitter_name);

$page_title = 'Xác nhận thông tin';
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="lb-test-code-input-form">
    <form method="GET" action="/test" class="gdv-card" style="max-width: 500px;">
        <h2 style="text-align: center;">Xác nhận thông tin</h2>
        
        <div class="form-group">
            <label>Mã đề thi:</label>
            <input type="text" class="input" value="<?php echo htmlspecialchars($ma_de); ?>" readonly>
            <input type="hidden" name="ma_de" value="<?php echo htmlspecialchars($ma_de); ?>">
        </div>
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="tel" class="input" value="<?php echo htmlspecialchars($phone_number); ?>" readonly>
            <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
        </div>
        <div class="form-group">
            <label for="submitter_name">Tên của bạn:</label>
            <input type="text" name="submitter_name" id="submitter_name" class="input" value="<?php echo htmlspecialchars($submitter_name); ?>" required>
            <?php if (!$is_new_contestant): ?>
                <p style="color: var(--gdv-primary); font-size: 0.9em; margin-top: 5px;">Chào mừng bạn trở lại! Vui lòng xác nhận lại tên của bạn.</p>
            <?php else: ?>
                <p style="color: var(--gdv-text-secondary); font-size: 0.9em; margin-top: 5px;">Đây là lần đầu bạn tham gia? Vui lòng nhập họ và tên đầy đủ.</p>
            <?php endif; ?>
        </div>
        <div class="form-group"><button type="submit" class="gdv-button" style="width: 100%;">Bắt đầu làm bài</button></div>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>