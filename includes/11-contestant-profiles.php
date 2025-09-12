<?php
if (!defined('ABSPATH')) exit;


/**
 * ===================================================================
 * SHORTCODE ĐỂ HIỂN THỊ TRANG HỒ SƠ THÍ SINH Ở FRONTEND
 * Shortcode: [ho_so_thi_sinh]
 * ===================================================================
 */
function lb_test_render_contestant_profile_shortcode() {
    // Chỉ người có quyền 'grade_submissions' mới được xem
    if (!current_user_can('grade_submissions')) {
        return '<p>Bạn không có quyền truy cập trang này.</p>';
    }

    ob_start();
    ?>
    <div class="gdv-container">
        <div class="gdv-main-tabs">
            <a href="<?php echo esc_url(site_url('/chamdiem/')); ?>" class="gdv-main-tab">Chấm Bài & Lịch Sử</a>
            <a href="<?php echo esc_url(site_url('/code/')); ?>" class="gdv-main-tab">Danh Sách Đề Thi</a>
            <a href="<?php echo esc_url(site_url('/bxh/')); ?>" class="gdv-main-tab">Bảng Xếp Hạng</a>
            <a href="<?php echo esc_url(site_url('/hosothisinh/')); ?>" class="gdv-main-tab active">Hồ sơ Thí sinh</a>
        </div>
    <?php
    lb_test_render_contestant_profile_content();
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('ho_so_thi_sinh', 'lb_test_render_contestant_profile_shortcode');

function lb_test_render_contestant_profile_content() {
    // Kiểm tra xem có đang xem chi tiết một thí sinh không
    if (isset($_GET['contestant_id']) && is_numeric($_GET['contestant_id'])) {
        $contestant_id = intval($_GET['contestant_id']);
        lb_test_render_single_contestant_view($contestant_id);
    } else {
        lb_test_render_all_contestants_list();
    }
}

/**
 * Hiển thị danh sách tất cả thí sinh.
 */
function lb_test_render_all_contestants_list() {
    global $wpdb;
    $contestants_table = $wpdb->prefix . 'lb_test_contestants';
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';

    // Xử lý tìm kiếm
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where_clause = '';
    $params = [];
    if (!empty($search_term)) {
        $where_clause = " WHERE c.display_name LIKE %s OR c.phone_number LIKE %s ";
        $params[] = '%' . $wpdb->esc_like($search_term) . '%';
        $params[] = '%' . $wpdb->esc_like($search_term) . '%';
    }

    // Truy vấn để lấy danh sách thí sinh và đếm số bài đã làm
    $query = "
        SELECT c.contestant_id, c.display_name, c.phone_number, c.created_at, COUNT(s.submission_id) as submission_count
        FROM $contestants_table c
        LEFT JOIN $submissions_table s ON c.contestant_id = s.contestant_id
        $where_clause
        GROUP BY c.contestant_id
        ORDER BY c.display_name ASC
    ";

    $contestants = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

    ?>
    <div class="gdv-header">
        <h1>Hồ sơ Thí sinh</h1>
    </div>
    <p class="gdv-description">Danh sách tất cả các thí sinh đã tham gia làm bài.</p>

    <div class="gdv-toolbar">
        <form method="get" action="">
            <div class="gdv-search-box">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                <input type="search" name="s" placeholder="Tìm theo tên hoặc SĐT..." value="<?php echo esc_attr($search_term); ?>">
            </div>
        </form>
    </div>

    <?php

    if (empty($contestants)) {
        echo '<div class="gdv-table-wrapper" style="text-align: center; padding: 40px;">Không tìm thấy thí sinh nào.</div>';
        return;
    }
    ?>
    <div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>Thí sinh</th>
                <th style="text-align: center;">Số bài đã làm</th>
                <th>Ngày đăng ký</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contestants as $contestant) :
                $name_initial = mb_substr($contestant->display_name, 0, 1);
            ?>
                <tr>
                    <td>
                        <div class="gdv-avatar-cell">
                            <div class="gdv-avatar"><?php echo esc_html(strtoupper($name_initial)); ?></div>
                            <div class="gdv-avatar-info">
                                <div class="name"><?php echo esc_html($contestant->display_name); ?></div>
                                <div class="subtext"><?php echo esc_html($contestant->phone_number); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: center;"><?php echo esc_html($contestant->submission_count); ?></td>
                    <td><?php echo wp_date('d/m/Y', strtotime($contestant->created_at)); ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg('contestant_id', $contestant->contestant_id, get_permalink())); ?>" class="gdv-button secondary">Xem chi tiết</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php
}

