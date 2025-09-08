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
    $user_id = get_current_user_id(); // Sẽ là 0 nếu người dùng chưa đăng nhập
    $submitter_name = sanitize_text_field($_POST['submitter_name'] ?? 'Ẩn danh'); // Lấy tên thí sinh

    // Ghi nhận bài làm vào bảng submissions
    $wpdb->insert(
        $wpdb->prefix . 'lb_test_submissions',
        [
            'test_id' => $test_id,
            'user_id' => $user_id,
            'submitter_name' => $submitter_name, // LƯU TÊN THÍ SINH VÀO CỘT MỚI
            'ma_de' => sanitize_text_field($_POST['ma_de']),
            'start_time' => current_time('mysql'),
            'end_time' => current_time('mysql'),
            'status' => 'submitted',
        ]
    );
    $submission_id = $wpdb->insert_id;

    if (!$submission_id) {
        wp_send_json_error('Không thể lưu bài làm.');
        return;
    }
    
    // Lưu các câu trả lời
    $answers_to_insert = [];
    $answers = $_POST['answers'] ?? [];
    foreach ($answers as $q_id => $data) {
        $q_id = intval($q_id);
        $user_answer = sanitize_text_field($data['answer'] ?? '');
        $question_type = sanitize_text_field($data['type'] ?? '');
        
        $is_correct = 2; // Mặc định là 'chưa chấm'
        if ($question_type === 'trac_nghiem') {
            $correct_answer = get_post_meta($q_id, 'lb_test_dap_an', true);
            if ($user_answer === $correct_answer) {
                $is_correct = 1; // Đúng
            } else {
                $is_correct = 0; // Sai
            }
        }

        $answers_to_insert[] = [
            'submission_id' => $submission_id,
            'question_id' => $q_id,
            'user_answer' => $user_answer,
            'is_correct' => $is_correct,
        ];
    }

    if (!empty($answers_to_insert)) {
        foreach ($answers_to_insert as $answer_data) {
            $wpdb->insert($wpdb->prefix . 'lb_test_answers', $answer_data);
        }
    }

    // Chuyển bài kiểm tra về trạng thái 'draft' (bản nháp) sau khi nộp
    wp_update_post(array('ID' => $test_id, 'post_status' => 'draft'));

    wp_send_json_success(['message' => 'Nộp bài thành công!']);
}