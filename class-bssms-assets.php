<?php
/**
 * BSSMS_Assets کلاس
 * ایڈمن کے صفحات پر (CSS) اور (JavaScript) اثاثوں کو ان کی ضرورت کے مطابق لوڈ کرنے کا انتظام۔
 * قاعدہ 30 کے تحت، یہ ہر پیج کے لیے سرشار (JS) اور (CSS) کو لوڈ کرتا ہے۔
 */
class BSSMS_Assets {

	/**
	 * تمام ایڈمن اثاثوں کو Enqueue کریں۔
	 *
	 * @param string $hook موجودہ ایڈمن صفحہ ہک۔
	 */
	public static function enqueue_admin_assets( $hook ) {

		// یہ ہک صرف ہمارے پلگ اِن کے صفحات کے لیے ہونا چاہیے۔
		if ( strpos( $hook, 'bssms-' ) === false && strpos( $hook, 'bssms_page_' ) === false ) {
			return;
		}

		// 1. مشترکہ (CSS) اثاثے
		wp_enqueue_style(
			'bssms-common-styles',
			BSSMS_URL . 'bssms-common.css', // قاعدہ 31: مشترکہ CSS
			array(),
			BSSMS_VERSION
		);

		// 2. مشترکہ (JavaScript) اثاثے
		wp_enqueue_script(
			'bssms-common-scripts',
			BSSMS_URL . 'bssms-common.js', // قاعدہ 31: مشترکہ JS
			array( 'jquery' ),
			BSSMS_VERSION,
			true // Footer میں لوڈ کریں
		);

		// 3. (Localized Data) شامل کریں
		self::localize_data();

		// 4. سرشار (Dedicated) پیج اثاثے شامل کریں (قاعدہ 30)
		$page_slug = '';
		
		// WordPress کے مینو ہک سے پیج سلگ نکالیں
		if ( strpos( $hook, 'toplevel_page_bssms-' ) !== false ) {
		    // ٹاپ لیول مینو کے لیے (مثلاً bssms-dashboard)
		    $page_slug = str_replace( 'toplevel_page_', '', $hook );
		} elseif ( strpos( $hook, 'bssms_page_' ) !== false ) {
		    // سب مینیوز کے لیے (مثلاً bssms_page_bssms-admission)
			$page_slug = str_replace( 'bssms_page_', '', $hook );
		}

		if ( empty( $page_slug ) || strpos( $page_slug, 'bssms-' ) === false ) {
		    return; // اگر پیج سلگ ہمارا نہیں ہے تو رک جائیں
		}
		
		// سرشار JS لوڈ کریں
		wp_enqueue_script(
			'bssms-page-' . $page_slug,
			BSSMS_URL . 'pages/' . $page_slug . '.js', // مثال: pages/bssms-admission.js
			array( 'bssms-common-scripts' ),
			BSSMS_VERSION,
			true
		);

		// سرشار CSS لوڈ کریں
		wp_enqueue_style(
			'bssms-page-' . $page_slug,
			BSSMS_URL . 'pages/' . $page_slug . '.css', // مثال: pages/bssms-admission.css
			array( 'bssms-common-styles' ),
			BSSMS_VERSION
		);
	}

