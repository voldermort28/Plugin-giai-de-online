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

    // --- FRONTEND JS: Form nhập thông tin thí sinh (AJAX check SĐT) ---
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
                    form.hide();
                    timerDiv.hide();
                    
                    // Lấy URL gốc của trang làm bài (không chứa mã đề)
                    const returnUrl = window.location.origin + window.location.pathname;

                    // Tạo thông báo thành công và nút bấm "Làm bài thi khác"
                    const successHtml = '<h3>' + response.data.message + '</h3>' +
                                      '<a href="' + returnUrl + '" style="text-decoration:none; background:#0073aa; color:white; padding:10px 15px; border-radius:3px; display:inline-block; margin-top:15px;">Làm bài thi khác</a>';

                    resultMsg.css('color', 'green').html(successHtml);
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