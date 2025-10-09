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

// Lấy tên thí sinh (từ bài nộp gần nhất để đảm bảo tên là mới nhất)
$contestant_name_row = $db->fetch("SELECT contestant_name FROM submissions WHERE contestant_phone = ? ORDER BY submission_id DESC LIMIT 1", [$phone_number]);
if (!$contestant_name_row) {
    set_message('error', 'Không tìm thấy thí sinh.');
    redirect('/admin/contestants');
}
$contestant_name = $contestant_name_row['contestant_name'];

// Lấy danh sách các cuộc thi mà thí sinh đã tham gia
$contests = $db->fetchAll("
    SELECT DISTINCT t.contest_name
    FROM submissions s
    JOIN tests t ON s.test_id = t.test_id
    WHERE s.contestant_phone = ? AND t.contest_name IS NOT NULL AND t.contest_name != ''
    ORDER BY t.contest_name
", [$phone_number]);

// Lấy bộ lọc cuộc thi từ URL, mặc định là cuộc thi đầu tiên trong danh sách
$filter_contest = $_GET['contest'] ?? ($contests[0]['contest_name'] ?? null);

// Tính điểm trung bình và số bài đã làm dựa trên bộ lọc
$score_stats_sql = "
    SELECT ROUND(AVG(s.score), 2) as average_score, COUNT(s.submission_id) as tests_taken
    FROM submissions s
    JOIN tests t ON s.test_id = t.test_id
    WHERE s.contestant_phone = ? AND s.status = 'graded'
";
$score_params = [$phone_number];
if ($filter_contest) {
    $score_stats_sql .= " AND t.contest_name = ?";
    $score_params[] = $filter_contest;
}
$score_stats = $db->fetch($score_stats_sql, $score_params);

// Lấy danh sách bài làm của thí sinh
$submissions_sql = "
    SELECT s.submission_id, s.test_id, s.status, s.score, s.submission_time, t.title as test_title, s.contestant_name
    FROM submissions s
    LEFT JOIN tests t ON s.test_id = t.test_id
    WHERE s.contestant_phone = ?
";
$submissions_params = [$phone_number];
if ($filter_contest) {
    $submissions_sql .= " AND t.contest_name = ?";
    $submissions_params[] = $filter_contest;
}
$submissions_sql .= " ORDER BY s.submission_time DESC";
$submissions = $db->fetchAll($submissions_sql, $submissions_params);

$is_editing = isset($_GET['edit']);

$page_title = 'Hồ sơ: ' . htmlspecialchars($contestant_name);
include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <a href="/admin/contestants" class="gdv-button secondary" style="padding: 8px 12px; font-size: 14px; text-decoration: none;">&larr; Quay lại</a>
        <?php echo $is_editing ? 'Chỉnh sửa hồ sơ' : 'Hồ sơ'; ?>: <?php echo htmlspecialchars($contestant_name); ?>
    </h1>
</div>

<div class="gdv-card" style="max-width: 900px; margin-top: 0;">
    <?php if ($is_editing): ?>
        <form method="POST" action="/admin/contestants/view">
            <input type="hidden" name="action" value="update_contestant">
            <input type="hidden" name="original_phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
            
            <div class="form-group">
                <label for="contestant_phone">Số điện thoại</label>
                <input type="tel" name="contestant_phone" id="contestant_phone" class="input" value="<?php echo htmlspecialchars($phone_number); ?>" required>
            </div>
            <div class="form-group">
                <label for="contestant_name">Tên thí sinh</label>
                <input type="text" name="contestant_name" id="contestant_name" class="input" value="<?php echo htmlspecialchars($contestant_name); ?>" required>
            </div>
            <div class="form-group" style="text-align: right;">
                <a href="/admin/contestants/view?phone=<?php echo urlencode($phone_number); ?>" class="gdv-button secondary" style="margin-right: 10px;">Hủy</a>
                <button type="submit" class="gdv-button">Lưu thay đổi</button>
            </div>
        </form>
    <?php else: ?>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
            <div>
                <p style="margin: 0 0 5px 0;"><strong>Số điện thoại:</strong> <code><?php echo htmlspecialchars($phone_number); ?></code></p>
                <p style="margin: 0;"><strong>Tên hiển thị:</strong> <strong><?php echo htmlspecialchars($contestant_name); ?></strong></p>
                <a href="/admin/contestants/view?phone=<?php echo urlencode($phone_number); ?>&edit=true" class="gdv-button secondary" style="margin-top: 15px;">Chỉnh sửa thông tin</a>
            </div>
            <!-- Score Card -->
            <div style="background: var(--gdv-primary); color: white; border-radius: 12px; padding: 20px; text-align: center; min-width: 250px;">
                <h4 style="color: white; margin: 0 0 5px 0; font-size: 1rem; opacity: 0.8; text-transform: uppercase;">Điểm trung bình</h4>
                <div style="font-size: 3rem; font-weight: 700; line-height: 1;"><?php echo htmlspecialchars($score_stats['average_score'] ?? 'N/A'); ?></div>
                <p style="margin: 5px 0 0 0; opacity: 0.8;"><?php echo htmlspecialchars($filter_contest ?? 'Tất cả các cuộc thi'); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// --- Chuẩn bị dữ liệu cho biểu đồ ---
// Lọc ra các bài đã chấm và đảo ngược để có thứ tự thời gian tăng dần
$graded_submissions_for_chart = array_filter($submissions, function($sub) {
    return $sub['status'] === 'graded';
});
$chart_submissions = array_reverse($graded_submissions_for_chart);

$chart_labels = array_column($chart_submissions, 'test_title');
$chart_scores = array_column($chart_submissions, 'score');
?>

<?php if (!empty($chart_scores)): ?>
<div class="gdv-card" style="max-width: 900px; margin-top: 20px;">
    <h3 style="margin-top: 0;">Biểu đồ điểm số (<?php echo htmlspecialchars($filter_contest ?? 'Tất cả'); ?>)</h3>
    <div style="position: relative; height:300px;">
        <canvas id="scoresChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('scoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Điểm số',
                data: <?php echo json_encode($chart_scores); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: { scales: { y: { beginAtZero: true, suggestedMax: 10 } }, responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
});
</script>
<?php endif; ?>

<div class="gdv-header" style="margin-top: 40px;">
    <h2>Lịch sử làm bài</h2>
    <?php if (!empty($contests)): ?>
        <form method="GET" action="/admin/contestants/view">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone_number); ?>">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="contest" style="margin: 0; font-weight: normal;">Lọc theo cuộc thi:</label>
                <select id="contest" name="contest" class="input" onchange="this.form.submit()" style="width: auto;">
                    <option value="">Tất cả</option>
                    <?php foreach ($contests as $contest): ?>
                        <option value="<?php echo htmlspecialchars($contest['contest_name']); ?>" <?php echo ($filter_contest === $contest['contest_name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($contest['contest_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    <?php endif; ?>
</div>

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
                    // Chỉ hiển thị các bài đã được chấm
                    if ($sub['status'] !== 'graded') {
                        continue;
                    }
                    $grader_url = '/grader/grade?submission_id=' . $sub['submission_id'];
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sub['test_title']); ?></strong></td>
                        <td><span class="gdv-status graded">Đã chấm</span></td>
                        <td><strong><?php echo intval($sub['score']); ?></strong></td>
                        <td><?php echo date('d/m/Y, H:i', strtotime($sub['submission_time'])); ?></td>
                        <td>
                            <a href="<?php echo $grader_url; ?>" class="gdv-action-link" target="_blank">Xem lại</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>