/**
 * Hiển thị chi tiết một thí sinh và danh sách các bài đã làm.
 */
function lb_test_render_single_contestant_view($contestant_id) {
    global $wpdb;
    $contestants_table = $wpdb->prefix . 'lb_test_contestants';
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $posts_table = $wpdb->prefix . 'posts';

    // Lấy thông tin thí sinh
    $contestant = $wpdb->get_row($wpdb->prepare("SELECT * FROM $contestants_table WHERE contestant_id = %d", $contestant_id));

    if (!$contestant) {
        echo '<h1>Lỗi</h1><p>Không tìm thấy thí sinh.</p>';
        return;
    }

    // Lấy danh sách bài làm của thí sinh
    $submissions = $wpdb->get_results($wpdb->prepare("
        SELECT s.submission_id, s.test_id, s.status, s.score, s.end_time, p.post_title
        FROM $submissions_table s
        LEFT JOIN $posts_table p ON s.test_id = p.ID
        WHERE s.contestant_id = %d
        ORDER BY s.end_time DESC
    ", $contestant_id));

    $back_url = get_permalink(); // Lấy URL của trang hiện tại, không có query string
    ?>
    <div class="gdv-header">
        <h1 style="display: flex; align-items: center; gap: 10px;">
            <a href="<?php echo esc_url($back_url); ?>" class="gdv-button" style="padding: 8px 12px; font-size: 14px; text-decoration: none;">&larr; Quay lại</a>
            Hồ sơ: <?php echo esc_html($contestant->display_name); ?>
        </h1>
    </div>

    <div style="background: var(--gdv-white); padding: 20px; border-radius: 12px; border: 1px solid var(--gdv-border); margin-bottom: 20px;">
        <p><strong>Số điện thoại:</strong> <code><?php echo esc_html($contestant->phone_number); ?></code></p>
        <p><strong>Ngày đăng ký:</strong> <?php echo wp_date('d/m/Y H:i', strtotime($contestant->created_at)); ?></p>
    </div>

    <h2>Lịch sử làm bài</h2>
    <div class="gdv-table-wrapper"><table class="gdv-table">
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
            <?php if (empty($submissions)) : ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">Thí sinh này chưa làm bài thi nào.</td></tr>
            <?php else : ?>
                <?php foreach ($submissions as $sub) :
                    $grader_url = site_url('/chamdiem/?submission_id=' . $sub->submission_id);
                    $status_text = ($sub->status === 'graded') ? 'Đã chấm' : 'Cần chấm';
                    $action_text = ($sub->status === 'graded') ? 'Xem lại' : 'Chấm bài';
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($sub->post_title); ?></strong></td>
                        <td><span class="gdv-status gdv-status--<?php echo esc_attr($sub->status); ?>"><?php echo esc_html($status_text); ?></span></td>
                        <td><?php echo ($sub->status === 'graded') ? '<strong>' . intval($sub->score) . '</strong>' : '—'; ?></td>
                        <td><?php echo wp_date('d/m/Y, H:i', strtotime($sub->end_time)); ?></td>
                        <td><a href="<?php echo esc_url($grader_url); ?>" class="gdv-action-link" target="_blank"><?php echo $action_text; ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php
}