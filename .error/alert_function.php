<?php
	function alert_success($msg) {
		echo '<div style="margin: 5px 0; padding: 10px; border-left: 5px solid #388E3C; background-color: #C8E6C9; color: #388E3C;">'.$msg.'</div>';
	}

	function alert_info($msg) {
		echo '<div style="margin: 5px 0; padding: 10px; border-left: 5px solid #1c92f2; background-color: #64B5F6; color: #fff;">'.$msg.'</div>';
	}

	function alert_warning($msg) {
		echo '<div style="margin: 5px 0; padding: 10px; border-left: 5px solid #f57c00; background-color: #ffe0b2; color: #f57c00;">'.$msg.'</div>';
	}

	function alert_error($msg) {
		echo '<div style="margin: 5px 0; padding: 10px; border-left: 5px solid #c2185b; background-color: #f8bbd0; color: #c2185b;">'.$msg.'</div>';
	}

	alert_success('This is a success message');
	alert_info('This is an info message');
	alert_warning('This is a warning message');
	alert_error('This is an error message');
?> 