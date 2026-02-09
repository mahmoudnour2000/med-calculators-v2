<?php
/**
 * Database Handler Class
 *
 * Handles all database operations for calculation logs.
 *
 * @package MedCalculators
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Med_Calc_Database
 *
 * Manages database operations for calculation logs.
 */
class Med_Calc_Database {

    /**
     * Table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'med_calc_logs';
    }

    /**
     * Create database table
     *
     * @return void
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            calculator_type varchar(50) NOT NULL,
            user_name varchar(255) DEFAULT NULL,
            user_email varchar(255) DEFAULT NULL,
            inputs longtext NOT NULL,
            result longtext NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            consent_given tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY calculator_type (calculator_type),
            KEY user_email (user_email),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Drop database table
     *
     * @return void
     */
    public function drop_table() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
    }

    /**
     * Log calculation
     *
     * @param array $data Calculation data.
     * @return int|false Log ID or false on failure.
     */
    public function log_calculation( $data ) {
        global $wpdb;

        $defaults = array(
            'calculator_type' => '',
            'user_name'       => '',
            'user_email'      => '',
            'inputs'          => array(),
            'result'          => array(),
            'ip_address'      => $this->get_client_ip(),
            'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
            'consent_given'   => 0,
        );

        $data = wp_parse_args( $data, $defaults );

        // Sanitize and prepare data
        $insert_data = array(
            'calculator_type' => sanitize_text_field( $data['calculator_type'] ),
            'user_name'       => sanitize_text_field( $data['user_name'] ),
            'user_email'      => sanitize_email( $data['user_email'] ),
            'inputs'           => wp_json_encode( $data['inputs'] ),
            'result'           => wp_json_encode( $data['result'] ),
            'ip_address'       => sanitize_text_field( $data['ip_address'] ),
            'user_agent'      => sanitize_text_field( $data['user_agent'] ),
            'consent_given'   => $data['consent_given'] ? 1 : 0,
        );

        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        if ( $result ) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get calculations
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_calculations( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'calculator_type' => '',
            'limit'           => 50,
            'offset'          => 0,
            'orderby'         => 'created_at',
            'order'           => 'DESC',
            'date_from'       => '',
            'date_to'         => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );
        $where_values = array();

        if ( ! empty( $args['calculator_type'] ) ) {
            $where[] = 'calculator_type = %s';
            $where_values[] = $args['calculator_type'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = implode( ' AND ', $where );

        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
        if ( ! $orderby ) {
            $orderby = 'created_at DESC';
        }

        $limit = absint( $args['limit'] );
        $offset = absint( $args['offset'] );

        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Get statistics
     *
     * @return array
     */
    public function get_statistics() {
        global $wpdb;

        $stats = array();

        // Total calculations
        $stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

        // By calculator type
        $stats['by_type'] = $wpdb->get_results(
            "SELECT calculator_type, COUNT(*) as count FROM {$this->table_name} GROUP BY calculator_type",
            ARRAY_A
        );

        // Emails collected
        $stats['emails_collected'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_email) FROM {$this->table_name} WHERE user_email != ''"
        );

        // Today's calculations
        $stats['today'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE DATE(created_at) = CURDATE()"
        );

        // This week
        $stats['this_week'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE WEEK(created_at) = WEEK(NOW())"
        );

        // This month
        $stats['this_month'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
        );

        // Daily usage (last 30 days)
        $stats['daily_usage'] = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            ARRAY_A
        );

        return $stats;
    }

    /**
     * Delete calculation log
     *
     * @param int $id Log ID.
     * @return bool
     */
    public function delete_log( $id ) {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Delete logs by date range
     *
     * @param string $date_from Start date.
     * @param string $date_to End date.
     * @return int Number of deleted rows.
     */
    public function delete_logs_by_date( $date_from, $date_to ) {
        global $wpdb;
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at >= %s AND created_at <= %s",
                $date_from,
                $date_to
            )
        );
    }

    /**
     * Export to CSV
     *
     * @param array $args Query arguments.
     * @return string CSV content.
     */
    public function export_csv( $args = array() ) {
        $calculations = $this->get_calculations( array_merge( $args, array( 'limit' => 10000 ) ) );

        $output = fopen( 'php://output', 'w' );

        // Headers
        fputcsv( $output, array(
            __( 'ID', 'med-calculators' ),
            __( 'Calculator Type', 'med-calculators' ),
            __( 'Name', 'med-calculators' ),
            __( 'Email', 'med-calculators' ),
            __( 'Inputs', 'med-calculators' ),
            __( 'Result', 'med-calculators' ),
            __( 'IP Address', 'med-calculators' ),
            __( 'Consent Given', 'med-calculators' ),
            __( 'Date', 'med-calculators' ),
        ) );

        // Data rows
        foreach ( $calculations as $calc ) {
            $inputs = json_decode( $calc['inputs'], true );
            $result = json_decode( $calc['result'], true );

            fputcsv( $output, array(
                $calc['id'],
                $calc['calculator_type'],
                $calc['user_name'],
                $calc['user_email'],
                wp_json_encode( $inputs ),
                wp_json_encode( $result ),
                $calc['ip_address'],
                $calc['consent_given'] ? __( 'Yes', 'med-calculators' ) : __( 'No', 'med-calculators' ),
                $calc['created_at'],
            ) );
        }

        fclose( $output );
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }

        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }
}

