<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * TẠO TRANG "TẠO ĐỀ HÀNG LOẠT" TRONG MENU ADMIN
 * ===================================================================
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=dethi_baikiemtra', // Gắn vào menu "Bài kiểm tra"
        'Tạo Đề Hàng Loạt',
        'Tạo Đề Hàng Loạt',
        'manage_options',
        'lb-test-bulk-generator',
        'lb_test_render_bulk_generator_page'
    );
});

/**
 * ===================================================================
 * HÀM XỬ LÝ VÀ HIỂN THỊ TRANG CÔNG CỤ
 * ===================================================================
 */
function lb_test_render_bulk_generator_page() {
    echo '<div class="wrap">';
    echo '<h1>Công cụ Tạo Đề Hàng Loạt</h1>';

    // --- XỬ LÝ FORM KHI SUBMIT ---
    if (isset($_POST['lb_bulk_generate_nonce']) && wp_verify_nonce($_POST['lb_bulk_generate_nonce'], 'lb_bulk_generate_action')) {
        
        // Lấy và làm sạch dữ liệu
        $contest_name = sanitize_text_field($_POST['contest_name']);
        $num_tests = intval($_POST['num_tests']);
        $num_questions = intval($_POST['num_questions']);
        $time_limit = intval($_POST['time_limit']);
        $question_pool_ids = isset($_POST['question_pool_ids']) ? array_map('intval', $_POST['question_pool_ids']) : [];
        $batch_id = uniqid('contest_'); // Tạo ID duy nhất cho đợt tạo đề này

        // Validation
        if (empty($contest_name) || $num_tests <= 0 || $num_questions <= 0 || empty($question_pool_ids) || $num_questions > count($question_pool_ids)) {
            echo '<div class="notice notice-error"><p>Lỗi: Vui lòng điền đầy đủ thông tin. Số câu hỏi mỗi đề phải nhỏ hơn hoặc bằng tổng số câu trong ngân hàng đã chọn.</p></div>';
        } else {
            $generated_count = 0;
            for ($i = 0; $i < $num_tests; $i++) {
                shuffle($question_pool_ids);
                $random_questions_for_child = array_slice($question_pool_ids, 0, $num_questions);

                $child_post_title = $contest_name . ' - Đề #' . ($i + 1);
                $child_id = wp_insert_post([
                    'post_type'    => 'dethi_baikiemtra',
                    'post_title'   => $child_post_title,
                    'post_status'  => 'publish',
                ]);

                if ($child_id > 0) {
                    update_post_meta($child_id, 'lb_test_ma_de', strtoupper(substr(md5($batch_id . '-' . $child_id), 0, 8)));
                    update_post_meta($child_id, 'lb_test_thoi_gian', $time_limit);
                    update_post_meta($child_id, 'lb_test_danh_sach_cau_hoi', $random_questions_for_child);
                    update_post_meta($child_id, 'lb_test_type', 'fixed');
                    update_post_meta($child_id, '_contest_name', $contest_name); // Meta để lọc
                    update_post_meta($child_id, '_batch_id', $batch_id); // Meta để nhận diện đợt
                    $generated_count++;
                }
            }
            echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> Đã tạo ra ' . $generated_count . ' bài kiểm tra thuộc cuộc thi "' . esc_html($contest_name) . '".</p></div>';
        }
    }

    // --- HIỂN THỊ FORM ---
    ?>
    <p>Sử dụng công cụ này để tạo hàng loạt các "Bài kiểm tra" riêng lẻ từ một ngân hàng câu hỏi do bạn chỉ định.</p>
    <form method="post" action="">
        <?php wp_nonce_field('lb_bulk_generate_action', 'lb_bulk_generate_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="contest_name">Tên Cuộc thi / Đợt kiểm tra</label></th>
                <td><input type="text" id="contest_name" name="contest_name" class="regular-text" required>
                <p class="description">Tên này sẽ được dùng để lọc và quản lý các đề đã tạo.</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="num_tests">Số lượng đề thi muốn tạo</label></th>
                <td><input type="number" id="num_tests" name="num_tests" class="small-text" required></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="num_questions">Số lượng câu hỏi trong 1 đề</label></th>
                <td><input type="number" id="num_questions" name="num_questions" class="small-text" required></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="time_limit">Thời gian làm bài (phút)</label></th>
                <td><input type="number" id="time_limit" name="time_limit" class="small-text" required></td>
            </tr>
        </table>
        <hr>
        <h3>Ngân hàng câu hỏi</h3>
        <p>Chọn tất cả các câu hỏi bạn muốn đưa vào vòng quay ngẫu nhiên.</p>
        <p>
            <button type="button" class="button" id="select-all-questions-generator">Chọn tất cả</button>
            <button type="button" class="button" id="deselect-all-questions-generator">Bỏ chọn tất cả</button>
        </p>
        <div id="question-pool-checklist-generator" style="max-height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #fff;">
            <?php
            $all_questions = new WP_Query(['post_type' => 'dethi_cauhoi', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
            if ($all_questions->have_posts()) {
                while ($all_questions->have_posts()) {
                    $all_questions->the_post();
                    $q_id = get_the_ID();
                    echo '<p><label><input type="checkbox" name="question_pool_ids[]" value="' . $q_id . '"> #' . $q_id . ' - ' . esc_html(wp_strip_all_tags(get_the_content())) . '</label></p>';
                }
                wp_reset_postdata();
            } else { echo '<p>Chưa có câu hỏi nào. Vui lòng thêm câu hỏi trước.</p>'; }
            ?>
        </div>
        <script>jQuery(document).ready(function($) { $('#select-all-questions-generator').on('click', function() { $('#question-pool-checklist-generator input[type="checkbox"]').prop('checked', true); }); $('#deselect-all-questions-generator').on('click', function() { $('#question-pool-checklist-generator input[type="checkbox"]').prop('checked', false); }); });</script>
        <?php submit_button('Tạo Đề Hàng Loạt'); ?>
    </form>
    </div>
    <?php
}