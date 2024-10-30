<?php

$is_popup = ! defined( 'ABSPATH' );

$ajax_url = false;
if( $is_popup ) {
	require 'popup/html_header.php';
	$dq = isset( $_GET['dq'] ) ? $_GET['dq'] : false;
	$ajax_url = base64_decode( $dq );
	if( substr( $ajax_url, 0, 4 ) != 'http') {
		echo "Invalid parameter. Please open the popup again from WordPress Admin";
		die();
	}
	$enabled = true;
	$disabled = true;
	$log_content = '';
} else {
	$ajax_url = admin_url( 'admin-ajax.php' );
	$enabled = ! DebugLogFunctions::get_status();
	$disabled = DebugLogFunctions::get_status();
	$log_content = htmlspecialchars( DebugLogFunctions::get_logfile_data() );
}

?>
<style>
.log-file { position: absolute; width: auto; left: 10px; right: 10px; overflow: auto; border: 1px solid #DDD; background: #FAFAFA; padding: 10px; box-sizing: border-box; -moz-box-sizing: border-box; }
.log-action { display: inline-block; padding: 0 0 5px 10px; }
.log-action label { cursor: default; }
</style>
<form method="POST" class="log-action refresh">
	<input type="hidden" name="action" value="refresh" />
	<input type="submit" name="submit" id="submit" class="button button-primary" value="Refresh">
</form>
<form method="POST" class="log-action flush">
	<input type="hidden" name="action" value="flush" />
	<input type="submit" name="submit" id="submit" class="button " value="Reset log file">
</form>
<form method="POST" class="log-action disable" <?php if( $enabled ) echo 'style="display:none"' ?>>
	<input type="hidden" name="action" value="disable" />
	<label>Debugger is <strong style="color:#086">active</strong></label>
	<input type="submit" name="submit" id="submit" class="button " value="Turn off">
</form>
<form method="POST" class="log-action enable" <?php if( $disabled ) echo 'style="display:none"' ?>>
	<input type="hidden" name="action" value="enable" />
	<label>Debugger is <strong style="color:#842">inactive</strong></label>
	<input type="submit" name="submit" id="submit" class="button " value="Turn on">
</form>
<form method="POST" class="log-action">
	<input type="hidden" name="action" value="dumptype" />
	<select class="dumptype">
		<option value="0">var_dump() </option>
		<option value="1">var_export()</option>
		<option value="2">print_r()</option>
		<option value="3">json_encode()</option>
	</select>
	<input type="submit" name="submit" id="submit" class="button " value="Save">
</form>
<div class="log-file">
<pre id="the-log"><?php echo $log_content; ?></pre>
</div>
<script>
jQuery(function() {
	var log_wnd = jQuery(window),
		log_box = jQuery('.log-file'),
		log_data = jQuery('pre', log_box);

	log_wnd.resize(function(ev) {
		log_box.css({'height': log_wnd.innerHeight()-32-log_box.offset().top });
	}).trigger('resize');

	log_box.scrollTop(log_data.height());

	<?php if( $ajax_url ) : ?>
	var ajax_url = '<?php echo $ajax_url; ?>',
		doing_ajax = false;

	jQuery('form').submit(function() {
		if( doing_ajax ) return;
		doing_ajax = true;

		var form = jQuery(this),
			action = jQuery('[name=action]', form).val(),
			the_log = jQuery('#the-log'),
			args = {
				'action': 'logfile',
				'query': action
			};

		if( action == 'dumptype' )
			args.type = jQuery('.dumptype').val();

		// Disable all forms, only one-ajax request allowed at a time
		jQuery('form.log-action input').prop('disabled', true);

		var handle_response = function(data) {
			status = data.substr( 0, 1 ); // first character is the debugging status [1 = on]
			type = data.substr( 1, 1 ); // second character is the dump-type
			data = data.substr( 2 );

			if( status == '1' ) {
				// Logging was enabled. Show the "Disable" button
				jQuery('form.log-action.enable').hide();
				jQuery('form.log-action.disable').show();
			} else {
				// Logging was disabled. Show the "Enable" button
				jQuery('form.log-action.disable').hide();
				jQuery('form.log-action.enable').show();
			}
			jQuery('.dumptype').val(type);

			switch( action ) {
				case 'flush':
				case 'refresh':
					// Logfile was flushed or refreshed. Clear the data on screen
					the_log.text(data);
					break;
			}
		};

		var ajax_completed = function() {
			// Enable all forms again
			jQuery('form.log-action input').prop('disabled', false);
			doing_ajax = false;
		}

		jQuery.ajax({
			'url': ajax_url,
			'type': 'POST',
			'data': args,
			'cache': false,
			'success': handle_response,
			'complete': ajax_completed
		});
		return false;
	});

	jQuery('form.log-action.refresh').submit();

	<?php endif; ?>
});
</script>


<?php
if( !$is_popup ) {
	require 'popup/html_footer.php';
}

