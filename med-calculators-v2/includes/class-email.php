<?php
/**
 * Email Handler Class
 *
 * Handles email sending for calculation results.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Email
 *
 * Manages email operations for calculation results.
 */
class Med_Calc_Email {

    /**
     * Send calculation result email
     *
     * @param string $to Recipient email.
     * @param string $calculator_type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @param string $user_name User name (optional).
     * @return bool
     */
    public function send_result_email( $to, $calculator_type, $inputs, $result, $user_name = '' ) {
        $settings = get_option( 'med_calc_settings', array() );

        // Check if email is enabled
        if ( empty( $settings['enable_email'] ) ) {
            return false;
        }

        $subject = $this->get_email_subject( $calculator_type, $settings );
        $message = $this->get_email_template( $calculator_type, $inputs, $result, $user_name, $settings );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        // From email
        $from_email = ! empty( $settings['email_from'] ) ? $settings['email_from'] : get_option( 'admin_email' );
        $from_name = ! empty( $settings['email_from_name'] ) ? $settings['email_from_name'] : get_bloginfo( 'name' );
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Get email subject
     *
     * @param string $calculator_type Calculator type.
     * @param array  $settings Plugin settings.
     * @return string
     */
    private function get_email_subject( $calculator_type, $settings ) {
        $default_subjects = array(
            'pregnancy' => __( 'Your Pregnancy Calculator Results', 'med-calculators' ),
            'ovulation' => __( 'Your Ovulation Calculator Results', 'med-calculators' ),
            'calories'  => __( 'Your Calories Calculator Results', 'med-calculators' ),
        );

        $subject = ! empty( $settings['email_subject'] ) ? $settings['email_subject'] : $default_subjects[ $calculator_type ];

        // Replace placeholders
        $subject = str_replace( '{calculator}', $this->get_calculator_label( $calculator_type ), $subject );
        $subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject );

        return $subject;
    }

    /**
     * Get email template
     *
     * @param string $calculator_type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @param string $user_name User name.
     * @param array  $settings Plugin settings.
     * @return string
     */
    private function get_email_template( $calculator_type, $inputs, $result, $user_name, $settings ) {
        // Get custom template or use default
        $template = ! empty( $settings['email_template'] ) ? $settings['email_template'] : $this->get_default_template();

        // Replace placeholders
        $replacements = array(
            '{logo}'           => $this->get_logo_html( $settings ),
            '{site_name}'      => get_bloginfo( 'name' ),
            '{site_url}'       => home_url(),
            '{user_name}'      => ! empty( $user_name ) ? esc_html( $user_name ) : __( 'User', 'med-calculators' ),
            '{calculator}'     => $this->get_calculator_label( $calculator_type ),
            '{inputs}'         => $this->format_inputs( $inputs, $calculator_type ),
            '{results}'        => $this->format_results( $result, $calculator_type ),
            '{date}'           => current_time( 'F j, Y' ),
            '{privacy_text}'   => ! empty( $settings['privacy_text'] ) ? wp_kses_post( $settings['privacy_text'] ) : '',
        );

        $template = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

        return $template;
    }

    /**
     * Get default email template
     *
     * @return string
     */
    private function get_default_template() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . esc_html__( 'Your Calculation Results', 'med-calculators' ) . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                {logo}
                
                <h1 style="color: #0d9488; margin-top: 20px; margin-bottom: 10px;">' . esc_html__( 'Your Calculation Results', 'med-calculators' ) . '</h1>
                
                <p style="margin-bottom: 20px;">' . esc_html__( 'Hello {user_name},', 'med-calculators' ) . '</p>
                
                <p style="margin-bottom: 20px;">' . esc_html__( 'Thank you for using our {calculator}. Below are your results:', 'med-calculators' ) . '</p>
                
                <div style="background: #f5f5f5; border-left: 4px solid #0d9488; padding: 20px; margin: 20px 0;">
                    <h2 style="color: #0d9488; margin-top: 0;">' . esc_html__( 'Your Inputs', 'med-calculators' ) . '</h2>
                    {inputs}
                </div>
                
                <div style="background: #e0f2f1; border-left: 4px solid #0d9488; padding: 20px; margin: 20px 0;">
                    <h2 style="color: #0d9488; margin-top: 0;">' . esc_html__( 'Results', 'med-calculators' ) . '</h2>
                    {results}
                </div>
                
