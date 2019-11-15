jQuery(document).ready( function($) {
	$('.opcm-about-logo').css({opacity:1});


	$( "#traffic-chart-button-memory" ).on(
		"click",
		function() {
			$( "#traffic-chart-memory" ).addClass( "active" );
			$( "#traffic-chart-data" ).removeClass( "active" );
			$( "#traffic-chart-uptime" ).removeClass( "active" );
			$( "#traffic-chart-button-memory" ).addClass( "active" );
			$( "#traffic-chart-button-data" ).removeClass( "active" );
			$( "#traffic-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#traffic-chart-button-data" ).on(
		"click",
		function() {
			$( "#traffic-chart-memory" ).removeClass( "active" );
			$( "#traffic-chart-data" ).addClass( "active" );
			$( "#traffic-chart-uptime" ).removeClass( "active" );
			$( "#traffic-chart-button-memory" ).removeClass( "active" );
			$( "#traffic-chart-button-data" ).addClass( "active" );
			$( "#traffic-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#traffic-chart-button-uptime" ).on(
		"click",
		function() {
			$( "#traffic-chart-memory" ).removeClass( "active" );
			$( "#traffic-chart-data" ).removeClass( "active" );
			$( "#traffic-chart-uptime" ).addClass( "active" );
			$( "#traffic-chart-button-memory" ).removeClass( "active" );
			$( "#traffic-chart-button-data" ).removeClass( "active" );
			$( "#traffic-chart-button-uptime" ).addClass( "active" );
		}
	);
} );
