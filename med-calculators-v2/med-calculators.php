<?php
/**
 * Plugin Name: Med Calculators
 * Plugin URI: https://example.com/med-calculators
 * Description: Professional medical calculators including pregnancy due date, ovulation window, and calorie needs calculators.
 * Version: 1.0.2
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: med-calculators
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants
 */
define( 'MED_CALC_VERSION', '1.0.2' );
define( 'MED_CALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MED_CALC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MED_CALC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload classes
 */
spl_autoload_register( function( $class_name ) {
    // Only autoload our plugin classes
    if ( strpos( $class_name, 'Med_Calc_' ) !== 0 ) {
        return;
    }

    // Convert class name to file name
    $class_file = str_replace( 'Med_Calc_', '', $class_name );
    $class_file = strtolower( str_replace( '_', '-', $class_file ) );
    
    // Check in includes directory
    $file_path = MED_CALC_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
    
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
        return;
    }
    
    // Check in calculators directory
    $calc_path = MED_CALC_PLUGIN_DIR . 'includes/calculators/class-' . $class_file . '.php';
    
    if ( file_exists( $calc_path ) ) {
        require_once $calc_path;
    }
});

/**
 * Initialize the plugin
 */
function med_calc_init() {
    // Load text domain for translations
    load_plugin_textdomain( 
        'med-calculators', 
        false, 
        dirname( MED_CALC_PLUGIN_BASENAME ) . '/languages' 
    );
    
    // Initialize the loader
    $loader = new Med_Calc_Loader();
    $loader->init();
}
add_action( 'plugins_loaded', 'med_calc_init' );

/**
 * Activation hook
 */
function med_calc_activate() {
    // Flush rewrite rules on activation
    flush_rewrite_rules();
    
    // Create database table
    $database = new Med_Calc_Database();
    $database->create_table();
    
    // Set plugin version in database
    update_option( 'med_calc_version', MED_CALC_VERSION );
    
    // Set default settings if not exists
    $default_settings = array(
        'output_mode' => 'instant', // instant, email_first, email_only
        'enable_email' => 0,
        'enable_logging' => 1,
        'require_email' => 0,
        'require_name' => 0,
        'gdpr_enabled' => 1,
        'privacy_text' => __( 'Your data is protected and will not be shared with third parties.', 'med-calculators' ),
    );
    
    $existing_settings = get_option( 'med_calc_settings', array() );
    $settings = wp_parse_args( $existing_settings, $default_settings );
    update_option( 'med_calc_settings', $settings );
}
register_activation_hook( __FILE__, 'med_calc_activate' );

/**
 * Deactivation hook
 */
function med_calc_deactivate() {
    // Flush rewrite rules on deactivation
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'med_calc_deactivate' );

/**
 * Helper function to get plugin setting
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value if setting not found.
 * @return mixed Setting value.
 */
function med_calc_get_setting( $key, $default = null ) {
    $settings = get_option( 'med_calc_settings', array() );
    return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}