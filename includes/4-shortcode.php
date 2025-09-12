<?php
if (!defined('ABSPATH')) exit;

add_shortcode('lam_bai_kiem_tra', function() {
    ob_start();

    // Lấy thông tin từ URL (sau khi submit form)
    $ma_de = isset($_GET['ma_de']) ? sanitize_text_field($_GET['ma_de']) : '';
    $phone_number = isset($_GET['phone_number']) ? sanitize_text_field($_GET['phone_number']) : '';
    $submitter_name = isset($_GET['submitter_name']) ? sanitize_text_field($_GET['submitter_name']) : '';

    // Nếu người dùng đã đăng nhập, ưu tiên tên tài khoản của họ
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $submitter_name = $user->display_name;
    }

    // Nếu có mã đề, tiến hành tìm và hiển thị bài thi
    if (!empty($ma_de)) {
        $test_query = new WP_Query([
            'post_type' => 'dethi_baikiemtra',
            'meta_key' => 'lb_test_ma_de',
            'meta_value' => $ma_de,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);

        if ($test_query->have_posts()) {
            $test_query->the_post();
            $test_id = get_the_ID();
            
            if (empty($submitter_name) || empty($phone_number)) {
                // Nếu chưa có tên, hiển thị lại form ban đầu với thông báo lỗi
                display_initial_form(true, false, $ma_de, $phone_number); 
            } else {
                // Nếu có đủ thông tin, hiển thị bài thi
                display_test_content($test_id, $ma_de, $submitter_name, $phone_number);
            }
            wp_reset_postdata();

        } else {
            // Nếu mã đề sai, hiển thị lại form với thông báo lỗi
            display_initial_form(false, true);
        }

    } else {
        // Nếu chưa có mã đề, hiển thị form ban đầu
        display_initial_form();
    }
    
    return ob_get_clean();
});

function display_initial_form($name_error = false, $code_error = false, $ma_de = '', $phone_number = '') {
    ?>
    <div class="lb-test-code-input-form">
        <form method="GET" action="">
            <label for="ma_de">Nhập mã đề thi:</label>
            <input type="text" name="ma_de" id="ma_de" value="<?php echo esc_attr($ma_de); ?>" required>
            <?php if ($code_error): ?>
                <p class="error-message">Mã đề thi không hợp lệ hoặc đã được sử dụng.</p>
            <?php endif; ?>

            <?php if (!is_user_logged_in()): ?>
                <label for="phone_number">Nhập số điện thoại:</label>
                <input type="tel" name="phone_number" id="phone_number" value="<?php echo esc_attr($phone_number); ?>" required>

                <label for="submitter_name">Nhập tên của bạn:</label>
                <input type="text" name="submitter_name" id="submitter_name" required>
                <?php if ($name_error): ?>
                    <p class="error-message">Vui lòng nhập đầy đủ thông tin.</p>
                <?php endif; ?>
                <p id="phone_check_msg" style="color: #0073aa; font-style: italic;"></p>
            <?php else: ?>
                <?php $user = wp_get_current_user(); ?>
                <input type="hidden" name="phone_number" value="<?php echo esc_attr($user->user_email); // Dùng email làm SĐT cho user đã đăng nhập ?>">
                <input type="hidden" name="submitter_name" value="<?php echo esc_attr($user->display_name); ?>">
            <?php endif; ?>

            <button type="submit">Bắt đầu làm bài</button>
        </form>
    </div>
    <?php
}

function display_test_content($test_id, $ma_de, $submitter_name, $phone_number) {
    $thoi_gian = get_post_meta($test_id, 'lb_test_thoi_gian', true);
    $question_ids = get_post_meta($test_id, 'lb_test_danh_sach_cau_hoi', true);
    $question_ids = is_array($question_ids) ? $question_ids : [];

    echo '<h1>' . get_the_title($test_id) . '</h1>';
    echo '<h3>Thí sinh: ' . esc_html($submitter_name) . '</h3>';
    
    if (!empty($thoi_gian)) {
        echo '<div id="lb-test-timer" data-time="' . intval($thoi_gian) . '"></div>';
    }
    
    if (!empty($question_ids)) {
        echo '<form id="lb-test-form">';
        wp_nonce_field('lb_test_submit_nonce', 'nonce');
        echo '<input type="hidden" name="test_id" value="' . $test_id . '">';
        echo '<input type="hidden" name="ma_de" value="' . esc_attr($ma_de) . '">';
        echo '<input type="hidden" name="submitter_name" value="' . esc_attr($submitter_name) . '">';
        echo '<input type="hidden" name="phone_number" value="' . esc_attr($phone_number) . '">';

        $count = 1;
        foreach ($question_ids as $q_id) {
            $question = get_post($q_id);
            if ($question) {
                $loai_cau_hoi = get_post_meta($q_id, 'lb_test_loai_cau_hoi', true);

                echo '<div class="lb-test-question-item">';
                echo '<h4>Câu ' . $count++ . ': ' . esc_html($question->post_content) . '</h4>';
                
                // Luôn gửi kèm loại câu hỏi để backend xử lý
                echo '<input type="hidden" name="answers[' . $q_id . '][type]" value="' . esc_attr($loai_cau_hoi) . '">';
                
                if ($loai_cau_hoi == 'trac_nghiem') {
                    $lua_chon = get_post_meta($q_id, 'lb_test_lua_chon', true);
                    $lua_chon = is_array($lua_chon) ? $lua_chon : [];
                    if (!empty($lua_chon)) {
                        foreach ($lua_chon as $char => $text) {
                            if (!empty($text)) {
                                echo '<label><input type="radio" name="answers[' . $q_id . '][answer]" value="' . esc_attr($char) . '"> ' . esc_html($char) . '. ' . esc_html($text) . '</label>';
                            }
                        }
                    }
                } elseif ($loai_cau_hoi == 'tu_luan') {
                    echo '<textarea name="answers[' . $q_id . '][answer]" rows="5" placeholder="Nhập câu trả lời của bạn"></textarea>';
                }
                echo '</div>';
            }
        }
        echo '<button type="submit" id="submit-test-btn">Nộp bài</button>';
        echo '</form>';
        echo '<div id="test-result-message"></div>';

    } else {
        echo '<p class="lb-test-error-message">Bài kiểm tra này chưa có câu hỏi nào.</p>';
    }
}