<?php
/**
 * Integrations Handler Class
 *
 * Handles third-party integrations (Mailchimp, Zapier, etc.).
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Integrations
 *
 * Manages third-party integrations.
 */
class Med_Calc_Integrations {

    /**
     * Send data to integrations
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param string $calculator_type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @return void
     */
    public function send_to_integrations( $email, $name, $calculator_type, $inputs, $result ) {
        $settings = get_option( 'med_calc_settings', array() );

        // Mailchimp
        if ( ! empty( $settings['mailchimp_enabled'] ) && ! empty( $settings['mailchimp_api_key'] ) && ! empty( $settings['mailchimp_list_id'] ) ) {
            $this->send_to_mailchimp( $email, $name, $settings );
        }

        // Zapier Webhook
        if ( ! empty( $settings['zapier_enabled'] ) && ! empty( $settings['zapier_webhook_url'] ) ) {
            $this->send_to_zapier( $email, $name, $calculator_type, $inputs, $result, $settings );
        }

        // ConvertKit
        if ( ! empty( $settings['convertkit_enabled'] ) && ! empty( $settings['convertkit_api_key'] ) && ! empty( $settings['convertkit_form_id'] ) ) {
            $this->send_to_convertkit( $email, $name, $settings );
        }

        // HubSpot
        if ( ! empty( $settings['hubspot_enabled'] ) && ! empty( $settings['hubspot_api_key'] ) ) {
            $this->send_to_hubspot( $email, $name, $calculator_type, $inputs, $result, $settings );
        }
    }

    /**
     * Send to Mailchimp
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param array  $settings Plugin settings.
     * @return bool
     */
    private function send_to_mailchimp( $email, $name, $settings ) {
        $api_key = $settings['mailchimp_api_key'];
        $list_id = $settings['mailchimp_list_id'];
        $data_center = substr( $api_key, strpos( $api_key, '-' ) + 1 );

        $url = "https://{$data_center}.api.mailchimp.com/3.0/lists/{$list_id}/members/";

        $body = array(
            'email_address' => $email,
            'status' => 'subscribed',
        );

        if ( ! empty( $name ) ) {
            $name_parts = explode( ' ', $name, 2 );
            $body['merge_fields'] = array(
                'FNAME' => $name_parts[0],
                'LNAME' => isset( $name_parts[1] ) ? $name_parts[1] : '',
            );
        }

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( 'apikey:' . $api_key ),
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code === 200 || $status_code === 201;
    }

    /**
     * Send to Zapier Webhook
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param string $calculator_type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @param array  $settings Plugin settings.
     * @return bool
     */
    private function send_to_zapier( $email, $name, $calculator_type, $inputs, $result, $settings ) {
        $webhook_url = $settings['zapier_webhook_url'];

        $data = array(
            'email' => $email,
            'name' => $name,
            'calculator_type' => $calculator_type,
            'inputs' => $inputs,
            'result' => $result,
            'site_name' => get_bloginfo( 'name' ),
            'site_url' => home_url(),
            'timestamp' => current_time( 'mysql' ),
        );

        $response = wp_remote_post(
            $webhook_url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $data ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code >= 200 && $status_code < 300;
    }

    /**
     * Send to ConvertKit
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param array  $settings Plugin settings.
     * @return bool
     */
    private function send_to_convertkit( $email, $name, $settings ) {
        $api_key = $settings['convertkit_api_key'];
        $form_id = $settings['convertkit_form_id'];

        $url = "https://api.convertkit.com/v3/forms/{$form_id}/subscribe";

        $body = array(
            'api_key' => $api_key,
            'email' => $email,
        );

        if ( ! empty( $name ) ) {
            $body['first_name'] = $name;
        }

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code === 200;
    }

    /**
     * Send to HubSpot
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param string $calculator_type Calculator type.
     * @param array  $inputs Input data.
     * @param array  $result Result data.
     * @param array  $settings Plugin settings.
     * @return bool
     */
    private function send_to_hubspot( $email, $name, $calculator_type, $inputs, $result, $settings ) {
        $api_key = $settings['hubspot_api_key'];

        $url = "https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/{$email}";

        $properties = array(
            array(
                'property' => 'email',
                'value' => $email,
            ),
        );

        if ( ! empty( $name ) ) {
            $name_parts = explode( ' ', $name, 2 );
            $properties[] = array(
                'property' => 'firstname',
                'value' => $name_parts[0],
            );
            if ( isset( $name_parts[1] ) ) {
                $properties[] = array(
                    'property' => 'lastname',
                    'value' => $name_parts[1],
                );
            }
        }

        // Add custom properties
        $properties[] = array(
            'property' => 'calculator_type',
            'value' => $calculator_type,
        );

        $body = array(
            'properties' => $properties,
        );

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body' => wp_json_encode( $body ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code === 200 || $status_code === 201;
    }
}

