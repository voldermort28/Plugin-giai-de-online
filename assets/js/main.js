jQuery(document).ready(function($) {

    // --- ADMIN JS: Meta box cho Câu hỏi ---
    const questionTypeSelect = $('#lb_test_loai_cau_hoi');
    const tracNghiemFields = $('#lb_test_trac_nghiem_fields');
    const tuLuanFields = $('#lb_test_tu_luan_fields');

    function toggleQuestionFields() {
        if (questionTypeSelect.val() === 'trac_nghiem') {
            tracNghiemFields.show();
            tuLuanFields.hide();
        } else {
            tracNghiemFields.hide();
            tuLuanFields.show();
        }
    }
    toggleQuestionFields(); // Chạy lần đầu khi tải trang
    questionTypeSelect.on('change', toggleQuestionFields);

    // --- FRONTEND JS: Form nhập thông tin nhân viên (AJAX check SĐT) ---
    const phoneInput = $('#phone_number');
    const nameInput = $('#submitter_name');
    const phoneCheckMsg = $('#phone_check_msg');

    phoneInput.on('blur', function() {
        const phone = $(this).val().trim();
        if (phone.length < 9) { // Simple validation
            phoneCheckMsg.text('');
            nameInput.prop('readonly', false);
            return;
        }

        phoneCheckMsg.text('Đang kiểm tra...');

        $.ajax({
            url: lb_test_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_contestant_phone',
                nonce: lb_test_ajax.phone_check_nonce,
                phone_number: phone
            },
            success: function(response) {
                if (response.success) {
                    nameInput.val(response.data.display_name).prop('readonly', true);
                    phoneCheckMsg.text('Chào mừng bạn trở lại!');
                } else {
                    nameInput.val('').prop('readonly', false);
                    phoneCheckMsg.text('Số điện thoại mới, vui lòng nhập tên của bạn.');
                }
            }
        });
    });

    // --- FRONTEND JS: Form làm bài ---
    const timerDiv = $('#lb-test-timer');
    if (timerDiv.length) {
        let totalMinutes = parseInt(timerDiv.data('time'), 10);
        let timeLeft = totalMinutes * 60;

        let timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('Hết giờ làm bài! Hệ thống sẽ tự động nộp bài của bạn.');
                $('#lb-test-form').submit();
            } else {
                timeLeft--;
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                timerDiv.text('Thời gian còn lại: ' + minutes + ':' + (seconds < 10 ? '0' : '') + seconds);
            }
        }, 1000);
    }
    
    // Xử lý nộp bài bằng AJAX
    $('#lb-test-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = $('#submit-test-btn');
        const resultMsg = $('#test-result-message');
        
        submitBtn.prop('disabled', true).text('Đang nộp bài...');

        $.ajax({
            url: lb_test_ajax.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=submit_test',
            success: function(response) {
                if (response.success) {
                    // Ẩn toàn bộ khu vực làm bài
                    $('#lb-test-content-wrapper').hide();
                    
                    const returnUrl = window.location.origin + window.location.pathname;

                    // SVG Icon cho dấu tick với hiệu ứng
                    const checkIconSvg = `
                        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="checkmark-path" fill="none" stroke="#fff" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    `;

                    // Cấu trúc HTML hiện đại cho màn hình thành công
                    const successHtml = `
                        <div class="lb-test-success-wrapper">
                            <div class="success-icon">${checkIconSvg}</div>
                            <h3>${response.data.message}</h3>
                            <div class="lb-test-success-details">
                                <p><strong>Bài thi:</strong> ${response.data.test_title}</p>
                                <p><strong>Thí sinh:</strong> ${response.data.submitter_name}</p>
                            </div>
                            <a href="${returnUrl}" class="success-button">Làm bài thi khác</a>
                        </div>
                    `;

                    resultMsg.html(successHtml);
                } else {
                    resultMsg.css('color', 'red').text('Lỗi: ' + response.data);
                    submitBtn.prop('disabled', false).text('Nộp bài');
                }
            },
            error: function() {
                resultMsg.css('color', 'red').text('Đã có lỗi xảy ra. Vui lòng tải lại trang và thử lại.');
                submitBtn.prop('disabled', false).text('Nộp bài');
            }
        });
    });

});