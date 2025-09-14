<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * SHORTCODE FOR LEADERBOARD PAGE
 * Shortcode: [bang_xep_hang]
 * ===================================================================
 */
add_shortcode('bang_xep_hang', 'lb_render_leaderboard_page');

function lb_render_leaderboard_page() {
    if (!current_user_can('grade_submissions')) {
        return '<p>Bạn không có quyền truy cập trang này.</p>';
    }

    ob_start();

    global $wpdb;

    // --- Lấy danh sách các cuộc thi để lọc ---
    $contest_meta_key = '_contest_name';
    $contest_names = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND p.post_type = 'dethi_baikiemtra'
             ORDER BY pm.meta_value",
            $contest_meta_key
        )
    );
    
    $current_contest = isset($_GET['contest_filter']) ? sanitize_text_field($_GET['contest_filter']) : '';
    $leaderboard_data = [];

    // Nếu không có cuộc thi nào được chọn qua GET, hãy tìm cuộc thi gần đây nhất để hiển thị theo mặc định.
    if (empty($current_contest) && !empty($contest_names)) {
        $latest_contest_name = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.meta_value
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'dethi_baikiemtra'
            AND pm.meta_key = %s
            ORDER BY p.post_date DESC
            LIMIT 1",
            $contest_meta_key
        ));
        if ($latest_contest_name) {
            $current_contest = $latest_contest_name;
        }
    }

    // --- Truy vấn dữ liệu bảng xếp hạng nếu có cuộc thi được chọn ---
    if (!empty($current_contest)) {
        $submissions_table = $wpdb->prefix . 'lb_test_submissions';
        $contestants_table = $wpdb->prefix . 'lb_test_contestants';
        $posts_table = $wpdb->posts;
        $postmeta_table = $wpdb->postmeta;

        $leaderboard_data = $wpdb->get_results($wpdb->prepare(
            "SELECT
                c.contestant_id,
                c.display_name,
                ROUND(AVG(s.score), 2) as average_score,
                COUNT(s.submission_id) as tests_taken
            FROM
                {$submissions_table} s
            INNER JOIN
                {$contestants_table} c ON s.contestant_id = c.contestant_id
            INNER JOIN
                {$posts_table} p ON s.test_id = p.ID
            INNER JOIN
                {$postmeta_table} pm ON p.ID = pm.post_id
            WHERE
                p.post_type = 'dethi_baikiemtra'
                AND pm.meta_key = %s
                AND pm.meta_value = %s
                AND s.status = 'graded'
            GROUP BY
                s.contestant_id
            ORDER BY
                average_score DESC, tests_taken DESC",
            $contest_meta_key,
            $current_contest
        ));
    }

    ?>
    <style> /* Chỉ giữ lại các style đặc thù cho trang này */
        .gdv-rank { font-size: 1.2em; font-weight: bold; text-align: center; }
        .gdv-rank-1 { color: #d4af37; } /* Gold */
        .gdv-rank-2 { color: #c0c0c0; } /* Silver */
        .gdv-rank-3 { color: #cd7f32; } /* Bronze */
    </style>

    <div class="gdv-container">
        <div class="gdv-main-tabs">
            <a href="<?php echo esc_url(get_site_url(null, '/chamdiem/')); ?>" class="gdv-main-tab">Chấm Bài & Lịch Sử</a>
            <a href="<?php echo esc_url(get_site_url(null, '/code/')); ?>" class="gdv-main-tab">Danh Sách Đề Thi</a>
            <a href="<?php echo esc_url(get_site_url(null, '/bxh/')); ?>" class="gdv-main-tab active">Bảng Xếp Hạng</a>
            <a href="<?php echo esc_url(site_url('/hosothisinh/')); ?>" class="gdv-main-tab">Hồ sơ Thí sinh</a>
        </div>

        <div class="gdv-header">
            <h1>Bảng xếp hạng</h1>
            <button class="gdv-button" style="background-color: var(--gdv-status-ready-text); opacity: 0.5;" disabled>Xuất Excel</button>
        </div>

        <div class="gdv-toolbar">
            <form method="get" action="" class="gdv-filter-form">
                <label for="contest_filter">Chọn cuộc thi:</label>
                <select name="contest_filter" id="contest_filter">
                    <option value="">-- Vui lòng chọn --</option>
                    <?php foreach ($contest_names as $name) : ?>
                        <option value="<?php echo esc_attr($name); ?>" <?php selected($current_contest, $name); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="gdv-button">Xem</button>
            </form>
        </div>

        <?php if (!empty($current_contest)) : ?>
            <?php if (!empty($leaderboard_data)) : ?>
                <?php
                // --- Chuẩn bị dữ liệu cho biểu đồ (Top 10) ---
                $chart_data = array_slice($leaderboard_data, 0, 10);
                $chart_labels = wp_list_pluck($chart_data, 'display_name');
                $chart_scores = wp_list_pluck($chart_data, 'average_score');

                // --- Mảng màu sắc cho biểu đồ, đồng bộ với màu rank ---
                $background_colors = [
                    'rgba(212, 175, 55, 0.6)', // Gold for Rank 1
                    'rgba(192, 192, 192, 0.6)', // Silver for Rank 2
                    'rgba(205, 127, 50, 0.6)',  // Bronze for Rank 3
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(83, 102, 255, 0.6)',
                    'rgba(40, 159, 64, 0.6)',
                ];
                $border_colors = [
                    'rgba(212, 175, 55, 1)',
                    'rgba(192, 192, 192, 1)',
                    'rgba(205, 127, 50, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(83, 102, 255, 1)',
                    'rgba(40, 159, 64, 1)',
                ];
                ?>
                <div class="gdv-chart-wrapper" style="background: var(--gdv-white); padding: 20px; border-radius: 12px; border: 1px solid var(--gdv-border); margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Top 10 Thí sinh</h2>
                    <div style="position: relative; height:400px;">
                        <canvas id="leaderboardChart"></canvas>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('leaderboardChart').getContext('2d');
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
                            <?php $rank = 1; foreach ($leaderboard_data as $row) : ?>
                                <tr class="rank-row-<?php echo $rank; ?>">
                                    <td class="gdv-rank gdv-rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>"><?php echo $rank; ?></td>
                                    <td><strong><a href="<?php echo esc_url(site_url('/hosothisinh/?contestant_id=' . $row->contestant_id)); ?>" class="gdv-action-link"><?php echo esc_html($row->display_name); ?></a></strong></td>
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
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}