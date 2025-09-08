<?php
/**
 * Plugin Name:       Giải Đề Online
 * Description:       Một hệ thống quản lý câu hỏi, bài kiểm tra, làm bài và chấm điểm online.
 * Version:           2.0.0
 * Author:            VolPi
 * Text Domain:       lb-test
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Định nghĩa các hằng số
define('LB_TEST_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LB_TEST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Kích hoạt plugin: Tạo bảng trong database
function lb_test_activate_plugin() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();
    $submissions_table_name = $wpdb->prefix . 'lb_test_submissions';
    $answers_table_name = $wpdb->prefix . 'lb_test_answers';

    $sql_submissions = "CREATE TABLE $submissions_table_name (
        submission_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        test_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
        submitter_name VARCHAR(255) NULL, -- DÒNG NÀY PHẢI CÓ để lưu tên thí sinh
        ma_de VARCHAR(50) NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME DEFAULT NULL,
        status VARCHAR(20) NOT NULL,
        score FLOAT DEFAULT NULL,
        PRIMARY KEY  (submission_id)
    ) $charset_collate;";

    $sql_answers = "CREATE TABLE $answers_table_name (
        answer_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        submission_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        user_answer LONGTEXT,
        is_correct TINYINT(1) DEFAULT 2 NOT NULL,
        PRIMARY KEY  (answer_id)
    ) $charset_collate;";

    dbDelta($sql_submissions);
    dbDelta($sql_answers);
    
    // Thêm vai trò "Giám khảo"
    add_role(
        'giam_khao',
        'Giám khảo',
        [
            'read' => true,
            'grade_submissions' => true,
        ]
    );

    // Thêm quyền chấm bài cho Administrator
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('grade_submissions');
    }
    
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'lb_test_activate_plugin');


// Nạp các file chức năng
include_once(LB_TEST_PLUGIN_PATH . 'includes/1-post-types.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/2-metaboxes.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/3-admin-columns.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/4-shortcode.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/5-ajax-handlers.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/7-frontend-grading.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/8-import-page.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/bulk-generator-page.php'); // FILE MỚI
include_once(LB_TEST_PLUGIN_PATH . 'includes/bulk-management.php');    // FILE MỚI

// Nạp file script và style
function lb_test_enqueue_scripts() {
    // Nạp file CSS mới cho plugin
    wp_enqueue_style('lb-test-style', LB_TEST_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0');

    // Nạp file JS (giữ nguyên)
    wp_enqueue_script('lb-test-main-js', LB_TEST_PLUGIN_URL . 'assets/js/main.js', array('jquery'), '1.0.1', true);
    wp_localize_script('lb-test-main-js', 'lb_test_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'grading_nonce' => wp_create_nonce('lb_test_grading_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'lb_test_enqueue_scripts');
add_action('admin_enqueue_scripts', 'lb_test_enqueue_scripts');