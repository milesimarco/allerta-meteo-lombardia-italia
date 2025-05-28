<?php

class AMLI_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'handle_refresh_action'));
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
            'Allerte Meteo',
            'Allerte Meteo',
            'manage_options',
            'amli-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-warning',
            80
        );
    }

    public function dashboard_page() {
        $show_debug = isset($_GET['amli_debug']) && $_GET['amli_debug'] === '1';
        $last_update = get_option('amli_last_update');
        ?>
        <div class="wrap amli-dashboard">
            <div class="amli-header">
                <h1>Allerta Meteo Lombardia</h1>
                <div class="amli-actions">
                    <a href="<?php echo esc_url(add_query_arg('amli_refresh', '1')); ?>" class="button button-primary">
                        Aggiorna dati
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('amli_debug', $show_debug ? '0' : '1')); ?>" class="button">
                        <span class="dashicons dashicons-<?php echo $show_debug ? 'hidden' : 'visibility'; ?>"></span>
                        <?php echo $show_debug ? 'Nascondi Debug' : 'Mostra Debug'; ?>
                    </a>
                </div>
            </div>

            <div class="amli-status">
                <div class="amli-status-item">
                    <span class="dashicons dashicons-clock"></span>
                    <strong>Ultimo aggiornamento:</strong>
                    <?php echo $last_update ? date('d/m/Y H:i:s', $last_update) : 'Mai eseguito'; ?>
                </div>
            </div>

            <div class="amli-grid">
                <?php
                if (function_exists('amli_get_paesi')) {
                    $zone = amli_get_paesi();
                    foreach ($zone as $z) {
                        $id = $z[0];
                        $name = $z[1];
                        $alert_data = do_shortcode('[amli id="' . esc_attr($id) . '"]');
                        $risk_data = get_option('amli_' . $id);

                        $alert_class = 'no-alert'; // default state
                        if (is_array($risk_data)) {
                            if (max($risk_data) >= 2) {
                                $alert_class = 'danger-alert';
                            } elseif (max($risk_data) == 1) {
                                $alert_class = 'warning-alert';
                            }
                        }
                        ?>
                        <div class="amli-card <?php echo $alert_class; ?>">
                            <h3><?php echo esc_html($name); ?></h3>
                            <div class="amli-card-content">
                                <div class="amli-status-display">
                                    <?php echo $alert_data; ?>
                                </div>
                                <div class="amli-shortcode">
                                    <code>[amli id="<?php echo esc_attr($id); ?>"]</code>
                                </div>
                                <?php if ($show_debug): ?>
                                    <div class="amli-debug">
                                        <pre><?php echo esc_html(print_r(get_option('amli_' . $id), true)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <?php
        $this->enqueue_dashboard_styles();
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
            .amli-shortcode {
                margin-top: 15px;
                padding: 10px;
                background: #f0f0f1;
                border-radius: 6px;
                font-family: monospace;
                color: #3c434a;
            }
            .amli-shortcode code {
                background: none;
                padding: 0;
            }
            .amli-debug {
                margin-top: 15px;
                padding: 12px;
                background: #f8f9fa;
                border-radius: 6px;
                font-size: 12px;
                border: 1px solid #ddd;
            }
            .amli-debug pre {
                margin: 0;
                white-space: pre-wrap;
                font-family: monospace;
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