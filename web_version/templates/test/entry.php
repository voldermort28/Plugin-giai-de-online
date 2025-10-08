<?php
// web_version/templates/test/entry.php

$page_title = 'Bắt đầu làm bài';
include APP_ROOT . '/templates/partials/header.php';

// Lấy các giá trị cũ để điền lại form nếu có lỗi
$ma_de = $_GET['ma_de'] ?? '';
$phone_number = $_GET['phone_number'] ?? '';
$error = $_GET['error'] ?? '';
$error_msg = '';
if ($error === 'invalid_code') $error_msg = 'Mã đề thi không hợp lệ hoặc đã được sử dụng.';
if ($error === 'missing_info') $error_msg = 'Vui lòng nhập đầy đủ Mã đề và Số điện thoại.';
?>

<div class="lb-test-code-input-form">
    <form method="POST" action="/test/confirm" class="gdv-card" style="max-width: 500px;">
        <h2 style="text-align: center;">Bắt đầu làm bài</h2>
        <?php if ($error_msg): ?><p style="color: var(--gdv-danger); font-size: 0.9em; margin-top: 5px; background: var(--gdv-error-bg); padding: 10px; border-radius: 5px;"><?php echo $error_msg; ?></p><?php endif; ?>
        <div class="form-group">
            <label for="ma_de">Nhập mã đề thi:</label>
            <input type="text" name="ma_de" id="ma_de" class="input" value="<?php echo htmlspecialchars($ma_de); ?>" required>
        </div>
        <div class="form-group">
            <label for="phone_number">Nhập số điện thoại:</label>
            <input type="tel" name="phone_number" id="phone_number" class="input" value="<?php echo htmlspecialchars($phone_number); ?>" required>
        </div>
        <div class="form-group"><button type="submit" class="gdv-button" style="width: 100%;">Tiếp tục</button></div>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>