<?php
/**
 * File này tạo một trang riêng cho Giám khảo để xem danh sách mã đề.
 * Shortcode: [danh_sach_ma_de]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Shortcode [danh_sach_ma_de] để hiển thị giao diện danh sách mã đề.
 */
function lb_ma_de_dashboard_shortcode() {
    // Chỉ cho phép người có quyền 'grade_submissions' xem trang này.
    if ( ! is_user_logged_in() || ! current_user_can( 'grade_submissions' ) ) {
        return '<p>Bạn không có quyền truy cập trang này.</p>';
    }

    ob_start();

    // --- Dữ liệu ---
    $grader_dashboard_url = get_site_url(null, '/chamdiem/'); 
    $all_tests = new WP_Query([
        'post_type' => 'dethi_baikiemtra',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    // --- Tối ưu hóa: Lấy trước tất cả các submission liên quan ---
    $all_test_ids = wp_list_pluck($all_tests->posts, 'ID');
    $submissions_by_test_id = [];
    if (!empty($all_test_ids)) {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lb_test_submissions';
        // Lấy tất cả submission cho các test_id này trong 1 truy vấn duy nhất
        $submissions_results = $wpdb->get_results(
            "SELECT test_id, submission_id, submitter_name, end_time, status 
             FROM $submissions_table 
             WHERE test_id IN (" . implode(',', array_map('intval', $all_test_ids)) . ")"
        );
        // Tạo một map để tra cứu nhanh: test_id => submission_object
        foreach ($submissions_results as $sub) {
            $submissions_by_test_id[$sub->test_id] = $sub;
        }
    }

    // Đếm số lượng cho mỗi tab
    $counts = ['all' => 0, 'ready' => 0, 'submitted' => 0, 'graded' => 0];
    if ($all_tests->have_posts()) {
        $counts['all'] = $all_tests->post_count;
        
        while($all_tests->have_posts()) {
            $all_tests->the_post();
            $test_id = get_the_ID();
            $post_status = get_post_status($test_id);

            if ($post_status === 'publish') $counts['ready']++;
            else { // draft
                $submission = $submissions_by_test_id[$test_id] ?? null;
                if ($submission) $counts[$submission->status]++;
            }
        }
        wp_reset_postdata();
    }

    ?>
    <style>
        :root {
            --gdv-bg: #f4f7fe;
            --gdv-white: #ffffff;
            --gdv-primary: #4a43ec;
            --gdv-text-primary: #1a214f;
            --gdv-text-secondary: #7a859f;
            --gdv-border: #e5e9f2;
            --gdv-status-ready-bg: #e9f8f1;
            --gdv-status-ready-text: #28a745;
            --gdv-status-submitted-bg: #fff8e1;
            --gdv-status-submitted-text: #ffc107;
            --gdv-status-graded-bg: #e7f3ff;
            --gdv-status-graded-text: #007bff;
            --gdv-status-nodata-bg: #f8f9fa;
            --gdv-status-nodata-text: #6c757d;
            --gdv-danger-bg: #fdeaea;
            --gdv-danger-text: #dc3545;
        }
        .gdv-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--gdv-bg);
            padding: 20px;
            border-radius: 16px;
        }
        .gdv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .gdv-header h1 {
            font-size: 24px;
            color: var(--gdv-text-primary);
            margin: 0;
        }
        .gdv-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .gdv-tabs {
            display: flex;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .gdv-tabs li {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: var(--gdv-text-secondary);
            background-color: transparent;
            transition: all 0.2s ease-in-out;
        }
        .gdv-tabs li.active, .gdv-tabs li:hover {
            background-color: var(--gdv-white);
            color: var(--gdv-text-primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .gdv-tabs .count {
            background-color: var(--gdv-border);
            color: var(--gdv-text-secondary);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 8px;
        }
        .gdv-table-wrapper {
            background-color: var(--gdv-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .gdv-table {
            width: 100%;
            border-collapse: collapse;
        }
        .gdv-table th, .gdv-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--gdv-border);
            color: var(--gdv-text-secondary);
            font-size: 14px;
        }
        .gdv-table th {
            color: var(--gdv-text-primary);
            font-weight: 600;
        }
        .gdv-table tbody tr:hover {
            background-color: #fafbff;
        }
        .gdv-table td strong {
            color: var(--gdv-text-primary);
            font-weight: 500;
        }
        .gdv-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 12px;
            text-transform: capitalize;
        }
        .gdv-status::before {
            content: '●';
            margin-right: 6px;
        }
        .gdv-status--ready { background-color: var(--gdv-status-ready-bg); color: var(--gdv-status-ready-text); }
        .gdv-status--submitted { background-color: var(--gdv-status-submitted-bg); color: var(--gdv-status-submitted-text); }
        .gdv-status--graded { background-color: var(--gdv-status-graded-bg); color: var(--gdv-status-graded-text); }
        .gdv-status--nodata { background-color: var(--gdv-status-nodata-bg); color: var(--gdv-status-nodata-text); }
        
        .gdv-action-link {
            color: var(--gdv-primary);
            text-decoration: none;
            font-weight: 500;
        }
        .gdv-bulk-actions {
            position: fixed;
            bottom: -100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--gdv-text-primary);
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
        .gdv-bulk-actions.visible {
            bottom: 20px;
        }
        .gdv-bulk-actions button {
            background: transparent;
            border: none;
            color: var(--gdv-white);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        .gdv-bulk-actions button:hover { background-color: rgba(255,255,255,0.1); }
        .gdv-bulk-actions .delete-btn { background-color: var(--gdv-danger-bg); color: var(--gdv-danger-text); }
        .gdv-bulk-actions .delete-btn:hover { background-color: #fbd0d4; }

        .gdv-main-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--gdv-border);
        }
        .gdv-main-tab {
            padding: 10px 20px;
            text-decoration: none;
            color: var(--gdv-text-secondary);
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            margin-bottom: -1px;
        }
        .gdv-main-tab.active, .gdv-main-tab:hover {
            color: var(--gdv-primary);
            border-bottom-color: var(--gdv-primary);
        }

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .gdv-table-wrapper {
                overflow-x: auto; /* Enable horizontal scrolling */
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            }
            .gdv-table th, .gdv-table td {
                white-space: nowrap; /* Prevent content from wrapping */
            }
            .gdv-tabs {
                flex-wrap: wrap;
                gap: 4px;
            }
            .gdv-tabs li {
                padding: 6px 12px;
            }
            .gdv-container {
                padding: 10px;
            }
            .gdv-main-tabs {
                flex-wrap: wrap;
            }
        }
    </style>

    <div class="gdv-container">
        <div class="gdv-main-tabs">
            <a href="<?php echo esc_url(get_site_url(null, '/chamdiem/')); ?>" class="gdv-main-tab">Chấm Bài & Lịch Sử</a>
            <a href="<?php echo esc_url(get_site_url(null, '/code/')); ?>" class="gdv-main-tab active">Danh Sách Đề Thi</a>
        </div>

        <div class="gdv-header">
            <h1>Danh sách Đề thi</h1>
        </div>

        <div class="gdv-toolbar">
            <ul class="gdv-tabs">
                <li data-filter="all">Tất cả <span class="count"><?php echo $counts['all']; ?></span></li>
                <li class="active" data-filter="ready">Sẵn sàng <span class="count"><?php echo $counts['ready']; ?></span></li>
                <li data-filter="submitted">Cần chấm <span class="count"><?php echo $counts['submitted']; ?></span></li>
                <li data-filter="graded">Đã chấm <span class="count"><?php echo $counts['graded']; ?></span></li>
            </ul>
        </div>

        <div class="gdv-table-wrapper">
            <?php lb_render_ma_de_list_table($grader_dashboard_url, $all_tests, $submissions_by_test_id); ?>
        </div>
    </div>

    <div id="gdv-bulk-actions" class="gdv-bulk-actions">
        <span id="gdv-selected-count">0 items selected</span>
        <button id="gdv-bulk-delete-btn" class="delete-btn">Delete</button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.gdv-tabs li');
        const tableRows = document.querySelectorAll('.gdv-table tbody tr');
        const selectAllCheckbox = document.getElementById('gdv-select-all');
        const rowCheckboxes = document.querySelectorAll('.gdv-row-checkbox');
        const bulkActionsBar = document.getElementById('gdv-bulk-actions');
        const selectedCountSpan = document.getElementById('gdv-selected-count');
        const bulkDeleteBtn = document.getElementById('gdv-bulk-delete-btn');

        // Function to filter rows
        function filterRows(filter) {
            tableRows.forEach(row => {
                if (filter === 'all' || row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Tab filtering
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const filter = this.getAttribute('data-filter');
                filterRows(filter);
            });
        });

        // Set initial filter based on active tab
        const initialActiveTab = document.querySelector('.gdv-tabs li.active');
        if (initialActiveTab) {
            filterRows(initialActiveTab.getAttribute('data-filter'));
        }

        // Checkbox logic
        function updateBulkActionsBar() {
            const selectedCheckboxes = document.querySelectorAll('.gdv-row-checkbox:checked');
            const count = selectedCheckboxes.length;
            
            if (count > 0) {
                selectedCountSpan.textContent = `${count} mục đã chọn`;
                bulkActionsBar.classList.add('visible');
            } else {
                bulkActionsBar.classList.remove('visible');
            }
            selectAllCheckbox.checked = (count > 0 && count === rowCheckboxes.length);
        }

        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionsBar();
        });

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionsBar);
        });

        // Bulk delete action
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.gdv-row-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất một đề thi để xóa.');
                return;
            }
            if (confirm(`Bạn có chắc chắn muốn xóa vĩnh viễn ${selectedIds.length} đề thi đã chọn? Tất cả bài làm liên quan cũng sẽ bị xóa. Hành động này không thể hoàn tác.`)) {
                jQuery.ajax({
                    url: lb_test_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bulk_delete_items',
                        nonce: lb_test_ajax.bulk_delete_nonce,
                        item_ids: selectedIds,
                        delete_type: 'test'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            window.location.reload();
                        } else {
                            alert('Lỗi: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Đã xảy ra lỗi không xác định. Vui lòng thử lại.');
                    }
                });
            }
        });

        // Copy to clipboard
        document.body.addEventListener('click', function(event) {
            if (event.target.matches('.copy-ma-de')) {
                const button = event.target;
                const maDe = button.getAttribute('data-code');
                navigator.clipboard.writeText(maDe).then(() => {
                    const originalText = button.innerText;
                    button.innerText = 'Đã chép!';
                    setTimeout(() => { button.innerText = originalText; }, 1500);
                });
            }
        });
    });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('danh_sach_ma_de', 'lb_ma_de_dashboard_shortcode');


