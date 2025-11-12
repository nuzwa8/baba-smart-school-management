<?php
/**
 * Plugin Name: Baba Smart School Management System (BSSMS)
 * Description: AI اکیڈمی کے لیے ایڈمیشن، فیس مینجمنٹ، اور رپورٹنگ سسٹم. (PHP), (JS), (CSS) کو استعمال کرتا ہے۔
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
	// BSSMS_Activator کلاس Autoload کے ذریعے خود ہی شامل ہو جائے گی
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
    
    // تمام پیج سلگز کو اسٹور کرنے کے لیے
    public $pages = array();

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
        
        // پیج سلگز کو init پر سیٹ کریں تاکہ وہ کلاس کے اندر دستیاب ہوں
        $this->pages = [
            'dashboard' => 'bssms-dashboard',
            'admission' => 'bssms-admission',
            'students-list' => 'bssms-students-list',
            'courses-setup' => 'bssms-courses-setup',
            'settings' => 'bssms-settings',
        ];
	}

	/**
	 * ضروری کلاس فائلیں شامل کریں۔ (صرف پیج لاجک فائلیں شامل ہوں گی)
	 */
	private function includes() {
		// پیج لاجک فائلیں شامل کریں (قاعدہ 30 کے مطابق)
		// Autoload فنکشن باقی تمام کلاسز (Activator, DB, Assets, Ajax) کو خود ہی شامل کرے گا
		require_once BSSMS_PATH . 'pages/bssms-admission-page.php';
		require_once BSSMS_PATH . 'pages/bssms-students-list-page.php';
		require_once BSSMS_PATH . 'pages/bssms-courses-setup-page.php';
		require_once BSSMS_PATH . 'pages/bssms-settings-page.php';
		require_once BSSMS_PATH . 'pages/bssms-dashboard-page.php';
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
		add_action( 'wp_ajax_bssms_translate_text', array( 'BSSMS_Ajax', 'handle_translate_text' ) );
		add_action( 'wp_ajax_bssms_delete_admission', array( 'BSSMS_Ajax', 'handle_delete_admission' ) );
		
		add_action( 'wp_ajax_bssms_fetch_courses', array( 'BSSMS_Ajax', 'handle_fetch_courses' ) );
		add_action( 'wp_ajax_bssms_save_course', array( 'BSSMS_Ajax', 'handle_save_course' ) );
		add_action( 'wp_ajax_bssms_delete_course', array( 'BSSMS_Ajax', 'handle_delete_course' ) );
		
		add_action( 'wp_ajax_bssms_save_settings', array( 'BSSMS_Ajax', 'handle_save_settings' ) );
		add_action( 'wp_ajax_bssms_reset_defaults', array( 'BSSMS_Ajax', 'handle_reset_defaults' ) );
        
        // ڈیش بورڈ کا AJAX ہینڈلر
		add_action( 'wp_ajax_bssms_fetch_dashboard_data', array( 'BSSMS_Ajax', 'handle_fetch_dashboard_data' ) );
	}

	/**
	 * ایڈمن مینو شامل کریں۔
	 */
	public function add_plugin_menu() {
        $pages = $this->pages;
        
		add_menu_page(
			esc_html__( 'بابا اکیڈمی ڈیش بورڈ', 'bssms' ),
			esc_html__( 'بابا اکیڈمی', 'bssms' ),
			'bssms_manage_admissions',
			$pages['dashboard'],
			array( $this, 'render_dashboard_page' ),
			'dashicons-welcome-learn-more',
			6
		);

		// 1. داخلہ فارم
		add_submenu_page(
			$pages['dashboard'],
			esc_html__( 'داخلہ فارم', 'bssms' ),
			esc_html__( 'داخلہ فارم', 'bssms' ),
			'bssms_create_admission',
			$pages['admission'],
			array( $this, 'render_admission_page' )
		);

		// 2. طالب علم کی فہرست
		add_submenu_page(
			$pages['dashboard'],
			esc_html__( 'طالب علم کی فہرست', 'bssms' ),
			esc_html__( 'طالب علم کی فہرست', 'bssms' ),
			'bssms_manage_admissions',
			$pages['students-list'],
			array( $this, 'render_students_list_page' )
		);

		// 3. کورسز سیٹ اپ (صرف ایڈمن کیلئے)
		add_submenu_page(
			$pages['dashboard'],
			esc_html__( 'کورسز سیٹ اپ', 'bssms' ),
			esc_html__( 'کورسز سیٹ اپ', 'bssms' ),
			'manage_options',
			$pages['courses-setup'],
			array( $this, 'render_courses_setup_page' )
		);

		// 4. سسٹمز ترتیبات (قاعدہ 29)
		add_submenu_page(
			$pages['dashboard'],
			esc_html__( 'سسٹم ترتیبات', 'bssms' ),
			esc_html__( 'سسٹم ترتیبات', 'bssms' ),
			'manage_options',
			$pages['settings'],
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * ہر صفحے کے لیے رینڈر فنکشنز۔
	 */
	public function render_dashboard_page() {
		BSSMS_Dashboard_Page::render_page();
	}
	public function render_admission_page() {
		BSSMS_Admission_Page::render_page();
	}
	public function render_students_list_page() {
		BSSMS_Students_List_Page::render_page();
	}
	public function render_courses_setup_page() {
		BSSMS_Courses_Setup_Page::render_page();
	}
	public function render_settings_page() {
		BSSMS_Settings_Page::render_page();
	}
}

BSSMS_Core::get_instance();
// 🔴 یہاں پر Core Plugin Code ختم ہو رہا ہے

// ✅ Syntax verified block end
