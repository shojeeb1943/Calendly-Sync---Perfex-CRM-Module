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

                <!-- Sync result alert -->
                <div id="sync-alert" style="display:none;"></div>

                <!-- Meetings table -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <?php if (empty($events)): ?>
                            <p class="text-muted text-center" style="padding:30px 0;">
                                <i class="fa fa-calendar-o fa-2x"></i><br/>
                                <?php echo _l('calendly_sync_no_meetings'); ?>
                            </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('calendly_sync_col_time'); ?></th>
                                        <th><?php echo _l('calendly_sync_col_invitee'); ?></th>
                                        <th><?php echo _l('calendly_sync_col_type'); ?></th>
                                        <th><?php echo _l('calendly_sync_col_platform'); ?></th>
                                        <th><?php echo _l('calendly_sync_col_contact'); ?></th>
                                        <th><?php echo _l('calendly_sync_col_action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($events as $event):
                                    $is_active  = $event['status'] === 'active';
                                    $start_ts   = $event['start_time'] ? strtotime($event['start_time']) : 0;
                                    $now_ts     = time();
                                    $diff_mins  = $start_ts ? (int) round(($start_ts - $now_ts) / 60) : null;
                                    $show_countdown = $diff_mins !== null && $diff_mins > 0 && $diff_mins <= 120;

                                    // Platform icon detection
                                    $join_lower = strtolower($event['join_url'] ?? '');
                                    if (strpos($join_lower, 'zoom.us') !== false) {
                                        $platform_icon  = 'fa-video-camera';
                                        $platform_label = 'Zoom';
                                        $platform_color = 'text-primary';
                                    } elseif (strpos($join_lower, 'meet.google') !== false) {
                                        $platform_icon  = 'fa-google';
                                        $platform_label = 'Meet';
                                        $platform_color = 'text-danger';
                                    } elseif (strpos($join_lower, 'teams.microsoft') !== false) {
                                        $platform_icon  = 'fa-windows';
                                        $platform_label = 'Teams';
                                        $platform_color = 'text-primary';
                                    } else {
                                        $platform_icon  = 'fa-link';
                                        $platform_label = '';
                                        $platform_color = 'text-muted';
                                    }
                                ?>
                                    <tr class="<?php echo $is_active ? '' : 'text-muted'; ?>">
                                        <td>
                                            <?php if ($event['start_time']): ?>
                                                <strong><?php echo date('M j, H:i', $start_ts); ?></strong>
                                                <?php if ($show_countdown): ?>
                                                    <br/><small class="text-warning">
                                                        <i class="fa fa-clock-o"></i>
                                                        <?php echo _l('calendly_sync_starts_in') . ' ' . $diff_mins . ' ' . _l('calendly_sync_minutes'); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['invitee_name']); ?></strong>
                                            <br/><small class="text-muted"><?php echo htmlspecialchars($event['invitee_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td>
                                            <?php if ($event['join_url']): ?>
                                                <i class="fa <?php echo $platform_icon; ?> <?php echo $platform_color; ?>"></i>
                                                <?php echo $platform_label; ?>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($event['lead_id']): ?>
                                                <a href="<?php echo admin_url('leads/index/' . $event['lead_id']); ?>" target="_blank" class="label label-info">
                                                    <i class="fa fa-user"></i> <?php echo _l('calendly_sync_known_contact'); ?>
                                                </a>
                                            <?php elseif ($event['client_id']): ?>
                                                <a href="<?php echo admin_url('clients/client/' . $event['client_id']); ?>" target="_blank" class="label label-success">
                                                    <i class="fa fa-building"></i> <?php echo _l('calendly_sync_known_contact'); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_active && !empty($event['join_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($event['join_url']); ?>" target="_blank" class="btn btn-success btn-xs">
                                                    <i class="fa fa-sign-in"></i> <?php echo _l('calendly_sync_join_meeting'); ?>
                                                </a>
                                            <?php elseif (!$is_active): ?>
                                                <span class="label label-danger"><?php echo _l('calendly_sync_canceled'); ?></span>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#btn-sync-past').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');

        $.ajax({
            url:  '<?php echo admin_url('calendly_sync/sync_past'); ?>',
            type: 'POST',
            dataType: 'json',
            success: function (r) {
                var cls = r.success ? 'alert-success' : 'alert-danger';
                $('#sync-alert').removeClass().addClass('alert ' + cls).html(r.message).show();
                if (r.success && r.synced > 0) {
                    setTimeout(function () { location.reload(); }, 1500);
                }
            },
            error: function () {
                $('#sync-alert').removeClass().addClass('alert alert-danger').html('Request failed.').show();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('calendly_sync_sync_past_btn'); ?>');
            }
        });
    });
});
</script>

<?php init_tail(); ?>
