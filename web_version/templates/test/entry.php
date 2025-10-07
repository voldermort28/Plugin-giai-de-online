<?php
// web_version/templates/test/entry.php

$page_title = 'Bắt đầu làm bài';
include APP_ROOT . '/templates/partials/header.php';

$ma_de = $_GET['ma_de'] ?? '';
$phone_number = $_GET['phone_number'] ?? '';
$submitter_name = $_GET['submitter_name'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="lb-test-code-input-form">
    <form method="GET" action="/test" class="gdv-card" style="max-width: 500px;">
        <h2 style="text-align: center;">Bắt đầu làm bài</h2>
        <div class="form-group">
            <label for="ma_de">Nhập mã đề thi:</label>
            <input type="text" name="ma_de" id="ma_de" class="input" value="<?php echo htmlspecialchars($ma_de); ?>" required>
            <?php if ($error === 'invalid_code'): ?><p style="color: var(--gdv-danger); font-size: 0.9em; margin-top: 5px;">Mã đề thi không hợp lệ.</p><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="phone_number">Nhập số điện thoại:</label>
            <input type="tel" name="phone_number" id="phone_number" class="input" value="<?php echo htmlspecialchars($phone_number); ?>" required>
        </div>
        <div class="form-group">
            <label for="submitter_name">Nhập tên của bạn:</label>
            <input type="text" name="submitter_name" id="submitter_name" class="input" value="<?php echo htmlspecialchars($submitter_name); ?>" required>
        </div>
        <div class="form-group"><button type="submit" class="gdv-button" style="width: 100%;">Bắt đầu làm bài</button></div>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>