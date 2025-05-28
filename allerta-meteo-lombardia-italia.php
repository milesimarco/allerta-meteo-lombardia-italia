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

});

function amli_short( $atts ) {
    // Create new instance of shortcode class
    require_once 'class-shortcode.php';
    $shortcode = new AMLI_Shortcode();
    return $shortcode->render_shortcode($atts);
}
add_shortcode('amli', 'amli_short');

function amli_scrape( $force = false ) {

    $old_timestamp = get_option( 'amli_last_update' );

    if ( !$force ) {
        if ( $old_timestamp && ( $old_timestamp > ( current_time( 'timestamp' )-20*60) ) ) {
            return;
        }
    }

    $url = 'https://www.allertalom.regione.lombardia.it/lista-zone';
    
    $risks_id_array = array(
        'temporali' => 7,
        'vento' => 8,
        'idrogeologico' => 9,
        'idraulico' => 10
    );

    $rischi_zone = array();

    foreach ( $risks_id_array as $key => $risk ) {
        $request_url = add_query_arg( 'cdTipologiaGis', $risk, $url );

        // Detect local environment
        $site_url = get_site_url();
        $is_local = (
            strpos($site_url, 'localhost') !== false ||
            strpos($site_url, '127.0.0.1') !== false ||
            strpos($site_url, '.local') !== false
        );

        $args = array( 'timeout' => 15 );
        if ( $is_local ) {
            $args['sslverify'] = false;
        }
        $args['sslverify'] = false;

        $response = wp_remote_get( $request_url, $args );
        if ( is_wp_error( $response ) ) {
            continue;
        }
        $response_xml_data = wp_remote_retrieve_body( $response );
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response_xml_data);
        if (!$xml) {
            continue;
        }
        foreach($xml->item as $item) {
            $codiceZonaOmogenea = (string)$item->codiceZonaOmogenea;
            $codiceZonaOmogenea = str_replace( 'IM-', '', $codiceZonaOmogenea );
            if ( !isset( $rischi_zone[ $codiceZonaOmogenea ] ) ) {
                $rischi_zone[ $codiceZonaOmogenea ] = array();
            }
            if ( !isset( $rischi_zone[ $codiceZonaOmogenea ][ $key ] ) ) {
                $rischi_zone[ $codiceZonaOmogenea ][ $key ] = (string)$item->cdLivello;
            }
        }
    }

    /*
    echo '<pre>';
    print_r( $rischi_zone );
    echo '</pre>';
    die();
    */
    foreach( $rischi_zone as $key => $risk ) {
        $id = $key;
        if ( get_option( 'amli_'.$id ) != $risk ) {
            update_option( 'amli_'.$id, $risk);
            do_action( 'amli_weather_change', $id, $risk );
        }
    }
    update_option( 'amli_last_update', current_time( 'timestamp' ) );

    return;
}

function amli_get_paesi( $zona_id = 0 ) {
    $zone = array(
        array( '01', 'Valchiavenna' ),
        array( '02', 'Media-Bassa Valtellina' ),
        array( '03', 'Alta Valtellina' ),
        array( '04', 'Laghi e Prealpi Varesine' ),
        array( '05', 'Lario e Prealpi Occidentali' ),
        array( '06', 'Orobie Bergamasche' ),
        array( '07', 'Valcamonica' ),
        array( '08', 'Laghi e Prealpi Orientali' ),
        array( '09', 'Nodo Idraulico di Milano' ),
        array( '10', 'Pianura Centrale' ),
        array( '11', 'Alta Pianura Orientale' ),
        array( '12', 'Bassa Pianura Occidentale' ),
        array( '13', 'Bassa Pianura Centro-Occidentale' ),
        array( '14', 'Bassa Pianura Centro-Orientale' ),
        array( '15', 'Bassa Pianura Orientale' ),
        array( '16', 'Appennino Pavese' )
    );

    if ( $zona_id ) {
        $zona_id = ((int)$zona_id)-1;
        return isset($zone[$zona_id]) ? $zone[$zona_id][1] : '';
    } else {
        return $zone;
    }
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

// require the admin class
require_once plugin_dir_path(__FILE__) . 'class-amli-admin.php';

// Initialize admin page
if (is_admin()) {
    new AMLI_Admin();
}
