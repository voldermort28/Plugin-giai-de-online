<?php
if (!defined('ABSPATH')) exit;

/**
 * Hiển thị form đăng nhập cho Giám khảo nếu người dùng chưa đăng nhập.
 *
 * @return bool Trả về true nếu form được hiển thị (và hàm nên dừng lại), false nếu người dùng đã đăng nhập.
 */
function lb_test_render_grader_login_form() {
    if (is_user_logged_in()) {
        return false;
    }

    ob_start();
    ?>
    <div class="gdv-container">
        <h2 style="text-align: center;">Vui lòng đăng nhập</h2>
        <p style="text-align: center;">Bạn cần đăng nhập với tài khoản Giám khảo để xem trang này.</p>
        <?php
        wp_login_form([
            'echo'           => true,
            'redirect'       => home_url($_SERVER['REQUEST_URI']),
            'label_username' => __('Tên tài khoản hoặc Email'),
            'label_password' => __('Mật khẩu'),
            'label_remember' => __('Ghi nhớ'),
            'label_log_in'   => __('Đăng nhập'),
        ]);
        ?>
        <p class="login-lost-password"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php echo __('Quên mật khẩu?'); ?></a></p>
    </div>
    <?php
    // Sử dụng echo thay vì return để hàm wp_login_form hoạt động đúng
    echo ob_get_clean();
    return true;
}

/**
 * Hiển thị thanh điều hướng chính cho các trang của Giám khảo.
 *
 * @param string $active_tab Slug của tab đang hoạt động (e.g., 'chamdiem', 'code', 'bxh', 'hosothisinh').
 */
function lb_test_render_grader_main_tabs($active_tab = '') {
    $tabs = [
        'chamdiem'   => ['label' => 'Chấm Bài & Lịch Sử', 'url' => site_url('/chamdiem/')],
        'code'       => ['label' => 'Danh Sách Đề Thi', 'url' => site_url('/code/')],
        'bxh'        => ['label' => 'Bảng Xếp Hạng', 'url' => site_url('/bxh/')],
        'hosothisinh' => ['label' => 'Hồ sơ Thí sinh', 'url' => site_url('/hosothisinh/')],
    ];
    ?>
    <div class="gdv-main-tabs">
        <?php foreach ($tabs as $slug => $tab) : ?>
            <a href="<?php echo esc_url($tab['url']); ?>" class="gdv-main-tab <?php echo ($slug === $active_tab) ? 'active' : ''; ?>"><?php echo esc_html($tab['label']); ?></a>
        <?php endforeach; ?>
    </div>
    <?php
}