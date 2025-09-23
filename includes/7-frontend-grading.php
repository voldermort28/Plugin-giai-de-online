<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * HÀM XỬ LÝ CÁC HÀNH ĐỘNG (CHẤM BÀI, XÓA BÀI) TRÊN FRONTEND
 * ===================================================================
 */
function lb_test_handle_frontend_grader_actions() {
    // --- BẢO VỆ TRANG: Yêu cầu đăng nhập để xem các trang của giám khảo ---
    // Các trang này bao gồm Chấm bài, Danh sách đề, Bảng xếp hạng và Hồ sơ thí sinh.
    if ( ! is_user_logged_in() && ( is_page('chamdiem') || is_page('code') || is_page('bxh') || is_page('hosothisinh') ) ) {
        
        // Lấy URL hiện tại để chuyển hướng người dùng trở lại sau khi đăng nhập.
        $redirect_url = home_url( $_SERVER['REQUEST_URI'] );
        
        // Tạo URL đăng nhập và đính kèm URL chuyển hướng.
        $login_url = wp_login_url( $redirect_url );
        
        // Thực hiện chuyển hướng và kết thúc thực thi.
        wp_safe_redirect( $login_url );
        exit;
    }

    if (is_admin()) return;
    global $wpdb;

    // --- XỬ LÝ XÓA BÀI THI ---
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

    // --- XỬ LÝ CHẤM BÀI THI ---
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
                [
                    'score' => $final_correct_count, 
                    'status' => 'graded',
                    'submitter_name' => $submitter_name
                ],
                ['submission_id' => $submission_id]
            );
            
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
    if (!current_user_can('grade_submissions')) return '<p>Bạn không có quyền truy cập trang này.</p>';
    ob_start();
    ?>
    <div class="gdv-container">
        <div class="gdv-main-tabs">
            <a href="<?php echo esc_url(get_site_url(null, '/chamdiem/')); ?>" class="gdv-main-tab active">Chấm Bài & Lịch Sử</a>
            <a href="<?php echo esc_url(get_site_url(null, '/code/')); ?>" class="gdv-main-tab">Danh Sách Đề Thi</a>
            <a href="<?php echo esc_url(get_site_url(null, '/bxh/')); ?>" class="gdv-main-tab">Bảng Xếp Hạng</a>
            <a href="<?php echo esc_url(site_url('/hosothisinh/')); ?>" class="gdv-main-tab">Hồ sơ Thí sinh</a>
        </div>
    <?php
    if (isset($_GET['grading_status'])) echo '<div class="gdv-notice success">Chấm bài thi #' . intval($_GET['graded_id']) . ' thành công!</div>';
    if (isset($_GET['delete_status'])) echo '<div class="gdv-notice error">Đã xóa bài thi thành công!</div>';
    
    if (isset($_GET['submission_id']) && is_numeric($_GET['submission_id'])) {
        $submission_id = intval($_GET['submission_id']);
        $view_mode = isset($_GET['view_mode']) ? sanitize_key($_GET['view_mode']) : 'regrade'; // Mặc định là chấm lại
        render_single_submission_grading_form($submission_id, $view_mode);
    } else {
        render_grader_dashboard_tables();
    }
    ?>
    <div id="gdv-bulk-actions" class="gdv-bulk-actions">
        <span id="gdv-selected-count">0 items selected</span>
        <button id="gdv-bulk-delete-btn" class="delete-btn">Delete</button>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckboxes = document.querySelectorAll('.gdv-select-all');
        const rowCheckboxes = document.querySelectorAll('.gdv-row-checkbox');
        const bulkActionsBar = document.getElementById('gdv-bulk-actions');
        const selectedCountSpan = document.getElementById('gdv-selected-count');
        const bulkDeleteBtn = document.getElementById('gdv-bulk-delete-btn');

        function updateBulkActionsBar() {
            const selectedCheckboxes = document.querySelectorAll('.gdv-row-checkbox:checked');
            const count = selectedCheckboxes.length;
            
            if (count > 0) {
                selectedCountSpan.textContent = `${count} mục đã chọn`;
                bulkActionsBar.classList.add('visible');
            } else {
                bulkActionsBar.classList.remove('visible');
            }
            selectAllCheckboxes.forEach(box => {
                box.checked = (count > 0 && count === rowCheckboxes.length);
            });
        }

        selectAllCheckboxes.forEach(box => {
            box.addEventListener('change', function() {
                const tableId = this.getAttribute('data-table');
                document.querySelectorAll(`#${tableId} .gdv-row-checkbox`).forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionsBar();
            });
        });

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionsBar);
        });

        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.gdv-row-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất một bài làm để xóa.');
                return;
            }
            if (confirm(`Bạn có chắc chắn muốn xóa ${selectedIds.length} bài làm đã chọn? Đề thi gốc sẽ được chuyển về trạng thái "Sẵn sàng".`)) {
                 jQuery.ajax({
                    url: lb_test_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bulk_delete_items',
                        nonce: lb_test_ajax.bulk_delete_nonce,
                        item_ids: selectedIds,
                        delete_type: 'submission'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            window.location.reload();
                        } else {
                            alert('Lỗi: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Đã xảy ra lỗi không xác định. Vui lòng thử lại.');
                    }
                });
            }
        });
    });
    </script>
    <?php
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('grader_dashboard', 'lb_test_render_grader_dashboard_shortcode');

