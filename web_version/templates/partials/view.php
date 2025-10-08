<?php
// web_version/templates/admin/contestants/view.php

// Xử lý cập nhật thông tin thí sinh (cả tên và SĐT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_contestant') {
    $original_phone = $_POST['original_phone_number'] ?? '';
    $new_phone = trim($_POST['contestant_phone'] ?? '');
    $new_name = trim($_POST['contestant_name'] ?? '');

    if (!empty($original_phone) && !empty($new_name) && !empty($new_phone)) {
        // Cập nhật tên và SĐT cho tất cả các bài làm có cùng SĐT gốc
        $db->update(
            'submissions', 
            ['contestant_name' => $new_name, 'contestant_phone' => $new_phone], 
            'contestant_phone = ?', 
            [$original_phone]
        );
        set_message('success', 'Đã cập nhật thông tin thí sinh thành công.');
        // Chuyển hướng đến trang xem chi tiết với SĐT mới
        redirect('/admin/contestants/view?phone=' . urlencode($new_phone));
    } else {
        set_message('error', 'Tên và Số điện thoại không được để trống.');
        // Chuyển hướng về trang edit với SĐT gốc
        redirect('/admin/contestants/view?phone=' . urlencode($original_phone) . '&edit=true');
    }
}


$phone_number = $_GET['phone'] ?? null;
if (!$phone_number) {
    set_message('error', 'Số điện thoại thí sinh không hợp lệ.');
    redirect('/admin/contestants');
}

// Lấy thông tin thí sinh từ bài nộp gần nhất
$contestant = $db->fetch("SELECT contestant_name, contestant_phone, MIN(submission_time) as first_submission_time FROM submissions WHERE contestant_phone = ? GROUP BY contestant_phone", [$phone_number]);
if (!$contestant) {
    set_message('error', 'Không tìm thấy thí sinh.');
    redirect('/admin/contestants');
}

// Lấy danh sách bài làm của thí sinh
$submissions = $db->fetchAll("
    SELECT s.submission_id, s.test_id, s.status, s.score, s.submission_time, t.title as test_title, s.contestant_name
    FROM submissions s
    LEFT JOIN tests t ON s.test_id = t.test_id
    WHERE s.contestant_phone = ?
    ORDER BY s.submission_time DESC
", [$phone_number]);

$is_editing = isset($_GET['edit']);

$page_title = 'Hồ sơ: ' . htmlspecialchars($contestant['contestant_name']);
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <a href="/admin/contestants" class="gdv-button secondary" style="padding: 8px 12px; font-size: 14px; text-decoration: none;">&larr; Quay lại</a>
        <?php echo $is_editing ? 'Chỉnh sửa hồ sơ' : 'Hồ sơ'; ?>: <?php echo htmlspecialchars($contestant['contestant_name']); ?>
    </h1>
</div>

<div class="gdv-card" style="max-width: 900px; margin-top: 0;">
    <?php if ($is_editing): ?>
        <form method="POST" action="/admin/contestants/view">
            <input type="hidden" name="action" value="update_contestant">
            <input type="hidden" name="original_phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
            
            <div class="form-group">
                <label for="contestant_phone">Số điện thoại</label>
                <input type="tel" name="contestant_phone" id="contestant_phone" class="input" value="<?php echo htmlspecialchars($contestant['contestant_phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="contestant_name">Tên thí sinh</label>
                <input type="text" name="contestant_name" id="contestant_name" class="input" value="<?php echo htmlspecialchars($contestant['contestant_name']); ?>" required>
            </div>
            <div class="form-group" style="text-align: right;">
                <a href="/admin/contestants/view?phone=<?php echo urlencode($phone_number); ?>" class="gdv-button secondary" style="margin-right: 10px;">Hủy</a>
                <button type="submit" class="gdv-button">Lưu thay đổi</button>
            </div>
        </form>
    <?php else: ?>
        <p><strong>Số điện thoại:</strong> <code><?php echo htmlspecialchars($contestant['contestant_phone']); ?></code></p>
        <p><strong>Tên hiển thị:</strong> <strong><?php echo htmlspecialchars($contestant['contestant_name']); ?></strong></p>
        <p><strong>Lần thi đầu tiên:</strong> <?php echo date('d/m/Y H:i', strtotime($contestant['first_submission_time'])); ?></p>
        <a href="/admin/contestants/view?phone=<?php echo urlencode($phone_number); ?>&edit=true" class="gdv-button" style="margin-top: 10px;">Chỉnh sửa thông tin</a>
    <?php endif; ?>
</div>

<h2 style="margin-top: 40px;">Lịch sử làm bài</h2>
<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>Tên bài thi</th>
                <th>Trạng thái</th>
                <th>Điểm số</th>
                <th>Thời gian nộp</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($submissions)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">Thí sinh này chưa làm bài thi nào.</td></tr>
            <?php else: ?>
                <?php foreach ($submissions as $sub):
                    $grader_url = '/grader/grade?submission_id=' . $sub['submission_id'];
                    $status_text = ($sub['status'] === 'graded') ? 'Đã chấm' : 'Cần chấm';
                    $action_text = ($sub['status'] === 'graded') ? 'Xem lại' : 'Chấm bài';
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sub['test_title']); ?></strong></td>
                        <td><span class="gdv-status <?php echo $sub['status']; ?>"><?php echo htmlspecialchars($status_text); ?></span></td>
                        <td><?php echo ($sub['status'] === 'graded') ? '<strong>' . intval($sub['score']) . '</strong>' : '—'; ?></td>
                        <td><?php echo date('d/m/Y, H:i', strtotime($sub['submission_time'])); ?></td>
                        <td>
                            <a href="<?php echo $grader_url; ?>" class="gdv-action-link" target="_blank"><?php echo $action_text; ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>