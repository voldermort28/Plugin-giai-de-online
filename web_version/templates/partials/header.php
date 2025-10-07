<?php
// Lấy URL hiện tại để đánh dấu 'active' cho menu
$current_uri = strtok($_SERVER["REQUEST_URI"], '?');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Hệ thống Chấm thi</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* General Styles */
        :root {
            --gdv-primary: #4F46E5; --gdv-primary-dark: #4338CA; --gdv-white: #fff;
            --gdv-background: #F9FAFB; --gdv-border: #E5E7EB; --gdv-text: #111827;
            --gdv-text-secondary: #6B7280; --gdv-danger: #EF4444; --gdv-success: #10B981;
            --gdv-success-bg: #D1FAE5; --gdv-error-bg: #FEE2E2; --gdv-warning-bg: #FEF3C7;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; margin: 0; background-color: var(--gdv-background); color: var(--gdv-text); line-height: 1.6; }
        .gdv-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Typography */
        h1, h2, h3, h4, h5, h6 { color: var(--gdv-text); margin-top: 0; margin-bottom: 0.5em; font-weight: 600; }
        h1 { font-size: 1.875rem; } h2 { font-size: 1.5rem; } h3 { font-size: 1.25rem; }
        p { margin-bottom: 1em; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--gdv-text); }
        .gdv-description { color: var(--gdv-text-secondary); margin-top: -0.25em; margin-bottom: 1.5em; }

        /* Card/Form Wrapper */
        .gdv-card {
            background: var(--gdv-white);
            border: 1px solid var(--gdv-border);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07);
            padding: 40px;
            margin: 40px auto; /* Center the card */
        }
        .input, select, textarea { 
            width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gdv-border); 
            border-radius: 0.375rem; box-sizing: border-box; background-color: var(--gdv-white);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--gdv-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        .form-group { margin-bottom: 1.5rem; }

        /* Buttons */
        .gdv-button { 
            background-color: var(--gdv-primary); color: var(--gdv-white); border: 1px solid transparent; 
            padding: 0.625rem 1.25rem; border-radius: 0.375rem; cursor: pointer; text-decoration: none; 
            display: inline-block; font-size: 0.875rem; font-weight: 500;
            transition: background-color 0.2s, transform 0.1s;
        }
        .gdv-button:hover { background-color: var(--gdv-primary-dark); transform: translateY(-1px); }
        .gdv-button.secondary {
            background-color: var(--gdv-white); color: var(--gdv-text); border-color: var(--gdv-border);
        }
        .gdv-button.secondary:hover { background-color: #F9FAFB; }
        .gdv-button.danger {
            background-color: var(--gdv-danger); border-color: var(--gdv-danger);
        }
        .gdv-button.danger:hover { background-color: #D92D20; }

        /* Action Links */
        .gdv-action-link { color: var(--gdv-primary); text-decoration: none; font-weight: 500; }
        .gdv-action-link:hover { text-decoration: underline; }
        .gdv-action-link.danger { color: var(--gdv-danger); }

        /* Header & Navigation */
        .gdv-main-header {
            background-color: var(--gdv-white);
            padding: 0 20px;
            border-bottom: 1px solid var(--gdv-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: relative;
        }
        /* Header for content sections */
        .gdv-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gdv-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gdv-brand { font-size: 18px; font-weight: 600; text-decoration: none; color: var(--gdv-text); }
        .gdv-nav { display: flex; align-items: center; gap: 15px; }
        .gdv-nav a { text-decoration: none; color: var(--gdv-text-secondary); font-weight: 500; padding: 8px 12px; border-radius: 4px; white-space: nowrap; }
        .gdv-nav a.active, .gdv-nav a:hover { background-color: #f0f0f1; color: var(--gdv-text); }
        .gdv-hamburger { display: none; } /* Hidden on desktop */

        /* Responsive (Mobile) */
        @media (max-width: 960px) {
            .gdv-hamburger {
                display: block;
                cursor: pointer;
                background: none; border: none; padding: 10px;
                z-index: 1001; /* Ensure it's above the nav */
            }
            .gdv-hamburger .bar {
                display: block; width: 25px; height: 3px;
                background-color: var(--gdv-text); margin: 5px 0;
                transition: 0.4s;
            }
            .gdv-hamburger.is-active .bar:nth-child(1) { transform: rotate(-45deg) translate(-5px, 6px); }
            .gdv-hamburger.is-active .bar:nth-child(2) { opacity: 0; }
            .gdv-hamburger.is-active .bar:nth-child(3) { transform: rotate(45deg) translate(-5px, -6px); }

            .gdv-header {
                flex-wrap: wrap;
                align-items: flex-start;
                padding-bottom: 1rem;
            }

            .gdv-nav {
                position: absolute; top: 60px; right: 0;
                width: 250px;
                background-color: var(--gdv-white);
                flex-direction: column;
                align-items: stretch;
                padding: 10px;
                box-shadow: -2px 2px 5px rgba(0,0,0,0.1);
                border: 1px solid var(--gdv-border);
                border-top: none;
                border-radius: 0 0 0 8px;
                transform: translateY(-150%);
                transition: transform 0.3s ease-in-out;
                z-index: 1000;
                gap: 5px;
            }
            .gdv-nav.is-active { transform: translateY(0); }
            .gdv-nav a { width: 100%; box-sizing: border-box; }

            .gdv-card {
                padding: 20px;
                margin: 20px auto;
            }

            .gdv-table-wrapper {
                overflow-x: auto; /* Cho phép cuộn ngang bảng */
            }
        }

        /* Message/Notification Bar */
        .gdv-message { padding: 1rem 1.25rem; margin: 20px 0; border-radius: 0.5rem; border: 1px solid transparent; }
        .gdv-message.success { background-color: var(--gdv-success-bg); border-color: #A7F3D0; color: #047857; }
        .gdv-message.error { background-color: var(--gdv-error-bg); border-color: #FECACA; color: #B91C1C; }
        .gdv-message.warning { background-color: var(--gdv-warning-bg); border-color: #FDE68A; color: #B45309; }

        /* Table Styles */
        .gdv-table-wrapper { background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 0.75rem; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07); overflow-x: auto; }
        .gdv-table { width: 100%; border-collapse: collapse; }
        .gdv-table th, .gdv-table td { padding: 0.75rem 1.5rem; text-align: left; border-bottom: 1px solid var(--gdv-border); }
        .gdv-table thead th { background-color: #F9FAFB; color: var(--gdv-text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .gdv-table tbody tr:last-child td { border-bottom: none; }
        .gdv-table td strong { font-weight: 500; color: var(--gdv-text); }

        /* Status Tags */
        .gdv-status {
            display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px;
            font-size: 0.75rem; font-weight: 500;
        }
        .gdv-status.tu_luan, .gdv-status.trac_nghiem {
            background-color: #E0E7FF; color: #3730A3;
        }
        .gdv-status.submitted { background-color: #FEF3C7; color: #92400E; }
        .gdv-status.graded { background-color: #D1FAE5; color: #065F46; }
        .gdv-status.ready { background-color: #DBEAFE; color: #1E40AF; }
        .gdv-status.error { /* Thêm style cho trạng thái lỗi/đã dùng */
            background-color: var(--gdv-error-bg); color: #B91C1C;
        }

    </style>
</head>
<body>

<header class="gdv-main-header">
    <a href="/" class="gdv-brand">Hệ thống Chấm thi</a>
    <nav class="gdv-nav" id="main-nav">
        <?php if ($auth->check()): ?>
            <?php if ($auth->hasRole('grader') || $auth->hasRole('admin')): ?>
                <a href="/grader/dashboard" class="<?php echo ($current_uri == '/grader/dashboard') ? 'active' : ''; ?>">Dashboard</a>
                <a href="/grader/tests" class="<?php echo in_array($current_uri, ['/grader/tests', '/grader/tests/edit']) ? 'active' : ''; ?>">Quản lý Đề thi</a>
            <?php endif; ?>
            <?php if ($auth->check() && $auth->hasRole('admin')): ?>
                <a href="/admin/questions" class="<?php echo ($current_uri == '/admin/questions') ? 'active' : ''; ?>">Ngân hàng Câu hỏi</a>
                <a href="/admin/users" class="<?php echo ($current_uri == '/admin/users') ? 'active' : ''; ?>">Quản lý User</a>
                <a href="/admin/import" class="<?php echo ($current_uri == '/admin/import') ? 'active' : ''; ?>">Nhập liệu</a>
            <?php endif; ?>
            <a href="/logout">Đăng xuất (<?php echo htmlspecialchars($auth->user()['display_name']); ?>)</a>
        <?php else: ?>
            <a href="/" class="<?php echo ($current_uri == '/') ? 'active' : ''; ?>">Vào thi</a>
            <a href="/login" class="<?php echo ($current_uri == '/login') ? 'active' : ''; ?>">Đăng nhập</a>
        <?php endif; ?>
    </nav>
    <button class="gdv-hamburger" id="hamburger-button" aria-label="Menu" aria-controls="main-nav" aria-expanded="false">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </button>
</header>

<main class="gdv-container">
    <?php
    // Hiển thị thông báo nếu có
    if (has_message()) {
        $message = get_message();
        echo '<div class="gdv-message ' . htmlspecialchars($message['type']) . '">' . nl2br(htmlspecialchars($message['text'])) . '</div>'; // Đã sửa lỗi get_message()
    }
    ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger-button');
    const nav = document.getElementById('main-nav');

    if (hamburger && nav) {
        hamburger.addEventListener('click', function () {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.classList.toggle('is-active');
            nav.classList.toggle('is-active');
            this.setAttribute('aria-expanded', !isExpanded);
        });
    }
});
</script>

<!-- Main content of the page will be rendered after this -->