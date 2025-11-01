<?php
// /cron/monthly_test_generator.php

// --- Cấu hình cho kịch bản Cron ---

// Đặt múi giờ để đảm bảo tính toán ngày tháng chính xác
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình các tham số cho việc tạo đề
define('CRON_SECRET_KEY', 'your_super_secret_key_12345'); // <-- THAY ĐỔI: Một chuỗi bí mật để bảo vệ script
define('NUM_TESTS_TO_GENERATE', 50); // Số lượng đề thi sẽ tạo mỗi tháng
define('NUM_QUESTIONS_PER_TEST', 10); // Số câu hỏi trong mỗi đề thi
define('TIME_LIMIT_PER_TEST', 10); // Thời gian làm bài mỗi đề (phút)
define('LOG_FILE', __DIR__ . '/cron_log.txt'); // File để ghi lại nhật ký hoạt động

/**
 * Ghi nhật ký hoạt động của cron job.
 * @param string $message Nội dung cần ghi.
 */
function cron_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// --- Bảo mật ---
// Ngăn chặn truy cập trực tiếp từ trình duyệt mà không có khóa bí mật
if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== CRON_SECRET_KEY)) {
    http_response_code(403);
    cron_log("CRON FAILED: Forbidden access attempt.");
    die('Forbidden');
}

cron_log("CRON STARTED: Starting monthly test generation...");

// --- Bootstrap Ứng dụng ---
// Định nghĩa đường dẫn gốc của ứng dụng một cách tương đối
define('APP_ROOT', dirname(__DIR__));

// Nạp các file cần thiết
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/core/Database.php';

// Khởi tạo đối tượng Database
// Biến $pdo được định nghĩa trong config/database.php
$db = new Database($pdo);

// --- Logic chính của Cron Job ---

try {
    // 1. Xác định tên cuộc thi cho tháng hiện tại
    // Ví dụ: nếu cron chạy trong tháng 11/2025, nó sẽ tạo cuộc thi 'Nov_2025'.
    $contest_name = date('M_Y'); // Format: Nov_2025
    cron_log("Preparing to generate tests for contest: $contest_name");

    // 2. Kiểm tra xem cuộc thi đã tồn tại chưa
    $existing_contest = $db->fetch("SELECT COUNT(test_id) as count FROM tests WHERE contest_name = ?", [$contest_name]);
    if ($existing_contest && $existing_contest['count'] > 0) {
        cron_log("CRON SKIPPED: Contest '$contest_name' already exists. No new tests generated.");
        echo "Contest '$contest_name' already exists. Skipping.\n";
        exit;
    }

    // 3. Lấy toàn bộ câu hỏi từ ngân hàng đề
    $all_questions = $db->fetchAll("SELECT question_id FROM questions");
    $question_pool_ids = array_column($all_questions, 'question_id');

    if (count($question_pool_ids) < NUM_QUESTIONS_PER_TEST) {
        throw new Exception("Not enough questions in the bank (" . count($question_pool_ids) . ") to create tests with " . NUM_QUESTIONS_PER_TEST . " questions.");
    }

    cron_log("Found " . count($question_pool_ids) . " questions in the bank. Starting generation of " . NUM_TESTS_TO_GENERATE . " tests.");

    // 4. Bắt đầu vòng lặp tạo đề thi
    $generated_count = 0;
    $batch_id = uniqid(); // ID duy nhất cho đợt tạo đề này

    for ($i = 0; $i < NUM_TESTS_TO_GENERATE; $i++) {
        // Trộn và lấy ngẫu nhiên câu hỏi
        shuffle($question_pool_ids);
        $random_questions_for_test = array_slice($question_pool_ids, 0, NUM_QUESTIONS_PER_TEST);

        // Tạo tiêu đề và mã đề
        $test_title = $contest_name . ' - Đề #' . ($i + 1);
        
        // Cải tiến: Đảm bảo mã đề là duy nhất
        do {
            $ma_de = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)); // Dùng random_bytes an toàn hơn
            $is_duplicate = $db->fetch("SELECT test_id FROM tests WHERE ma_de = ?", [$ma_de]);
        } while ($is_duplicate);


        $test_data = [
            'title' => $test_title,
            'contest_name' => $contest_name,
            'ma_de' => $ma_de,
            'time_limit' => TIME_LIMIT_PER_TEST,
            'created_at' => date('Y-m-d H:i:s') // Thêm thời gian tạo
        ];

        // Thêm đề thi vào CSDL
        $new_test_id = $db->insert('tests', $test_data);

        if ($new_test_id) {
            // Gán câu hỏi cho đề thi vừa tạo
            foreach ($random_questions_for_test as $q_id) {
                $db->insert('test_questions', ['test_id' => $new_test_id, 'question_id' => $q_id]);
            }
            $generated_count++;
        }
    }

    cron_log("CRON SUCCESS: Successfully generated $generated_count / " . NUM_TESTS_TO_GENERATE . " tests for contest '$contest_name'.");
    echo "Successfully generated $generated_count tests for contest '$contest_name'.\n";

} catch (Exception $e) {
    $error_message = "CRON FAILED: An error occurred - " . $e->getMessage();
    cron_log($error_message);
    // Gửi email thông báo lỗi cho quản trị viên (tùy chọn, cần cấu hình thêm)
    // mail('admin@yourdomain.com', 'Cron Job Failed: Test Generation', $error_message);
    die($error_message);
}

?>