/**
 * Hàm render bảng danh sách mã đề.
 */
function lb_render_ma_de_list_table($grader_dashboard_url, $tests_query, $submissions_by_test_id) {
    if ($tests_query->have_posts()) {
        ?>
        <table class="gdv-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="gdv-select-all"></th>
                    <th>Tên đề thi</th>
                    <th>Mã đề</th>
                    <th>Trạng thái</th>
                    <th>Thí sinh</th>
                    <th>Thời gian nộp</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($tests_query->have_posts()) {
                    $tests_query->the_post();
                    $test_id = get_the_ID();
                    $ma_de = get_post_meta($test_id, 'lb_test_ma_de', true);
                    $post_status = get_post_status($test_id);

                    $status_slug = 'nodata';
                    $status_text = 'Không có dữ liệu';
                    $submitter_html = '<td>—</td>';
                    $end_time_html = '<td>—</td>';
                    $action_html = '<td>—</td>';
                    $submission_id_for_delete = null;
                    
                    if ($post_status === 'publish') {
                        $status_slug = 'ready';
                        $status_text = 'Sẵn sàng';
                    } else { // draft status
                        // Tối ưu hóa: Lấy dữ liệu từ map đã được truy vấn trước đó
                        if (isset($submissions_by_test_id[$test_id])) {
                            $submission = $submissions_by_test_id[$test_id];
                            $submission_id_for_delete = $submission->submission_id;
                            $submitter_html = '<td><strong>' . esc_html($submission->submitter_name) . '</strong></td>';
                            $end_time_html = '<td>' . wp_date('d/m/Y, H:i', strtotime($submission->end_time)) . '</td>';
                            $view_url = add_query_arg('submission_id', $submission->submission_id, $grader_dashboard_url);

                            if ($submission->status === 'graded') {
                                $status_slug = 'graded';
                                $status_text = 'Đã chấm';
                                $action_html = '<td><a href="' . esc_url($view_url) . '" class="gdv-action-link">Xem lại</a></td>';
                            } else {
                                $status_slug = 'submitted';
                                $status_text = 'Cần chấm';
                                $action_html = '<td><a href="' . esc_url($view_url) . '" class="gdv-action-link"><strong>Chấm bài</strong></a></td>';
                            }
                        }
                    }
                    ?>
                    <tr data-status="<?php echo $status_slug; ?>">
                        <td><input type="checkbox" class="gdv-row-checkbox" value="<?php echo esc_attr($test_id); ?>"></td>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><code><?php echo esc_html($ma_de); ?></code></td>
                        <td><span class="gdv-status gdv-status--<?php echo $status_slug; ?>"><?php echo $status_text; ?></span></td>
                        <?php echo $submitter_html; ?>
                        <?php echo $end_time_html; ?>
                        <?php echo $action_html; ?>
                    </tr>
                    <?php
                }
                wp_reset_postdata();
                ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p style="padding: 20px; text-align: center;">Chưa có bài kiểm tra nào được tạo.</p>';
    }
}
?>
