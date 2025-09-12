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
                        <?php if (!empty($leaderboard_data)) : ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($leaderboard_data as $row) : ?>
                                <tr class="rank-row-<?php echo $rank; ?>">
                                    <td class="gdv-rank gdv-rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                        <?php echo $rank; ?>
                                    </td>
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url(site_url('/hosothisinh/?contestant_id=' . $row->contestant_id)); ?>" class="gdv-action-link"><?php echo esc_html($row->display_name); ?></a>
                                        </strong>
                                    </td>
                                    <td style="text-align: center;"><?php echo intval($row->tests_taken); ?></td>
                                    <td style="text-align: right; font-weight: bold; font-size: 1.1em; color: var(--gdv-primary);">
                                        <?php echo esc_html($row->average_score); ?>
                                    </td>
                                </tr>
                                <?php $rank++; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px;">Chưa có dữ liệu chấm bài cho cuộc thi này.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}