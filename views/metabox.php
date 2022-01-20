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
    <?php if(isset($options[Pushnews::OPTION_NAME_BASIC_API_TOKEN]) && $options[Pushnews::OPTION_NAME_BASIC_API_TOKEN] != ""): ?>
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
			<p style="text-align: center;">
				<b><?php echo __('Error', 'pushnews'); ?></b>:
				<?php echo __('API token is not defined, please update your plugin configuration', 'pushnews') ?>
				<?php echo " <a href='{$pluginUrl}'>" . __('here') . "</a>" ?>
			</p>
			<hr style="background-color: #ccc; margin-top: 15px; width: 90%;">
            <div style="color:#ccc;">
                <input type="checkbox" id="input_send_notification" disabled />
                <label for="input_send_notification"><?php echo __( 'Send push on publish/update', 'pushnews') ?></label>
            </div>
            <div style="margin-top: 5px; color:#ccc;">
                <input type="checkbox" id="input_send_email" disabled  />
                <label for="input_send_email"><?php echo __( 'Send email on post publish', 'pushnews') ?></label>
            </div>
        </div>
    <?php endif; ?>
	<hr style="background-color: #ccc; margin-top: 15px; width: 90%;">
	<p style="text-align: center">
		<a href="https://app.pushnews.eu/" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
				 width="16" height="16"
				 viewBox="0 0 24 24"
				 style="fill: #2370b1;width: 16px;height: 16px;"><path d="M19,21H5c-1.1,0-2-0.9-2-2V5c0-1.1,0.9-2,2-2h7v2H5v14h14v-7h2v7C21,20.1,20.1,21,19,21z"></path><path d="M21 10L19 10 19 5 14 5 14 3 21 3z"></path><path d="M6.7 8.5H22.3V10.5H6.7z" transform="rotate(-45.001 14.5 9.5)"></path></svg></a>
		<a href="https://app.pushnews.eu/" target="_blank"><?php echo __('Open Pushnews', 'pushnews') ?></a>
	</p>
</div>
