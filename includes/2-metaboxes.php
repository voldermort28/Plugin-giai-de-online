<?php
if (!defined('ABSPATH')) exit;

// Đăng ký các meta box
function lb_test_add_meta_boxes() {
    // Meta box cho Câu hỏi (dethi_cauhoi)
    add_meta_box('lb_test_cauhoi_details', 'Chi tiết câu hỏi', 'lb_test_render_cauhoi_metabox', 'dethi_cauhoi', 'normal', 'high');
    
    // Meta box cho Bài kiểm tra (dethi_baikiemtra)
    add_meta_box('lb_test_baikiemtra_details', 'Cấu hình bài kiểm tra', 'lb_test_render_baikiemtra_metabox', 'dethi_baikiemtra', 'normal', 'high');
    
    // Meta box cho Nhóm Đề Thi (dethi_nhomde)
    add_meta_box('lb_test_nhomde_details', 'Quy tắc tạo đề', 'lb_test_render_nhomde_metabox', 'dethi_nhomde', 'normal', 'high');
    add_meta_box('lb_test_nhomde_actions', 'Hành động & Quản lý', 'lb_test_render_nhomde_actions_metabox', 'dethi_nhomde', 'side', 'high');
}
add_action('add_meta_boxes', 'lb_test_add_meta_boxes');


// ===================================================================
// HÀM RENDER CÁC META BOX
// ===================================================================

/**
 * Hàm render meta box cho CPT "Câu hỏi"
 */
function lb_test_render_cauhoi_metabox($post) {
    wp_nonce_field('lb_test_save_meta_data', 'lb_test_nonce');

    $loai_cau_hoi = get_post_meta($post->ID, 'lb_test_loai_cau_hoi', true);
    $dap_an = get_post_meta($post->ID, 'lb_test_dap_an', true);
    $lua_chon = get_post_meta($post->ID, 'lb_test_lua_chon', true);
    $lua_chon = is_array($lua_chon) ? $lua_chon : [];
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="lb_test_loai_cau_hoi">Loại câu hỏi</label></th>
            <td>
                <select name="lb_test_loai_cau_hoi" id="lb_test_loai_cau_hoi">
                    <option value="tu_luan" <?php selected($loai_cau_hoi, 'tu_luan'); ?>>Tự luận</option>
                    <option value="trac_nghiem" <?php selected($loai_cau_hoi, 'trac_nghiem'); ?>>Trắc nghiệm</option>
                </select>
            </td>
        </tr>
    </table>
    
    <div id="lb_test_trac_nghiem_fields" style="<?php echo ($loai_cau_hoi !== 'trac_nghiem') ? 'display:none;' : ''; ?>">
        <h4>Các lựa chọn và đáp án đúng</h4>
        <table class="form-table">
            <?php for ($i = 0; $i < 4; $i++) : $char = chr(65 + $i); ?>
            <tr valign="top">
                <th scope="row"><label for="lua_chon_<?php echo $char; ?>">Lựa chọn <?php echo $char; ?></label></th>
                <td><input type="text" name="lb_test_lua_chon[<?php echo $char; ?>]" id="lua_chon_<?php echo $char; ?>" value="<?php echo esc_attr($lua_chon[$char] ?? ''); ?>" class="widefat" /></td>
            </tr>
            <?php endfor; ?>
            <tr valign="top">
                <th scope="row"><label>Đáp án đúng</label></th>
                <td><?php for ($i = 0; $i < 4; $i++) : $char = chr(65 + $i); ?><label style="margin-right: 15px;"><input type="radio" name="lb_test_dap_an" value="<?php echo $char; ?>" <?php checked($dap_an, $char); ?>> <?php echo $char; ?></label><?php endfor; ?></td>
            </tr>
        </table>
    </div>
    
    <div id="lb_test_tu_luan_fields" style="<?php echo ($loai_cau_hoi !== 'tu_luan') ? 'display:none;' : ''; ?>">
        <h4>Đáp án mẫu (Tự luận)</h4>
        <textarea name="lb_test_dap_an_tuluan" class="widefat" rows="5"><?php echo ($loai_cau_hoi === 'tu_luan') ? esc_textarea($dap_an) : ''; ?></textarea>
    </div>
    <?php
}

/**
 * Hàm render meta box cho CPT "Bài kiểm tra"
 */
