<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

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

                <!-- Meetings table (server-side DataTable) -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <?php render_datatable([
                            _l('calendly_sync_col_time'),
                            _l('calendly_sync_col_invitee'),
                            _l('calendly_sync_col_type'),
                            _l('calendly_sync_col_platform'),
                            _l('calendly_sync_col_contact'),
                            _l('calendly_sync_col_action'),
                        ], 'calendly-events-dt'); ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    var CalendlyTable = $('#calendly-events-dt').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  admin_url + 'calendly_sync/get_events_dt',
            type: 'POST',
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3, orderable: false, searchable: false },
            { data: 4 },
            { data: 5, orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            emptyTable:     '<?php echo _l('calendly_sync_no_meetings'); ?>',
            zeroRecords:    '<?php echo _l('calendly_sync_no_meetings'); ?>',
        },
    });

    $('#btn-sync-past').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');

        $.ajax({
            url:      admin_url + 'calendly_sync/sync_past',
            type:     'POST',
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    alert_float('success', r.message);
                    if (r.synced > 0) {
                        CalendlyTable.ajax.reload();
                    }
                } else {
                    alert_float('danger', r.message);
                }
            },
            error: function () {
                alert_float('danger', 'Request failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('calendly_sync_sync_past_btn'); ?>');
            },
        });
    });
});
</script>
