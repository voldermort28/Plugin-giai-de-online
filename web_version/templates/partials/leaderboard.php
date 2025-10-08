<?php
// web_version/templates/public/leaderboard.php

// --- Xử lý đăng nhập/đăng xuất bằng SĐT ---
if (isset($_GET['logout'])) {
    unset($_SESSION['contestant_phone']);
    unset($_SESSION['contestant_name']);
    redirect('/leaderboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_login_submit'])) {
    $phone_number = trim($_POST['phone_number'] ?? '');
    if (!empty($phone_number)) {
        $contestant = $db->fetch("SELECT contestant_name FROM submissions WHERE contestant_phone = ? ORDER BY submission_id DESC LIMIT 1", [$phone_number]);
        if ($contestant) {
            $_SESSION['contestant_phone'] = $phone_number;
            $_SESSION['contestant_name'] = $contestant['contestant_name'];
            redirect('/leaderboard');
        } else {
            $_SESSION['login_error'] = 'Số điện thoại không tồn tại trong hệ thống.';
        }
    } else {
        $_SESSION['login_error'] = 'Vui lòng nhập số điện thoại.';
    }
    redirect('/leaderboard');
}

$page_title = 'Bảng Xếp Hạng';
include APP_ROOT . '/templates/partials/header.php';

if (isset($_SESSION['contestant_phone'])):
    // --- Người dùng đã đăng nhập: Hiển thị BXH ---
    $current_phone = $_SESSION['contestant_phone'];
    $current_name = $_SESSION['contestant_name'];

    // Lấy danh sách cuộc thi
    $contests = $db->fetchAll("SELECT DISTINCT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY contest_name");
    $current_contest = $_GET['contest'] ?? '';
    if (empty($current_contest) && !empty($contests)) {
        $latest_contest = $db->fetch("SELECT contest_name FROM tests WHERE contest_name IS NOT NULL AND contest_name != '' ORDER BY test_id DESC LIMIT 1");
        $current_contest = $latest_contest['contest_name'] ?? '';
    }

    // Lấy dữ liệu BXH
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
?>
    <div class="gdv-header">
        <h1>Bảng xếp hạng</h1>
        <div style="text-align: right;">
            <p style="margin: 0; font-size: 14px;">Chào, <strong><?php echo htmlspecialchars($current_name); ?></strong></p>
            <a href="/leaderboard?logout=true" style="font-size: 12px; color: var(--gdv-danger);">Đăng nhập SĐT khác</a>
        </div>
    </div>

    <div class="gdv-card" style="margin-top: 0; margin-bottom: 20px; padding: 20px;">
        <form method="GET" action="/leaderboard">
            <label for="contest">Chọn cuộc thi để xem bảng xếp hạng:</label>
            <select id="contest" name="contest" class="input" onchange="this.form.submit()">
                <?php foreach ($contests as $contest): ?>
                    <option value="<?php echo htmlspecialchars($contest['contest_name']); ?>" <?php echo ($current_contest === $contest['contest_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($contest['contest_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
                <canvas id="publicLeaderboardChart"></canvas>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('publicLeaderboardChart').getContext('2d');
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
                    <?php $rank = 1; foreach ($leaderboard_data as $row): 
                        $highlight_class = ($row['contestant_phone'] === $current_phone) ? 'is-current-user' : '';
                    ?>
                        <tr class="<?php echo $highlight_class; ?>">
                            <td style="text-align: center; font-weight: bold;"><?php echo $rank++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['contestant_name']); ?></strong></td>
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

<?php else: 
    // --- Người dùng chưa đăng nhập: Hiển thị form ---
    $error_message = $_SESSION['login_error'] ?? '';
    unset($_SESSION['login_error']);
?>
    <div class="lb-login-form">
        <form method="POST" action="/leaderboard" class="gdv-card" style="max-width: 400px;">
            <h2 style="text-align: center;">Xem Bảng Xếp Hạng</h2>
            <p style="text-align: center; color: var(--gdv-text-secondary);">Vui lòng nhập số điện thoại bạn đã dùng để làm bài thi.</p>
            
            <?php if ($error_message): ?>
                <p style="color: var(--gdv-danger); font-size: 0.9em; background: var(--gdv-error-bg); padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label for="phone_number">Số điện thoại</label>
                <input type="tel" name="phone_number" id="phone_number" class="input" required>
            </div>
            <div class="form-group">
                <button type="submit" name="phone_login_submit" class="gdv-button" style="width: 100%;">Xem kết quả</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>