<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * SHORTCODE FOR CONTESTANT PROFILE PAGE
 * Shortcode: [ho_so_thi_sinh]
 * ===================================================================
 */
add_shortcode('ho_so_thi_sinh', 'lb_render_contestant_profile_page');

function lb_render_contestant_profile_page() {
    if (!current_user_can('grade_submissions')) {
        return '<p>Bạn không có quyền truy cập trang này.</p>';
    }

    // Nạp CSS chung của dashboard
    wp_enqueue_style('lb-test-grader-dashboard-style', LB_TEST_PLUGIN_URL . 'assets/css/grader-dashboard.css', array(), '1.0.0');

    ob_start();

    $contestant_id = isset($_GET['contestant_id']) ? intval($_GET['contestant_id']) : 0;

    if (!$contestant_id) {
        echo '<div class="gdv-container"><p class="gdv-notice error">Không tìm thấy ID thí sinh.</p></div>';
        return ob_get_clean();
    }

    global $wpdb;
    $contestants_table = $wpdb->prefix . 'lb_test_contestants';
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $posts_table = $wpdb->posts;

    // Lấy thông tin thí sinh
    $contestant = $wpdb->get_row($wpdb->prepare("SELECT * FROM $contestants_table WHERE contestant_id = %d", $contestant_id));

    if (!$contestant) {
        echo '<div class="gdv-container"><p class="gdv-notice error">Không tìm thấy thông tin cho thí sinh này.</p></div>';
        return ob_get_clean();
    }

    // Lấy tất cả bài làm của thí sinh
    $submissions = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, p.post_title
         FROM $submissions_table s
         LEFT JOIN $posts_table p ON s.test_id = p.ID
         WHERE s.contestant_id = %d AND s.status = 'graded'
         ORDER BY s.end_time DESC",
        $contestant_id
    ));

    // Tối ưu hóa: Lấy tổng số câu hỏi cho các bài đã làm
    $submission_ids = wp_list_pluck($submissions, 'submission_id');
    $question_counts = [];
    if (!empty($submission_ids)) {
        $answers_table = $wpdb->prefix . 'lb_test_answers';
        $results = $wpdb->get_results("SELECT submission_id, COUNT(answer_id) as total FROM {$answers_table} WHERE submission_id IN (" . implode(',', array_map('intval', $submission_ids)) . ") GROUP BY submission_id");
        foreach ($results as $row) {
            $question_counts[$row->submission_id] = $row->total;
        }
    }
    ?>
    <div class="gdv-container">
        <div style="margin-bottom: 20px;">
            <a href="<?php echo esc_url(wp_get_referer() ?: get_site_url(null, '/bxh/')); ?>">&larr; Quay lại</a>
        </div>

        <div class="gdv-header">
            <h1>Hồ sơ thí sinh: <?php echo esc_html($contestant->display_name); ?></h1>
        </div>
        <p><strong>Số điện thoại:</strong> <?php echo esc_html($contestant->phone_number); ?></p>

        <div class="gdv-table-wrapper">
            <table class="gdv-table">
                <thead><tr><th>Bài thi</th><th>Ngày làm bài</th><th>Điểm số</th><th style="text-align: center;">Hành động</th></tr></thead>
                <tbody>
                    <?php if (!empty($submissions)) : foreach ($submissions as $sub) : ?>
                        <?php
                        $total_questions = $question_counts[$sub->submission_id] ?? 0;
                        $review_url = add_query_arg(['submission_id' => $sub->submission_id, 'view_mode' => 'review'], get_site_url(null, '/chamdiem/'));
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($sub->post_title); ?></strong></td>
                            <td><?php echo wp_date('d/m/Y, H:i', strtotime($sub->end_time)); ?></td>
                            <td><strong><?php echo intval($sub->score) . '/' . $total_questions; ?></strong></td>
                            <td style="text-align: center;"><a href="<?php echo esc_url($review_url); ?>" class="gdv-action-link">Xem lại bài làm</a></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px;">Thí sinh này chưa có bài làm nào được chấm.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}