<?php
/**
 * Plugin Name: Baba Smart School Management System (BSSMS)
 * Description: AI اکیڈمی کے لیے ایڈمیشن، فیس مینجمنٹ، اور رپورٹنگ سسٹم. (PHP), (JS), (CSS) کو استعمال کرتا ہے.
 * Version: 1.0.0
 * Author: Gemini Architect AI
 * License: GPL2
 * Text Domain: bssms
 * Domain Path: /languages
 */

// 🟢 یہاں سے Core Plugin Code شروع ہو رہا ہے
if ( ! defined( 'ABSPATH' ) ) {
	exit; // براہ راست رسائی ممنوع ہے۔
}

// پلگ اِن کا بنیادی پاتھ اور یو آر ایل ڈیفائن کریں۔
define( 'BSSMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSSMS_URL', plugin_dir_url( __FILE__ ) );
define( 'BSSMS_VERSION', '1.0.0' );

/**
 * کلاسز کو خودکار طور پر لوڈ کرنے کا فنکشن۔
 * یہ فنکشن پلگ اِن میں موجود تمام ضروری (PHP) کلاسز کو ڈھونڈ کر لوڈ کرتا ہے۔
 *
 * @param string $class_name وہ کلاس جو لوڈ کرنی ہے۔
 */
function bssms_autoload_classes( $class_name ) {
	if ( 0 !== strpos( $class_name, 'BSSMS_' ) ) {
		return;
	}

	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	$file_path = BSSMS_PATH . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}
spl_autoload_register( 'bssms_autoload_classes' );

/**
 * پلگ اِن کو ایکٹیویٹ کرنے کا فنکشن۔
 * یہ (DB) ٹیبلز بناتا ہے اور کسٹم رولز کو شامل کرتا ہے۔
 *
 * @uses BSSMS_Activator
 */
function bssms_activate_plugin() {
	BSSMS_Activator::activate();
}
register_activation_hook( __FILE__, 'bssms_activate_plugin' );

/**
 * پلگ اِن کی مرکزی کلاس کو شروع کرنا۔
 */
class BSSMS_Core {

	/**
	 * BSSMS_Core کا سنگلٹن انسٹینس۔
	 */
	protected static $instance = null;

	/**
	 * سنگلٹن انسٹینس حاصل کریں۔
	 *
	 * @return BSSMS_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * کنسٹرکٹر
	 */
	protected function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * ضروری کلاس فائلیں شامل کریں۔
	 */
	private function includes() {
		// بنیادی کلاسز یہاں پہلے سے ہی autoload ہو رہی ہیں۔
	}

	/**
	 * تمام ہکس (Hooks) کو سیٹ اپ کریں۔
	 */
	private function hooks() {
		// (PHP) ایڈمن مینو اور اثاثے لوڈ کریں۔
		add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( 'BSSMS_Assets', 'enqueue_admin_assets' ) );

