<?php
if (!defined('ABSPATH')) exit;

// === ĐĂNG KÝ META BOX ===
function lb_test_add_meta_boxes() {
    add_meta_box(
        'lb_test_cauhoi_details',
        'Chi tiết câu hỏi',
        'lb_test_render_cauhoi_metabox',
        'dethi_cauhoi',
        'normal',
        'high'
    );
    add_meta_box(
        'lb_test_baikiemtra_details',
        'Cấu hình bài kiểm tra',
        'lb_test_render_baikiemtra_metabox',
        'dethi_baikiemtra',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'lb_test_add_meta_boxes');

// === HIỂN THỊ META BOX CHO CÂU HỎI ===
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
            <?php for ($i = 0; $i < 4; $i++) : 
                $char = chr(65 + $i); // A, B, C, D
            ?>
            <tr valign="top">
                <th scope="row"><label for="lua_chon_<?php echo $char; ?>">Lựa chọn <?php echo $char; ?></label></th>
                <td>
                    <input type="text" name="lb_test_lua_chon[<?php echo $char; ?>]" id="lua_chon_<?php echo $char; ?>" value="<?php echo esc_attr($lua_chon[$char] ?? ''); ?>" class="widefat" />
                </td>
            </tr>
            <?php endfor; ?>
            <tr valign="top">
                <th scope="row"><label>Đáp án đúng</label></th>
                <td>
                    <?php for ($i = 0; $i < 4; $i++) : 
                        $char = chr(65 + $i);
                    ?>
                    <label style="margin-right: 15px;"><input type="radio" name="lb_test_dap_an" value="<?php echo $char; ?>" <?php checked($dap_an, $char); ?>> <?php echo $char; ?></label>
                    <?php endfor; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div id="lb_test_tu_luan_fields" style="<?php echo ($loai_cau_hoi !== 'tu_luan') ? 'display:none;' : ''; ?>">
        <h4>Đáp án mẫu (Tự luận)</h4>
        <textarea name="lb_test_dap_an_tuluan" class="widefat" rows="5"><?php echo ($loai_cau_hoi === 'tu_luan') ? esc_textarea($dap_an) : ''; ?></textarea>
    </div>
    <?php
}

// === HIỂN THỊ META BOX CHO BÀI KIỂM TRA ===
function lb_test_render_baikiemtra_metabox($post) {
    wp_nonce_field('lb_test_save_meta_data', 'lb_test_nonce');

    $ma_de = get_post_meta($post->ID, 'lb_test_ma_de', true);
    $ten_nguoi_lam_bai = get_post_meta($post->ID, 'lb_test_ten_nguoi_lam_bai', true);
    $thoi_gian = get_post_meta($post->ID, 'lb_test_thoi_gian', true);
    $danh_sach_cau_hoi = get_post_meta($post->ID, 'lb_test_danh_sach_cau_hoi', true);
    $danh_sach_cau_hoi = is_array($danh_sach_cau_hoi) ? $danh_sach_cau_hoi : [];

    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="lb_test_ma_de">Mã đề</label></th>
            <td><input type="text" id="lb_test_ma_de" name="lb_test_ma_de" value="<?php echo esc_attr($ma_de); ?>" class="regular-text">
                <p class="description">Nhập mã đề riêng. Nếu để trống, hệ thống sẽ tự sinh khi lưu.</p></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="lb_test_ten_nguoi_lam_bai">Tên Người làm bài</label></th>
            <td><input type="text" id="lb_test_ten_nguoi_lam_bai" name="lb_test_ten_nguoi_lam_bai" value="<?php echo esc_attr($ten_nguoi_lam_bai); ?>" class="widefat"></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="lb_test_thoi_gian">Thời gian làm bài (phút)</label></th>
            <td><input type="number" id="lb_test_thoi_gian" name="lb_test_thoi_gian" value="<?php echo esc_attr($thoi_gian); ?>" class="small-text"></td>
        </tr>
    </table>
    <hr>
    <h4>Danh sách câu hỏi</h4>
    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
        <?php
        $all_questions = new WP_Query(array(
            'post_type' => 'dethi_cauhoi',
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'DESC',
        ));
        if ($all_questions->have_posts()) {
            while ($all_questions->have_posts()) {
                $all_questions->the_post();
                $q_id = get_the_ID();
                $checked = in_array($q_id, $danh_sach_cau_hoi) ? 'checked' : '';
                echo '<p><label><input type="checkbox" name="lb_test_danh_sach_cau_hoi[]" value="' . $q_id . '" ' . $checked . '> #' . $q_id . ' - ' . wp_strip_all_tags(get_the_content()) . '</label></p>';
            }
            wp_reset_postdata();
        } else {
            echo '<p>Chưa có câu hỏi nào trong hệ thống.</p>';
        }
        ?>
    </div>
    <?php
}

// === LƯU DỮ LIỆU TỪ META BOX ===
function lb_test_save_meta_data($post_id) {
    if (!isset($_POST['lb_test_nonce']) || !wp_verify_nonce($_POST['lb_test_nonce'], 'lb_test_save_meta_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // === Lưu dữ liệu cho CPT Câu hỏi ===
    if (get_post_type($post_id) == 'dethi_cauhoi') {
        $loai_cau_hoi = sanitize_text_field($_POST['lb_test_loai_cau_hoi']);
        update_post_meta($post_id, 'lb_test_loai_cau_hoi', $loai_cau_hoi);

        if ($loai_cau_hoi == 'trac_nghiem') {
            update_post_meta($post_id, 'lb_test_dap_an', sanitize_text_field($_POST['lb_test_dap_an']));
            $lua_chon = array_map('sanitize_text_field', $_POST['lb_test_lua_chon']);
            update_post_meta($post_id, 'lb_test_lua_chon', $lua_chon);
        } else { // Tự luận
            update_post_meta($post_id, 'lb_test_dap_an', sanitize_textarea_field($_POST['lb_test_dap_an_tuluan']));
            delete_post_meta($post_id, 'lb_test_lua_chon');
        }
    }

    // === Lưu dữ liệu cho CPT Bài kiểm tra ===
    if (get_post_type($post_id) == 'dethi_baikiemtra') {
        $ma_de = sanitize_text_field($_POST['lb_test_ma_de']);
        if (empty($ma_de)) {
            $ma_de = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }
        update_post_meta($post_id, 'lb_test_ma_de', $ma_de);

        update_post_meta($post_id, 'lb_test_ten_nguoi_lam_bai', sanitize_text_field($_POST['lb_test_ten_nguoi_lam_bai']));
        update_post_meta($post_id, 'lb_test_thoi_gian', intval($_POST['lb_test_thoi_gian']));

        $danh_sach_cau_hoi = isset($_POST['lb_test_danh_sach_cau_hoi']) ? array_map('intval', $_POST['lb_test_danh_sach_cau_hoi']) : [];
        update_post_meta($post_id, 'lb_test_danh_sach_cau_hoi', $danh_sach_cau_hoi);
    }
}
add_action('save_post', 'lb_test_save_meta_data');