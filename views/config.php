<?php
/*
Author:             Pushnews <developers@pushnews.eu>
License:            GPLv2 or later
*/
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Pushnews', 'pushnews' ); ?></h2>
	<form action="options.php" method="post">
		<?php
		settings_fields("pushnews");
		do_settings_sections("pushnews");
		submit_button(__("Save", 'pushnews'));
		?>
	</form>
</div>