		// (AJAX) ہینڈلر کو رجسٹر کریں۔
		add_action( 'wp_ajax_bssms_save_admission', array( 'BSSMS_Ajax', 'handle_save_admission' ) );
		add_action( 'wp_ajax_bssms_fetch_students', array( 'BSSMS_Ajax', 'handle_fetch_students' ) );
		// مزید (AJAX) ایکشنز بعد میں شامل ہوں گے۔
	}

	/**
	 * ایڈمن مینو شامل کریں۔
	 *
	 * قاعدہ 12 اور 15: Slugs ہمیشہ مطابقت رکھیں۔
	 */
	public function add_plugin_menu() {
		add_menu_page(
			esc_html__( 'بابا اکیڈمی', 'bssms' ), // Page Title
			esc_html__( 'بابا اکیڈمی', 'bssms' ), // Menu Title
			'bssms_manage_admissions', // Capability: نیا رول
			'bssms-dashboard', // Menu Slug
			array( $this, 'render_dashboard_page' ), // Callback
			'dashicons-welcome-learn-more', // Icon
			6 // Position
		);

		// 1. داخلہ فارم
		add_submenu_page(
			'bssms-dashboard',
			esc_html__( 'داخلہ فارم', 'bssms' ),
			esc_html__( 'داخلہ فارم', 'bssms' ),
			'bssms_create_admission', // Capability
			'bssms-admission', // Slug
			array( $this, 'render_admission_page' )
		);

		// 2. طالب علم کی فہرست
		add_submenu_page(
			'bssms-dashboard',
			esc_html__( 'طالب علم کی فہرست', 'bssms' ),
			esc_html__( 'طالب علم کی فہرست', 'bssms' ),
			'bssms_manage_admissions', // Capability
			'bssms-students-list', // Slug
			array( $this, 'render_students_list_page' )
		);

		// 3. کورسز سیٹ اپ (صرف ایڈمن کیلئے)
		add_submenu_page(
			'bssms-dashboard',
			esc_html__( 'کورسز سیٹ اپ', 'bssms' ),
			esc_html__( 'کورسز سیٹ اپ', 'bssms' ),
			'manage_options', // Admin Capability
			'bssms-courses-setup', // Slug
			array( $this, 'render_courses_setup_page' )
		);

		// 4. سسٹمز ترتیبات (قاعدہ 29)
		add_submenu_page(
			'bssms-dashboard',
			esc_html__( 'سسٹم ترتیبات', 'bssms' ),
			esc_html__( 'سسٹم ترتیبات', 'bssms' ),
			'manage_options',
			'bssms-settings', // Slug
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * ہر صفحے کے لیے Placeholder رینڈر فنکشنز۔
	 * یہ فنکشنز بعد میں (template) بلاکس کو لوڈ کریں گے۔
	 */
	public function render_dashboard_page() {
		echo '<div class="wrap"><div id="bssms-dashboard-root"></div></div>';
	}
	public function render_admission_page() {
		echo '<div class="wrap"><div id="bssms-admission-root"></div></div>'; // قاعدہ 4
	}
	public function render_students_list_page() {
		echo '<div class="wrap"><div id="bssms-students-list-root"></div></div>'; // قاعدہ 4
	}
	public function render_courses_setup_page() {
		echo '<div class="wrap"><div id="bssms-courses-setup-root"></div></div>'; // قاعدہ 4
	}
	public function render_settings_page() {
		echo '<div class="wrap"><div id="bssms-settings-root"></div></div>'; // قاعدہ 4
	}

}

BSSMS_Core::get_instance();
// 🔴 یہاں پر Core Plugin Code ختم ہو رہا ہے

// ✅ Syntax verified block end
/** Part 1 — Admission Page: PHP Template & Localization Update */

// BSSMS_Core کلاس کے اندر، render_admission_page() فنکشن کو اپ ڈیٹ کریں۔
// render_admission_page() فنکشن کا نیا اور مکمل کوڈ (پُرانے کی جگہ پر):

public function render_admission_page() {
	// قاعدہ 4: ہر پیج Root : <div id="bssms-*-root">
	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'نیا داخلہ فارم', 'bssms' ); ?> <span style="font-size:14px; color:#999; margin-left:10px;">(Student Enrolment + Fee Entry)</span></h2>
		<div class="bssms-message-container"></div>
		<div id="bssms-admission-root">
			<?php 
			// یہاں (JS) سارا کام سنبھالے گا، لیکن ہم (PHP) میں ضروری ٹیمپلیٹ اور ڈیٹا فراہم کریں گے۔
			$this->render_admission_template();
			?>
		</div>
	</div>
	<?php
}

/**
 * داخلہ فارم کے لیے (PHP) ٹیمپلیٹ بلاک کو رینڈر کریں۔
 * قاعدہ 4: مکمل <template> blocks
 */
