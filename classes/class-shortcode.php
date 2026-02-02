<?php

class AMLI_Shortcode {
    private $atts;
    private $results;
    private $alerts;

    public function __construct() {
        add_shortcode('amli', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $raw_atts = shortcode_atts(array(
            'id' => '',          // Meteo ID (legacy or explicit)
            'idrometeo' => '',       // Explicit Meteo ID alias
            'neve' => '',        // Neve ID
            'valanghe' => '',    // Valanghe ID
            'incendi' => '',     // Incendi ID
            'height' => '',
            'width' => '100%',
            'title' => '',
            'previsione' => '',
            'previsioni' => ''   // Alias requested
        ), $atts);

        // Normalize Previsione
        $show_forecast = $raw_atts['previsione'] || $raw_atts['previsioni'];

        // Normalize Meteo ID: 'idrometeo' attribute takes precedence or fills 'id' if empty
        if ( !empty($raw_atts['idrometeo']) ) {
            $raw_atts['id'] = $raw_atts['idrometeo'];
        }

        // Build list of tables to render
        $tables_request = array();

        // CASE 2: Mixed Mode (and only mode now)
        // Default Meteo (if id is present)
        if ( !empty($raw_atts['id']) ) {
            $tables_request[] = array('type' => 'idrometeo', 'id' => $raw_atts['id']);
        }
        if ( !empty($raw_atts['neve']) ) {
            $tables_request[] = array('type' => 'neve', 'id' => $raw_atts['neve']);
        }
        if ( !empty($raw_atts['valanghe']) ) {
            $tables_request[] = array('type' => 'valanghe', 'id' => $raw_atts['valanghe']);
        }
        if ( !empty($raw_atts['incendi']) ) {
            $tables_request[] = array('type' => 'incendi', 'id' => $raw_atts['incendi']);
        }

        if ( empty($tables_request) ) {
            // Fallback for legacy calls like [amli id="1"] without type (defaults to meteo)
            if ( !empty($raw_atts['id']) ) {
                 // Already handled above? Yes.
                 // If we are here, id was empty or 0.
                 return 'error: no zones specified';
            }
            return 'error: ID not provided';
        }

        if (function_exists('amli_scrape')) {
            amli_scrape();
        }

        $all_alerts_rows = array();
        $errors = '';

        foreach( $tables_request as $req ) {
            // Setup internal state for this specific table fetch
            $this->atts = array(
                'id' => $req['id'],
                'type' => $req['type'],
                'previsione' => $show_forecast,
                'title' => $raw_atts['title'] // Just for context if needed
            );

            // Fetch Data
            $option_key = ($this->atts['type'] === 'idrometeo') 
                ? 'amli_' . $this->atts['id'] 
                : 'amli_' . $this->atts['type'] . '_' . $this->atts['id'];

            $this->results = get_option($option_key);
            
            if ($this->results === false) {
                 $errors .= '<div style="color:red; font-size:0.8em; border:1px solid red; padding:5px;">Dati non disp. (' . esc_html($this->atts['type'] . ' ' . $this->atts['id']) . ')</div>';
                 continue;
            }

            if ( $this->atts['type'] === 'idrometeo' ) {
                if ( !is_array($this->results) ) {
                    $errors .= '<div style="color:red;">Errore formato dati meteo.</div>';
                    continue;
                }
                $this->process_alerts_idrometeo();
            } else {
                $this->process_alerts_single();
            }
            
            // Merge into master list
            if (is_array($this->alerts)) {
                $all_alerts_rows = array_merge($all_alerts_rows, $this->alerts);
            }
        }

        // Final Render
        if (empty($all_alerts_rows) && !empty($errors)) {
            return $errors;
        }

        return $this->generate_merged_table($all_alerts_rows, $raw_atts);
    }

    private function generate_merged_table($alerts, $atts) {
        // Prepare global styles/vars
        $width = $atts['width'];
        $height = $atts['height'];
        $title = $atts['title'];
        
        // Define if header is needed: ONLY if title is present (as per user request "non deve comparire dicitura zona")
        // Exception: If we have legacy single zone, logic might want a default.
        // But the user was explicit: "quando uno shortcode mostra più rischi...".
        // Let's stick to: Title provided -> Header. No Title -> No Header (assuming mixed context). 
        // Note: For backward compatibility, single [amli id=1] calls usually want a header.
        
        // Count request types?
        $active_risks = array();
        
        // Handle ID parameter (Context-aware based on 'type' attribute removed, now implicit)
        if ( !empty($atts['id']) ) {
            $active_risks['idrometeo'] = $atts['id'];
        }

        // Handle explicit risk attributes (overrides or additions)
        if ( !empty($atts['neve']) ) $active_risks['neve'] = $atts['neve'];
        if ( !empty($atts['valanghe']) ) $active_risks['valanghe'] = $atts['valanghe'];
        if ( !empty($atts['incendi']) ) $active_risks['incendi'] = $atts['incendi'];

        // Determine Header Text
        $header_text = '';
        if ( !empty($title) ) {
            $header_text = $title;
        } elseif ( count($active_risks) === 1 ) {
            // Single risk active -> Show Zone Name
            $risk_type = key($active_risks);
            $risk_id = current($active_risks);
            $zone_name = '';
            $zone_code = '';

            switch($risk_type) {
                case 'idrometeo': 
                    $zone_name = \AMLI_Zone_Manager::get_zone_meteo($risk_id); 
                    $zone_code = 'IM-' . str_pad($risk_id, 2, '0', STR_PAD_LEFT);
                    break;
                case 'neve': 
                    $zone_name = \AMLI_Zone_Manager::get_zone_neve($risk_id); 
                    $zone_code = 'NV-' . str_pad($risk_id, 2, '0', STR_PAD_LEFT);
                    break;
                case 'valanghe': 
                    $zone_name = \AMLI_Zone_Manager::get_zone_valanghe($risk_id); 
                    $zone_code = $risk_id;
                    break;
                case 'incendi': 
                    $zone_name = \AMLI_Zone_Manager::get_zone_incendi($risk_id);
                    $zone_code = str_pad($risk_id, 2, '0', STR_PAD_LEFT);
                    break;
            }

            if ( $zone_name ) {
                $header_text = $zone_name . ' <span style="font-weight:normal">(Zona ' . $zone_code . ')</span>';
            }
        }
        
        ob_start();
        ?>
        <style>
            .amli-table {
                border-collapse: collapse;
                width: <?php echo esc_attr($width); ?>;
                <?php if ($height): ?>
                height: <?php echo esc_attr($height); ?>;
                <?php endif; ?>
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                margin: 1em 0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                background: #fff;
            }
            .amli-table th, .amli-table td {
                padding: 12px;
                border: 1px solid #ddd;
            }
            .amli-table thead th {
                background-color: #f8f9fa;
                font-weight: 600;
                text-align: left;
                color: #333;
            }
            .amli-table tbody th {
                font-weight: normal;
                text-align: left;
                color: #555;
            }
            .amli-table .amli-footer {
                font-size: 0.85em;
                color: #666;
                padding: 8px 12px;
                background: #fafafa;
            }
            .amli-table .amli-footer a {
                color: #0073aa;
                text-decoration: none;
            }
            .amli-table .amli-footer a:hover {
                text-decoration: underline;
            }
        </style>
        <table class="amli-table">
            <thead>
                <?php if ($header_text): ?>
                <tr>
                    <th colspan="2">
                        <?php echo wp_kses_post($header_text); ?> 
                    </th>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Rischio</th>
                    <th style="text-align: center;">Criticità</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $keys = array_keys($alerts);
                $count = count($keys);
                for ($i = 0; $i < $count; $i++) {
                    $type = $keys[$i];
                    $data = $alerts[$type];
                    list($value, $color) = $data;
                    $label = $this->get_alert_label($type);
                    
                    // Check for forecast in next item
                    $next_is_forecast = false;
                    if ( isset($keys[$i+1]) && $keys[$i+1] === $type . '_forecast' ) {
                         $next_is_forecast = true;
                    }

                    if ( $next_is_forecast ) {
                        // Main Row with Rowspan
                        ?>
                        <tr>
                            <th scope="row" width="50%" rowspan="2"><?php echo esc_html($label); ?></th>
                            <td bgcolor="<?php echo esc_attr($color); ?>" style="text-align: center; font-weight: bold; color: #333;">
                                <?php echo wp_kses_post($value); ?>
                            </td>
                        </tr>
                        <?php
                        // Forecast Row
                        $i++; // Skip next iteration
                        $f_type = $keys[$i];
                        $f_data = $alerts[$f_type];
                        list($f_value, $f_color) = $f_data;
                        ?>
                        <tr>
                            <td bgcolor="<?php echo esc_attr($f_color); ?>" style="text-align: center; font-weight: bold; color: #333;">
                                <?php echo wp_kses_post($f_value); ?>
                            </td>
                        </tr>
                        <?php
                    } else {
                        // Standard Row
                        ?>
                        <tr>
                            <th scope="row" width="50%"><?php echo esc_html($label); ?></th>
                            <td bgcolor="<?php echo esc_attr($color); ?>" style="text-align: center; font-weight: bold; color: #333;">
                                <?php echo wp_kses_post($value); ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                <tr>
                    <td colspan="2" class="amli-footer">
                        Ultimo aggiornamento: <?php echo date('H:i', get_option('amli_last_update')); ?> - 
                        Dati a cura di <a href="https://www.allertalom.regione.lombardia.it/" title="Allerte meteo Regione Lombardia">Regione Lombardia</a>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    private function process_alerts_idrometeo() {
        $this->alerts = array(
            'idrogeologico' => $this->get_safe_alert_display('idrogeologico'),
            'idraulico' => $this->get_safe_alert_display('idraulico'),
            'temporali' => $this->get_safe_alert_display('temporali'),
            'vento' => $this->get_safe_alert_display('vento')
        );
    }

    private function process_alerts_single() {
        $level = (int)$this->results;
        $this->alerts = array();
        
        $current_display = amli_get_alert_display($level);
        $this->alerts[$this->atts['type']] = $current_display;

        // Feature Previsione
        if ( !empty($this->atts['previsione']) ) {
            $forecast_key = 'amli_' . $this->atts['type'] . '_' . $this->atts['id'] . '_forecast';
            $forecast = get_option($forecast_key);

            if ( $forecast && is_array($forecast) ) {
                $f_ts = $forecast['ts'];
                $f_lvl = $forecast['lvl'];
                
                // --- TREND LOGIC ---
                // Se la previsione differisce, aggiungi indicazione di trend al livello corrente
                if ( $f_lvl > $level ) {
                    // Worsening (es. Assente -> Ordinaria)
                    $this->alerts[$this->atts['type']][0] .= ' <small>(in peggioramento)</small>';
                } elseif ( $f_lvl < $level && $level > 0 ) {
                    // Improving (es. Ordinaria -> Assente). Da 0 (Assente) non si migliora.
                    $this->alerts[$this->atts['type']][0] .= ' <small>(in miglioramento)</small>';
                }
                // -------------------

                $time_str = function_exists('date_i18n') ? date_i18n('H:i', $f_ts) : date('H:i', $f_ts);
                $f_display = amli_get_alert_display($f_lvl);

                // Evita duplicati se il testo è identico (es. 0 e -1 sono entrambi Assenti)
                if ( $f_display[0] === $current_display[0] ) {
                    return;
                }
                
                // Formatta forecast su nuova riga
                // "ORDINARIA ⚠ dalle 14"
                $forecast_text = '<span style="text-transform:uppercase;">' . esc_html($f_display[0]) . '</span>';
                $forecast_text .= ' <small> dalle ' . esc_html($time_str) . '</small>';
                
                // Aggiungi come alert separato. 
                // Usiamo suffisso _forecast che verrà pulito in get_alert_label
                $this->alerts[$this->atts['type'] . '_forecast'] = array($forecast_text, $f_display[1]);
            }
        }
    }

    private function get_safe_alert_display($key) {
        if (isset($this->results[$key])) {
            $res = amli_get_alert_display($this->results[$key]);
            return $res;
        }
        return array('N/D', '#ccc');
    }

// End of class
    private function get_alert_label($type) {
        $clean_type = str_replace('_forecast', '', $type);
        $labels = array(
            'idrogeologico' => 'Idrogeologico',
            'idraulico' => 'Idraulico',
            'temporali' => 'Temporali Forti',
            'vento' => 'Vento Forte',
            'neve' => 'Neve',
            'valanghe' => 'Valanghe',
            'incendi' => 'Incendi Boschivi'
        );
        return isset($labels[$clean_type]) ? $labels[$clean_type] : ucfirst($clean_type);
    }
}

// Initialize the shortcode
new AMLI_Shortcode();
