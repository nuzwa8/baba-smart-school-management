<?php
/**
 * Plugin Name:       SSM Core Inventory
 * Plugin URI:        https://your-plugin-uri.com
 * Description:       Manages core inventory (Units, Types, Rates) and settings for hotel/rental properties.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://your-author-uri.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ssm-inventory
 * Domain Path:       /languages
 */

// 🟢 یہاں سے [Plugin Security & Foundation] شروع ہو رہا ہے

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class
 */
final class SSM_Inventory_Plugin {

    /**
     * Plugin version.
     */
    const VERSION = '1.0.0';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->define_constants();
        add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Define plugin constants.
     */
    private function define_constants() {
        define( 'SSM_PLUGIN_FILE', __FILE__ );
        define( 'SSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Register all admin menus for the plugin.
     */
    public function register_admin_menus() {
        // Main Menu Page (Core Inventory)
        add_menu_page(
            __( 'Core Inventory', 'ssm-inventory' ),
            __( 'Core Inventory', 'ssm-inventory' ),
            'manage_options',
            'ssm-settings', // Main menu slug points to General Settings
            array( $this, 'render_admin_page_settings' ),
            'dashicons-building',
            25
        );

        // 1. General Settings (This is also the main page)
        add_submenu_page(
            'ssm-settings',
            __( 'General Settings', 'ssm-inventory' ),
            __( 'General Settings', 'ssm-inventory' ),
            'manage_options',
            'ssm-settings', // Slug
            array( $this, 'render_admin_page_settings' ) // Callback
        );

        // 2. Unit Types
        add_submenu_page(
            'ssm-settings',
            __( 'Unit Types', 'ssm-inventory' ),
            __( 'Unit Types', 'ssm-inventory' ),
            'manage_options',
            'ssm-unit-types', // Slug
            array( $this, 'render_admin_page_unit_types' ) // Callback
        );

        // 3. Units
        add_submenu_page(
            'ssm-settings',
            __( 'Units', 'ssm-inventory' ),
            __( 'Units', 'ssm-inventory' ),
            'manage_options',
            'ssm-units', // Slug
            array( $this, 'render_admin_page_units' ) // Callback
        );

        // 4. Rate Plans
        add_submenu_page(
            'ssm-settings',
            __( 'Rate Plans', 'ssm-inventory' ),
            __( 'Rate Plans', 'ssm-inventory' ),
            'manage_options',
            'ssm-rate-plans', // Slug
            array( $this, 'render_admin_page_rate_plans' ) // Callback
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // List of our plugin's admin pages
        $plugin_pages = array(
            'toplevel_page_ssm-settings',
            'core-inventory_page_ssm-unit-types',
            'core-inventory_page_ssm-units',
            'core-inventory_page_ssm-rate-plans',
        );

        // Load assets only on our plugin pages
        if ( in_array( $hook_suffix, $plugin_pages ) ) {
            
            // Enqueue Style
            wp_enqueue_style(
                'ssm-inventory-style',
                SSM_PLUGIN_URL . 'ssm-inventory-plugin.css',
                array(),
                self::VERSION
            );

            // Enqueue Script
            wp_enqueue_script(
                'ssm-inventory-script',
                SSM_PLUGIN_URL . 'ssm-inventory-plugin.js',
                array( 'jquery', 'wp-element' ), // wp-element for React/Vue, or just jquery
                self::VERSION,
                true // Load in footer
            );

            // Localize script data (Rule 6)
            wp_localize_script(
                'ssm-inventory-script',
                'ssm_data',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'ssm_ajax_nonce' ), // Security Nonce (Rule 6)
                )
            );
        }
    }

    // 🔴 یہاں پر [Plugin Security & Foundation] ختم ہو رہا ہے

    // 🟢 یہاں سے [Admin Page Render Functions] شروع ہو رہا ہے

    /**
     * Renders the General Settings page.
     * (Rule 6: Must have root div and template)
     */
    public function render_admin_page_settings() {
        // Root div for JS app (Rule 6)
        echo '<div id="ssm-settings-root" class="ssm-root" data-screen="settings">';
        echo '</div>'; // JS app will mount here
        
        // Full page template (Rule 6)
        echo '<template id="ssm-settings-template">';
        ?>
        <div class="ssm-page-wrapper">
            
            <header class="ssm-page-header">
                <div class="ssm-header-left">
                    <h1><?php _e( 'General Settings', 'ssm-inventory' ); ?></h1>
                    <p><?php _e( 'Manage global settings, language, branding, and API keys.', 'ssm-inventory' ); ?></p>
                </div>
                <div class="ssm-header-right">
                    <a href="#" class="ssm-button ssm-button-secondary"><?php _e( 'View Documentation', 'ssm-inventory' ); ?></a>
                    <button class="ssm-button ssm-button-primary" disabled>
                        <?php _e( 'Settings saved', 'ssm-inventory' ); ?>
                    </button>
                </div>
            </header>
            <div class="ssm-page-content-grid">
                
                <div class="ssm-grid-main">

                    <section class="ssm-card">
                        <h2><?php _e( 'Language & Locale', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-grid ssm-grid-cols-2">
                            <div class="ssm-form-field">
                                <label><?php _e( 'Default Content Language', 'ssm-inventory' ); ?></label>
                                <div class="ssm-toggle-switch">
                                    <span><?php _e( 'English', 'ssm-inventory' ); ?></span>
                                    <input type="checkbox" id="ssm-lang-toggle" class="ssm-toggle-input">
                                    <label for="ssm-lang-toggle" class="ssm-toggle-label"></label>
                                    <span><?php _e( 'Urdu', 'ssm-inventory' ); ?></span>
                                </div>
                            </div>
                            <div class="ssm-form-field">
                                </div>
                            <div class="ssm-form-field">
                                <label for="ssm-timezone"><?php _e( 'Timezone', 'ssm-inventory' ); ?></label>
                                <select id="ssm-timezone">
                                    <option value="PK"><?php _e( 'PKT (Asia/Karachi)', 'ssm-inventory' ); ?></option>
                                    </select>
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-date-format"><?php _e( 'Date Format', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-date-format" value="2023-11-08">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-currency"><?php _e( 'Currency', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-currency" value="PKR">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-digits"><?php _e( 'English Digits (0-9)', 'ssm-inventory' ); ?></label>
                                <input type="checkbox" id="ssm-digits" class="ssm-checkbox">
                            </div>
                        </div>
                        <p class="ssm-field-description">
                            <?php _e( 'Labels include Urdu/English for data entry clarity. This switch defines the default visitor-facing language.', 'ssm-inventory' ); ?>
                        </p>
                    </section>
                    <section class="ssm-card">
                        <h2><?php _e( 'Branding', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-grid ssm-grid-cols-2">
                             <div class="ssm-form-field">
                                <label><?php _e( 'Logo Maps', 'ssm-inventory' ); ?></label>
                                <div class="ssm-image-uploader">
                                    <img src="" alt="logo preview" class="ssm-logo-preview">
                                    <span><?php _e( 'Ethics.jpg (800x800 avif)', 'ssm-inventory' ); ?></span>
                                    <button class="ssm-button-tertiary"><?php _e( 'Change', 'ssm-inventory' ); ?></button>
                                </div>
                            </div>
                            <div class="ssm-form-field">
                                <label><?php _e( 'Google Map Pin', 'ssm-inventory' ); ?></label>
                                <div class="ssm-image-uploader">
                                    <img src="" alt="map pin preview" class="ssm-map-pin-preview">
                                    <span><?php _e( 'Google Maps Pin', 'ssm-inventory' ); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="ssm-form-field">
                            <input type="checkbox" id="ssm-apply-default-logo" class="ssm-checkbox">
                            <label for="ssm-apply-default-logo"><?php _e( 'Applies as default public placeholder for new Rate Plans', 'ssm-inventory' ); ?></label>
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Global Theme consistency (lesser)', 'ssm-inventory' ); ?></label>
                            <input type="text" value="[ssm_theme_consistency_shortcode]">
                        </div>
                    </section>
                    <section class="ssm-card">
                        <h2><?php _e( 'Contact & Support', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-grid ssm-grid-cols-2">
                            <div class="ssm-form-field">
                                <label for="ssm-country"><?php _e( 'Country', 'ssm-inventory' ); ?></label>
                                <select id="ssm-country">
                                    <option><?php _e( 'Pakistan', 'ssm-inventory' ); ?></option>
                                </select>
                            </div>
                            <div class="ssm-form-field ssm-form-group-inline">
                                <div class="ssm-form-field">
                                    <label for="ssm-mightly"><?php _e( 'Mightly (ants)', 'ssm-inventory' ); ?></label>
                                    <input type="text" id="ssm-mightly">
                                </div>
                                <div class="ssm-form-field">
                                    <label for="ssm-nestest"><?php _e( 'Nestest', 'ssm-inventory' ); ?></label>
                                    <input type="text" id="ssm-nestest">
                                </div>
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-city"><?php _e( 'City', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-city">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-default-tax"><?php _e( 'Default Tax %', 'ssm-inventory' ); ?></label>
                                <input type="number" id="ssm-default-tax" class="ssm-input-small">
                                <input type="checkbox" id="ssm-tax-toggle" class="ssm-checkbox">
                            </div>
                            <div class="ssm-form-field ssm-col-span-2">
                                <label for="ssm-street-address"><?php _e( 'Street Address', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-street-address">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-email"><?php _e( 'Email', 'ssm-inventory' ); ?></label>
                                <input type="email" id="ssm-email">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-directing-rule"><?php _e( 'Directing rule', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-directing-rule">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-website-url"><?php _e( 'Website URL', 'ssm-inventory' ); ?></label>
                                <input type="url" id="ssm-website-url">
                            </div>
                             <div class="ssm-form-field">
                                <label for="ssm-social-link"><?php _e( 'Social link', 'ssm-inventory' ); ?></label>
                                <input type="url" id="ssm-social-link">
                                <input type="checkbox" id="ssm-social-toggle" class="ssm-checkbox">
                            </div>
                        </div>
                    </section>
                    <section class="ssm-card">
                        <h2><?php _e( 'Data & Privacy', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-grid ssm-grid-cols-2">
                            <div class="ssm-form-field">
                                <label><?php _e( 'System IDS & API', 'ssm-inventory' ); ?></label>
                                <div class="ssm-input-with-button">
                                    <input type="text" value="Slug: ssm-settings" readonly>
                                </div>
                                <div class="ssm-input-with-button">
                                    <input type="text" value="Ruxs 2.0-ce100ED" readonly>
                                    <button class="ssm-button-tertiary"><?php _e( 'Copy', 'ssm-inventory' ); ?></button>
                                </div>
                            </div>
                            <div class="ssm-form-field">
                                </div>
                             <div class="ssm-form-field">
                                <label><?php _e( 'Plug: ssm-settings-root', 'ssm-inventory' ); ?></label>
                                <div class="ssm-input-with-button">
                                    <input type="text" value="Roost Token" readonly>
                                    <button class="ssm-button-tertiary"><?php _e( 'Generate', 'ssm-inventory' ); ?></button>
                                </div>
                            </div>
                        </div>
                    </section>
                    </div> <div class="ssm-grid-sidebar">

                    <section class="ssm-card">
                        <h2><?php _e( 'Business Profile', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-field">
                            <label for="ssm-biz-name"><?php _e( 'Business Name (English)*', 'ssm-inventory' ); ?></label>
                            <input type="text" id="ssm-biz-name">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-legal-name"><?php _e( 'Legal/Registered Name', 'ssm-inventory' ); ?></label>
                            <input type="text" id="ssm-legal-name">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-reg-no"><?php _e( 'Registration No.', 'ssm-inventory' ); ?></label>
                            <input type="text" id="ssm-reg-no">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-ntn-tax"><?php _e( 'NTN/Tax ID', 'ssm-inventory' ); ?></label>
                            <input type="text" id="ssm-ntn-tax">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-owner-contact"><?php _e( 'Owner/Contact Person', 'ssm-inventory' ); ?></label>
                            <input type="text" id="ssm-owner-contact">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-number-locations"><?php _e( 'Number Locations', 'ssm-inventory' ); ?></label>
                            <input type="number" id="ssm-number-locations" class="ssm-input-small">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-number-faxticons"><?php _e( 'Number Faxticons', 'ssm-inventory' ); ?></label>
                            <input type="number" id="ssm-number-faxticons" class="ssm-input-small">
                        </div>
                        <div class="ssm-form-field">
                            <label for="ssm-mixed-use"><?php _e( 'Mixed-Use', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-mixed-use" class="ssm-checkbox">
                        </div>
                    </section>
                    <section class="ssm-card">
                        <h2><?php _e( 'Locations & Address', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-field">
                            <label for="ssm-map-link"><?php _e( '+92 (International format)', 'ssm-inventory' ); ?></label>
                            <input type="text" id="ssm-map-link">
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Repeats for all (un-translated /additional) overrides', 'ssm-inventory' ); ?></label>
                            <div class="ssm-input-with-button">
                                <input type="text" value="Caretation grosse me..." readonly>
                                <button class="ssm-button-tertiary"><?php _e( 'Copy', 'ssm-inventory' ); ?></button>
                            </div>
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Rounding Pule', 'ssm-inventory' ); ?></label>
                            <div class="ssm-map-placeholder">
                                </div>
                        </div>
                    </section>
                    <section class="ssm-card">
                        <h2><?php _e( 'Cofault & Support', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Priculity', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-priculity-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Default Tax (SAVE)', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-default-tax-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Service Charge %', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-service-charge-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <p class="ssm-field-description"><?php _e( 'Show address pulicity by(int can Rlsose)', 'ssm-inventory' ); ?></p>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Usage Preview', 'ssm-inventory' ); ?></label>
                            <pre class="ssm-code-preview">[BUILDING-FLOORROOM]</pre>
                        </div>
                         <div class="ssm-form-field">
                            <label><?php _e( 'Tags power translate', 'ssm-inventory' ); ?></label>
                            <pre class="ssm-code-preview">I run cume caenp...</pre>
                        </div>
                    </section>
                    <section class="ssm-card">
                        <h2><?php _e( 'Notifications', 'ssm-inventory' ); ?></h2>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Dash Addon', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-dash-addon-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Post (wwws)', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-post-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Cemesto', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-cemesto-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <div class="ssm-form-field">
                            <label><?php _e( 'Email mishm a guest details after retention period', 'ssm-inventory' ); ?></label>
                            <input type="checkbox" id="ssm-email-mishm-toggle" class="ssm-checkbox-toggle">
                        </div>
                        <div class="ssm-form-field ssm-plugin-token-field">
                            <input type="checkbox" id="ssm-plugin-token-toggle" class="ssm-checkbox">
                            <label for="ssm-plugin-token-toggle"><?php _e( 'This plugin subfix tokens...', 'ssm-inventory' ); ?></label>
                            <span class="ssm-badge-represent"><?php _e( 'Represente', 'ssm-inventory' ); ?></span>
                        </div>
                    </section>
                    </div> </div> <footer class="ssm-page-footer">
                <span><?php _e( 'Last modified 2 mins ago', 'ssm-inventory' ); ?></span>
                <div class="ssm-footer-actions">
                    <button class="ssm-button ssm-button-primary"><?php _e( 'Save All Changes', 'ssm-inventory' ); ?></button>
                    <button class="ssm-button ssm-button-tertiary"><?php _e( 'Discard', 'ssm-inventory' ); ?></button>
                    <button class="ssm-button ssm-button-link"><?php _e( 'Reset to Defaults', 'ssm-inventory' ); ?></button>
                </div>
            </footer>
            </div> <?php
        echo '</template>';
    }

    /**
     * Renders the Unit Types page.
     * (Rule 6: Must have root div and template)
     */
    public function render_admin_page_unit_types() {
        // Root div for JS app (Rule 6)
        echo '<div id="ssm-unit-types-root" class="ssm-root" data-screen="unit-types">';
        echo '</div>'; // JS app will mount here
        
        // Full page template (Rule 6)
        echo '<template id="ssm-unit-types-template">';
        ?>
        <div class="ssm-page-wrapper">
            
            <header class="ssm-page-header">
                <div class="ssm-header-left">
                    <h1><?php _e( 'Unit Types', 'ssm-inventory' ); ?></h1>
                    <p><?php _e( 'Define, manage, and categorize all available unit types.', 'ssm-inventory' ); ?></p>
                </div>
                <div class="ssm-header-right">
                    <button class="ssm-button ssm-button-primary ssm-button-add-new">
                        <?php _e( 'Add Unit Type', 'ssm-inventory' ); ?>
                    </button>
                </div>
            </header>
            <div class="ssm-stat-card-row">
                <div class="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #e0f2fe;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Total Unit Types', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">8 <small><?php _e( 'Active', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
                <div class="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #fef9c3;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Linked Units', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">57 <small><?php _e( 'Units assigned', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
                <div class="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #fce7f3;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Pending Reviews', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">2 <small><?php _e( 'Drafts', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
                <div classs="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #ede9fe;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Last Updated', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value"><small><?php _e( 'Today, 3:15 PM', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
            </div>
            <div class="ssm-page-content-grid ssm-grid-with-sidebar">
                
                <div class="ssm-grid-main">
                    <section class="ssm-card ssm-card-table">
                        <div class="ssm-table-header">
                            <h2><?php _e( 'All Unit Types', 'ssm-inventory' ); ?></h2>
                            <div class="ssm-table-controls">
                                <input type="search" placeholder="<?php _e( 'Search Types...', 'ssm-inventory' ); ?>" class="ssm-search-input">
                                <select class="ssm-filter-select">
                                    <option><?php _e( 'Filter', 'ssm-inventory' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <table class="ssm-table">
                            <thead>
                                <tr>
                                    <th><?php _e( 'ID', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Type Name', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Category', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Capacity', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Features JSON', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Status', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Actions', 'ssm-inventory' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><?php _e( 'Studio', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( '(English)', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( '2 Guests', 'ssm-inventory' ); ?></td>
                                    <td><button class="ssm-button-link"><?php _e( 'View Details', 'ssm-inventory' ); ?></button></td>
                                    <td><span class="ssm-badge ssm-badge-success"><?php _e( 'Active', 'ssm-inventory' ); ?></span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete"><?php _e( 'Delete', 'ssm-inventory' ); ?></button>
                                        <input type="checkbox" class="ssm-checkbox-toggle" checked>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><?php _e( 'Shop', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Residential', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( '50 Seats', 'ssm-inventory' ); ?></td>
                                    <td><button class="ssm-button-link"><?php _e( 'JSON Details', 'ssm-inventory' ); ?></button></td>
                                    <td><span class="ssm-badge ssm-badge-success"><?php _e( 'Active', 'ssm-inventory' ); ?></span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete"><?php _e( 'Delete', 'ssm-inventory' ); ?></button>
                                        <input type="checkbox" class="ssm-checkbox-toggle" checked>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><?php _e( 'Hall', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Event', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( '50 Seats', 'ssm-inventory' ); ?></td>
                                    <td><button class="ssm-button-link"><?php _e( 'View Details', 'ssm-inventory' ); ?></button></td>
                                    <td><span class="ssm-badge ssm-badge-inactive"><?php _e( 'Inactive', 'ssm-inventory' ); ?></span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete"><?php _e( 'Delete', 'ssm-inventory' ); ?></button>
                                        <input type="checkbox" class="ssm-checkbox-toggle">
                                    </td>
                                </tr>
                                </tbody>
                        </table>
                        </section>
                </div> <div class="ssm-grid-sidebar">
                    <section class="ssm-card ssm-form-sidebar">
                        <div class="ssm-form-sidebar-header">
                            <h3><?php _e( 'Edit Unit Type', 'ssm-inventory' ); ?></h3>
                            </div>
                        <div class="ssm-form-sidebar-content">
                            <div class="ssm-form-field">
                                <label for="ssm-type-name"><?php _e( 'Type Name (Urdu)', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-type-name" value="Studio">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-type-category"><?php _e( 'Category', 'ssm-inventory' ); ?></label>
                                <select id="ssm-type-category">
                                    <option><?php _e( 'Residential', 'ssm-inventory' ); ?></option>
                                    <option><?php _e( 'Commercial', 'ssm-inventory' ); ?></option>
                                    <option><?php _e( 'Event', 'ssm-inventory' ); ?></option>
                                </select>
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-type-capacity"><?php _e( 'Capacity', 'ssm-inventory' ); ?></label>
                                <input type="number" id="ssm-type-capacity" value="2" class="ssm-input-small">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-default-rate-plan"><?php _e( 'Default Rate Plan', 'ssm-inventory' ); ?></label>
                                <select id="ssm-default-rate-plan">
                                    <option><?php _e( 'Select rate plan...', 'ssm-inventory' ); ?></option>
                                </select>
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-features-json"><?php _e( 'Description / Features JSON', 'ssm-inventory' ); ?></label>
                                <textarea id="ssm-features-json" rows="6"><?php echo esc_textarea( '{\n  "beds": 1,\n  "kitchenette": true,\n  "tv_size_inches": 42\n}' ); ?></textarea>
                                <button class="ssm-button-tertiary ssm-button-small"><?php _e( 'Validate JSON', 'ssm-inventory' ); ?></button>
                            </div>
                            <div class="ssm-form-field">
                                <input type="checkbox" id="ssm-mark-featured" class="ssm-checkbox">
                                <label for="ssm-mark-featured"><?php _e( 'Mark as Featured', 'ssm-inventory' ); ?></label>
                            </div>
                        </div>
                        <div class="ssm-form-sidebar-footer">
                            <button class="ssm-button ssm-button-primary"><?php _e( 'Save', 'ssm-inventory' ); ?></button>
                            <button class="ssm-button ssm-button-tertiary"><?php _e( 'Cancel', 'ssm-inventory' ); ?></button>
                        </div>
                    </section>
                    </div>

            </div> <footer class="ssm-page-footer">
                <span><?php _e( 'Last updated just now', 'ssm-inventory' ); ?></span>
                <div class="ssm-footer-actions">
                    <button class="ssm-button ssm-button-primary"><?php _e( 'Save All Changes', 'ssm-inventory' ); ?></button>
                    <button class="ssm-button ssm-button-tertiary"><?php _e( 'Discard', 'ssm-inventory' ); ?></button>
                    <button class="ssm-button ssm-button-link"><?php _e( 'Reset to Defaults', 'ssm-inventory' ); ?></button>
                </div>
            </footer>
            </div> <?php
        echo '</template>';
    }

    /**
     * Renders the Units page.
     * (Rule 6: Must have root div and template)
     */
    public function render_admin_page_units() {
        // Root div for JS app (Rule 6)
        echo '<div id="ssm-units-root" class="ssm-root" data-screen="units">';
        echo '</div>'; // JS app will mount here
        
        // Full page template (Rule 6)
        echo '<template id="ssm-units-template">';
        ?>
        <div class="ssm-page-wrapper">
            
            <header class="ssm-page-header">
                <div class="ssm-header-left">
                    <h1><?php _e( 'Units', 'ssm-inventory' ); ?></h1>
                    <p><?php _e( 'Manage all available units with status, and assignment.', 'ssm-inventory' ); ?></p>
                </div>
                <div class="ssm-header-right">
                    <button class="ssm-button ssm-button-primary ssm-button-add-new">
                        <?php _e( 'Add Unit', 'ssm-inventory' ); ?>
                    </button>
                </div>
            </header>
            <div class="ssm-stat-card-row">
                <div class="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #e0f2fe;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Total Units', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">57 <small><?php _e( 'Active', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
                <div class="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #fef9c3;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Unit Type 1', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">41 <small><?php _e( 'Active', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
                <div classs="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #dcfce7;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Unit Type', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">16 <small><?php _e( 'Available', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
                <div classs="ssm-stat-card">
                    <div class="ssm-stat-card-icon" style="--icon-bg: #fce7f3;">
                        </div>
                    <div class="ssm-stat-card-info">
                        <span class="ssm-stat-card-title"><?php _e( 'Under Maintenance', 'ssm-inventory' ); ?></span>
                        <span class="ssm-stat-card-value">0 <small><?php _e( 'Units', 'ssm-inventory' ); ?></small></span>
                    </div>
                </div>
            </div>
            <div class="ssm-page-content-grid ssm-grid-with-sidebar">
                
                <div class="ssm-grid-main">
                    <section class="ssm-card ssm-card-table">
                        <div class="ssm-table-header">
                            <h2><?php _e( 'Unit Type (Urdu)', 'ssm-inventory' ); ?></h2>
                            <div class="ssm-table-controls">
                                <select class="ssm-filter-select">
                                    <option><?php _e( 'Select option/filter', 'ssm-inventory' ); ?></option>
                                </select>
                                <button class="ssm-button-tertiary"><?php _e( 'Bulk Actions', 'ssm-inventory' ); ?></button>
                                <button class="ssm-button-tertiary"><?php _e( 'Import / Export CSV', 'ssm-inventory' ); ?></button>
                                <button class="ssm-button-secondary"><?php _e( 'Imaged Actions', 'ssm-inventory' ); ?></button>
                            </div>
                        </div>
                        <div class="ssm-table-search-bar">
                            <input type="search" placeholder="<?php _e( 'Search...', 'ssm-inventory' ); ?>" class="ssm-search-input">
                            <select class="ssm-filter-select">
                                <option><?php _e( 'Name/Translate', 'ssm-inventory' ); ?></option>
                            </select>
                            <select class="ssm-filter-select">
                                <option><?php _e( 'Type/Quick', 'ssm-inventory' ); ?></option>
                            </select>
                            <button class="ssm-button-icon">+</button>
                        </div>

                        <table class="ssm-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="ssm-checkbox"></th>
                                    <th><?php _e( 'Unit', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Unit Name', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Assigned Rate Plan', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Status', 'ssm-inventory' ); ?></th>
                                    <th><?php _e( 'Actions', 'ssm-inventory' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" class="ssm-checkbox"></td>
                                    <td>101</td>
                                    <td><?php _e( 'Deluxe Room (English)', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Nightly', 'ssm-inventory' ); ?></td>
                                    <td><span class="ssm-badge ssm-badge-success-icon">✓</span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete">X</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="ssm-checkbox"></td>
                                    <td>101</td>
                                    <td><?php _e( 'Deluxe Room 101', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Studio', 'ssm-inventory' ); ?></td>
                                    <td><span class="ssm-badge ssm-badge-success-icon">✓</span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete">X</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="ssm-checkbox"></td>
                                    <td>11</td>
                                    <td><?php _e( 'Studio', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Nightly', 'ssm-inventory' ); ?></td>
                                    <td><span class="ssm-badge ssm-badge-success-icon">✓</span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete">X</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="ssm-checkbox"></td>
                                    <td>10</td>
                                    <td><?php _e( 'Shop-A', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Vacant', 'ssm-inventory' ); ?></td>
                                    <td><span class="ssm-badge ssm-badge-error-icon">X</span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete">X</button>
                                    </td>
                                </tr>
                                 <tr>
                                    <td><input type="checkbox" class="ssm-checkbox"></td>
                                    <td>25</td>
                                    <td><?php _e( 'Floor / Level', 'ssm-inventory' ); ?></td>
                                    <td><?php _e( 'Mezzanine', 'ssm-inventory' ); ?></td>
                                    <td><span class="ssm-badge ssm-badge-success-icon">✓</span></td>
                                    <td class="ssm-table-actions">
                                        <button class="ssm-action-button ssm-action-edit"><?php _e( 'Edit', 'ssm-inventory' ); ?></button>
                                        <button class="ssm-action-button ssm-action-delete">X</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </section>
                </div> <div class="ssm-grid-sidebar">
                    <section class="ssm-card ssm-form-sidebar">
                        <div class="ssm-form-sidebar-header">
                            <h3><?php _e( 'Edit Unit', 'ssm-inventory' ); ?></h3>
                            </div>
                        <div class="ssm-form-sidebar-content">
                            <div class="ssm-form-field">
                                <label for="ssm-unit-name-urdu"><?php _e( 'Unit Name (Urdu)', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-unit-name-urdu" value="Deluxe Room (English)">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-unit-name-english"><?php _e( 'Unit Name (English)', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-unit-name-english" value="Deluxe Room 101">
                            </div>

                            <div class="ssm-form-field">
                                <label><?php _e( 'Fixite (active-fix)', 'ssm-inventory' ); ?></label>
                                <input type="checkbox" id="ssm-fixite-toggle" class="ssm-checkbox-toggle" checked>
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-hourly-rate"><?php _e( 'Hourly/Rate @ yes(R)', 'ssm-inventory' ); ?></label>
                                <input type="number" id="ssm-hourly-rate" value="0">
                            </div>
                            <div class="ssm-form-field">
                                <label><?php _e( 'Hot/Sale', 'ssm-inventory' ); ?></label>
                                <input type="checkbox" id="ssm-hotsale-toggle" class="ssm-checkbox-toggle">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-day-rate"><?php _e( 'Dar/day/foo/fefe', 'ssm-inventory' ); ?></label>
                                <input type="number" id="ssm-day-rate" value="0">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-rearrange"><?php _e( 'Rearrange/Pause', 'ssm-inventory' ); ?></label>
                                <input type="text" id="ssm-rearrange" value="Pause 7 tick 31st">
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-description"><?php _e( 'Description', 'ssm-inventory' ); ?></label>
                                <textarea id="ssm-description" rows="3"></textarea>
                            </div>
                            <div class="ssm-form-field">
                                <label for="ssm-description-urdu"><?php _e( 'Description (Urdu)', 'ssm-inventory' ); ?></label>
                                <textarea id="ssm-description-urdu" rows="3"></textarea>
                            </div>

                            <div class="ssm-form-field">
                                <label><?php _e( 'Custom Attributes', 'ssm-inventory' ); ?></label>
                                <div class="ssm-key-value-pairs">
                                    <div class="ssm-kv-pair">
                                        <input type="text" placeholder="Attribute" value="Floor">
                                        <input type="text" placeholder="Value" value="1st">
                                        <button>X</button>
                                    </div>
                                    <div class="ssm-kv-pair">
                                        <input type="text" placeholder="Attribute" value="View">
                                        <input type="text" placeholder="Value" value="Sea">
                                        <button>X</button>
                                    </div>
                                    <button class="ssm-button-tertiary ssm-button-small"><?php _e( 'Add Attribute', 'ssm-inventory' ); ?></button>
                                </div>
                            </div>
                        </div>
                        <div class="ssm-form-sidebar-footer">
                            <button class="ssm-button ssm-button-primary"><?php _e( 'Save Unit', 'ssm-inventory' ); ?></button>
                            <button class="ssm-button ssm-button-tertiary"><?php _e( 'Cancel', 'ssm-inventory' ); ?></button>
                        </div>
                    </section>
                    </div>

            </div> <footer class="ssm-page-footer">
                <span><?php _e( 'Last updated 5 mins ago', 'ssm-inventory' ); ?></span>
                <div class="ssm-footer-actions">
                    <button class="ssm-button ssm-button-primary"><?php _e( 'Save Changes', 'ssm-inventory' ); ?></button>
                    <button class="ssm-button ssm-button-tertiary"><?php _e( 'Discard', 'ssm-inventory' ); ?></button>
                    <button class="ssm-button ssm-button-link"><?php _e( 'Reset to Defaults', 'ssm-inventory' ); ?></button>
                </div>
            </footer>
            </div> <?php
        echo '</template>';
    }

    /**
     * Renders the Rate Plans page.
     * (Rule 6: Must have root div and template)
     */
    public function render_admin_page_rate_plans() {
        // Root div for JS app
        echo '<div id="ssm-rate-plans-root" class="ssm-root" data-screen="rate-plans"></div>';

        // Full page template (Rule 6)
        echo '<template id="ssm-rate-plans-template">';
        echo '<div>Loading Rate Plans Page...</div>'; // Placeholder
        echo '</template>';
    }

    // 🔴 یہاں پر [Admin Page Render Functions] ختم ہو رہا ہے

}

/**
 * Initialize the plugin.
 */
function ssm_run_inventory_plugin() {
    new SSM_Inventory_Plugin();
}
add_action( 'plugins_loaded', 'ssm_run_inventory_plugin' );
