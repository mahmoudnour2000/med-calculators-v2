<?php
/**
 * Pregnancy Due Date Calculator Class
 *
 * Calculates expected due date based on last menstrual period.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Pregnancy
 *
 * Calculates pregnancy due date using Naegele's rule (+280 days from LMP).
 */
class Med_Calc_Pregnancy {

    /**
     * Pregnancy duration in days (40 weeks)
     *
     * @var int
     */
    const PREGNANCY_DAYS = 280;

    /**
     * Calculate the expected due date
     *
     * @param array $data Form data.
     * @return array|WP_Error Result data or error.
     */
    public function calculate( array $data ) {
        // Sanitize and validate input
        $lmp = isset( $data['lmp'] ) ? sanitize_text_field( $data['lmp'] ) : '';

        if ( empty( $lmp ) ) {
            return new WP_Error(
                'missing_lmp',
                __( 'Please enter your last menstrual period date.', 'med-calculators' )
            );
        }

        // Validate date format
        $lmp_date = $this->parse_date( $lmp );

        if ( ! $lmp_date ) {
            return new WP_Error(
                'invalid_date',
                __( 'Please enter a valid date.', 'med-calculators' )
            );
        }

        // Check if date is in reasonable range
        $validation = $this->validate_date_range( $lmp_date );

        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        // Calculate due date
        $due_date = $this->calculate_due_date( $lmp_date );

        // Calculate current pregnancy week
        $current_week = $this->calculate_current_week( $lmp_date );

        // Calculate trimester
        $trimester = $this->calculate_trimester( $current_week );

        // Calculate conception date (approximately 2 weeks after LMP)
        $conception_date = clone $lmp_date;
        $conception_date->modify( '+14 days' );

        // Calculate days remaining
        $today = new DateTime( 'now', wp_timezone() );
        $days_remaining = max( 0, $due_date->diff( $today )->days );
        
        if ( $due_date < $today ) {
            $days_remaining = 0;
        }

        return array(
            'result'          => sprintf(
                /* translators: %s: expected due date */
                __( 'Your expected due date is %s', 'med-calculators' ),
                wp_date( get_option( 'date_format' ), $due_date->getTimestamp() )
            ),
            'due_date'        => wp_date( get_option( 'date_format' ), $due_date->getTimestamp() ),
            'due_date_iso'    => $due_date->format( 'Y-m-d' ),
            'conception_date' => wp_date( get_option( 'date_format' ), $conception_date->getTimestamp() ),
            'current_week'    => $current_week,
            'trimester'       => $trimester,
            'days_remaining'  => $days_remaining,
            'weeks_remaining' => floor( $days_remaining / 7 ),
        );
    }

    /**
     * Parse date string into DateTime object
     *
     * @param string $date_string Date string.
     * @return DateTime|false DateTime object or false on failure.
     */
    private function parse_date( $date_string ) {
        try {
            $date = new DateTime( $date_string, wp_timezone() );
            return $date;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Validate that the date is within reasonable range
     *
     * @param DateTime $lmp_date LMP date.
     * @return true|WP_Error True if valid, error otherwise.
     */
    private function validate_date_range( DateTime $lmp_date ) {
        $today = new DateTime( 'now', wp_timezone() );
        
        // LMP should not be in the future
        if ( $lmp_date > $today ) {
            return new WP_Error(
                'future_date',
                __( 'The date cannot be in the future.', 'med-calculators' )
            );
        }

        // LMP should not be more than 280 days ago (past due date)
        $max_past = clone $today;
        $max_past->modify( '-' . self::PREGNANCY_DAYS . ' days' );

        if ( $lmp_date < $max_past ) {
            return new WP_Error(
                'date_too_old',
                __( 'The date is too far in the past for an active pregnancy.', 'med-calculators' )
            );
        }

        return true;
    }

    /**
     * Calculate the due date
     *
     * @param DateTime $lmp_date LMP date.
     * @return DateTime Due date.
     */
    private function calculate_due_date( DateTime $lmp_date ) {
        $due_date = clone $lmp_date;
        $due_date->modify( '+' . self::PREGNANCY_DAYS . ' days' );
        return $due_date;
    }

    /**
     * Calculate current pregnancy week
     *
     * @param DateTime $lmp_date LMP date.
     * @return int Current week (1-42).
     */
    private function calculate_current_week( DateTime $lmp_date ) {
        $today = new DateTime( 'now', wp_timezone() );
        $days_pregnant = $lmp_date->diff( $today )->days;
        $current_week = floor( $days_pregnant / 7 ) + 1;
        
        return min( max( $current_week, 1 ), 42 );
    }

    /**
     * Calculate current trimester
     *
     * @param int $week Current week.
     * @return int Trimester number (1-3).
     */
    private function calculate_trimester( $week ) {
        if ( $week <= 12 ) {
            return 1;
        } elseif ( $week <= 27 ) {
            return 2;
        } else {
            return 3;
        }
    }
}

