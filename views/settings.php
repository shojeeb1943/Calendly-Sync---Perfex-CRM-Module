<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">

                <!-- Page header -->
                <div class="page-heading">
                    <h3 class="no-margin">
                        <i class="fa fa-cog text-muted"></i>
                        <?php echo _l('calendly_sync_settings_title'); ?>
                        <small class="text-muted" style="font-size:13px;margin-left:8px;">v<?php echo CALENDLY_SYNC_VERSION; ?></small>
                    </h3>
                </div>
                <hr class="hr-panel-heading" />

                <!-- Sub-navigation -->
                <ul class="nav nav-tabs" style="margin-bottom:20px;">
                    <li>
                        <a href="<?php echo admin_url('calendly_sync'); ?>">
                            <i class="fa fa-tachometer"></i> <?php echo _l('calendly_sync_dashboard'); ?>
                        </a>
                    </li>
                    <li class="active">
                        <a href="<?php echo admin_url('calendly_sync/settings'); ?>">
                            <i class="fa fa-cog"></i> <?php echo _l('calendly_sync_settings'); ?>
                        </a>
                    </li>
                </ul>

                <!-- ── General Settings ── -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title no-margin"><?php echo _l('calendly_sync_general_settings'); ?></h4>
                    </div>
                    <div class="panel-body">
                        <?php echo form_open(admin_url('calendly_sync/save_settings')); ?>

                        <div class="form-group">
                            <label for="calendly_api_token"><?php echo _l('calendly_sync_api_token'); ?></label>
                            <input type="password"
                                   name="calendly_api_token"
                                   id="calendly_api_token"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($api_token); ?>"
                                   autocomplete="new-password"
                                   placeholder="eyJraWQi..." />
                            <p class="help-block"><?php echo _l('calendly_sync_api_token_help'); ?></p>
                        </div>

                        <div class="form-group">
                            <label for="calendly_display_limit"><?php echo _l('calendly_sync_display_limit'); ?></label>
                            <input type="number"
                                   name="calendly_display_limit"
                                   id="calendly_display_limit"
                                   class="form-control"
                                   value="<?php echo (int) $display_limit; ?>"
                                   min="1"
                                   max="500"
                                   style="width:120px;" />
                            <p class="help-block"><?php echo _l('calendly_sync_display_limit_help'); ?></p>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?php echo _l('calendly_sync_save_settings'); ?>
                        </button>

                        <?php echo form_close(); ?>
                    </div>
                </div>

                <!-- ── Webhook Setup ── -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title no-margin"><?php echo _l('calendly_sync_webhook_section'); ?></h4>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label><?php echo _l('calendly_sync_webhook_url_label'); ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly
                                       value="<?php echo htmlspecialchars($webhook_url); ?>"
                                       id="webhook-url-display" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" onclick="copyWebhookUrl()">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo _l('calendly_sync_webhook_status'); ?></label><br/>
                            <?php if (!empty($webhook_uuid)): ?>
                                <span class="label label-success">
                                    <i class="fa fa-check"></i> <?php echo _l('calendly_sync_webhook_active'); ?>
                                </span>
                                <span class="text-muted" style="margin-left:8px;font-size:12px;">UUID: <?php echo htmlspecialchars($webhook_uuid); ?></span>
                            <?php else: ?>
                                <span class="label label-default">
                                    <i class="fa fa-times"></i> <?php echo _l('calendly_sync_webhook_inactive'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($webhook_uuid)): ?>
                            <button id="btn-setup-webhook" class="btn btn-success">
                                <i class="fa fa-plug"></i> <?php echo _l('calendly_sync_setup_webhook'); ?>
                            </button>
                        <?php else: ?>
                            <button id="btn-delete-webhook" class="btn btn-danger">
                                <i class="fa fa-trash"></i> <?php echo _l('calendly_sync_delete_webhook'); ?>
                            </button>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function copyWebhookUrl() {
    var el = document.getElementById('webhook-url-display');
    el.select();
    document.execCommand('copy');
    alert_float('success', '<?php echo _l('calendly_sync_url_copied'); ?>');
}

$(function () {
    $('#btn-setup-webhook').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Setting up...');

        $.ajax({
            url:      admin_url + 'calendly_sync/setup_webhook',
            type:     'POST',
            dataType: 'json',
            success: function (r) {
                alert_float(r.success ? 'success' : 'danger', r.message);
                if (r.success) {
                    setTimeout(function () { location.reload(); }, 1500);
                }
            },
            error: function () {
                alert_float('danger', 'Request failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-plug"></i> <?php echo _l('calendly_sync_setup_webhook'); ?>');
            },
        });
    });

    $('#btn-delete-webhook').on('click', function () {
        if (!confirm('<?php echo _l('calendly_sync_delete_webhook_confirm'); ?>')) {
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Removing...');

        $.ajax({
            url:      admin_url + 'calendly_sync/delete_webhook',
            type:     'POST',
            dataType: 'json',
            success: function (r) {
                alert_float(r.success ? 'success' : 'danger', r.message);
                if (r.success) {
                    setTimeout(function () { location.reload(); }, 1500);
                }
            },
            error: function () {
                alert_float('danger', 'Request failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> <?php echo _l('calendly_sync_delete_webhook'); ?>');
            },
        });
    });
});
</script>

<?php init_tail(); ?>
