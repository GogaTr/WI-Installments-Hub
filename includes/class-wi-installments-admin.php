<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WI_Installments_Admin {
    const SETTINGS_OPTION = 'wi_installments_hub_settings';

    private $logger;
    private $providers;

    public function __construct( WI_Installments_Logger $logger, array $providers ) {
        $this->logger    = $logger;
        $this->providers = $providers;

        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        add_action( 'admin_post_wi_installments_create_application', [ $this, 'handle_create_application' ] );
        add_action( 'admin_post_wi_installments_check_status', [ $this, 'handle_check_status' ] );
        add_action( 'admin_post_wi_installments_clear_history', [ $this, 'handle_clear_history' ] );
        add_action( 'admin_post_wi_installments_clear_logs', [ $this, 'handle_clear_logs' ] );
    }

    public function register_admin_menu() {
        add_menu_page(
            'განვადებები',
            'განვადებები',
            'manage_woocommerce',
            'wi-installments-hub',
            [ $this, 'render_dashboard_page' ],
            'dashicons-money-alt',
            56
        );

        add_submenu_page(
            'wi-installments-hub',
            'TBC განვადება',
            'TBC',
            'manage_woocommerce',
            'wi-installments-hub-tbc',
            [ $this, 'render_tbc_page' ]
        );

        add_submenu_page(
            'wi-installments-hub',
            'Credo განვადება',
            'Credo',
            'manage_woocommerce',
            'wi-installments-hub-credo',
            [ $this, 'render_credo_page' ]
        );

        add_submenu_page(
            'wi-installments-hub',
            'საქართველოს ბანკი',
            'BOG',
            'manage_woocommerce',
            'wi-installments-hub-bog',
            [ $this, 'render_bog_page' ]
        );

        add_submenu_page(
            'wi-installments-hub',
            'მოთხოვნების ისტორია',
            'ისტორია',
            'manage_woocommerce',
            'wi-installments-hub-history',
            [ $this, 'render_history_page' ]
        );

        add_submenu_page(
            'wi-installments-hub',
            'ლოგები',
            'ლოგები',
            'manage_woocommerce',
            'wi-installments-hub-logs',
            [ $this, 'render_logs_page' ]
        );

        add_submenu_page(
            'wi-installments-hub',
            'პარამეტრები',
            'პარამეტრები',
            'manage_options',
            'wi-installments-hub-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting(
            'wi_installments_hub_settings_group',
            self::SETTINGS_OPTION,
            [ $this, 'sanitize_settings' ]
        );
    }

    public function sanitize_settings( $input ) {
        return [
            'mode'                  => isset( $input['mode'] ) && 'production' === $input['mode'] ? 'production' : 'test',
            'tbc_api_key'           => isset( $input['tbc_api_key'] ) ? sanitize_text_field( $input['tbc_api_key'] ) : '',
            'tbc_api_secret'        => isset( $input['tbc_api_secret'] ) ? sanitize_text_field( $input['tbc_api_secret'] ) : '',
            'tbc_merchant_key'      => isset( $input['tbc_merchant_key'] ) ? sanitize_text_field( $input['tbc_merchant_key'] ) : '',
            'tbc_campaign_id'       => isset( $input['tbc_campaign_id'] ) ? sanitize_text_field( $input['tbc_campaign_id'] ) : '',
            'credo_enabled'         => ! empty( $input['credo_enabled'] ) ? 1 : 0,
            'credo_merchant_id'     => isset( $input['credo_merchant_id'] ) ? sanitize_text_field( $input['credo_merchant_id'] ) : '',
            'credo_secret_password' => isset( $input['credo_secret_password'] ) ? sanitize_text_field( $input['credo_secret_password'] ) : '',
            'credo_create_url'      => isset( $input['credo_create_url'] ) ? esc_url_raw( $input['credo_create_url'] ) : 'https://ganvadeba.credo.ge/widget_api/order.php',
            'credo_status_url'      => isset( $input['credo_status_url'] ) ? esc_url_raw( $input['credo_status_url'] ) : 'https://ganvadeba.credo.ge/widget/api.php',
            'bog_enabled'           => ! empty( $input['bog_enabled'] ) ? 1 : 0,
            'bog_client_id'         => isset( $input['bog_client_id'] ) ? sanitize_text_field( $input['bog_client_id'] ) : '',
            'bog_secret_key'        => isset( $input['bog_secret_key'] ) ? sanitize_text_field( $input['bog_secret_key'] ) : '',

        ];
    }

    public function enqueue_assets( $hook ) {
        if ( false === strpos( $hook, 'wi-installments-hub' ) ) {
            return;
        }

        wp_enqueue_style(
            'wi-installments-hub-admin',
            WI_INSTALLMENTS_HUB_URL . 'assets/admin.css',
            [],
            WI_INSTALLMENTS_HUB_VERSION
        );
    }

    public function render_dashboard_page() {
        $settings = $this->get_settings();
        $credo_enabled = ! empty( $settings['credo_enabled'] );
        $bog_enabled   = ! empty( $settings['bog_enabled'] );
        ?>
        <div class="wrap wi-installments-wrap">
            <h1>განვადებების ჰაბი</h1>
            <p>აირჩიე ბანკი და შექმენი განვადების განაცხადი ადმინისტრაციიდან.</p>

            <div class="wi-provider-grid">
                <a class="wi-provider-card" href="<?php echo esc_url( admin_url( 'admin.php?page=wi-installments-hub-tbc' ) ); ?>">
                    <div class="wi-provider-logo wi-provider-logo--tbc">TBC</div>
                    <div class="wi-provider-title">TBC განვადება</div>
                    <div class="wi-provider-text">ხელით შექმნა ადმინისტრაციიდან</div>
                    <div class="wi-provider-mode">Mode: <?php echo esc_html( strtoupper( $settings['mode'] ) ); ?></div>
                </a>

                <a class="wi-provider-card <?php echo $credo_enabled ? '' : 'wi-provider-card--disabled'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wi-installments-hub-credo' ) ); ?>">
                    <div class="wi-provider-logo wi-provider-logo--placeholder">CREDO</div>
                    <div class="wi-provider-title">Credo განვადება</div>
                    <div class="wi-provider-text">ხელით შექმნა ადმინისტრაციიდან</div>
                    <div class="wi-provider-mode"><?php echo $credo_enabled ? 'Enabled' : 'ჯერ არ არის ჩართული'; ?></div>
                </a>

                <a class="wi-provider-card <?php echo $bog_enabled ? '' : 'wi-provider-card--disabled'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wi-installments-hub-bog' ) ); ?>">
                    <div class="wi-provider-logo wi-provider-logo--bog">BOG</div>
                    <div class="wi-provider-title">საქართველოს ბანკი</div>
                    <div class="wi-provider-text">ხელით შექმნა ადმინისტრაციიდან</div>
                    <div class="wi-provider-mode"><?php echo $bog_enabled ? 'Enabled' : 'ჯერ არ არის ჩართული'; ?></div>
                </a>
            </div>
        </div>
        <?php
    }

    public function render_tbc_page() {
        $this->render_provider_page( 'tbc' );
    }

    public function render_credo_page() {
        $this->render_provider_page( 'credo' );
    }

    public function render_bog_page() {
        $this->render_provider_page( 'bog' );
    }

    private function render_provider_page( $provider_key ) {
        $provider = $this->get_provider( $provider_key );
        if ( ! $provider ) {
            echo '<div class="wrap"><h1>Provider ვერ მოიძებნა.</h1></div>';
            return;
        }

        $last_request = $this->get_latest_request( $provider_key );
        ?>
        <div class="wrap wi-installments-wrap">
            <h1><?php echo esc_html( $provider->get_label() ); ?> განვადების მოთხოვნა</h1>
            <?php $this->render_flash_notice(); ?>

            <?php if ( 'credo' === $provider_key && ! $provider->is_enabled() ) : ?>
                <div class="notice notice-warning"><p>Credo ჯერ პარამეტრებში უნდა ჩართო და შეავსო Merchant ID / Secret Password.</p></div>
            <?php endif; ?>

            <?php if ( 'bog' === $provider_key && ! $provider->is_enabled() ) : ?>
                <div class="notice notice-warning"><p>BOG ჯერ პარამეტრებში უნდა ჩართო და შეავსო Client ID / Secret Key.</p></div>
            <?php endif; ?>

            <div class="wi-grid-two">
                <div class="wi-card">
                    <h2>ახალი მოთხოვნა</h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'wi_installments_create_application', 'wi_installments_nonce' ); ?>
                        <input type="hidden" name="action" value="wi_installments_create_application">
                        <input type="hidden" name="provider" value="<?php echo esc_attr( $provider_key ); ?>">

                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th><label for="wi_product_name">პროდუქტის სახელი</label></th>
                                    <td><input name="product_name" id="wi_product_name" type="text" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="wi_price_total">ფასი (GEL)</label></th>
                                    <td><input name="price_total" id="wi_price_total" type="number" class="regular-text" min="0.01" step="0.01" required></td>
                                </tr>
                                <tr>
                                    <th><label for="wi_quantity">რაოდენობა</label></th>
                                    <td><input name="quantity" id="wi_quantity" type="number" class="small-text" min="1" step="1" value="1" required></td>
                                </tr>
                                <tr>
                                    <th><label for="wi_invoice_id">Invoice / Reference</label></th>
                                    <td><input name="invoice_id" id="wi_invoice_id" type="text" class="regular-text" value="<?php echo esc_attr( 'ADM-' . wp_generate_password( 8, false, false ) ); ?>" required></td>
                                </tr>
                                <?php if ( 'bog' === $provider_key ) : ?>
                                <tr>
                                    <th>განვადების პირობა</th>
                                    <td>
                                        <button type="button" id="wi_bog_open_calc" class="button button-secondary">
                                            📊 კალკულატორი — პირობის არჩევა
                                        </button>
                                        <span id="wi_bog_selected_label" style="margin-left:10px;color:#646970;"></span>
                                    </td>
                                </tr>
                                <tr id="wi_bog_selected_row" style="display:none;">
                                    <th>არჩეული პირობა</th>
                                    <td>
                                        <strong id="wi_bog_selected_summary" style="color:#1d8348;"></strong>
                                        <input type="hidden" name="bog_installment_month" id="wi_bog_month" value="" required>
                                        <input type="hidden" name="bog_installment_type"  id="wi_bog_type"  value="">
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <?php submit_button( 'განვადების შექმნა' ); ?>
                    </form>
                </div>
                <?php if ( 'bog' === $provider_key ) : ?>
                <?php
                $bog_client_id = $this->get_settings()['bog_client_id'] ?? '';
                ?>
                <script src="https://webstatic.bog.ge/bog-sdk/bog-sdk.js?version=2&client_id=<?php echo esc_attr( $bog_client_id ); ?>"></script>
                <script>
                (function() {
                    var btn     = document.getElementById('wi_bog_open_calc');
                    var label   = document.getElementById('wi_bog_selected_label');
                    var row     = document.getElementById('wi_bog_selected_row');
                    var summary = document.getElementById('wi_bog_selected_summary');
                    var inMonth = document.getElementById('wi_bog_month');
                    var inType  = document.getElementById('wi_bog_type');

                    function getAmount() {
                        return parseFloat( document.getElementById('wi_price_total').value ) || 0;
                    }

                   btn.addEventListener('click', function() {
    var amount = getAmount();

    if (amount <= 0) {
        alert('პირველ რიგში შეიყვანე ფასი (GEL).');
        return;
    }

    BOG.Calculator.open({
        amount: Number(amount) || 0,
        bnpl: false,
        onClose: function() {},
        onRequest: function(selected, successCb, closeCb) {
            selected = selected || {};

            var month = selected.month || '';
            var discountCode = selected.discount_code || 'STANDARD';
            var monthlyAmount = Number(selected.amount || 0);

            inMonth.value = month;
            inType.value = discountCode;

            var typeLabel = 'სტანდარტული';

            if (discountCode === 'ZERO') {
                typeLabel = '0%-იანი';
            } else if (discountCode === 'DISCOUNTED') {
                typeLabel = 'ფასდაკლებით';
            }

            summary.textContent =
                month + ' თვე · ' +
                typeLabel + ' · ' +
                monthlyAmount.toFixed(2) + ' ₾/თვე';

            label.textContent = '✓ პირობა არჩეულია';
            row.style.display = '';

            if (window.BOG && BOG.Calculator && typeof BOG.Calculator.close === 'function') {
                BOG.Calculator.close();
            }

            return false;
        },
        onComplete: function() {
            return false;
        }
    });
});

                    document.getElementById('wi_price_total').addEventListener('change', function() {
                        inMonth.value   = '';
                        inType.value    = '';
                        label.textContent = '';
                        row.style.display = 'none';
                    });
                })();
                </script>
                <?php endif; ?>

                <div class="wi-card">
                    <h2>ბოლო მოთხოვნა</h2>
                    <?php if ( $last_request ) : ?>
                        <p><strong>ID:</strong> <?php echo esc_html( $last_request['id'] ); ?></p>
                        <p><strong>Invoice:</strong> <?php echo esc_html( $last_request['invoice_id'] ); ?></p>
                        <p><strong>Session ID:</strong> <?php echo esc_html( $last_request['session_id'] ? $last_request['session_id'] : '—' ); ?></p>
                        <p><strong>Status:</strong> <?php echo esc_html( $last_request['remote_status_text'] ? $last_request['remote_status_text'] : 'ჯერ არ შემოწმებულა' ); ?></p>
                        <p><strong>Redirect:</strong>
                            <?php if ( ! empty( $last_request['redirect_url'] ) ) : ?>
                                <a href="<?php echo esc_url( $last_request['redirect_url'] ); ?>" target="_blank" rel="noopener noreferrer">გახსნა</a>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </p>

                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'wi_installments_check_status', 'wi_installments_status_nonce' ); ?>
                            <input type="hidden" name="action" value="wi_installments_check_status">
                            <input type="hidden" name="request_id" value="<?php echo esc_attr( $last_request['id'] ); ?>">
                            <?php submit_button( 'სტატუსის შემოწმება', 'secondary', 'submit', false ); ?>
                        </form>
                    <?php else : ?>
                        <p>ჯერ მოთხოვნა არ შექმნილა.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_history_page() {
        global $wpdb;
        $rows = $wpdb->get_results( 'SELECT * FROM ' . WI_Installments_DB::requests_table() . ' ORDER BY id DESC LIMIT 100', ARRAY_A );
        ?>
        <div class="wrap wi-installments-wrap">
            <h1>მოთხოვნების ისტორია</h1>
            <?php $this->render_flash_notice(); ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 15px 0;">
                <?php wp_nonce_field( 'wi_installments_clear_history', 'wi_installments_clear_history_nonce' ); ?>
                <input type="hidden" name="action" value="wi_installments_clear_history">
                <?php submit_button( 'ისტორიის გასუფთავება', 'delete', 'submit', false, [
                    'onclick' => "return confirm('ნამდვილად გინდა მოთხოვნების ისტორიის სრულად წაშლა?');"
                ] ); ?>
            </form>
            <div class="wi-card wi-card--wide">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>Invoice</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Session</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $rows ) ) : ?>
                            <tr><td colspan="8">ისტორია ცარიელია.</td></tr>
                        <?php else : ?>
                            <?php foreach ( $rows as $row ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $row['id'] ); ?></td>
                                    <td><?php echo esc_html( strtoupper( $row['provider'] ) ); ?></td>
                                    <td><?php echo esc_html( $row['invoice_id'] ); ?></td>
                                    <td><?php echo esc_html( $row['product_name'] ); ?></td>
                                    <td><?php echo esc_html( $row['price_total'] ); ?></td>
                                    <td><?php echo esc_html( $row['session_id'] ? $row['session_id'] : '—' ); ?></td>
                                    <td><?php echo esc_html( $row['remote_status_text'] ? $row['remote_status_text'] : '—' ); ?></td>
                                    <td><?php echo esc_html( $row['created_at'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_logs_page() {
        $logs = $this->logger->get_recent_logs( 100 );
        ?>
        <div class="wrap wi-installments-wrap">
            <h1>ლოგები</h1>
            <?php $this->render_flash_notice(); ?>
            <p>ლოგ ფაილები ინახება: <code><?php echo esc_html( $this->logger->get_log_dir() ); ?></code></p>
            <div style="margin: 15px 0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'wi_installments_clear_logs', 'wi_installments_clear_logs_nonce' ); ?>
                    <input type="hidden" name="action" value="wi_installments_clear_logs">
                    <input type="hidden" name="clear_db_logs" value="1">
                    <?php submit_button( 'DB ლოგების გასუფთავება', 'delete', 'submit', false, [
                        'onclick' => "return confirm('ნამდვილად გინდა DB ლოგების წაშლა?');"
                    ] ); ?>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'wi_installments_clear_logs', 'wi_installments_clear_logs_nonce' ); ?>
                    <input type="hidden" name="action" value="wi_installments_clear_logs">
                    <input type="hidden" name="clear_file_logs" value="1">
                    <?php submit_button( 'ლოგ ფაილების გასუფთავება', 'delete', 'submit', false, [
                        'onclick' => "return confirm('ნამდვილად გინდა ლოგ ფაილების წაშლა?');"
                    ] ); ?>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'wi_installments_clear_logs', 'wi_installments_clear_logs_nonce' ); ?>
                    <input type="hidden" name="action" value="wi_installments_clear_logs">
                    <input type="hidden" name="clear_db_logs" value="1">
                    <input type="hidden" name="clear_file_logs" value="1">
                    <?php submit_button( 'ყველა ლოგის გასუფთავება', 'delete', 'submit', false, [
                        'onclick' => "return confirm('ნამდვილად გინდა ყველა ლოგის სრულად წაშლა?');"
                    ] ); ?>
                </form>
            </div>
            <div class="wi-card wi-card--wide">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Level</th>
                            <th>Provider</th>
                            <th>Action</th>
                            <th>Message</th>
                            <th>HTTP</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $logs ) ) : ?>
                            <tr><td colspan="7">ლოგები არ მოიძებნა.</td></tr>
                        <?php else : ?>
                            <?php foreach ( $logs as $log ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $log['id'] ); ?></td>
                                    <td><?php echo esc_html( strtoupper( $log['level'] ) ); ?></td>
                                    <td><?php echo esc_html( strtoupper( $log['provider'] ) ); ?></td>
                                    <td><?php echo esc_html( $log['action_name'] ); ?></td>
                                    <td><?php echo esc_html( $log['message'] ); ?></td>
                                    <td><?php echo esc_html( $log['response_code'] ? $log['response_code'] : '—' ); ?></td>
                                    <td><?php echo esc_html( $log['created_at'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        $settings = $this->get_settings();
        ?>
        <div class="wrap wi-installments-wrap">
            <h1>პარამეტრები</h1>
            <div class="wi-card wi-card--wide">
                <form method="post" action="options.php">
                    <?php settings_fields( 'wi_installments_hub_settings_group' ); ?>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th colspan="2"><h2>TBC Settings</h2></th>
                            </tr>
                            <tr>
                                <th>Environment</th>
                                <td>
                                    <label><input type="radio" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[mode]" value="test" <?php checked( $settings['mode'], 'test' ); ?>> Test</label>
                                    &nbsp;&nbsp;
                                    <label><input type="radio" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[mode]" value="production" <?php checked( $settings['mode'], 'production' ); ?>> Production</label>
                                    <p class="description">Test Base URL: <code>https://test-api.tbcbank.ge</code> | Production Base URL: <code>https://api.tbcbank.ge</code></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="tbc_api_key">TBC API Key</label></th>
                                <td><input id="tbc_api_key" class="regular-text" type="text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[tbc_api_key]" value="<?php echo esc_attr( $settings['tbc_api_key'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="tbc_api_secret">TBC API Secret</label></th>
                                <td><input id="tbc_api_secret" class="regular-text" type="password" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[tbc_api_secret]" value="<?php echo esc_attr( $settings['tbc_api_secret'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="tbc_merchant_key">TBC Merchant Key</label></th>
                                <td><input id="tbc_merchant_key" class="regular-text" type="text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[tbc_merchant_key]" value="<?php echo esc_attr( $settings['tbc_merchant_key'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="tbc_campaign_id">TBC Campaign ID</label></th>
                                <td><input id="tbc_campaign_id" class="regular-text" type="text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[tbc_campaign_id]" value="<?php echo esc_attr( $settings['tbc_campaign_id'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th colspan="2"><h2>Credo Settings</h2></th>
                            </tr>
                            <tr>
                                <th><label for="credo_enabled">Credo Enabled</label></th>
                                <td><label><input id="credo_enabled" type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[credo_enabled]" value="1" <?php checked( ! empty( $settings['credo_enabled'] ) ); ?>> ჩართე Credo provider</label></td>
                            </tr>
                            <tr>
                                <th><label for="credo_merchant_id">Credo Merchant ID</label></th>
                                <td><input id="credo_merchant_id" class="regular-text" type="text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[credo_merchant_id]" value="<?php echo esc_attr( $settings['credo_merchant_id'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="credo_secret_password">Credo Secret Password</label></th>
                                <td><input id="credo_secret_password" class="regular-text" type="password" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[credo_secret_password]" value="<?php echo esc_attr( $settings['credo_secret_password'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="credo_create_url">Credo Create URL</label></th>
                                <td><input id="credo_create_url" class="regular-text" type="url" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[credo_create_url]" value="<?php echo esc_attr( $settings['credo_create_url'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="credo_status_url">Credo Status URL</label></th>
                                <td><input id="credo_status_url" class="regular-text" type="url" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[credo_status_url]" value="<?php echo esc_attr( $settings['credo_status_url'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th colspan="2"><h2>BOG Settings (საქართველოს ბანკი)</h2></th>
                            </tr>
                            <tr>
                                <th><label for="bog_enabled">BOG Enabled</label></th>
                                <td><label><input id="bog_enabled" type="checkbox" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[bog_enabled]" value="1" <?php checked( ! empty( $settings['bog_enabled'] ) ); ?>> ჩართე BOG provider</label></td>
                            </tr>
                            <tr>
                                <th><label for="bog_client_id">BOG Client ID</label></th>
                                <td><input id="bog_client_id" class="regular-text" type="text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[bog_client_id]" value="<?php echo esc_attr( $settings['bog_client_id'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="bog_secret_key">BOG Secret Key</label></th>
                                <td><input id="bog_secret_key" class="regular-text" type="password" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[bog_secret_key]" value="<?php echo esc_attr( $settings['bog_secret_key'] ); ?>"></td>
                            </tr>

                        </tbody>
                    </table>
                    <?php submit_button( 'შენახვა' ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_create_application() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'წვდომა აკრძალულია.' );
        }

        check_admin_referer( 'wi_installments_create_application', 'wi_installments_nonce' );

        $provider_key = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
        $provider     = $this->get_provider( $provider_key );

        if ( ! $provider ) {
            $this->redirect_with_notice( 'error', 'უცნობი provider.' );
        }

        if ( 'credo' === $provider_key && ! $provider->is_enabled() ) {
            $this->redirect_with_notice( 'error', 'Credo ჯერ არ არის ჩართული პარამეტრებში.' );
        }

        $product_name = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
        $price_total  = isset( $_POST['price_total'] ) ? (float) wp_unslash( $_POST['price_total'] ) : 0;
        $quantity     = isset( $_POST['quantity'] ) ? (int) wp_unslash( $_POST['quantity'] ) : 1;
        $invoice_id   = isset( $_POST['invoice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_id'] ) ) : '';

        // BOG-სთვის: ფორმიდან თვეები და ტიპი
        $bog_installment_month = isset( $_POST['bog_installment_month'] ) ? max( 1, (int) wp_unslash( $_POST['bog_installment_month'] ) ) : 12;
        $bog_installment_type  = isset( $_POST['bog_installment_type'] ) && in_array( wp_unslash( $_POST['bog_installment_type'] ), [ 'STANDARD', 'ZERO', 'DISCOUNTED' ], true )
            ? sanitize_text_field( wp_unslash( $_POST['bog_installment_type'] ) )
            : 'STANDARD';

        if ( '' === $product_name || $price_total <= 0 || $quantity < 1 || '' === $invoice_id ) {
            $this->redirect_with_notice( 'error', 'ყველა ველი სწორად შეავსე.' );
        }

        $request_id = $this->insert_request(
            [
                'provider'       => $provider->get_key(),
                'provider_label' => $provider->get_label(),
                'mode'           => $provider->get_mode(),
                'product_name'   => $product_name,
                'quantity'       => $quantity,
                'price_total'    => $price_total,
                'invoice_id'     => $invoice_id,
                'merchant_key'   => method_exists( $provider, 'get_merchant_key' ) ? $provider->get_merchant_key() : '',
                'campaign_id'    => method_exists( $provider, 'get_campaign_id' ) ? $provider->get_campaign_id() : '',
                'created_by'     => get_current_user_id(),
            ]
        );

        $this->logger->info(
            $provider->get_key(),
            'create_application_started',
            'Admin started installment application creation.',
            [
                'request_id' => $request_id,
                'context'    => [
                    'product_name' => $product_name,
                    'price_total'  => $price_total,
                    'quantity'     => $quantity,
                    'invoice_id'   => $invoice_id,
                    'user_id'      => get_current_user_id(),
                ],
            ]
        );

        $result = $provider->create_application(
            [
                'product_name'          => $product_name,
                'price_total'           => $price_total,
                'quantity'              => $quantity,
                'invoice_id'            => $invoice_id,
                'bog_installment_month' => $bog_installment_month,
                'bog_installment_type'  => $bog_installment_type,
            ]
        );

        if ( is_wp_error( $result ) ) {
            $data = $result->get_error_data();

            $this->logger->error(
                $provider->get_key(),
                'create_application_failed',
                $result->get_error_message(),
                [
                    'request_id'       => $request_id,
                    'request_url'      => isset( $data['request_url'] ) ? $data['request_url'] : null,
                    'request_method'   => 'POST',
                    'request_body'     => isset( $data['request_payload'] ) ? $data['request_payload'] : null,
                    'response_code'    => isset( $data['response_code'] ) ? $data['response_code'] : null,
                    'response_headers' => isset( $data['response_headers'] ) ? (array) $data['response_headers'] : null,
                    'response_body'    => isset( $data['response_body'] ) ? $data['response_body'] : null,
                ]
            );

            $this->update_request(
                $request_id,
                [
                    'response_code' => isset( $data['response_code'] ) ? (int) $data['response_code'] : null,
                    'response_body' => isset( $data['response_body'] ) ? $data['response_body'] : $result->get_error_message(),
                    'updated_at'    => current_time( 'mysql' ),
                ]
            );

            $this->redirect_with_notice( 'error', strtoupper( $provider->get_key() ) . ' მოთხოვნა ვერ შეიქმნა. დეტალები იხილე ლოგებში.' );
        }

        $this->update_request(
            $request_id,
            [
                'session_id'        => $result['session_id'],
                'redirect_url'      => $result['redirect_url'],
                'request_payload'   => wp_json_encode( $result['request_payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'response_code'     => $result['response_code'],
                'response_headers'  => wp_json_encode( $this->headers_to_array( $result['response_headers'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'response_body'     => is_string( $result['response_body_raw'] ) ? $result['response_body_raw'] : wp_json_encode( $result['response_body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'updated_at'        => current_time( 'mysql' ),
            ]
        );

        $this->logger->info(
            $provider->get_key(),
            'create_application_success',
            'Installment application created successfully.',
            [
                'request_id'       => $request_id,
                'request_url'      => $result['request_url'],
                'request_method'   => $result['request_method'],
                'request_headers'  => $result['request_headers'],
                'request_body'     => $result['request_payload'],
                'response_code'    => $result['response_code'],
                'response_headers' => $this->headers_to_array( $result['response_headers'] ),
                'response_body'    => $result['response_body'],
                'context'          => [
                    'session_id'   => $result['session_id'],
                    'redirect_url' => $result['redirect_url'],
                ],
            ]
        );

        if ( ! empty( $result['redirect_url'] ) && filter_var( $result['redirect_url'], FILTER_VALIDATE_URL ) ) {
            wp_redirect( $result['redirect_url'] );
            exit;
        }

        $this->redirect_with_notice( 'success', strtoupper( $provider->get_key() ) . ' მოთხოვნა წარმატებით შეიქმნა. მაგრამ redirect URL არ დაბრუნდა. დეტალები იხილე ლოგებში.' );
    }

    public function handle_check_status() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'წვდომა აკრძალულია.' );
        }

        check_admin_referer( 'wi_installments_check_status', 'wi_installments_status_nonce' );

        $request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;
        if ( $request_id <= 0 ) {
            $this->redirect_with_notice( 'error', 'არასწორი მოთხოვნის ID.' );
        }

        $request = $this->get_request( $request_id );
        if ( ! $request || empty( $request['provider'] ) ) {
            $this->redirect_with_notice( 'error', 'სტატუსის შემოწმება ვერ მოხერხდა.' );
        }

        $provider = $this->get_provider( $request['provider'] );
        if ( ! $provider ) {
            $this->redirect_with_notice( 'error', 'provider ვერ მოიძებნა.' );
        }

        $result = $provider->get_application_status( $request );

        if ( is_wp_error( $result ) ) {
            $data = $result->get_error_data();
            $this->logger->error(
                $provider->get_key(),
                'check_status_failed',
                $result->get_error_message(),
                [
                    'request_id'       => $request_id,
                    'request_url'      => isset( $data['request_url'] ) ? $data['request_url'] : null,
                    'request_method'   => 'GET',
                    'response_code'    => isset( $data['response_code'] ) ? $data['response_code'] : null,
                    'response_headers' => isset( $data['response_headers'] ) ? (array) $data['response_headers'] : null,
                    'response_body'    => isset( $data['response_body'] ) ? $data['response_body'] : null,
                ]
            );

            $this->redirect_with_notice( 'error', 'სტატუსის მიღება ვერ მოხერხდა. იხილე ლოგები.' );
        }

        $status_id   = isset( $result['response_body']['statusId'] ) ? (int) $result['response_body']['statusId'] : null;
        $status_text = isset( $result['response_body']['statusDescription'] ) ? sanitize_text_field( $result['response_body']['statusDescription'] ) : ( isset( $result['response_body']['message'] ) ? sanitize_text_field( $result['response_body']['message'] ) : '' );

        $this->update_request(
            $request_id,
            [
                'remote_status_id'   => $status_id,
                'remote_status_text' => $status_text,
                'last_checked_at'    => current_time( 'mysql' ),
                'updated_at'         => current_time( 'mysql' ),
                'response_code'      => $result['response_code'],
                'response_headers'   => wp_json_encode( $this->headers_to_array( $result['response_headers'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'response_body'      => is_string( $result['response_body_raw'] ) ? $result['response_body_raw'] : wp_json_encode( $result['response_body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
            ]
        );

        $this->logger->info(
            $provider->get_key(),
            'check_status_success',
            'Installment application status checked successfully.',
            [
                'request_id'       => $request_id,
                'request_url'      => $result['request_url'],
                'request_method'   => $result['request_method'],
                'request_headers'  => $result['request_headers'],
                'response_code'    => $result['response_code'],
                'response_headers' => $this->headers_to_array( $result['response_headers'] ),
                'response_body'    => $result['response_body'],
                'context'          => [
                    'status_id'   => $status_id,
                    'status_text' => $status_text,
                ],
            ]
        );

        $this->redirect_with_notice( 'success', 'სტატუსი განახლდა.' );
    }

    private function get_provider( $key ) {
        return isset( $this->providers[ $key ] ) ? $this->providers[ $key ] : null;
    }

    private function get_settings() {
        $defaults = [
            'mode'                  => 'test',
            'tbc_api_key'           => '',
            'tbc_api_secret'        => '',
            'tbc_merchant_key'      => '',
            'tbc_campaign_id'       => '',
            'credo_enabled'         => 0,
            'credo_merchant_id'     => '',
            'credo_secret_password' => '',
            'credo_create_url'      => 'https://ganvadeba.credo.ge/widget_api/order.php',
            'credo_status_url'      => 'https://ganvadeba.credo.ge/widget/api.php',
            'bog_enabled'           => 0,
            'bog_client_id'         => '',
            'bog_secret_key'        => '',

        ];

        return wp_parse_args( get_option( self::SETTINGS_OPTION, [] ), $defaults );
    }

    private function render_flash_notice() {
        if ( empty( $_GET['wi_notice'] ) || empty( $_GET['wi_message'] ) ) {
            return;
        }

        $class   = 'success' === sanitize_key( wp_unslash( $_GET['wi_notice'] ) ) ? 'notice-success' : 'notice-error';
        $message = sanitize_text_field( wp_unslash( $_GET['wi_message'] ) );

        echo '<div class="notice ' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
    }

    public function handle_clear_history() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'წვდომა აკრძალულია.' );
        }

        check_admin_referer( 'wi_installments_clear_history', 'wi_installments_clear_history_nonce' );

        global $wpdb;

        $deleted = $wpdb->query( 'TRUNCATE TABLE ' . WI_Installments_DB::requests_table() );

        $message = false !== $deleted
            ? 'მოთხოვნების ისტორია წარმატებით გასუფთავდა.'
            : 'ისტორიის გასუფთავება ვერ მოხერხდა.';

        $this->redirect_with_notice( false !== $deleted ? 'success' : 'error', $message, 'wi-installments-hub-history' );
    }

    public function handle_clear_logs() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'წვდომა აკრძალულია.' );
        }

        check_admin_referer( 'wi_installments_clear_logs', 'wi_installments_clear_logs_nonce' );

        $clear_db_logs   = ! empty( $_POST['clear_db_logs'] );
        $clear_file_logs = ! empty( $_POST['clear_file_logs'] );

        $db_result     = null;
        $files_deleted = 0;

        if ( $clear_db_logs ) {
            $db_result = $this->logger->clear_logs_table();
        }

        if ( $clear_file_logs ) {
            $files_deleted = $this->logger->clear_log_files();
        }

        $parts = [];

        if ( $clear_db_logs ) {
            $parts[] = false !== $db_result ? 'DB ლოგები გასუფთავდა' : 'DB ლოგების გასუფთავება ვერ მოხერხდა';
        }

        if ( $clear_file_logs ) {
            $parts[] = 'წაიშალა ' . (int) $files_deleted . ' ლოგ ფაილი';
        }

        $message = ! empty( $parts ) ? implode( '. ', $parts ) . '.' : 'გასასუფთავებელი არაფერი იყო არჩეული.';
        $notice  = ( $clear_db_logs && false === $db_result ) ? 'error' : 'success';

        $this->redirect_with_notice( $notice, $message, 'wi-installments-hub-logs' );
    }


    private function redirect_with_notice( $notice, $message ) {
        $page = isset( $_REQUEST['provider'] ) && 'credo' === sanitize_key( wp_unslash( $_REQUEST['provider'] ) ) ? 'wi-installments-hub-credo' : 'wi-installments-hub-tbc';
        if ( isset( $_REQUEST['provider'] ) && 'bog' === sanitize_key( wp_unslash( $_REQUEST['provider'] ) ) ) {
            $page = 'wi-installments-hub-bog';
        }
        if ( isset( $_REQUEST['request_id'] ) ) {
            $request = $this->get_request( (int) $_REQUEST['request_id'] );
            if ( $request && 'credo' === $request['provider'] ) {
                $page = 'wi-installments-hub-credo';
            }
            if ( $request && 'bog' === $request['provider'] ) {
                $page = 'wi-installments-hub-bog';
            }
        }

        $url = add_query_arg(
            [
                'page'       => $page,
                'wi_notice'  => sanitize_key( $notice ),
                'wi_message' => rawurlencode( $message ),
            ],
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $url );
        exit;
    }

    private function insert_request( array $data ) {
        global $wpdb;
        $now = current_time( 'mysql' );

        $wpdb->insert(
            WI_Installments_DB::requests_table(),
            [
                'provider'       => $data['provider'],
                'provider_label' => $data['provider_label'],
                'mode'           => $data['mode'],
                'product_name'   => $data['product_name'],
                'quantity'       => $data['quantity'],
                'price_total'    => $data['price_total'],
                'invoice_id'     => $data['invoice_id'],
                'merchant_key'   => $data['merchant_key'],
                'campaign_id'    => $data['campaign_id'],
                'created_by'     => $data['created_by'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%d', '%s', '%s' ]
        );

        return (int) $wpdb->insert_id;
    }

    private function update_request( $request_id, array $data ) {
        global $wpdb;

        $wpdb->update(
            WI_Installments_DB::requests_table(),
            $data,
            [ 'id' => (int) $request_id ]
        );
    }

    private function get_request( $request_id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . WI_Installments_DB::requests_table() . ' WHERE id = %d', $request_id ),
            ARRAY_A
        );
    }

    private function get_latest_request( $provider ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . WI_Installments_DB::requests_table() . ' WHERE provider = %s ORDER BY id DESC LIMIT 1', $provider ),
            ARRAY_A
        );
    }

    private function headers_to_array( $headers ) {
        if ( is_object( $headers ) && method_exists( $headers, 'getAll' ) ) {
            return $headers->getAll();
        }

        return is_array( $headers ) ? $headers : [];
    }
}
