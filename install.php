<?php
/**
 * @file        install.php
 * @package     Perfex CRM — Calendly Master Sync Module
 *
 * Activation script — executed once by calendly_sync_activation_hook().
 * Creates the calendly_events table and secures the logs/ directory.
 * Idempotent: safe to re-run on an existing installation.
 */

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// ─── calendly_events table ────────────────────────────────────────────────────

$table = db_prefix() . 'calendly_events';

if (!$CI->db->table_exists($table)) {
    $CI->db->query("
        CREATE TABLE `{$table}` (
            `id`            INT(11)      NOT NULL AUTO_INCREMENT,
            `event_uuid`    VARCHAR(255) NOT NULL DEFAULT '',
            `invitee_name`  VARCHAR(191) NOT NULL DEFAULT '',
            `invitee_email` VARCHAR(191) NOT NULL DEFAULT '',
            `start_time`    DATETIME     DEFAULT NULL,
            `end_time`      DATETIME     DEFAULT NULL,
            `join_url`      TEXT         DEFAULT NULL,
            `event_type`    VARCHAR(100) NOT NULL DEFAULT '',
            `status`        VARCHAR(20)  NOT NULL DEFAULT 'active',
            `lead_id`       INT(11)      DEFAULT NULL,
            `client_id`     INT(11)      DEFAULT NULL,
            `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_event_uuid` (`event_uuid`),
            KEY `idx_start_time` (`start_time`),
            KEY `idx_status` (`status`),
            KEY `idx_invitee_email` (`invitee_email`),
            KEY `idx_lead_id` (`lead_id`),
            KEY `idx_client_id` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    log_message('info', '[calendly_sync] Created table ' . $table);
}

// ─── Secure logs directory ────────────────────────────────────────────────────

$log_dir = CALENDLY_SYNC_MODULE_PATH . 'logs/';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

// Deny direct HTTP access to log files containing PII webhook payloads
if (!file_exists($log_dir . '.htaccess')) {
    file_put_contents($log_dir . '.htaccess', "Order Deny,Allow\nDeny from all\n");
}
if (!file_exists($log_dir . 'index.html')) {
    file_put_contents($log_dir . 'index.html', '');
}

log_message('info', '[calendly_sync] Module activated / upgraded.');
