<?php

class AMLI_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'handle_refresh_action'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_amli_preview_shortcode', array($this, 'handle_ajax_preview'));
    }

    public function handle_ajax_preview() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $atts = array();
        
        // Accept both id and idrometeo (idrometeo preferred for clarity in builder)
        if (!empty($_POST['idrometeo'])) $atts['idrometeo'] = sanitize_text_field($_POST['idrometeo']);
        if (!empty($_POST['id'])) $atts['id'] = sanitize_text_field($_POST['id']);
        
        if (!empty($_POST['neve'])) $atts['neve'] = sanitize_text_field($_POST['neve']);
        if (!empty($_POST['valanghe'])) $atts['valanghe'] = sanitize_text_field($_POST['valanghe']);
        if (!empty($_POST['incendi'])) $atts['incendi'] = sanitize_text_field($_POST['incendi']);
        
        if (!empty($_POST['width'])) $atts['width'] = sanitize_text_field($_POST['width']);
        if (!empty($_POST['height'])) $atts['height'] = sanitize_text_field($_POST['height']);
        if (!empty($_POST['title'])) $atts['title'] = sanitize_text_field($_POST['title']);

        if (isset($_POST['previsioni']) && $_POST['previsioni'] === 'true') {
            $atts['previsioni'] = '1';
        }

        // Build shortcode string to leverage existing logic
        $shortcode = '[amli';
        foreach($atts as $k => $v) {
            $shortcode .= ' ' . $k . '="' . esc_attr($v) . '"'; 
        }
        $shortcode .= ']';

        echo do_shortcode($shortcode);
        wp_die();
    }

    public function register_settings() {
        register_setting('amli_options_group', 'amli_cron_frequency');
    }

    public function handle_refresh_action() {
        if (isset($_GET['amli_refresh']) && current_user_can('manage_options')) {
            if (function_exists('amli_scrape')) {
                amli_scrape( $force = true );
            }
            wp_safe_redirect(remove_query_arg('amli_refresh'));
            exit;
        }
    }

    public function register_admin_menu() {
        add_menu_page(
            'Allerta Meteo',
            'Allerta Meteo',
            'manage_options',
            'amli-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-warning',
            80
        );

        // Rename first submenu
        add_submenu_page(
            'amli-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'amli-dashboard',
            array($this, 'dashboard_page')
        );

        // Add Shortcode Builder
        add_submenu_page(
            'amli-dashboard',
            'Shortcode Builder',
            'Shortcode Builder',
            'manage_options',
            'amli-shortcode-builder',
            array($this, 'shortcode_builder_page')
        );

        // Add settings submenu
        add_submenu_page(
            'amli-dashboard',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'amli-settings',
            array($this, 'settings_page')
        );
    }

    public function shortcode_builder_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Fetch Zones
        $zones_meteo = \AMLI_Zone_Manager::get_zone_meteo();
        $zones_neve = \AMLI_Zone_Manager::get_zone_neve();
        $zones_valanghe = \AMLI_Zone_Manager::get_zone_valanghe();
        $zones_incendi = \AMLI_Zone_Manager::get_zone_incendi();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Shortcode Builder & Anteprima</h1>
            <p>Componi il tuo shortcode e visualizza l'anteprima in tempo reale.</p>

            <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">
                
                <!-- Controls Column -->
                <div style="width:300px; flex:0 0 300px;">
                    
                    <div class="postbox">
                        <h2 class="hndle ui-sortable-handle" style="padding:10px 15px; margin:0; border-bottom:1px solid #eee;"><span>1. Seleziona Zone</span></h2>
                        <div class="inside" style="padding:15px;">
                            
                            <!-- Meteo -->
                            <div style="margin-bottom:15px;">
                                <label style="font-weight:600; display:block; margin-bottom:5px;">Zona Meteo</label>
                                <select id="amli_sb_meteo" class="amli-input" style="width:100%;">
                                    <option value="">-- Nessuna --</option>
                                    <?php foreach($zones_meteo as $z): ?>
                                        <option value="<?php echo esc_attr($z[0]); ?>"><?php echo esc_html($z[0] . ' - ' . $z[1]); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Neve -->
                            <div style="margin-bottom:15px;">
                                <label style="font-weight:600; display:block; margin-bottom:5px;">Zona Neve</label>
                                <select id="amli_sb_neve" class="amli-input" style="width:100%;">
                                    <option value="">-- Nessuna --</option>
                                    <?php foreach($zones_neve as $id => $name): ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($id . ' - ' . $name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Valanghe -->
                            <div style="margin-bottom:15px;">
                                <label style="font-weight:600; display:block; margin-bottom:5px;">Zona Valanghe</label>
                                <select id="amli_sb_valanghe" class="amli-input" style="width:100%;">
                                    <option value="">-- Nessuna --</option>
                                    <?php foreach($zones_valanghe as $id => $name): ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($id . ' - ' . $name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                             <!-- Incendi -->
                             <div style="margin-bottom:15px;">
                                <label style="font-weight:600; display:block; margin-bottom:5px;">Zona Incendi</label>
                                <select id="amli_sb_incendi" class="amli-input" style="width:100%;">
                                    <option value="">-- Nessuna --</option>
                                    <?php foreach($zones_incendi as $id => $name): ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($id . ' - ' . $name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle ui-sortable-handle" style="padding:10px 15px; margin:0; border-bottom:1px solid #eee;"><span>2. Opzioni Visualizzazione</span></h2>
                        <div class="inside" style="padding:15px;">
                            
                            <!-- Title -->
                            <div style="margin-bottom:15px;">
                                <label style="font-weight:600; display:block; margin-bottom:2px;">Titolo Personalizzato (Opz)</label>
                                <input type="text" id="amli_sb_title" class="amli-input" style="width:100%;" placeholder="Es. Allerta Milano">
                                <p class="description" style="font-size:11px;">Sostituisce il nome della zona nell'intestazione.</p>
                            </div>

                            <!-- Width / Height -->
                            <div style="display:flex; gap:10px; margin-bottom:15px;">
                                <div style="flex:1;">
                                    <label style="font-weight:600; display:block; margin-bottom:2px;">Larghezza</label>
                                    <input type="text" id="amli_sb_width" class="amli-input" style="width:100%;" value="100%" placeholder="100% or 300px">
                                </div>
                                <div style="flex:1;">
                                    <label style="font-weight:600; display:block; margin-bottom:2px;">Altezza</label>
                                    <input type="text" id="amli_sb_height" class="amli-input" style="width:100%;" placeholder="auto">
                                </div>
                            </div>

                            <!-- Forecast -->
                            <div style="margin-bottom:5px;">
                                <label>
                                    <input type="checkbox" id="amli_sb_forecast" class="amli-input" value="1"> Mostra Previsioni
                                </label>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Result Column -->
                <div style="flex:1; min-width:400px;">
                    
                    <!-- Shortcode Output -->
                    <div style="background:#fff; border:1px solid #c3c4c7; box-shadow:0 1px 1px rgba(0,0,0,.04); padding:15px; margin-bottom:20px;">
                        <h3 style="margin-top:0; font-size:14px; text-transform:uppercase; color:#666;">Codice Generato</h3>
                        <div style="display:flex; gap:10px;">
                            <textarea id="amli_sb_output" readonly style="flex:1; height:50px; font-family:monospace; font-size:1.2em; background:#f6f7f7; color:#3c434a; border:1px solid #dcdcde;">[amli]</textarea>
                            <button class="button button-primary" onclick="amliCopyStartcode()" style="height:50px;">Copia</button>
                        </div>
                    </div>
                    
                    <!-- Live Preview -->
                    <div style="background:#fff; border:1px solid #c3c4c7; box-shadow:0 1px 1px rgba(0,0,0,.04);">
                        <div style="padding:10px 15px; border-bottom:1px solid #eee; background:#fcfcfc;">
                            <h3 style="margin:0; font-size:14px; display:inline-block;">Anteprima Live</h3>
                            <span id="amli_loading" style="display:none; float:right; color:#0073aa; font-size:12px;">Caricamento...</span>
                        </div>
                        <div id="amli_preview_canvas" style="padding:20px; min-height:200px; display:flex; align-items:center; justify-content:center; background:#f0f0f1;">
                            <em style="color:#999;">Seleziona una zona per vedere l'anteprima</em>
                        </div>
                    </div>

                </div>

            </div>

            <script>
            (function($){
                var typingTimer;
                var doneTypingInterval = 500;

                function getAttributes() {
                    return {
                        id: $('#amli_sb_meteo').val(),
                        neve: $('#amli_sb_neve').val(),
                        valanghe: $('#amli_sb_valanghe').val(),
                        incendi: $('#amli_sb_incendi').val(),
                        title: $('#amli_sb_title').val(),
                        width: $('#amli_sb_width').val(),
                        height: $('#amli_sb_height').val(),
                        previsioni: $('#amli_sb_forecast').is(':checked')
                    };
                }

                function updateShortcodeBox() {
                    var atts = getAttributes();
                    var parts = ['amli'];
                    
                    if (atts.id) parts.push('idrometeo="' + atts.id + '"'); // Use 'idrometeo' instead of 'id' for clarity
                    if (atts.neve) parts.push('neve="' + atts.neve + '"');
                    if (atts.valanghe) parts.push('valanghe="' + atts.valanghe + '"');
                    if (atts.incendi) parts.push('incendi="' + atts.incendi + '"');
                    
                    if (atts.title) parts.push('title="' + atts.title + '"');
                    if (atts.width && atts.width !== '100%') parts.push('width="' + atts.width + '"');
                    if (atts.height) parts.push('height="' + atts.height + '"');
                    if (atts.previsioni) parts.push('previsioni="1"');

                    var code = '[' + parts.join(' ') + ']';
                    $('#amli_sb_output').val(code);

                    return atts; // Pass to ajax
                }

                function fetchPreview() {
                    var atts = updateShortcodeBox();
                    
                    // Don't fetch if nothing selected
                    if (!atts.id && !atts.neve && !atts.valanghe && !atts.incendi) {
                        $('#amli_preview_canvas').html('<em style="color:#999;">Seleziona una zona...</em>');
                        return;
                    }

                    $('#amli_loading').show();
                    
                    var data = {
                        action: 'amli_preview_shortcode',
                        ...atts
                    };

                    // Map 'id' (which is meteo ID in js atts object) to 'idrometeo' param for backend consistency if desired
                    // though backend accepts 'id' OR 'idrometeo'. 
                    if (atts.id) data.idrometeo = atts.id; 
                    
                    // Convert boolean to string for PHP ease (though jQuery does this well usually)
                    data.previsioni = atts.previsioni ? 'true' : 'false';

                    $.post(ajaxurl, data, function(response) {
                        $('#amli_preview_canvas').html(response);
                        $('#amli_loading').hide();
                    });
                }

                // Bind events
                $('.amli-input').on('change', function() {
                    updateShortcodeBox(); // Update text immediately
                    fetchPreview();       // Update visual
                });

                // Debounce text inputs like Title/Width to avoid too many requests
                $('#amli_sb_title, #amli_sb_width, #amli_sb_height').on('keyup', function() {
                    clearTimeout(typingTimer);
                    updateShortcodeBox();
                    typingTimer = setTimeout(fetchPreview, doneTypingInterval);
                });

                // Initial run
                updateShortcodeBox();

                window.amliCopyStartcode = function() {
                    var copyText = document.getElementById("amli_sb_output");
                    copyText.select();
                    navigator.clipboard.writeText(copyText.value);
                    alert("Copiato negli appunti");
                }

            })(jQuery);
            </script>
        </div>
        <?php
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1>Impostazioni Allerta Meteo</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=amli-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">Generali</a>
                <a href="?page=amli-settings&tab=debug" class="nav-tab <?php echo $active_tab == 'debug' ? 'nav-tab-active' : ''; ?>">Debug</a>
            </h2>

            <?php
            if ($active_tab == 'debug') {
                $this->render_settings_debug();
            } else {
                $this->render_settings_general();
            }
            ?>
        </div>
        <?php
    }

    private function render_settings_debug() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE 'amli_%' ORDER BY option_name ASC");
        ?>
        <div style="margin-top: 20px;">
            <p>Elenco completo di tutte le opzioni salvate nel database relative a questo plugin.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Option Name</th>
                        <th>Option Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="2">Nessuna opzione trovata.</td></tr>
                    <?php else: ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><strong><?php echo esc_html($row->option_name); ?></strong></td>
                                <td>
                                    <?php 
                                    $value = $row->option_value;
                                    $unserialized = maybe_unserialize($value);
                                    if (is_array($unserialized) || is_object($unserialized)) {
                                        echo '<pre style="margin:0; font-size:11px;">' . esc_html(print_r($unserialized, true)) . '</pre>';
                                    } else {
                                        echo esc_html($value);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_settings_general() {
        $cron_freq = get_option('amli_cron_frequency', 'hourly');
        $last_update = get_option('amli_last_update');
        $next_run = wp_next_scheduled('amli_cron_event');
        $server_time = current_time('timestamp');
        ?>
        
            <?php if ( isset($_GET['settings-updated']) ) : ?>
                <div id="message" class="updated notice is-dismissible"><p>Impostazioni salvate.</p></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top:20px;">

                <form method="post" action="options.php">
                    <?php settings_fields('amli_options_group'); ?>
                    <?php do_settings_sections('amli_options_group'); ?>
                        <table class="form-table">
                            <tr valign="top">
                            <th scope="row">Metodo di Aggiornamento</th>
                            <td>
                                <select name="amli_cron_frequency">
                                    <option value="manual" <?php selected($cron_freq, 'manual'); ?>>Ad ogni visita (Manuale - Cache di 20 min)</option>
                                    <option value="hourly" <?php selected($cron_freq, 'hourly'); ?>>Cron - Ogni ora (Consigliato)</option>
                                    <option value="twicedaily" <?php selected($cron_freq, 'twicedaily'); ?>>Cron - Due volte al giorno</option>
                                    <option value="daily" <?php selected($cron_freq, 'daily'); ?>>Cron - Una volta al giorno</option>
                                </select>
                                <p class="description">Selezionando una voce "Cron", i dati verranno aggiornati in background. Se il Cron fallisce, verrà usato il metodo manuale come fallback.</p>
                            </td>
                            </tr>
                        </table>
                        <?php submit_button('Salva Impostazioni'); ?>
                    </form>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h3 style="margin-top:0;">Stato del Cron</h3>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <li style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <strong>Stato Attuale:</strong><br>
                            <?php if ($cron_freq === 'manual'): ?>
                                <span class="dashicons dashicons-controls-pause" style="color: #666;"></span> Disattivato (Manuale)
                            <?php elseif ($next_run): ?>
                                <span class="dashicons dashicons-yes" style="color: #46b450;"></span> Attivo e schedulato
                            <?php else: ?>
                                <span class="dashicons dashicons-warning" style="color: #dc3232;"></span> Errore (Non schedulato)
                            <?php endif; ?>
                        </li>
                        <li style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <strong>Ultima Esecuzione:</strong><br>
                            <?php echo $last_update ? date('d/m/Y H:i:s', $last_update) : 'Mai'; ?>
                        </li>
                        <li style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <strong>Prossima Esecuzione:</strong><br>
                            <?php 
                            if ($cron_freq === 'manual') {
                                echo '-';
                            } elseif ($next_run) {
                                echo date('d/m/Y H:i:s', $next_run);
                                $diff = $next_run - $server_time;
                                if ($diff > 0) {
                                    echo ' <small style="color:#666;">(tra ' . round($diff/60) . ' min)</small>';
                                } else {
                                    echo ' <small style="color:#dc3232;">(IN RITARDO)</small>';
                                }
                            } else {
                                echo '<span style="color:#dc3232;">Nessuna pianificata</span>';
                            }
                            ?>
                        </li>
                        <li style="padding-top: 5px;">
                            <span style="font-size: 0.9em; color: #666;">Ora del server: <?php echo date('H:i:s', $server_time); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        <?php
    }

    public function dashboard_page() {
        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'meteo';
        $forecast_enabled = isset($_GET['amli_forecast']) && $_GET['amli_forecast'] == '1';
        $last_update = get_option('amli_last_update');
        $cron_freq = get_option('amli_cron_frequency', 'hourly');
        
        // Calcolo avviso Cron
        $cron_alert = false;
        if ($cron_freq !== 'manual') {
            $expected_interval = 3600; // Default fallback
            switch($cron_freq) {
                case 'hourly': $expected_interval = 3600; break;
                case 'twicedaily': $expected_interval = 43200; break;
                case 'daily': $expected_interval = 86400; break;
            }
            // Se sono passati più di 2 intervalli senza aggiornamenti, c'è un problema
            if ($last_update && (current_time('timestamp') - $last_update) > ($expected_interval * 2)) {
                $cron_alert = true;
            }
        }
        ?>
        <div class="wrap amli-dashboard">
            <div class="amli-header">
                <h1>Allerta Meteo Lombardia</h1>
                <div class="amli-actions">
                    <a href="<?php echo esc_url(add_query_arg('amli_forecast', $forecast_enabled ? '0' : '1')); ?>" class="button">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo $forecast_enabled ? 'Nascondi Previsioni' : 'Mostra Previsioni'; ?>
                    </a>
                </div>
            </div>
            <div class="amli-status">
                <div class="amli-status-item">
                    <span class="dashicons dashicons-clock"></span>
                    <strong>Ultimo aggiornamento dati: </strong>
                    <?php echo $last_update ? date('d/m/Y H:i:s', $last_update) : 'Mai eseguito'; ?>
                    <a href="<?php echo esc_url(add_query_arg('amli_refresh', '1')); ?>" class="button button-primary">
                        Aggiorna
                    </a>
                </div>
            </div>
            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="<?php echo esc_url(add_query_arg('tab', 'meteo')); ?>" class="nav-tab <?php echo $current_tab === 'meteo' ? 'nav-tab-active' : ''; ?>">Temporali/Idro</a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'neve')); ?>" class="nav-tab <?php echo $current_tab === 'neve' ? 'nav-tab-active' : ''; ?>">Neve</a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'valanghe')); ?>" class="nav-tab <?php echo $current_tab === 'valanghe' ? 'nav-tab-active' : ''; ?>">Valanghe</a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'incendi')); ?>" class="nav-tab <?php echo $current_tab === 'incendi' ? 'nav-tab-active' : ''; ?>">Incendi</a>
            </h2>

            <?php if ( $cron_alert ) : ?>
                <div class="notice notice-error" style="margin-top: 20px;">
                    <p><strong>⚠ Attenzione:</strong> Il Cron automatico sembra bloccato! L'ultimo aggiornamento risale a molto tempo fa. Controlla che il gestore cron del server (WP-Cron) stia funzionando.</p>
                </div>
            <?php endif; ?>

            <div class="amli-grid">
                <?php
                $zones = array();
                $prefix = '';
                
                switch($current_tab) {
                    case 'meteo':
                        $zones = AMLI_Zone_Manager::get_zone_meteo();
                        $prefix = 'amli_'; // legacy
                        break;
                    case 'neve':
                        $zones = AMLI_Zone_Manager::get_zone_neve();
                        $prefix = 'amli_neve_';
                        break;
                    case 'valanghe':
                        $zones = AMLI_Zone_Manager::get_zone_valanghe();
                        $prefix = 'amli_valanghe_';
                        break;
                    case 'incendi':
                        $zones = AMLI_Zone_Manager::get_zone_incendi();
                        $prefix = 'amli_incendi_';
                        break;
                }

                foreach ($zones as $key => $val) {
                    // Normalizzazione chiave/valore basata su come get_zone restituisce i dati
                    // get_zone_meteo restituisce array di array [[code, name], ...]
                    // gli altri restituiscono array associativo [code => name]
                    
                    if ( $current_tab === 'meteo' ) {
                        $id = $val[0];
                        $name = $val[1];
                    } else {
                        $id = $key;
                        $name = $val;
                    }

                    $option_key = $current_tab === 'meteo' ? 'amli_' . $id : $prefix . $id;
                    $risk_data = get_option($option_key);
                    $alert_display = '';

                    // Calcolo classe CSS e Display
                    $alert_class = 'no-alert'; 
                    
                    if ( $current_tab === 'meteo' ) {
                        // Meteo è un array
                        $alert_display = do_shortcode('[amli id="' . esc_attr($id) . '"]');
                        if (is_array($risk_data)) {
                            if (max($risk_data) >= 3) $alert_class = 'danger-alert';
                            elseif (max($risk_data) >= 2) $alert_class = 'warning-alert';
                        }
                    } else {
                        // Altri sono scalari
                        $risk_val = (int)$risk_data;
                        if ($risk_val >= 3) {
                            $alert_class = 'danger-alert';
                        } elseif ($risk_val == 2) {
                            $alert_class = 'warning-alert';
                        }
                        // Usa Shortcode anche per i singoli rischi
                        $shortcode_str = '[amli ' . esc_attr($current_tab) . '="' . esc_attr($id) . '"';
                        if ($forecast_enabled) {
                            $shortcode_str .= ' previsione="1"';
                        }
                        $shortcode_str .= ']';
                        $alert_display = do_shortcode($shortcode_str);
                    }

                    
                    ?>
                    <div class="amli-card <?php echo $alert_class; ?>">
                        <h3><?php echo esc_html($name); ?></h3>
                        <div class="amli-card-content">
                            <div class="amli-status-display">
                                <?php echo $alert_display; ?>
                            </div>
                            
                            <?php 
                            // Determine simplified shortcode for copy
                            if ($current_tab === 'meteo') {
                                $copy_code = '[amli idrometeo="' . esc_attr($id) . '"]';
                            } else {
                                $copy_code = '[amli ' . esc_attr($current_tab) . '="' . esc_attr($id) . '"]';
                            }
                            ?>

                            <div class="amli-shortcode-container">
                                <button type="button" class="amli-btn-modal" onclick="openAmliModal('<?php echo esc_js($copy_code); ?>')">
                                    Mostra Shortcode
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                } // endforeach
                ?>
            </div>
            
            <!-- Modal Structure -->
            <div id="amliModal" class="amli-modal">
                <div class="amli-modal-content">
                    <span class="amli-close" onclick="closeAmliModal()">&times;</span>
                    <h3 style="margin-top:0;">Copia Shortcode</h3>
                    <p>Usa questo codice per inserire l'allerta:</p>
                    <input type="text" id="amliModalInput" class="amli-shortcode-box" readonly>
                    <button class="button button-primary" onclick="copyAmliModal()" style="margin-top:10px; width:100%;">Copia</button>
                    <p class="description" style="margin-top:10px; font-size:12px; color:#666;">Per combinare più zone o rischi, usa lo <a href="?page=amli-shortcode-builder">Shortcode Builder</a>.</p>
                </div>
            </div>

            <script>
            function openAmliModal(code) {
                document.getElementById('amliModalInput').value = code;
                document.getElementById('amliModal').style.display = "block";
            }
            function closeAmliModal() {
                document.getElementById('amliModal').style.display = "none";
            }
            function copyAmliModal() {
                var copyText = document.getElementById("amliModalInput");
                copyText.select();
                navigator.clipboard.writeText(copyText.value);
                alert("Copiato!");
                closeAmliModal();
            }
            window.onclick = function(event) {
                var modal = document.getElementById('amliModal');
                if (event.target == modal) { modal.style.display = "none"; }
            }
            </script>
        </div>
        <?php
        $this->enqueue_dashboard_styles();

        ?>
        <div style="text-align: center; margin-top: 50px; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px;">
           Created by <a href="https://github.com/milesimarco" target="_blank" style="text-decoration:none; color: #0073aa; font-weight:600;">Marco Milesi</a>
        </div>
        <?php
    }

    private function enqueue_dashboard_styles() {
        ?>
        <style>
            .amli-dashboard {
                max-width: 100%;
                margin: 20px;
            }
            .amli-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .amli-actions {
                display: flex;
                gap: 10px;
            }
            .amli-status {
                background: #fff;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .amli-status-item {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .amli-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }

            @media screen and (max-width: 1200px) {
                .amli-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            @media screen and (max-width: 900px) {
                .amli-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media screen and (max-width: 600px) {
                .amli-grid {
                    grid-template-columns: 1fr;
                }
            }
            .amli-card {
                background: #fff;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                border-top: 4px solid transparent;
            }
            .amli-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .amli-card.has-alert {
                border-top-color: #dc3545;
            }
            .amli-card.no-alert {
                background-color: #fff;
            }
            .amli-card.warning-alert {
                border-top-color: #ffc107;
                background-color: #fff8f0;
            }
            .amli-card.danger-alert {
                border-top-color: #dc3545;
                background-color: #fff5f5;
            }
            .amli-card h3 {
                margin: 0 0 15px 0;
                color: #1d2327;
                font-size: 1.2em;
            }
            .amli-card-content {
                position: relative;
            }
            .amli-status-display {
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
            }
            .amli-status-display table {
                margin: 0;
            }
            .amli-shortcode-container {
                margin-top: 15px;
            }
            .amli-btn-modal {
                background: none !important;
                border: none;
                color: #0073aa;
                cursor: pointer;
                padding: 0;
                font-size: 13px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .amli-btn-modal:hover {
                color: #005177;
                text-decoration: underline;
            }
            .amli-modal {
                display: none;
                position: fixed;
                z-index: 10000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.5);
            }
            .amli-modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 400px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                position: relative;
            }
            .amli-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                line-height: 20px;
            }
            .amli-close:hover,
            .amli-close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }
            .amli-shortcode-box {
                background: #f0f0f1;
                border: 1px solid #ccc;
                padding: 10px;
                font-family: monospace;
                width: 100%;
                box-sizing: border-box;
                margin-top: 10px;
                font-size: 1.1em;
            }
            /* Alert Level Colors */
            td[bgcolor="#ffff00"] { /* Ordinaria */
                background-color: #fff3cd !important;
                color: #856404;
            }
            td[bgcolor="#ff9900"] { /* Moderata */
                background-color: #fff3cd !important;
                color: #856404;
            }
            td[bgcolor="#ff0000"] { /* Elevata */
                background-color: #f8d7da !important;
                color: #721c24;
            }
            td[bgcolor="#66ff00"] { /* Assente */
                background-color: #d4edda !important;
                color: #155724;
            }
        </style>
        <?php
    }
}