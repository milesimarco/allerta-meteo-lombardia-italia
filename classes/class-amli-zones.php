<?php

class AMLI_Zone_Manager {

    public static function get_zone_meteo( $zona_id = 0 ) {
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

    public static function get_zone_neve( $zona_id = 0 ) {
        $zone = array(
            '01' => 'Valchiavenna',
            '02' => 'Media-bassa Valtellina',
            '03' => 'Alta Valtellina',
            '04' => 'Prealpi varesine',
            '05' => 'Prealpi comasche-lecchesi',
            '06' => 'Prealpi bergamasche',
            '07' => 'Valcamonica',
            '08' => 'Prealpi bresciane',
            '09' => 'Alta pianura varesina',
            '10' => 'Brianza',
            '11' => 'Area Milanese',
            '12' => 'Alta pianura bergamasca',
            '13' => 'Pianura centrale',
            '14' => 'Alta pianura bresciana',
            '15' => 'Pianura pavese',
            '16' => 'Bassa pianura centrale',
            '17' => 'Bassa pianura bresciana-cremonese',
            '18' => 'Pianura mantovana',
            '19' => 'Fascia collinare Oltrepo\' pavese',
            '20' => 'Appennino pavese'
        );
    
        if ( $zona_id ) {
            // Rimuove NV- se presente per normalizzare l'input
            $code = str_replace('NV-', '', $zona_id);
            return isset($zone[$code]) ? $zone[$code] : '';
        } else {
            return $zone;
        }
    }

    public static function get_zone_valanghe( $zona_id = 0 ) {
        $zone = array(
            '15' => 'Adamello',
            '57' => 'Appennino Pavese',
            '60' => 'Orobie Bergamasche',
            '59' => 'Orobie Valtellinesi',
            '61' => 'Prealpi Bergamasche',
            '16' => 'Prealpi Bresciane',
            '58' => 'Prealpi Comasche',
            '56' => 'Prealpi Lecchesi',
            '11' => 'Prealpi Varesine',
            '13' => 'Retiche Centrali',
            '12' => 'Retiche Occidentali',
            '14' => 'Retiche Orientali'
        );
    
        if ( $zona_id ) {
            return isset($zone[$zona_id]) ? $zone[$zona_id] : '';
        } else {
            return $zone;
        }
    }

    public static function get_zone_incendi( $zona_id = 0 ) {
        $zone = array(
            '02' => 'Alpi Centrali',
            '16' => 'Alta Pianura Orientale',
            '03' => 'Alta Valtellina',
            '06' => 'Alto Brembo',
            '07' => 'Alto Serio-Scalve',
            '17' => 'Bassa Pianura Orientale',
            '13' => 'Garda',
            '05' => 'Lario',
            '12' => 'Mella-Chiese',
            '18' => 'Oltrepo\' Pavese',
            '09' => 'Pedemontana Occidentale',
            '15' => 'Pianura Centrale',
            '14' => 'Pianura Occidentale',
            '10' => 'Prealpi Bergamasche Occidentali',
            '11' => 'Prealpi Bergamasche Orientali',
            '08' => 'Valcamonica',
            '01' => 'Valchiavenna',
            '04' => 'Verbano'
        );
    
        if ( $zona_id ) {
            // Rimuove IB- se presente per normalizzare l'input
            $code = str_replace('IB-', '', $zona_id);
            return isset($zone[$code]) ? $zone[$code] : '';
        } else {
            return $zone;
        }
    }

}