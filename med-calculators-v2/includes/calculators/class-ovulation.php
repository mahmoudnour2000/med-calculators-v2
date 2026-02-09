<?php
/**
 * Ovulation Calculator Class
 *
 * Calculates ovulation window and fertile days based on cycle data.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Ovulation
 *
 * Calculates ovulation date and fertile window based on LMP and cycle length.
 */
class Med_Calc_Ovulation {

    /**
     * Days before ovulation when fertility begins
     *
     * @var int
     */
    const FERTILE_DAYS_BEFORE = 5;

    /**
     * Days after ovulation when fertility ends
     *
     * @var int
     */
    const FERTILE_DAYS_AFTER = 1;

    /**
     * Default cycle length
     *
     * @var int
     */
    const DEFAULT_CYCLE_LENGTH = 28;

    /**
     * Minimum valid cycle length
     *
     * @var int
     */
    const MIN_CYCLE_LENGTH = 21;

    /**
     * Maximum valid cycle length
     *
     * @var int
     */
    const MAX_CYCLE_LENGTH = 35;

    /**
     * Calculate ovulation and fertile window
     *
     * @param array $data Form data.
     * @return array|WP_Error Result data or error.
     */
    public function calculate( array $data ) {
        // Sanitize and validate inputs
        $lmp = isset( $data['lmp'] ) ? sanitize_text_field( $data['lmp'] ) : '';
        $cycle_length = isset( $data['cycle_length'] ) 
            ? absint( $data['cycle_length'] ) 
            : self::DEFAULT_CYCLE_LENGTH;

        // Validate LMP
        if ( empty( $lmp ) ) {
            return new WP_Error(
                'missing_lmp',
                __( 'Please enter the first day of your last period.', 'med-calculators' )
            );
        }

        // Parse LMP date
        $lmp_date = $this->parse_date( $lmp );

        if ( ! $lmp_date ) {
            return new WP_Error(
                'invalid_date',
                __( 'Please enter a valid date.', 'med-calculators' )
            );
        }

        // Validate cycle length
        if ( $cycle_length < self::MIN_CYCLE_LENGTH || $cycle_length > self::MAX_CYCLE_LENGTH ) {
            return new WP_Error(
                'invalid_cycle',
                sprintf(
                    /* translators: 1: minimum days, 2: maximum days */
                    __( 'Cycle length must be between %1$d and %2$d days.', 'med-calculators' ),
                    self::MIN_CYCLE_LENGTH,
                    self::MAX_CYCLE_LENGTH
                )
            );
        }

        // Calculate ovulation day (typically 14 days before next period)
        $days_to_ovulation = $cycle_length - 14;
        $ovulation_date = clone $lmp_date;
        $ovulation_date->modify( '+' . $days_to_ovulation . ' days' );

        // Calculate next period date
        $next_period = clone $lmp_date;
        $next_period->modify( '+' . $cycle_length . ' days' );

        // Calculate fertile window
        $fertile_start = clone $ovulation_date;
        $fertile_start->modify( '-' . self::FERTILE_DAYS_BEFORE . ' days' );

        $fertile_end = clone $ovulation_date;
        $fertile_end->modify( '+' . self::FERTILE_DAYS_AFTER . ' days' );

        // Calculate peak fertility days (2 days before and day of ovulation)
        $peak_start = clone $ovulation_date;
        $peak_start->modify( '-2 days' );

        // Generate fertile days list
        $fertile_days = $this->generate_fertile_days( $fertile_start, $fertile_end, $ovulation_date );

        // Determine current fertility status
        $fertility_status = $this->get_fertility_status( $fertile_start, $fertile_end, $ovulation_date );

        return array(
            'result'          => sprintf(
                /* translators: %s: ovulation date */
                __( 'Your estimated ovulation date is %s', 'med-calculators' ),
                wp_date( get_option( 'date_format' ), $ovulation_date->getTimestamp() )
            ),
            'ovulation_date'  => wp_date( get_option( 'date_format' ), $ovulation_date->getTimestamp() ),
            'ovulation_iso'   => $ovulation_date->format( 'Y-m-d' ),
            'fertile_start'   => wp_date( get_option( 'date_format' ), $fertile_start->getTimestamp() ),
            'fertile_end'     => wp_date( get_option( 'date_format' ), $fertile_end->getTimestamp() ),
            'fertile_window'  => sprintf(
                /* translators: 1: start date, 2: end date */
                __( '%1$s to %2$s', 'med-calculators' ),
                wp_date( get_option( 'date_format' ), $fertile_start->getTimestamp() ),
                wp_date( get_option( 'date_format' ), $fertile_end->getTimestamp() )
            ),
            'next_period'     => wp_date( get_option( 'date_format' ), $next_period->getTimestamp() ),
            'cycle_length'    => $cycle_length,
            'fertile_days'    => $fertile_days,
            'peak_fertility'  => sprintf(
                /* translators: 1: start date, 2: end date */
                __( '%1$s to %2$s', 'med-calculators' ),
                wp_date( get_option( 'date_format' ), $peak_start->getTimestamp() ),
                wp_date( get_option( 'date_format' ), $ovulation_date->getTimestamp() )
            ),
            'fertility_status' => $fertility_status,
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
     * Generate list of fertile days with fertility levels
     *
     * @param DateTime $start  Fertile window start.
     * @param DateTime $end    Fertile window end.
     * @param DateTime $ovulation Ovulation date.
     * @return array Array of fertile days.
     */
    private function generate_fertile_days( DateTime $start, DateTime $end, DateTime $ovulation ) {
        $days = array();
        $current = clone $start;

        while ( $current <= $end ) {
            $diff_to_ovulation = abs( $ovulation->diff( $current )->days );
            
            // Determine fertility level
            if ( $diff_to_ovulation <= 1 ) {
                $level = 'high';
            } elseif ( $diff_to_ovulation <= 2 ) {
                $level = 'medium';
            } else {
                $level = 'low';
            }

            $days[] = array(
                'date'  => wp_date( get_option( 'date_format' ), $current->getTimestamp() ),
                'iso'   => $current->format( 'Y-m-d' ),
                'level' => $level,
                'is_ovulation' => $current->format( 'Y-m-d' ) === $ovulation->format( 'Y-m-d' ),
            );

            $current->modify( '+1 day' );
        }

        return $days;
    }

    /**
     * Get current fertility status
     *
     * @param DateTime $fertile_start Fertile window start.
     * @param DateTime $fertile_end   Fertile window end.
     * @param DateTime $ovulation     Ovulation date.
     * @return array Fertility status.
     */
    private function get_fertility_status( DateTime $fertile_start, DateTime $fertile_end, DateTime $ovulation ) {
        $today = new DateTime( 'now', wp_timezone() );
        $today->setTime( 0, 0, 0 );

        if ( $today < $fertile_start ) {
            $days_until = $fertile_start->diff( $today )->days;
            return array(
                'status'  => 'before',
                'message' => sprintf(
                    /* translators: %d: number of days */
                    _n(
                        'Fertile window begins in %d day.',
                        'Fertile window begins in %d days.',
                        $days_until,
                        'med-calculators'
                    ),
                    $days_until
                ),
            );
        } elseif ( $today >= $fertile_start && $today <= $fertile_end ) {
            if ( $today->format( 'Y-m-d' ) === $ovulation->format( 'Y-m-d' ) ) {
                return array(
                    'status'  => 'ovulation',
                    'message' => __( 'Today is your estimated ovulation day!', 'med-calculators' ),
                );
            }
            return array(
                'status'  => 'fertile',
                'message' => __( 'You are currently in your fertile window.', 'med-calculators' ),
            );
        } else {
            return array(
                'status'  => 'after',
                'message' => __( 'Your fertile window has passed for this cycle.', 'med-calculators' ),
            );
        }
    }
}

