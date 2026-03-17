<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WI_Installments_DB {
    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $requests_table  = self::requests_table();
        $logs_table      = self::logs_table();

        $sql_requests = "CREATE TABLE {$requests_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(50) NOT NULL,
            provider_label VARCHAR(100) NOT NULL,
            mode VARCHAR(20) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            price_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            invoice_id VARCHAR(191) NOT NULL,
            merchant_key VARCHAR(191) NULL,
            campaign_id VARCHAR(50) NULL,
            session_id VARCHAR(191) NULL,
            redirect_url TEXT NULL,
            request_payload LONGTEXT NULL,
            response_code INT NULL,
            response_headers LONGTEXT NULL,
            response_body LONGTEXT NULL,
            remote_status_id INT NULL,
            remote_status_text VARCHAR(255) NULL,
            last_checked_at DATETIME NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY provider (provider),
            KEY session_id (session_id),
            KEY invoice_id (invoice_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_logs = "CREATE TABLE {$logs_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id BIGINT UNSIGNED NULL,
            provider VARCHAR(50) NOT NULL,
            action_name VARCHAR(100) NOT NULL,
            level VARCHAR(20) NOT NULL DEFAULT 'info',
            message TEXT NOT NULL,
            request_url TEXT NULL,
            request_method VARCHAR(10) NULL,
            request_headers LONGTEXT NULL,
            request_body LONGTEXT NULL,
            response_code INT NULL,
            response_headers LONGTEXT NULL,
            response_body LONGTEXT NULL,
            context LONGTEXT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY request_id (request_id),
            KEY provider (provider),
            KEY action_name (action_name),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql_requests );
        dbDelta( $sql_logs );
    }

    public static function requests_table() {
        global $wpdb;
        return $wpdb->prefix . 'wi_installment_requests';
    }

    public static function logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'wi_installment_logs';
    }
}
