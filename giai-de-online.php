<?php
/**
 * Plugin Name:       Giải Đề Online
 * Description:       Một hệ thống quản lý câu hỏi, bài kiểm tra, làm bài và chấm điểm online.
 * Version:           2.0.1
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
    $contestants_table_name = $wpdb->prefix . 'lb_test_contestants';

    // Bảng mới để lưu thông tin thí sinh
    $sql_contestants = "CREATE TABLE $contestants_table_name (
        contestant_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        phone_number VARCHAR(20) NOT NULL,
        display_name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (contestant_id),
        UNIQUE KEY phone_number (phone_number)
    ) $charset_collate;";

    $sql_submissions = "CREATE TABLE $submissions_table_name (
        submission_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        test_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
        contestant_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
        submitter_name VARCHAR(255) NULL, -- Giữ lại để tương thích với dữ liệu cũ
        ma_de VARCHAR(50) NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME DEFAULT NULL,
        status VARCHAR(20) NOT NULL,
        score FLOAT DEFAULT NULL,
        PRIMARY KEY  (submission_id),
        KEY test_id (test_id),
        KEY contestant_id (contestant_id)
    ) $charset_collate;";

    $sql_answers = "CREATE TABLE $answers_table_name (
        answer_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        submission_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        user_answer LONGTEXT,
        is_correct TINYINT(1) DEFAULT 2 NOT NULL,
        PRIMARY KEY  (answer_id)
    ) $charset_collate;";

    dbDelta($sql_contestants);
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
include_once(LB_TEST_PLUGIN_PATH . 'includes/template-helpers.php'); // Nạp file tiện ích lên trước
include_once(LB_TEST_PLUGIN_PATH . 'includes/4-shortcode.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/5-ajax-handlers.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/7-frontend-grading.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/8-import-page.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/9-ma-de-dashboard.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/bulk-generator-page.php');
include_once(LB_TEST_PLUGIN_PATH . 'includes/10-leaderboard-page.php'); // FILE MỚI
include_once(LB_TEST_PLUGIN_PATH . 'includes/11-contestant-profiles.php'); // FILE MỚI
include_once(LB_TEST_PLUGIN_PATH . 'includes/bulk-management.php');    // FILE MỚI
include_once(LB_TEST_PLUGIN_PATH . 'includes/12-contestant-leaderboard.php'); // Bảng xếp hạng cho thí sinh

/**
 * Khởi tạo PHP Session để lưu trạng thái đăng nhập của thí sinh.
 */
function lb_test_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'lb_test_start_session', 1);

/**
 * ===================================================================
 * HÀM XỬ LÝ TẬP TRUNG CÁC FORM SUBMISSION VÀ REDIRECT
 * Hook vào 'template_redirect' để chạy trước khi header được gửi đi.
 * ===================================================================
 */
