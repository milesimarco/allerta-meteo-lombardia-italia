<?php

class AMLI_Scraper {
    
    // Configura e interroga il servizio remoto
    private $base_url = 'https://www.allertalom.regione.lombardia.it/lista-zone';
    
    // Mappatura ID GIS per ogni tipologia
    // Meteo è composito (7,8,9,10)
    // Neve=2, Valanghe=1, Incendi=3
    
    public function scrape_all( $force = false ) {
        
        // Verifica cache globale (basata sull'opzione principale amli_last_update)
        $old_timestamp = get_option( 'amli_last_update' );
        $freq = get_option( 'amli_cron_frequency', 'hourly' );
        
        $seconds = 3600;
        switch($freq) {
            case 'manual': $seconds = 1200; break;
            case 'hourly': $seconds = 3600; break;
            case 'twicedaily': $seconds = 43200; break;
            case 'daily': $seconds = 86400; break;
        }

        // Se non forziamo e la cache è valida, usciamo
        if ( !$force && $old_timestamp && ( $old_timestamp > ( current_time( 'timestamp' ) - $seconds ) ) ) {
            return;
        }

        // Eseguiamo tutti gli scraping
        $this->scrape_meteo();
        $this->scrape_single_risk('neve', 2, 'NV-');     // Prefisso opzioni: amli_neve_ID
        $this->scrape_single_risk('valanghe', 1, 'VA-'); // Prefisso opzioni: amli_valanghe_ID
        $this->scrape_single_risk('incendi', 3, 'IB-');     // IB- mantenuto per Incendi

        // Aggiorna timestamp globale
        update_option( 'amli_last_update', current_time( 'timestamp' ) );
    }

    /**
     * Scraper Legacy per Meteo (Temporali, Vento, Idrogeologico, Idraulico)
     * Salva in amli_{ZONE_ID} (es. amli_01) - Array associativo
     */
    public function scrape_meteo() {
        $risks_id_array = array(
            'temporali' => 7,
            'vento' => 8,
            'idrogeologico' => 9,
            'idraulico' => 10
        );

        $data_buffer = array();

        foreach ( $risks_id_array as $key => $risk_id ) {
            $xml = $this->fetch_xml( $risk_id );
            if ( !$xml ) continue;

            foreach($xml->item as $item) {
                // Per meteo rimuoviamo "IM-"
                $codice = (string)$item->codiceZonaOmogenea;
                $codice = str_replace( 'IM-', '', $codice );
                
                if ( !isset( $data_buffer[ $codice ] ) ) {
                    $data_buffer[ $codice ] = array();
                }
                $data_buffer[ $codice ][ $key ] = (string)$item->cdLivello;
            }
        }

        // Salva opzioni
        foreach( $data_buffer as $id => $risk_data ) {
            if ( get_option( 'amli_'.$id ) != $risk_data ) {
                update_option( 'amli_'.$id, $risk_data );
                do_action( 'amli_weather_change', $id, $risk_data );
            }
        }
    }

    /**
     * Scraper Generico per Rischi Singoli (Neve, Valanghe, Incendi)
     * Salva in amli_{TYPE}_{ZONE_ID} - Valore singolo (stringa/int del livello corrente)
     * Salva forecast in amli_{TYPE}_{ZONE_ID}_forecast
     */
    private function scrape_single_risk( $type, $gis_id, $zone_prefix_strip = '' ) {
        $xml = $this->fetch_xml( $gis_id );
        if ( !$xml ) return;

        $option_prefix = 'amli_' . $type . '_';
        $zones_timeline = array();

        // 1. Raccogli e organizza i dati per zona
        foreach($xml->item as $item) {
            $codice = (string)$item->codiceZonaOmogenea;
            if ( $zone_prefix_strip ) {
                $codice = str_replace( $zone_prefix_strip, '', $codice );
            }
            
            $livello = (int)$item->cdLivello;
            // Usa dtPrevisione se esiste, default a now
            $ts = isset($item->dtPrevisione) ? (float)$item->dtPrevisione / 1000 : time();

            $zones_timeline[$codice][] = array(
                'ts' => $ts, 
                'lvl' => $livello
            );
        }

        $now = time();

        // 2. Elabora ogni zona
        foreach ( $zones_timeline as $codice => $timeline ) {
            // Ordina per data crescente
            usort($timeline, function($a, $b) {
                return $a['ts'] - $b['ts'];
            });

            $current_level = 0;
            $future_items = array();
            
            // Trova l'ultimo item valido (ts <= now + buffer)
            $last_valid_idx = -1;
            $buffer = 1800; // 30 min buffer

            foreach ($timeline as $idx => $slot) {
                if ( $slot['ts'] <= ($now + $buffer) ) {
                    $last_valid_idx = $idx;
                } else {
                    break;
                }
            }

            if ( $last_valid_idx >= 0 ) {
                $current_level = $timeline[$last_valid_idx]['lvl'];
                $future_items = array_slice($timeline, $last_valid_idx + 1);
            } elseif ( !empty($timeline) ) {
                // Se non c'è storico, prendi il primo futuro
                $current_level = $timeline[0]['lvl'];
                $future_items = array_slice($timeline, 1);
            }

            // Salva Livello Corrente
            $opt_name = $option_prefix . $codice;
            // Use strict comparison or default value to ensure initial 0 is saved
            if ( get_option( $opt_name, -1 ) != $current_level ) {
                update_option( $opt_name, $current_level );
            }
            

            // Cerca prossimo cambio (Forecast)
            $forecast_data = false;
            foreach ($future_items as $f) {
                if ( $f['lvl'] != $current_level ) {
                    $forecast_data = array(
                        'ts' => $f['ts'],
                        'lvl' => $f['lvl']
                    );
                    break;
                }
            }
            
            update_option( $opt_name . '_forecast', $forecast_data );
        }
    }

    private function fetch_xml( $gis_id ) {
        $request_url = add_query_arg( 'cdTipologiaGis', $gis_id, $this->base_url );
        
        // Environment check per SSL locale (copiato dal vecchio file)
        $site_url = get_site_url();
        $is_local = ( strpos($site_url, 'localhost') !== false || strpos($site_url, '127.0.0.1') !== false || strpos($site_url, '.local') !== false );
        
        $args = array( 'timeout' => 15, 'sslverify' => false ); // Sempre false come da codice originale

        $response = wp_remote_get( $request_url, $args );
        
        if ( is_wp_error( $response ) ) return false;
        
        $body = wp_remote_retrieve_body( $response );
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string( $body );
        
        return $xml ? $xml : false;
    }

}
