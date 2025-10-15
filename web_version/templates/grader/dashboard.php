<?php
// web_version/templates/grader/dashboard.php

$page_title = 'Chấm Bài';
include APP_ROOT . '/templates/partials/header.php';

// Fetch pending submissions
$pending_submissions = $db->fetchAll("
    SELECT s.*, t.title as test_title 
    FROM submissions s
    JOIN tests t ON s.test_id = t.test_id
    WHERE s.status = 'submitted'
    ORDER BY s.submission_time DESC
");

// Fetch graded submissions
$graded_submissions = $db->fetchAll("
    SELECT s.*, t.title as test_title 
    FROM submissions s
    JOIN tests t ON s.test_id = t.test_id
    WHERE s.status = 'graded'
    ORDER BY s.submission_time DESC
");

// Fetch total questions for graded submissions to display score like '8/10'
$graded_submission_ids = array_column($graded_submissions, 'submission_id');
$question_counts = [];
if (!empty($graded_submission_ids)) {
    $in_clause = implode(',', array_fill(0, count($graded_submission_ids), '?'));
    $results = $db->fetchAll(
        "SELECT submission_id, COUNT(answer_id) as total 
         FROM answers 
         WHERE submission_id IN ($in_clause) 
         GROUP BY submission_id",
        $graded_submission_ids
    );
    foreach ($results as $row) {
        $question_counts[$row['submission_id']] = $row['total'];
    }
}
?>

<h2 style="margin-top: 2rem;">Các bài thi cần chấm</h2>
<div class="gdv-table-wrapper">
    <table class="gdv-table" id="pending-table">
        <thead>
            <tr>
                <th>Bài thi</th>
                <th>Nhân viên</th>
                <th>Thời gian nộp</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pending_submissions)): ?>
                <tr><td colspan="4" style="text-align: center; padding: 2rem;">Không có bài thi nào cần chấm.</td></tr>
            <?php else: foreach ($pending_submissions as $sub): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sub['test_title']); ?></strong></td>
                    <td>
                        <a href="/admin/contestants/view?phone=<?php echo urlencode($sub['contestant_phone']); ?>" class="gdv-action-link" target="_blank">
                            <strong><?php echo htmlspecialchars($sub['contestant_name']); ?></strong>
                        </a>
                    </td>
                    <td><?php echo date('d/m/Y, H:i', strtotime($sub['submission_time'])); ?></td>
                    <td>
                        <a href="/grader/grade?submission_id=<?php echo $sub['submission_id']; ?>" class="gdv-button small">Chấm bài</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<h2 style="margin-top: 3rem;">Lịch sử chấm bài</h2>
<div class="gdv-table-wrapper">
    <table class="gdv-table" id="graded-table">
        <thead>
            <tr>
                <th>Bài thi</th>
                <th>Nhân viên</th>
                <th>Điểm số</th>
                <th>Thời gian nộp</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($graded_submissions)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 2rem;">Chưa có bài thi nào được chấm.</td></tr>
            <?php else: foreach ($graded_submissions as $sub): 
                $total_questions = $question_counts[$sub['submission_id']] ?? 0;
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sub['test_title']); ?></strong></td>
                    <td>
                        <a href="/admin/contestants/view?phone=<?php echo urlencode($sub['contestant_phone']); ?>" class="gdv-action-link" target="_blank">
                            <strong><?php echo htmlspecialchars($sub['contestant_name']); ?></strong>
                        </a>
                    </td>
                    <td><strong><?php echo intval($sub['score']); ?>/<?php echo $total_questions; ?></strong></td>
                    <td><?php echo date('d/m/Y, H:i', strtotime($sub['submission_time'])); ?></td>
                    
                    <td>
                        <div class="gdv-action-buttons">
                            <a href="/grader/grade?submission_id=<?php echo $sub['submission_id']; ?>&view_mode=review" class="gdv-button small secondary">Xem lại</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>