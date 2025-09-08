<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * XỬ LÝ CÁC HÀNH ĐỘNG TỪ TRANG "NHÓM ĐỀ THI"
 * ===================================================================
 */
function lb_test_handle_nhomde_actions() {
    if (!isset($_POST['lb_test_nhomde_action'])) return;
    if (!current_user_can('edit_posts')) wp_die('Bạn không có quyền thực hiện hành động này.');

    $action = sanitize_key($_POST['lb_test_nhomde_action']);
    $parent_id = intval($_POST['post_ID']);

    // --- XỬ LÝ HÀNH ĐỘNG TẠO ĐỀ HÀNG LOẠT ---
    if ($action === 'generate_child_tests' && check_admin_referer('generate_child_tests_' . $parent_id)) {
        $count = intval(get_post_meta($parent_id, 'lb_test_group_count', true));
        if ($count <= 0) return;

        $thoi_gian = get_post_meta($parent_id, 'lb_test_thoi_gian', true);
        $test_type = get_post_meta($parent_id, 'lb_test_type', true);

        for ($i = 0; $i < $count; $i++) {
            $child_post_title = get_the_title($parent_id) . ' - Bộ đề #' . ($i + 1);
            $child_id = wp_insert_post([
                'post_type' => 'dethi_baikiemtra',
                'post_title' => $child_post_title,
                'post_status' => 'publish',
                'post_parent' => $parent_id,
            ]);

            if ($child_id > 0) {
                update_post_meta($child_id, 'lb_test_ma_de', strtoupper(substr(md5($parent_id . '-' . $child_id . uniqid()), 0, 8)));
                update_post_meta($child_id, 'lb_test_thoi_gian', $thoi_gian);
                
                // Sao chép các quy tắc dựa trên loại đề thi của Nhóm cha
                if ($test_type === 'random_pool') {
                    $pool_source_ids = get_post_meta($parent_id, 'lb_test_random_pool_source_ids', true);
                    $pool_count = intval(get_post_meta($parent_id, 'lb_test_random_pool_count', true));
                    
                    if (!empty($pool_source_ids) && is_array($pool_source_ids) && $pool_count > 0 && $pool_count <= count($pool_source_ids)) {
                        shuffle($pool_source_ids);
                        $random_questions_for_child = array_slice($pool_source_ids, 0, $pool_count);
                        
                        update_post_meta($child_id, 'lb_test_danh_sach_cau_hoi', $random_questions_for_child);
                        update_post_meta($child_id, 'lb_test_type', 'fixed'); 
                    }
                } elseif ($test_type === 'random') {
                    update_post_meta($child_id, 'lb_test_type', 'random');
                    update_post_meta($child_id, 'lb_test_random_count', get_post_meta($parent_id, 'lb_test_random_count', true));
                    update_post_meta($child_id, 'lb_test_random_cat', get_post_meta($parent_id, 'lb_test_random_cat', true));
                } else { // fixed
                    update_post_meta($child_id, 'lb_test_type', 'fixed');
                    update_post_meta($child_id, 'lb_test_danh_sach_cau_hoi', get_post_meta($parent_id, 'lb_test_danh_sach_cau_hoi', true));
                }
            }
        }
        wp_safe_redirect(add_query_arg('message', '101', get_edit_post_link($parent_id, 'raw')));
        exit;
    }

    // --- XỬ LÝ HÀNH ĐỘNG XÓA ĐỀ HÀNG LOẠT ---
    if ($action === 'delete_child_tests' && check_admin_referer('delete_child_tests_' . $parent_id)) {
        $child_tests = get_children(['post_parent' => $parent_id, 'post_type' => 'dethi_baikiemtra', 'numberposts' => -1]);
        foreach ($child_tests as $child) {
            wp_delete_post($child->ID, true);
        }
        wp_safe_redirect(add_query_arg('message', '102', get_edit_post_link($parent_id, 'raw')));
        exit;
    }
}
add_action('admin_init', 'lb_test_handle_nhomde_actions');


// Thêm thông báo tùy chỉnh vào admin
function lb_test_show_nhomde_admin_notices($messages) {
    $messages['post'][101] = 'Đã tạo các bộ đề thi con thành công.';
    $messages['post'][102] = 'Đã xóa tất cả các bộ đề thi con.';
    return $messages;
}
add_filter('post_updated_messages', 'lb_test_show_nhomde_admin_notices');