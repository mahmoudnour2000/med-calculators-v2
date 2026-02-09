<?php
/**
 * Uninstall Med Calculators
 *
 * Fired when the plugin is uninstalled.
 *
 * @package MedCalculators
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'med_calc_version' );
delete_option( 'med_calc_settings' );

// Delete any transients
delete_transient( 'med_calc_cache' );

// Clean up any user meta if stored
// delete_metadata( 'user', 0, 'med_calc_user_data', '', true );

// For multisite, clean up each site
if ( is_multisite() ) {
    $sites = get_sites();
    
    foreach ( $sites as $site ) {
        switch_to_blog( $site->blog_id );
        
        delete_option( 'med_calc_version' );
        delete_option( 'med_calc_settings' );
        delete_transient( 'med_calc_cache' );
        
        restore_current_blog();
    }
}

