<?php
if (!defined('ABSPATH')) exit;

// Thêm cột mới
function lb_test_add_baikiemtra_admin_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key == 'title') {
            $new_columns['ma_de'] = __('Mã đề', 'lb-test');
            $new_columns['nguoi_lam_bai'] = __('Người làm bài', 'lb-test');
        }
    }
    return $new_columns;
}
add_filter('manage_dethi_baikiemtra_posts_columns', 'lb_test_add_baikiemtra_admin_columns');

// Hiển thị dữ liệu
function lb_test_display_baikiemtra_admin_columns_data($column, $post_id) {
    switch ($column) {
        case 'ma_de':
            echo esc_html(get_post_meta($post_id, 'lb_test_ma_de', true)) ?: '—';
            break;
        case 'nguoi_lam_bai':
            echo esc_html(get_post_meta($post_id, 'lb_test_ten_nguoi_lam_bai', true)) ?: '—';
            break;
    }
}
add_action('manage_dethi_baikiemtra_posts_custom_column', 'lb_test_display_baikiemtra_admin_columns_data', 10, 2);

// Cho phép sắp xếp
function lb_test_make_ma_de_column_sortable($columns) {
    $columns['ma_de'] = 'ma_de';
    return $columns;
}
add_filter('manage_edit-dethi_baikiemtra_sortable_columns', 'lb_test_make_ma_de_column_sortable');

function lb_test_ma_de_orderby($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'dethi_baikiemtra') {
        return;
    }
    if ('ma_de' === $query->get('orderby')) {
        $query->set('meta_key', 'lb_test_ma_de');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'lb_test_ma_de_orderby');

/**
 * ===================================================================
 * HIỂN THỊ NỘI DUNG CÂU HỎI THAY CHO TIÊU ĐỀ "AUTO DRAFT"
 * ===================================================================
 */
function lb_test_show_content_as_title_in_admin_list($title, $id = null) {
    // Chỉ áp dụng cho CPT 'dethi_cauhoi' trong khu vực admin
    if (!is_admin() || !isset($id) || get_post_type($id) !== 'dethi_cauhoi') {
        return $title;
    }

    // Nếu tiêu đề là rỗng hoặc 'Auto Draft', ta sẽ thay thế nó
    if (empty(trim($title)) || $title === 'Auto Draft') {
        // Lấy nội dung của câu hỏi
        $question_content = get_post_field('post_content', $id);
        
        // Rút gọn nội dung để hiển thị (lấy khoảng 15 từ đầu) và loại bỏ HTML
        $new_title = wp_trim_words(strip_tags($question_content), 15, '...');
        
        // Nếu sau khi rút gọn vẫn rỗng, hiển thị ID để dễ nhận biết
        if (empty($new_title)) {
            return '(Câu hỏi #' . $id . ')';
        }
        
        return $new_title;
    }

    // Nếu không thì trả về tiêu đề gốc
    return $title;
}
add_filter('the_title', 'lb_test_show_content_as_title_in_admin_list', 10, 2);