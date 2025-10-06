<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Giải Đề Online'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/grader-dashboard.css">
    <style>
        .flash-message { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 8px; font-weight: 500; }
        .flash-message.success { color: #0f5132; background-color: #d1e7dd; border-color: #badbcc; }
        .flash-message.error { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; }
        .flash-message.warning { color: #664d03; background-color: #fff3cd; border-color: #ffecb5; }
        .main-nav { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .main-nav a { margin-left: 15px; text-decoration: none; color: #4f46e5; font-weight: 500; }
        .main-nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="gdv-container">
    <nav class="main-nav">
        <div>
            <a href="/">Trang chủ</a>
            <?php if ($auth->check() && in_array($auth->user()['role'], ['grader', 'admin'])): ?>
                <a href="/grader/dashboard">Dashboard Giám khảo</a>
            <?php endif; ?>
            <?php if ($auth->check() && $auth->hasRole('admin')): ?>
                <a href="/admin/tests">Quản lý Bài thi</a>
                <a href="/admin/questions">Quản lý Câu hỏi</a>
                <a href="/admin/users">Quản lý Người dùng</a>
                <a href="/admin/import">Nhập câu hỏi (CSV)</a>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($auth->check()): ?>
                <span>Xin chào, <?php echo htmlspecialchars($auth->user()['display_name']); ?>!</span>
                <a href="/logout">Đăng xuất</a>
            <?php else: ?>
                <a href="/login">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if ($message = get_message('success')): ?><div class="flash-message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($message = get_message('error')): ?><div class="flash-message error"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($message = get_message('warning')): ?><div class="flash-message warning"><?php echo nl2br($message); ?></div><?php endif; ?>