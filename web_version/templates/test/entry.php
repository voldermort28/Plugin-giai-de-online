<?php
// web_version/templates/test/entry.php

$page_title = 'Bắt đầu làm bài';
include APP_ROOT . '/templates/partials/header.php';

$ma_de = $_GET['ma_de'] ?? '';
$phone_number = $_GET['phone_number'] ?? '';
$submitter_name = $_GET['submitter_name'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="lb-test-code-input-form gdv-container">
    <form method="GET" action="/test" class="gdv-container" style="max-width: 500px; margin: 40px auto; padding: 40px; background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h2>Bắt đầu làm bài kiểm tra</h2>
        <p>
            <label for="ma_de">Nhập mã đề thi:</label>
            <input type="text" name="ma_de" id="ma_de" class="input" value="<?php echo htmlspecialchars($ma_de); ?>" required>
            <?php if ($error === 'invalid_code'): ?><p style="color: #dc3545; font-size: 0.9em; margin-top: 5px;">Mã đề thi không hợp lệ.</p><?php endif; ?>
        </p>
        <p>
            <label for="phone_number">Nhập số điện thoại:</label>
            <input type="tel" name="phone_number" id="phone_number" class="input" value="<?php echo htmlspecialchars($phone_number); ?>" required>
        </p>
        <p>
            <label for="submitter_name">Nhập tên của bạn:</label>
            <input type="text" name="submitter_name" id="submitter_name" class="input" value="<?php echo htmlspecialchars($submitter_name); ?>" required>
        </p>
        <p class="submit"><button type="submit" class="gdv-button" style="width: 100%; font-size: 16px; padding: 12px 20px;">Bắt đầu làm bài</button></p>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>