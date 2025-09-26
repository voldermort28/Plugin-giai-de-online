<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * SHORTCODE BẢNG XẾP HẠNG CHO THÍ SINH
 * Shortcode: [bxh_thi_sinh]
 * ===================================================================
 */
add_shortcode('bxh_thi_sinh', 'lb_render_contestant_leaderboard_shortcode');

function lb_render_contestant_leaderboard_shortcode() {
    ob_start();

    // Kiểm tra session và quyết định hiển thị nội dung phù hợp
    if (isset($_SESSION['lb_test_contestant_phone'])) {
        lb_render_actual_contestant_leaderboard($_SESSION['lb_test_contestant_phone'], $_SESSION['lb_test_contestant_name'] ?? '');
    } else {
        // Lấy thông báo lỗi từ session nếu có
        $error_message = $_SESSION['lb_phone_login_error'] ?? '';
        unset($_SESSION['lb_phone_login_error']); // Xóa sau khi lấy
        lb_render_phone_login_form($error_message);
    }

    return ob_get_clean();
}

/**
 * Hiển thị form yêu cầu nhập SĐT.
 */
function lb_render_phone_login_form($error_message = '') {
    ?>
    <div class="gdv-container">
        <h2 style="text-align: center;">Xem Bảng Xếp Hạng</h2>
        <p style="text-align: center;">Vui lòng nhập số điện thoại bạn đã dùng để làm bài thi.</p>
        <form method="POST" action="" id="loginform" class="lb-phone-login-form">
            <?php wp_nonce_field('lb_phone_login_action', 'lb_phone_login_nonce'); ?>
            <p>
                <label for="phone_number">Số điện thoại</label>
                <input type="tel" id="phone_number" name="phone_number" class="input" required>
            </p>
            <?php if (!empty($error_message)) : ?>
                <p class="login-error" style="color: var(--gdv-danger-text); background: var(--gdv-danger-bg); border-left-color: var(--gdv-danger-text);"><?php echo esc_html($error_message); ?></p>
            <?php endif; ?>
            <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Xem kết quả"></p>
        </form>
    </div>
    <?php
}

/**
 * Hiển thị nội dung Bảng xếp hạng thực tế sau khi đã xác thực.
 */
function lb_render_actual_contestant_leaderboard($phone_number, $display_name) {
    global $wpdb;

    // --- Lấy danh sách các cuộc thi để lọc (logic tương tự trang admin) ---
    $contest_meta_key = '_contest_name';
    $contest_names = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s AND p.post_type = 'dethi_baikiemtra'
         ORDER BY pm.meta_value",
        $contest_meta_key
    ));

    $current_contest = isset($_GET['contest_filter']) ? sanitize_text_field($_GET['contest_filter']) : '';
    if (empty($current_contest) && !empty($contest_names)) {
        $current_contest = end($contest_names); // Mặc định chọn cuộc thi cuối cùng (mới nhất)
    }

    $leaderboard_data = [];
    if (!empty($current_contest)) {
        $submissions_table = $wpdb->prefix . 'lb_test_submissions';
        $contestants_table = $wpdb->prefix . 'lb_test_contestants';
        $leaderboard_data = $wpdb->get_results($wpdb->prepare(
            "SELECT
                c.phone_number,
                c.display_name,
                ROUND(AVG(s.score), 2) as average_score,
                COUNT(s.submission_id) as tests_taken
            FROM {$submissions_table} s
            INNER JOIN {$contestants_table} c ON s.contestant_id = c.contestant_id
            INNER JOIN {$wpdb->posts} p ON s.test_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'dethi_baikiemtra' AND pm.meta_key = %s AND pm.meta_value = %s AND s.status = 'graded'
            GROUP BY s.contestant_id
            ORDER BY average_score DESC, tests_taken DESC",
            $contest_meta_key,
            $current_contest
        ));
    }
    ?>
    <div class="gdv-container">
        <div class="gdv-header">
            <h1>Bảng xếp hạng</h1>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 14px;">Chào, <strong><?php echo esc_html($display_name); ?></strong></p>
                <a href="<?php echo esc_url(add_query_arg('logout', 'true')); ?>" style="font-size: 12px; color: var(--gdv-danger-text);">Đăng nhập SĐT khác</a>
            </div>
        </div>

        <div class="gdv-toolbar">
            <form method="get" action="" class="gdv-filter-form">
                <label for="contest_filter">Chọn cuộc thi:</label>
                <select name="contest_filter" id="contest_filter" onchange="this.form.submit()">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($contest_names as $name) : ?>
                        <option value="<?php echo esc_attr($name); ?>" <?php selected($current_contest, $name); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (!empty($leaderboard_data)) : ?>
            <?php
            // --- Chuẩn bị dữ liệu cho biểu đồ (Top 10) ---
            $chart_data = array_slice($leaderboard_data, 0, 10);
            $chart_labels = wp_list_pluck($chart_data, 'display_name');
            $chart_scores = wp_list_pluck($chart_data, 'average_score');

            // --- Mảng màu sắc cho biểu đồ ---
            $background_colors = [
                'rgba(212, 175, 55, 0.6)', // Gold
                'rgba(192, 192, 192, 0.6)', // Silver
                'rgba(205, 127, 50, 0.6)',  // Bronze
                'rgba(79, 70, 229, 0.6)',   // Indigo/Primary
                'rgba(54, 162, 235, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)',
                'rgba(255, 99, 132, 0.6)',
                'rgba(40, 159, 64, 0.6)',
            ];
            $border_colors = [
                'rgba(212, 175, 55, 1)',
                'rgba(192, 192, 192, 1)',
                'rgba(205, 127, 50, 1)',
                'rgba(79, 70, 229, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(40, 159, 64, 1)',
            ];
            ?>
            <div class="gdv-chart-wrapper" style="background: var(--gdv-white); padding: 20px; border-radius: 12px; border: 1px solid var(--gdv-border); margin-bottom: 20px;">
                <h2 style="margin-top: 0;">Top 10 Thí sinh</h2>
                <div style="position: relative; height:400px;">
                    <canvas id="contestantLeaderboardChart"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('contestantLeaderboardChart').getContext('2d');
                const leaderboardChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Điểm Trung Bình',
                            data: <?php echo json_encode($chart_scores); ?>,
                            backgroundColor: <?php echo json_encode($background_colors); ?>,
                            borderColor: <?php echo json_encode($border_colors); ?>,
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
                        <?php $rank = 1; foreach ($leaderboard_data as $row) :
                            // Tô sáng dòng của người đang xem
                            $highlight_class = ($row->phone_number === $phone_number) ? 'is-current-user' : '';
                        ?>
                            <tr class="<?php echo $highlight_class; ?>">
                                <td style="text-align: center; font-weight: bold;"><?php echo $rank; ?></td>
                                <td><strong><?php echo esc_html($row->display_name); ?></strong></td>
                                <td style="text-align: center;"><?php echo intval($row->tests_taken); ?></td>
                                <td style="text-align: right; font-weight: bold; font-size: 1.1em; color: var(--gdv-primary);"><?php echo esc_html($row->average_score); ?></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div style="text-align: center; padding: 30px; background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 12px;">Chưa có dữ liệu chấm bài cho cuộc thi này.</div>
        <?php endif; ?>
    </div>
    <?php
}