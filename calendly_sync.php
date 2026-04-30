<?php
/**
 * @file        calendly_sync.php
 * @package     Perfex CRM — Calendly Master Sync Module
 * @version     1.0.0
 * @author      Bytesis
 *
 * Module bootstrap. Loaded by Perfex CRM on every request when the module is
 * active. Defines constants, registers CSRF exclusions, language files, hooks,
 * sidebar menu, and staff permissions.
 */

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Calendly Master Sync
Description: Real-time Calendly meeting sync for Perfex CRM via webhooks
Version: 1.0.0
Requires at least: 2.3.*
Author: Bytesis
*/

// ─── Module identity ──────────────────────────────────────────────────────────

define('CALENDLY_SYNC_VERSION',     '1.0.0');
define('CALENDLY_SYNC_MODULE_NAME', 'calendly_sync');
define('CALENDLY_SYNC_MODULE_PATH', dirname(__FILE__) . '/');

// ─── Calendly API base URL ────────────────────────────────────────────────────

define('CALENDLY_API_BASE', 'https://api.calendly.com');

// ─── CSRF: allow Calendly to POST webhooks without a CSRF token ───────────────

hooks()->add_filter('csrf_exclude_uris', 'calendly_sync_csrf_exclude_uris');

function calendly_sync_csrf_exclude_uris(array $exclude_uris): array
{
    $exclude_uris[] = 'calendly_sync/webhook';
    $exclude_uris[] = 'admin/calendly_sync/webhook';
    return $exclude_uris;
}

// ─── Language ─────────────────────────────────────────────────────────────────

register_language_files(CALENDLY_SYNC_MODULE_NAME, [CALENDLY_SYNC_MODULE_NAME]);

// ─── Activation / Deactivation ────────────────────────────────────────────────

register_activation_hook(CALENDLY_SYNC_MODULE_NAME, 'calendly_sync_activation_hook');
register_deactivation_hook(CALENDLY_SYNC_MODULE_NAME, 'calendly_sync_deactivation_hook');

function calendly_sync_activation_hook(): void
{
    require_once(CALENDLY_SYNC_MODULE_PATH . 'install.php');
}

function calendly_sync_deactivation_hook(): void
{
    // No-op. Preserve tblcalendly_events data between activations.
}

// ─── Admin init: sidebar menu + permissions ───────────────────────────────────

hooks()->add_action('admin_init', 'calendly_sync_init_menu_items');
hooks()->add_action('admin_init', 'calendly_sync_permissions');

function calendly_sync_init_menu_items(): void
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('calendly-sync', [
        'name'     => _l('calendly_sync_menu'),
        'href'     => admin_url('calendly_sync'),
        'icon'     => 'fa fa-calendar',
        'position' => 13,
    ]);

    $CI->app_menu->add_sidebar_children_item('calendly-sync', [
        'slug'     => 'calendly-sync-dashboard',
        'name'     => _l('calendly_sync_dashboard'),
        'href'     => admin_url('calendly_sync'),
        'icon'     => 'fa fa-tachometer',
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('calendly-sync', [
        'slug'     => 'calendly-sync-settings',
        'name'     => _l('calendly_sync_settings'),
        'href'     => admin_url('calendly_sync/settings'),
        'icon'     => 'fa fa-cog',
        'position' => 2,
    ]);
}

function calendly_sync_permissions(): void
{
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . ' (' . _l('permission_global') . ')',
    ];
    register_staff_capabilities(CALENDLY_SYNC_MODULE_NAME, $capabilities, _l('calendly_sync_menu'));
}

// ─── Dashboard widget ─────────────────────────────────────────────────────────

hooks()->add_action('app_admin_dashboard_widgets', 'calendly_sync_dashboard_widget');

function calendly_sync_dashboard_widget(): void
{
    if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
        return;
    }
    $CI = &get_instance();
    $CI->load->model('calendly_sync/calendly_sync_model');
    $events = $CI->calendly_sync_model->get_upcoming_events(5);
    include(CALENDLY_SYNC_MODULE_PATH . 'views/widget.php');
}