function lb_test_handle_all_form_submissions() {
    if (is_admin()) return;
    global $wpdb;

    // --- 1. XỬ LÝ ĐĂNG NHẬP/ĐĂNG XUẤT BẰNG SĐT CHO BXH THÍ SINH ---
    if (isset($_GET['logout']) && $_GET['logout'] == 'true' && is_page('ketqua')) {
        unset($_SESSION['lb_test_contestant_phone']);
        unset($_SESSION['lb_test_contestant_name']);
        wp_safe_redirect(get_permalink());
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lb_phone_login_nonce'])) {
        if (wp_verify_nonce($_POST['lb_phone_login_nonce'], 'lb_phone_login_action')) {
            $phone_number = sanitize_text_field($_POST['phone_number']);
            $contestant = $wpdb->get_row($wpdb->prepare(
                "SELECT contestant_id, display_name FROM {$wpdb->prefix}lb_test_contestants WHERE phone_number = %s",
                $phone_number
            ));

            if ($contestant) {
                $_SESSION['lb_test_contestant_phone'] = $phone_number;
                $_SESSION['lb_test_contestant_name'] = $contestant->display_name;
                wp_safe_redirect(get_permalink());
                exit;
            } else {
                $_SESSION['lb_phone_login_error'] = 'Số điện thoại không tồn tại trong hệ thống hoặc bạn chưa tham gia bài thi nào.';
            }
        } else {
            $_SESSION['lb_phone_login_error'] = 'Lỗi bảo mật, vui lòng thử lại.';
        }
    }

    // --- 2. XỬ LÝ XÓA BÀI THI TỪ DASHBOARD GIÁM KHẢO ---
    if (isset($_GET['action']) && $_GET['action'] === 'delete_submission' && isset($_GET['submission_id']) && isset($_GET['_wpnonce'])) {
        if (!current_user_can('grade_submissions')) wp_die('Bạn không có quyền thực hiện hành động này.');
        
        $submission_id = intval($_GET['submission_id']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'lb_test_delete_submission_' . $submission_id)) {
            $wpdb->delete($wpdb->prefix . 'lb_test_answers', ['submission_id' => $submission_id]);
            $wpdb->delete($wpdb->prefix . 'lb_test_submissions', ['submission_id' => $submission_id]);

            $redirect_url = remove_query_arg(['action', 'submission_id', '_wpnonce']);
            $redirect_url = add_query_arg('delete_status', 'success', $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    // --- 3. XỬ LÝ CHẤM BÀI THI TỪ DASHBOARD GIÁM KHẢO ---
    if (isset($_POST['action']) && $_POST['action'] === 'grade_submission' && isset($_POST['lb_test_grade_nonce'])) {
        if (!current_user_can('grade_submissions')) wp_die('Bạn không có quyền thực hiện hành động này.');

        if (wp_verify_nonce($_POST['lb_test_grade_nonce'], 'lb_test_grade_action')) {
            $submission_id = intval($_POST['submission_id']);
            $submitter_name = sanitize_text_field($_POST['submitter_name'] ?? '');
            $submitted_is_correct = $_POST['is_correct'] ?? [];
            $final_correct_count = 0;
            $answers_table = $wpdb->prefix . 'lb_test_answers';
            
            $all_answers_in_db = $wpdb->get_results($wpdb->prepare("SELECT answer_id FROM $answers_table WHERE submission_id = %d", $submission_id));

            foreach ($all_answers_in_db as $db_answer) {
                $answer_id = $db_answer->answer_id;
                $is_correct_status = (isset($submitted_is_correct[$answer_id]) && $submitted_is_correct[$answer_id] == '1') ? 1 : 0;

                $wpdb->update(
                    $answers_table,
                    ['is_correct' => $is_correct_status],
                    ['answer_id' => $answer_id]
                );

                if ($is_correct_status === 1) {
                    $final_correct_count++;
                }
            }

            $wpdb->update(
                $wpdb->prefix . 'lb_test_submissions',
                ['score' => $final_correct_count, 'status' => 'graded', 'submitter_name' => $submitter_name],
                ['submission_id' => $submission_id]
            );
            
            $redirect_url = remove_query_arg('submission_id');
            $redirect_url = add_query_arg(['grading_status' => 'success', 'graded_id' => $submission_id], $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
add_action('template_redirect', 'lb_test_handle_all_form_submissions');

// Nạp file script và style
function lb_test_enqueue_scripts() {
    // Nạp file CSS chung cho các trang frontend (làm bài, nhập mã đề)
    wp_enqueue_style('lb-test-style', LB_TEST_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0');
    
    // Chỉ nạp file CSS cho dashboard nếu đang ở một trong các trang quản lý của giám khảo hoặc trang BXH của thí sinh
    // Thêm 'ho-so-thi-sinh' vào danh sách các trang sử dụng dashboard style
    if (is_page('chamdiem') || is_page('code') || is_page('bxh') || is_page('hosothisinh') || is_page('ketqua')) {
        wp_enqueue_style('lb-test-grader-dashboard-style', LB_TEST_PLUGIN_URL . 'assets/css/grader-dashboard.css', array(), '1.0.0');
    }

    wp_enqueue_script('lb-test-main-js', LB_TEST_PLUGIN_URL . 'assets/js/main.js', array('jquery'), '1.0.1', true);
    wp_localize_script('lb-test-main-js', 'lb_test_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'bulk_delete_nonce' => wp_create_nonce('lb_test_bulk_delete_nonce'),
        'phone_check_nonce' => wp_create_nonce('lb_test_phone_check_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'lb_test_enqueue_scripts');
add_action('admin_enqueue_scripts', 'lb_test_enqueue_scripts');