	/**
	 * (JavaScript) میں استعمال کے لیے ضروری ڈیٹا کو لوکلائز کریں۔
	 */
	private static function localize_data() {
		$nonce_data = array();
		
		// قاعدہ 15: تمام Slugs/Nonces کو ایک جگہ سے ریکارڈ کریں۔
		$pages = array(
			'admission' => 'bssms-admission',
			'students-list' => 'bssms-students-list',
			'courses-setup' => 'bssms-courses-setup',
			'settings' => 'bssms-settings',
		);
		
		// قاعدہ 12: Page-Link Validation (PHP ↔ JS)
		$ajax_actions = array(
			'save_admission' => 'bssms_save_admission',
			'fetch_students' => 'bssms_fetch_students',
			'save_settings' => 'bssms_save_settings',
			'fetch_courses' => 'bssms_fetch_courses',
			'translate_text' => 'bssms_translate_text', // داخلہ فارم کے لیے نیا ایکشن
		);
		
		// تمام Nonces کو محفوظ طریقے سے (JavaScript) میں بھیجیں
		foreach ( $ajax_actions as $key => $action ) {
			$nonce_data[ $key . '_nonce' ] = wp_create_nonce( $action );
		}

		// کورسز کا ڈیٹا (DB) سے لوڈ کریں (صرف داخلہ پیج کے لیے)
		$all_courses = BSSMS_DB::get_all_active_courses();
		
		// ضروری ڈیٹا لوکلائز کریں۔
		wp_localize_script(
			'bssms-common-scripts',
			'bssms_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonces'   => $nonce_data,
				'pages'    => $pages,
				'actions'  => $ajax_actions,
				'current_user_id' => get_current_user_id(),
				'user_can_manage' => current_user_can( 'bssms_manage_admissions' ),
				'theme_mode' => BSSMS_DB::get_setting( 'theme_mode', 'light' ),
				'language_mode' => BSSMS_DB::get_setting( 'language', 'ur_en' ),
				'courses' => $all_courses, // کورسز کا ڈیٹا
				// قاعدہ 8: مختصر یوزر میسجز
				'messages' => array(
					'saving' => 'معلومات محفوظ کی جا رہی ہیں، براہ کرم انتظار کریں۔',
					'save_success' => 'کامیابی سے محفوظ ہو گیا۔',
					'save_error' => 'محفوظ کرنے میں خرابی پیش آئی۔',
					'missing_fields' => 'براہ کرم تمام ضروری فیلڈز کو پُر کریں۔',
					'translation_error' => 'ترجمہ سروس تک رسائی میں خرابی۔',
					'fee_mismatch' => 'بقایا رقم منفی نہیں ہو سکتی۔',
				),
			)
		);
	}
}

// ✅ Syntax verified block end
/** Part 7 — Students List: Localization Update for Delete Action */

// BSSMS_Assets کلاس کے اندر، localize_data() فنکشن کا نیا اور مکمل کوڈ (پُرانے کی جگہ پر):
private static function localize_data() {
    $nonce_data = array();
    
    // قاعدہ 15: تمام Slugs/Nonces کو ایک جگہ سے ریکارڈ کریں۔
    $pages = array(
        'admission' => 'bssms-admission',
        'students-list' => 'bssms-students-list',
        'courses-setup' => 'bssms-courses-setup',
        'settings' => 'bssms-settings',
    );
    
    // قاعدہ 12: Page-Link Validation (PHP ↔ JS)
    $ajax_actions = array(
        'save_admission' => 'bssms_save_admission',
        'fetch_students' => 'bssms_fetch_students',
        'save_settings' => 'bssms_save_settings',
        'fetch_courses' => 'bssms_fetch_courses',
        'translate_text' => 'bssms_translate_text',
        'delete_admission' => 'bssms_delete_admission', // نیا AJAX ایکشن
    );
    
    // تمام Nonces کو محفوظ طریقے سے (JavaScript) میں بھیجیں
    foreach ( $ajax_actions as $key => $action ) {
        $nonce_data[ $key . '_nonce' ] = wp_create_nonce( $action );
    }

    // کورسز کا ڈیٹا (DB) سے لوڈ کریں (فہرست میں فلٹر کے لیے ضروری)
    $all_courses = BSSMS_DB::get_all_active_courses();
    
    // ضروری ڈیٹا لوکلائز کریں۔
    wp_localize_script(
        'bssms-common-scripts',
        'bssms_data',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonces'   => $nonce_data,
            'pages'    => $pages,
            'actions'  => $ajax_actions,
            'current_user_id' => get_current_user_id(),
            'user_can_manage' => current_user_can( 'bssms_manage_admissions' ),
            'theme_mode' => BSSMS_DB::get_setting( 'theme_mode', 'light' ),
            'language_mode' => BSSMS_DB::get_setting( 'language', 'ur_en' ),
            'courses' => $all_courses, // کورسز کا ڈیٹا
            // قاعدہ 8: مختصر یوزر میسجز
            'messages' => array(
                'saving' => 'معلومات محفوظ کی جا رہی ہیں، براہ کرم انتظار کریں۔',
                'save_success' => 'کامیابی سے محفوظ ہو گیا۔',
                'save_error' => 'محفوظ کرنے میں خرابی پیش آئی۔',
                'missing_fields' => 'براہ کرم تمام ضروری فیلڈز کو پُر کریں۔',
                'translation_error' => 'ترجمہ سروس تک رسائی میں خرابی۔',
                'fee_mismatch' => 'بقایا رقم منفی نہیں ہو سکتی۔',
                'delete_confirm' => 'کیا آپ واقعی اس ریکارڈ کو حذف کرنا چاہتے ہیں؟ یہ عمل واپس نہیں لیا جا سکتا۔', // نیا میسج
                'delete_success' => 'ریکارڈ کامیابی سے حذف ہو گیا۔',
            ),
        )
    );
}