function render_grader_dashboard_tables() {
    global $wpdb;
    $page_url = get_permalink();
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $contestants_table = $wpdb->prefix . 'lb_test_contestants';
    $posts_table = $wpdb->prefix . 'posts';

    $pending_submissions = $wpdb->get_results("
        SELECT s.*, p.post_title, COALESCE(c.display_name, s.submitter_name) as final_submitter_name
        FROM $submissions_table s 
        LEFT JOIN $posts_table p ON s.test_id = p.ID 
        LEFT JOIN $contestants_table c ON s.contestant_id = c.contestant_id
        WHERE s.status = 'submitted' 
        ORDER BY s.end_time DESC
    ");
    echo '<h2>Các bài thi cần chấm</h2>';
    if ($pending_submissions) {
        echo '<div class="gdv-table-wrapper"><table class="gdv-table" id="pending-table"><thead><tr><th><input type="checkbox" class="gdv-select-all" data-table="pending-table"></th><th>ID</th><th>Bài thi</th><th>Tên thí sinh</th><th>Thời gian nộp</th><th>Hành động</th></tr></thead><tbody>';
        foreach ($pending_submissions as $sub) {
            $grading_url = add_query_arg('submission_id', $sub->submission_id, $page_url);
            $delete_nonce = wp_create_nonce('lb_test_delete_submission_' . $sub->submission_id);
            $delete_url = add_query_arg(['action' => 'delete_submission', 'submission_id' => $sub->submission_id, '_wpnonce' => $delete_nonce], $page_url);
            
            // Tạo link cho tên thí sinh
            $submitter_html = $sub->contestant_id
                ? '<a href="' . esc_url(site_url('/hosothisinh/?contestant_id=' . $sub->contestant_id)) . '" class="gdv-action-link">' . esc_html($sub->final_submitter_name) . '</a>'
                : esc_html($sub->final_submitter_name);

            echo '<tr>
                    <td><input type="checkbox" class="gdv-row-checkbox" value="' . esc_attr($sub->submission_id) . '"></td>
                    <td>#' . $sub->submission_id . '</td>
                    <td><strong>' . esc_html($sub->post_title) . '</strong></td>
                    <td><strong>' . $submitter_html . '</strong></td>
                    <td>' . wp_date('d/m/Y, H:i', strtotime($sub->end_time)) . '</td>
                    <td><a href="' . esc_url($grading_url) . '" class="gdv-action-link"><strong>Chấm bài</strong></a> <a href="' . esc_url($delete_url) . '" class="gdv-action-link" style="color: var(--gdv-danger-text);" onclick="return confirm(\'Xóa vĩnh viễn?\');">Xóa</a></td>
                  </tr>';
        }
        echo '</tbody></table></div>';
    } else { echo '<p>Không có bài thi nào cần chấm.</p>'; }

    $graded_submissions = $wpdb->get_results("
        SELECT s.*, p.post_title, COALESCE(c.display_name, s.submitter_name) as final_submitter_name
        FROM $submissions_table s 
        LEFT JOIN $posts_table p ON s.test_id = p.ID 
        LEFT JOIN $contestants_table c ON s.contestant_id = c.contestant_id
        WHERE s.status = 'graded' ORDER BY s.end_time DESC
    ");
    echo '<h2 style="margin-top: 40px;">Lịch sử chấm bài</h2>';
    if ($graded_submissions) {
        echo '<div class="gdv-table-wrapper"><table class="gdv-table" id="graded-table"><thead><tr><th><input type="checkbox" class="gdv-select-all" data-table="graded-table"></th><th>ID</th><th>Bài thi</th><th>Tên thí sinh</th><th>Thời gian nộp</th><th>Điểm số</th><th>Hành động</th></tr></thead><tbody>';
        
        // --- Tối ưu hóa: Lấy tổng số câu hỏi cho tất cả các bài đã chấm trong 1 truy vấn ---
        $submission_ids = wp_list_pluck($graded_submissions, 'submission_id');
        $question_counts = [];
        if (!empty($submission_ids)) {
            $answers_table = $wpdb->prefix . 'lb_test_answers';
            $results = $wpdb->get_results(
                "SELECT submission_id, COUNT(answer_id) as total 
                 FROM {$answers_table} 
                 WHERE submission_id IN (" . implode(',', array_map('intval', $submission_ids)) . ") 
                 GROUP BY submission_id"
            );
            // Tạo map để tra cứu: submission_id => total_questions
            foreach ($results as $row) {
                $question_counts[$row->submission_id] = $row->total;
            }
        }

        foreach ($graded_submissions as $sub) {
            $total_questions_for_sub = $question_counts[$sub->submission_id] ?? 0;
            $review_url = add_query_arg(['submission_id' => $sub->submission_id, 'view_mode' => 'review'], $page_url);
            $regrade_url = add_query_arg(['submission_id' => $sub->submission_id, 'view_mode' => 'regrade'], $page_url);
            $delete_nonce = wp_create_nonce('lb_test_delete_submission_' . $sub->submission_id);
            $delete_url = add_query_arg(['action' => 'delete_submission', 'submission_id' => $sub->submission_id, '_wpnonce' => $delete_nonce], $page_url);
            
            // Tạo link cho tên thí sinh
            $submitter_html = $sub->contestant_id
                ? '<a href="' . esc_url(site_url('/hosothisinh/?contestant_id=' . $sub->contestant_id)) . '" class="gdv-action-link">' . esc_html($sub->final_submitter_name) . '</a>'
                : esc_html($sub->final_submitter_name);

            echo '<tr>
                    <td><input type="checkbox" class="gdv-row-checkbox" value="' . esc_attr($sub->submission_id) . '"></td>
                    <td>#' . $sub->submission_id . '</td>
                    <td><strong>' . esc_html($sub->post_title) . '</strong></td>
                    <td><strong>' . $submitter_html . '</strong></td>
                    <td>' . wp_date('d/m/Y, H:i', strtotime($sub->end_time)) . '</td>
                    <td><strong><a href="' . esc_url($review_url) . '" class="gdv-action-link">' . intval($sub->score) . '/' . $total_questions_for_sub . '</a></strong></td>
                    <td><a href="' . esc_url($review_url) . '" class="gdv-action-link">Xem lại</a> | <a href="' . esc_url($regrade_url) . '" class="gdv-action-link">Chấm lại</a> | <a href="' . esc_url($delete_url) . '" class="gdv-action-link" style="color: var(--gdv-danger-text);" onclick="return confirm(\'Xóa vĩnh viễn?\');">Xóa</a></td>
                  </tr>';
        }
        echo '</tbody></table></div>';
    } else { echo '<p>Chưa có bài thi nào được chấm.</p>'; }
}

function render_single_submission_grading_form($submission_id, $view_mode = 'regrade') {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'lb_test_submissions';
    $contestants_table = $wpdb->prefix . 'lb_test_contestants';
    $submission = $wpdb->get_row($wpdb->prepare("
        SELECT s.*, COALESCE(c.display_name, s.submitter_name) as final_submitter_name
        FROM $submissions_table s
        LEFT JOIN $contestants_table c ON s.contestant_id = c.contestant_id
        WHERE s.submission_id = %d", $submission_id));
    if (!$submission) { echo '<p>Không tìm thấy bài làm.</p>'; return; }
    $answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}lb_test_answers WHERE submission_id = %d", $submission_id));
    $is_graded = ($submission->status === 'graded');

    echo '<a href="' . esc_url(remove_query_arg('submission_id')) . '">&larr; Quay lại Dashboard</a>';
    
    $page_title = ($view_mode === 'review') ? 'Xem lại bài thi' : 'Chấm bài thi';
    echo '<h1>' . $page_title . ' #' . $submission_id . '</h1>';

    // Hiển thị form nếu là chế độ chấm/chấm lại
    if ($view_mode !== 'review') {
        $button_text = $is_graded ? 'Cập nhật điểm' : 'Hoàn tất chấm bài';
        echo '<form method="POST" action="">';
        wp_nonce_field('lb_test_grade_action', 'lb_test_grade_nonce');
        echo '<input type="hidden" name="action" value="grade_submission">';
        echo '<input type="hidden" name="submission_id" value="' . $submission_id . '">';
        echo '<h3>Thí sinh: <input type="text" name="submitter_name" value="' . esc_attr($submission->final_submitter_name) . '" style="font-size: 1em; padding: 5px;"></h3>';
        // Thêm nút submit ở đầu form
        echo '<button type="submit" class="finish-button" style="margin-bottom: 20px;">' . $button_text . '</button>';
        if ($is_graded) {
            echo '<p><em>(Bạn đang ở chế độ chấm lại. Thay đổi sẽ được lưu khi bạn nhấn "Cập nhật điểm".)</em></p>';
        }
    } else {
        // Chế độ chỉ đọc
        $regrade_url = add_query_arg('view_mode', 'regrade');
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid var(--gdv-border);">
                <div>
                    <h3 style="margin:0 0 5px 0;">Thí sinh: ' . esc_html($submission->final_submitter_name) . '</h3>
                    <p style="margin:0; color: var(--gdv-text-secondary);"><em>(Bạn đang ở chế độ xem lại. Không thể chỉnh sửa.)</em></p>
                </div>
                <a href="' . esc_url($regrade_url) . '" class="gdv-button">Chấm lại</a>
              </div>';

    }
    
    $count = 1;
    foreach ($answers as $answer) {
        $question = get_post($answer->question_id);
        if ($question) {
            $dap_an_mau = get_post_meta($answer->question_id, 'lb_test_dap_an', true);
            $loai_cau_hoi = get_post_meta($answer->question_id, 'lb_test_loai_cau_hoi', true);
            
            // Lấy danh sách các lựa chọn cho câu trắc nghiệm
            $lua_chon = ($loai_cau_hoi === 'trac_nghiem') ? get_post_meta($answer->question_id, 'lb_test_lua_chon', true) : [];
            $lua_chon = is_array($lua_chon) ? $lua_chon : [];

            echo '<div class="grading-question-block">';
            echo '<h4>Câu ' . $count++ . ': ' . esc_html($question->post_content) . '</h4>';
            
            // Thêm class màu vàng cho câu tự luận chưa chấm
            $student_answer_extra_class = ($loai_cau_hoi === 'tu_luan' && $answer->is_correct == 2) ? 'pending-answer' : '';
            $student_answer_full_text = $answer->user_answer;
            if ($loai_cau_hoi === 'trac_nghiem' && isset($lua_chon[$answer->user_answer])) {
                $student_answer_full_text = $answer->user_answer . '. ' . $lua_chon[$answer->user_answer];
            }
            
            echo '<div class="answer-box student-answer ' . $student_answer_extra_class . '"><strong>Câu trả lời của thí sinh:</strong><div>' . nl2br(esc_html($student_answer_full_text)) . '</div></div>';

            if ($loai_cau_hoi === 'trac_nghiem') {
                $is_correct_in_db = ($answer->is_correct == 1);
                $correct_answer_box_class = $is_correct_in_db ? 'correct-answer' : 'incorrect-answer';
                $correct_answer_full_text = isset($lua_chon[$dap_an_mau]) ? ($dap_an_mau . '. ' . $lua_chon[$dap_an_mau]) : $dap_an_mau;

                echo '<div class="answer-box ' . $correct_answer_box_class . '"><strong>Đáp án đúng:</strong> ' . esc_html($correct_answer_full_text) . ' &mdash; ';
                if ($is_correct_in_db) echo '<span class="result-correct">Thí sinh trả lời Đúng</span>';
                else echo '<span class="result-incorrect">Thí sinh trả lời Sai</span>';
                echo '</div>';
                echo '<input type="hidden" name="is_correct[' . $answer->answer_id . ']" value="' . ($is_correct_in_db ? '1' : '0') . '">';
            } else { // Tự luận
                echo '<div class="answer-box correct-answer"><strong>Đáp án mẫu:</strong><div>' . nl2br(esc_html($dap_an_mau)) . '</div></div>';
                
                if ($view_mode !== 'review') {
                    // Chế độ chấm/chấm lại: hiển thị checkbox
                    $checked = ($answer->is_correct == 1) ? 'checked' : '';
                    echo '<div class="grading-tick"><label><input type="checkbox" name="is_correct[' . $answer->answer_id . ']" value="1" ' . $checked . '> <strong>Đánh dấu câu trả lời này là Đúng</strong></label></div>';
                } else {
                    // Chế độ xem lại: hiển thị kết quả
                    $result_text = '<span class="result-incorrect">Thí sinh trả lời Sai</span>'; // Mặc định là sai
                    $result_box_class = 'incorrect-answer';
                    if ($answer->is_correct == 1) {
                        $result_text = '<span class="result-correct">Thí sinh trả lời Đúng</span>';
                        $result_box_class = 'correct-answer';
                    } elseif ($answer->is_correct == 2) {
                        $result_text = '<span style="color: #fbc02d; font-weight: bold;">Chưa chấm</span>';
                        $result_box_class = 'pending-answer';
                    }
                    echo '<div class="answer-box ' . $result_box_class . '">';
                    echo $result_text;
                    echo '</div>';
                }
            }
            echo '</div>';
        }
    }

    if ($view_mode !== 'review') {
        echo '<button type="submit" class="finish-button">' . $button_text . '</button></form>';
    }
}