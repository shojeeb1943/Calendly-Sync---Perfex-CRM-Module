<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="col-md-4">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-calendar text-success"></i>
                <?php echo _l('calendly_sync_widget_title'); ?>
            </h4>
        </div>
        <div class="panel-body" style="padding:0;">
            <?php if (empty($events)): ?>
                <p class="text-muted text-center" style="padding:15px;">
                    <?php echo _l('calendly_sync_widget_no_events'); ?>
                </p>
            <?php else: ?>
                <table class="table table-condensed no-margin">
                    <tbody>
                    <?php foreach ($events as $event):
                        $start_ts  = $event['start_time'] ? strtotime($event['start_time']) : 0;
                        $now_ts    = time();
                        $diff_mins = $start_ts ? (int) round(($start_ts - $now_ts) / 60) : null;
                        $soon      = $diff_mins !== null && $diff_mins > 0 && $diff_mins <= 60;

                        // Platform icon
                        $join_lower = strtolower($event['join_url'] ?? '');
                        if (strpos($join_lower, 'zoom.us') !== false) {
                            $icon = 'fa-video-camera text-primary';
                        } elseif (strpos($join_lower, 'meet.google') !== false) {
                            $icon = 'fa-google text-danger';
                        } elseif (strpos($join_lower, 'teams.microsoft') !== false) {
                            $icon = 'fa-windows text-primary';
                        } else {
                            $icon = 'fa-link text-muted';
                        }
                    ?>
                        <tr>
                            <td style="width:36px;padding:8px 6px;">
                                <i class="fa <?php echo $icon; ?>"></i>
                            </td>
                            <td style="padding:8px 4px;">
                                <strong style="font-size:12px;"><?php echo htmlspecialchars($event['invitee_name']); ?></strong>
                                <br/>
                                <small class="text-muted"><?php echo $event['start_time'] ? date('H:i', $start_ts) : ''; ?></small>
                                <?php if ($soon): ?>
                                    <small class="text-warning">&nbsp;&bull;&nbsp;in <?php echo $diff_mins; ?>m</small>
                                <?php endif; ?>
                            </td>
                            <td style="width:90px;padding:8px 6px;text-align:right;">
                                <?php if (!empty($event['join_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($event['join_url']); ?>"
                                       target="_blank"
                                       class="btn btn-success btn-xs">
                                        <i class="fa fa-sign-in"></i> Join
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="panel-footer text-right" style="padding:6px 12px;">
            <a href="<?php echo admin_url('calendly_sync'); ?>" class="text-muted" style="font-size:12px;">
                <?php echo _l('calendly_sync_widget_view_all'); ?> <i class="fa fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