private function render_admission_template() {
	// ہم فارم کو ماؤنٹ کرنے کے لیے صرف ایک خالی <template> استعمال کر رہے ہیں۔
	// مکمل فارم کا HTML (JavaScript) فیز میں بنایا جائے گا۔
	?>
	<template id="bssms-admission-form-template">
		<div class="bssms-form-wrapper">
			<form id="bssms-admission-form" class="bssms-card bssms-form-grid">
				
				<div class="bssms-card-section" data-label="Personal Information">
					<h4 class="section-title">👤 ذاتی معلومات (Personal Information)</h4>
					
					<div class="bssms-form-group">
						<label for="full_name_en" class="bssms-label">Full Name (English) <span class="required">*</span></label>
						<input type="text" id="full_name_en" name="full_name_en" class="bssms-input bssms-input-en" required placeholder="مثلاً: Ali Ahmed">
						<small class="bssms-hint">نام (اردو میں خودکار)</small>
						<input type="text" id="full_name_ur" name="full_name_ur" class="bssms-input bssms-input-ur" readonly placeholder="علی احمد">
					</div>

					<div class="bssms-form-group">
						<label for="father_name_en" class="bssms-label">Father Name (English) <span class="required">*</span></label>
						<input type="text" id="father_name_en" name="father_name_en" class="bssms-input bssms-input-en" required placeholder="مثلاً: Muhammad Akram">
						<small class="bssms-hint">والد کا نام (اردو میں خودکار)</small>
						<input type="text" id="father_name_ur" name="father_name_ur" class="bssms-input bssms-input-ur" readonly placeholder="محمد اکرم">
					</div>

					<div class="bssms-form-group">
						<label class="bssms-label">Gender (جنس) <span class="required">*</span></label>
						<div class="bssms-radio-group">
							<input type="radio" id="gender_male" name="gender" value="Male" required>
							<label for="gender_male">Male (مرد)</label>
							
							<input type="radio" id="gender_female" name="gender" value="Female">
							<label for="gender_female">Female (عورت)</label>
							
							<input type="radio" id="gender_other" name="gender" value="Other">
							<label for="gender_other">Other (دیگر)</label>
						</div>
					</div>
					
				</div>
				
				<div class="bssms-card-section" data-label="Course and Fee">
					<h4 class="section-title">🎓 کورس اور فیس کی تفصیلات (Course Details)</h4>
					
					<div class="bssms-form-group">
						<label for="dob" class="bssms-label">Date of Birth (تاریخ پیدائش) <span class="required">*</span></label>
						<input type="date" id="dob" name="dob" class="bssms-input" required>
					</div>
					
					<div class="bssms-form-group">
						<label for="course_select" class="bssms-label">Select Course (کورس منتخب کریں) <span class="required">*</span></label>
						<select id="course_select" name="course_id" class="bssms-select" required>
							<option value="">--- کورس منتخب کریں ---</option>
						</select>
					</div>

					<div class="bssms-form-group">
						<label for="total_fee" class="bssms-label">Total Fee (کل فیس)</label>
						<input type="number" id="total_fee" name="total_fee" class="bssms-input bssms-fee-display" readonly value="0">
					</div>
					
					<div class="bssms-form-group">
						<label for="paid_amount" class="bssms-label">Paid Amount (ادا شدہ رقم) <span class="required">*</span></label>
						<input type="number" id="paid_amount" name="paid_amount" class="bssms-input bssms-input-fee" required min="0" placeholder="مثلاً: 10000">
						<p class="bssms-fee-words" id="paid_amount_words">صفر روپے</p> </div>
					
					<div class="bssms-form-group">
						<label for="due_amount" class="bssms-label">Due Amount (بقایا رقم)</label>
						<input type="number" id="due_amount" name="due_amount" class="bssms-input bssms-fee-display" readonly value="0">
						<p class="bssms-fee-words bssms-due-amount" id="due_amount_words">صفر روپے بقایا</p>
					</div>
					
				</div>
				
				<div class="bssms-card-section bssms-col-span-full" data-label="Smart and Payment Features">
					<div class="bssms-row-flex">
						<div class="bssms-feature-card">
							<h4 class="section-title">✨ اسمارٹ فیچرز (Smart Features)</h4>
							<div class="bssms-form-group">
								<label for="payment_screenshot" class="bssms-label">Upload Screenshot (ادائیگی کا اسکرین شاٹ) <span class="required">*</span></label>
								<input type="file" id="payment_screenshot" name="payment_screenshot" class="bssms-input-file" accept="image/*" required>
								<p class="bssms-file-preview" id="file_preview_name">فائل منتخب نہیں کی گئی</p>
							</div>
							<button type="button" class="bssms-btn bssms-btn-secondary" id="translate_urdu_btn">🇵🇰 Translate Name Fields to Urdu (خودکار ترجمہ)</button>
						</div>

						<div class="bssms-feature-card">
							<h4 class="section-title">💳 ادائیگی کا طریقہ (Payment Method)</h4>
							<div class="bssms-form-group">
								<label for="payment_method" class="bssms-label">Method <span class="required">*</span></label>
								<select id="payment_method" name="payment_method" class="bssms-select" required>
									<option value="Cash">Cash (نقدی)</option>
									<option value="Bank Transfer">Bank Transfer (بینک ٹرانسفر)</option>
									<option value="Easypaisa/JazzCash">Easypaisa/JazzCash</option>
								</select>
							</div>
							<button type="button" class="bssms-btn bssms-btn-secondary" id="convert_to_words_btn">Convert Numbers to Words (الفاظ میں تبدیلی)</button>
						</div>
					</div>
				</div>
				
				<div class="bssms-form-actions bssms-col-span-full">
					<button type="submit" class="bssms-btn bssms-btn-primary" id="save_admission_btn">💾 Save (محفوظ کریں)</button>
					<button type="reset" class="bssms-btn bssms-btn-secondary">Reset (دوبارہ سیٹ)</button>
					<button type="button" class="bssms-btn bssms-btn-info" id="print_btn">🖨️ Print (پرنٹ)</button> <button type="button" class="bssms-btn bssms-btn-info" id="export_excel_btn">📊 Export (ایکسل)</button> </div>
				
			</form>
		</div>
		
		<div id="bssms-admission-success-card" class="bssms-success-card" style="display: none;">
			</div>
		
	</template>
	<?php
}

