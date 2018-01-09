<?php
		
	define( 'DISABLE_INLINE_ERROR_REPORTING', true );

	require_once( '../../../core.php' );
	require_api( 'authentication_api.php' );
	require_api( 'bug_api.php' );
	require_api( 'columns_api.php' );
	require_api( 'config_api.php' );
	require_api( 'excel_api.php' );
	require_api( 'file_api.php' );
	require_api( 'filter_api.php' );
	require_api( 'gpc_api.php' );
	require_api( 'helper_api.php' );
	require_api( 'print_api.php' );
	require_api( 'utility_api.php' );
	require_api( '../plugins/SLA/core/sla_api.php' );
	require_api( '../plugins/SLA/core/SLA_extract_api.php' );


	auth_ensure_user_authenticated(); // controle que l utilisateur a l autorisation de se connecter

	helper_begin_long_process(); // gestion des processus long


	$project_id                 = helper_get_current_project(); //projets courants filtre de l utilisateur
	$specific_where             = helper_project_specific_where( $project_id ); // clauses where pour la requete
	$project_ids                = project_full( $project_id );	
	$projet 					= db_prepare_string( $_GET['idproj']);

	$f_export = gpc_get_string( 'export', '' );

	helper_begin_long_process();

	$t_export_title = excel_get_default_filename();

	$t_short_date_format = config_get( 'short_date_format' );

	header( 'Content-Type: application/vnd.ms-excel; charset=UTF-8' );
	header( 'Pragma: public' );
	header( 'Content-Disposition: attachment; filename="' . urlencode( file_clean_name( $t_export_title ) ) . '.xml"' ) ;

	echo excel_get_header( $t_export_title );
	echo report_excel_get_columns();

	$t_content = report_get_content($projet);

	// Lecture de chaque ligne du tableau
	foreach($t_content as $ligne){
		echo excel_get_start_row();
		// Lecture de chaque tableau de chaque ligne
		foreach ($ligne as $cle=>$valeur){
		// Affichage
			$cell ='<Cell><Data ss:Type="String">'. $valeur .'</Data></Cell>';
			echo $cell;
		}
		echo excel_get_end_row();	
	}

	echo excel_get_footer();
?>
