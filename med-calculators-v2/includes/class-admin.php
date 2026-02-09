<?php
/**
 * Admin Handler Class
 *
 * Manages admin panel, settings, and Live Preview functionality.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Med_Calc_Admin
 *
 * Handles all admin-related functionality.
 */
class Med_Calc_Admin {

	/**
	 * Available calculator types
	 *
	 * @var array
	 */
	private $calculators;

	/**
	 * Constructor
	 *
	 * @param array $calculators Available calculator types.
	 */
	public function __construct( array $calculators ) {
		$this->calculators = $calculators;
	}

	/**
	 * Initialize admin functionality
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers
		add_action( 'wp_ajax_med_calc_save_preview_settings', array( $this, 'ajax_save_preview_settings' ) );
		add_action( 'wp_ajax_med_calc_export_csv', array( $this, 'ajax_export_csv' ) );
		add_action( 'wp_ajax_med_calc_delete_log', array( $this, 'ajax_delete_log' ) );
		add_action( 'wp_ajax_med_calc_get_analytics', array( $this, 'ajax_get_analytics' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Med Calculators', 'med-calculators' ),
			__( 'Med Calculators', 'med-calculators' ),
			'manage_options',
			'med-calculators',
			array( $this, 'render_settings_page' ),
			'dashicons-calculator',
			30
		);

		add_submenu_page(
			'med-calculators',
			__( 'Settings', 'med-calculators' ),
			__( 'Settings', 'med-calculators' ),
			'manage_options',
			'med-calculators',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'med-calculators',
			__( 'Live Preview', 'med-calculators' ),
			'<span class="dashicons dashicons-visibility" style="font-size:16px;vertical-align:middle;margin-right:5px;"></span>' . __( 'Live Preview', 'med-calculators' ),
			'manage_options',
			'med-calculators-preview',
			array( $this, 'render_preview_page' )
		);

		add_submenu_page(
			'med-calculators',
			__( 'Analytics', 'med-calculators' ),
			__( 'Analytics', 'med-calculators' ),
			'manage_options',
			'med-calculators-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'med-calculators',
			__( 'Leads & Logs', 'med-calculators' ),
			__( 'Leads & Logs', 'med-calculators' ),
			'manage_options',
			'med-calculators-leads',
			array( $this, 'render_leads_page' )
		);

		add_submenu_page(
			'med-calculators',
			__( 'Usage Guide', 'med-calculators' ),
			'<span class="dashicons dashicons-book-alt" style="font-size:16px;vertical-align:middle;margin-right:5px;"></span>' . __( 'Usage Guide', 'med-calculators' ),
			'manage_options',
			'med-calculators-guide',
			array( $this, 'render_guide_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on plugin pages
		if ( strpos( $hook, 'med-calculators' ) === false ) {
			return;
		}

		// Admin CSS
		wp_enqueue_style(
			'med-calc-admin',
			MED_CALC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MED_CALC_VERSION
		);

		// Admin JS
		wp_enqueue_script(
			'med-calc-admin',
			MED_CALC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MED_CALC_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'med-calc-admin',
			'medCalcAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'med_calc_admin_nonce' ),
			)
		);

		// Load Modern Design assets on Preview page
		// All handles are already registered by Med_Calc_Loader::register_assets() at priority 5
		if ( $hook === 'med-calculators_page_med-calculators-preview' ) {
			wp_enqueue_style( 'med-calculators-modern' );
			wp_enqueue_script( 'med-calculators-modern' );
		}
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'med_calc_settings_group',
			'med_calc_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// General Settings
		add_settings_section(
			'med_calc_general',
			__( 'General Settings', 'med-calculators' ),
			null,
			'med-calculators-general'
		);

		add_settings_field(
			'calculator_template',
			__( 'Default Template', 'med-calculators' ),
			array( $this, 'render_field_select' ),
			'med-calculators-general',
			'med_calc_general',
			array(
				'name'    => 'calculator_template',
				'options' => array(
					'default' => __( 'Default', 'med-calculators' ),
					'modern'  => __( 'Modern Design', 'med-calculators' ),
				),
			)
		);

		// Modern Design Settings
		$this->register_modern_design_settings();

		// Leads Settings
		$this->register_leads_settings();

		// Email Settings
		$this->register_email_settings();

		// Integrations Settings
		$this->register_integrations_settings();
	}

	/**
	 * Register Modern Design settings
	 *
	 * @return void
	 */
	private function register_modern_design_settings() {
		add_settings_section(
			'med_calc_modern_design',
			__( 'Modern Design Settings', 'med-calculators' ),
			null,
			'med-calculators-modern'
		);

		// Colors
		$color_fields = array(
			'mcm_panel_color'  => __( 'Panel Color', 'med-calculators' ),
			'mcm_active_color' => __( 'Active Color', 'med-calculators' ),
			'mcm_result_bg'    => __( 'Result Background', 'med-calculators' ),
			'mcm_text_light'   => __( 'Text Light', 'med-calculators' ),
			'mcm_text_dark'    => __( 'Text Dark', 'med-calculators' ),
			'mcm_text_gray'    => __( 'Text Gray', 'med-calculators' ),
		);

		foreach ( $color_fields as $name => $label ) {
			add_settings_field(
				$name,
				$label,
				array( $this, 'render_field_color' ),
				'med-calculators-modern',
				'med_calc_modern_design',
				array( 'name' => $name )
			);
		}

		// Layout
		$layout_fields = array(
			'mcm_panel_width'    => __( 'Panel Width (px)', 'med-calculators' ),
			'mcm_panel_padding'  => __( 'Panel Padding (px)', 'med-calculators' ),
			'mcm_result_padding' => __( 'Result Padding (px)', 'med-calculators' ),
			'mcm_border_radius'  => __( 'Border Radius (px)', 'med-calculators' ),
			'mcm_max_width'      => __( 'Max Width (px)', 'med-calculators' ),
		);

		foreach ( $layout_fields as $name => $label ) {
			add_settings_field(
				$name,
				$label,
				array( $this, 'render_field_number' ),
				'med-calculators-modern',
				'med_calc_modern_design',
				array( 'name' => $name )
			);
		}

		// Typography
		$typo_fields = array(
			'mcm_heading_size'       => __( 'Heading Size (px)', 'med-calculators' ),
			'mcm_body_size'          => __( 'Body Size (px)', 'med-calculators' ),
			'mcm_input_size'         => __( 'Input Size (px)', 'med-calculators' ),
			'mcm_result_number_size' => __( 'Result Number Size (px)', 'med-calculators' ),
			'mcm_btn_size'           => __( 'Button Size (px)', 'med-calculators' ),
		);

		foreach ( $typo_fields as $name => $label ) {
			add_settings_field(
				$name,
				$label,
				array( $this, 'render_field_number' ),
				'med-calculators-modern',
				'med_calc_modern_design',
				array( 'name' => $name )
			);
		}

		// Font Family
		add_settings_field(
			'mcm_font_family',
			__( 'Font Family', 'med-calculators' ),
			array( $this, 'render_field_select' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array(
				'name'    => 'mcm_font_family',
				'options' => array(
					'system'     => __( 'System Default', 'med-calculators' ),
					'inter'      => __( 'Inter', 'med-calculators' ),
					'roboto'     => __( 'Roboto', 'med-calculators' ),
					'poppins'    => __( 'Poppins', 'med-calculators' ),
					'opensans'   => __( 'Open Sans', 'med-calculators' ),
					'montserrat' => __( 'Montserrat', 'med-calculators' ),
					'lato'       => __( 'Lato', 'med-calculators' ),
					'nunito'     => __( 'Nunito', 'med-calculators' ),
					'cairo'      => __( 'Cairo', 'med-calculators' ),
					'tajawal'    => __( 'Tajawal', 'med-calculators' ),
				),
			)
		);

		// Shadow
		add_settings_field(
			'mcm_shadow',
			__( 'Shadow Intensity', 'med-calculators' ),
			array( $this, 'render_field_select' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array(
				'name'    => 'mcm_shadow',
				'options' => array(
					'none' => __( 'None', 'med-calculators' ),
					'sm'   => __( 'Small', 'med-calculators' ),
					'md'   => __( 'Medium', 'med-calculators' ),
					'lg'   => __( 'Large', 'med-calculators' ),
					'xl'   => __( 'Extra Large', 'med-calculators' ),
				),
			)
		);

		// Button Radius
		add_settings_field(
			'mcm_btn_radius',
			__( 'Button Border Radius (px)', 'med-calculators' ),
			array( $this, 'render_field_number' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_btn_radius' )
		);

		// Button Padding
		add_settings_field(
			'mcm_btn_padding',
			__( 'Button Padding (px)', 'med-calculators' ),
			array( $this, 'render_field_number' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_btn_padding' )
		);

		// Input BG Opacity
		add_settings_field(
			'mcm_input_bg_opacity',
			__( 'Input Background Opacity (%)', 'med-calculators' ),
			array( $this, 'render_field_number' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_input_bg_opacity' )
		);

		// Slider Thumb Style
		add_settings_field(
			'mcm_slider_thumb_style',
			__( 'Slider Thumb Style', 'med-calculators' ),
			array( $this, 'render_field_select' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array(
				'name'    => 'mcm_slider_thumb_style',
				'options' => array(
					'circle' => __( 'Circle', 'med-calculators' ),
					'square' => __( 'Square', 'med-calculators' ),
				),
			)
		);

		// Enable Animations
		add_settings_field(
			'mcm_enable_animations',
			__( 'Enable Animations', 'med-calculators' ),
			array( $this, 'render_field_checkbox' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_enable_animations' )
		);

		// Hover Effects
		add_settings_field(
			'mcm_hover_color',
			__( 'Hover Color', 'med-calculators' ),
			array( $this, 'render_field_color' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_hover_color' )
		);

		add_settings_field(
			'mcm_hover_brightness',
			__( 'Hover Brightness (%)', 'med-calculators' ),
			array( $this, 'render_field_number' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_hover_brightness' )
		);

		add_settings_field(
			'mcm_hover_scale',
			__( 'Hover Scale (%)', 'med-calculators' ),
			array( $this, 'render_field_number' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_hover_scale' )
		);

		add_settings_field(
			'mcm_enable_hover',
			__( 'Enable Hover Effects', 'med-calculators' ),
			array( $this, 'render_field_checkbox' ),
			'med-calculators-modern',
			'med_calc_modern_design',
			array( 'name' => 'mcm_enable_hover' )
		);
	}

	/**
	 * Register Leads settings
	 *
	 * @return void
	 */
	private function register_leads_settings() {
		add_settings_section(
			'med_calc_leads',
			__( 'Leads & Data Collection', 'med-calculators' ),
			null,
			'med-calculators-leads'
		);

		add_settings_field(
			'output_mode',
			__( 'Output Mode', 'med-calculators' ),
			array( $this, 'render_field_select' ),
			'med-calculators-leads',
			'med_calc_leads',
			array(
				'name'    => 'output_mode',
				'options' => array(
					'instant'     => __( 'Show Result Instantly', 'med-calculators' ),
					'email_first' => __( 'Ask for Email Before Showing', 'med-calculators' ),
					'email_only'  => __( 'Send Result to Email Only', 'med-calculators' ),
				),
			)
		);

		add_settings_field(
			'require_name',
			__( 'Require Name', 'med-calculators' ),
			array( $this, 'render_field_checkbox' ),
			'med-calculators-leads',
			'med_calc_leads',
			array( 'name' => 'require_name' )
		);

		add_settings_field(
			'enable_logging',
			__( 'Enable Calculation Logging', 'med-calculators' ),
			array( $this, 'render_field_checkbox' ),
			'med-calculators-leads',
			'med_calc_leads',
			array( 'name' => 'enable_logging' )
		);

		add_settings_field(
			'gdpr_enabled',
			__( 'Enable GDPR Consent', 'med-calculators' ),
			array( $this, 'render_field_checkbox' ),
			'med-calculators-leads',
			'med_calc_leads',
			array( 'name' => 'gdpr_enabled' )
		);

		add_settings_field(
			'privacy_text',
			__( 'Privacy Text', 'med-calculators' ),
			array( $this, 'render_field_textarea' ),
			'med-calculators-leads',
			'med_calc_leads',
			array( 'name' => 'privacy_text' )
		);

		add_settings_field(
			'disclaimer_text',
			__( 'Disclaimer Text', 'med-calculators' ),
			array( $this, 'render_field_textarea' ),
			'med-calculators-leads',
			'med_calc_leads',
			array( 'name' => 'disclaimer_text' )
		);
	}

	/**
	 * Register Email settings
	 *
	 * @return void
	 */
	private function register_email_settings() {
		add_settings_section(
			'med_calc_email',
			__( 'Email Settings', 'med-calculators' ),
			null,
			'med-calculators-email'
		);

		add_settings_field(
			'enable_email',
			__( 'Enable Email Sending', 'med-calculators' ),
			array( $this, 'render_field_checkbox' ),
			'med-calculators-email',
			'med_calc_email',
			array( 'name' => 'enable_email' )
		);

		add_settings_field(
			'email_from_name',
			__( 'From Name', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-email',
			'med_calc_email',
			array( 'name' => 'email_from_name' )
		);

		add_settings_field(
			'email_from_email',
			__( 'From Email', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-email',
			'med_calc_email',
			array( 'name' => 'email_from_email' )
		);

		add_settings_field(
			'email_subject',
			__( 'Email Subject', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-email',
			'med_calc_email',
			array( 'name' => 'email_subject' )
		);
	}

	/**
	 * Register Integrations settings
	 *
	 * @return void
	 */
	private function register_integrations_settings() {
		add_settings_section(
			'med_calc_integrations',
			__( 'Third-Party Integrations', 'med-calculators' ),
			null,
			'med-calculators-integrations'
		);

		add_settings_field(
			'mailchimp_api_key',
			__( 'Mailchimp API Key', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-integrations',
			'med_calc_integrations',
			array( 'name' => 'mailchimp_api_key' )
		);

		add_settings_field(
			'mailchimp_list_id',
			__( 'Mailchimp List ID', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-integrations',
			'med_calc_integrations',
			array( 'name' => 'mailchimp_list_id' )
		);

		add_settings_field(
			'zapier_webhook_url',
			__( 'Zapier Webhook URL', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-integrations',
			'med_calc_integrations',
			array( 'name' => 'zapier_webhook_url' )
		);

		add_settings_field(
			'convertkit_api_key',
			__( 'ConvertKit API Key', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-integrations',
			'med_calc_integrations',
			array( 'name' => 'convertkit_api_key' )
		);

		add_settings_field(
			'convertkit_form_id',
			__( 'ConvertKit Form ID', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-integrations',
			'med_calc_integrations',
			array( 'name' => 'convertkit_form_id' )
		);

		add_settings_field(
			'hubspot_api_key',
			__( 'HubSpot API Key', 'med-calculators' ),
			array( $this, 'render_field_text' ),
			'med-calculators-integrations',
			'med_calc_integrations',
			array( 'name' => 'hubspot_api_key' )
		);
	}

	/**
	 * Render field: text
	 */
	public function render_field_text( $args ) {
		$settings = get_option( 'med_calc_settings', array() );
		$name     = $args['name'];
		$value    = isset( $settings[ $name ] ) ? $settings[ $name ] : '';
		?>
		<input type="text" name="med_calc_settings[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<?php
	}

	/**
	 * Render field: number
	 */
	public function render_field_number( $args ) {
		$settings = get_option( 'med_calc_settings', array() );
		$name     = $args['name'];
		$value    = isset( $settings[ $name ] ) ? $settings[ $name ] : '';
		?>
		<input type="number" name="med_calc_settings[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="small-text">
		<?php
	}

	/**
	 * Render field: color
	 */
	public function render_field_color( $args ) {
		$settings = get_option( 'med_calc_settings', array() );
		$name     = $args['name'];
		$value    = isset( $settings[ $name ] ) ? $settings[ $name ] : '';
		?>
		<input type="color" name="med_calc_settings[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( $value ); ?>">
		<span class="color-value"><?php echo esc_html( $value ); ?></span>
		<?php
	}

	/**
	 * Render field: checkbox
	 */
	public function render_field_checkbox( $args ) {
		$settings = get_option( 'med_calc_settings', array() );
		$name     = $args['name'];
		$checked  = ! empty( $settings[ $name ] );
		?>
		<label>
			<input type="checkbox" name="med_calc_settings[<?php echo esc_attr( $name ); ?>]" value="1" <?php checked( $checked ); ?>>
			<?php echo isset( $args['label'] ) ? esc_html( $args['label'] ) : ''; ?>
		</label>
		<?php
	}

	/**
	 * Render field: select
	 */
	public function render_field_select( $args ) {
		$settings = get_option( 'med_calc_settings', array() );
		$name     = $args['name'];
		$value    = isset( $settings[ $name ] ) ? $settings[ $name ] : '';
		$options  = isset( $args['options'] ) ? $args['options'] : array();
		?>
		<select name="med_calc_settings[<?php echo esc_attr( $name ); ?>]">
			<?php foreach ( $options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render field: textarea
	 */
	public function render_field_textarea( $args ) {
		$settings = get_option( 'med_calc_settings', array() );
		$name     = $args['name'];
		$value    = isset( $settings[ $name ] ) ? $settings[ $name ] : '';
		?>
		<textarea name="med_calc_settings[<?php echo esc_attr( $name ); ?>]" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Input data.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		// Checkboxes
		$checkboxes = array(
			'require_name',
			'enable_logging',
			'gdpr_enabled',
			'enable_email',
			'mcm_enable_animations',
			'mcm_enable_hover',
		);

		foreach ( $checkboxes as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		// Colors
		$colors = array(
			'mcm_panel_color',
			'mcm_active_color',
			'mcm_result_bg',
			'mcm_text_light',
			'mcm_text_dark',
			'mcm_text_gray',
			'mcm_hover_color',
		);

		foreach ( $colors as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_hex_color( $input[ $key ] );
			}
		}

		// Numbers
		$numbers = array(
			'mcm_panel_width',
			'mcm_panel_padding',
			'mcm_result_padding',
			'mcm_border_radius',
			'mcm_max_width',
			'mcm_heading_size',
			'mcm_body_size',
			'mcm_input_size',
			'mcm_result_number_size',
			'mcm_btn_size',
			'mcm_btn_radius',
			'mcm_btn_padding',
			'mcm_input_bg_opacity',
			'mcm_hover_brightness',
			'mcm_hover_scale',
		);

		foreach ( $numbers as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = absint( $input[ $key ] );
			}
		}

		// Selects
		$selects = array(
			'calculator_template',
			'output_mode',
			'mcm_font_family',
			'mcm_shadow',
			'mcm_slider_thumb_style',
		);

		foreach ( $selects as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_key( $input[ $key ] );
			}
		}

		// Text fields
		$text_fields = array(
			'email_from_name',
			'email_subject',
			'mailchimp_list_id',
			'convertkit_form_id',
		);

		foreach ( $text_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		// Email
		if ( isset( $input['email_from_email'] ) ) {
			$sanitized['email_from_email'] = sanitize_email( $input['email_from_email'] );
		}

		// URLs
		$urls = array( 'zapier_webhook_url' );
		foreach ( $urls as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = esc_url_raw( $input[ $key ] );
			}
		}

		// API Keys (text)
		$api_keys = array( 'mailchimp_api_key', 'convertkit_api_key', 'hubspot_api_key' );
		foreach ( $api_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		// Textareas
		$textareas = array( 'privacy_text', 'disclaimer_text' );
		foreach ( $textareas as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = wp_kses_post( $input[ $key ] );
			}
		}

		return $sanitized;
	}

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			// General
			'calculator_template' => 'modern',

			// Modern Design
			'mcm_panel_color'        => '#F47B4A',
			'mcm_active_color'       => '#1A1A1A',
			'mcm_result_bg'          => '#FFFFFF',
			'mcm_text_light'         => '#FFFFFF',
			'mcm_text_dark'          => '#1A1A1A',
			'mcm_text_gray'          => '#888888',
			'mcm_panel_width'        => 400,
			'mcm_panel_padding'      => 40,
			'mcm_result_padding'     => 40,
			'mcm_border_radius'      => 12,
			'mcm_max_width'          => 960,
			'mcm_heading_size'       => 15,
			'mcm_body_size'          => 12,
			'mcm_input_size'         => 28,
			'mcm_result_number_size' => 56,
			'mcm_btn_size'           => 13,
			'mcm_font_family'        => 'system',
			'mcm_shadow'             => 'md',
			'mcm_btn_radius'         => 0,
			'mcm_btn_padding'        => 16,
			'mcm_input_bg_opacity'   => 18,
			'mcm_slider_thumb_style' => 'circle',
			'mcm_enable_animations'  => 1,
			'mcm_hover_color'        => '#000000',
			'mcm_hover_brightness'   => 90,
			'mcm_hover_scale'        => 102,
			'mcm_enable_hover'       => 1,

			// Leads
			'output_mode'     => 'instant',
			'require_name'    => 0,
			'enable_logging'  => 1,
			'gdpr_enabled'    => 1,
			'privacy_text'    => __( 'Your data is protected and will not be shared with third parties.', 'med-calculators' ),
			'disclaimer_text' => __( 'This calculator is for informational purposes only and should not replace professional medical advice.', 'med-calculators' ),

			// Email
			'enable_email'     => 0,
			'email_from_name'  => get_bloginfo( 'name' ),
			'email_from_email' => get_bloginfo( 'admin_email' ),
			'email_subject'    => __( 'Your Calculation Results', 'med-calculators' ),

			// Integrations
			'mailchimp_api_key'   => '',
			'mailchimp_list_id'   => '',
			'zapier_webhook_url'  => '',
			'convertkit_api_key'  => '',
			'convertkit_form_id'  => '',
			'hubspot_api_key'     => '',
		);
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=med-calculators&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General', 'med-calculators' ); ?>
				</a>
				<a href="?page=med-calculators&tab=modern" class="nav-tab <?php echo $active_tab === 'modern' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Modern Design', 'med-calculators' ); ?>
				</a>
				<a href="?page=med-calculators&tab=leads" class="nav-tab <?php echo $active_tab === 'leads' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Leads', 'med-calculators' ); ?>
				</a>
				<a href="?page=med-calculators&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Email', 'med-calculators' ); ?>
				</a>
				<a href="?page=med-calculators&tab=integrations" class="nav-tab <?php echo $active_tab === 'integrations' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Integrations', 'med-calculators' ); ?>
				</a>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'med_calc_settings_group' );

				switch ( $active_tab ) {
					case 'modern':
						do_settings_sections( 'med-calculators-modern' );
						break;
					case 'leads':
						do_settings_sections( 'med-calculators-leads' );
						break;
					case 'email':
						do_settings_sections( 'med-calculators-email' );
						break;
					case 'integrations':
						do_settings_sections( 'med-calculators-integrations' );
						break;
					default:
						do_settings_sections( 'med-calculators-general' );
						break;
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Live Preview page
	 *
	 * @return void
	 */
	public function render_preview_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = wp_parse_args( get_option( 'med_calc_settings', array() ), $this->get_default_settings() );

		// Generate dynamic CSS for Modern Design
		$shadow_map = array(
			'none' => 'none',
			'sm'   => '0 2px 10px rgba(0,0,0,0.06)',
			'md'   => '0 8px 40px rgba(0,0,0,0.12)',
			'lg'   => '0 12px 60px rgba(0,0,0,0.18)',
			'xl'   => '0 20px 80px rgba(0,0,0,0.24)',
		);

		$css_vars = array(
			'--mcm-panel-color'        => $settings['mcm_panel_color'],
			'--mcm-active-color'       => $settings['mcm_active_color'],
			'--mcm-result-bg'          => $settings['mcm_result_bg'],
			'--mcm-text-light'         => $settings['mcm_text_light'],
			'--mcm-text-dark'          => $settings['mcm_text_dark'],
			'--mcm-text-gray'          => $settings['mcm_text_gray'],
			'--mcm-panel-width'        => $settings['mcm_panel_width'] . 'px',
			'--mcm-panel-padding'      => $settings['mcm_panel_padding'] . 'px',
			'--mcm-result-padding'     => $settings['mcm_result_padding'] . 'px',
			'--mcm-border-radius'      => $settings['mcm_border_radius'] . 'px',
			'--mcm-heading-size'       => $settings['mcm_heading_size'] . 'px',
			'--mcm-body-size'          => $settings['mcm_body_size'] . 'px',
			'--mcm-input-size'         => $settings['mcm_input_size'] . 'px',
			'--mcm-result-number-size' => $settings['mcm_result_number_size'] . 'px',
			'--mcm-btn-size'           => $settings['mcm_btn_size'] . 'px',
			'--mcm-shadow'             => isset( $shadow_map[ $settings['mcm_shadow'] ] ) ? $shadow_map[ $settings['mcm_shadow'] ] : $shadow_map['md'],
		);

		// Input BG opacity
		$opacity = intval( $settings['mcm_input_bg_opacity'] ) / 100;
		$css_vars['--mcm-input-bg'] = 'rgba(0,0,0,' . $opacity . ')';

		// Panel color light variant
		$panel_hex = $settings['mcm_panel_color'];
		$r = hexdec( substr( $panel_hex, 1, 2 ) );
		$g = hexdec( substr( $panel_hex, 3, 2 ) );
		$b = hexdec( substr( $panel_hex, 5, 2 ) );
		$css_vars['--mcm-panel-color-light'] = 'rgba(' . $r . ',' . $g . ',' . $b . ',0.3)';

		$css_string = '.mcm {';
		foreach ( $css_vars as $var => $value ) {
			$css_string .= $var . ': ' . $value . ';';
		}
		$css_string .= '}';

		// Max width
		if ( isset( $settings['mcm_max_width'] ) ) {
			$css_string .= '.mcm-wrapper { max-width: ' . intval( $settings['mcm_max_width'] ) . 'px; }';
		}

		// Button radius
		if ( isset( $settings['mcm_btn_radius'] ) ) {
			$r = intval( $settings['mcm_btn_radius'] ) . 'px';
			$css_string .= '.mcm-gender__btn, .mcm-goal, .mcm-calc-btn, .mcm-tab { border-radius: ' . $r . '; }';
		}

		// Button padding
		if ( isset( $settings['mcm_btn_padding'] ) ) {
			$css_string .= '.mcm-calc-btn { padding-top: ' . intval( $settings['mcm_btn_padding'] ) . 'px; padding-bottom: ' . intval( $settings['mcm_btn_padding'] ) . 'px; }';
		}

		// Slider thumb style
		if ( ! empty( $settings['mcm_slider_thumb_style'] ) && $settings['mcm_slider_thumb_style'] === 'square' ) {
			$css_string .= '.mcm-slider::-webkit-slider-thumb { border-radius: 4px; }';
			$css_string .= '.mcm-slider::-moz-range-thumb { border-radius: 4px; }';
		}

		// Disable animations
		if ( empty( $settings['mcm_enable_animations'] ) ) {
			$css_string .= '.mcm * { transition-duration: 0s !important; animation: none !important; }';
		}

		// Hover effects
		if ( ! empty( $settings['mcm_enable_hover'] ) ) {
			$hover_color      = isset( $settings['mcm_hover_color'] ) ? $settings['mcm_hover_color'] : '#000000';
			$hover_brightness = isset( $settings['mcm_hover_brightness'] ) ? intval( $settings['mcm_hover_brightness'] ) : 90;
			$hover_scale      = isset( $settings['mcm_hover_scale'] ) ? intval( $settings['mcm_hover_scale'] ) / 100 : 1.02;

			$css_string .= '.mcm-calc-btn:hover { background: ' . $hover_color . ' !important; filter: brightness(' . $hover_brightness . '%); transform: scale(' . $hover_scale . '); }';
			$css_string .= '.mcm-gender__btn:hover, .mcm-goal:hover { opacity: 0.85 !important; transform: scale(' . $hover_scale . '); }';
			$css_string .= '.mcm-tab:hover { transform: scale(' . $hover_scale . '); }';
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="med-calc-preview-layout">
				<!-- Controls Sidebar -->
				<div class="med-calc-preview-controls-wrapper">
					<div class="med-calc-preview-controls">
						<!-- Calculator Tabs -->
						<div class="postbox">
							<div class="postbox-header">
								<h2 class="hndle"><span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Calculator Type', 'med-calculators' ); ?></h2>
							</div>
							<div class="inside">
								<div class="med-calc-preview-tabs">
									<button class="med-calc-preview-tab active" data-calculator="calories">
										<span class="med-calc-preview-tab-icon dashicons dashicons-carrot"></span>
										<?php esc_html_e( 'Calories', 'med-calculators' ); ?>
									</button>
									<button class="med-calc-preview-tab" data-calculator="ovulation">
										<span class="med-calc-preview-tab-icon dashicons dashicons-heart"></span>
										<?php esc_html_e( 'Ovulation', 'med-calculators' ); ?>
									</button>
									<button class="med-calc-preview-tab" data-calculator="pregnancy">
										<span class="med-calc-preview-tab-icon dashicons dashicons-admin-users"></span>
										<?php esc_html_e( 'Pregnancy', 'med-calculators' ); ?>
									</button>
								</div>
							</div>
						</div>

						<!-- Colors Section -->
						<div class="postbox">
							<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'med-calculators' ); ?></span></button>
							<h2 class="hndle"><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Colors', 'med-calculators' ); ?></h2>
							<div class="inside">
								<?php
								$color_controls = array(
									'mcm_panel_color'  => __( 'Panel Color', 'med-calculators' ),
									'mcm_active_color' => __( 'Active Color', 'med-calculators' ),
									'mcm_result_bg'    => __( 'Result BG', 'med-calculators' ),
									'mcm_text_light'   => __( 'Text Light', 'med-calculators' ),
									'mcm_text_dark'    => __( 'Text Dark', 'med-calculators' ),
									'mcm_text_gray'    => __( 'Text Gray', 'med-calculators' ),
								);

								foreach ( $color_controls as $key => $label ) {
									$this->render_preview_control( 'color', $key, $label, $settings[ $key ] );
								}
								?>
							</div>
						</div>

						<!-- Layout Section -->
						<div class="postbox">
							<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'med-calculators' ); ?></span></button>
							<h2 class="hndle"><span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Layout', 'med-calculators' ); ?></h2>
							<div class="inside">
								<?php
								$layout_controls = array(
									'mcm_panel_width'    => array( 'label' => __( 'Panel Width', 'med-calculators' ), 'min' => 300, 'max' => 500, 'unit' => 'px' ),
									'mcm_panel_padding'  => array( 'label' => __( 'Panel Padding', 'med-calculators' ), 'min' => 20, 'max' => 60, 'unit' => 'px' ),
									'mcm_result_padding' => array( 'label' => __( 'Result Padding', 'med-calculators' ), 'min' => 20, 'max' => 60, 'unit' => 'px' ),
									'mcm_border_radius'  => array( 'label' => __( 'Border Radius', 'med-calculators' ), 'min' => 0, 'max' => 30, 'unit' => 'px' ),
									'mcm_max_width'      => array( 'label' => __( 'Max Width', 'med-calculators' ), 'min' => 700, 'max' => 1200, 'unit' => 'px' ),
								);

								foreach ( $layout_controls as $key => $data ) {
									$this->render_preview_control( 'range', $key, $data['label'], $settings[ $key ], $data );
								}
								?>
							</div>
						</div>

						<!-- Typography Section -->
						<div class="postbox">
							<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'med-calculators' ); ?></span></button>
							<h2 class="hndle"><span class="dashicons dashicons-editor-textcolor"></span> <?php esc_html_e( 'Typography', 'med-calculators' ); ?></h2>
							<div class="inside">
								<?php
								$typo_controls = array(
									'mcm_heading_size'       => array( 'label' => __( 'Heading Size', 'med-calculators' ), 'min' => 12, 'max' => 24, 'unit' => 'px' ),
									'mcm_body_size'          => array( 'label' => __( 'Body Size', 'med-calculators' ), 'min' => 10, 'max' => 18, 'unit' => 'px' ),
									'mcm_input_size'         => array( 'label' => __( 'Input Size', 'med-calculators' ), 'min' => 20, 'max' => 40, 'unit' => 'px' ),
									'mcm_result_number_size' => array( 'label' => __( 'Result Number', 'med-calculators' ), 'min' => 40, 'max' => 80, 'unit' => 'px' ),
									'mcm_btn_size'           => array( 'label' => __( 'Button Size', 'med-calculators' ), 'min' => 10, 'max' => 18, 'unit' => 'px' ),
								);

								foreach ( $typo_controls as $key => $data ) {
									$this->render_preview_control( 'range', $key, $data['label'], $settings[ $key ], $data );
								}

								// Font Family Select
								$font_options = array(
									'system'     => __( 'System', 'med-calculators' ),
									'inter'      => __( 'Inter', 'med-calculators' ),
									'roboto'     => __( 'Roboto', 'med-calculators' ),
									'poppins'    => __( 'Poppins', 'med-calculators' ),
									'opensans'   => __( 'Open Sans', 'med-calculators' ),
									'montserrat' => __( 'Montserrat', 'med-calculators' ),
								);
								$this->render_preview_control( 'select', 'mcm_font_family', __( 'Font Family', 'med-calculators' ), $settings['mcm_font_family'], array( 'options' => $font_options ) );
								?>
							</div>
						</div>

						<!-- Button & Interaction Section -->
						<div class="postbox">
							<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'med-calculators' ); ?></span></button>
							<h2 class="hndle"><span class="dashicons dashicons-button"></span> <?php esc_html_e( 'Button & Interaction', 'med-calculators' ); ?></h2>
							<div class="inside">
								<?php
								$button_controls = array(
									'mcm_btn_radius'       => array( 'label' => __( 'Button Radius', 'med-calculators' ), 'min' => 0, 'max' => 30, 'unit' => 'px' ),
									'mcm_btn_padding'      => array( 'label' => __( 'Button Padding', 'med-calculators' ), 'min' => 10, 'max' => 24, 'unit' => 'px' ),
									'mcm_input_bg_opacity' => array( 'label' => __( 'Input BG Opacity', 'med-calculators' ), 'min' => 0, 'max' => 40, 'unit' => '%' ),
								);

								foreach ( $button_controls as $key => $data ) {
									$this->render_preview_control( 'range', $key, $data['label'], $settings[ $key ], $data );
								}

								// Shadow Select
								$shadow_options = array(
									'none' => __( 'None', 'med-calculators' ),
									'sm'   => __( 'Small', 'med-calculators' ),
									'md'   => __( 'Medium', 'med-calculators' ),
									'lg'   => __( 'Large', 'med-calculators' ),
									'xl'   => __( 'Extra Large', 'med-calculators' ),
								);
								$this->render_preview_control( 'select', 'mcm_shadow', __( 'Shadow', 'med-calculators' ), $settings['mcm_shadow'], array( 'options' => $shadow_options ) );

								// Slider Thumb Style
								$thumb_options = array(
									'circle' => __( 'Circle', 'med-calculators' ),
									'square' => __( 'Square', 'med-calculators' ),
								);
								$this->render_preview_control( 'select', 'mcm_slider_thumb_style', __( 'Slider Thumb', 'med-calculators' ), $settings['mcm_slider_thumb_style'], array( 'options' => $thumb_options ) );

						// Enable Animations Checkbox
						$this->render_preview_control( 'checkbox', 'mcm_enable_animations', __( 'Enable Animations', 'med-calculators' ), $settings['mcm_enable_animations'] );
						?>
					</div>
				</div>

				<!-- Hover & Active Effects -->
				<div class="postbox">
					<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'med-calculators' ); ?></span></button>
					<h2 class="hndle"><span class="dashicons dashicons-pointer"></span> <?php esc_html_e( 'Hover & Active Effects', 'med-calculators' ); ?></h2>
					<div class="inside">
						<?php
						// Hover Effect Color
						$this->render_preview_control( 'color', 'mcm_hover_color', __( 'Hover Color', 'med-calculators' ), isset( $settings['mcm_hover_color'] ) ? $settings['mcm_hover_color'] : '#000000' );

						// Active Effect Intensity
						$hover_controls = array(
							'mcm_hover_brightness' => array( 'label' => __( 'Hover Brightness (%)', 'med-calculators' ), 'min' => 80, 'max' => 120, 'unit' => '%' ),
							'mcm_hover_scale'      => array( 'label' => __( 'Hover Scale (%)', 'med-calculators' ), 'min' => 95, 'max' => 110, 'unit' => '%' ),
						);

						foreach ( $hover_controls as $key => $data ) {
							$default_value = $key === 'mcm_hover_brightness' ? 90 : 102;
							$value = isset( $settings[ $key ] ) ? $settings[ $key ] : $default_value;
							$this->render_preview_control( 'range', $key, $data['label'], $value, $data );
						}

						// Enable Hover Effects Checkbox
						$enable_hover = isset( $settings['mcm_enable_hover'] ) ? $settings['mcm_enable_hover'] : 1;
						$this->render_preview_control( 'checkbox', 'mcm_enable_hover', __( 'Enable Hover Effects', 'med-calculators' ), $enable_hover );
						?>
					</div>
				</div>

						<!-- Device Toggle -->
						<div class="postbox">
							<div class="postbox-header">
								<h2 class="hndle"><span class="dashicons dashicons-laptop"></span> <?php esc_html_e( 'Device Preview', 'med-calculators' ); ?></h2>
							</div>
							<div class="inside">
								<div class="med-calc-device-toggle">
									<button class="med-calc-device-btn active" data-device="desktop">
										<span class="dashicons dashicons-desktop"></span>
									</button>
									<button class="med-calc-device-btn" data-device="tablet">
										<span class="dashicons dashicons-tablet"></span>
									</button>
									<button class="med-calc-device-btn" data-device="mobile">
										<span class="dashicons dashicons-smartphone"></span>
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- Action Bar (Fixed) -->
					<div class="med-calc-preview-actions-bar">
						<button type="button" id="preview-save-btn" class="button button-primary button-hero">
							<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Settings', 'med-calculators' ); ?>
						</button>
						<button type="button" id="preview-reset-btn" class="button button-hero">
							<span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Reset to Defaults', 'med-calculators' ); ?>
						</button>
					</div>
				</div>

				<!-- Preview Panel -->
				<div class="med-calc-preview-panel">
					<div class="postbox">
						<div class="hndle">
							<h2><?php esc_html_e( 'Live Preview', 'med-calculators' ); ?></h2>
						</div>
						<div class="inside" id="preview-container">
							<div class="med-calc-preview-container">
								<div id="preview-frame" class="med-calc-preview-frame desktop-view">
									<style id="mcm-preview-styles">
										<?php echo $css_string; ?>
									</style>

									<!-- Calories Calculator Preview -->
									<div class="med-calc-preview-item" data-preview="calories">
										<?php
										$calculator_type = 'calories';
										ob_start();
										include MED_CALC_PLUGIN_DIR . 'templates/calories-modern.php';
										echo ob_get_clean();
										?>
									</div>

									<!-- Ovulation Calculator Preview -->
									<div class="med-calc-preview-item" data-preview="ovulation" style="display:none;">
										<?php
										$calculator_type = 'ovulation';
										ob_start();
										include MED_CALC_PLUGIN_DIR . 'templates/ovulation-modern.php';
										echo ob_get_clean();
										?>
									</div>

									<!-- Pregnancy Calculator Preview -->
									<div class="med-calc-preview-item" data-preview="pregnancy" style="display:none;">
										<?php
										$calculator_type = 'pregnancy';
										ob_start();
										include MED_CALC_PLUGIN_DIR . 'templates/pregnancy-modern.php';
										echo ob_get_clean();
										?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a preview control
	 *
	 * @param string $type Control type.
	 * @param string $name Setting name.
	 * @param string $label Label.
	 * @param mixed  $value Current value.
	 * @param array  $args Additional arguments.
	 * @return void
	 */
	private function render_preview_control( $type, $name, $label, $value, $args = array() ) {
		?>
		<div class="med-calc-preview-control">
			<label><?php echo esc_html( $label ); ?></label>
			<?php if ( $type === 'color' ) : ?>
				<input type="color" class="med-calc-preview-input" data-setting="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<span class="color-value"><?php echo esc_html( $value ); ?></span>
			<?php elseif ( $type === 'range' ) : ?>
				<input type="range" class="med-calc-preview-input" data-setting="<?php echo esc_attr( $name ); ?>" 
					value="<?php echo esc_attr( $value ); ?>" 
					min="<?php echo isset( $args['min'] ) ? esc_attr( $args['min'] ) : '0'; ?>" 
					max="<?php echo isset( $args['max'] ) ? esc_attr( $args['max'] ) : '100'; ?>">
				<span class="range-value"><?php echo esc_html( $value . ( isset( $args['unit'] ) ? $args['unit'] : '' ) ); ?></span>
			<?php elseif ( $type === 'select' ) : ?>
				<select class="med-calc-preview-input" data-setting="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $args['options'] as $key => $option_label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
							<?php echo esc_html( $option_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php elseif ( $type === 'checkbox' ) : ?>
				<label>
					<input type="checkbox" class="med-calc-preview-input" data-setting="<?php echo esc_attr( $name ); ?>" <?php checked( $value ); ?>>
					<?php esc_html_e( 'Enable', 'med-calculators' ); ?>
				</label>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Analytics page
	 *
	 * @return void
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$database = new Med_Calc_Database();
		$stats    = $database->get_statistics();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Analytics Cards -->
			<div class="med-calc-analytics-cards">
				<div class="med-calc-analytics-card med-calc-analytics-card--primary">
					<div class="med-calc-analytics-card__icon">
						<span class="dashicons dashicons-calculator"></span>
					</div>
					<div class="med-calc-analytics-card__content">
						<div class="med-calc-analytics-card__number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></div>
						<div class="med-calc-analytics-card__label"><?php esc_html_e( 'Total Calculations', 'med-calculators' ); ?></div>
					</div>
				</div>

				<div class="med-calc-analytics-card med-calc-analytics-card--success">
					<div class="med-calc-analytics-card__icon">
						<span class="dashicons dashicons-email"></span>
					</div>
					<div class="med-calc-analytics-card__content">
						<div class="med-calc-analytics-card__number"><?php echo esc_html( number_format_i18n( $stats['emails_collected'] ) ); ?></div>
						<div class="med-calc-analytics-card__label"><?php esc_html_e( 'Emails Collected', 'med-calculators' ); ?></div>
					</div>
				</div>

				<div class="med-calc-analytics-card med-calc-analytics-card--info">
					<div class="med-calc-analytics-card__icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="med-calc-analytics-card__content">
						<div class="med-calc-analytics-card__number"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></div>
						<div class="med-calc-analytics-card__label"><?php esc_html_e( 'Today', 'med-calculators' ); ?></div>
					</div>
				</div>

				<div class="med-calc-analytics-card med-calc-analytics-card--warning">
					<div class="med-calc-analytics-card__icon">
						<span class="dashicons dashicons-chart-line"></span>
					</div>
					<div class="med-calc-analytics-card__content">
						<div class="med-calc-analytics-card__number"><?php echo esc_html( number_format_i18n( $stats['this_month'] ) ); ?></div>
						<div class="med-calc-analytics-card__label"><?php esc_html_e( 'This Month', 'med-calculators' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Analytics Row: Chart + Breakdown -->
			<div class="med-calc-analytics-row">
				<!-- Daily Usage Chart -->
				<div class="med-calc-analytics-chart postbox">
					<div class="hndle">
						<h2><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'Daily Usage (Last 30 Days)', 'med-calculators' ); ?></h2>
					</div>
					<div class="inside">
						<?php if ( ! empty( $stats['daily_usage'] ) ) : ?>
							<canvas id="med-calc-usage-chart" width="600" height="300"></canvas>
							<script>
								document.addEventListener('DOMContentLoaded', function() {
									const canvas = document.getElementById('med-calc-usage-chart');
									if (!canvas) return;

									const ctx = canvas.getContext('2d');
									const data = <?php echo wp_json_encode( $stats['daily_usage'] ); ?>;
									
									const labels = data.map(d => d.date);
									const values = data.map(d => parseInt(d.count));
									
									const width = canvas.width;
									const height = canvas.height;
									const padding = 40;
									const maxValue = Math.max(...values, 1);
									
									// Clear canvas
									ctx.clearRect(0, 0, width, height);
									
									// Draw grid
									ctx.strokeStyle = '#e5e7eb';
									ctx.lineWidth = 1;
									for (let i = 0; i <= 5; i++) {
										const y = padding + (height - 2 * padding) * i / 5;
										ctx.beginPath();
										ctx.moveTo(padding, y);
										ctx.lineTo(width - padding, y);
										ctx.stroke();
									}
									
									// Draw line
									ctx.strokeStyle = '#0d9488';
									ctx.lineWidth = 3;
									ctx.beginPath();
									
									values.forEach((val, i) => {
										const x = padding + (width - 2 * padding) * i / (values.length - 1);
										const y = height - padding - (height - 2 * padding) * val / maxValue;
										if (i === 0) ctx.moveTo(x, y);
										else ctx.lineTo(x, y);
									});
									ctx.stroke();
									
									// Draw points
									ctx.fillStyle = '#0d9488';
									values.forEach((val, i) => {
										const x = padding + (width - 2 * padding) * i / (values.length - 1);
										const y = height - padding - (height - 2 * padding) * val / maxValue;
										ctx.beginPath();
										ctx.arc(x, y, 4, 0, 2 * Math.PI);
										ctx.fill();
									});
								});
							</script>
						<?php else : ?>
							<div class="med-calc-analytics-empty">
								<span class="dashicons dashicons-chart-area"></span>
								<p><?php esc_html_e( 'No data available yet. Start collecting calculations to see analytics.', 'med-calculators' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Calculator Type Breakdown -->
				<div class="med-calc-analytics-breakdown postbox">
					<div class="hndle">
						<h2><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'By Calculator Type', 'med-calculators' ); ?></h2>
					</div>
					<div class="inside">
						<?php if ( ! empty( $stats['by_type'] ) ) : ?>
							<div class="med-calc-analytics-type-list">
								<?php
								$type_icons = array(
									'calories'  => 'carrot',
									'ovulation' => 'heart',
									'pregnancy' => 'admin-users',
								);

								$type_colors = array(
									'calories'  => '#0d9488',
									'ovulation' => '#ec4899',
									'pregnancy' => '#8b5cf6',
								);

								foreach ( $stats['by_type'] as $type_data ) {
									$type       = $type_data['calculator_type'];
									$count      = intval( $type_data['count'] );
									$percentage = $stats['total'] > 0 ? round( ( $count / $stats['total'] ) * 100, 1 ) : 0;
									$icon       = isset( $type_icons[ $type ] ) ? $type_icons[ $type ] : 'calculator';
									$color      = isset( $type_colors[ $type ] ) ? $type_colors[ $type ] : '#6b7280';
									?>
									<div class="med-calc-analytics-type-item">
										<div class="med-calc-analytics-type-header">
											<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?> med-calc-analytics-type-icon" style="color: <?php echo esc_attr( $color ); ?>;"></span>
											<span class="med-calc-analytics-type-name"><?php echo esc_html( ucfirst( $type ) ); ?></span>
											<span class="med-calc-analytics-type-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
										</div>
										<div class="med-calc-analytics-type-bar">
											<div class="med-calc-analytics-type-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%; background: <?php echo esc_attr( $color ); ?>;"></div>
										</div>
										<span class="med-calc-analytics-type-percentage"><?php echo esc_html( $percentage ); ?>%</span>
									</div>
									<?php
								}
								?>
							</div>
						<?php else : ?>
							<div class="med-calc-analytics-empty">
								<span class="dashicons dashicons-category"></span>
								<p><?php esc_html_e( 'No calculator data available.', 'med-calculators' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Leads page
	 *
	 * @return void
	 */
	public function render_leads_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$database = new Med_Calc_Database();

		// Get filters
		$filter_type = isset( $_GET['filter_type'] ) ? sanitize_key( $_GET['filter_type'] ) : '';
		$paged       = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page    = 50;

		// Build query args
		$args = array(
			'limit'  => $per_page,
			'offset' => ( $paged - 1 ) * $per_page,
		);

		if ( ! empty( $filter_type ) ) {
			$args['calculator_type'] = $filter_type;
		}

		$calculations = $database->get_calculations( $args );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Actions Bar -->
			<div class="med-calc-leads-actions">
				<div class="med-calc-leads-filters">
					<form method="get">
						<input type="hidden" name="page" value="med-calculators-leads">
						<select name="filter_type">
							<option value=""><?php esc_html_e( 'All Calculators', 'med-calculators' ); ?></option>
							<option value="calories" <?php selected( $filter_type, 'calories' ); ?>><?php esc_html_e( 'Calories', 'med-calculators' ); ?></option>
							<option value="ovulation" <?php selected( $filter_type, 'ovulation' ); ?>><?php esc_html_e( 'Ovulation', 'med-calculators' ); ?></option>
							<option value="pregnancy" <?php selected( $filter_type, 'pregnancy' ); ?>><?php esc_html_e( 'Pregnancy', 'med-calculators' ); ?></option>
						</select>
						<button type="submit" class="button"><?php esc_html_e( 'Filter', 'med-calculators' ); ?></button>
					</form>
				</div>

				<div class="med-calc-leads-export">
					<button type="button" id="med-calc-export-csv-btn" class="button button-primary">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'med-calculators' ); ?>
					</button>
				</div>
			</div>

			<!-- Logs Table -->
			<?php if ( ! empty( $calculations ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'med-calculators' ); ?></th>
							<th><?php esc_html_e( 'Calculator', 'med-calculators' ); ?></th>
							<th><?php esc_html_e( 'Name', 'med-calculators' ); ?></th>
							<th><?php esc_html_e( 'Email', 'med-calculators' ); ?></th>
							<th><?php esc_html_e( 'Result', 'med-calculators' ); ?></th>
							<th><?php esc_html_e( 'Date', 'med-calculators' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'med-calculators' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $calculations as $calc ) : ?>
							<tr>
								<td><?php echo esc_html( $calc['id'] ); ?></td>
								<td><?php echo esc_html( ucfirst( $calc['calculator_type'] ) ); ?></td>
								<td><?php echo esc_html( $calc['user_name'] ); ?></td>
								<td><?php echo esc_html( $calc['user_email'] ); ?></td>
								<td>
									<?php
									$result = json_decode( $calc['result'], true );
									if ( is_array( $result ) ) {
										$preview = array_slice( $result, 0, 2 );
										echo '<span class="med-calc-result-preview">';
										foreach ( $preview as $key => $value ) {
											if ( is_array( $value ) ) {
												$value = wp_json_encode( $value );
											}
											echo esc_html( $key . ': ' . $value ) . ' ';
										}
										echo '</span>';
									}
									?>
								</td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $calc['created_at'] ) ) ); ?></td>
								<td>
									<button type="button" class="button med-calc-delete-log-btn" data-id="<?php echo esc_attr( $calc['id'] ); ?>">
										<?php esc_html_e( 'Delete', 'med-calculators' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						$total_pages = ceil( $database->get_statistics()['total'] / $per_page );
						if ( $total_pages > 1 ) {
							echo '<span class="pagination-links">';
							if ( $paged > 1 ) {
								echo '<a class="button" href="' . esc_url( add_query_arg( 'paged', $paged - 1 ) ) . '">&laquo; ' . esc_html__( 'Previous', 'med-calculators' ) . '</a> ';
							}
							echo '<span class="paging-input">' . sprintf( esc_html__( 'Page %1$d of %2$d', 'med-calculators' ), $paged, $total_pages ) . '</span>';
							if ( $paged < $total_pages ) {
								echo ' <a class="button" href="' . esc_url( add_query_arg( 'paged', $paged + 1 ) ) . '">' . esc_html__( 'Next', 'med-calculators' ) . ' &raquo;</a>';
							}
							echo '</span>';
						}
						?>
					</div>
				</div>
			<?php else : ?>
				<div class="med-calc-analytics-empty">
					<span class="dashicons dashicons-database"></span>
					<p><?php esc_html_e( 'No calculation logs found.', 'med-calculators' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Usage Guide page
	 *
	 * @return void
	 */
	public function render_guide_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="med-calc-guide-section notice notice-info">
				<h2><span class="dashicons dashicons-info med-calc-guide-icon"></span><?php esc_html_e( 'How to Use', 'med-calculators' ); ?></h2>
				<p><?php esc_html_e( 'Use these shortcodes to display calculators on any page or post:', 'med-calculators' ); ?></p>
				<ul>
					<li><code class="med-calc-guide-code">[med_calculator type="calories"]</code> - <?php esc_html_e( 'Calories Calculator', 'med-calculators' ); ?></li>
					<li><code class="med-calc-guide-code">[med_calculator type="ovulation"]</code> - <?php esc_html_e( 'Ovulation Calculator', 'med-calculators' ); ?></li>
					<li><code class="med-calc-guide-code">[med_calculator type="pregnancy"]</code> - <?php esc_html_e( 'Pregnancy Calculator', 'med-calculators' ); ?></li>
				</ul>
			</div>

			<div class="med-calc-guide-section notice notice-success">
				<h2><span class="dashicons dashicons-art med-calc-guide-icon"></span><?php esc_html_e( 'Choose a Template', 'med-calculators' ); ?></h2>
				<p><?php esc_html_e( 'You can specify which template to use:', 'med-calculators' ); ?></p>
				<ul>
					<li><code class="med-calc-guide-code">[med_calculator type="calories" template="modern"]</code> - <?php esc_html_e( 'Modern Design', 'med-calculators' ); ?></li>
					<li><code class="med-calc-guide-code">[med_calculator type="calories" template="default"]</code> - <?php esc_html_e( 'Default Design', 'med-calculators' ); ?></li>
				</ul>
			</div>

			<div class="med-calc-guide-section notice">
				<h2><span class="dashicons dashicons-admin-tools med-calc-guide-icon"></span><?php esc_html_e( 'Settings', 'med-calculators' ); ?></h2>
				<p><?php esc_html_e( 'Configure your calculators:', 'med-calculators' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Use the Settings page to customize colors, typography, and layout.', 'med-calculators' ); ?></li>
					<li><?php esc_html_e( 'Use the Live Preview to see changes in real-time.', 'med-calculators' ); ?></li>
					<li><?php esc_html_e( 'Configure leads and email settings to capture user data.', 'med-calculators' ); ?></li>
					<li><?php esc_html_e( 'Connect to marketing tools via the Integrations tab.', 'med-calculators' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Save preview settings
	 *
	 * @return void
	 */
	public function ajax_save_preview_settings() {
		check_ajax_referer( 'med_calc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'med-calculators' ) ) );
		}

		$settings = isset( $_POST['settings'] ) ? $_POST['settings'] : array();

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'med-calculators' ) ) );
		}

		$current_settings = get_option( 'med_calc_settings', array() );
		$updated_settings = array_merge( $current_settings, $this->sanitize_settings( $settings ) );

		update_option( 'med_calc_settings', $updated_settings );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'med-calculators' ) ) );
	}

	/**
	 * AJAX: Export CSV
	 *
	 * @return void
	 */
	public function ajax_export_csv() {
		check_ajax_referer( 'med_calc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'med-calculators' ) );
		}

		$database = new Med_Calc_Database();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=med-calc-logs-' . date( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$database->export_csv();

		exit;
	}

	/**
	 * AJAX: Delete log
	 *
	 * @return void
	 */
	public function ajax_delete_log() {
		check_ajax_referer( 'med_calc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'med-calculators' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! $log_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid log ID.', 'med-calculators' ) ) );
		}

		$database = new Med_Calc_Database();
		$result   = $database->delete_log( $log_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Log deleted successfully.', 'med-calculators' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete log.', 'med-calculators' ) ) );
		}
	}

	/**
	 * AJAX: Get analytics
	 *
	 * @return void
	 */
	public function ajax_get_analytics() {
		check_ajax_referer( 'med_calc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'med-calculators' ) ) );
		}

		$database = new Med_Calc_Database();
		$stats    = $database->get_statistics();

		wp_send_json_success( $stats );
	}
}