                <p style="margin-top: 30px; font-size: 12px; color: #666;">
                    {privacy_text}
                </p>
                
                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    ' . esc_html__( 'This email was sent from', 'med-calculators' ) . ' <a href="{site_url}" style="color: #0d9488;">{site_name}</a>
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * Get logo HTML
     *
     * @param array $settings Plugin settings.
     * @return string
     */
    private function get_logo_html( $settings ) {
        if ( ! empty( $settings['email_logo'] ) ) {
            $logo_url = wp_get_attachment_image_url( $settings['email_logo'], 'full' );
            if ( $logo_url ) {
                return '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" style="max-width: 200px; height: auto; margin-bottom: 20px;">';
            }
        }
        return '<h2 style="color: #0d9488; margin-top: 0;">' . esc_html( get_bloginfo( 'name' ) ) . '</h2>';
    }

    /**
     * Format inputs for email
     *
     * @param array  $inputs Input data.
     * @param string $calculator_type Calculator type.
     * @return string
     */
    private function format_inputs( $inputs, $calculator_type ) {
        $html = '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ( $inputs as $key => $value ) {
            $label = $this->get_field_label( $key, $calculator_type );
            $html .= '<tr>';
            $html .= '<td style="padding: 8px 0; font-weight: bold; width: 40%;">' . esc_html( $label ) . ':</td>';
            $html .= '<td style="padding: 8px 0;">' . esc_html( $value ) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }

    /**
     * Format results for email
     *
     * @param array  $result Result data.
     * @param string $calculator_type Calculator type.
     * @return string
     */
    private function format_results( $result, $calculator_type ) {
        $html = '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ( $result as $key => $value ) {
            $label = $this->get_result_label( $key, $calculator_type );
            $html .= '<tr>';
            $html .= '<td style="padding: 8px 0; font-weight: bold; width: 40%;">' . esc_html( $label ) . ':</td>';
            $html .= '<td style="padding: 8px 0; color: #0d9488; font-size: 16px;">' . esc_html( $value ) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }

    /**
     * Get field label
     *
     * @param string $field Field key.
     * @param string $calculator_type Calculator type.
     * @return string
     */
    private function get_field_label( $field, $calculator_type ) {
        $labels = array(
            'pregnancy' => array(
                'last_period' => __( 'Last Period Date', 'med-calculators' ),
            ),
            'ovulation' => array(
                'last_period' => __( 'Last Period Date', 'med-calculators' ),
                'cycle_length' => __( 'Cycle Length', 'med-calculators' ),
            ),
            'calories' => array(
                'gender' => __( 'Gender', 'med-calculators' ),
                'weight' => __( 'Weight', 'med-calculators' ),
                'height' => __( 'Height', 'med-calculators' ),
                'age' => __( 'Age', 'med-calculators' ),
                'activity' => __( 'Activity Level', 'med-calculators' ),
            ),
        );

        return isset( $labels[ $calculator_type ][ $field ] ) ? $labels[ $calculator_type ][ $field ] : ucfirst( str_replace( '_', ' ', $field ) );
    }

    /**
     * Get result label
     *
     * @param string $field Field key.
     * @param string $calculator_type Calculator type.
     * @return string
     */
    private function get_result_label( $field, $calculator_type ) {
        $labels = array(
            'pregnancy' => array(
                'due_date' => __( 'Expected Due Date', 'med-calculators' ),
                'current_week' => __( 'Current Week', 'med-calculators' ),
                'trimester' => __( 'Trimester', 'med-calculators' ),
            ),
            'ovulation' => array(
                'ovulation_date' => __( 'Ovulation Date', 'med-calculators' ),
                'fertile_window' => __( 'Fertile Window', 'med-calculators' ),
            ),
            'calories' => array(
                'tdee' => __( 'Daily Calories', 'med-calculators' ),
                'bmr' => __( 'BMR', 'med-calculators' ),
            ),
        );

        return isset( $labels[ $calculator_type ][ $field ] ) ? $labels[ $calculator_type ][ $field ] : ucfirst( str_replace( '_', ' ', $field ) );
    }

    /**
     * Get calculator label
     *
     * @param string $type Calculator type.
     * @return string
     */
    private function get_calculator_label( $type ) {
        $labels = array(
            'pregnancy' => __( 'Pregnancy Calculator', 'med-calculators' ),
            'ovulation' => __( 'Ovulation Calculator', 'med-calculators' ),
            'calories' => __( 'Calories Calculator', 'med-calculators' ),
        );

        return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( $type );
    }
}

