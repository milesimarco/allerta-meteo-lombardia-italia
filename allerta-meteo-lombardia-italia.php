<?php
/*
Plugin Name:  Allerta Meteo Lombardia Italia
Description:  Visualizzatore di allerte meteo collegato con il portale di Regione Lombardia
Version:      2.0.1
Author:       Marco Milesi
Contributors: Milmor
*/

add_action('admin_init', function() {

    $arraya_v = get_plugin_data ( __FILE__ );
    $new_version = $arraya_v['Version'];

    if ( version_compare($new_version,  get_option('amli_version_number') ) == 1 ) {
        update_option( 'amli_version_number', $new_version );
    }

    // --- MANUTENZIONE CRON (Self-Healing) ---
    $freq = get_option( 'amli_cron_frequency' );
    $valid_schedules = array('hourly', 'twicedaily', 'daily', 'manual');

    // 2. Allineamento tra Opzione e Evento Cron reale
    if ( $freq && $freq !== 'manual' ) {
        $is_scheduled = wp_next_scheduled( 'amli_cron_event' );
        $current_schedule = wp_get_schedule( 'amli_cron_event' );
        
        // Se non è schedulato O è schedulato con una frequenza diversa da quella scelta
        if ( !$is_scheduled || $current_schedule !== $freq ) {
            wp_clear_scheduled_hook( 'amli_cron_event' );
            wp_schedule_event( time(), $freq, 'amli_cron_event' );
        }
    } elseif ( $freq === 'manual' ) {
        // Se è manuale, rimuovi eventuali cron attivi
        if ( wp_next_scheduled( 'amli_cron_event' ) ) {
            wp_clear_scheduled_hook( 'amli_cron_event' );
        }
    }

});

// Hook per l'evento Cron
add_action( 'amli_cron_event', 'amli_cron_exec' );
function amli_cron_exec() {
    amli_scrape( true );
}

// Gestione attivazione/disattivazione Cron al cambio opzione
add_action( 'update_option_amli_cron_frequency', function( $old_value, $value ) {
    wp_clear_scheduled_hook( 'amli_cron_event' );
    if ( $value && $value !== 'manual' ) {
         if ( ! wp_next_scheduled( 'amli_cron_event' ) ) {
            wp_schedule_event( time(), $value, 'amli_cron_event' );
        }
    }
}, 10, 2 );

// Pulizia allo spegnimento
register_deactivation_hook( __FILE__, 'amli_deactivate' );
function amli_deactivate() {
    wp_clear_scheduled_hook( 'amli_cron_event' );
}

// Load Classes
require_once plugin_dir_path(__FILE__) . 'classes/class-amli-zones.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-amli-scraper.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-amli-admin.php';

function amli_scrape( $force = false ) {
    $scraper = new AMLI_Scraper();
    $scraper->scrape_all( $force );
}

/**
 * Funzione legacy per compatibilità
 * @deprecated Usa AMLI_Zone_Manager::get_zone_meteo
 */
function amli_get_paesi( $zona_id = 0 ) {
    return AMLI_Zone_Manager::get_zone_meteo( $zona_id );
}

function amli_get_alert_display( $int ){
    switch( $int ) {
        case 1:
            return array ( 'Ordinaria ⚠', '#fff3cd', '⚠' );  // Light yellow background
        case 2:
            return array ( 'Moderata ⚠⚠', '#ffe5d0', '⚠⚠' );  // Light orange background
        case 3:
            return array ( 'Elevata ⚠⚠⚠', '#f8d7da', '⚠⚠⚠' );  // Light red background
        default:
            return array ( 'Assente', '#d4edda' );  // Light green background
    }
}

// Initialize admin page
if (is_admin()) {
    new AMLI_Admin();
}

// Add links to plugin listing
add_filter( 'plugin_row_meta', 'amli_plugin_meta_links', 10, 2 );
function amli_plugin_meta_links( $links, $file ) {
    $plugin_file = plugin_basename( __FILE__ );
    if ( $file == $plugin_file ) {
        $row_meta = array(
            'docs'    => '<a href="' . esc_url( 'https://wordpress.org/plugins/allerta-meteo-lombardia-italia/' ) . '" target="_blank" aria-label="' . esc_attr__( 'Plugin Page', 'amli' ) . '">' . esc_html__( 'Pagina Plugin', 'amli' ) . '</a>',
            'author'  => '<a href="' . esc_url( 'https://profiles.wordpress.org/milmor/' ) . '" target="_blank" aria-label="' . esc_attr__( 'Developer', 'amli' ) . '">' . esc_html__( 'www.marcomilesi.com', 'amli' ) . '</a>',
        );
        $links = array_merge( $links, $row_meta );
    }
    return $links;
}