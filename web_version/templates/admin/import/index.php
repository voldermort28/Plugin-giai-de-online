<?php
// web_version/templates/admin/import/index.php

$page_title = 'Nhập câu hỏi từ CSV';

$preview_data = [];
$lines_to_process = [];
$form_submitted = false;
$text_data_submitted = '';

// Xử lý khi submit từ form nhập text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_text_submit'])) {
    $form_submitted = true;
    if (!empty($_POST['text_data'])) {
        $text_data_submitted = stripslashes($_POST['text_data']);
        $lines_to_process = preg_split('/\\r\\n|\\r|\\n/', $text_data_submitted);
    }
}

// Xử lý khi submit từ form upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_file_submit'])) {
    $form_submitted = true;
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file'];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (strtolower($file_extension) === 'csv') {
            $file_content = file_get_contents($file['tmp_name']);
            // Xóa BOM nếu có (thường gặp ở file CSV xuất từ Excel)
            if (substr($file_content, 0, 3) === "\xef\xbb\xbf") { $file_content = substr($file_content, 3); }
            $lines_to_process = preg_split('/\\r\\n|\\r|\\n/', $file_content);
        } else {
            set_message('error', 'Vui lòng chọn một file CSV hợp lệ.');
        }
    } else {
        set_message('error', 'Có lỗi xảy ra khi tải file lên hoặc bạn chưa chọn file.');
    }
}

// Bước 2: Xử lý khi người dùng xác nhận import từ màn hình xem trước.
// Phải đặt khối này TRƯỚC khối "Bước 1" để nó có thể xử lý POST request từ màn hình preview.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import_submit'])) {
    $lines_to_process = json_decode($_POST['import_data'], true);

    $created_count = 0;
    $updated_count = 0;
    $error_count = 0;
    $errors = [];
    $row_number = 0;

    foreach ($lines_to_process as $line) {
        $row_number++; // Dòng này bây giờ là chỉ số mảng, không phải số dòng file gốc
        $data = $line; // Dữ liệu đã được phân tích ở bước xem trước

        // Các bước kiểm tra và xử lý dữ liệu (đã được thực hiện ở bước xem trước, nhưng có thể kiểm tra lại cho chắc chắn)
        // ... (phần logic này được giữ nguyên) ...
        $content = trim($data[0] ?? '');
        $type = trim(strtolower($data[1] ?? ''));
        $option_a = trim($data[2] ?? '');
        $option_b = trim($data[3] ?? '');
        $option_c = trim($data[4] ?? '');
        $option_d = trim($data[5] ?? '');
        $correct_answer = trim($data[6] ?? '');

        if (empty($content)) {
            $error_count++; $errors[] = "Dòng {$row_number}: Cột 'question' (nội dung câu hỏi) không được để trống."; continue;
        }

        if (!in_array($type, ['tu_luan', 'trac_nghiem'])) {
            $error_count++; $errors[] = "Dòng {$row_number}: Cột 'type' có giá trị không hợp lệ ('" . htmlspecialchars($type) . "'). Chỉ chấp nhận 'tu_luan' hoặc 'trac_nghiem'."; continue;
        }

        $options_json = null;
        if ($type === 'trac_nghiem') {
            $options_data = [];
            if (!empty($option_a)) $options_data['A'] = $option_a;
            if (!empty($option_b)) $options_data['B'] = $option_b;
            if (!empty($option_c)) $options_data['C'] = $option_c;
            if (!empty($option_d)) $options_data['D'] = $option_d;

            if (empty($options_data['A']) || empty($options_data['B'])) {
                $error_count++; $errors[] = "Dòng {$row_number}: Câu trắc nghiệm phải có ít nhất 2 lựa chọn (option_a và option_b không được trống)."; continue;
            }

            if (empty($correct_answer)) {
                $error_count++; $errors[] = "Dòng {$row_number}: Cột 'correct_answer' không được để trống cho câu trắc nghiệm."; continue;
            }

            if (!array_key_exists(strtoupper($correct_answer), $options_data)) {
                $error_count++; $errors[] = "Dòng {$row_number}: Đáp án đúng '" . htmlspecialchars($correct_answer) . "' không hợp lệ hoặc lựa chọn tương ứng bị bỏ trống."; continue;
            }
            $options_json = json_encode($options_data, JSON_UNESCAPED_UNICODE);
        }

        $data_to_save = ['content' => $content, 'type' => $type, 'options' => $options_json, 'correct_answer' => $correct_answer];

        try {
            // Kiểm tra xem câu hỏi đã tồn tại chưa
            $existing_question = $db->fetch("SELECT question_id FROM questions WHERE content = ?", [$content]);

            if ($existing_question) {
                // Nếu tồn tại, cập nhật nó
                $db->update('questions', $data_to_save, 'question_id = ?', [$existing_question['question_id']]);
                $updated_count++;
            } else {
                // Nếu không, thêm mới
                $db->insert('questions', $data_to_save);
                $created_count++;
            }
        } catch (Exception $e) {
            $error_count++; $errors[] = "Dòng {$row_number}: Lỗi CSDL - " . $e->getMessage();
        }
    }

    if ($error_count > 0) {
        $error_details_html = '<ul>';
        foreach ($errors as $err) { $error_details_html .= '<li>' . htmlspecialchars($err) . '</li>'; }
        $error_details_html .= '</ul>';
        $summary_message = "Nhập liệu hoàn tất.<br><ul>" .
                           "<li>Câu hỏi mới: <strong>{$created_count}</strong></li>" .
                           "<li>Câu hỏi cập nhật: <strong>{$updated_count}</strong></li>" .
                           "<li>Lỗi: <strong>{$error_count}</strong></li></ul>";
        set_message('warning', $summary_message . "<strong>Chi tiết lỗi:</strong>" . $error_details_html);
    } else {
        set_message('success', "Nhập liệu hoàn tất.<br><ul><li>Câu hỏi mới: <strong>{$created_count}</strong></li><li>Câu hỏi cập nhật: <strong>{$updated_count}</strong></li></ul>");
    }
    redirect('/admin/import');
}

