<?php

class AMLI_Shortcode {
    private $atts;
    private $results;
    private $alerts;

    public function __construct() {
        add_shortcode('amli', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $this->atts = shortcode_atts(array(
            'id' => '0',
            'height' => '',
            'width' => '100%',
            'title' => ''
        ), $atts);

        if (!$this->atts['id']) {
            return 'error: ID not provided';
        }

        // Run the scraper
        amli_scrape();

        $this->results = get_option('amli_' . $this->atts['id']);
        
        if (!$this->results || !is_array($this->results)) {
            return '<div style="color:red;">Dati non disponibili. Riprova più tardi.</div>';
        }

        $this->process_alerts();
        return $this->generate_table();
    }

    private function process_alerts() {
        $this->alerts = array(
            'idrogeologico' => $this->get_safe_alert_display('idrogeologico'),
            'idraulico' => $this->get_safe_alert_display('idraulico'),
            'temporali' => $this->get_safe_alert_display('temporali'),
            'vento' => $this->get_safe_alert_display('vento')
        );
    }

    private function get_safe_alert_display($key) {
        if (isset($this->results[$key])) {
            $res = amli_get_alert_display($this->results[$key]);
            return $res;
        }
        return array('N/D', '#ccc');
    }

    private function generate_table() {
        ob_start();
        ?>
        <style>
            .amli-table {
                border-collapse: collapse;
                width: <?php echo esc_attr($this->atts['width']); ?>;
                <?php if ($this->atts['height']): ?>
                height: <?php echo esc_attr($this->atts['height']); ?>;
                <?php endif; ?>
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                margin: 1em 0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .amli-table th, .amli-table td {
                padding: 12px;
                border: 1px solid #ddd;
            }
            .amli-table thead th {
                background-color: #f8f9fa;
                font-weight: 600;
                text-align: left;
            }
            .amli-table tbody th {
                font-weight: normal;
                text-align: left;
            }
            .amli-table .amli-footer {
                font-size: 0.85em;
                color: #666;
                padding: 8px 12px;
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
                <tr>
                    <th><?php echo amli_get_paesi($this->atts['id']); ?> (IM-<?php echo esc_html($this->atts['id']); ?>)</th>
                    <th>Criticità</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->alerts as $type => $data): 
                    list($value, $color) = $data;
                    $label = $this->get_alert_label($type);
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html($label); ?></th>
                    <td bgcolor="<?php echo esc_attr($color); ?>" style="text-align: center; font-weight: bold;">
                        <?php echo esc_html($value); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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

    private function get_alert_label($type) {
        $labels = array(
            'idrogeologico' => 'Idrogeologico',
            'idraulico' => 'Idraulico',
            'temporali' => 'Temporali Forti',
            'vento' => 'Vento'
        );
        return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
    }
}

// Initialize the shortcode
new AMLI_Shortcode();