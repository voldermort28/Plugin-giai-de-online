<?php
// web_version/public/api/check-phone.php

// Đặt header để trình duyệt hiểu đây là một phản hồi JSON
header('Content-Type: application/json');

// Nạp file khởi động để có thể sử dụng các hàm và biến toàn cục (như $db)
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

$phone_number = $_POST['phone_number'] ?? null;

if (empty($phone_number)) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không được để trống.']);
    exit;
}

// Truy vấn để tìm tên nhân viên từ lần nộp bài gần nhất có cùng số điện thoại.
// Điều này đảm bảo lấy được tên mới nhất nếu nhân viên có thay đổi tên.
$contestant = $db->fetch(
    "SELECT contestant_name FROM submissions WHERE contestant_phone = ? ORDER BY submission_id DESC LIMIT 1",
    [$phone_number]
);

if ($contestant) {
    // Tìm thấy nhân viên
    echo json_encode(['success' => true, 'data' => ['display_name' => $contestant['contestant_name']]]);
} else {
    // Không tìm thấy
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên.']);
}