// ✅ Syntax verified block end
/** Part 11 — Courses Setup: Localization Update for CRUD Actions */

// BSSMS_Assets کلاس کے اندر، localize_data() فنکشن کا نیا اور مکمل کوڈ (پُرانے کی جگہ پر):
private static function localize_data() {
    $nonce_data = array();
    
    // قاعدہ 15: تمام Slugs/Nonces کو ایک جگہ سے ریکارڈ کریں۔
    $pages = array(
        'admission' => 'bssms-admission',
        'students-list' => 'bssms-students-list',
        'courses-setup' => 'bssms-courses-setup',
        'settings' => 'bssms-settings',
    );
    
    // قاعدہ 12: Page-Link Validation (PHP ↔ JS)
    $ajax_actions = array(
        'save_admission' => 'bssms_save_admission',
        'fetch_students' => 'bssms_fetch_students',
        'save_settings' => 'bssms_save_settings',
        'translate_text' => 'bssms_translate_text',
        'delete_admission' => 'bssms_delete_admission',
        'fetch_courses' => 'bssms_fetch_courses',
        'save_course' => 'bssms_save_course',   // نیا AJAX ایکشن
        'delete_course' => 'bssms_delete_course', // نیا AJAX ایکشن
    );
    
    // تمام Nonces کو محفوظ طریقے سے (JavaScript) میں بھیجیں
    foreach ( $ajax_actions as $key => $action ) {
        $nonce_data[ $key . '_nonce' ] = wp_create_nonce( $action );
    }

    // کورسز کا ڈیٹا (DB) سے لوڈ کریں (اب کورس سیٹ اپ پیج کے اپنے AJAX سے آئے گا، لیکن یہاں صرف ڈیفالٹ کے لیے رکھیں)
    $all_courses = BSSMS_DB::get_all_active_courses();
    
    // ضروری ڈیٹا لوکلائز کریں۔
    wp_localize_script(
        'bssms-common-scripts',
        'bssms_data',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonces'   => $nonce_data,
            'pages'    => $pages,
            'actions'  => $ajax_actions,
            'current_user_id' => get_current_user_id(),
            'user_can_manage' => current_user_can( 'bssms_manage_admissions' ),
            'theme_mode' => BSSMS_DB::get_setting( 'theme_mode', 'light' ),
            'language_mode' => BSSMS_DB::get_setting( 'language', 'ur_en' ),
            'courses' => $all_courses,
            // قاعدہ 8: مختصر یوزر میسجز
            'messages' => array(
                'saving' => 'معلومات محفوظ کی جا رہی ہیں، براہ کرم انتظار کریں۔',
                'save_success' => 'کامیابی سے محفوظ ہو گیا۔',
                'save_error' => 'محفوظ کرنے میں خرابی پیش آئی۔',
                'missing_fields' => 'براہ کرم تمام ضروری فیلڈز کو پُر کریں۔',
                'translation_error' => 'ترجمہ سروس تک رسائی میں خرابی۔',
                'fee_mismatch' => 'بقایا رقم منفی نہیں ہو سکتی۔',
                'delete_confirm' => 'کیا آپ واقعی اس ریکارڈ کو حذف کرنا چاہتے ہیں؟ یہ عمل واپس نہیں لیا جا سکتا۔',
                'course_delete_confirm' => 'کیا آپ واقعی اس کورس کو حذف کرنا چاہتے ہیں؟ اگر یہ کورس کسی طالب علم کے ریکارڈ میں استعمال ہوا تو یہ صرف غیر فعال ہو جائے گا۔', // نیا میسج
                'delete_success' => 'ریکارڈ کامیابی سے حذف ہو گیا۔',
                'course_add_success' => 'نیا کورس کامیابی سے شامل کر دیا گیا ہے۔',
                'course_update_success' => 'کورس کی تفصیلات کامیابی سے اپ ڈیٹ ہو گئیں۔',
            ),
        )
    );
}

