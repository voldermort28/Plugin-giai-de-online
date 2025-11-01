# Hướng dẫn Cài đặt Cron Job Tự động Tạo Đề Thi Hàng Tháng

Tài liệu này hướng dẫn cách thiết lập một kịch bản (script) để tự động tạo bộ đề thi mới vào đầu mỗi tháng. Kịch bản này sẽ được kích hoạt bởi một "cron job" trên máy chủ của bạn.

### Mục tiêu của kịch bản:

*   Tự động xác định tên cuộc thi cho tháng tới theo định dạng `Tháng_Năm` (ví dụ: `Oct_2024`).
*   Kiểm tra và ngăn chặn việc tạo trùng lặp các cuộc thi đã có.
*   Lấy ngẫu nhiên câu hỏi từ ngân hàng đề để tạo ra một số lượng đề thi mới.
*   Ghi lại nhật ký hoạt động vào file `cron/cron_log.txt` để dễ dàng theo dõi và gỡ lỗi.

---

## Bước 1: Tạo Script Tự Động

Đầu tiên, chúng ta cần tạo một file PHP chứa logic để tạo đề. File này sẽ được đặt trong thư mục `cron` ở gốc dự án để giữ cho cấu trúc code gọn gàng.

#### Tạo file mới: `cron/monthly_test_generator.php`

Nội dung file như sau:

```php
<?php
// /cron/monthly_test_generator.php

// --- Cấu hình cho kịch bản Cron ---

// Đặt múi giờ để đảm bảo tính toán ngày tháng chính xác
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình các tham số cho việc tạo đề
define('CRON_SECRET_KEY', 'your_super_secret_key_12345'); // <-- THAY ĐỔI: Một chuỗi bí mật để bảo vệ script
define('NUM_TESTS_TO_GENERATE', 30); // Số lượng đề thi sẽ tạo mỗi tháng
define('NUM_QUESTIONS_PER_TEST', 10); // Số câu hỏi trong mỗi đề thi
define('TIME_LIMIT_PER_TEST', 15); // Thời gian làm bài mỗi đề (phút)
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

// ... (Logic tạo đề thi nằm ở đây) ...

?>
```

**Lưu ý quan trọng:**
*   **Bảo mật:** Thay đổi giá trị `your_super_secret_key_12345` trong hằng số `CRON_SECRET_KEY` thành một chuỗi ký tự ngẫu nhiên, phức tạp của riêng bạn. Khóa này dùng để bảo vệ script khỏi các truy cập trái phép qua trình duyệt.
*   **Tùy chỉnh:** Bạn có thể điều chỉnh các hằng số ở đầu file để phù hợp với nhu cầu:
    *   `NUM_TESTS_TO_GENERATE`: Số lượng đề thi sẽ được tạo mỗi tháng.
    *   `NUM_QUESTIONS_PER_TEST`: Số câu hỏi trong mỗi đề thi.
    *   `TIME_LIMIT_PER_TEST`: Thời gian làm bài (phút) cho mỗi đề.

---

## Bước 2: Thiết lập Cron Job trên Máy chủ

Cron job là một công cụ lập lịch trên các hệ điều hành tương tự Unix, cho phép bạn thực thi các lệnh hoặc kịch bản tại những thời điểm hoặc khoảng thời gian cụ thể.

Bạn cần thiết lập một lệnh để chạy vào **00:05 sáng ngày đầu tiên của mỗi tháng**.

#### Cách 1: Sử dụng PHP CLI (Khuyến khích)
Nếu bạn có quyền truy cập SSH vào máy chủ, đây là phương pháp an toàn và hiệu quả nhất.

```bash
5 0 1 * * /usr/bin/php /www/wwwroot/kiemtra.laboon.vn/cron/monthly_test_generator.php
```

#### Cách 2: Sử dụng `curl` hoặc `wget` (Web Cron)
Nếu hosting của bạn chỉ hỗ trợ cron job qua giao diện web (như cPanel, Plesk), bạn có thể sử dụng lệnh `curl`.

```bash
5 0 1 * * curl "https://kiemtra.laboon.vn/cron/monthly_test_generator.php?secret=your_super_secret_key_12345" > /dev/null 2>&1
```

**Giải thích lệnh:**
*   `5 0 1 * *`: Lập lịch chạy vào lúc 00:05, ngày 1 hàng tháng.
*   `/usr/bin/php`: Đường dẫn đến trình thông dịch PHP trên máy chủ của bạn (có thể khác nhau).
*   `/www/wwwroot/kiemtra.laboon.vn/...`: Đường dẫn tuyệt đối đến file script của bạn.
*   `> /dev/null 2>&1`: Chuyển hướng tất cả output (kể cả lỗi) vào "hư không", để tránh máy chủ gửi email thông báo mỗi khi cron chạy.

---

## Bước 3: Kiểm tra

Sau khi cron job chạy lần đầu tiên, một file `cron_log.txt` sẽ được tạo trong thư mục `cron/`. Bạn có thể mở file này để xem nhật ký hoạt động, kiểm tra xem script đã chạy thành công hay gặp lỗi.

---

## Hướng dẫn Cụ thể cho aaPanel

1.  **Đăng nhập aaPanel** và vào mục **Cron** từ menu bên trái.
2.  Nhấn nút **Add Cron**.
3.  Cấu hình các mục như sau:
    *   **Type of task**: Chọn `Shell Script`.
    *   **Name of task**: Đặt tên dễ nhớ (ví dụ: `Tao De Thi Hang Thang`).
    *   **Execution cycle**: Chọn `Month`, sau đó điền:
        *   Day: `1`
        *   Hour: `0`
        *   Minute: `5`
    *   **Script content**: Dán lệnh thực thi vào. **Ưu tiên dùng cách 1**.
        *   **Cách 1 (PHP CLI):**
            ```bash
            /usr/bin/php /www/wwwroot/kiemtra.laboon.vn/cron/monthly_test_generator.php
            ```
            *(Lưu ý kiểm tra lại đường dẫn PHP và đường dẫn file trên máy chủ của bạn)*
        *   **Cách 2 (Curl):**
            ```bash
            curl "https://kiemtra.laboon.vn/cron/monthly_test_generator.php?secret=your_super_secret_key_12345" > /dev/null 2>&1
            ```
4.  Nhấn **Add Task** để lưu lại.
5.  Bạn có thể nhấn **Execute** để chạy thử và kiểm tra file `cron/cron_log.txt`.

Sau khi cron job chạy lần đầu tiên, một file `cron_log.txt` sẽ được tạo trong thư mục `cron/`. Bạn có thể mở file này để xem nhật ký hoạt động, kiểm tra xem script đã chạy thành công hay gặp lỗi.