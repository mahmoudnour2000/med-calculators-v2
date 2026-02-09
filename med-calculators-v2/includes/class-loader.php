<?php
/**
 * Plugin Loader Class
 *
 * Orchestrates all plugin components and initializes them.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Loader
 *
 * Main loader class that initializes all plugin components.
 */
class Med_Calc_Loader {

    /**
     * Shortcode handler instance
     *
     * @var Med_Calc_Shortcode
     */
    private $shortcode;

    /**
     * AJAX handler instance
     *
     * @var Med_Calc_Ajax
     */
    private $ajax;

    /**
     * Admin handler instance
     *
     * @var Med_Calc_Admin
     */
    private $admin;

    /**
     * Available calculator types
     *
     * @var array
     */
    private $calculators = array();

    /**
     * Initialize the loader
     *
     * @return void
     */
    public function init() {
        $this->register_calculators();
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Register all available calculators
     *
     * New calculators are automatically registered by scanning the calculators directory.
     *
     * @return void
     */
    private function register_calculators() {
        $calculators_dir = MED_CALC_PLUGIN_DIR . 'includes/calculators/';
        
        if ( ! is_dir( $calculators_dir ) ) {
            return;
        }

        $files = glob( $calculators_dir . 'class-*.php' );

        foreach ( $files as $file ) {
            $filename = basename( $file, '.php' );
            $type = str_replace( 'class-', '', $filename );
            
            // Convert filename to class name (e.g., 'pregnancy' -> 'Med_Calc_Pregnancy')
            $class_name = 'Med_Calc_' . ucfirst( $type );
            
            // Verify class exists and implements required interface
            require_once $file;
            
            if ( class_exists( $class_name ) && method_exists( $class_name, 'calculate' ) ) {
                $this->calculators[ $type ] = $class_name;
            }
        }
    }

    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function init_components() {
        $this->shortcode = new Med_Calc_Shortcode( $this->calculators );
        $this->ajax = new Med_Calc_Ajax( $this->calculators );
        
        // Initialize admin only in admin context
        if ( is_admin() ) {
            require_once MED_CALC_PLUGIN_DIR . 'includes/class-admin.php';
            $this->admin = new Med_Calc_Admin( $this->calculators );
            $this->admin->init();
        }
    }

    /**
     * Register WordPress hooks
     *
     * @return void
     */
    private function register_hooks() {
        // Register shortcode
        add_shortcode( 'med_calculator', array( $this->shortcode, 'render' ) );

        // Register AJAX handlers
        add_action( 'wp_ajax_med_calc', array( $this->ajax, 'handle_request' ) );
        add_action( 'wp_ajax_nopriv_med_calc', array( $this->ajax, 'handle_request' ) );

        // Register assets on BOTH frontend and admin (priority 5 to run before other enqueues)
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

        // Force Elementor to refresh cache when language changes
        add_filter( 'elementor/widget/render_content', array( $this, 'elementor_cache_bust' ), 10, 2 );
    }

    /**
     * Register plugin assets (enqueued conditionally by shortcode or admin)
     *
     * Assets are registered on both frontend and admin so dependencies
     * are always available regardless of context.
     *
     * @return void
     */
    public function register_assets() {
        // Prevent double-registration
        if ( wp_style_is( 'med-calculators-style', 'registered' ) ) {
            return;
        }

        // ---- Base CSS ----
        wp_register_style(
            'med-calculators-style',
            MED_CALC_PLUGIN_URL . 'assets/css/style.css',
            array(),
            MED_CALC_VERSION
        );

        // ---- Base JS ----
        wp_register_script(
            'med-calculators-app',
            MED_CALC_PLUGIN_URL . 'assets/js/app.js',
            array(),
            MED_CALC_VERSION,
            true
        );

        wp_localize_script(
            'med-calculators-app',
            'medCalcConfig',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'med_calc_nonce' ),
                'i18n'    => array(
                    // General
                    'calculating'    => __( 'Calculating...', 'med-calculators' ),
                    'error'          => __( 'An error occurred. Please try again.', 'med-calculators' ),
                    'required'       => __( 'Please fill in all required fields.', 'med-calculators' ),
                    'calculation_error' => __( 'Calculation error', 'med-calculators' ),
                    'network_error'  => __( 'Network error. Please try again.', 'med-calculators' ),

                    // Units
                    'g_unit'         => __( 'g', 'med-calculators' ),
                    'g_per_meal'     => __( 'g/meal', 'med-calculators' ),

                    // Activity levels
                    'activity_low'       => __( 'Low', 'med-calculators' ),
                    'activity_low_desc'  => __( 'Little or no exercise. Desk job with minimal movement.', 'med-calculators' ),
                    'activity_middle'      => __( 'Middle', 'med-calculators' ),
                    'activity_middle_desc' => __( 'Activity that burns an additional 400-650 calories for females or 500-800 calories for males.', 'med-calculators' ),
                    'activity_high'      => __( 'High', 'med-calculators' ),
                    'activity_high_desc' => __( 'Intense exercise 6-7 days/week. Very physically demanding lifestyle.', 'med-calculators' ),
                    'activity_very_high'      => __( 'Very High', 'med-calculators' ),
                    'activity_very_high_desc' => __( 'Extremely active. Athlete-level training twice per day.', 'med-calculators' ),

                    // Pregnancy
                    'week_label'     => __( 'Week', 'med-calculators' ),
                    'days_label'     => __( 'days', 'med-calculators' ),
                    'weeks_label'    => __( 'weeks', 'med-calculators' ),
                    'trimester_1st'  => __( '1st Trimester', 'med-calculators' ),
                    'trimester_2nd'  => __( '2nd Trimester', 'med-calculators' ),
                    'trimester_3rd'  => __( '3rd Trimester', 'med-calculators' ),
                ),
            )
        );

