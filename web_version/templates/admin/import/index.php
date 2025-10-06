<?php
// web_version/templates/admin/import/index.php

$page_title = 'Nhập câu hỏi từ CSV';
include APP_ROOT . '/templates/partials/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    if ($file['error'] === UPLOAD_ERR_OK && strtolower($file_extension) === 'csv') {
        $csv_path = $file['tmp_name'];
        $handle = fopen($csv_path, "r");

        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $row_number = 1;

        fgetcsv($handle, 1000, ","); // Skip header row

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row_number++;
            if (count($data) < 4) {
                $error_count++; $errors[] = "Dòng {$row_number}: Không đủ số cột (yêu cầu 4)."; continue;
            }

            $content = trim($data[0]);
            $type = trim(strtolower($data[1]));
            $options_str = trim($data[2]);
            $correct_answer = trim($data[3]);

            if (empty($content) || !in_array($type, ['tu_luan', 'trac_nghiem'])) {
                $error_count++; $errors[] = "Dòng {$row_number}: Nội dung hoặc loại câu hỏi không hợp lệ."; continue;
            }

            $options_json = null;
            if ($type === 'trac_nghiem') {
                json_decode($options_str);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error_count++; $errors[] = "Dòng {$row_number}: Cột 'options' không phải là chuỗi JSON hợp lệ."; continue;
                }
                $options_json = $options_str;
            }

            try {
                $db->insert('questions', ['content' => $content, 'type' => $type, 'options' => $options_json, 'correct_answer' => $correct_answer]);
                $success_count++;
            } catch (Exception $e) {
                $error_count++; $errors[] = "Dòng {$row_number}: Lỗi CSDL - " . $e->getMessage();
            }
        }
        fclose($handle);

        if ($error_count > 0) {
            $error_details_html = '<ul>';
            foreach ($errors as $err) { $error_details_html .= '<li>' . htmlspecialchars($err) . '</li>'; }
            $error_details_html .= '</ul>';
            set_message('warning', "Nhập liệu hoàn tất. Thành công: {$success_count}, Thất bại: {$error_count}.<br><strong>Chi tiết lỗi:</strong>" . $error_details_html);
        } else {
            set_message('success', "Nhập liệu hoàn tất. Đã nhập thành công {$success_count} câu hỏi.");
        }
        redirect('/admin/import');
    } else {
        set_message('error', 'Vui lòng chọn một file CSV hợp lệ.');
    }
}
?>

<div class="gdv-header"><h1>Nhập câu hỏi hàng loạt từ file CSV</h1></div>

<div class="gdv-container" style="max-width: 900px; margin: 20px auto; padding: 40px; background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 12px;">
    <h3>Hướng dẫn định dạng file CSV</h3>
    <p>File CSV của bạn phải được mã hóa UTF-8 và có 4 cột với dòng tiêu đề: <code>content,type,options,correct_answer</code></p>
    <ul>
        <li><strong>content:</strong> Nội dung đầy đủ của câu hỏi.</li>
        <li><strong>type:</strong> Loại câu hỏi, chỉ chấp nhận <code>tu_luan</code> hoặc <code>trac_nghiem</code>.</li>
        <li><strong>options:</strong> Đối với <code>trac_nghiem</code>, đây phải là chuỗi JSON hợp lệ (ví dụ: <code>{"A": "Lựa chọn 1", "B": "Lựa chọn 2"}</code>). Để trống cho <code>tu_luan</code>.</li>
        <li><strong>correct_answer:</strong> Đáp án đúng (ví dụ: <code>A</code> cho trắc nghiệm) hoặc đáp án mẫu cho tự luận.</li>
    </ul>
    <hr style="margin: 30px 0;">
    <form method="POST" action="/admin/import" enctype="multipart/form-data">
        <p><label for="csv_file">Chọn file CSV để tải lên:</label><input type="file" name="csv_file" id="csv_file" accept=".csv" required class="input" style="padding: 10px;"></p>
        <p class="submit" style="margin-top: 20px;"><button type="submit" class="gdv-button" style="font-size: 16px; padding: 12px 24px;">Bắt đầu nhập liệu</button></p>
    </form>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>