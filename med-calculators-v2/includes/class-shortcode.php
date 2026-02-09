<?php
/**
 * Shortcode Handler Class
 *
 * Handles shortcode rendering and asset enqueueing.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Shortcode
 *
 * Processes shortcode attributes and renders calculator templates.
 */
class Med_Calc_Shortcode {

    /**
     * Available calculator types
     *
     * @var array
     */
    private $calculators;

    /**
     * Track if assets have been enqueued
     *
     * @var bool
     */
    private static $assets_enqueued = false;

    /**
     * Constructor
     *
     * @param array $calculators Available calculator types.
     */
    public function __construct( array $calculators ) {
        $this->calculators = $calculators;
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render( $atts ) {
        // Check if template was explicitly provided
        $raw_atts = $atts;

        // Parse attributes
        $atts = shortcode_atts(
            array(
                'type'     => 'pregnancy',
                'template' => '',
            ),
            $atts,
            'med_calculator'
        );

        // Sanitize the type
        $type = sanitize_key( $atts['type'] );

        // Determine template: explicit attribute > global setting > default
        if ( ! empty( $atts['template'] ) ) {
            $template = sanitize_key( $atts['template'] );
        } else {
            $settings = get_option( 'med_calc_settings', array() );
            $template = ! empty( $settings['calculator_template'] ) 
                ? sanitize_key( $settings['calculator_template'] ) 
                : 'default';
        }

        // Validate calculator type
        if ( ! array_key_exists( $type, $this->calculators ) ) {
            return $this->render_error(
                sprintf(
                    /* translators: %s: calculator type */
                    __( 'Calculator type "%s" not found.', 'med-calculators' ),
                    esc_html( $type )
                )
            );
        }

        // Enqueue assets only when shortcode is used
        $this->enqueue_assets( $template );

        // Load and return template
        return $this->load_template( $type, $template );
    }

    /**
     * Enqueue plugin assets conditionally
     *
     * All assets are already registered by Med_Calc_Loader::register_assets().
     * This method just enqueues the appropriate handles.
     *
     * @param string $template Template name.
     * @return void
     */
    private function enqueue_assets( $template = 'default' ) {
        if ( self::$assets_enqueued ) {
            return;
        }

        // Enqueue base styles and scripts (already registered by loader)
        wp_enqueue_style( 'med-calculators-style' );
        wp_enqueue_script( 'med-calculators-app' );

        // Enqueue modern template assets (already registered by loader with proper deps)
        if ( $template === 'modern' ) {
            wp_enqueue_style( 'med-calculators-modern' );
            wp_enqueue_script( 'med-calculators-modern' );
        }

        self::$assets_enqueued = true;
    }

    /**
     * Load calculator template
     *
     * @param string $type     Calculator type.
     * @param string $template Template name.
     * @return string Template HTML.
     */
    private function load_template( $type, $template = 'default' ) {
        // Determine template file name
        if ( $template !== 'default' ) {
            $template_file = $type . '-' . $template . '.php';
        } else {
            $template_file = $type . '-form.php';
        }

        $template_path = MED_CALC_PLUGIN_DIR . 'templates/' . $template_file;

        // Fallback to default template if custom doesn't exist
        if ( ! file_exists( $template_path ) && $template !== 'default' ) {
            $template_path = MED_CALC_PLUGIN_DIR . 'templates/' . $type . '-form.php';
        }

        // Check if template exists
        if ( ! file_exists( $template_path ) ) {
            return $this->render_error(
                sprintf(
                    /* translators: %s: calculator type */
                    __( 'Template for "%s" calculator not found.', 'med-calculators' ),
                    esc_html( $type )
                )
            );
        }

        // Start output buffering
        ob_start();

        // Make type available to template
        $calculator_type = $type;

        // Add cache-busting comment for Elementor (includes current locale and version)
        // This forces Elementor to refresh its HTML cache when language changes
        $current_locale = get_locale();
        echo '<!-- med-calc-v' . esc_attr( MED_CALC_VERSION ) . '-lang:' . esc_attr( $current_locale ) . ' -->';

        // Include template
        include $template_path;

        // Return buffered content
        return ob_get_clean();
    }

    /**
     * Render an error message
     *
     * @param string $message Error message.
     * @return string Error HTML.
     */
    private function render_error( $message ) {
        return sprintf(
            '<div class="med-calc-error">%s</div>',
            esc_html( $message )
        );
    }
}

