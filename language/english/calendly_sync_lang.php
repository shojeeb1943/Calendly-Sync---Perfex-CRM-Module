<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Menu & navigation
$lang['calendly_sync_menu']              = 'Calendly Sync';
$lang['calendly_sync_dashboard']         = 'Meetings Dashboard';
$lang['calendly_sync_settings']          = 'Settings';

// Dashboard headings
$lang['calendly_sync_page_title']        = 'Calendly Master Sync';
$lang['calendly_sync_subtitle']          = 'Real-time Calendly meeting overview';
$lang['calendly_sync_sync_past_btn']     = 'Sync Past Meetings';

// Stat cards
$lang['calendly_sync_stat_today']        = "Today's Meetings";
$lang['calendly_sync_stat_active']       = 'Total Active';
$lang['calendly_sync_stat_canceled']     = 'Canceled';
$lang['calendly_sync_stat_known']        = 'Known Contacts';

// Table columns
$lang['calendly_sync_col_time']          = 'Time';
$lang['calendly_sync_col_invitee']       = 'Invitee';
$lang['calendly_sync_col_type']          = 'Meeting Type';
$lang['calendly_sync_col_platform']      = 'Platform';
$lang['calendly_sync_col_contact']       = 'CRM Contact';
$lang['calendly_sync_col_action']        = 'Action';

// Badges & labels
$lang['calendly_sync_join_meeting']      = 'Join Meeting';
$lang['calendly_sync_known_contact']     = 'Known Contact';
$lang['calendly_sync_canceled']          = 'Canceled';
$lang['calendly_sync_starts_in']         = 'Starts in';
$lang['calendly_sync_minutes']           = 'min';
$lang['calendly_sync_no_meetings']       = 'No meetings found. They will appear here once Calendly sends a webhook.';

// Settings page
$lang['calendly_sync_settings_title']        = 'Calendly Sync Settings';
$lang['calendly_sync_general_settings']      = 'General Settings';
$lang['calendly_sync_api_token']             = 'Personal Access Token (PAT)';
$lang['calendly_sync_api_token_help']        = 'Generate from Calendly → Integrations → API & Webhooks';
$lang['calendly_sync_display_limit']         = 'Dashboard Display Limit';
$lang['calendly_sync_display_limit_help']    = 'Maximum number of meetings to show on the dashboard';
$lang['calendly_sync_save_settings']         = 'Save Settings';
$lang['calendly_sync_saved']                 = 'Settings saved successfully.';
$lang['calendly_sync_url_copied']            = 'Webhook URL copied to clipboard.';
$lang['calendly_sync_delete_webhook_confirm']= 'Remove the webhook from Calendly? Events will stop syncing.';

// Webhook setup
$lang['calendly_sync_webhook_section']   = 'Webhook Setup';
$lang['calendly_sync_webhook_url_label'] = 'Your Webhook Receiver URL';
$lang['calendly_sync_webhook_status']    = 'Webhook Status';
$lang['calendly_sync_webhook_active']    = 'Active';
$lang['calendly_sync_webhook_inactive']  = 'Not Registered';
$lang['calendly_sync_setup_webhook']     = 'Setup Webhook';
$lang['calendly_sync_delete_webhook']    = 'Remove Webhook';
$lang['calendly_sync_webhook_registered']= 'Webhook registered successfully!';
$lang['calendly_sync_webhook_deleted']   = 'Webhook removed.';
$lang['calendly_sync_webhook_error']     = 'Error communicating with Calendly API: ';

// Tabs
$lang['calendly_sync_tab_upcoming']      = 'Upcoming';
$lang['calendly_sync_tab_past']          = 'Past';
$lang['calendly_sync_tab_date_range']    = 'Date Range';

// Widget
$lang['calendly_sync_widget_title']      = 'Upcoming Meetings';
$lang['calendly_sync_widget_view_all']   = 'View All Meetings';
$lang['calendly_sync_widget_no_events']  = 'No upcoming meetings.';
