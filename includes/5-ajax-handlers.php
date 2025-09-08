<?php
if (!defined('ABSPATH')) exit;

function lb_test_handle_submission() {
    // Bảo mật: Kiểm tra nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lb_test_submit_nonce')) {
        wp_send_json_error('Lỗi bảo mật!');
        return;
    }

    global $wpdb;
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $answers_table = $wpdb->prefix . 'lb_test_answers';

    // Lấy dữ liệu từ form
    $test_id = intval($_POST['test_id']);
    $ma_de = sanitize_text_field($_POST['ma_de']);
    $submitter_name = sanitize_text_field($_POST['submitter_name']); // DÒNG NÀY PHẢI CÓ
    $answers = $_POST['answers'] ?? [];
    $user_id = get_current_user_id();
    $current_time = current_time('mysql');

    // Tạo một bản ghi submission mới
    $result = $wpdb->insert(
        $submissions_table,
        array(
            'test_id'        => $test_id,
            'user_id'        => $user_id,
            'submitter_name' => $submitter_name, // DÒNG NÀY PHẢI CÓ
            'ma_de'          => $ma_de,
            'start_time'     => $current_time,
            'end_time'       => $current_time,
            'status'         => 'submitted',
        )
    );
    
    if ($result === false) {
        $db_error = $wpdb->last_error;
        $error_message = 'Không thể lưu bài làm. Vui lòng thử lại.';
        if (!empty($db_error)) {
            $error_message .= ' (Lỗi DB: ' . $db_error . ')';
        }
        wp_send_json_error($error_message);
        return;
    }

    $submission_id = $wpdb->insert_id;

    // Lặp qua từng câu trả lời và lưu
    foreach ($answers as $q_id => $user_answer) {
        $q_id = intval($q_id);
        $user_answer = is_array($user_answer) ? implode(',', $user_answer) : sanitize_textarea_field($user_answer);
        $loai_cau_hoi = get_post_meta($q_id, 'lb_test_loai_cau_hoi', true);
        $is_correct = 2;

        if ($loai_cau_hoi === 'trac_nghiem') {
            $correct_answer = get_post_meta($q_id, 'lb_test_dap_an', true);
            $is_correct = (strtoupper(trim($user_answer)) == strtoupper(trim($correct_answer))) ? 1 : 0;
        }

        $wpdb->insert(
            $answers_table,
            array(
                'submission_id' => $submission_id,
                'question_id'   => $q_id,
                'user_answer'   => $user_answer,
                'is_correct'    => $is_correct
            )
        );
    }

    // === PHẦN MỚI: Cập nhật trạng thái bài thi thành "Bản nháp" ===
    $post_update_args = array(
        'ID'          => $test_id,
        'post_status' => 'draft',
    );
    wp_update_post($post_update_args);
    // === KẾT THÚC PHẦN MỚI ===

    wp_send_json_success(array('message' => 'Nộp bài thành công! Chúng tôi sẽ sớm có kết quả.'));
}

add_action('wp_ajax_submit_test', 'lb_test_handle_submission');
add_action('wp_ajax_nopriv_submit_test', 'lb_test_handle_submission');