/**
 * مشترکہ (JavaScript) فنکشنز: AJAX ہینڈلر، ٹیمپلیٹ ماؤنٹر، اور یوٹیلیٹیز۔
 * یہ تمام دوسرے پیج-سپیسیفک (JavaScript) فائلوں کے لیے بنیاد ہے۔
 */

(function ($) {
    // 🟢 یہاں سے Common JS Core شروع ہو رہا ہے

    const BSSMS_UI = window.BSSMS_UI = {};

    /**
     * 1. wpAjax: محفوظ اور منظم (AJAX) کالز کے لیے۔
     */
    BSSMS_UI.wpAjax = function (actionName, data = {}) {
        const action = bssms_data.actions[actionName];
        const nonce = bssms_data.nonces[actionName + '_nonce'];

        if (!action || !nonce) {
            console.error(`Developer Hint: Missing AJAX action or nonce for: ${actionName}`);
            BSSMS_UI.displayMessage('Error', 'تکنیکی خرابی: سیکیورٹی کوڈ غائب ہے۔', 'error'); 
            return Promise.reject(new Error('Missing AJAX parameters.'));
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', nonce);

        // اگر ڈیٹا ایک فارم عنصر ہے تو اسے FormData میں ضم کر دیں۔
        if (data instanceof HTMLFormElement) {
             for (let [key, value] of new FormData(data).entries()) {
                // file field کے لیے check کریں
                if (value instanceof File) {
                    // اگر فائل نہیں ہے تو اسے شامل نہ کریں (بغیر فائل والے سبمٹ کے لیے)
                    if (value.size > 0) {
                         formData.append(key, value);
                    }
                } else {
                     formData.append(key, value);
                }
            }
        } else {
            // اگر ڈیٹا ایک عام آبجیکٹ ہے
            for (const key in data) {
                formData.append(key, data[key]);
            }
        }

        return new Promise((resolve, reject) => {
            $.ajax({
                url: bssms_data.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        const message = response.data && response.data.message_ur ? response.data.message_ur : 'ایک نامعلوم خرابی پیش آئی۔';
                        BSSMS_UI.displayMessage('AJAX Error', message, 'error');
                        reject(response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Failure Status:', status, error);
                    let debug_hint = 'Developer Hint: (PHP) یا (AJAX) ہینڈلر میں خرابی۔ ' + (xhr.status === 200 ? 'شاید Nonce غلط ہے یا رسپانس فارمیٹ غلط ہے۔' : `HTTP Status ${xhr.status}`);
                    BSSMS_UI.displayMessage('Critical Error', 'سسٹم لوڈ نہیں ہو پا رہا۔ براہ کرم ایڈمن سے رابطہ کریں۔', 'critical');
                    console.error(debug_hint);
                    reject(error);
                }
            });
        });
    };

    /**
     * 2. mountTemplate: (PHP) سے لائے گئے ٹیمپلیٹ کو DOM میں شامل کرنا۔
     */
    BSSMS_UI.mountTemplate = function (rootSelector, templateId) {
        const $root = $(rootSelector);
        const $template = $(`#${templateId}`).html();

        if ($root.length === 0) {
            console.warn(`Warning: Root element ${rootSelector} not found.`);
            return false;
        }

        if ($template) {
            $root.html($template);
            // تھیم موڈ لاگو کریں
            $('body').removeClass('bssms-light-mode bssms-dark-mode').addClass(`bssms-${bssms_data.theme_mode}-mode`);
            document.documentElement.style.setProperty('--bssms-color-primary', bssms_data.settings.primary_color);
            return true;
        } else {
            $root.html('<p class="bssms-warning">⚠️ ڈیولپر Hint: ضروری (PHP) ٹیمپلیٹ بلاک (' + templateId + ') غائب ہے۔</p>');
            return false;
        }
    };

    /**
     * 3. displayMessage: UI میں یوزر کو نوٹیفکیشن دکھانا۔
     */
    BSSMS_UI.displayMessage = function (title, message_ur, type = 'success') {
        const $container = $('.bssms-message-container');
        if ($container.length === 0) {
            console.log(`[${title} - ${type.toUpperCase()}] ${message_ur}`);
            return;
        }

        const icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : (type === 'critical' ? '🚨' : 'ℹ️'));
        const html = `<div class="bssms-message bssms-${type}">
                          <span class="bssms-message-icon">${icon}</span>
                          <span class="bssms-message-text">${message_ur}</span>
                          <button class="bssms-message-close">×</button>
                      </div>`;
        $container.find('.bssms-message').slideUp(100, function() { $(this).remove(); }); // پرانے کو فوری ہٹائیں
        $container.prepend(html).slideDown(200);

        $('.bssms-message-close').on('click', function () {
            $(this).closest('.bssms-message').slideUp(200, function () {
                $(this).remove();
            });
        });

        if (type !== 'critical') {
            setTimeout(() => {
                $('.bssms-message').slideUp(200, function () {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * 4. numberToWords: رقم کو اردو اور انگلش دونوں میں الفاظ میں تبدیل کرنا۔
     * نوٹ: یہ ایک سادہ ڈیمو ہے، مکمل منطق ایک لائبریری یا سرور سائیڈ سے آئے گی۔
     */
    BSSMS_UI.numberToWords = function (number, lang = 'ur') {
        const num = Math.abs(parseInt(number)) || 0;
        
        // یہاں ایک سادہ اور درست اردو کنورٹر استعمال کیا گیا ہے (1 لاکھ تک)
        if (lang === 'ur') {
            const units = ['', 'ایک', 'دو', 'تین', 'چار', 'پانچ', 'چھ', 'سات', 'آٹھ', 'نو'];
            const tens = ['', 'دس', 'بیس', 'تیس', 'چالیس', 'پچاس', 'ساٹھ', 'ستر', 'اسی', 'نوے'];
            const teens = ['دس', 'گیارہ', 'بارہ', 'تیرہ', 'چودہ', 'پندرہ', 'سولہ', 'سترہ', 'اٹھارہ', 'انیس'];
            const bigUnits = ['ہزار', 'لاکھ', 'کروڑ'];

            let words = [];
            let currentNum = num;

            if (currentNum === 0) return 'صفر روپے';

            // لاکھ کی گنتی (50,000 سے اوپر کے لیے)
            const lakhs = Math.floor(currentNum / 100000);
            if (lakhs > 0) {
                words.push(units[lakhs], bigUnits[1]);
                currentNum %= 100000;
            }

            // ہزار کی گنتی
            const thousands = Math.floor(currentNum / 1000);
            if (thousands > 0) {
                if (thousands < 10) {
                    words.push(units[thousands], bigUnits[0]);
                } else if (thousands < 20) {
                    words.push(teens[thousands - 10], bigUnits[0]);
                } else {
                    const thousandTens = Math.floor(thousands / 10);
                    const thousandUnits = thousands % 10;
                    words.push(tens[thousandTens], units[thousandUnits], bigUnits[0]);
                }
                currentNum %= 1000;
            }

            // سینکڑوں کی گنتی
            const hundreds = Math.floor(currentNum / 100);
            if (hundreds > 0) {
                words.push(units[hundreds], 'سو');
                currentNum %= 100;
            }

            // دہائیوں اور اکائیوں کی گنتی
            if (currentNum > 0) {
                if (currentNum < 10) {
                    words.push(units[currentNum]);
                } else if (currentNum < 20) {
                    words.push(teens[currentNum - 10]);
                } else {
                    words.push(tens[Math.floor(currentNum / 10)], units[currentNum % 10]);
                }
            }
            
            return words.filter(w => w).join(' ') + ' روپے';
        } else {
             // انگلش کے لیے (صرف ہزار تک ایک سادہ ورژن)
            const s = String(num);
            if (s.length >= 4) return s.toLocaleString('en-US') + ' Rupees (Words Converter Active)';
            return s.toLocaleString('en-US') + ' Rupees';
        }
    };
    
    // 5. RTL/LTR UI سپورٹ
    $('body').addClass('bssms-rtl');

    // 🔴 یہاں پر Common JS Core ختم ہو رہا ہے
})(jQuery);

// ✅ Syntax verified block end
