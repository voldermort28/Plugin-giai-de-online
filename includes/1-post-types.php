<?php
if (!defined('ABSPATH')) exit;

function lb_test_register_post_types() {
    // CPT: Nhóm Đề thi (Mới)
    register_post_type('dethi_nhomde', array(
        'labels' => array(
            'name' => 'Nhóm Đề thi',
            'singular_name' => 'Nhóm Đề thi',
            'add_new_item' => 'Tạo Nhóm Đề thi mới',
        ),
        'public' => false, // Không cần xem ở ngoài frontend
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => array('title'),
        'hierarchical' => true,
    ));

    // CPT: Câu hỏi (Giữ nguyên)
    register_post_type('dethi_cauhoi', array(
        'labels' => array('name' => 'Câu hỏi', 'singular_name' => 'Câu hỏi', 'add_new_item' => 'Thêm câu hỏi mới'),
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-editor-help', 'supports' => array('editor'),
    ));

    // CPT: Bài kiểm tra (Giữ nguyên)
    register_post_type('dethi_baikiemtra', array(
        'labels' => array('name' => 'Bài kiểm tra', 'singular_name' => 'Bài kiểm tra', 'add_new_item' => 'Tạo bài kiểm tra mới'),
        'public' => true, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-format-aside', 'supports' => array('title'),
        'rewrite' => array('slug' => 'bai-kiem-tra'),
    ));

    // Taxonomy: Môn học (Giữ nguyên)
    register_taxonomy('mon_hoc', 'dethi_cauhoi', array(
        'labels' => array('name' => 'Môn học', 'singular_name' => 'Môn học'),
        'public' => true, 'hierarchical' => true, 'show_admin_column' => true,
    ));
}
add_action('init', 'lb_test_register_post_types');