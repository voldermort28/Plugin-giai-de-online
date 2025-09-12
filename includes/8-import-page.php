<?php
if (!defined('ABSPATH')) exit;

/**
 * ===================================================================
 * TẠO TRANG IMPORT TRONG MENU ADMIN
 * ===================================================================
 */
function lb_test_add_import_page_to_menu() {
    add_submenu_page('edit.php?post_type=dethi_cauhoi', 'Import Câu hỏi', 'Import từ CSV/Text', 'manage_options', 'lb-test-import', 'lb_test_render_import_page');
}
add_action('admin_menu', 'lb_test_add_import_page_to_menu');

/**
 * ===================================================================
 * HÀM XỬ LÝ CHUNG CHO MỘT DÒNG DỮ LIỆU
 * ===================================================================
 */
function lb_test_process_import_row($data, $row_index) {
    global $wpdb;

    if (count($data) !== 7) {
        return "Lỗi ở dòng số {$row_index}: Số lượng cột không hợp lệ (tìm thấy " . count($data) . ", cần 7).";
    }

    $question = mb_convert_encoding(trim($data[0] ?? ''), 'UTF-8');
    $type = strtolower(trim($data[1] ?? ''));
    $option_a = mb_convert_encoding(trim($data[2] ?? ''), 'UTF-8');
    $option_b = mb_convert_encoding(trim($data[3] ?? ''), 'UTF-8');
    $option_c = mb_convert_encoding(trim($data[4] ?? ''), 'UTF-8');
    $option_d = mb_convert_encoding(trim($data[5] ?? ''), 'UTF-8');
    $correct_answer = trim($data[6] ?? '');

    if (empty($question)) return "Lỗi ở dòng số {$row_index}: Cột 'question' không được để trống.";
    if (empty($type)) return "Lỗi ở dòng số {$row_index}: Cột 'type' không được để trống.";
    if (!in_array($type, ['trac_nghiem', 'tu_luan'])) return "Lỗi ở dòng số {$row_index}: Cột 'type' có giá trị '{$type}' không hợp lệ.";
    if ($type === 'trac_nghiem') {
        if (empty($option_a) || empty($option_b)) return "Lỗi ở dòng số {$row_index}: Câu trắc nghiệm phải có ít nhất 2 lựa chọn (option_a, option_b).";
        if (empty($correct_answer)) return "Lỗi ở dòng số {$row_index}: Cột 'correct_answer' không được để trống cho câu trắc nghiệm.";
        if (!in_array(strtoupper($correct_answer), ['A', 'B', 'C', 'D'])) return "Lỗi ở dòng số {$row_index}: Cột 'correct_answer' phải là A, B, C, hoặc D.";
        $options_map = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
        if (empty($options_map[strtoupper($correct_answer)])) {
            return "Lỗi ở dòng số {$row_index}: Đáp án '{$correct_answer}' không hợp lệ vì lựa chọn tương ứng không có nội dung.";
        }
    }

    $existing_post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'dethi_cauhoi' AND post_content = %s",
        $question
    ));

    $post_id = $existing_post_id ?: 0;
    $status = 'created';

    if ($existing_post_id) {
        $status = 'updated';
    } else {
        $post_id = wp_insert_post(['post_type' => 'dethi_cauhoi', 'post_content' => $question, 'post_status' => 'publish'], true);
    }
    
    if (is_wp_error($post_id)) {
        return "Lỗi không xác định ở dòng số {$row_index}: " . $post_id->get_error_message();
    }

    update_post_meta($post_id, 'lb_test_loai_cau_hoi', $type);
    if ($type === 'trac_nghiem') {
        $options = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
        update_post_meta($post_id, 'lb_test_lua_chon', $options);
        update_post_meta($post_id, 'lb_test_dap_an', strtoupper($correct_answer));
    } else {
        update_post_meta($post_id, 'lb_test_dap_an', $correct_answer);
    }
    
    return ['status' => $status, 'post_id' => $post_id, 'question' => $question];
}


/**
 * ===================================================================
 * HÀM HIỂN THỊ TRANG IMPORT CHÍNH
 * ===================================================================
 */
