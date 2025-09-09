<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_submit_test', 'lb_test_handle_submission');
add_action('wp_ajax_nopriv_submit_test', 'lb_test_handle_submission');

function lb_test_handle_submission() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lb_test_submit_nonce')) {
        wp_send_json_error('Lỗi bảo mật!');
        return;
    }

    global $wpdb;
    $test_id = intval($_POST['test_id']);
    $user_id = get_current_user_id();
    $submitter_name = sanitize_text_field($_POST['submitter_name'] ?? 'Ẩn danh');

    $wpdb->insert($wpdb->prefix . 'lb_test_submissions', [
        'test_id' => $test_id,
        'user_id' => $user_id,
        'submitter_name' => $submitter_name,
        'ma_de' => sanitize_text_field($_POST['ma_de']),
        'start_time' => current_time('mysql'),
        'end_time' => current_time('mysql'),
        'status' => 'submitted',
    ]);
    $submission_id = $wpdb->insert_id;

    if (!$submission_id) {
        wp_send_json_error('Không thể lưu bài làm.');
        return;
    }
    
    // SỬA LỖI: Xử lý đúng cấu trúc dữ liệu đa chiều
    $answers = $_POST['answers'] ?? [];
    foreach ($answers as $q_id => $data) {
        $q_id = intval($q_id);
        
        // Lấy câu trả lời và loại câu hỏi từ mảng $data
        $user_answer = sanitize_textarea_field($data['answer'] ?? '');
        $loai_cau_hoi = sanitize_text_field($data['type'] ?? '');
        
        $is_correct = 2; // Mặc định là 'chờ chấm' cho câu tự luận

        if ($loai_cau_hoi === 'trac_nghiem') {
            $correct_answer = get_post_meta($q_id, 'lb_test_dap_an', true);
            
            // Logic so sánh mạnh hơn để tránh lỗi ký tự ẩn
            $clean_user_answer = preg_replace('/[^A-Z]/', '', strtoupper($user_answer));
            $clean_correct_answer = preg_replace('/[^A-Z]/', '', strtoupper($correct_answer));
            
            $is_correct = ($clean_user_answer == $clean_correct_answer) ? 1 : 0;
        }

        $wpdb->insert(
            $wpdb->prefix . 'lb_test_answers',
            [
                'submission_id' => $submission_id,
                'question_id' => $q_id,
                'user_answer' => $user_answer,
                'is_correct' => $is_correct,
            ]
        );
    }

    wp_update_post(array('ID' => $test_id, 'post_status' => 'draft'));

    wp_send_json_success(['message' => 'Nộp bài thành công!']);
}