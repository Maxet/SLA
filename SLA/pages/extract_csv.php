<?php
	# Prevent output of HTML in the content if errors occur
	define( 'DISABLE_INLINE_ERROR_SLA', true );
	
	require_once( '../../../core.php' );
	require_api( 'authentication_api.php' );
	require_api( 'columns_api.php' );
	require_api( 'constant_inc.php' );
	require_api( 'csv_api.php' );
	require_api( 'file_api.php' );
	require_api( 'filter_api.php' );
	require_api( 'helper_api.php' );
	require_api( 'print_api.php' );
	require_api( '../plugins/SLA/core/sla_api.php' );
	require_api( '../plugins/SLA/core/SLA_extract_api.php' );
	
	auth_ensure_user_authenticated(); // controle que l utilisateur a l autorisation de se connecter
	
	helper_begin_long_process(); // gestion des processus long
	
	$t_nl = csv_get_newline(); // equivaut à un retour chariot
	$t_sep = csv_get_separator(); // par defaut c est une virgule a modifier dans Administration/rapport de configuration/csv_separator
	
	
	$project_id                 = helper_get_current_project(); //projets courants filtre de l utilisateur
	$specific_where             = helper_project_specific_where( $project_id ); // clauses where pour la requete
	$project_ids                = project_full( $project_id );	
	$projet                     = db_prepare_string( $_GET['idproj']);
	
	
	# Get columns to be exported
	$t_columns = report_get_columns();
	
	csv_start( csv_get_default_filename() );
	
	# export the titles
	$t_first_column = true;
	ob_start();
	$t_titles = array();
	foreach ( $t_columns as $t_column ) {
		if( !$t_first_column ) {
			echo $t_sep;
			} else {
			$t_first_column = false;
		}
		
		echo $t_column;
	}
	
	echo $t_nl;
	
	$t_header = ob_get_clean();
	
	# Fixed for a problem in Excel where it prompts error message "SYLK: File Format Is Not Valid"
	# See Microsoft Knowledge Base Article - 323626
	# http://support.microsoft.com/default.aspx?scid=kb;en-us;323626&Product=xlw
	$t_first_three_chars = utf8_substr( $t_header, 0, 3 );
	if( strcmp( $t_first_three_chars, 'ID' . $t_sep ) == 0 ) {
		$t_header = str_replace( 'ID' . $t_sep, 'Id' . $t_sep, $t_header );
	}
	# end of fix

	echo $t_header;
	$t_end_of_results = false;


	# Clear cache for next block
	bug_clear_cache_all();

	$t_content = report_get_content($projet);
	
	// Lecture de chaque ligne du tableau
	foreach($t_content as $ligne){
		$t_first_column = true;
		// Lecture de chaque tableau de chaque ligne
		foreach ($ligne as $cle=>$valeur){
			// Affichage
			if( !$t_first_column ) {
				echo $t_sep;
				} else {
				$t_first_column = false;
			}
			echo $valeur;
		}
		echo $t_nl;		
	}
?>