// Bước 1: Phân tích dữ liệu để xem trước.
// Khối này sẽ chạy khi người dùng submit từ form nhập text hoặc upload file.
if ($form_submitted && !empty($lines_to_process)) {
    $row_number = 0;
    $preview_data = [];
    foreach ($lines_to_process as $line) {
        $row_number++;
        if (empty(trim($line)) || stripos(trim($line), 'question;type;option_a') === 0) {
            continue;
        }
        $data = str_getcsv($line, ";");
        $preview_data[] = $data;
    }

    // Nếu có dữ liệu để xem trước, hiển thị màn hình preview và dừng lại.
    if (!empty($preview_data)) {
        // Đặt tiêu đề lại cho trang preview
        $page_title = 'Xem trước dữ liệu Import';
        
        // Hiển thị màn hình preview
        echo '<div class="gdv-header"><h1>Xem trước dữ liệu Import</h1></div>';
        echo '<div class="gdv-card">';
        echo '<p>Vui lòng kiểm tra lại dữ liệu đã được phân tích dưới đây. Nếu mọi thứ chính xác, hãy nhấn "Xác nhận Import" để tiến hành lưu vào cơ sở dữ liệu.</p>';
        echo '<div class="gdv-table-wrapper" style="max-height: 500px; overflow-y: auto;">';
        echo '<table class="gdv-table">';
        echo '<thead><tr><th>#</th><th>Nội dung</th><th>Loại</th><th>Lựa chọn A</th><th>Lựa chọn B</th><th>Lựa chọn C</th><th>Lựa chọn D</th><th>Đáp án</th></tr></thead>';
        echo '<tbody>';
        foreach ($preview_data as $index => $row) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars(mb_substr($row[0] ?? '', 0, 50)) . '...</td>';
            echo '<td><span class="gdv-status ' . htmlspecialchars($row[1] ?? '') . '">' . htmlspecialchars($row[1] ?? '') . '</span></td>';
            // Hiển thị đầy đủ 4 lựa chọn
            echo '<td>' . htmlspecialchars($row[2] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row[3] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row[4] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row[5] ?? '') . '</td>';
            echo '<td><strong>' . htmlspecialchars($row[6] ?? '') . '</strong></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        
        // Form xác nhận
        echo '<form method="POST" action="/admin/import" style="margin-top: 20px; text-align: right;">';
        echo '<input type="hidden" name="import_data" value="' . htmlspecialchars(json_encode($preview_data)) . '">';
        echo '<a href="/admin/import" class="gdv-button secondary">Hủy bỏ</a>';
        echo ' <button type="submit" name="confirm_import_submit" class="gdv-button">Xác nhận Import</button>';
        echo '</form>';
        echo '</div>';

        // Dừng hiển thị phần còn lại của trang (form nhập liệu ban đầu)
        include APP_ROOT . '/templates/partials/footer.php';
        exit(); // Dùng exit() để đảm bảo script dừng hoàn toàn.
    } else {
        // Nếu không có dữ liệu hợp lệ nào được tìm thấy
        set_message('error', 'Không tìm thấy dữ liệu hợp lệ để import. Vui lòng kiểm tra lại nội dung hoặc file của bạn.');
        redirect('/admin/import');
    }
}
?>

