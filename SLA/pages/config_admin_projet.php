<?php
	if( !defined( 'MANTIS_VERSION' ) ) { exit(); }

	auth_reauthenticate();
	access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

	layout_page_header( lang_get( 'plugin_format_title' ) );
	layout_page_begin( 'manage_overview_page.php' );
	print_manage_menu();

	require_once 'common_includes.php';
$ko = 0;
if ( FALSE == form_security_validate('config_admin')) { exit; };
	if (isset ($_POST['idpro'])){
		$projet = $_POST['idpro'];//recuperation de l id du projet
		
		// project setting
		if (isset ($_POST['project_update']))
		{
			$proj_val = $_POST['valeur'];
			$proj_delai = $_POST['Delai'];
			$proj_prio = $_POST['Priorite'];
			IF ($proj_val == '')
			{
				$ko = 1;
			}			
			IF($ko == 0){
				if ($proj_delai == 1) {
					$lib_delai= 'Prise en charge';
				}
				if ($proj_delai == 2) {
						$lib_delai= 'Contournement';
				}
				if ($proj_delai == 3) {
						$lib_delai= 'Résolution';
				}

				$query_val_update = " SELECT valeur
										FROM mantis_plugin_SLA_struct_table
									   WHERE project_id = ". $projet ."
										 AND priorite = '". $proj_prio ."'
										 AND delai = '". $lib_delai ."'
									";
				$res_up_proj = db_query( $query_val_update );

				if ( db_num_rows( $res_up_proj ) == 0 ) {

					$query_insert = "insert into mantis_plugin_SLA_struct_table (project_id, priorite, delai, valeur)
											 values ('".$projet."', '".$proj_prio."','".$lib_delai."','".$proj_val."')";
					db_query( $query_insert );

				} elseif ( db_num_rows( $res_up_proj ) == 1 ) {
					$query_update = "update mantis_plugin_SLA_struct_table 
									   set valeur = '" . $proj_val  . "' 
									 where project_id = ".$projet."
									   and priorite = '".$proj_prio."'
									   and delai = '".$lib_delai."'
									";
					db_query( $query_update );
				}
			}						 
		}
		
		// deadlines setting
		if (isset ($_POST['deadlines_update']))
		{
			//prise en charge
			if (isset ($_POST['Prise_en_Charge']))
			{
				$prise_charge = $_POST['Prise_en_Charge'];
				$query_pc  = "select statut 
								from " . plugin_table( 'statut' ) . " 
							   where project_id = ".$projet."
								 and etat = 'Prise_en_Charge'
					  ";
				$result_pc = db_query( $query_pc );

				if ( db_num_rows( $result_pc ) == 0 ) {

					$query_insert_pc = "insert into " . plugin_table( 'statut' ) . " (project_id, etat, statut)
											 values ('".$projet."', 'Prise_en_Charge','".$prise_charge."' )";
					db_query( $query_insert_pc );

				} elseif ( db_num_rows( $result_pc ) == 1 ) {

					$query_update_pc = "update " . plugin_table( 'statut' ) . " 
										   set statut = '" . $prise_charge  . "' 
										 where project_id = ".$projet."
										   and etat = 'Prise_en_Charge'
										";
					db_query( $query_update_pc );

				}
			}
			//Contournement
			if (isset ($_POST['Contournement']))
			{
				$contournement = $_POST['Contournement'];
				$query_co  = "select statut 
								from " . plugin_table( 'statut' ) . " 
							   where project_id = ".$projet."
								 and etat = 'Contournement'
					  ";
				$result_co = db_query( $query_co );

				if ( db_num_rows( $result_co ) == 0 ) {

					$query_insert_co = "insert into " . plugin_table( 'statut' ) . " (project_id, etat, statut)
											 values ('".$projet."', 'Contournement','".$contournement."' )";
					db_query( $query_insert_co );

				} elseif ( db_num_rows( $result_co ) == 1 ) {

					$query_update_co = "update " . plugin_table( 'statut' ) . " 
										   set statut = '" . $contournement  . "' 
										 where project_id = ".$projet."
										   and etat = 'Contournement'
										";
					db_query( $query_update_co );

				}
			}
			//Resolution
			if (isset ($_POST['Resolution']))
			{
				$resolution = $_POST['Resolution'];
				$query_re  = "select statut 
								from " . plugin_table( 'statut' ) . " 
							   where project_id = ".$projet."
								 and etat = 'Resolution'
					  ";
				$result_re = db_query( $query_re );

				if ( db_num_rows( $result_re ) == 0 ) {

					$query_insert_re = "insert into " . plugin_table( 'statut' ) . " (project_id, etat, statut)
											 values ('".$projet."', 'Resolution','".$resolution."' )";
					db_query( $query_insert_re );

				} elseif ( db_num_rows( $result_re ) == 1 ) {

					$query_update_re= " update " . plugin_table( 'statut' ) . " 
										   set statut = '" . $resolution . "' 
										 where project_id = ".$projet."
										   and etat = 'Resolution'
										";
					db_query( $query_update_re );

				}
			}
			//statuts client
			if (isset ($_POST['stat_cli']))
			{
				$tab_statut = $_POST['stat_cli'];
				$statut = implode(";", $tab_statut);
				
				$q_stat_client = "
									SELECT statut
									  FROM ". plugin_table( 'statut' ) ."
									 WHERE project_id = ". $projet ."
									   AND etat = 'Statut_client'
								 ";
				$r_stat_client = db_query( $q_stat_client );
				
				if( db_num_rows( $r_stat_client) == 0 ){
					$q_i_stat_client = "INSERT INTO " . plugin_table( 'statut' ) . " (project_id, etat, statut)
											 VALUES ('". $projet ."','Statut_client','". $statut ."')";
					db_query( $q_i_stat_client );
				}elseif ( db_num_rows( $r_stat_client) == 1 ){
					$q_u_stat_client = " update " . plugin_table( 'statut' ) . " 
										   set statut = '" . $statut . "' 
										 where project_id = ".$projet."
										   and etat = 'Statut_client'
										";
					db_query( $q_u_stat_client );
				}
			}
		}
		
		//schedule setting
		if (isset ($_POST['horaire_update']))
		{
			//h_jour : horaire jour
			if (isset ($_POST['h_jour']) || isset ($_POST['hdeb']) || isset ($_POST['hfin']) || isset($_POST['pdej']))
			{
				$hjour 	= $_POST['h_jour'];
				$hdeb 	= $_POST['hdeb'];
				$hfin 	= $_POST['hfin'];
				$pdej 	= $_POST['pdej'];
				
				$q_h_jour = " select *
								from ".plugin_table( 'horaire')."
							   where project_id = ".$projet ."
							 ";
				$r_h_jour = db_query($q_h_jour);
				if ( db_num_rows( $r_h_jour ) == 0 ) {
					$q_insert = "insert into " . plugin_table( 'horaire' ) . " (project_id, hjour, hdeb, hfin, pdej)
								 values (".$projet.",".$hjour.",'".$hdeb."','".$hfin."',".$pdej." )";
					db_query( $q_insert);
				}
				elseif ( db_num_rows( $r_h_jour ) == 1 ){
					$q_update = "update " . plugin_table( 'horaire' ) . "
									set hjour = ".$hjour."
									  ,	hdeb  = '".$hdeb."' 
									  ,	hfin  = '".$hfin."' 
									  ,	pdej  = ".$pdej." 
								  where project_id = ".$projet."
								";
					db_query( $q_update);
				}
				
			}
		}
	
		//initialisation
		if (isset ($_POST['project_init']))
		{
			if (isset ($_POST['def_proj']))
			{
				$def_projet = $_POST['def_proj'];
				//suppression des données du projet présent dans les tables
				$q_delete = " delete from mantis_plugin_SLA_struct_table where project_id = ". $projet ."";
				db_query( $q_delete );
				//Récupération des délais par défaut
				$q_defaut = "
							SELECT priorite,
								   delai,
								   valeur
							  FROM mantis_plugin_SLA_struct_table 
							 WHERE project_id = ". $def_projet ."
							 ORDER BY priorite desc
							";
				$r_defaut = db_query( $q_defaut );
				$row_count_def = db_num_rows($r_defaut);
				while( $t_row_def = db_fetch_array($r_defaut) ) {
					$t_def_prio	= $t_row_def['priorite'];
					$t_def_del	= $t_row_def['delai'];
					$t_def_val	= $t_row_def['valeur'];
					
					
					$q_insert = "
								INSERT into mantis_plugin_SLA_struct_table 
									   (project_id, priorite, delai, valeur) 
								VALUES ('". $projet ."','".$t_def_prio ."','".$t_def_del ."','".$t_def_val ."')
								";
					db_query( $q_insert );
				}
				
				//suppression des données du projet présent dans les tables
				$q_delete = "delete from mantis_plugin_SLA_statut_table where project_id = ". $projet ."";
				db_query( $q_delete );
				//Récupération des status par défaut
				$q_defaut = "
							SELECT etat,
								   statut
							  FROM mantis_plugin_SLA_statut_table 
							 WHERE project_id = ". $def_projet ."
							";
				$r_defaut = db_query( $q_defaut );
				$row_count_def = db_num_rows($r_defaut);
				while( $t_row_def = db_fetch_array($r_defaut) ) {
					$t_def_etat	= $t_row_def['etat'];
					$t_def_stat	= $t_row_def['statut'];
					
					$q_insert = "
								INSERT into mantis_plugin_SLA_statut_table 
									   (project_id, etat, statut) 
								VALUES ('". $projet ."','". $t_def_etat ."','". $t_def_stat ."') 
								";
					db_query( $q_insert );
				}
				
				//suppression des données du projet présent dans les tables
				$q_delete = "delete from mantis_plugin_SLA_horaire_table where project_id = ". $projet ."";
				db_query( $q_delete );
				//Récupération des horaires par défaut
				$q_defaut = "
							SELECT hjour,
								   hdeb,
								   hfin,
								   pdej
							  FROM mantis_plugin_SLA_horaire_table 
							 WHERE project_id = ". $def_projet ."
							";
				$r_defaut = db_query( $q_defaut );
				$row_count_def = db_num_rows($r_defaut);
				while( $t_row_def = db_fetch_array($r_defaut) ) {
					$t_def_hjou = $t_row_def['hjour'];
					$t_def_hdeb	= $t_row_def['hdeb'];
					$t_def_hfin	= $t_row_def['hfin'];
					$t_def_pdej	= $t_row_def['pdej'];
					
					$q_insert = "
								INSERT into mantis_plugin_SLA_horaire_table 
									   (project_id, hjour, hdeb, hfin, pdej) 
								VALUES ('". $projet ."','". $t_def_hjou ."','". $t_def_hdeb ."','". $t_def_hfin ."','". $t_def_pdej ."') 
								";
					db_query( $q_insert );
				}
			}
		}
	}

	
	
	if ($projet == 0){	
		$t_redirect_url = plugin_page( 'config_admin', true );
		layout_page_header( null, $t_redirect_url );
	}
	else{
		$t_redirect_url = plugin_page( 'config_projet', true );
		layout_page_header( null, $t_redirect_url );
	}
	
	if ($ko == 0){
		html_operation_successful( $t_redirect_url );
	}
	else{
		html_operation_failure( $t_redirect_url );
	}
	
	form_security_purge( 'config_admin' );
	
	layout_page_end();
?>