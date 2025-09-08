<?php
if (!defined('ABSPATH')) exit;

function lb_test_render_shortcode() {
    ob_start();

    if (isset($_GET['ma_de']) && !empty($_GET['ma_de'])) {
        $ma_de = sanitize_text_field($_GET['ma_de']);
        
        $args = [
            'post_type' => 'dethi_baikiemtra',
            'meta_key' => 'lb_test_ma_de',
            'meta_value' => $ma_de,
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ];
        $test_query = new WP_Query($args);

        if ($test_query->have_posts()) {
            $test_query->the_post();
            $test_id = get_the_ID();
            $thoi_gian = get_post_meta($test_id, 'lb_test_thoi_gian', true);
            $test_type = get_post_meta($test_id, 'lb_test_type', true) ?: 'fixed';
            $question_ids = [];

            if ($test_type === 'random') {
                $random_count = get_post_meta($test_id, 'lb_test_random_count', true);
                $random_cat = get_post_meta($test_id, 'lb_test_random_cat', true);
                
                $query_args = [
                    'post_type' => 'dethi_cauhoi',
                    'posts_per_page' => $random_count ?: 10,
                    'orderby' => 'rand',
                    'fields' => 'ids'
                ];

                if ($random_cat > 0) {
                    // Lọc theo một Môn học cụ thể
                    $query_args['tax_query'] = [[
                        'taxonomy' => 'mon_hoc',
                        'terms' => $random_cat,
                    ]];
                } elseif ($random_cat == -1) {
                    // Lọc những câu hỏi CHƯA CÓ Môn học
                    $query_args['tax_query'] = [[
                        'taxonomy' => 'mon_hoc',
                        'operator' => 'NOT EXISTS',
                    ]];
                }
                // Nếu $random_cat = 0 (Tất cả Môn học), thì không cần thêm tax_query

                $question_ids = get_posts($query_args);

            } else { // 'fixed'
                $question_ids = get_post_meta($test_id, 'lb_test_danh_sach_cau_hoi', true);
            }

            echo '<h1>' . get_the_title() . '</h1>';
            
            $submitter_name = get_post_meta($test_id, 'lb_test_ten_nguoi_lam_bai', true);
            echo '<h3>Thí sinh: ' . esc_html($submitter_name) . '</h3>';
            if (!empty($thoi_gian) && is_numeric($thoi_gian)) {
                 echo '<div id="lb-test-timer" style="position:fixed; top:10px; right:10px; background: #fff; border: 2px solid red; padding: 10px; font-size: 1.2em; z-index: 999;" data-time="' . intval($thoi_gian) . '"></div>';
            }

            if (!empty($question_ids)) {
                echo '<form id="lb-test-form">';
                wp_nonce_field('lb_test_submit_nonce', 'nonce');
                echo '<input type="hidden" name="test_id" value="' . $test_id . '">';
                echo '<input type="hidden" name="ma_de" value="' . esc_attr($ma_de) . '">';
                echo '<input type="hidden" name="submitter_name" value="' . esc_attr($submitter_name) . '">';

                $count = 1;
                foreach ($question_ids as $q_id) {
                    $question = get_post($q_id);
                    $loai_cau_hoi = get_post_meta($q_id, 'lb_test_loai_cau_hoi', true);

                    echo '<div class="question-block" style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">';
                    echo '<h4>Câu ' . $count . ':</h4>';
                    echo '<div>' . wpautop($question->post_content) . '</div>';
                    
                    if ($loai_cau_hoi === 'trac_nghiem') {
                        $lua_chon = get_post_meta($q_id, 'lb_test_lua_chon', true);
                        if (!empty($lua_chon) && is_array($lua_chon)) {
                            $keys = array_keys($lua_chon);
                            shuffle($keys);
                            $shuffled_lua_chon = [];
                            foreach ($keys as $key) {
                                $shuffled_lua_chon[$key] = $lua_chon[$key];
                            }
                            echo '<ul style="list-style-type: none; padding-left: 0;">';
                            foreach ($shuffled_lua_chon as $key => $value) {
                                 echo '<li><label><input type="radio" name="answers[' . $q_id . ']" value="' . esc_attr($key) . '"> ' . esc_html($value) . '</label></li>';
                            }
                            echo '</ul>';
                        }
                    } else {
                        echo '<textarea name="answers[' . $q_id . ']" rows="5" class="widefat" style="width: 100%;"></textarea>';
                    }
                    echo '</div>';
                    $count++;
                }
                echo '<button type="submit" id="submit-test-btn">Nộp bài</button>';
                echo '</form>';
                echo '<div id="test-result-message"></div>';
            } else {
                 echo '<p>Bài kiểm tra này không có câu hỏi nào hoặc không thể tạo đề ngẫu nhiên.</p>';
            }
            wp_reset_postdata();
        } else {
            echo '<p>Không tìm thấy bài kiểm tra với mã đề này. Vui lòng thử lại.</p>';
            display_ma_de_form();
        }
    } else {
        display_ma_de_form();
    }
    return ob_get_clean();
}
add_shortcode('lam_bai_kiem_tra', 'lb_test_render_shortcode');

function display_ma_de_form() {
    echo '
    <form method="GET" action="">
        <label for="ma_de">Nhập mã đề thi:</label>
        <input type="text" name="ma_de" id="ma_de" required>
        <button type="submit">Bắt đầu làm bài</button>
    </form>
    ';
}