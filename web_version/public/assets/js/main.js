jQuery(document).ready(function($) {

    let isTestSubmitted = false; // Biến cờ để theo dõi trạng thái nộp bài

    // --- Custom Popup Logic ---
    const modalOverlay = $('#gdv-custom-modal-overlay');
    const modalTitle = $('#gdv-modal-title');
    const modalMessage = $('#gdv-modal-message');
    const modalIcon = $('#gdv-modal-icon');
    const confirmBtn = $('#gdv-modal-confirm');
    const cancelBtn = $('#gdv-modal-cancel');

    const icons = {
        warning: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#FBBF24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#DC2626"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>',
        success: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#16A34A"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
    };

    /**
     * @param {object} options
     * @param {string} options.title - The title of the popup.
     * @param {string} options.message - The message content.
     * @param {string} [options.type='warning'] - 'warning', 'error', 'success'.
     * @param {boolean} [options.isConfirmation=false] - If true, shows Cancel button.
     * @param {function} [options.onConfirm] - Callback for confirm button.
     * @param {string} [options.confirmText='Đồng ý']
     * @param {string} [options.cancelText='Hủy']
     */
    function showCustomPopup(options) {
        modalTitle.text(options.title);
        modalMessage.html(options.message); // Use .html() to allow for <br>
        modalIcon.html(icons[options.type || 'warning']);

        confirmBtn.text(options.confirmText || 'Đồng ý').removeClass('danger');
        if (options.type === 'error') {
            confirmBtn.addClass('danger');
        }

        if (options.isConfirmation) {
            cancelBtn.show().text(options.cancelText || 'Hủy');
        } else {
            cancelBtn.hide();
        }

        modalOverlay.addClass('visible');

        confirmBtn.off('click').on('click', function() {
            modalOverlay.removeClass('visible');
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
        });

        cancelBtn.off('click').on('click', function() {
            modalOverlay.removeClass('visible');
        });
        
        modalOverlay.off('click').on('click', function(e) {
            if (e.target === this) modalOverlay.removeClass('visible');
        });
    }

    // Xử lý form nộp bài thi
    $('#test-submission-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();
        var submitButton = form.find('button[type="submit"]');

        submitButton.prop('disabled', true).text('Đang nộp bài...');

        $.ajax({
            type: 'POST',
            url: '/api/ajax', // Endpoint AJAX của chúng ta
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    isTestSubmitted = true; // Đánh dấu đã nộp bài thành công để không hiện cảnh báo
                    // Cải tiến: Thay thế form bằng một thông báo cảm ơn đẹp mắt
                    var thankYouMessage = `
                        <div style="text-align: center; padding: 50px 20px; border: 1px solid var(--gdv-border); border-radius: 12px; background: var(--gdv-bg);">
                            <h2 style="color: var(--gdv-primary); font-size: 24px;">Nộp bài thành công!</h2>
                            <p style="color: var(--gdv-text-secondary); margin-top: 10px; margin-bottom: 30px;">${response.message}</p>
                            <a href="/" class="gdv-button">Quay về trang chủ</a>
                        </div>`;
                    $('.lb-take-test-container').html(thankYouMessage);
                } else {
                    showCustomPopup({
                        title: 'Lỗi Nộp Bài',
                        message: response.message,
                        type: 'error'
                    });
                    submitButton.prop('disabled', false).text('Nộp bài');
                }
            },
            error: function() {
                showCustomPopup({
                    title: 'Lỗi Hệ Thống',
                    message: 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại kết nối mạng và thử lại.',
                    type: 'error'
                });
                submitButton.prop('disabled', false).text('Nộp bài');
            }
        });
    });

    // Xử lý form chấm bài của giám khảo
    $('#grading-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var formData = form.serialize();
        var submitButton = form.find('button[type="submit"]');

        submitButton.prop('disabled', true).text('Đang lưu...');

        $.ajax({
            type: 'POST',
            url: '/api/ajax',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showCustomPopup({
                        title: 'Thành Công',
                        message: response.message,
                        type: 'success',
                        onConfirm: () => window.location.href = '/grader/dashboard'
                    });
                } else {
                    showCustomPopup({ title: 'Lỗi', message: response.message, type: 'error' });
                    submitButton.prop('disabled', false).text('Lưu điểm');
                }
            },
            error: function() {
                showCustomPopup({ title: 'Lỗi Hệ Thống', message: 'Đã có lỗi xảy ra. Vui lòng thử lại.', type: 'error' });
                submitButton.prop('disabled', false).text('Lưu điểm');
            }
        });
    });

    // Xử lý đồng hồ đếm ngược
    const timerElement = $('#lb-test-timer');
    if (timerElement.length) {
        const timeInMinutes = parseInt(timerElement.data('time'), 10);
        let timeInSeconds = timeInMinutes * 60;

        const countdownInterval = setInterval(function() {
            if (timeInSeconds <= 0) {
                clearInterval(countdownInterval);
                showCustomPopup({
                    title: 'Hết Giờ!',
                    message: 'Thời gian làm bài đã kết thúc. Hệ thống sẽ tự động nộp bài của bạn.',
                    type: 'warning',
                    onConfirm: () => $('#test-submission-form').trigger('submit')
                });
                // Tự động nộp bài khi hết giờ
                $('#test-submission-form').trigger('submit');
                return;
            }

            timeInSeconds--;

            const minutes = Math.floor(timeInSeconds / 60);
            const seconds = timeInSeconds % 60;

            const formattedMinutes = String(minutes).padStart(2, '0');
            const formattedSeconds = String(seconds).padStart(2, '0');

            timerElement.html(`
                <span>${formattedMinutes}</span>:<span>${formattedSeconds}</span>
            `);

            // Thêm hiệu ứng cảnh báo khi gần hết giờ
            if (timeInSeconds <= 60) {
                timerElement.addClass('warning');
            }
        }, 1000);

        // Thêm cảnh báo khi người dùng rời trang
        $(window).on('beforeunload', function() {
            // Chỉ hiển thị cảnh báo nếu bài thi chưa được nộp
            if (!isTestSubmitted) {
                return 'Bạn có chắc chắn muốn rời khỏi trang? Bài làm của bạn sẽ không được lưu lại.';
            }
        });
    }

    // --- Replace native confirm() on forms ---
    $('form[onsubmit*="confirm"]').each(function() {
        const form = $(this);
        const confirmText = form.attr('onsubmit').match(/confirm\('([^']+)'\)/)[1];
        
        form.removeAttr('onsubmit'); // Remove the original attribute

        form.on('submit', function(e) {
            e.preventDefault(); // Stop the form from submitting immediately
            showCustomPopup({
                title: 'Xác nhận hành động',
                message: confirmText,
                type: 'warning',
                isConfirmation: true,
                onConfirm: () => form.get(0).submit() // Submit the original form if confirmed
            });
        });
    });
});