function lb_test_render_import_page() {
    echo '<div class="wrap">';
    echo '<h1>Import Câu hỏi</h1>';

    $created_count = 0; $updated_count = 0; $skipped_count = 0;
    $error_messages = []; $success_details = [];

    function process_input_data($lines) {
        $created = 0; $updated = 0; $skipped = 0;
        $errors = []; $successes = [];
        $row_index = 0;
        foreach ($lines as $line) {
            $row_index++;
            if (empty(trim($line)) || strpos(strtolower($line), 'question;type;') === 0) continue;
            $data = str_getcsv($line, ';');
            $result = lb_test_process_import_row($data, $row_index);
            if (is_array($result)) {
                if ($result['status'] === 'created') $created++; else $updated++;
                $successes[] = $result;
            } else {
                $errors[] = $result; $skipped++;
            }
        }
        return compact('created', 'updated', 'skipped', 'errors', 'successes');
    }

    $lines_to_process = [];
    $form_submitted = false;
    $plaintext_data_submitted = '';

    if (isset($_POST['lb_test_import_submit']) && check_admin_referer('lb_test_import_action', 'lb_test_import_nonce')) {
        $form_submitted = true;
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            set_time_limit(300);
            $file_content = file_get_contents($_FILES['csv_file']['tmp_name']);
            if (substr($file_content, 0, 3) === "\xef\xbb\xbf") { $file_content = substr($file_content, 3); }
            $lines_to_process = preg_split('/\\r\\n|\\r|\\n/', $file_content);
        } else {
             $error_messages[] = 'Lỗi: Không thể tải file lên hoặc file không hợp lệ.';
        }
    }
    
    if (isset($_POST['lb_test_import_text_submit']) && check_admin_referer('lb_test_import_text_action', 'lb_test_import_text_nonce')) {
        $form_submitted = true;
        if (!empty($_POST['plaintext_data'])) {
            $plaintext_data = $plaintext_data_submitted = stripslashes($_POST['plaintext_data']);
            $lines_to_process = preg_split('/\\r\\n|\\r|\\n/', $plaintext_data);
        }
    }

    if ($form_submitted && !empty($lines_to_process)) {
        $result = process_input_data($lines_to_process);
        $created_count = $result['created'];
        $updated_count = $result['updated'];
        $skipped_count = $result['skipped'];
        $error_messages = $result['errors'];
        $success_details = $result['successes'];
    }

    if ($form_submitted) {
        echo '<div class="notice notice-info is-dismissible"><p><strong>Import hoàn tất!</strong></p><ul>';
        echo '<li>Số câu hỏi đã được **Tạo mới**: ' . $created_count . '</li>';
        echo '<li>Số câu hỏi đã được **Cập nhật**: ' . $updated_count . '</li>';
        echo '<li>Số dòng bị bỏ qua (do lỗi): ' . $skipped_count . '</li>';
        echo '</ul></div>';
        if (!empty($success_details)) {
            echo '<h3>Chi tiết các câu hỏi đã được xử lý thành công</h3>';
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th style="width:80px;">ID</th><th>Nội dung câu hỏi</th><th style="width:120px;">Trạng thái</th></tr></thead><tbody>';
            foreach ($success_details as $detail) {
                $status_label = ($detail['status'] === 'created') 
                    ? '<span style="color:green; font-weight:bold;">Tạo mới</span>' 
                    : '<span style="color:orange; font-weight:bold;">Cập nhật</span>';
                echo '<tr><td>' . esc_html($detail['post_id']) . '</td><td>' . esc_html(wp_trim_words($detail['question'], 30, '...')) . '</td><td>' . $status_label . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        if (!empty($error_messages)) {
            echo '<div class="notice notice-error" style="margin-top:20px;"><p><strong>Chi tiết các lỗi đã phát hiện:</strong></p><ul style="list-style-type: disc; padding-left: 20px;">';
            foreach ($error_messages as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul></div>';
        }
    }
    
    ?>
    <hr>
    <h2>Cách 1: Import từ nội dung text</h2>
    <p>Dán nội dung câu hỏi của bạn vào khung bên dưới, mỗi câu hỏi một dòng, các cột phân cách bằng dấu chấm phẩy (<code>;</code>).</p>
    <form method="post" action="">
        <textarea name="plaintext_data" rows="15" style="width:100%; font-family: monospace;"><?php echo esc_textarea($plaintext_data_submitted); ?></textarea>
        <?php wp_nonce_field('lb_test_import_text_action', 'lb_test_import_text_nonce'); ?>
        <?php submit_button('Import từ Text', 'primary', 'lb_test_import_text_submit'); ?>
    </form>
    
    <hr>
    <h2>Cách 2: Import từ file CSV</h2>
    <p>File CSV của bạn phải có cấu trúc <strong>7 cột</strong> và dùng dấu chấm phẩy (<code>;</code>) để phân cách. Dòng đầu tiên là tiêu đề.</p>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>question</th>
                <th>type</th>
                <th>option_a</th>
                <th>option_b</th>
                <th>option_c</th>
                <th>option_d</th>
                <th>correct_answer</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Nội dung đầy đủ của câu hỏi.</td>
                <td><code>trac_nghiem</code> hoặc <code>tu_luan</code></td>
                <td>Nội dung lựa chọn A.</td>
                <td>Nội dung lựa chọn B.</td>
                <td>Nội dung lựa chọn C.</td>
                <td>Nội dung lựa chọn D.</td>
                <td>Đáp án đúng (<code>A</code>, <code>B</code>, <code>C</code>, hoặc <code>D</code>).</td>
            </tr>
        </tbody>
    </table>
    <h3 style="margin-top: 20px;">Tải file lên</h3>
    <form method="post" action="" enctype="multipart/form-data">
        <p><label for="csv_file">Chọn file CSV:</label> <input type="file" name="csv_file" id="csv_file" accept=".csv"></p>
        <?php wp_nonce_field('lb_test_import_action', 'lb_test_import_nonce'); ?>
        <?php submit_button('Bắt đầu Import từ File', 'secondary', 'lb_test_import_submit'); ?>
    </form>
    <?php
    echo '</div>';
}