function lb_test_render_baikiemtra_metabox($post) {
    wp_nonce_field('lb_test_save_meta_data', 'lb_test_nonce');
    $ma_de = get_post_meta($post->ID, 'lb_test_ma_de', true);
    $ten_nguoi_lam_bai = get_post_meta($post->ID, 'lb_test_ten_nguoi_lam_bai', true);
    $thoi_gian = get_post_meta($post->ID, 'lb_test_thoi_gian', true);
    $test_type = get_post_meta($post->ID, 'lb_test_type', true) ?: 'fixed';
    $danh_sach_cau_hoi = get_post_meta($post->ID, 'lb_test_danh_sach_cau_hoi', true);
    $danh_sach_cau_hoi = is_array($danh_sach_cau_hoi) ? $danh_sach_cau_hoi : [];
    $random_count = get_post_meta($post->ID, 'lb_test_random_count', true);
    $random_cat = get_post_meta($post->ID, 'lb_test_random_cat', true);
    ?>
    <table class="form-table">
        <tr valign="top"><th scope="row"><label for="lb_test_ma_de">Mã đề</label></th><td><input type="text" id="lb_test_ma_de" name="lb_test_ma_de" value="<?php echo esc_attr($ma_de); ?>" class="regular-text"><p class="description">Nếu để trống, hệ thống sẽ tự sinh khi lưu.</p></td></tr>
        <tr valign="top"><th scope="row"><label for="lb_test_ten_nguoi_lam_bai">Tên Người làm bài (Mặc định)</label></th><td><input type="text" id="lb_test_ten_nguoi_lam_bai" name="lb_test_ten_nguoi_lam_bai" value="<?php echo esc_attr($ten_nguoi_lam_bai); ?>" class="widefat"></td></tr>
        <tr valign="top"><th scope="row"><label for="lb_test_thoi_gian">Thời gian làm bài (phút)</label></th><td><input type="number" id="lb_test_thoi_gian" name="lb_test_thoi_gian" value="<?php echo esc_attr($thoi_gian); ?>" class="small-text"></td></tr>
        <tr valign="top">
            <th scope="row">Loại đề thi</th>
            <td>
                <label><input type="radio" name="lb_test_type" value="fixed" <?php checked($test_type, 'fixed'); ?>> Cố định</label><br>
                <label><input type="radio" name="lb_test_type" value="random" <?php checked($test_type, 'random'); ?>> Ngẫu nhiên</label>
            </td>
        </tr>
    </table>
    <div id="fixed-test-options" style="<?php echo $test_type !== 'fixed' ? 'display:none;' : ''; ?>">
        <h4>Danh sách câu hỏi cố định</h4>
        <div class="question-checklist-wrapper">
            <?php
            $all_questions = new WP_Query(['post_type' => 'dethi_cauhoi', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
            if ($all_questions->have_posts()) {
                while ($all_questions->have_posts()) {
                    $all_questions->the_post();
                    $q_id = get_the_ID();
                    $checked = in_array($q_id, $danh_sach_cau_hoi) ? 'checked' : '';
                    echo '<p><label><input type="checkbox" name="lb_test_danh_sach_cau_hoi[]" value="' . $q_id . '" ' . $checked . '> #' . $q_id . ' - ' . esc_html(wp_strip_all_tags(get_the_content())) . '</label></p>';
                }
                wp_reset_postdata();
            } else { echo '<p>Chưa có câu hỏi nào.</p>'; }
            ?>
        </div>
    </div>
    <div id="random-test-options" style="<?php echo $test_type !== 'random' ? 'display:none;' : ''; ?>">
        <h4>Cấu hình đề thi ngẫu nhiên</h4>
        <table class="form-table">
            <tr valign="top"><th scope="row"><label for="lb_test_random_count">Số lượng câu hỏi</label></th><td><input type="number" name="lb_test_random_count" id="lb_test_random_count" value="<?php echo esc_attr($random_count); ?>" class="small-text"></td></tr>
            <tr valign="top"><th scope="row"><label for="lb_test_random_cat">Lấy từ Môn học</label></th><td><?php wp_dropdown_categories(['taxonomy' => 'mon_hoc', 'name' => 'lb_test_random_cat', 'selected' => $random_cat, 'show_option_all' => 'Tất cả Môn học', 'hierarchical' => true, 'value_field' => 'term_id']); ?></td></tr>
        </table>
    </div>
    <?php
}

/**
 * Hàm render meta box cho CPT "Nhóm Đề thi" (phần chính)
 */
function lb_test_render_nhomde_metabox($post) {
    wp_nonce_field('lb_test_save_meta_data', 'lb_test_nonce');
    $thoi_gian = get_post_meta($post->ID, 'lb_test_thoi_gian', true);
    $test_type = get_post_meta($post->ID, 'lb_test_type', true) ?: 'fixed';
    $danh_sach_cau_hoi = get_post_meta($post->ID, 'lb_test_danh_sach_cau_hoi', true);
    $danh_sach_cau_hoi = is_array($danh_sach_cau_hoi) ? $danh_sach_cau_hoi : [];
    $random_count = get_post_meta($post->ID, 'lb_test_random_count', true);
    $random_cat = get_post_meta($post->ID, 'lb_test_random_cat', true);
    $random_pool_count = get_post_meta($post->ID, 'lb_test_random_pool_count', true);
    $random_pool_source_ids = get_post_meta($post->ID, 'lb_test_random_pool_source_ids', true);
    $random_pool_source_ids = is_array($random_pool_source_ids) ? $random_pool_source_ids : [];
    ?>
    <p>Thiết lập các quy tắc chung cho tất cả các bộ đề con sẽ được tạo ra từ nhóm này.</p>
    <table class="form-table">
        <tr valign="top"><th scope="row"><label for="lb_test_thoi_gian">Thời gian làm bài (phút)</label></th><td><input type="number" id="lb_test_thoi_gian" name="lb_test_thoi_gian" value="<?php echo esc_attr($thoi_gian); ?>" class="small-text"></td></tr>
        <tr valign="top">
            <th scope="row">Loại đề thi</th>
            <td>
                <label style="display: block; margin-bottom: 5px;"><input type="radio" name="lb_test_type" value="fixed" <?php checked($test_type, 'fixed'); ?>> Cố định (Chọn câu hỏi thủ công)</label>
                <label style="display: block; margin-bottom: 5px;"><input type="radio" name="lb_test_type" value="random" <?php checked($test_type, 'random'); ?>> Ngẫu nhiên theo Môn học</label>
                <label style="display: block; margin-bottom: 5px;"><input type="radio" name="lb_test_type" value="random_pool" <?php checked($test_type, 'random_pool'); ?>> <strong>Ngẫu nhiên từ danh sách chọn</strong></label>
            </td>
        </tr>
    </table>
    
    <div id="fixed-test-options" class="test-type-options">
        <h4>Danh sách câu hỏi cố định</h4>
        <div class="question-checklist-wrapper">
            <?php
            $all_questions_query = new WP_Query(['post_type' => 'dethi_cauhoi', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
            if ($all_questions_query->have_posts()) {
                while ($all_questions_query->have_posts()) { $all_questions_query->the_post(); $q_id = get_the_ID(); $checked = in_array($q_id, $danh_sach_cau_hoi) ? 'checked' : ''; echo '<p><label><input type="checkbox" name="lb_test_danh_sach_cau_hoi[]" value="' . $q_id . '" ' . $checked . '> #' . $q_id . ' - ' . esc_html(wp_strip_all_tags(get_the_content())) . '</label></p>'; }
                wp_reset_postdata();
            } else { echo '<p>Chưa có câu hỏi nào.</p>'; }
            ?>
        </div>
    </div>

    <div id="random-test-options" class="test-type-options">
        <h4>Cấu hình đề thi ngẫu nhiên theo Môn học</h4>
        <table class="form-table">
            <tr valign="top"><th scope="row"><label for="lb_test_random_count">Số lượng câu hỏi</label></th><td><input type="number" name="lb_test_random_count" id="lb_test_random_count" value="<?php echo esc_attr($random_count); ?>" class="small-text"></td></tr>
            <tr valign="top"><th scope="row"><label for="lb_test_random_cat">Lấy từ Môn học</label></th><td><?php wp_dropdown_categories(['taxonomy' => 'mon_hoc', 'name' => 'lb_test_random_cat', 'selected' => $random_cat, 'show_option_all' => 'Tất cả Môn học', 'hierarchical' => true, 'value_field' => 'term_id']); ?></td></tr>
        </table>
    </div>

    <div id="random-pool-test-options" class="test-type-options">
        <h4>Cấu hình đề thi ngẫu nhiên từ danh sách chọn</h4>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="lb_test_random_pool_count">Số lượng câu hỏi trong 1 đề</label></th>
                <td><input type="number" name="lb_test_random_pool_count" id="lb_test_random_pool_count" value="<?php echo esc_attr($random_pool_count); ?>" class="small-text">
                <p class="description">Ví dụ: Chọn 30 câu bên dưới, và nhập 10. Mỗi đề thi con sẽ lấy ngẫu nhiên 10 câu từ 30 câu đã chọn.</p></td>
            </tr>
        </table>
        <p>
            <button type="button" class="button" id="select-all-questions">Chọn tất cả</button>
            <button type="button" class="button" id="deselect-all-questions">Bỏ chọn tất cả</button>
        </p>
        <div id="question-pool-checklist" class="question-checklist-wrapper">
            <?php
            $all_questions_query_pool = new WP_Query(['post_type' => 'dethi_cauhoi', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
            if ($all_questions_query_pool->have_posts()) {
                while ($all_questions_query_pool->have_posts()) { $all_questions_query_pool->the_post(); $q_id = get_the_ID(); $checked = in_array($q_id, $random_pool_source_ids) ? 'checked' : ''; echo '<p><label><input type="checkbox" name="lb_test_random_pool_source_ids[]" value="' . $q_id . '" ' . $checked . '> #' . $q_id . ' - ' . esc_html(wp_strip_all_tags(get_the_content())) . '</label></p>'; }
                wp_reset_postdata();
            } else { echo '<p>Chưa có câu hỏi nào.</p>'; }
            ?>
        </div>
    </div>

    <style>.question-checklist-wrapper { max-height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #fff; }</style>
    <script>
    jQuery(document).ready(function($) {
        function toggleTestTypeOptions() {
            var selectedType = $('input[name="lb_test_type"]:checked').val();
            $('.test-type-options').hide();
            $('#' + selectedType + '-test-options').show();
        }
        $('input[name="lb_test_type"]').on('change', toggleTestTypeOptions);
        toggleTestTypeOptions(); // Run on page load

        $('#select-all-questions').on('click', function() {
            $('#question-pool-checklist input[type="checkbox"]').prop('checked', true);
        });
        $('#deselect-all-questions').on('click', function() {
            $('#question-pool-checklist input[type="checkbox"]').prop('checked', false);
        });
    });
    </script>
    <?php
}

/**
 * Hàm render meta box cho CPT "Nhóm Đề thi" (phần hành động)
 */
function lb_test_render_nhomde_actions_metabox($post) {
    $count = get_post_meta($post->ID, 'lb_test_group_count', true);
    $child_tests = get_children(['post_parent' => $post->ID, 'post_type' => 'dethi_baikiemtra', 'numberposts' => -1]);
    ?>
    <form method="post" action="">
        <input type="hidden" name="post_ID" value="<?php echo $post->ID; ?>">
        <input type="hidden" name="lb_test_nhomde_action" value="generate_child_tests">
        <?php wp_nonce_field('generate_child_tests_' . $post->ID); ?>
        <p><label for="lb_test_group_count"><strong>Số lượng đề cần tạo:</strong></label><input type="number" name="lb_test_group_count" id="lb_test_group_count" value="<?php echo esc_attr($count); ?>" class="widefat"></p>
        <?php submit_button('Tạo bộ đề con', 'primary', 'submit', false); ?>
    </form>
    <hr>
    <?php if (!empty($child_tests)) : ?>
        <h4>Quản lý các đề đã tạo</h4>
        <p>Hiện có <strong><?php echo count($child_tests); ?></strong> bộ đề con đã được tạo.</p>
        <ul style="max-height: 150px; overflow-y:auto; border: 1px solid #ddd; padding: 5px 12px; margin-bottom: 10px;">
            <?php foreach ($child_tests as $child) : $ma_de = get_post_meta($child->ID, 'lb_test_ma_de', true); ?>
                <li><?php echo esc_html($child->post_title); ?> (Mã: <code><?php echo esc_html($ma_de); ?></code>)</li>
            <?php endforeach; ?>
        </ul>
        <form method="post" action="" onsubmit="return confirm('Hành động này sẽ xóa vĩnh viễn tất cả các bộ đề con. Bạn có chắc chắn?');">
            <input type="hidden" name="post_ID" value="<?php echo $post->ID; ?>">
            <input type="hidden" name="lb_test_nhomde_action" value="delete_child_tests">
            <?php wp_nonce_field('delete_child_tests_' . $post->ID); ?>
            <?php submit_button('Xóa tất cả đề con', 'delete', 'submit', false, ['style' => 'color: #a00;']); ?>
        </form>
    <?php endif;
}

// ===================================================================
// HÀM LƯU TẤT CẢ META DATA
// ===================================================================
function lb_test_save_meta_data($post_id) {
    if (!isset($_POST['lb_test_nonce']) || !wp_verify_nonce($_POST['lb_test_nonce'], 'lb_test_save_meta_data') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) { return; }

    if (get_post_type($post_id) == 'dethi_cauhoi') {
        $loai_cau_hoi = sanitize_text_field($_POST['lb_test_loai_cau_hoi']); update_post_meta($post_id, 'lb_test_loai_cau_hoi', $loai_cau_hoi);
        if ($loai_cau_hoi == 'trac_nghiem') {
            update_post_meta($post_id, 'lb_test_dap_an', sanitize_text_field($_POST['lb_test_dap_an']));
            $lua_chon = isset($_POST['lb_test_lua_chon']) ? array_map('sanitize_text_field', $_POST['lb_test_lua_chon']) : [];
            update_post_meta($post_id, 'lb_test_lua_chon', $lua_chon);
        } else {
            update_post_meta($post_id, 'lb_test_dap_an', sanitize_textarea_field($_POST['lb_test_dap_an_tuluan']));
            delete_post_meta($post_id, 'lb_test_lua_chon');
        }
    }

    if (get_post_type($post_id) == 'dethi_baikiemtra') {
        $ma_de = sanitize_text_field($_POST['lb_test_ma_de']); if (empty($ma_de)) $ma_de = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)); update_post_meta($post_id, 'lb_test_ma_de', $ma_de);
        update_post_meta($post_id, 'lb_test_ten_nguoi_lam_bai', sanitize_text_field($_POST['lb_test_ten_nguoi_lam_bai']));
        update_post_meta($post_id, 'lb_test_thoi_gian', intval($_POST['lb_test_thoi_gian']));
        $test_type = sanitize_text_field($_POST['lb_test_type']); update_post_meta($post_id, 'lb_test_type', $test_type);
        if ($test_type === 'fixed') {
            $danh_sach_cau_hoi = isset($_POST['lb_test_danh_sach_cau_hoi']) ? array_map('intval', $_POST['lb_test_danh_sach_cau_hoi']) : [];
            update_post_meta($post_id, 'lb_test_danh_sach_cau_hoi', $danh_sach_cau_hoi);
        } else {
            update_post_meta($post_id, 'lb_test_random_count', intval($_POST['lb_test_random_count']));
            update_post_meta($post_id, 'lb_test_random_cat', intval($_POST['lb_test_random_cat']));
        }
    }
    
    if (get_post_type($post_id) == 'dethi_nhomde') {
        update_post_meta($post_id, 'lb_test_thoi_gian', intval($_POST['lb_test_thoi_gian']));
        if (isset($_POST['lb_test_group_count'])) { update_post_meta($post_id, 'lb_test_group_count', intval($_POST['lb_test_group_count'])); }
        $test_type = sanitize_text_field($_POST['lb_test_type']); update_post_meta($post_id, 'lb_test_type', $test_type);

        if ($test_type === 'fixed') {
            $danh_sach_cau_hoi = isset($_POST['lb_test_danh_sach_cau_hoi']) ? array_map('intval', $_POST['lb_test_danh_sach_cau_hoi']) : [];
            update_post_meta($post_id, 'lb_test_danh_sach_cau_hoi', $danh_sach_cau_hoi);
        } elseif ($test_type === 'random') {
            update_post_meta($post_id, 'lb_test_random_count', intval($_POST['lb_test_random_count']));
            update_post_meta($post_id, 'lb_test_random_cat', intval($_POST['lb_test_random_cat']));
        } elseif ($test_type === 'random_pool') {
            update_post_meta($post_id, 'lb_test_random_pool_count', intval($_POST['lb_test_random_pool_count']));
            $random_pool_source_ids = isset($_POST['lb_test_random_pool_source_ids']) ? array_map('intval', $_POST['lb_test_random_pool_source_ids']) : [];
            update_post_meta($post_id, 'lb_test_random_pool_source_ids', $random_pool_source_ids);
        }
    }
}
add_action('save_post', 'lb_test_save_meta_data');