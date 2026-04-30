<?php
/**
 * @file        models/Calendly_sync_model.php
 * @package     Perfex CRM — Calendly Master Sync Module
 *
 * Data-access layer. All SQL lives here; no raw queries in the controller.
 * Uses db_prefix() throughout so installations with custom prefixes work correctly.
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Calendly_sync_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /**
     * Upcoming active meetings ordered by start_time ASC (used by the widget).
     */
    public function get_upcoming_events(int $limit = 20): array
    {
        return $this->db
            ->select('*')
            ->from(db_prefix() . 'calendly_events')
            ->where('status', 'active')
            ->where('start_time >=', date('Y-m-d H:i:s'))
            ->order_by('start_time', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * All events for legacy/fallback use (most recent first).
     */
    public function get_all_events(int $limit = 50): array
    {
        return $this->db
            ->select('*')
            ->from(db_prefix() . 'calendly_events')
            ->order_by('start_time', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Dashboard stat cards.
     */
    public function get_stats(): array
    {
        $today_start = date('Y-m-d') . ' 00:00:00';
        $today_end   = date('Y-m-d') . ' 23:59:59';
        $tbl         = db_prefix() . 'calendly_events';

        $total = $this->db->count_all($tbl);

        $today = $this->db
            ->where('start_time >=', $today_start)
            ->where('start_time <=', $today_end)
            ->where('status', 'active')
            ->count_all_results($tbl);

        $active = $this->db
            ->where('status', 'active')
            ->count_all_results($tbl);

        $canceled = $this->db
            ->where('status', 'canceled')
            ->count_all_results($tbl);

        $with_lead = $this->db
            ->where('lead_id IS NOT NULL', null, false)
            ->or_where('client_id IS NOT NULL', null, false)
            ->count_all_results($tbl);

        return compact('total', 'today', 'active', 'canceled', 'with_lead');
    }

    /**
     * Find a single event by its Calendly UUID.
     */
    public function get_event_by_uuid(string $uuid): ?array
    {
        $row = $this->db
            ->where('event_uuid', $uuid)
            ->get(db_prefix() . 'calendly_events')
            ->row_array();
        return $row ?: null;
    }

    // ─── DataTable server-side support ────────────────────────────────────────

    /**
     * Total row count (unfiltered) for DataTables recordsTotal.
     */
    public function count_events(): int
    {
        return (int) $this->db->count_all(db_prefix() . 'calendly_events');
    }

    /**
     * Row count matching a search term for DataTables recordsFiltered.
     */
    public function count_events_filtered(string $search): int
    {
        return (int) $this->db
            ->group_start()
            ->like('invitee_name', $search)
            ->or_like('invitee_email', $search)
            ->or_like('event_type', $search)
            ->group_end()
            ->count_all_results(db_prefix() . 'calendly_events');
    }

    /**
     * Paginated + filtered + sorted rows for DataTables AJAX response.
     *
     * @param string $sort  Column name (validated against allowlist before use)
     * @param string $dir   'ASC' or 'DESC'
     */
    public function get_events_paged(int $start, int $length, string $search = '', string $sort = 'start_time', string $dir = 'DESC'): array
    {
        // Allowlist prevents column-injection via DataTables order parameter
        $allowed = ['start_time', 'invitee_name', 'invitee_email', 'event_type', 'status'];
        $sort    = in_array($sort, $allowed, true) ? $sort : 'start_time';
        $dir     = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        if ($search !== '') {
            $this->db
                ->group_start()
                ->like('invitee_name', $search)
                ->or_like('invitee_email', $search)
                ->or_like('event_type', $search)
                ->group_end();
        }

        return $this->db
            ->select('*')
            ->from(db_prefix() . 'calendly_events')
            ->order_by($sort, $dir)
            ->limit($length, $start)
            ->get()
            ->result_array();
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Insert a new event or update the existing record (matched by event_uuid).
     */
    public function upsert_event(array $data): bool
    {
        $existing = $this->get_event_by_uuid($data['event_uuid']);

        if ($existing) {
            $this->db->where('event_uuid', $data['event_uuid']);
            return $this->db->update(db_prefix() . 'calendly_events', $data);
        }

        return $this->db->insert(db_prefix() . 'calendly_events', $data);
    }

    /**
     * Mark an event as canceled by its Calendly UUID.
     */
    public function cancel_event(string $uuid): bool
    {
        return $this->db
            ->where('event_uuid', $uuid)
            ->update(db_prefix() . 'calendly_events', ['status' => 'canceled']);
    }

    /**
     * Save CRM cross-reference IDs after email matching.
     */
    public function update_crm_refs(int $event_id, ?int $lead_id, ?int $client_id): bool
    {
        return $this->db
            ->where('id', $event_id)
            ->update(db_prefix() . 'calendly_events', [
                'lead_id'   => $lead_id,
                'client_id' => $client_id,
            ]);
    }

    // ─── CRM cross-reference ─────────────────────────────────────────────────

    /**
     * Find a lead ID by email address.
     */
    public function find_lead_by_email(string $email): ?int
    {
        $row = $this->db
            ->select('id')
            ->where('email', $email)
            ->get(db_prefix() . 'leads')
            ->row_array();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Find a client contact ID by email address.
     */
    public function find_client_by_email(string $email): ?int
    {
        $row = $this->db
            ->select('userid')
            ->where('email', $email)
            ->get(db_prefix() . 'clients')
            ->row_array();
        return $row ? (int) $row['userid'] : null;
    }

    // ─── Settings helpers ─────────────────────────────────────────────────────

    public function get_setting(string $key): string
    {
        return (string) get_option($key);
    }

    public function save_setting(string $key, string $value): void
    {
        update_option($key, $value);
    }
}
