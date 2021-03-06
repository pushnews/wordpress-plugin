<?php
/*
Author:             Pushnews <developers@pushnews.eu>
License:            GPLv2 or later
*/
?>
<div>
    <div style="text-align: center">
        <img src="https://icons.pn.vg/pushnews-icon-positive-512.png" alt="pushnews_logo" style="display: block; width: 40px; margin: 10px auto;">
    </div>
    <?php
    $sendNotification = filter_var(get_post_meta(get_the_ID(), 'sendNotification', true), FILTER_VALIDATE_BOOLEAN);
    $sendEmail        = filter_var(get_post_meta(get_the_ID(), 'sendEmail', true), FILTER_VALIDATE_BOOLEAN);
    $pluginPath       = 'admin.php?page=pushnews';
    $pluginUrl        = admin_url($pluginPath);
    $options          = get_option('pushnews_options');
    ?>
    <?php if(isset($options['auth_token']) && $options['auth_token'] != ""): ?>
        <div>
            <input type="checkbox" value="true" id="input_send_notification" name="pushnews_send_notification" <?php echo true === $sendNotification ? "checked" : "" ?> />
            <label for="input_send_notification"><?php echo __( 'Send push on publish/update', 'pushnews') ?></label>
        </div>
        <div>
            <input type="checkbox" value="true" id="input_send_email" name="pushnews_send_email" <?php echo true === $sendEmail ? "checked" : "" ?> />
            <label for="input_send_email"><?php echo __( 'Send email on post publish', 'pushnews') ?></label>
        </div>
    <?php else: ?>
        <div>
            <div style="color:#ccc;">
                <input type="checkbox" id="input_send_notification" disabled />
                <label for="input_send_notification"><?php echo __( 'Send push on publish/update', 'pushnews') ?></label>
            </div>
            <div style="margin-top: 5px; color:#ccc;">
                <input type="checkbox" id="input_send_email" disabled  />
                <label for="input_send_email"><?php echo __( 'Send email on post publish', 'pushnews') ?></label>
            </div>
            <hr style="background-color: #ccc; margin-top: 15px; width: 90%;">
            <div style="margin-top: 10px; font-size: 12px; text-align: center">
                <?php echo __('Auth token is not defined, please update your plugin configuration') ?>
                <?php echo " <a href='{$pluginUrl}'>" . __('here') . "</a>" ?>
            </div>
        </div>
    <?php endif; ?>
</div>