// ✅ Syntax verified block end
/** Part 15 — Settings Page: Localization Update for Settings Data & Reset Action */

// BSSMS_Assets کلاس کے اندر، localize_data() فنکشن کا نیا اور مکمل کوڈ (پُرانے کی جگہ پر):
private static function localize_data() {
    $nonce_data = array();
    
    // قاعدہ 15: تمام Slugs/Nonces کو ایک جگہ سے ریکارڈ کریں۔
    $pages = array(
        'admission' => 'bssms-admission',
        'students-list' => 'bssms-students-list',
        'courses-setup' => 'bssms-courses-setup',
        'settings' => 'bssms-settings',
    );
    
    // قاعدہ 12: Page-Link Validation (PHP ↔ JS)
    $ajax_actions = array(
        'save_admission' => 'bssms_save_admission',
        'fetch_students' => 'bssms_fetch_students',
        'save_settings' => 'bssms_save_settings',
        'fetch_courses' => 'bssms_fetch_courses',
        'translate_text' => 'bssms_translate_text',
        'delete_admission' => 'bssms_delete_admission',
        'save_course' => 'bssms_save_course',
        'delete_course' => 'bssms_delete_course',
        'reset_defaults' => 'bssms_reset_defaults', // نیا AJAX ایکشن
    );
    
    // تمام Nonces کو محفوظ طریقے سے (JavaScript) میں بھیجیں
    foreach ( $ajax_actions as $key => $action ) {
        $nonce_data[ $key . '_nonce' ] = wp_create_nonce( $action );
    }

    // تمام کورسز اور ترتیبات حاصل کریں
    $all_courses = BSSMS_DB::get_all_active_courses();
    // تمام ترتیبات کو بلک میں لوڈ کریں تاکہ (JS) میں استعمال ہوں
    $all_settings = BSSMS_DB::get_settings_bulk([
        'academy_name', 'admin_email', 'default_currency', 'date_format', 'theme_mode', 
        'logo_url', 'enable_bilingual_labels', 'enable_auto_urdu_translation', 'primary_color'
    ]);
    
    // ضروری ڈیٹا لوکلائز کریں۔
    wp_localize_script(
        'bssms-common-scripts',
        'bssms_data',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonces'   => $nonce_data,
            'pages'    => $pages,
            'actions'  => $ajax_actions,
            'current_user_id' => get_current_user_id(),
            'user_can_manage' => current_user_can( 'bssms_manage_admissions' ),
            'theme_mode' => $all_settings['theme_mode'], // تھیم موڈ کو سیٹنگز سے لائیں
            'language_mode' => $all_settings['enable_bilingual_labels'], // بائی لنگوئل کو ٹریک کریں
            'courses' => $all_courses,
            'settings' => $all_settings, // تمام ترتیبات (JS) میں بھیجیں
            // قاعدہ 8: مختصر یوزر میسجز
            'messages' => array(
                'saving' => 'معلومات محفوظ کی جا رہی ہیں، براہ کرم انتظار کریں۔',
                'save_success' => 'کامیابی سے محفوظ ہو گیا۔',
                'save_error' => 'محفوظ کرنے میں خرابی پیش آئی۔',
                'missing_fields' => 'براہ کرم تمام ضروری فیلڈز کو پُر کریں۔',
                'translation_error' => 'ترجمہ سروس تک رسائی میں خرابی۔',
                'fee_mismatch' => 'بقایا رقم منفی نہیں ہو سکتی۔',
                'delete_confirm' => 'کیا آپ واقعی اس ریکارڈ کو حذف کرنا چاہتے ہیں؟',
                'course_delete_confirm' => 'کیا آپ واقعی اس کورس کو حذف کرنا چاہتے ہیں؟',
                'delete_success' => 'ریکارڈ کامیابی سے حذف ہو گیا۔',
                'reset_confirm' => '⚠️ کیا آپ واقعی تمام ترتیبات کو ڈیفالٹ پر ری سیٹ کرنا چاہتے ہیں؟ یہ عمل واپس نہیں لیا جا سکتا۔',
                'reset_success' => 'ترتیبات کامیابی سے ڈیفالٹ پر ری سیٹ کر دی گئیں۔',
            ),
        )
    );
}

// ✅ Syntax verified block end
