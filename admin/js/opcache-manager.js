jQuery(document).ready( function($) {
	$('.opcm-about-logo').css({opacity:1});


	$( "#opcm-chart-button-ratio" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).addClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).addClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-hit" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).addClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).addClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-memory" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).addClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).addClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-file" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).addClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).addClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-key" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).addClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).addClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-string" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).addClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).addClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-buffer" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).addClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).addClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-uptime" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-file" ).removeClass( "active" );
			$( "#opcm-chart-key" ).removeClass( "active" );
			$( "#opcm-chart-string" ).removeClass( "active" );
			$( "#opcm-chart-buffer" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).addClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-file" ).removeClass( "active" );
			$( "#opcm-chart-button-key" ).removeClass( "active" );
			$( "#opcm-chart-button-string" ).removeClass( "active" );
			$( "#opcm-chart-button-buffer" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).addClass( "active" );
		}
	);
} );
