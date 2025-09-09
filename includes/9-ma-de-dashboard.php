<?php
/**
 * File này tạo một trang riêng cho Giám khảo để xem danh sách mã đề.
 * Shortcode: [danh_sach_ma_de]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Shortcode [danh_sach_ma_de] để hiển thị giao diện danh sách mã đề.
 */
function lb_ma_de_dashboard_shortcode() {
    // Chỉ cho phép người có quyền 'grade_submissions' xem trang này.
    if ( ! is_user_logged_in() || ! current_user_can( 'grade_submissions' ) ) {
        return '<p>Bạn không có quyền truy cập trang này.</p>';
    }

    ob_start();

    // **QUAN TRỌNG**: Thay 'trang-cham-bai' bằng slug (đường dẫn) của trang chứa shortcode [grader_dashboard] của bạn.
    // Ví dụ: nếu URL là example.com/giam-khao/cham-bai, bạn sẽ điền '/giam-khao/cham-bai/'.
    $grader_dashboard_url = get_site_url(null, '/chamdiem/'); 

    ?>
    <style>
        .dsmd-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-family: sans-serif; }
        .dsmd-table th, .dsmd-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .dsmd-table th { background-color: #f2f2f2; }
        .status-dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .status-ready { background-color: #28a745; } /* Green */
        .status-submitted { background-color: #ffc107; } /* Orange */
        .status-graded { background-color: #007bff; } /* Blue */
        .copy-ma-de { cursor: pointer; border: none; background: #eee; padding: 3px 6px; border-radius: 3px; margin-left: 5px; }
        .copy-ma-de:hover { background: #ddd; }
    </style>

    <div class="ma-de-dashboard">
        <h2>Danh Sách Đề Thi</h2>
        <p>Xem trạng thái các mã đề, lấy mã để giao cho thí sinh và đi đến trang chấm bài.</p>
        <?php lb_render_ma_de_list_table($grader_dashboard_url); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(event) {
                if (event.target.matches('.copy-ma-de')) {
                    const button = event.target;
                    const maDe = button.getAttribute('data-code');
                    navigator.clipboard.writeText(maDe).then(() => {
                        const originalText = button.innerText;
                        button.innerText = 'Đã chép!';
                        setTimeout(() => { button.innerText = originalText; }, 1500);
                    });
                }
            });
        });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('danh_sach_ma_de', 'lb_ma_de_dashboard_shortcode');


/**
 * Hàm render bảng danh sách mã đề.
 */
function lb_render_ma_de_list_table($grader_dashboard_url) {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';

    $args = [
        'post_type' => 'dethi_baikiemtra',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
        'orderby' => 'post_title',
        'order' => 'ASC',
    ];
    $tests_query = new WP_Query($args);

    if ($tests_query->have_posts()) {
        ?>
        <table class="dsmd-table">
            <thead>
                <tr>
                    <th>Tên Bài kiểm tra</th>
                    <th>Mã đề</th>
                    <th>Trạng thái</th>
                    <th>Người làm bài</th>
                    <th>Thời gian nộp</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($tests_query->have_posts()) {
                    $tests_query->the_post();
                    $test_id = get_the_ID();
                    $ma_de = get_post_meta($test_id, 'lb_test_ma_de', true);
                    $post_status = get_post_status($test_id);

                    $status_html = '';
                    $submitter_html = '<td></td>';
                    $end_time_html = '<td></td>';
                    $action_html = '<td></td>';
                    
                    if ($post_status === 'publish') {
                        $status_html = '<td><span class="status-dot status-ready"></span>Sẵn sàng</td>';
                    } else { // draft status
                        $submission = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM $submissions_table WHERE test_id = %d",
                            $test_id
                        ));

                        if ($submission) {
                            $submitter_html = '<td>' . esc_html($submission->submitter_name) . '</td>';
                            $end_time_html = '<td>' . date('d/m/Y H:i', strtotime($submission->end_time)) . '</td>';
                            $view_url = add_query_arg('submission_id', $submission->submission_id, $grader_dashboard_url);

                            if ($submission->status === 'graded') {
                                $status_html = '<td><span class="status-dot status-graded"></span>Đã chấm</td>';
                                $action_html = '<td><a href="' . esc_url($view_url) . '">Xem lại</a></td>';
                            } else {
                                $status_html = '<td><span class="status-dot status-submitted"></span>Đã có người làm</td>';
                                $action_html = '<td><a href="' . esc_url($view_url) . '"><strong>Đi đến chấm bài</strong></a></td>';
                            }
                        } else {
                            $status_html = '<td><span class="status-dot"></span>Không có dữ liệu</td>';
                        }
                    }
                    ?>
                    <tr>
                        <td><?php the_title(); ?></td>
                        <td>
                            <code><?php echo esc_html($ma_de); ?></code>
                            <button class="copy-ma-de" data-code="<?php echo esc_attr($ma_de); ?>" title="Chép mã đề">📋</button>
                        </td>
                        <?php echo $status_html; ?>
                        <?php echo $submitter_html; ?>
                        <?php echo $end_time_html; ?>
                        <?php echo $action_html; ?>
                    </tr>
                    <?php
                }
                wp_reset_postdata();
                ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p>Chưa có bài kiểm tra nào được tạo.</p>';
    }
}
?>