        // ---- Modern Design CSS (depends on base) ----
        wp_register_style(
            'med-calculators-modern',
            MED_CALC_PLUGIN_URL . 'assets/css/modern.css',
            array( 'med-calculators-style' ),
            MED_CALC_VERSION
        );

        // ---- Modern Design JS (depends on base) ----
        wp_register_script(
            'med-calculators-modern',
            MED_CALC_PLUGIN_URL . 'assets/js/modern.js',
            array( 'med-calculators-app' ),
            MED_CALC_VERSION,
            true
        );

        // ---- Add dynamic inline CSS on frontend only ----
        // Inline CSS is attached to registered handles and printed when enqueued.
        if ( ! is_admin() ) {
            $this->add_dynamic_css();
        }
    }

    /**
     * Add dynamic CSS based on settings
     *
     * Called during asset registration. Inline CSS is attached to registered
     * handles and automatically printed when those handles are enqueued.
     *
     * @return void
     */
    public function add_dynamic_css() {
        $settings = get_option( 'med_calc_settings', array() );
        
        $css = ':root {';
        
        // Typography
        if ( isset( $settings['heading_font_size'] ) ) {
            $css .= '--med-heading-size: ' . intval( $settings['heading_font_size'] ) . 'px;';
        }
        if ( isset( $settings['body_font_size'] ) ) {
            $css .= '--med-text-base: ' . intval( $settings['body_font_size'] ) . 'px;';
        }
        if ( isset( $settings['label_font_size'] ) ) {
            $css .= '--med-text-sm: ' . intval( $settings['label_font_size'] ) . 'px;';
        }
        if ( isset( $settings['line_height'] ) ) {
            $css .= '--med-line-height: ' . floatval( $settings['line_height'] ) . ';';
        }
        
        // Colors
        if ( isset( $settings['primary_color'] ) ) {
            $css .= '--med-primary: ' . sanitize_hex_color( $settings['primary_color'] ) . ';';
        }
        if ( isset( $settings['secondary_color'] ) ) {
            $css .= '--med-secondary: ' . sanitize_hex_color( $settings['secondary_color'] ) . ';';
        }
        if ( isset( $settings['success_color'] ) ) {
            $css .= '--med-success: ' . sanitize_hex_color( $settings['success_color'] ) . ';';
        }
        if ( isset( $settings['error_color'] ) ) {
            $css .= '--med-error: ' . sanitize_hex_color( $settings['error_color'] ) . ';';
        }
        if ( isset( $settings['background_color'] ) ) {
            $css .= '--med-bg: ' . sanitize_hex_color( $settings['background_color'] ) . ';';
        }
        if ( isset( $settings['text_color'] ) ) {
            $css .= '--med-text-color: ' . sanitize_hex_color( $settings['text_color'] ) . ';';
        }
        if ( isset( $settings['border_color'] ) ) {
            $css .= '--med-border-color: ' . sanitize_hex_color( $settings['border_color'] ) . ';';
        }
        
        // Spacing
        if ( isset( $settings['card_padding'] ) ) {
            $css .= '--med-card-padding: ' . intval( $settings['card_padding'] ) . 'px;';
        }
        if ( isset( $settings['form_spacing'] ) ) {
            $css .= '--med-form-spacing: ' . intval( $settings['form_spacing'] ) . 'px;';
        }
        if ( isset( $settings['input_padding'] ) ) {
            $css .= '--med-input-padding: ' . intval( $settings['input_padding'] ) . 'px;';
        }
        if ( isset( $settings['button_padding_vertical'] ) ) {
            $css .= '--med-button-padding-y: ' . intval( $settings['button_padding_vertical'] ) . 'px;';
        }
        if ( isset( $settings['button_padding_horizontal'] ) ) {
            $css .= '--med-button-padding-x: ' . intval( $settings['button_padding_horizontal'] ) . 'px;';
        }
        
        // Border Radius
        if ( isset( $settings['card_border_radius'] ) ) {
            $css .= '--med-radius-2xl: ' . intval( $settings['card_border_radius'] ) . 'px;';
        }
        if ( isset( $settings['button_border_radius'] ) ) {
            $css .= '--med-radius-lg: ' . intval( $settings['button_border_radius'] ) . 'px;';
        }
        if ( isset( $settings['input_border_radius'] ) ) {
            $css .= '--med-radius-md: ' . intval( $settings['input_border_radius'] ) . 'px;';
        }
        
        // Button
        if ( isset( $settings['button_font_size'] ) ) {
            $css .= '--med-button-font-size: ' . intval( $settings['button_font_size'] ) . 'px;';
        }
        
        // Animation
        if ( isset( $settings['animation_speed'] ) ) {
            $speed_map = array( 'fast' => '0.2s', 'normal' => '0.4s', 'slow' => '0.8s' );
            $speed = isset( $speed_map[ $settings['animation_speed'] ] ) ? $speed_map[ $settings['animation_speed'] ] : '0.4s';
            $css .= '--med-animation-speed: ' . $speed . ';';
        }
        if ( isset( $settings['result_animation'] ) ) {
            $css .= '--med-result-animation: ' . sanitize_key( $settings['result_animation'] ) . ';';
        }
        if ( isset( $settings['enable_animations'] ) ) {
            $css .= '--med-animations-enabled: ' . ( $settings['enable_animations'] ? '1' : '0' ) . ';';
        }
        if ( isset( $settings['enable_hover_effects'] ) ) {
            $css .= '--med-hover-effects-enabled: ' . ( $settings['enable_hover_effects'] ? '1' : '0' ) . ';';
        }
        
        $css .= '}';
        
        // Add specific styles based on settings
        if ( isset( $settings['card_border_width'] ) && $settings['card_border_width'] > 0 ) {
            $css .= '.med-calc-card { border: ' . intval( $settings['card_border_width'] ) . 'px solid var(--med-border-color, #e5e7eb); }';
        }
        
        // Disable animations if setting is off
        if ( isset( $settings['enable_animations'] ) && ! $settings['enable_animations'] ) {
            $css .= '.med-calc-card * { transition-duration: 0s !important; animation: none !important; }';
        }
        
        // Enable hover effects if setting is on
        if ( isset( $settings['enable_hover_effects'] ) && $settings['enable_hover_effects'] ) {
            $css .= '.med-calc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }';
            $css .= '.med-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }';
        }
        
        if ( isset( $settings['input_border_width'] ) ) {
            $css .= '.med-input, .med-select { border-width: ' . intval( $settings['input_border_width'] ) . 'px; }';
        }
        
        if ( isset( $settings['button_text_transform'] ) ) {
            $css .= '.med-btn { text-transform: ' . sanitize_key( $settings['button_text_transform'] ) . '; }';
        }
        
        wp_add_inline_style( 'med-calculators-style', $css );

        // Modern design dynamic CSS
        $this->add_modern_dynamic_css( $settings );
    }

    /**
     * Add dynamic CSS for modern design template
     *
     * Inline CSS is attached to the registered 'med-calculators-modern' handle.
     * It will be printed automatically when the handle is enqueued.
     *
     * @param array $settings Plugin settings.
     * @return void
     */
    private function add_modern_dynamic_css( $settings ) {

        $shadow_map = array(
            'none' => 'none',
            'sm'   => '0 2px 10px rgba(0,0,0,0.06)',
            'md'   => '0 8px 40px rgba(0,0,0,0.12)',
            'lg'   => '0 12px 60px rgba(0,0,0,0.18)',
            'xl'   => '0 20px 80px rgba(0,0,0,0.24)',
        );

        $font_map = array(
            'system'     => "'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif",
            'inter'      => "'Inter', sans-serif",
            'roboto'     => "'Roboto', sans-serif",
            'poppins'    => "'Poppins', sans-serif",
            'opensans'   => "'Open Sans', sans-serif",
            'montserrat' => "'Montserrat', sans-serif",
            'lato'       => "'Lato', sans-serif",
            'nunito'     => "'Nunito', sans-serif",
            'cairo'      => "'Cairo', sans-serif",
            'tajawal'    => "'Tajawal', sans-serif",
        );

        $css = '.mcm {';

        // Colors
        $color_vars = array(
            'mcm_panel_color'  => '--mcm-panel-color',
            'mcm_active_color' => '--mcm-active-color',
            'mcm_result_bg'    => '--mcm-result-bg',
            'mcm_text_light'   => '--mcm-text-light',
            'mcm_text_dark'    => '--mcm-text-dark',
            'mcm_text_gray'    => '--mcm-text-gray',
        );

        foreach ( $color_vars as $key => $var ) {
            if ( ! empty( $settings[ $key ] ) ) {
                $hex = sanitize_hex_color( $settings[ $key ] );
                $css .= $var . ': ' . $hex . ';';

                // Generate a light variant for the panel color (used in fertility day etc.)
                if ( $key === 'mcm_panel_color' && $hex ) {
                    $r = hexdec( substr( $hex, 1, 2 ) );
                    $g = hexdec( substr( $hex, 3, 2 ) );
                    $b = hexdec( substr( $hex, 5, 2 ) );
                    $css .= '--mcm-panel-color-light: rgba(' . $r . ',' . $g . ',' . $b . ',0.3);';
                }
            }
        }

        // Input bg opacity
        if ( isset( $settings['mcm_input_bg_opacity'] ) ) {
            $opacity = intval( $settings['mcm_input_bg_opacity'] ) / 100;
            $css .= '--mcm-input-bg: rgba(0,0,0,' . $opacity . ');';
            $inactive = max( 0, $opacity - 0.06 );
            $css .= '--mcm-inactive-bg: rgba(0,0,0,' . $inactive . ');';
        }

        // Layout
        $layout_vars = array(
            'mcm_panel_width'        => array( '--mcm-panel-width', 'px' ),
            'mcm_panel_padding'      => array( '--mcm-panel-padding', 'px' ),
            'mcm_result_padding'     => array( '--mcm-result-padding', 'px' ),
            'mcm_border_radius'      => array( '--mcm-border-radius', 'px' ),
        );

        foreach ( $layout_vars as $key => $arr ) {
            if ( isset( $settings[ $key ] ) ) {
                $css .= $arr[0] . ': ' . intval( $settings[ $key ] ) . $arr[1] . ';';
            }
        }

        // Typography
        $typo_vars = array(
            'mcm_heading_size'       => array( '--mcm-heading-size', 'px' ),
            'mcm_body_size'          => array( '--mcm-body-size', 'px' ),
            'mcm_input_size'         => array( '--mcm-input-size', 'px' ),
            'mcm_result_number_size' => array( '--mcm-result-number-size', 'px' ),
            'mcm_btn_size'           => array( '--mcm-btn-size', 'px' ),
        );

        foreach ( $typo_vars as $key => $arr ) {
            if ( isset( $settings[ $key ] ) ) {
                $css .= $arr[0] . ': ' . intval( $settings[ $key ] ) . $arr[1] . ';';
            }
        }

        // Font family
        if ( ! empty( $settings['mcm_font_family'] ) && isset( $font_map[ $settings['mcm_font_family'] ] ) ) {
            $css .= '--mcm-ff: ' . $font_map[ $settings['mcm_font_family'] ] . ';';
        }

        // Shadow
        if ( ! empty( $settings['mcm_shadow'] ) && isset( $shadow_map[ $settings['mcm_shadow'] ] ) ) {
            $css .= '--mcm-shadow: ' . $shadow_map[ $settings['mcm_shadow'] ] . ';';
        }

        $css .= '}';

        // Max width
        if ( isset( $settings['mcm_max_width'] ) ) {
            $css .= '.mcm-wrapper { max-width: ' . intval( $settings['mcm_max_width'] ) . 'px; }';
        }

        // Button border radius
        if ( isset( $settings['mcm_btn_radius'] ) ) {
            $r = intval( $settings['mcm_btn_radius'] ) . 'px';
            $css .= '.mcm-gender__btn, .mcm-goal, .mcm-calc-btn, .mcm-tab { border-radius: ' . $r . '; }';
        }

        // Button padding
        if ( isset( $settings['mcm_btn_padding'] ) ) {
            $css .= '.mcm-calc-btn { padding-top: ' . intval( $settings['mcm_btn_padding'] ) . 'px; padding-bottom: ' . intval( $settings['mcm_btn_padding'] ) . 'px; }';
        }

        // Slider thumb style
        if ( ! empty( $settings['mcm_slider_thumb_style'] ) && $settings['mcm_slider_thumb_style'] === 'square' ) {
            $css .= '.mcm-slider::-webkit-slider-thumb { border-radius: 4px; }';
            $css .= '.mcm-slider::-moz-range-thumb { border-radius: 4px; }';
            $css .= '.mcm-protein-slider::-webkit-slider-thumb { border-radius: 4px; }';
            $css .= '.mcm-protein-slider::-moz-range-thumb { border-radius: 4px; }';
        }

        // Disable animations
        if ( isset( $settings['mcm_enable_animations'] ) && ! $settings['mcm_enable_animations'] ) {
            $css .= '.mcm * { transition-duration: 0s !important; animation: none !important; }';
        }

        // Hover effects
        if ( ! empty( $settings['mcm_enable_hover'] ) ) {
            $hover_color      = ! empty( $settings['mcm_hover_color'] ) ? sanitize_hex_color( $settings['mcm_hover_color'] ) : '#000000';
            $hover_brightness = isset( $settings['mcm_hover_brightness'] ) ? intval( $settings['mcm_hover_brightness'] ) : 90;
            $hover_scale      = isset( $settings['mcm_hover_scale'] ) ? intval( $settings['mcm_hover_scale'] ) / 100 : 1.02;

            $css .= '.mcm-calc-btn:hover { background: ' . $hover_color . ' !important; filter: brightness(' . $hover_brightness . '%); transform: scale(' . $hover_scale . '); }';
            $css .= '.mcm-gender__btn:hover, .mcm-goal:hover { opacity: 0.85 !important; transform: scale(' . $hover_scale . '); }';
            $css .= '.mcm-tab:hover { transform: scale(' . $hover_scale . '); }';
        }

        // Google Font loading
        if ( ! empty( $settings['mcm_font_family'] ) && $settings['mcm_font_family'] !== 'system' ) {
            $font_name = ucfirst( $settings['mcm_font_family'] );
            if ( $font_name === 'Opensans' ) $font_name = 'Open+Sans';
            wp_enqueue_style(
                'med-calc-google-font',
                'https://fonts.googleapis.com/css2?family=' . $font_name . ':wght@300;400;500;600;700&display=swap',
                array(),
                null
            );
        }

        wp_add_inline_style( 'med-calculators-modern', $css );
    }

    /**
     * Get registered calculators
     *
     * @return array
     */
    public function get_calculators() {
        return $this->calculators;
    }

    /**
     * Get admin instance
     *
     * @return Med_Calc_Admin|null
     */
    public function get_admin() {
        return $this->admin;
    }

    /**
     * Force Elementor to refresh cache when language/version changes
     *
     * This filter checks if the widget contains our shortcode and adds cache-busting
     * attributes to force Elementor to regenerate its HTML cache.
     *
     * @param string $content Widget content.
     * @param object $widget  Widget instance.
     * @return string Modified content.
     */
    public function elementor_cache_bust( $content, $widget ) {
        // Only process shortcode widgets
        if ( ! isset( $widget->get_settings()['shortcode'] ) ) {
            return $content;
        }

        $shortcode = $widget->get_settings()['shortcode'];
        
        // Check if it's our shortcode
        if ( strpos( $shortcode, '[med_calculator' ) === false ) {
            return $content;
        }

        // If content contains our calculator wrapper, add cache-busting comment
        if ( strpos( $content, 'class="mcm"' ) !== false ) {
            $current_locale = get_locale();
            $cache_comment = '<!-- med-calc-cache-bust:lang=' . esc_attr( $current_locale ) . '&v=' . esc_attr( MED_CALC_VERSION ) . ' -->';
            
            // Add comment at the beginning of the content
            $content = $cache_comment . $content;
        }

        return $content;
    }
}

