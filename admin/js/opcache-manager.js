jQuery(document).ready( function($) {
	$('.opcm-about-logo').css({opacity:1});


	$( "#opcm-chart-button-ratio" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).addClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).addClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-hit" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).addClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).addClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-memory" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).addClass( "active" );
			$( "#opcm-chart-uptime" ).removeClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).addClass( "active" );
			$( "#opcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#opcm-chart-button-uptime" ).on(
		"click",
		function() {
			$( "#opcm-chart-ratio" ).removeClass( "active" );
			$( "#opcm-chart-hit" ).removeClass( "active" );
			$( "#opcm-chart-memory" ).removeClass( "active" );
			$( "#opcm-chart-uptime" ).addClass( "active" );
			$( "#opcm-chart-button-ratio" ).removeClass( "active" );
			$( "#opcm-chart-button-hit" ).removeClass( "active" );
			$( "#opcm-chart-button-memory" ).removeClass( "active" );
			$( "#opcm-chart-button-uptime" ).addClass( "active" );
		}
	);
} );
