<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * HÀM XỬ LÝ CÁC HÀNH ĐỘNG (CHẤM BÀI, XÓA BÀI) TRÊN FRONTEND
 * ===================================================================
 */
function lb_test_handle_frontend_grader_actions() {
    // Chỉ chạy ở ngoài frontend
    if (is_admin()) {
        return;
    }

    global $wpdb;

    // --- XỬ LÝ XÓA BÀI THI ---
    if (isset($_GET['action']) && $_GET['action'] === 'delete_submission' && isset($_GET['submission_id']) && isset($_GET['_wpnonce'])) {
        if (!current_user_can('grade_submissions')) {
            wp_die('Bạn không có quyền thực hiện hành động này.');
        }
        
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

    // --- XỬ LÝ CHẤM BÀI THI ---
    if (isset($_POST['action']) && $_POST['action'] === 'grade_submission' && isset($_POST['lb_test_grade_nonce'])) {
        if (!current_user_can('grade_submissions')) {
            wp_die('Bạn không có quyền thực hiện hành động này.');
        }

        if (wp_verify_nonce($_POST['lb_test_grade_nonce'], 'lb_test_grade_action')) {
            $submission_id = intval($_POST['submission_id']);
            $submitted_answers = $_POST['is_correct'] ?? [];
            $answers_table = $wpdb->prefix . 'lb_test_answers';

            $total_questions = 0;
            $correct_answers = 0;
            $all_answers_in_db = $wpdb->get_results($wpdb->prepare("SELECT answer_id FROM $answers_table WHERE submission_id = %d", $submission_id));

            foreach ($all_answers_in_db as $db_answer) {
                $total_questions++;
                $answer_id = $db_answer->answer_id;
                $is_correct_status = isset($submitted_answers[$answer_id]) && $submitted_answers[$answer_id] == '1' ? 1 : 0;
                if ($is_correct_status == 1) $correct_answers++;
                $wpdb->update($answers_table, ['is_correct' => $is_correct_status], ['answer_id' => $answer_id]);
            }
            
            $score = $correct_answers;
            $wpdb->update($wpdb->prefix . 'lb_test_submissions', ['score' => $score, 'status' => 'graded'], ['submission_id' => $submission_id]);
            
            $redirect_url = remove_query_arg('submission_id');
            $redirect_url = add_query_arg(['grading_status' => 'success', 'graded_id' => $submission_id], $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
add_action('template_redirect', 'lb_test_handle_frontend_grader_actions');


/**
 * ===================================================================
 * CÁC HÀM HIỂN THỊ GIAO DIỆN QUA SHORTCODE
 * ===================================================================
 */
function lb_test_render_grader_dashboard_shortcode() {
    if (!current_user_can('grade_submissions')) {
        return '<p>Bạn không có quyền truy cập trang này. Vui lòng đăng nhập bằng tài khoản Giám khảo.</p>';
    }

    ob_start();

    // Hiển thị thông báo
    if (isset($_GET['grading_status']) && $_GET['grading_status'] === 'success') {
        echo '<div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px;">Chấm bài thi #' . intval($_GET['graded_id']) . ' thành công!</div>';
    }
    if (isset($_GET['delete_status']) && $_GET['delete_status'] === 'success') {
        echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px;">Đã xóa bài thi thành công!</div>';
    }

    echo '<div class="grader-wrapper">';
    if (isset($_GET['submission_id']) && is_numeric($_GET['submission_id'])) {
        render_single_submission_grading_form(intval($_GET['submission_id']));
    } else {
        render_grader_dashboard_tables();
    }
    echo '</div>';

    return ob_get_clean();
}
add_shortcode('grader_dashboard', 'lb_test_render_grader_dashboard_shortcode');


function render_grader_dashboard_tables() {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $posts_table = $wpdb->prefix . 'posts';
    $page_url = get_permalink();

    // Bảng 1: Bài thi cần chấm
    $pending_submissions = $wpdb->get_results("SELECT s.*, p.post_title FROM $submissions_table s LEFT JOIN $posts_table p ON s.test_id = p.ID WHERE s.status = 'submitted' ORDER BY s.end_time DESC");
    echo '<h2>Các bài thi cần chấm</h2>';
    if ($pending_submissions) {
        echo '<table><thead><tr><th>ID</th><th>Bài thi</th><th>Tên thí sinh</th><th>Thời gian nộp</th><th>Hành động</th></tr></thead><tbody>';
        foreach ($pending_submissions as $sub) {
            $grading_url = add_query_arg('submission_id', $sub->submission_id, $page_url);
            $delete_nonce = wp_create_nonce('lb_test_delete_submission_' . $sub->submission_id);
            $delete_url = add_query_arg(['action' => 'delete_submission', 'submission_id' => $sub->submission_id, '_wpnonce' => $delete_nonce], $page_url);

            echo '<tr>';
            echo '<td>' . $sub->submission_id . '</td>';
            echo '<td>' . esc_html($sub->post_title) . '</td>';
            echo '<td><strong>' . esc_html($sub->submitter_name) . '</strong></td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($sub->end_time)) . '</td>';
            echo '<td><a href="' . esc_url($grading_url) . '" class="button">Chấm bài</a> <a href="' . esc_url($delete_url) . '" class="button" style="background-color:#dc3545;" onclick="return confirm(\'Bạn có chắc chắn muốn xóa vĩnh viễn bài thi này không?\');">Xóa</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Không có bài thi nào cần chấm.</p>';
    }

    // Bảng 2: Lịch sử chấm bài
    $graded_submissions = $wpdb->get_results("SELECT s.*, p.post_title FROM $submissions_table s LEFT JOIN $posts_table p ON s.test_id = p.ID WHERE s.status = 'graded' ORDER BY s.end_time DESC");
    echo '<h2 style="margin-top: 40px;">Lịch sử chấm bài</h2>';
     if ($graded_submissions) {
        echo '<table><thead><tr><th>ID</th><th>Bài thi</th><th>Tên thí sinh</th><th>Thời gian nộp</th><th>Điểm số</th><th>Hành động</th></tr></thead><tbody>';
        foreach ($graded_submissions as $sub) {
            $total_questions_for_sub = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}lb_test_answers WHERE submission_id = %d", $sub->submission_id));
            $review_url = add_query_arg('submission_id', $sub->submission_id, $page_url);
            $delete_nonce = wp_create_nonce('lb_test_delete_submission_' . $sub->submission_id);
            $delete_url = add_query_arg(['action' => 'delete_submission', 'submission_id' => $sub->submission_id, '_wpnonce' => $delete_nonce], $page_url);

            echo '<tr>';
            echo '<td>' . $sub->submission_id . '</td>';
            echo '<td>' . esc_html($sub->post_title) . '</td>';
            echo '<td><strong>' . esc_html($sub->submitter_name) . '</strong></td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($sub->end_time)) . '</td>';
            echo '<td><strong><a href="' . esc_url($review_url) . '">' . $sub->score . '/' . $total_questions_for_sub . '</a></strong></td>';
            echo '<td><a href="' . esc_url($review_url) . '" class="button" style="background-color:#0073aa;">Xem lại</a> <a href="' . esc_url($delete_url) . '" class="button" style="background-color:#dc3545;" onclick="return confirm(\'Bạn có chắc chắn muốn xóa vĩnh viễn bài thi này không?\');">Xóa</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Chưa có bài thi nào được chấm.</p>';
    }
}

function render_single_submission_grading_form($submission_id) {
    global $wpdb;
    $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}lb_test_submissions WHERE submission_id = %d", $submission_id));
    if (!$submission) { echo '<p>Không tìm thấy bài làm.</p>'; return; }
    $answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}lb_test_answers WHERE submission_id = %d", $submission_id));
    $is_graded = ($submission->status === 'graded');

    echo '<a href="' . esc_url(get_permalink()) . '">&larr; Quay lại Dashboard</a>';
    echo '<h1>Chấm bài thi #' . $submission_id . '</h1>';
    echo '<h3>Thí sinh: ' . esc_html($submission->submitter_name) . '</h3>';
    if ($is_graded) echo '<em>(Đây là bài thi đã được chấm. Bạn đang ở chế độ xem lại.)</em>';

    if (!$is_graded) echo '<form method="POST" action="">';
    wp_nonce_field('lb_test_grade_action', 'lb_test_grade_nonce');
    echo '<input type="hidden" name="action" value="grade_submission">';
    echo '<input type="hidden" name="submission_id" value="' . $submission_id . '">';
    
    $count = 1;
    foreach ($answers as $answer) {
        $question = get_post($answer->question_id);
        $dap_an_mau = get_post_meta($answer->question_id, 'lb_test_dap_an', true);
        $loai_cau_hoi = get_post_meta($answer->question_id, 'lb_test_loai_cau_hoi', true);
        
        echo '<div class="grading-question-block">';
        echo '<h3>Câu ' . $count++ . '</h3>';
        echo '<div class="question-content">' . wpautop($question->post_content) . '</div>';
        echo '<div class="answer-box student-answer">';
        echo '<strong>Câu trả lời của thí sinh:</strong>';
        echo '<div>' . nl2br(esc_html($answer->user_answer)) . '</div>';
        echo '</div>';

        if ($loai_cau_hoi === 'trac_nghiem') {
            $is_correct_auto = (strtoupper(trim($answer->user_answer)) == strtoupper(trim($dap_an_mau)));
            echo '<div class="answer-box correct-answer">';
            echo '<strong>Đáp án đúng:</strong> ' . esc_html($dap_an_mau) . ' &mdash; ';
            if ($is_correct_auto) echo '<span class="result-correct">Thí sinh trả lời Đúng</span>';
            else echo '<span class="result-incorrect">Thí sinh trả lời Sai</span>';
            echo '</div>';
            echo '<input type="hidden" name="is_correct[' . $answer->answer_id . ']" value="' . ($is_correct_auto ? '1' : '0') . '">';
        } else {
            echo '<div class="answer-box correct-answer">';
            echo '<strong>Đáp án mẫu để đối chiếu:</strong>';
            echo '<div>' . nl2br(esc_html($dap_an_mau)) . '</div>';
            echo '</div>';
            if (!$is_graded) echo '<div class="grading-tick"><label><input type="checkbox" name="is_correct[' . $answer->answer_id . ']" value="1"> <strong>Đánh dấu câu trả lời này là Đúng</strong></label></div>';
        }
        echo '</div>';
    }

    if (!$is_graded) echo '<button type="submit" class="finish-button">Finish - Hoàn tất chấm bài</button></form>';
}