<div class="gdv-header"><h1>Nhập câu hỏi</h1></div>

<div class="gdv-card" style="max-width: 900px;">
    <h3 style="margin-top: 0;">Hướng dẫn định dạng</h3>
    <p>Dữ liệu của bạn (dù nhập trực tiếp hay từ file) phải có **7 cột**, phân cách bằng dấu chấm phẩy (<code>;</code>). Dòng tiêu đề (nếu có) sẽ được tự động bỏ qua.</p>
    <ul>
        <li><strong>question:</strong> Nội dung đầy đủ của câu hỏi.</li>
        <li><strong>type:</strong> Loại câu hỏi, chỉ chấp nhận <code>tu_luan</code> hoặc <code>trac_nghiem</code>.</li>
        <li><strong>option_a:</strong> Nội dung lựa chọn A.</li>
        <li><strong>option_b:</strong> Nội dung lựa chọn B.</li>
        <li><strong>option_c:</strong> Nội dung lựa chọn C (có thể để trống).</li>
        <li><strong>option_d:</strong> Nội dung lựa chọn D (có thể để trống).</li>
        <li><strong>correct_answer:</strong> Đáp án đúng (ví dụ: <code>A</code> cho trắc nghiệm) hoặc đáp án mẫu cho tự luận.</li>
    </ul>
</div>

<div class="gdv-card" style="max-width: 900px;">
    <h3>Cách 1: Nhập trực tiếp</h3>
    <p class="gdv-description">Dán nội dung câu hỏi vào khung bên dưới, mỗi câu hỏi một dòng.</p>
    <form method="POST" action="/admin/import">
        <div class="form-group">
            <textarea name="text_data" rows="10" class="input" style="font-family: monospace;" placeholder="Nội dung câu hỏi 1;trac_nghiem;Lựa chọn A;Lựa chọn B;Lựa chọn C;Lựa chọn D;A&#10;Nội dung câu hỏi 2;tu_luan;;;;;Đáp án mẫu cho câu 2"><?php echo htmlspecialchars($text_data_submitted); ?></textarea>
        </div>
        <div class="form-group" style="text-align: right;">
            <button type="submit" name="import_text_submit" class="gdv-button">Nhập từ Text</button>
        </div>
    </form>
</div>

<div class="gdv-card" style="max-width: 900px;">
    <h3>Cách 2: Nhập từ file CSV</h3>
    <form method="POST" action="/admin/import" enctype="multipart/form-data" class="gdv-uploader">
        <div class="gdv-drop-zone">
            <div class="gdv-drop-zone__icon"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg></div>
            <p class="gdv-drop-zone__text">Kéo & thả file vào đây</p>
            <p class="gdv-drop-zone__subtext">hoặc</p>
            <span class="gdv-drop-zone__button">Chọn file từ máy tính</span>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" class="hidden" required>
        </div>
        <div class="gdv-file-list"></div>
        <p class="submit" style="margin-top: 30px; text-align: right;">
            <button type="submit" name="import_file_submit" class="gdv-button">Xem trước & Nhập từ File</button>
        </p>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>