<?php
if (!defined('ABSPATH')) exit;

/**
 * Thêm dropdown lọc theo "Tên Cuộc thi" vào trang danh sách "Bài kiểm tra"
 */
add_action('restrict_manage_posts', function($post_type) {
    if ('dethi_baikiemtra' !== $post_type) {
        return;
    }

    global $wpdb;
    $meta_key = '_contest_name';
    $results = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND p.post_type = %s
             ORDER BY pm.meta_value",
            $meta_key, $post_type
        )
    );
    
    $current_filter = $_GET['contest_filter'] ?? '';

    echo '<select name="contest_filter" id="contest_filter">';
    echo '<option value="">Tất cả Cuộc thi</option>';
    foreach ($results as $name) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($name),
            selected($current_filter, $name, false),
            esc_html($name)
        );
    }
    echo '</select>';
});

/**
 * Xử lý việc lọc khi người dùng chọn từ dropdown
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'dethi_baikiemtra') {
        return;
    }

    if (isset($_GET['contest_filter']) && !empty($_GET['contest_filter'])) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key' => '_contest_name',
            'value' => sanitize_text_field($_GET['contest_filter']),
            'compare' => '=',
        ];
        $query->set('meta_query', $meta_query);
    }
});