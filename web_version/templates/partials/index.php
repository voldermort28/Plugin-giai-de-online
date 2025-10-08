<?php
// web_version/templates/admin/leaderboard/index.php

$page_title = 'Bảng Xếp Hạng';

// --- Lấy danh sách các cuộc thi để lọc ---
$contests = $db->fetchAll("SELECT DISTINCT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY contest_name");

// --- Lấy cuộc thi đang được lọc ---
$current_contest = $_GET['contest'] ?? '';
if (empty($current_contest) && !empty($contests)) {
    // Mặc định chọn cuộc thi mới nhất nếu không có lựa chọn
    $latest_contest = $db->fetch("SELECT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY test_id DESC LIMIT 1");
    if ($latest_contest) {
        $current_contest = $latest_contest['contest_name'];
    }
}

// --- Truy vấn dữ liệu bảng xếp hạng nếu có cuộc thi được chọn ---
$leaderboard_data = [];
if (!empty($current_contest)) {
    $leaderboard_data = $db->fetchAll(
        "SELECT
            s.contestant_phone,
            (SELECT s2.contestant_name FROM submissions s2 WHERE s2.contestant_phone = s.contestant_phone ORDER BY s2.submission_id DESC LIMIT 1) as contestant_name,
            ROUND(AVG(s.score), 2) as average_score,
            COUNT(s.submission_id) as tests_taken
        FROM submissions s
        INNER JOIN tests t ON s.test_id = t.test_id
        WHERE t.contest_name = ? AND s.status = 'graded'
        GROUP BY s.contestant_phone
        ORDER BY average_score DESC, tests_taken DESC",
        [$current_contest]
    );
}

include APP_ROOT . '/templates/partials/header.php';
?>

<div class="gdv-header">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px;">
    <form method="GET" action="/admin/leaderboard" style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex-grow: 1;">
            <label for="contest">Lọc theo cuộc thi</label>
            <select id="contest" name="contest" class="input" onchange="this.form.submit()">
                <option value="">-- Chọn một cuộc thi --</option>
                <?php foreach ($contests as $contest): ?>
                    <option value="<?php echo htmlspecialchars($contest['contest_name']); ?>" <?php echo ($current_contest === $contest['contest_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($contest['contest_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (!empty($leaderboard_data)): ?>
    <?php
    // --- Chuẩn bị dữ liệu cho biểu đồ (Top 10) ---
    $chart_data = array_slice($leaderboard_data, 0, 10);
    $chart_labels = array_column($chart_data, 'contestant_name');
    $chart_scores = array_column($chart_data, 'average_score');
    ?>
    <div class="gdv-card" style="margin-bottom: 20px;">
        <h3 style="margin-top: 0;">Top 10 Thí sinh</h3>
        <div style="position: relative; height:400px;">
            <canvas id="leaderboardChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('leaderboardChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Điểm Trung Bình',
                    data: <?php echo json_encode($chart_scores); ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.6)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, suggestedMax: 10 } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
    </script>

    <div class="gdv-table-wrapper">
        <table class="gdv-table">
            <thead>
                <tr>
                    <th style="width: 80px; text-align: center;">Hạng</th>
                    <th>Tên Thí Sinh</th>
                    <th style="text-align: center;">Số bài đã làm</th>
                    <th style="text-align: right;">Điểm Trung Bình</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($leaderboard_data as $row): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $rank++; ?></td>
                        <td>
                            <a href="/admin/contestants/view?phone=<?php echo urlencode($row['contestant_phone']); ?>" class="gdv-action-link">
                                <strong><?php echo htmlspecialchars($row['contestant_name']); ?></strong>
                            </a>
                        </td>
                        <td style="text-align: center;"><?php echo intval($row['tests_taken']); ?></td>
                        <td style="text-align: right; font-weight: bold; font-size: 1.1em; color: var(--gdv-primary);"><?php echo htmlspecialchars($row['average_score']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (!empty($current_contest)): ?>
    <div class="gdv-card" style="text-align: center; padding: 40px;">Chưa có dữ liệu chấm bài cho cuộc thi này.</div>
<?php endif; ?>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>