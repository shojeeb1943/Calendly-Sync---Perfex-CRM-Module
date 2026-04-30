<?php
/**
 * @file        controllers/Calendly_sync.php
 * @package     Perfex CRM — Calendly Master Sync Module
 *
 * Routes:
 *  GET  admin/calendly_sync                         → index()           Dashboard
 *  GET  admin/calendly_sync/settings                → settings()        Settings form
 *  POST admin/calendly_sync/save_settings           → save_settings()   Persist settings
 *  POST admin/calendly_sync/setup_webhook           → setup_webhook()   Register with Calendly API
 *  POST admin/calendly_sync/delete_webhook          → delete_webhook()  Unregister webhook
 *  POST admin/calendly_sync/sync_past               → sync_past()       Fetch historical events
 *  POST admin/calendly_sync/get_events_dt           → get_events_dt()   DataTables AJAX source
 *  POST admin/calendly_sync/webhook                 → webhook()         Calendly callback (CSRF-free)
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Calendly_sync extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('calendly_sync/calendly_sync_model');
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    /**
     * @route GET admin/calendly_sync
     */
    public function index(): void
    {
        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            access_denied(CALENDLY_SYNC_MODULE_NAME);
        }

        $data['stats'] = $this->calendly_sync_model->get_stats();
        $data['title'] = _l('calendly_sync_page_title');

        $this->load->view('calendly_sync/dashboard', $data);
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    /**
     * @route GET admin/calendly_sync/settings
     */
    public function settings(): void
    {
        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            access_denied(CALENDLY_SYNC_MODULE_NAME);
        }

        $data['api_token']     = $this->calendly_sync_model->get_setting('calendly_api_token');
        $data['display_limit'] = $this->calendly_sync_model->get_setting('calendly_display_limit') ?: '20';
        $data['webhook_uuid']  = $this->calendly_sync_model->get_setting('calendly_webhook_uuid');
        $data['signing_key']   = $this->calendly_sync_model->get_setting('calendly_webhook_signing_key');
        $data['webhook_url']   = base_url('admin/calendly_sync/webhook');
        $data['title']         = _l('calendly_sync_settings_title');

        $this->load->view('calendly_sync/settings', $data);
    }

    /**
     * @route POST admin/calendly_sync/save_settings
     */
    public function save_settings(): void
    {
        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            access_denied(CALENDLY_SYNC_MODULE_NAME);
        }

        $token = $this->input->post('calendly_api_token');
        $limit = (int) $this->input->post('calendly_display_limit');

        if ($token !== null) {
            $this->calendly_sync_model->save_setting('calendly_api_token', trim($token));
        }
        if ($limit > 0) {
            $this->calendly_sync_model->save_setting('calendly_display_limit', (string) $limit);
        }

        set_alert('success', _l('calendly_sync_saved'));
        redirect(admin_url('calendly_sync/settings'));
    }

    // ─── Webhook management ───────────────────────────────────────────────────

    /**
     * Programmatically register this CRM's webhook URL with the Calendly API.
     *
     * @route POST admin/calendly_sync/setup_webhook
     */
    public function setup_webhook(): void
    {
        header('Content-Type: application/json');

        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $token = $this->calendly_sync_model->get_setting('calendly_api_token');
        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'No API token saved. Please save your Personal Access Token first.']);
            return;
        }

        // Step 1: get current user to find org URI
        $me = $this->_calendly_get('/users/me', $token);
        if (!$me || empty($me['resource']['current_organization'])) {
            $msg = $me === null
                ? 'Could not connect to the Calendly API. Check your server SSL/network settings.'
                : 'Could not fetch user info. Verify your Personal Access Token.';
            echo json_encode(['success' => false, 'message' => _l('calendly_sync_webhook_error') . $msg]);
            return;
        }
        $org_uri = $me['resource']['current_organization'];
        $this->calendly_sync_model->save_setting('calendly_org_uri', $org_uri);

        // Step 2: create webhook subscription
        $payload = [
            'url'          => base_url('admin/calendly_sync/webhook'),
            'events'       => ['invitee.created', 'invitee.canceled'],
            'organization' => $org_uri,
            'scope'        => 'organization',
        ];

        $result = $this->_calendly_post('/webhook_subscriptions', $token, $payload);

        if (!$result || empty($result['resource']['uri'])) {
            $msg = $result === null
                ? 'Could not connect to the Calendly API.'
                : ($result['message'] ?? 'Unknown error from Calendly API.');
            echo json_encode(['success' => false, 'message' => _l('calendly_sync_webhook_error') . $msg]);
            return;
        }

        $uri  = $result['resource']['uri'];
        $uuid = basename($uri);
        $skey = $result['resource']['signing_key'] ?? '';

        $this->calendly_sync_model->save_setting('calendly_webhook_uuid', $uuid);
        $this->calendly_sync_model->save_setting('calendly_webhook_signing_key', $skey);

        echo json_encode(['success' => true, 'message' => _l('calendly_sync_webhook_registered')]);
    }

    /**
     * Delete the registered webhook from Calendly.
     *
     * @route POST admin/calendly_sync/delete_webhook
     */
    public function delete_webhook(): void
    {
        header('Content-Type: application/json');

        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $uuid  = $this->calendly_sync_model->get_setting('calendly_webhook_uuid');
        $token = $this->calendly_sync_model->get_setting('calendly_api_token');

        if (empty($uuid) || empty($token)) {
            echo json_encode(['success' => false, 'message' => 'No webhook is currently registered.']);
            return;
        }

        $ok = $this->_calendly_delete('/webhook_subscriptions/' . $uuid, $token);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => _l('calendly_sync_webhook_error') . 'Could not reach the Calendly API. Check logs for details.']);
            return;
        }

        $this->calendly_sync_model->save_setting('calendly_webhook_uuid', '');
        $this->calendly_sync_model->save_setting('calendly_webhook_signing_key', '');

        echo json_encode(['success' => true, 'message' => _l('calendly_sync_webhook_deleted')]);
    }

    // ─── Webhook receiver (no CSRF) ───────────────────────────────────────────

    /**
     * Receives Calendly event notifications and stores them locally.
     *
     * @route POST admin/calendly_sync/webhook
     */
    public function webhook(): void
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        // Verify HMAC signature
        $signing_key = $this->calendly_sync_model->get_setting('calendly_webhook_signing_key');
        if (!empty($signing_key) && !$this->_verify_signature($signing_key, $raw)) {
            $this->_log_webhook(['error' => 'invalid_signature', 'raw' => $raw]);
            http_response_code(403);
            echo json_encode(['status' => 'forbidden']);
            return;
        }

        // Respond immediately so Calendly does not retry
        ob_start();
        echo json_encode(['status' => 'ok']);
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        header('Content-Type: application/json');
        ob_end_flush();
        @ob_flush();
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // ── Async processing ──────────────────────────────────────────────────

        $this->_log_webhook($data);

        $event_type = $data['event'] ?? '';

        if ($event_type === 'invitee.created') {
            $this->_handle_invitee_created($data['payload'] ?? []);
        } elseif ($event_type === 'invitee.canceled') {
            $this->_handle_invitee_canceled($data['payload'] ?? []);
        }
    }

    // ─── Sync past meetings ───────────────────────────────────────────────────

    /**
     * Fetches historical events from the Calendly API and stores them locally.
     *
     * @route POST admin/calendly_sync/sync_past
     */
    public function sync_past(): void
    {
        header('Content-Type: application/json');

        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $token   = $this->calendly_sync_model->get_setting('calendly_api_token');
        $org_uri = $this->calendly_sync_model->get_setting('calendly_org_uri');

        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'No API token configured.']);
            return;
        }

        if (empty($org_uri)) {
            $me = $this->_calendly_get('/users/me', $token);
            if ($me === null) {
                echo json_encode(['success' => false, 'message' => 'Could not connect to the Calendly API.']);
                return;
            }
            $org_uri = $me['resource']['current_organization'] ?? '';
            if ($org_uri) {
                $this->calendly_sync_model->save_setting('calendly_org_uri', $org_uri);
            }
        }

        $params = http_build_query([
            'organization' => $org_uri,
            'status'       => 'active',
            'count'        => 100,
            'sort'         => 'start_time:desc',
        ]);

        $result = $this->_calendly_get('/scheduled_events?' . $params, $token);

        if ($result === null) {
            echo json_encode(['success' => false, 'message' => 'Could not connect to the Calendly API.']);
            return;
        }

        if (empty($result['collection'])) {
            echo json_encode(['success' => true, 'synced' => 0, 'message' => 'No events found.']);
            return;
        }

        $synced = 0;
        foreach ($result['collection'] as $event) {
            $this->_store_event_from_api($event, $token);
            $synced++;
        }

        echo json_encode(['success' => true, 'synced' => $synced, 'message' => "Synced {$synced} event(s)."]);
    }

    // ─── DataTables AJAX source ───────────────────────────────────────────────

    /**
     * Returns server-side paginated events for the dashboard DataTable.
     *
     * @route POST admin/calendly_sync/get_events_dt
     */
    public function get_events_dt(): void
    {
        header('Content-Type: application/json');

        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            return;
        }

        $draw   = (int) $this->input->post('draw');
        $start  = max(0, (int) $this->input->post('start'));
        $length = max(1, (int) ($this->input->post('length') ?: 25));
        $search = $this->input->post('search');
        $term   = is_array($search) ? trim($search['value'] ?? '') : '';

        // Map DataTables column index → DB column (only sortable columns)
        $col_map = [
            0 => 'start_time',
            1 => 'invitee_name',
            2 => 'event_type',
            4 => 'status',
        ];
        $order_raw = $this->input->post('order');
        $order_idx = is_array($order_raw) ? (int) ($order_raw[0]['column'] ?? 0) : 0;
        $order_dir = is_array($order_raw) && strtolower($order_raw[0]['dir'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $sort_col  = $col_map[$order_idx] ?? 'start_time';

        $total    = $this->calendly_sync_model->count_events();
        $filtered = $term !== '' ? $this->calendly_sync_model->count_events_filtered($term) : $total;
        $rows     = $this->calendly_sync_model->get_events_paged($start, $length, $term, $sort_col, $order_dir);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                $this->_dt_cell_time($row),
                $this->_dt_cell_invitee($row),
                htmlspecialchars($row['event_type']),
                $this->_dt_cell_platform($row),
                $this->_dt_cell_contact($row),
                $this->_dt_cell_action($row),
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    // ─── Tabbed events list AJAX source ──────────────────────────────────────

    /**
     * Returns JSON events for the Calendly-style tabbed list view.
     *
     * @route POST admin/calendly_sync/get_events_list
     */
    public function get_events_list(): void
    {
        header('Content-Type: application/json');

        if (!staff_can('view', CALENDLY_SYNC_MODULE_NAME)) {
            echo json_encode(['success' => false, 'events' => []]);
            return;
        }

        $tab       = $this->input->post('tab') ?: 'upcoming';
        $date_from = (string) ($this->input->post('date_from') ?: '');
        $date_to   = (string) ($this->input->post('date_to') ?: '');

        switch ($tab) {
            case 'past':
                $events = $this->calendly_sync_model->get_past_events_list(200);
                break;
            case 'date_range':
                $events = ($date_from !== '' && $date_to !== '')
                    ? $this->calendly_sync_model->get_events_by_date_range($date_from, $date_to)
                    : [];
                break;
            default:
                $events = $this->calendly_sync_model->get_upcoming_events_list(200);
        }

        echo json_encode(['success' => true, 'events' => $events]);
    }

    // ─── Private: DataTable cell formatters ───────────────────────────────────

    private function _dt_cell_time(array $row): string
    {
        if (!$row['start_time']) {
            return '&mdash;';
        }
        $ts   = strtotime($row['start_time']);
        $diff = (int) round(($ts - time()) / 60);
        $out  = '<strong>' . date('M j, Y H:i', $ts) . '</strong>';
        if ($diff > 0 && $diff <= 120) {
            $out .= '<br><small class="text-warning"><i class="fa fa-clock-o"></i> '
                . _l('calendly_sync_starts_in') . ' ' . $diff . ' ' . _l('calendly_sync_minutes') . '</small>';
        }
        return $out;
    }

    private function _dt_cell_invitee(array $row): string
    {
        return '<strong>' . htmlspecialchars($row['invitee_name']) . '</strong>'
            . '<br><small class="text-muted">' . htmlspecialchars($row['invitee_email']) . '</small>';
    }

    private function _dt_cell_platform(array $row): string
    {
        $url = strtolower($row['join_url'] ?? '');
        if (!$url) {
            return '&mdash;';
        }
        if (strpos($url, 'zoom.us') !== false) {
            return '<i class="fa fa-video-camera text-primary"></i> Zoom';
        }
        if (strpos($url, 'meet.google') !== false) {
            return '<i class="fa fa-google text-danger"></i> Meet';
        }
        if (strpos($url, 'teams.microsoft') !== false) {
            return '<i class="fa fa-windows text-primary"></i> Teams';
        }
        return '<i class="fa fa-link text-muted"></i>';
    }

    private function _dt_cell_contact(array $row): string
    {
        if ($row['lead_id']) {
            return '<a href="' . admin_url('leads/index/' . (int) $row['lead_id']) . '" target="_blank" class="label label-info">'
                . '<i class="fa fa-user"></i> ' . _l('calendly_sync_known_contact') . '</a>';
        }
        if ($row['client_id']) {
            return '<a href="' . admin_url('clients/client/' . (int) $row['client_id']) . '" target="_blank" class="label label-success">'
                . '<i class="fa fa-building"></i> ' . _l('calendly_sync_known_contact') . '</a>';
        }
        return '<span class="text-muted">&mdash;</span>';
    }

    private function _dt_cell_action(array $row): string
    {
        if ($row['status'] !== 'active') {
            return '<span class="label label-danger">' . _l('calendly_sync_canceled') . '</span>';
        }
        if (!empty($row['join_url'])) {
            return '<a href="' . htmlspecialchars($row['join_url']) . '" target="_blank" class="btn btn-success btn-xs">'
                . '<i class="fa fa-sign-in"></i> ' . _l('calendly_sync_join_meeting') . '</a>';
        }
        return '&mdash;';
    }

    // ─── Private: webhook handlers ────────────────────────────────────────────

    private function _handle_invitee_created(array $payload): void
    {
        $scheduled = $payload['scheduled_event'] ?? [];
        $uuid      = $this->_extract_uuid($scheduled['uri'] ?? '');

        if (!$uuid) {
            return;
        }

        $join_url   = $scheduled['location']['join_url'] ?? '';
        $event_name = $scheduled['name'] ?? '';
        $start_raw  = $scheduled['start_time'] ?? '';
        $end_raw    = $scheduled['end_time'] ?? '';

        $data = [
            'event_uuid'    => $uuid,
            'invitee_name'  => $payload['name'] ?? '',
            'invitee_email' => $payload['email'] ?? '',
            'start_time'    => $start_raw ? date('Y-m-d H:i:s', strtotime($start_raw)) : null,
            'end_time'      => $end_raw   ? date('Y-m-d H:i:s', strtotime($end_raw))   : null,
            'join_url'      => $join_url,
            'event_type'    => $event_name,
            'status'        => 'active',
        ];

        $this->calendly_sync_model->upsert_event($data);

        $email     = $payload['email'] ?? '';
        $event_row = $this->calendly_sync_model->get_event_by_uuid($uuid);
        if ($event_row && $email) {
            $lead_id   = $this->calendly_sync_model->find_lead_by_email($email);
            $client_id = $this->calendly_sync_model->find_client_by_email($email);
            $this->calendly_sync_model->update_crm_refs($event_row['id'], $lead_id, $client_id);
        }
    }

    private function _handle_invitee_canceled(array $payload): void
    {
        $scheduled = $payload['scheduled_event'] ?? [];
        $uuid      = $this->_extract_uuid($scheduled['uri'] ?? '');

        if (!$uuid) {
            return;
        }

        $this->calendly_sync_model->cancel_event($uuid);
    }

    // ─── Private: sync helper ─────────────────────────────────────────────────

    private function _store_event_from_api(array $event, string $token): void
    {
        $uuid = $this->_extract_uuid($event['uri'] ?? '');
        if (!$uuid) {
            return;
        }

        $inv_result = $this->_calendly_get('/scheduled_events/' . $uuid . '/invitees?count=1', $token);
        $invitee    = $inv_result['collection'][0] ?? [];

        $join_url  = $event['location']['join_url'] ?? '';
        $start_raw = $event['start_time'] ?? '';
        $end_raw   = $event['end_time'] ?? '';
        $status    = ($event['status'] ?? 'active') === 'canceled' ? 'canceled' : 'active';

        $data = [
            'event_uuid'    => $uuid,
            'invitee_name'  => $invitee['name'] ?? '',
            'invitee_email' => $invitee['email'] ?? '',
            'start_time'    => $start_raw ? date('Y-m-d H:i:s', strtotime($start_raw)) : null,
            'end_time'      => $end_raw   ? date('Y-m-d H:i:s', strtotime($end_raw))   : null,
            'join_url'      => $join_url,
            'event_type'    => $event['name'] ?? '',
            'status'        => $status,
        ];

        $this->calendly_sync_model->upsert_event($data);

        $email     = $invitee['email'] ?? '';
        $event_row = $this->calendly_sync_model->get_event_by_uuid($uuid);
        if ($event_row && $email) {
            $lead_id   = $this->calendly_sync_model->find_lead_by_email($email);
            $client_id = $this->calendly_sync_model->find_client_by_email($email);
            $this->calendly_sync_model->update_crm_refs($event_row['id'], $lead_id, $client_id);
        }
    }

    // ─── Private: Calendly API helpers ────────────────────────────────────────

    private function _calendly_get(string $path, string $token): ?array
    {
        $ch = curl_init(CALENDLY_API_BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', '[calendly_sync] cURL GET ' . $path . ' failed: ' . $err);
            return null;
        }

        return $response ? json_decode($response, true) : null;
    }

    private function _calendly_post(string $path, string $token, array $payload): ?array
    {
        $ch = curl_init(CALENDLY_API_BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', '[calendly_sync] cURL POST ' . $path . ' failed: ' . $err);
            return null;
        }

        return $response ? json_decode($response, true) : null;
    }

    /**
     * Returns true on HTTP 2xx or 404 (already deleted); false on cURL error.
     */
    private function _calendly_delete(string $path, string $token): bool
    {
        $ch = curl_init(CALENDLY_API_BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            log_message('error', '[calendly_sync] cURL DELETE ' . $path . ' failed: ' . $err);
            return false;
        }

        // 204 = success, 404 = already gone — both are acceptable outcomes
        return ($code >= 200 && $code < 300) || $code === 404;
    }

    // ─── Private: signature verification ─────────────────────────────────────

    /**
     * Verify the Calendly-Webhook-Signature header.
     * Format: t=<timestamp>,v1=<hmac-sha256>
     * Signed content: <timestamp>.<raw_body>
     */
    private function _verify_signature(string $signing_key, string $raw_body): bool
    {
        $header = $_SERVER['HTTP_CALENDLY_WEBHOOK_SIGNATURE'] ?? '';
        if (empty($header)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $v1        = $parts['v1'] ?? '';

        if (empty($timestamp) || empty($v1)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $raw_body, $signing_key);
        return hash_equals($expected, $v1);
    }

    // ─── Private: utilities ───────────────────────────────────────────────────

    private function _extract_uuid(string $uri): string
    {
        return $uri ? basename($uri) : '';
    }

    private function _log_webhook(array $data): void
    {
        $log_dir = CALENDLY_SYNC_MODULE_PATH . 'logs/';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        file_put_contents(
            $log_dir . 'webhook_' . date('Y-m-d') . '.log',
            '[' . date('Y-m-d H:i:s') . '] ' . json_encode($data) . "\n",
            FILE_APPEND
        );
    }
}
