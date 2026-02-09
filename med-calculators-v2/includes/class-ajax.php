<?php
/**
 * AJAX Handler Class
 *
 * Processes all AJAX requests for calculators.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Ajax
 *
 * Handles AJAX requests and delegates to appropriate calculator classes.
 */
class Med_Calc_Ajax {

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
     * Handle incoming AJAX request
     *
     * @return void
     */
    public function handle_request() {
        // Verify nonce for security
        if ( ! check_ajax_referer( 'med_calc_nonce', 'nonce', false ) ) {
            $this->send_error( __( 'Security check failed. Please refresh the page.', 'med-calculators' ) );
        }

        $settings = get_option( 'med_calc_settings', array() );
        $output_mode = isset( $settings['output_mode'] ) ? $settings['output_mode'] : 'instant';

        // Handle different request types
        $action = isset( $_POST['action_type'] ) ? sanitize_key( $_POST['action_type'] ) : 'calculate';

        if ( $action === 'submit_email' ) {
            $this->handle_email_submission();
            return;
        }

        // Get and sanitize calculator type
        $type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';

        // Validate calculator type
        if ( empty( $type ) || ! array_key_exists( $type, $this->calculators ) ) {
            $this->send_error( __( 'Invalid calculator type.', 'med-calculators' ) );
        }

        // Get user data if provided
        $user_name = isset( $_POST['user_name'] ) ? sanitize_text_field( wp_unslash( $_POST['user_name'] ) ) : '';
        $user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
        $consent_given = isset( $_POST['consent'] ) ? (bool) $_POST['consent'] : false;

        // Check output mode requirements
        if ( $output_mode === 'email_first' || $output_mode === 'email_only' ) {
            if ( empty( $user_email ) ) {
                $this->send_error( 
                    array(
                        'message' => __( 'Email is required to view results.', 'med-calculators' ),
                        'require_email' => true,
                    )
                );
            }
        }

        // Get the calculator class
        $class_name = $this->calculators[ $type ];

        // Instantiate and calculate
        try {
            $calculator = new $class_name();
            
            // Prepare inputs (remove user data from calculation inputs)
            $calc_inputs = $_POST;
            unset( $calc_inputs['user_name'], $calc_inputs['user_email'], $calc_inputs['consent'], $calc_inputs['action_type'] );
            
            $result = $calculator->calculate( $calc_inputs );

            if ( is_wp_error( $result ) ) {
                $this->send_error( $result->get_error_message() );
            }

            // Log calculation
            if ( ! empty( $settings['enable_logging'] ) ) {
                $this->log_calculation( $type, $user_name, $user_email, $calc_inputs, $result, $consent_given );
            }

            // Send email if enabled and email provided
            if ( ! empty( $settings['enable_email'] ) && ! empty( $user_email ) ) {
                $this->send_result_email( $user_email, $user_name, $type, $calc_inputs, $result );
            }

            // Send to integrations
            if ( ! empty( $user_email ) ) {
                $this->send_to_integrations( $user_email, $user_name, $type, $calc_inputs, $result );
            }

            // Prepare response based on output mode
            $response_data = array(
                'result' => $result,
                'output_mode' => $output_mode,
            );

            if ( $output_mode === 'email_only' ) {
                $response_data['message'] = __( 'Your results have been sent to your email address.', 'med-calculators' );
                $response_data['show_result'] = false;
            } else {
                $response_data['show_result'] = true;
            }

            $this->send_success( $response_data );

        } catch ( Exception $e ) {
            $this->send_error( $e->getMessage() );
        }
    }

    /**
     * Handle email submission (for email_first mode)
     *
     * @return void
     */
    private function handle_email_submission() {
        $user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
        $user_name = isset( $_POST['user_name'] ) ? sanitize_text_field( wp_unslash( $_POST['user_name'] ) ) : '';
        $type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
        $calc_data = isset( $_POST['calc_data'] ) ? json_decode( wp_unslash( $_POST['calc_data'] ), true ) : array();

        if ( empty( $user_email ) ) {
            $this->send_error( __( 'Email is required.', 'med-calculators' ) );
        }

        if ( empty( $calc_data ) || empty( $calc_data['result'] ) ) {
            $this->send_error( __( 'Calculation data not found.', 'med-calculators' ) );
        }

        $settings = get_option( 'med_calc_settings', array() );

        // Send email
        if ( ! empty( $settings['enable_email'] ) ) {
            $this->send_result_email( $user_email, $user_name, $type, $calc_data['inputs'], $calc_data['result'] );
        }

        // Send to integrations
        $this->send_to_integrations( $user_email, $user_name, $type, $calc_data['inputs'], $calc_data['result'] );

        // Log calculation
        if ( ! empty( $settings['enable_logging'] ) ) {
            $consent = isset( $_POST['consent'] ) ? (bool) $_POST['consent'] : false;
            $this->log_calculation( $type, $user_name, $user_email, $calc_data['inputs'], $calc_data['result'], $consent );
        }

        $this->send_success( array(
            'message' => __( 'Email sent successfully. Check your inbox for results.', 'med-calculators' ),
            'result' => $calc_data['result'],
        ) );
    }

    /**
     * Log calculation
     *
     * @param string $type Calculator type.
     * @param string $name User name.
     * @param string $email User email.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @param bool   $consent Consent given.
     * @return void
     */
    private function log_calculation( $type, $name, $email, $inputs, $result, $consent ) {
        $database = new Med_Calc_Database();
        $database->log_calculation( array(
            'calculator_type' => $type,
            'user_name' => $name,
            'user_email' => $email,
            'inputs' => $inputs,
            'result' => $result,
            'consent_given' => $consent,
        ) );
    }

    /**
     * Send result email
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param string $type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @return void
     */
    private function send_result_email( $email, $name, $type, $inputs, $result ) {
        $email_handler = new Med_Calc_Email();
        $email_handler->send_result_email( $email, $type, $inputs, $result, $name );
    }

    /**
     * Send to integrations
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param string $type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @return void
     */
    private function send_to_integrations( $email, $name, $type, $inputs, $result ) {
        $integrations = new Med_Calc_Integrations();
        $integrations->send_to_integrations( $email, $name, $type, $inputs, $result );
    }

    /**
     * Send success response
     *
     * @param array $data Response data.
     * @return void
     */
    private function send_success( array $data ) {
        wp_send_json_success( $data );
    }

    /**
     * Send error response
     *
     * @param string|array $message Error message or error data.
     * @return void
     */
    private function send_error( $message ) {
        if ( is_array( $message ) ) {
            wp_send_json_error( $message );
        } else {
            wp_send_json_error(
                array(
                    'message' => $message,
                )
            );
        }
    }
}

