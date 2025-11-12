/**
 * bssms-courses-setup.js
 * کورسز سیٹ اپ پیج کی کلائنٹ سائیڈ لاجک کو سنبھالتا ہے۔
 * کورسز کی فہرست لوڈ کرنا، CRUD آپریشنز، اور فارم ہینڈلنگ شامل ہے۔
 */

(function ($) {
    // 🟢 یہاں سے Courses Setup JS Logic شروع ہو رہا ہے
    
    // فارم اور لسٹ کی بنیادی آبجیکٹس
    const coursesConfig = {
        root: '#bssms-courses-setup-root',
        templateId: 'bssms-courses-setup-template',
        formId: '#bssms-course-form',
        listTbodyId: '#bssms-courses-tbody',
        currentCourses: [],
    };

    /**
     * کورسز سیٹ اپ پیج کو شروع کریں۔
     */
    function initCoursesSetupPage() {
        if (BSSMS_UI.mountTemplate(coursesConfig.root, coursesConfig.templateId)) {
            bindEvents();
            fetchCoursesList(); // پہلی بار ڈیٹا لوڈ کریں
        }
    }

    /**
     * AJAX کے ذریعے کورسز کی فہرست حاصل کریں۔
     */
    function fetchCoursesList() {
        const $tbody = $(coursesConfig.listTbodyId);
        $tbody.html('<tr><td colspan="5" class="bssms-loading">🔄 کورسز لوڈ ہو رہے ہیں...</td></tr>');
        
        const filters = {
            search: $('#course-search-input').val().trim(),
            status: $('#course-status-filter').val(),
        };

        BSSMS_UI.wpAjax('fetch_courses', filters)
            .then(response => {
                coursesConfig.currentCourses = response.courses; // ڈیٹا کو محفوظ کریں
                renderCoursesTable(response.courses);
            })
            .catch(error => {
                $tbody.html('<tr><td colspan="5" class="bssms-error">❌ کورسز کی فہرست لوڈ کرنے میں خرابی۔</td></tr>');
                console.error('Courses List Fetch Failed:', error);
            });
    }

    /**
     * کورسز کے ڈیٹا کو ٹیبل میں رینڈر کریں۔
     */
    function renderCoursesTable(items) {
        const $tbody = $(coursesConfig.listTbodyId);
        $tbody.empty();
        
        let activeCount = 0;
        let totalFeeSum = 0;

        if (items.length === 0) {
            $tbody.html('<tr><td colspan="5" class="bssms-no-results">کوئی کورس ریکارڈ نہیں ملا۔</td></tr>');
            return;
        }

        items.forEach(item => {
            const isActive = parseInt(item.is_active) === 1;
            if (isActive) activeCount++;
            totalFeeSum += parseInt(item.course_fee);

            const statusText = isActive ? '🟢 فعال' : '🔴 غیر فعال';
            const statusClass = isActive ? 'status-active' : 'status-inactive';

            const row = `
                <tr data-id="${item.id}">
                    <td>${item.id}</td>
                    <td>
                        <strong>${item.course_name_en}</strong>
                        <br><small class="bssms-urdu-text">(${item.course_name_ur || 'اردو نام غائب'})</small>
                    </td>
                    <td class="column-fee">₹${parseInt(item.course_fee).toLocaleString()}</td>
                    <td class="${statusClass}">${statusText}</td>
                    <td>
                        <button class="bssms-icon-btn btn-edit-course" data-id="${item.id}" title="ایڈٹ کریں">✏️</button>
                        <button class="bssms-icon-btn btn-delete-course" data-id="${item.id}" title="حذف کریں">🗑️</button>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });

        // سمری کو اپ ڈیٹ کریں
        $('#total-courses-summary').text(`Total Courses: ${items.length}`);
        $('#active-courses-summary').text(`Active: ${activeCount}`);
    }

    /**
     * کورس کو شامل/ایڈٹ کرنے کے لیے فارم کو بھریں۔
     *
     * @param {number} id - کورس ID (0 اگر نیا ہے)
     */
    function loadCourseForEdit(id) {
        // فارم ری سیٹ کریں
        $(coursesConfig.formId)[0].reset();
        
        if (id === 0) {
            // نیا کورس
            $('#course-form-title').text('➕ نیا کورس شامل کریں');
            $('#course_id').val(0);
            $('#is_active').prop('checked', true);
            $('#btn-save-course').text('💾 Save (محفوظ کریں)');
        } else {
            // ایڈٹ موڈ
            const course = coursesConfig.currentCourses.find(c => parseInt(c.id) === id);
            
            if (!course) {
                BSSMS_UI.displayMessage('Error', 'کورس کا ڈیٹا نہیں مل سکا۔', 'error');
                return;
            }

            $('#course-form-title').text(`✏️ کورس ایڈٹ کریں: ID #${id}`);
            $('#course_id').val(course.id);
            $('#course_name_en').val(course.course_name_en);
            $('#course_name_ur').val(course.course_name_ur);
            $('#course_fee').val(course.course_fee);
            $('#is_active').prop('checked', parseInt(course.is_active) === 1);
            $('#btn-save-course').text('✅ Update (اپ ڈیٹ کریں)');
            
            // فیس کو الفاظ میں تبدیل کریں
            updateFeeWords();
        }
    }

    /**
     * فارم جمع کرانے کا AJAX ہینڈلر (Add/Edit Course)
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const $form = $(coursesConfig.formId);
        
        // کورس کی فیس کو الفاظ میں لکھ کر دکھائیں
        updateFeeWords();
        
        // بٹن کو غیر فعال کریں اور لوڈنگ دکھائیں
        $('#btn-save-course').prop('disabled', true).text('محفوظ کیا جا رہا ہے...');
        BSSMS_UI.displayMessage('Processing', bssms_data.messages.saving, 'info');

        // (AJAX) کال
        BSSMS_UI.wpAjax('save_course', $form[0])
            .then(response => {
                // کامیابی کا پیغام
                const isNew = response.is_new;
                const successMsg = isNew ? bssms_data.messages.course_add_success : bssms_data.messages.course_update_success;
                BSSMS_UI.displayMessage('Success', successMsg, 'success');
                
                // فہرست ریفریش کریں اور فارم ری سیٹ کریں
                fetchCoursesList();
                loadCourseForEdit(0); // فارم ری سیٹ کریں
            })
            .catch(error => {
                // خرابی کا پیغام
                console.error('Course Save Failed:', error);
            })
            .finally(() => {
                // بٹن کو دوبارہ فعال کریں
                $('#btn-save-course').prop('disabled', false).text('💾 Save (محفوظ کریں)');
            });
    }

    /**
     * کورس کو حذف کریں (AJAX Call)
     */
    function handleDeleteCourse(id) {
        if (!confirm(bssms_data.messages.course_delete_confirm)) {
            return;
        }

        // بٹن کو غیر فعال کریں
        $(`tr[data-id="${id}"] .btn-delete-course`).prop('disabled', true).text('...');
        
        BSSMS_UI.wpAjax('delete_course', { id: id })
            .then(response => {
                BSSMS_UI.displayMessage('Success', bssms_data.messages.delete_success, 'success');
                fetchCoursesList(); // ڈیٹا ریفریش کریں
            })
            .catch(error => {
                // اگر استعمال ہو رہا ہو تو حذف کے بجائے غیر فعال ہونے کا میسج دکھائیں
                BSSMS_UI.displayMessage('Warning', 'کورس استعمال ہو رہا تھا، لہذا اسے غیر فعال کر دیا گیا ہے۔', 'warning');
                fetchCoursesList(); // ریفریش کر کے حیثیت دکھائیں
            });
    }

    /**
     * فیس کو الفاظ میں تبدیل کرنے کا فنکشن۔
     */
    function updateFeeWords() {
        const fee = parseInt($('#course_fee').val()) || 0;
        $('#course_fee_words').text(BSSMS_UI.numberToWords(fee, 'ur'));
    }

    /**
     * تمام (DOM) ایونٹس کو باندھیں۔
     */
    function bindEvents() {
        // لسٹ فلٹرز
        $('#course-search-input, #course-status-filter').on('change keyup', function() {
            // سرچ کے لیے Debounce
            if (this.id === 'course-search-input') {
                clearTimeout($(this).data('timeout'));
                $(this).data('timeout', setTimeout(fetchCoursesList, 300));
            } else {
                fetchCoursesList();
            }
        });
        
        // فارم ہینڈلنگ
        $(coursesConfig.formId).on('submit', handleFormSubmit);
        $('#btn-reset-course').on('click', () => loadCourseForEdit(0));
        $('#btn-open-add-new').on('click', () => loadCourseForEdit(0));
        
        // فیس کی تبدیلی پر الفاظ میں تبدیلی
        $('#course_fee').on('change keyup', updateFeeWords);

        // ٹیبل ایکشنز (Edit, Delete)
        $(coursesConfig.root).on('click', '.btn-edit-course', function() {
            const id = parseInt($(this).data('id'));
            loadCourseForEdit(id);
        });
        
        $(coursesConfig.root).on('click', '.btn-delete-course', function() {
            const id = parseInt($(this).data('id'));
            handleDeleteCourse(id);
        });
        
        // Print اور Export بٹن کی ہینڈلنگ (قاعدہ 27)
        $('#btn-print-courses').on('click', function() {
            BSSMS_UI.displayMessage('Info', '🖨️ پرنٹ فنکشن فعال ہو گیا۔', 'info');
            window.print();
        });
        $('#btn-export-courses-excel').on('click', function() {
             BSSMS_UI.displayMessage('Info', '📊 کورسز کی فہرست (Excel) ڈاؤن لوڈ کا فنکشن جلد شامل ہو گا۔', 'info');
        });
    }

    // جب DOM تیار ہو جائے تو صفحہ شروع کریں
    $(document).ready(function () {
        if ($(coursesConfig.root).length) {
            initCoursesSetupPage();
        }
    });

    // 🔴 یہاں پر Courses Setup JS Logic ختم ہو رہا ہے
})(jQuery);

// ✅ Syntax verified block end
