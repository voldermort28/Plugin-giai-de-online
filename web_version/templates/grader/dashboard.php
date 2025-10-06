<?php
// web_version/templates/grader/dashboard.php

$page_title = 'Dashboard Chấm bài';
include APP_ROOT . '/templates/partials/header.php';

$status_filter = $_GET['status'] ?? 'submitted';
$search_query = $_GET['search'] ?? '';

$sql = "
    SELECT 
        s.submission_id, s.contestant_name, s.contestant_phone, 
        s.submission_time, s.status, s.score,
        t.title as test_title, t.ma_de
    FROM submissions s
    JOIN tests t ON s.test_id = t.test_id
";

$params = [];
$where_clauses = [];

if (!empty($status_filter) && $status_filter !== 'all') {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(s.contestant_name LIKE ? OR s.contestant_phone LIKE ?)";
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY s.submission_time DESC";

$submissions = $db->fetchAll($sql, $params);
?>

<div class="gdv-header">
    <h1>Bài làm của thí sinh</h1>
</div>

<div class="gdv-toolbar">
    <ul class="gdv-tabs">
        <li class="<?php echo $status_filter === 'submitted' ? 'active' : ''; ?>" onclick="window.location.href='?status=submitted'">Chưa chấm</li>
        <li class="<?php echo $status_filter === 'graded' ? 'active' : ''; ?>" onclick="window.location.href='?status=graded'">Đã chấm</li>
        <li class="<?php echo $status_filter === 'all' ? 'active' : ''; ?>" onclick="window.location.href='?status=all'">Tất cả</li>
    </ul>

    <form method="GET" action="/grader/dashboard" class="gdv-search-box">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <input type="search" name="search" placeholder="Tìm theo tên hoặc SĐT..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" style="display:none;">Search</button>
    </form>
</div>

<div class="gdv-table-wrapper">
    <table class="gdv-table">
        <thead>
            <tr>
                <th>Thí sinh</th>
                <th>Bài kiểm tra</th>
                <th>Thời gian nộp</th>
                <th>Trạng thái</th>
                <th>Điểm</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($submissions)): ?>
                <tr><td colspan="6" style="text-align: center;">Không có bài nộp nào phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td>
                            <div class="gdv-avatar-cell">
                                <div class="gdv-avatar"><?php echo strtoupper(mb_substr($sub['contestant_name'], 0, 1)); ?></div>
                                <div class="gdv-avatar-info">
                                    <div class="name"><?php echo htmlspecialchars($sub['contestant_name']); ?></div>
                                    <div class="subtext"><?php echo htmlspecialchars($sub['contestant_phone']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($sub['test_title']); ?></strong>
                            <div class="subtext">Mã đề: <?php echo htmlspecialchars($sub['ma_de']); ?></div>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($sub['submission_time'])); ?></td>
                        <td><span class="gdv-status gdv-status--<?php echo htmlspecialchars($sub['status']); ?>"><?php echo htmlspecialchars($sub['status']); ?></span></td>
                        <td><strong><?php echo $sub['score'] !== null ? htmlspecialchars($sub['score']) : 'N/A'; ?></strong></td>
                        <td><a href="/grader/grade?id=<?php echo $sub['submission_id']; ?>" class="gdv-action-link"><?php echo $sub['status'] === 'submitted' ? 'Chấm bài' : 'Xem lại'; ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_ROOT . '/templates/partials/footer.php'; ?>