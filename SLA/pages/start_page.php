<?php
	if( !defined( 'MANTIS_VERSION' ) ) { exit(); }

require_once 'common_includes.php';

	print_successful_redirect( plugin_page( $reportsToShow[0], true ) );

?>