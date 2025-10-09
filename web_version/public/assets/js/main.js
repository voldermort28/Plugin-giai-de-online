jQuery(document).ready(function($) {

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
                    // Cải tiến: Thay thế form bằng một thông báo cảm ơn đẹp mắt
                    var thankYouMessage = `
                        <div style="text-align: center; padding: 50px 20px; border: 1px solid var(--gdv-border); border-radius: 12px; background: var(--gdv-bg);">
                            <h2 style="color: var(--gdv-primary); font-size: 24px;">Nộp bài thành công!</h2>
                            <p style="color: var(--gdv-text-secondary); margin-top: 10px; margin-bottom: 30px;">${response.message}</p>
                            <a href="/" class="gdv-button">Quay về trang chủ</a>
                        </div>`;
                    $('.lb-take-test-container').html(thankYouMessage);
                } else {
                    alert('Lỗi: ' + response.message);
                    submitButton.prop('disabled', false).text('Nộp bài');
                }
            },
            error: function() {
                alert('Đã có lỗi xảy ra với hệ thống. Vui lòng thử lại.');
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
                    alert(response.message); // Giữ lại alert cho admin/grader để xác nhận nhanh
                    window.location.href = '/grader/dashboard'; // Tải lại trang dashboard để cập nhật danh sách
                } else {
                    alert('Lỗi: ' + response.message);
                    submitButton.prop('disabled', false).text('Lưu điểm');
                }
            },
            error: function() {
                alert('Đã có lỗi xảy ra với hệ thống. Vui lòng thử lại.');
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
                timerElement.text("Hết giờ!");
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
    }
});