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
    $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');
    $submitter_name = sanitize_text_field($_POST['submitter_name'] ?? 'Ẩn danh');

    if (empty($phone_number) || empty($submitter_name)) {
        wp_send_json_error('Thiếu thông tin thí sinh.');
        return;
    }

    // --- Xử lý thông tin thí sinh ---
    $contestants_table = $wpdb->prefix . 'lb_test_contestants';
    $contestant_id = 0;

    // Tìm thí sinh bằng SĐT
    $existing_contestant = $wpdb->get_row($wpdb->prepare("SELECT contestant_id, display_name FROM $contestants_table WHERE phone_number = %s", $phone_number));

    if ($existing_contestant) {
        $contestant_id = $existing_contestant->contestant_id;
        // Cập nhật tên nếu có sự thay đổi
        if ($existing_contestant->display_name !== $submitter_name) {
            $wpdb->update($contestants_table, ['display_name' => $submitter_name], ['contestant_id' => $contestant_id]);
        }
    } else {
        // Tạo thí sinh mới nếu chưa tồn tại
        $wpdb->insert($contestants_table, [
            'phone_number' => $phone_number,
            'display_name' => $submitter_name,
            'created_at'   => current_time('mysql'),
        ]);
        $contestant_id = $wpdb->insert_id;
    }

    $wpdb->insert($wpdb->prefix . 'lb_test_submissions', [
        'test_id' => $test_id,
        'user_id' => $user_id,
        'contestant_id' => $contestant_id,
        'submitter_name' => $submitter_name, // Vẫn lưu tên tại thời điểm thi
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
            $correct_answer = get_post_meta($q_id, 'lb_test_dap_an', true) ?: '';

            if (!empty($user_answer)) {
                // Logic so sánh mạnh hơn để tránh lỗi ký tự ẩn và khoảng trắng
                $clean_user_answer = preg_replace('/[^A-Z]/', '', strtoupper($user_answer));
                $clean_correct_answer = preg_replace('/[^A-Z]/', '', strtoupper($correct_answer));
                $is_correct = ($clean_user_answer === $clean_correct_answer) ? 1 : 0;
            } else {
                $is_correct = 0; // Nếu không trả lời, tính là sai
            }
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

    // Gửi về thêm thông tin để hiển thị trên màn hình thành công
    wp_send_json_success([
        'message' => 'Nộp bài thành công!',
        'test_title' => get_the_title($test_id),
        'submitter_name' => $submitter_name
    ]);
}

/**
 * ===================================================================
 * DỌN DẸP DỮ LIỆU KHI XÓA ĐỀ THI
 * ===================================================================
 */
function lb_cleanup_submissions_on_test_delete($post_id) {
    global $wpdb;
    if (get_post_type($post_id) !== 'dethi_baikiemtra') {
        return;
    }
    
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $answers_table = $wpdb->prefix . 'lb_test_answers';

    // Tìm tất cả các submission_id liên quan đến test_id này
    $submission_ids = $wpdb->get_col($wpdb->prepare("SELECT submission_id FROM $submissions_table WHERE test_id = %d", $post_id));

    if (!empty($submission_ids)) {
        // Xóa tất cả các câu trả lời liên quan
        $placeholders = implode(',', array_fill(0, count($submission_ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM $answers_table WHERE submission_id IN ($placeholders)", $submission_ids));

        // Xóa tất cả các bài làm liên quan
        $wpdb->delete($submissions_table, ['test_id' => $post_id], ['%d']);
    }
}
add_action('before_delete_post', 'lb_cleanup_submissions_on_test_delete');


/**
 * ===================================================================
 * AJAX HANDLER FOR BULK DELETION
 * ===================================================================
 */
add_action('wp_ajax_bulk_delete_items', 'lb_handle_bulk_delete');

function lb_handle_bulk_delete() {
    check_ajax_referer('lb_test_bulk_delete_nonce', 'nonce');

    if (!current_user_can('grade_submissions')) {
        wp_send_json_error(['message' => 'Bạn không có quyền thực hiện hành động này.']);
        return;
    }

    global $wpdb;
    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
    $delete_type = sanitize_text_field($_POST['delete_type']);

    if (empty($item_ids) || empty($delete_type)) {
        wp_send_json_error(['message' => 'Dữ liệu không hợp lệ.']);
        return;
    }

    if ($delete_type === 'submission') {
        $submissions_table = $wpdb->prefix . 'lb_test_submissions';
        $answers_table = $wpdb->prefix . 'lb_test_answers';
        $test_ids_to_update = [];

        foreach ($item_ids as $submission_id) {
            // Lấy test_id trước khi xóa để cập nhật lại trạng thái
            $test_id = $wpdb->get_var($wpdb->prepare("SELECT test_id FROM $submissions_table WHERE submission_id = %d", $submission_id));
            if ($test_id) {
                $test_ids_to_update[] = $test_id;
            }

            // Xóa câu trả lời và bài làm
            $wpdb->delete($answers_table, ['submission_id' => $submission_id]);
            $wpdb->delete($submissions_table, ['submission_id' => $submission_id]);
        }
        // Cập nhật lại trạng thái của các đề thi thành 'publish' (Sẵn sàng)
        foreach (array_unique($test_ids_to_update) as $test_id) {
            wp_update_post(['ID' => $test_id, 'post_status' => 'publish']);
        }
        wp_send_json_success(['message' => 'Đã xóa các bài làm đã chọn và đặt lại trạng thái đề thi.']);

    } elseif ($delete_type === 'test') {
        foreach ($item_ids as $test_id) {
            wp_delete_post($test_id, true); // true để xóa vĩnh viễn
        }
        wp_send_json_success(['message' => 'Đã xóa vĩnh viễn các đề thi đã chọn.']);

    } else {
        wp_send_json_error(['message' => 'Loại xóa không được hỗ trợ.']);
    }
}

/**
 * ===================================================================
 * AJAX HANDLER FOR CHECKING CONTESTANT PHONE
 * ===================================================================
 */
add_action('wp_ajax_check_contestant_phone', 'lb_handle_check_contestant_phone');
add_action('wp_ajax_nopriv_check_contestant_phone', 'lb_handle_check_contestant_phone');

function lb_handle_check_contestant_phone() {
    check_ajax_referer('lb_test_phone_check_nonce', 'nonce');

    global $wpdb;
    $phone_number = sanitize_text_field($_POST['phone_number']);

    if (empty($phone_number)) {
        wp_send_json_error();
        return;
    }

    $contestant = $wpdb->get_row($wpdb->prepare("SELECT display_name FROM {$wpdb->prefix}lb_test_contestants WHERE phone_number = %s", $phone_number));

    if ($contestant) {
        wp_send_json_success(['display_name' => $contestant->display_name]);
    } else {
        wp_send_json_error();
    }
}