<?php
/*
Plugin Name:  Allerta Meteo Lombardia Italia
Description:  Visualizzatore di allerte meteo collegato con il portale di Regione Lombardia
Version:      1.5.5
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
    ob_start();
    require 'class-shortcode.php';
    return ob_get_clean();
}
add_shortcode('amli', 'amli_short');

function amli_scrape() {

    $old_timestamp = get_option( 'amli_last_update' );

    if ( $old_timestamp && ( $old_timestamp > ( current_time( 'timestamp' )-20*60) ) ) {
        return;
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
        if (($response_xml_data = file_get_contents( add_query_arg( 'cdTipologiaGis', $risk, $url ) ) ) === false ){
            echo "Error fetching XML data\n";
        } else {
           libxml_use_internal_errors(true);
           $xml = simplexml_load_string($response_xml_data);
           if (!$xml) {
               echo "Error loading XML data\n";
               foreach(libxml_get_errors() as $error) {
                   echo "\t", $error->message;
               }
           } else {
            foreach($xml->item as $item)
            {
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
        array( '01', 'VALCHIAVENNA' ),
        array( '02', 'MEDIA-BASSA VALTELLINA' ),
        array( '03', 'ALTA VALTELLINA' ),
        array( '04', 'LAGHI E PREALPI VARESINE' ),
        array( '05', 'LARIO E PREALPI OCCIDENTALI' ),
        array( '06', 'OROBIE BERGAMASCHE' ),
        array( '07', 'VALCAMONICA' ),
        array( '08', 'LAGHI E PREALPI ORIENTALI' ),
        array( '09', 'NODO IDRAULICO DI MILANO' ),
        array( '10', 'PIANURA CENTRALE' ),
        array( '11', 'ALTA PIANURA ORIENTALE' ),
        array( '12', 'BASSA PIANURA OCCIDENTALE' ),
        array( '13', 'BASSA PIANURA ORIENTALE' ),
        array( '14', 'APPENNINO PAVESE' )
    );

    if ( $zona_id ) {
        $zona_id = ((int)$zona_id)-1;
        return $zone[$zona_id][1];
    } else {
        return $zone;
    }
}

function amli_get_alert_display( $int ){
    switch( $int ) {
        case 1:
            return array ( 'Ordinaria ⚠', '#ffff00', '⚠' );
        case 2:
            return array ( 'Moderata ⚠⚠', '#ff9900', '⚠⚠' );
        case 3:
            return array ( 'Elevata ⚠⚠⚠', '#ff0000', '⚠⚠⚠' );
        default:
            return array ( 'Assente', '#66ff00' );
    }
}

?>
