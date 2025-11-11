/**
 * bssms-admission.js
 * داخلہ فارم (Admission Form) کی کلائنٹ سائیڈ لاجک کو سنبھالتا ہے۔
 * فیس کیلکولیشن، خودکار ترجمہ، اور فارم جمع کرانا شامل ہے۔
 */

(function ($) {
    // 🟢 یہاں سے Admission JS Logic شروع ہو رہا ہے
    
    // فارم کی بنیادی آبجیکٹس
    const admissionForm = {
        root: '#bssms-admission-root',
        formId: '#bssms-admission-form',
        templateId: 'bssms-admission-form-template',
        courseData: bssms_data.courses || [],
    };

    /**
     * داخلہ فارم کے صفحہ کو شروع کریں۔
     */
    function initAdmissionPage() {
        if (BSSMS_UI.mountTemplate(admissionForm.root, admissionForm.templateId)) {
            populateCourses();
            bindEvents();
            // تھیم موڈ کو یقینی بنائیں
            $('body').addClass(`bssms-${bssms_data.theme_mode}-mode`); 
        }
    }

    /**
     * کورسز کے ڈیٹا کو سلیکٹ فیلڈ میں شامل کریں۔
     * (PHP) سے لوکلائزڈ ڈیٹا استعمال کریں۔
     */
    function populateCourses() {
        const $select = $('#course_select');
        admissionForm.courseData.forEach(course => {
            const optionText = `${course.course_name_ur} (${course.course_name_en}) - ₹${course.course_fee.toLocaleString()}`;
            $select.append(`<option value="${course.id}" data-fee="${course.course_fee}">${optionText}</option>`);
        });
    }

    /**
     * فیس کو خودکار طریقے سے کیلکولیٹ کریں (Total Fee, Due Amount)
     */
    function calculateFees() {
        const $courseSelect = $('#course_select');
        const $paidInput = $('#paid_amount');
        const $totalFeeInput = $('#total_fee');
        const $dueAmountInput = $('#due_amount');
        const $paidWords = $('#paid_amount_words');
        const $dueWords = $('#due_amount_words');

        const selectedOption = $courseSelect.find('option:selected');
        const totalFee = parseInt(selectedOption.data('fee')) || 0;
        const paidAmount = parseInt($paidInput.val()) || 0;
        
        // کل فیس سیٹ کریں (Total Fee)
        $totalFeeInput.val(totalFee);

        // بقایا رقم کیلکولیٹ کریں (Due Amount)
        let dueAmount = totalFee - paidAmount;
        if (dueAmount < 0) {
            dueAmount = 0; // بقایا رقم منفی نہیں ہو سکتی
            $paidInput.val(totalFee); // اگر زیادہ رقم ڈالی جائے تو اسے کل فیس پر محدود کریں
            BSSMS_UI.displayMessage('Warning', bssms_data.messages.fee_mismatch, 'warning');
        }
        $dueAmountInput.val(dueAmount);

        // رقم کو الفاظ میں تبدیل کریں (Number to Words)
        $paidWords.text(BSSMS_UI.numberToWords(paidAmount, 'ur'));
        $dueWords.text(BSSMS_UI.numberToWords(dueAmount, 'ur') + ' بقایا');

        // اگر paid Amount ہو تو Due Amount فیلڈ کو نمایاں کریں
        $dueAmountInput.closest('.bssms-form-group').toggleClass('has-due', dueAmount > 0);
    }

    /**
     * خودکار ترجمہ فنکشن کو ہینڈل کریں
     */
    function handleTranslation() {
        const $fullNameEn = $('#full_name_en');
        const $fatherNameEn = $('#father_name_en');
        
        const nameEn = $fullNameEn.val().trim();
        const fatherEn = $fatherNameEn.val().trim();
        
        if (nameEn || fatherEn) {
            BSSMS_UI.displayMessage('Processing', bssms_data.messages.saving, 'info');

            // 1. Full Name ترجمہ
            BSSMS_UI.wpAjax('translate_text', { text_en: nameEn })
                .then(response => {
                    $('#full_name_ur').val(response.text_ur);
                })
                .catch(() => {
                    BSSMS_UI.displayMessage('Error', bssms_data.messages.translation_error, 'error');
                });
            
            // 2. Father Name ترجمہ
            BSSMS_UI.wpAjax('translate_text', { text_en: fatherEn })
                .then(response => {
                    $('#father_name_ur').val(response.text_ur);
                    BSSMS_UI.displayMessage('Success', 'اردو میں ترجمہ مکمل ہو گیا۔', 'success');
                })
                .catch(() => {
                     BSSMS_UI.displayMessage('Error', bssms_data.messages.translation_error, 'error');
                });
        } else {
            BSSMS_UI.displayMessage('Warning', 'براہ کرم پہلے انگلش میں نام لکھیں۔', 'warning');
        }
    }

    /**
     * فارم کو جمع کرانے کا AJAX ہینڈلر
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const $form = $(admissionForm.formId);
        
        // فارم ڈیٹا میں فائل شامل کرنے کے لیے، ہمیں FormData آبجیکٹ کو استعمال کرنا ہو گا
        const formData = $form[0]; // (JavaScript) DOM element
        
        // Validation چیک (براہ کرم یہ فنکشنز بھی استعمال کریں)
        calculateFees(); // حتمی کیلکولیشن کریں

        const paid = parseInt($('#paid_amount').val()) || 0;
        const total = parseInt($('#total_fee').val()) || 0;
        
        if (paid > total) {
             BSSMS_UI.displayMessage('Error', bssms_data.messages.fee_mismatch, 'error');
             return;
        }

        // بٹن کو غیر فعال کریں اور لوڈنگ دکھائیں
        $('#save_admission_btn').prop('disabled', true).text('محفوظ کیا جا رہا ہے...');
        BSSMS_UI.displayMessage('Processing', bssms_data.messages.saving, 'info');

        // (AJAX) کال (قاعدہ 5)
        BSSMS_UI.wpAjax('save_admission', formData) // FormData آبجیکٹ کو بھیجیں
            .then(response => {
                // کامیابی کا پیغام
                BSSMS_UI.displayMessage('Success', bssms_data.messages.save_success, 'success');
                
                // کامیابی کا کارڈ دکھائیں (لے آؤٹ کے مطابق)
                renderSuccessCard(response);

                // فارم ری سیٹ کریں
                $form[0].reset();
                calculateFees(); // فیس کو دوبارہ صفر پر سیٹ کریں

            })
            .catch(error => {
                // خرابی کا پیغام (AJAX ہینڈلر خود دکھائے گا)
                console.error('Admission Save Failed:', error);
            })
            .finally(() => {
                // بٹن کو دوبارہ فعال کریں
                $('#save_admission_btn').prop('disabled', false).text('💾 Save (محفوظ کریں)');
            });
    }

    /**
     * کامیابی کے بعد رزلٹ کارڈ رینڈر کریں (لے آؤٹ کے مطابق)
     */
    function renderSuccessCard(data) {
        const html = `
            <div class="bssms-success-box">
                <h4 class="bssms-success-title">✅ Admission Saved Successfully!</h4>
                <div class="bssms-success-details">
                    <p><strong>Student Name (Auto-Ref):</strong> ${data.student_name_en}</p>
                    <p><strong>Selected Course:</strong> ${data.course_name_en}</p>
                    <p><strong>Paid:</strong> ₹${data.paid.toLocaleString('en-US')}</p>
                    <p><strong>Due:</strong> ₹${data.due.toLocaleString('en-US')}</p>
                    <div class="bssms-progress-bar">
                        <div class="bssms-progress-fill" style="width: ${data.percentage}%;"></div>
                        <span class="bssms-progress-text">${data.percentage}% Paid</span>
                    </div>
                </div>
                <div class="bssms-card-actions">
                     <button class="bssms-btn bssms-btn-secondary" onclick="window.print()">پرنٹ سلپ</button>
                     <button class="bssms-btn bssms-btn-primary" onclick="window.location.reload()">نیا داخلہ</button>
                </div>
            </div>
        `;
        $('#bssms-admission-success-card').html(html).slideDown(300);
        
        // کچھ دیر بعد چھپائیں
        setTimeout(() => {
            $('#bssms-admission-success-card').slideUp(500);
        }, 8000); 
    }

    /**
     * تمام (DOM) ایونٹس کو باندھیں۔
     */
    function bindEvents() {
        // فیس کی کیلکولیشن کے لیے
        $('#course_select, #paid_amount').on('change keyup', calculateFees);

        // رقم کو الفاظ میں تبدیل کرنے کے لیے
        $('#convert_to_words_btn').on('click', calculateFees); // پہلے ہی calculateFees میں شامل ہے

        // اردو ترجمہ
        $('#translate_urdu_btn').on('click', handleTranslation);

        // فارم جمع کرانا
        $(admissionForm.formId).on('submit', handleFormSubmit);

        // اسکرین شاٹ فائل نام کی تبدیلی دکھائیں
        $('#payment_screenshot').on('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : 'فائل منتخب نہیں کی گئی';
            $('#file_preview_name').text(fileName);
        });
        
        // Print اور Export بٹن کی ہینڈلنگ (قاعدہ 27)
        $('#print_btn').on('click', function() {
            window.print();
        });
        $('#export_excel_btn').on('click', function() {
             BSSMS_UI.displayMessage('Info', '📊 یہ فنکشن ابھی فعال نہیں ہے، براہ کرم Save کر کے لسٹ سے ایکسپورٹ کریں۔', 'info');
        });
    }

    // جب DOM تیار ہو جائے تو صفحہ شروع کریں
    $(document).ready(function () {
        // یہ صرف تب ہی چلے گا جب ہم درست پیج پر ہوں
        if ($(admissionForm.root).length) {
            initAdmissionPage();
        }
    });

    // 🔴 یہاں پر Admission JS Logic ختم ہو رہا ہے
})(jQuery);

// ✅ Syntax verified block end
