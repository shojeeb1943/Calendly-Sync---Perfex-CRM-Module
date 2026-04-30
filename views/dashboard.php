<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<style>
/* ── Calendly-style meeting list ── */
.meetings-tab-bar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 14px 20px 0;
    border-bottom: 2px solid #e8e8e8;
}
.meetings-tab-nav {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 4px;
}
.meetings-tab-nav > li > a {
    display: block;
    padding: 8px 18px 10px;
    color: #666;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    font-size: 14px;
    font-weight: 500;
    transition: color .15s, border-color .15s;
}
.meetings-tab-nav > li > a:hover { color: #333; }
.meetings-tab-nav > li.active > a {
    color: #1a1a1a;
    border-bottom-color: #1a1a1a;
    font-weight: 700;
}
.meetings-tab-nav > li.dropdown > a { display: flex; align-items: center; gap: 4px; }

.meeting-date-header {
    padding: 12px 22px 9px;
    font-size: 12px;
    color: #777;
    font-weight: 500;
    letter-spacing: .3px;
    background: #fafafa;
    border-bottom: 1px solid #f0f0f0;
}
.meeting-today-badge {
    display: inline-block;
    background: #0073b1;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .5px;
    padding: 1px 7px;
    border-radius: 10px;
    margin-left: 10px;
    vertical-align: middle;
}
.meeting-row {
    display: flex;
    align-items: center;
    padding: 13px 22px;
    border-bottom: 1px solid #f2f2f2;
    gap: 18px;
    transition: background .1s;
}
.meeting-row:hover { background: #fafcff; }
.meeting-row:last-child { border-bottom: none; }
.meeting-circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    flex-shrink: 0;
}
.meeting-time {
    min-width: 140px;
    font-size: 13px;
    color: #0073b1;
    flex-shrink: 0;
}
.meeting-info { flex: 1; min-width: 0; }
.meeting-info .invitee-name {
    font-weight: 600;
    font-size: 14px;
    color: #222;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.meeting-info .event-type-label {
    font-size: 12px;
    color: #888;
    margin-top: 2px;
}
.meeting-info .event-type-label strong { color: #555; }
.meeting-hosts {
    min-width: 170px;
    font-size: 12px;
    color: #888;
    flex-shrink: 0;
    text-align: center;
}
.meeting-action { flex-shrink: 0; min-width: 70px; text-align: right; }
.meeting-action a { color: #aaa; font-size: 12px; text-decoration: none; }
.meeting-action a:hover { color: #555; }
.meetings-empty {
    text-align: center;
    padding: 60px 20px;
    color: #bbb;
    font-size: 14px;
}
.meetings-loading { text-align: center; padding: 50px; color: #ccc; }
.meetings-date-group { border-bottom: 1px solid #eee; }
.meetings-date-group:last-child { border-bottom: none; }
</style>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <!-- Page header -->
                <div class="page-heading">
                    <h3 class="no-margin">
                        <i class="fa fa-calendar text-success"></i>
                        <?php echo _l('calendly_sync_page_title'); ?>
                        <small class="text-muted" style="font-size:13px;margin-left:8px;">v<?php echo CALENDLY_SYNC_VERSION; ?></small>
                    </h3>
                    <small class="text-muted"><?php echo _l('calendly_sync_subtitle'); ?></small>
                    <button id="btn-sync-past" class="btn btn-default btn-sm pull-right" style="margin-top:4px;">
                        <i class="fa fa-refresh"></i> <?php echo _l('calendly_sync_sync_past_btn'); ?>
                    </button>
                </div>
                <hr class="hr-panel-heading" />

                <!-- Stat cards -->
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h2 class="text-primary no-margin"><?php echo $stats['today']; ?></h2>
                                <small><?php echo _l('calendly_sync_stat_today'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h2 class="text-success no-margin"><?php echo $stats['active']; ?></h2>
                                <small><?php echo _l('calendly_sync_stat_active'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h2 class="text-danger no-margin"><?php echo $stats['canceled']; ?></h2>
                                <small><?php echo _l('calendly_sync_stat_canceled'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h2 class="text-info no-margin"><?php echo $stats['with_lead']; ?></h2>
                                <small><?php echo _l('calendly_sync_stat_known'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendly-style meetings panel -->
                <div class="panel panel-default" style="overflow:hidden;">

                    <!-- Tab bar -->
                    <div class="meetings-tab-bar">
                        <ul class="meetings-tab-nav">
                            <li class="active" data-tab="upcoming">
                                <a href="#"><?php echo _l('calendly_sync_tab_upcoming'); ?></a>
                            </li>
                            <li data-tab="past">
                                <a href="#"><?php echo _l('calendly_sync_tab_past'); ?></a>
                            </li>
                            <li class="dropdown" id="date-range-tab-li" data-tab="date_range">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <?php echo _l('calendly_sync_tab_date_range'); ?>
                                    <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu" style="padding:16px; min-width:240px; left:0;">
                                    <li>
                                        <div style="margin-bottom:10px;">
                                            <label style="font-size:12px; font-weight:600; color:#555;">From</label>
                                            <input type="date" id="filter-date-from" class="form-control input-sm" style="margin-top:4px;">
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <label style="font-size:12px; font-weight:600; color:#555;">To</label>
                                            <input type="date" id="filter-date-to" class="form-control input-sm" style="margin-top:4px;">
                                        </div>
                                        <button id="btn-apply-date-range" class="btn btn-primary btn-sm" style="width:100%;">Apply</button>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <div style="padding-bottom:8px; display:flex; gap:8px;">
                            <button class="btn btn-default btn-sm" id="btn-export-meetings" style="border-radius:18px;">
                                <i class="fa fa-upload"></i> Export
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" style="border-radius:18px;">
                                    <i class="fa fa-sliders"></i> Filter <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" class="filter-status-item" data-status="all">All</a></li>
                                    <li><a href="#" class="filter-status-item" data-status="active">Active only</a></li>
                                    <li><a href="#" class="filter-status-item" data-status="canceled">Canceled only</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Meetings list -->
                    <div id="meetings-container">
                        <div class="meetings-loading">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    var currentTab   = 'upcoming';
    var filterStatus = 'all';
    var today        = '<?php echo date('Y-m-d'); ?>';

    /* ── helpers ─────────────────────────────────────────────── */

    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtTime(dtStr) {
        if (!dtStr) return '';
        /* MySQL returns "YYYY-MM-DD HH:MM:SS" — replace space with T so
           Safari / Firefox parse it correctly as local time */
        var d = new Date(dtStr.replace(' ', 'T'));
        var h = d.getHours(), m = d.getMinutes();
        var ampm = h >= 12 ? 'pm' : 'am';
        h = h % 12 || 12;
        var min = m === 0 ? '' : ':' + (m < 10 ? '0' + m : m);
        return h + min + ' ' + ampm;
    }

    function fmtDateHeader(ymd) {
        var d = new Date(ymd + 'T00:00:00');
        var days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var months = ['January','February','March','April','May','June',
                      'July','August','September','October','November','December'];
        return days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    var palette = ['#6B4FBB','#4A90D9','#E67E22','#2ECC71','#E74C3C','#16A085','#8E44AD'];
    function circleColor(ev) {
        if (ev.status === 'canceled') return '#ccc';
        var idx = ((ev.event_type || '').length + (ev.invitee_name || '').charCodeAt(0)) % palette.length;
        return palette[idx];
    }

    function platformBadge(url) {
        if (!url) return '';
        url = url.toLowerCase();
        if (url.indexOf('zoom.us')         >= 0) return ' &middot; <i class="fa fa-video-camera" style="color:#2D8CFF;"></i> Zoom';
        if (url.indexOf('meet.google')     >= 0) return ' &middot; <i class="fa fa-google"       style="color:#EA4335;"></i> Meet';
        if (url.indexOf('teams.microsoft') >= 0) return ' &middot; <i class="fa fa-windows"      style="color:#6264A7;"></i> Teams';
        return '';
    }

    /* ── render ──────────────────────────────────────────────── */

    function renderEvents(events) {
        /* apply status filter */
        if (filterStatus !== 'all') {
            events = events.filter(function (ev) { return ev.status === filterStatus; });
        }

        if (!events || events.length === 0) {
            return '<div class="meetings-empty">'
                 + '<i class="fa fa-calendar-o fa-3x" style="margin-bottom:14px; display:block;"></i>'
                 + 'No meetings found.</div>';
        }

        /* group by date */
        var groups = {}, order = [];
        events.forEach(function (ev) {
            var key = ev.start_time ? ev.start_time.substring(0, 10) : 'unknown';
            if (!groups[key]) { groups[key] = []; order.push(key); }
            groups[key].push(ev);
        });

        var html = '';
        order.forEach(function (dateKey) {
            var isToday = (dateKey === today);
            var label   = dateKey !== 'unknown' ? fmtDateHeader(dateKey) : 'Unknown Date';

            html += '<div class="meetings-date-group">';
            html += '<div class="meeting-date-header">' + escHtml(label);
            if (isToday) html += '<span class="meeting-today-badge">TODAY</span>';
            html += '</div>';

            groups[dateKey].forEach(function (ev) {
                var startFmt = fmtTime(ev.start_time);
                var endFmt   = fmtTime(ev.end_time);
                var timeStr  = (startFmt && endFmt) ? startFmt + ' – ' + endFmt : (startFmt || '&mdash;');
                var color    = circleColor(ev);
                var platform = platformBadge(ev.join_url);

                /* action column */
                var action = '';
                if (ev.status === 'canceled') {
                    action = '<span style="font-size:11px; color:#ccc;">Canceled</span>';
                } else if (ev.join_url) {
                    action = '<a href="' + escHtml(ev.join_url) + '" target="_blank">'
                           + '&#9658; Details</a>';
                } else {
                    action = '<span style="color:#ddd;">&#9658; Details</span>';
                }

                html += '<div class="meeting-row">';
                html += '<div class="meeting-circle" style="background:' + color + ';"></div>';
                html += '<div class="meeting-time">' + timeStr + '</div>';
                html += '<div class="meeting-info">';
                html += '<div class="invitee-name">' + escHtml(ev.invitee_name || 'Unknown') + '</div>';
                html += '<div class="event-type-label">Event type <strong>' + escHtml(ev.event_type || '') + '</strong>' + platform + '</div>';
                html += '</div>';
                html += '<div class="meeting-hosts">1 host &nbsp;|&nbsp; 0 non-hosts</div>';
                html += '<div class="meeting-action">' + action + '</div>';
                html += '</div>';
            });

            html += '</div>';
        });

        return html;
    }

    /* ── AJAX load ───────────────────────────────────────────── */

    function loadEvents(tab, dateFrom, dateTo) {
        $('#meetings-container').html('<div class="meetings-loading"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');

        var postData = { tab: tab };
        if (dateFrom) postData.date_from = dateFrom;
        if (dateTo)   postData.date_to   = dateTo;

        $.ajax({
            url:      admin_url + 'calendly_sync/get_events_list',
            type:     'POST',
            data:     postData,
            dataType: 'json',
            success: function (r) {
                $('#meetings-container').html(renderEvents(r.events || []));
            },
            error: function () {
                $('#meetings-container').html('<div class="meetings-empty">Failed to load meetings.</div>');
            },
        });
    }

    /* ── Tab clicks ──────────────────────────────────────────── */

    $('.meetings-tab-nav > li:not(.dropdown)').on('click', function (e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        currentTab = tab;
        $('.meetings-tab-nav > li').removeClass('active');
        $(this).addClass('active');
        loadEvents(tab);
    });

    /* ── Date range apply ────────────────────────────────────── */

    $('#btn-apply-date-range').on('click', function () {
        var from = $('#filter-date-from').val();
        var to   = $('#filter-date-to').val();
        if (!from || !to) { alert_float('warning', 'Please select both From and To dates.'); return; }
        currentTab = 'date_range';
        $('.meetings-tab-nav > li').removeClass('active');
        $('#date-range-tab-li').addClass('active');
        /* close dropdown */
        $('#date-range-tab-li').removeClass('open');
        loadEvents('date_range', from, to);
    });

    /* ── Status filter ───────────────────────────────────────── */

    $(document).on('click', '.filter-status-item', function (e) {
        e.preventDefault();
        filterStatus = $(this).data('status');
        loadEvents(currentTab);
    });

    /* ── Export ──────────────────────────────────────────────── */

    $('#btn-export-meetings').on('click', function () {
        window.location.href = admin_url + 'calendly_sync/export_events?tab=' + currentTab;
    });

    /* ── Sync past ───────────────────────────────────────────── */

    $('#btn-sync-past').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');
        $.ajax({
            url:      admin_url + 'calendly_sync/sync_past',
            type:     'POST',
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    alert_float('success', r.message);
                    if (r.synced > 0) { loadEvents(currentTab); }
                } else {
                    alert_float('danger', r.message);
                }
            },
            error: function () { alert_float('danger', 'Request failed.'); },
            complete: function () {
                $btn.prop('disabled', false)
                    .html('<i class="fa fa-refresh"></i> <?php echo _l('calendly_sync_sync_past_btn'); ?>');
            },
        });
    });

    /* ── Initial load ────────────────────────────────────────── */
    loadEvents('upcoming');
});
</script>
