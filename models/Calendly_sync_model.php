<?php
/**
 * @file        models/Calendly_sync_model.php
 * @package     Perfex CRM — Calendly Master Sync Module
 *
 * Data-access layer. All SQL lives here; no raw queries in the controller.
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
     * Upcoming active meetings, ordered by start_time ASC.
     */
    public function get_upcoming_events(int $limit = 20): array
    {
        return $this->db
            ->select('*')
            ->from('tblcalendly_events')
            ->where('status', 'active')
            ->where('start_time >=', date('Y-m-d H:i:s'))
            ->order_by('start_time', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * All events for the dashboard table (most recent first).
     */
    public function get_all_events(int $limit = 50): array
    {
        return $this->db
            ->select('*')
            ->from('tblcalendly_events')
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

        $total = $this->db->count_all('tblcalendly_events');

        $today = $this->db
            ->where('start_time >=', $today_start)
            ->where('start_time <=', $today_end)
            ->where('status', 'active')
            ->count_all_results('tblcalendly_events');

        $active = $this->db
            ->where('status', 'active')
            ->count_all_results('tblcalendly_events');

        $canceled = $this->db
            ->where('status', 'canceled')
            ->count_all_results('tblcalendly_events');

        $with_lead = $this->db
            ->where('lead_id IS NOT NULL', null, false)
            ->or_where('client_id IS NOT NULL', null, false)
            ->count_all_results('tblcalendly_events');

        return compact('total', 'today', 'active', 'canceled', 'with_lead');
    }

    /**
     * Find a single event by its Calendly UUID.
     */
    public function get_event_by_uuid(string $uuid): ?array
    {
        $row = $this->db
            ->where('event_uuid', $uuid)
            ->get('tblcalendly_events')
            ->row_array();
        return $row ?: null;
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
            return $this->db->update('tblcalendly_events', $data);
        }

        return $this->db->insert('tblcalendly_events', $data);
    }

    /**
     * Mark an event as canceled by its Calendly UUID.
     */
    public function cancel_event(string $uuid): bool
    {
        return $this->db
            ->where('event_uuid', $uuid)
            ->update('tblcalendly_events', ['status' => 'canceled']);
    }

    /**
     * Save CRM cross-reference IDs after email matching.
     */
    public function update_crm_refs(int $event_id, ?int $lead_id, ?int $client_id): bool
    {
        return $this->db
            ->where('id', $event_id)
            ->update('tblcalendly_events', [
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
            ->get('tblleads')
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
            ->get('tblclients')
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
