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
        :root { /* Inspired by the reference image */
            --gdv-primary: #4F46E5; --gdv-primary-dark: #4338CA; --gdv-white: #fff;
            --gdv-background: #F3F4F6; --gdv-border: #E5E7EB; --gdv-text: #1F2937;
            --gdv-text-secondary: #6B7280; --gdv-text-light: #D1D5DB; --gdv-danger: #DC2626; --gdv-success: #16A34A;
            --gdv-success-bg: #F0FDF4; --gdv-error-bg: #FEF2F2; --gdv-warning-bg: #FFFBEB;
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; color: var(--gdv-text); line-height: 1.6;
            background: linear-gradient(170deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .site-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main.gdv-container { flex-grow: 1; max-width: 1200px; margin: 0 auto; padding: 20px; width: 100%; box-sizing: border-box; }
        
        /* Typography */
        h1, h2, h3, h4, h5, h6 { color: var(--gdv-text); margin-top: 0; margin-bottom: 0.5em; font-weight: 700; }
        h1 { font-size: 1.875rem; } h2 { font-size: 1.5rem; } h3 { font-size: 1.25rem; }
        p { margin-bottom: 1em; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--gdv-text); }
        .gdv-description { color: var(--gdv-text-secondary); margin-top: -0.25em; margin-bottom: 1.5em; }

        /* Card/Form Wrapper */
        .gdv-card {
            background: var(--gdv-white);
            border: 1px solid var(--gdv-border);
            border-top: 4px solid var(--gdv-primary);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin: 40px auto; /* Center the card */
            animation: fadeInSlideUp 0.5s ease-out forwards;
        }
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input, select, textarea { 
            width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gdv-border); 
            border-radius: 8px; box-sizing: border-box; background-color: var(--gdv-white);
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }
        .input:hover, select:hover, textarea:hover { background-color: #f9fafb; }
        .input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--gdv-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        .form-group { margin-bottom: 1.5rem; }

        /* Buttons */
        .gdv-button { 
            background-color: var(--gdv-primary); color: var(--gdv-white); border: 1px solid transparent; 
            padding: 0.625rem 1.25rem; border-radius: 8px; cursor: pointer; text-decoration: none; 
            display: inline-block; font-size: 0.875rem; font-weight: 500;
            transition: background-color 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .gdv-button:hover { 
            background-color: var(--gdv-primary-dark); 
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
        }
        .gdv-button.secondary {
            background-color: var(--gdv-white); color: var(--gdv-text); border-color: var(--gdv-border);
        }
        .gdv-button.secondary:hover { background-color: #f9fafb; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .gdv-button.danger {
            background-color: var(--gdv-danger); border-color: var(--gdv-danger);
        }
        .gdv-button.danger:hover { background-color: #D92D20; }

        /* Action Links */
        .gdv-action-buttons { display: flex; gap: 8px; }
        .gdv-button.small {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        .gdv-button.icon {
            padding: 0.5rem;
            line-height: 1;
        }
        .gdv-button svg {
            width: 1.1em;
            height: 1.1em;
            vertical-align: -0.15em;
        }
        .gdv-action-link { color: var(--gdv-primary); text-decoration: none; font-weight: 500; }
        .gdv-action-link:hover { text-decoration: underline; }
        .gdv-action-link.danger { color: var(--gdv-danger); }

        /* Header & Navigation */
        .gdv-main-header {
            background-color: #1d1d1d;
            padding: 0 20px;
            /* border-bottom: 1px solid var(--gdv-border); */
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
        .gdv-brand { display: flex; align-items: center; text-decoration: none; }
        .gdv-brand img { height: 36px; display: block; }
        .gdv-nav { display: flex; align-items: center; gap: 6px; }
        .gdv-nav-item > a {
            text-decoration: none;
            color: var(--gdv-white); font-weight: 500;
            padding: 8px 12px; border-radius: 8px; white-space: nowrap;
            display: flex; align-items: center; gap: 4px; transition: background-color 0.2s, color 0.2s;
        }
        .gdv-nav-item > a:hover { background-color: #333; color: var(--gdv-white); }
        .gdv-nav-item > a.active, .gdv-nav-item.dropdown.is-open > a.dropdown-toggle {
            background-color: #EDE9FE; /* Light purple background */
            color: var(--gdv-primary); /* Dark purple text */
            font-weight: 600;
        }
        
        /* Dropdown Menu */
        .gdv-nav-item.dropdown { position: relative; }
        .gdv-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--gdv-white);
            border: 1px solid var(--gdv-border);
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 8px;
            min-width: 220px;
            z-index: 1010;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .gdv-nav-item.dropdown:hover > .gdv-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .gdv-dropdown-menu a { display: block; padding: 8px 12px; border-radius: 6px; color: var(--gdv-text); text-decoration: none; transition: background-color 0.2s, color 0.2s; }
        .gdv-dropdown-menu a:hover { background-color: #F3F4F6; }
        .gdv-dropdown-menu a.active, .gdv-dropdown-menu a.active:hover {
            background-color: #EDE9FE;
            color: var(--gdv-primary);
            font-weight: 600;
        }

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
                background-color: var(--gdv-white); margin: 5px 0;
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
                transform: translateY(-10px); /* Hiệu ứng trượt xuống nhẹ nhàng hơn */
                opacity: 0;
                visibility: hidden;
                transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
                z-index: 1005;
                gap: 5px;
            }
            .gdv-nav.is-active { transform: translateY(0); opacity: 1; visibility: visible; }
            .gdv-nav a { width: 100%; box-sizing: border-box; }
            
            /* Đổi màu chữ menu về màu tối trên mobile vì nền trắng */
            .gdv-nav-item > a {
                color: var(--gdv-text);
            }

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
        .gdv-table-wrapper { background: var(--gdv-white); border: 1px solid var(--gdv-border); border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); overflow-x: auto; }
        .gdv-table { width: 100%; border-collapse: collapse; }
        .gdv-table th, .gdv-table td { padding: 0.75rem 1.5rem; text-align: left; border-bottom: 1px solid var(--gdv-border); }
        .gdv-table thead th { background-color: #F9FAFB; color: var(--gdv-text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .gdv-table tbody tr:nth-child(even) { background-color: #F9FAFB; } /* Zebra-striping */
        .gdv-table tbody tr:last-child td { border-bottom: none; }
        .gdv-table td strong { font-weight: 500; color: var(--gdv-text); }
        /* Rank colors */
        .gdv-rank-1 { color: #D4AF37; } /* Gold */
        .gdv-rank-2 { color: #A8A29E; } /* Silver */
        .gdv-rank-3 { color: #A16207; } /* Bronze */
        
        /* Rank colors for leaderboard rows */
        .gdv-table tr.gdv-rank-1 { background-color: rgba(255, 215, 0, 0.1); }
        .gdv-table tr.gdv-rank-2 { background-color: rgba(192, 192, 192, 0.1); }
        .gdv-table tr.gdv-rank-3 { background-color: rgba(205, 127, 50, 0.1); }
        .gdv-table tr[class*="gdv-rank-"] .gdv-rank-cell {
            position: relative;
            overflow: hidden;
        }
        .gdv-table tr.gdv-rank-1 .gdv-rank-cell { color: #B8860B; }
        .gdv-table tr.gdv-rank-2 .gdv-rank-cell { color: #696969; }
        .gdv-table tr.gdv-rank-3 .gdv-rank-cell { color: #8B4513; }


        /* Status Tags */
        .gdv-status {
            display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px;
            font-size: 0.75rem; font-weight: 500;
        }
        .gdv-status.tu_luan { background-color: #E0E7FF; color: #4338CA; }
        .gdv-status.trac_nghiem { background-color: #E0F2FE; color: #0369A1; }
        .gdv-status.submitted { background-color: #FEF3C7; color: #B45309; }
        .gdv-status.graded { background-color: #E0E7FF; color: #4338CA; }
        .gdv-status.ready { background-color: #D1FAE5; color: #059669; }
        .gdv-status.error { /* Thêm style cho trạng thái lỗi/đã dùng */
            background-color: var(--gdv-error-bg); color: #B91C1C;
        }
        .gdv-table tbody tr.is-current-user {
            background-color: var(--gdv-warning-bg) !important;
            border-left: 3px solid #FBBF24;
        }

        /* Bulk Actions Bar */
        .gdv-bulk-actions {
            position: fixed;
            bottom: -100px; /* Bắt đầu ẩn bên dưới */
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--gdv-text);
            color: var(--gdv-white);
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: bottom 0.3s ease-in-out;
            z-index: 1000;
        }
        .gdv-bulk-actions.visible { bottom: 20px; }

        /* Filter/Tabs Bar */
        .gdv-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: var(--gdv-white);
            border-radius: 12px;
            border: 1px solid var(--gdv-border);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        /* New Page Banner */
        .gdv-page-banner {
            background-image: url('https://laboon.vn/wp-content/uploads/2023/10/head-banner1.jpg');
            background-size: cover;
            background-position: center;
            padding: 4rem 1.5rem;
            text-align: center;
            position: relative;
            color: var(--gdv-white);
            margin-bottom: 2rem;
        }
        .gdv-page-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(2px);
        }
        .gdv-page-banner .banner-content {
            position: relative;
            z-index: 1;
        }
        .gdv-page-banner h1 {
            color: var(--gdv-white);
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
        }

        /* New Footer */
        .gdv-main-footer-v2 {
            background-image: url('https://laboon.vn/wp-content/uploads/2023/10/bg-footer-home1-copy-1-scaled.webp');
            background-size: cover;
            background-position: top center;
            color: var(--gdv-text-light);
            padding: 40px 20px;
            font-size: 14px;
            text-align: center;
            position: relative;
        }
        .gdv-main-footer-v2::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: -1;
        }
        .gdv-main-footer-v2 .footer-logo-container {
            margin-bottom: 20px;
            position: relative; z-index: 1;
        }
        .gdv-main-footer-v2 .footer-logo-container img {
            max-width: 200px;
            height: auto;
        }
        .gdv-main-footer-v2 .footer-bottom {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #374151; /* Giữ lại đường kẻ mờ */
            font-size: 13px;
            color: var(--gdv-text-secondary);
            position: relative; z-index: 1;
        }

    </style>
    <style>
        /* ==========================================================================
           Custom Components for Test Taking UI
           ========================================================================== */
        /* --- Test Timer --- */
        #lb-test-timer {
            position: fixed;
            top: 80px; /* Dưới header 20px */
            right: 20px;
            z-index: 1000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1.5em;
            font-weight: bold;
            color: #f0f0f0;
            background-color: rgba(26, 26, 26, 0.9);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            padding: 10px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
            min-width: 160px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        
        /* --- Test Timer Warning Effect --- */
        #lb-test-timer.warning {
            animation: neon-glow-warning 1s infinite alternate;
            color: #ff4d4d;
        }
        @keyframes neon-glow-warning {
            from { box-shadow: 0 0 25px rgba(0, 0, 0, 0.5), 0 0 40px rgba(255, 0, 0, 0.5); }
            to { box-shadow: 0 0 25px rgba(0, 0, 0, 0.5), 0 0 60px rgba(255, 0, 0, 0.8); }
        }

        /* --- Modern Radio & Checkbox --- */
        .test-options label {
            position: relative;
            padding-left: 35px; /* Create space for the custom radio/checkbox */
            cursor: pointer;
            font-size: 1rem;
            user-select: none;
            display: flex; /* Use flexbox for alignment */
            align-items: center;
            min-height: 24px;
        }

        /* Hide the browser's default radio button */
        .test-options input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        /* Create a custom radio button */
        .test-options .checkmark {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            height: 22px;
            width: 22px;
            background-color: #e5e7eb;
            border: 1px solid #d1d5db;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        /* On mouse-over, add a grey background color */
        .test-options label:hover input ~ .checkmark {
            border-color: var(--gdv-primary);
        }

        /* When the radio button is checked, add a blue background */
        .test-options input:checked ~ .checkmark {
            background-color: var(--gdv-primary);
            border-color: var(--gdv-primary);
        }

        /* Create the indicator (the dot/circle - hidden when not checked) */
        .test-options .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 8px; height: 8px;
            border-radius: 50%;
            background: white;
        }

        /* Show the indicator when checked */
        .test-options input:checked ~ .checkmark:after {
            display: block;
        }
    </style>
</head>
<body>

<div class="site-wrapper">

<header class="gdv-main-header">
    <a href="/" class="gdv-brand">
        <img src="https://laboon.vn/wp-content/uploads/2023/10/web_logo1.png" alt="Hệ thống Chấm thi">
    </a>
    <nav class="gdv-nav" id="main-nav" role="navigation">
        <?php if ($auth->check()): ?>
            <div class="gdv-nav-item">
                <a href="/grader/dashboard" class="<?php echo ($current_uri == '/grader/dashboard') ? 'active' : ''; ?>">Chấm bài</a>
            </div>
            <div class="gdv-nav-item">
                <a href="/grader/tests" class="<?php echo in_array($current_uri, ['/grader/tests', '/grader/tests/edit', '/admin/tests/bulk-generate']) ? 'active' : ''; ?>">Đề thi</a>
            </div>
            <div class="gdv-nav-item">
                <a href="/admin/contestants" class="<?php echo in_array($current_uri, ['/admin/contestants', '/admin/contestants/view']) ? 'active' : ''; ?>">Thí sinh</a>
            </div>

            <?php if ($auth->hasRole('admin')): 
                $is_management_active = in_array($current_uri, [
                    '/admin/questions', '/admin/questions/edit',
                    '/admin/users', '/admin/users/edit',
                    '/admin/import'
                ]);
            ?>
                <div class="gdv-nav-item dropdown">
                    <a href="#" class="dropdown-toggle <?php echo $is_management_active ? 'active' : ''; ?>" aria-haspopup="true" aria-expanded="false">
                        Quản lý
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </a>
                    <div class="gdv-dropdown-menu">
                        <a href="/admin/questions" class="<?php echo in_array($current_uri, ['/admin/questions', '/admin/questions/edit']) ? 'active' : ''; ?>">Câu Hỏi</a>
                        <a href="/admin/users" class="<?php echo in_array($current_uri, ['/admin/users', '/admin/users/edit']) ? 'active' : ''; ?>">Users</a>
                        <a href="/admin/import" class="<?php echo ($current_uri == '/admin/import') ? 'active' : ''; ?>">Import Câu Hỏi</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="gdv-nav-item dropdown">
                <a href="#" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                    <?php echo htmlspecialchars($auth->user()['display_name']); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </a>
                <div class="gdv-dropdown-menu">
                    <a href="/logout">Đăng xuất</a>
                </div>
            </div>
        <?php else: ?>
            <div class="gdv-nav-item"><a href="/leaderboard" class="<?php echo ($current_uri == '/leaderboard') ? 'active' : ''; ?>">Bảng Xếp Hạng</a></div>
            <div class="gdv-nav-item"><a href="/" class="<?php echo ($current_uri == '/') ? 'active' : ''; ?>">Vào thi</a></div>
            <div class="gdv-nav-item"><a href="/login" class="<?php echo ($current_uri == '/login') ? 'active' : ''; ?>">Đăng nhập</a></div>
        <?php endif; ?>
    </nav>
    <button class="gdv-hamburger" id="hamburger-button" aria-label="Menu" aria-controls="main-nav" aria-expanded="false">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </button>
</header>

<?php if (isset($page_title) && !empty($page_title)): ?>
<div class="gdv-page-banner">
    <div class="banner-content">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>
</div>
<?php endif; ?>


<main class="gdv-container" role="main">
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

        // Thêm tính năng: click ra ngoài để đóng menu
        document.addEventListener('click', function(event) {
            // Kiểm tra xem menu có đang mở không, và điểm click có nằm ngoài cả menu (nav) và nút hamburger không
            const isClickOutside = !nav.contains(event.target) && !hamburger.contains(event.target);
            if (nav.classList.contains('is-active') && isClickOutside) {
                nav.classList.remove('is-active');
                hamburger.classList.remove('is-active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>

<!-- Custom Modal Popup -->
<div id="gdv-custom-modal-overlay" style="display: none;">
    <div id="gdv-custom-modal">
        <div id="gdv-modal-icon">
            <!-- Icon will be inserted by JS -->
        </div>
        <h3 id="gdv-modal-title">Tiêu đề</h3>
        <p id="gdv-modal-message">Nội dung thông báo.</p>
        <div id="gdv-modal-actions">
            <button id="gdv-modal-cancel" class="gdv-button secondary">Hủy</button>
            <button id="gdv-modal-confirm" class="gdv-button">Đồng ý</button>
        </div>
    </div>
</div>
<style>
    #gdv-custom-modal-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    #gdv-custom-modal-overlay.visible {
        opacity: 1;
        visibility: visible;
    }
    #gdv-custom-modal {
        background: var(--gdv-white);
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        width: 90%;
        max-width: 450px;
        text-align: center;
        transform: scale(0.95);
        transition: transform 0.3s ease;
    }
    #gdv-custom-modal-overlay.visible #gdv-custom-modal {
        transform: scale(1);
    }
    #gdv-modal-icon { margin-bottom: 15px; }
    #gdv-modal-icon svg { width: 50px; height: 50px; }
    #gdv-modal-title {
        font-size: 1.5rem;
        margin-top: 0;
        margin-bottom: 10px;
        color: var(--gdv-text);
    }
    #gdv-modal-message {
        color: var(--gdv-text-secondary);
        margin-bottom: 25px;
        line-height: 1.6;
    }
    #gdv-modal-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    #gdv-modal-actions .gdv-button {
        min-width: 120px;
    }
    #gdv-modal-confirm.danger {
        background-color: var(--gdv-danger);
    }
    #gdv-modal-confirm.danger:hover {
        background-color: #B91C1C;
    }
</style>

<!-- Main content of the page will be rendered after this -->