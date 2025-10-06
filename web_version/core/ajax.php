<?php
// web_version/core/ajax.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Chỉ chấp nhận phương thức POST.']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'submit_test':
        handle_submit_test($db);
        break;
    case 'save_grade':
        handle_save_grade($db);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
        break;
}

function handle_submit_test(Database $db) {
    $test_id = $_POST['test_id'] ?? null;
    $ma_de = $_POST['ma_de'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $submitter_name = $_POST['submitter_name'] ?? '';
    $answers = $_POST['answers'] ?? [];

    if (!$test_id || empty($ma_de) || empty($phone_number) || empty($submitter_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu nộp bài không đầy đủ.']);
        return;
    }

    $submission_id = $db->insert('submissions', [
        'test_id' => $test_id,
        'contestant_name' => $submitter_name,
        'contestant_phone' => $phone_number,
        'submission_time' => date('Y-m-d H:i:s'),
        'status' => 'submitted'
    ]);

    foreach ($answers as $question_id => $answer_content) {
        $db->insert('answers', [
            'submission_id' => $submission_id,
            'question_id' => $question_id,
            'answer_content' => is_array($answer_content) ? json_encode($answer_content) : $answer_content
        ]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Bài làm của bạn đã được nộp thành công!', 'submission_id' => $submission_id]);
}

function handle_save_grade(Database $db) {
    $submission_id = $_POST['submission_id'] ?? null;
    $grader_id = $_POST['grader_id'] ?? null;
    $score = $_POST['score'] ?? null;

    if (!$submission_id || !$grader_id || $score === null) {
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu chấm bài không đầy đủ.']);
        return;
    }

    $rows_affected = $db->update('submissions', [
        'score' => $score,
        'status' => 'graded',
        'grader_id' => $grader_id
    ], 'submission_id = ?', [$submission_id]);

    echo json_encode(['status' => 'success', 'message' => 'Đã lưu điểm thành công!', 'rows_affected' => $rows_affected]);
}