// BSSMS_Assets::localize_data() فنکشن کا نیا اور مکمل کوڈ (پُرانے کی جگہ پر):
// قاعدہ 4: localized data (کورسز کو بھیجنا)
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
        'translate_text' => 'bssms_translate_text', // نیا ایکشن
    );
    
    // تمام Nonces کو محفوظ طریقے سے (JavaScript) میں بھیجیں
    foreach ( $ajax_actions as $key => $action ) {
        $nonce_data[ $key . '_nonce' ] = wp_create_nonce( $action );
    }

    // کورسز کا ڈیٹا (DB) سے لوڈ کریں تاکہ (JS) میں استعمال ہو سکے
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
                'save_success' => 'داخلہ فارم کامیابی سے محفوظ ہو گیا ہے۔',
                'save_error' => 'داخلہ محفوظ کرنے میں خرابی پیش آئی۔',
                'missing_fields' => 'براہ کرم تمام ضروری فیلڈز کو پُر کریں۔',
                'translation_error' => 'ترجمہ سروس تک رسائی میں خرابی۔ براہ کرم خود سے اردو میں نام لکھیں۔',
                'fee_mismatch' => 'بقایا رقم منفی (Negative) نہیں ہو سکتی۔ ادا شدہ رقم کل فیس سے زیادہ ہے۔',
            ),
        )
    );
}

// BSSMS_Core کلاس کے اندر، hooks() فنکشن کا نیا اور مکمل کوڈ (پُرانے کی جگہ پر):
// ایک نئے AJAX ایکشن کو شامل کرنا:
private function hooks() {
    // (PHP) ایڈمن مینو اور اثاثے لوڈ کریں۔
    add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
    add_action( 'admin_enqueue_scripts', array( 'BSSMS_Assets', 'enqueue_admin_assets' ) );

    // (AJAX) ہینڈلر کو رجسٹر کریں۔
    add_action( 'wp_ajax_bssms_save_admission', array( 'BSSMS_Ajax', 'handle_save_admission' ) );
    add_action( 'wp_ajax_bssms_fetch_students', array( 'BSSMS_Ajax', 'handle_fetch_students' ) );
    add_action( 'wp_ajax_bssms_translate_text', array( 'BSSMS_Ajax', 'handle_translate_text' ) ); // نیا AJAX ہینڈلر
}


// ✅ Syntax verified block end
