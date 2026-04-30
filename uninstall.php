<?php
/**
 * @file        uninstall.php
 * @package     Perfex CRM — Calendly Master Sync Module
 *
 * Uninstallation script — executed by Perfex when the module is deleted.
 * Drops the calendly_events table and removes all stored module options.
 */

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// ─── Drop events table ────────────────────────────────────────────────────────

$table = db_prefix() . 'calendly_events';
if ($CI->db->table_exists($table)) {
    $CI->db->query('DROP TABLE `' . $table . '`');
    log_message('info', '[calendly_sync] Dropped table ' . $table);
}

// ─── Remove module options ────────────────────────────────────────────────────

$options_table = db_prefix() . 'options';
$keys = [
    'calendly_api_token',
    'calendly_display_limit',
    'calendly_webhook_uuid',
    'calendly_webhook_signing_key',
    'calendly_org_uri',
];

foreach ($keys as $key) {
    $CI->db->where('name', $key)->delete($options_table);
}

log_message('info', '[calendly_sync] Module uninstalled. All data removed.');
