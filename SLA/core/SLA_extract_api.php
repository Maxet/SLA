<?php
	require_api( 'authentication_api.php' );
	require_api( 'bug_api.php' );
	require_api( 'category_api.php' );
	require_api( 'config_api.php' );
	require_api( 'constant_inc.php' );
	require_api( 'file_api.php' );
	require_api( 'helper_api.php' );
	require_api( 'project_api.php' );
	require_api( 'user_api.php' );
	require_api( 'excel_api.php' );
	require_api( '../plugins/SLA/core/sla_api.php' );
	
	
	
	function report_get_columns() {	
		//retourne un tableau avec les entêtes de colonnes.
		$t_columns = array( ''. lang_get( 'plugin_SLA_Bogue_ID' ) .''
						   ,''. lang_get( 'plugin_SLA_Proj_lib' ) .''
						   ,''. lang_get( 'plugin_SLA_Gravite' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_crea' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_crea' ) .''
						   ,''. lang_get( 'plugin_SLA_statut' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_P_Charge' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_P_Charge' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_Cont' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_Cont' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_reso' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_reso' ) .''
						   ,''. lang_get( 'plugin_SLA_Delai_P_Charge' ) .''
						   ,''. lang_get( 'plugin_SLA_Delai_Cont' ) .''
						   ,''. lang_get( 'plugin_SLA_Tps_reso' ) .''
						  );	
		return $t_columns;
	}
	
	function recup_Bogue($proj_id){
		// Renvoi un tableau de Bogues
		$q_bogues = "
					SELECT id
					  FROM mantis_bug_table
					 WHERE project_id = ".$proj_id ."
					 ORDER BY id
					";
				   
		$r_bogues = db_query( $q_bogues );
		$t_tab_bug = array();
		While ($t_bogues = db_fetch_array($r_bogues)){
			$t_tab_bugs[$t_bogues['id']] = $t_bogues['id'];
		}
		return $t_tab_bugs;
	}
	
	function Detail_bogue($bogue_id){
		//on récupère les informations suivantes : 'ID','ID Projet','Libellé projet','Gravité','Date Création','heure création'
		$q_detail="
			SELECT b.id as 'BOGUE_ID',
				   b.project_id as 'id_projet',
				   p.name as'lib_projet', 
				   b.severity as 'gravit',
				   b.date_submitted as 'date_cre',
				   b.status as statut
			  FROM mantis_bug_table b 
			 INNER JOIN mantis_project_table p on (b.project_id = p.id)
			 WHERE b.id= ".$bogue_id
		;
		$r_detail = db_query( $q_detail );
		while ($t_details = db_fetch_array($r_detail)){
			$detail_id_proj		= $t_details['id_projet'];
			$detail_lib_proj	= $t_details['lib_projet'];
			$detail_gravite		= $t_details['gravit'];
			$detail_lib_grav    = get_enum_element( 'severity', $detail_gravite );
			$detail_date_crea	= date('d/m/Y',$t_details['date_cre']);
			$detail_heure_crea	= date('H:i',$t_details['date_cre']);
			$detail_statut 		= $t_details['statut'];
			$detail_lib_statut	= get_enum_element( 'status', $detail_statut );
		}
		$ligne = array ( ''. $detail_lib_proj .''
						,''. $detail_lib_grav .''
						,''. $detail_date_crea .''
						,''. $detail_heure_crea .''
						,''. $detail_lib_statut .''
					   );		
		return $ligne;
	}
	
	function recup_hd ($bogue, $statut, $precision = '>='){
		//renvoi la date et l'heure de la première utilisation du statut donné en paramètre
		if(isset($statut)){
			$q_date = " 
						SELECT date_modified as 'date'
						  FROM mantis_bug_history_table 
						 WHERE bug_id=". $bogue ."
						   AND field_name = 'status'
						   AND new_value". $precision ." ". $statut ."
						 ORDER BY date_modified 
						 LIMIT 1
					  ";
			$r_date = db_query( $q_date );
			While ($t_date = db_fetch_array($r_date)){
				$date = $t_date['date'];
			}
			IF (isset($date)){
				$heure = date('H:i',$date);
				$date = date('d/m/Y',$date);			
			}else{
				$date = '/';
				$heure= '/';
			}
		}else{
			$date = lang_get('plugin_SLA_Empty_Ref');
			$heure= lang_get('plugin_SLA_Empty_Ref');
		}
		$ligne = Array( '' .$date. ''
					   ,'' .$heure. ''
					  );
		return $ligne;
	}
	
	function etat_bogue($etat, $projet){
		//renvoi le statut associé a un type de délai
		$statut = NULL;
		$q_statut = "
					 SELECT statut
					   FROM mantis_plugin_SLA_statut_table 
					  WHERE project_id = ". $projet ."
						AND etat = '". $etat ."'
					";
		$r_statut = db_query( $q_statut );
		While ($t_statut = db_fetch_array($r_statut)){
			$statut = $t_statut['statut'];
		}
		return $statut;
	}
	
	function report_get_content($proj_id){
		//Récupération des statuts
		$statut_pc = etat_bogue('Prise_en_Charge', $proj_id);
		$statut_co = etat_bogue('Contournement', $proj_id);
		$statut_re = etat_bogue('Resolution', $proj_id);
		
		//Récupération de la lise des bogues du projet
		$tab_bogues = recup_Bogue($proj_id);
		$boucle = 0;
		//Récupération des différents données Bogues par bogues
		foreach ($tab_bogues as $key => $bug)
		{
			$bogue = $tab_bogues[$bug];
			$t_content[$boucle][] = str_pad($bogue, 7, '0', STR_PAD_LEFT);
			//récupération des données non calculées.
			$tab_detail_bug = Detail_bogue($bogue);
			foreach ($tab_detail_bug as $detail){
				$t_content[$boucle][] = $detail;					
			}				
			//Récupération de la date et de l'heure de prise en charge
			$tab_pc = recup_hd ($bogue, $statut_pc);
			foreach ($tab_pc as $detail){
				$t_content[$boucle][] = $detail;					
			}								
			//Récupération de la date et de l'heure de contournement
			$precision = '=';
			$tab_co = recup_hd ($bogue, $statut_co, $precision);
			foreach ($tab_co as $detail){
				$t_content[$boucle][] = $detail;					
			}
			//récupération de la date et de l'heure de résolution
			$tab_re = recup_hd ($bogue, $statut_re);
			foreach ($tab_re as $detail){
				$t_content[$boucle][] = $detail;					
			}
			//Calcul du délai de prise en charge
			$time_pc = nb_minutes($proj_id, $bogue, $statut_pc);
			$tot_affiche = minute_to_heure($time_pc);
			$t_content[$boucle][] = $tot_affiche;
			
			//Calcul du délai de countournement
			$precision = '=';
			$affichage = '0'; // on ne le calcule que si le statut est utilisé
			$time_co = nb_minutes($proj_id, $bogue, $statut_co, $precision, $affichage);
			$time_cli_co = '';
			$tot_affiche = $time_co;
			if ($time_co != '/'){
				$time_cli_co = nb_h_client($proj_id, $bogue, $statut_co,0);
				$total = $time_co - $time_cli_co;
				$tot_affiche = minute_to_heure($total);
			}
			$t_content[$boucle][] =  $tot_affiche ;
			
			//Calcul du délai de résolution
			$time_re = nb_minutes($proj_id, $bogue, $statut_re);
			$time_cli_re = '';
			$tot_affiche = $time_re;
			if ($time_re != '/'){
				$time_cli_re = nb_h_client($proj_id, $bogue, $statut_re);
				$total = $time_re - $time_cli_re;
				$tot_affiche = minute_to_heure($total);
			}				
			$t_content[$boucle][] = $tot_affiche ;
			
			$boucle += 1;
		}
		return $t_content;
	}
	
	function report_excel_get_columns(){
		//retourne un tableau avec les entêtes de colonnes.
		$t_entetes = array( ''. lang_get( 'plugin_SLA_Bogue_ID' ) .''
						   ,''. lang_get( 'plugin_SLA_Proj_lib' ) .''
						   ,''. lang_get( 'plugin_SLA_Gravite' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_crea' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_crea' ) .''
						   ,''. lang_get( 'plugin_SLA_statut' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_P_Charge' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_P_Charge' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_Cont' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_Cont' ) .''
						   ,''. lang_get( 'plugin_SLA_Date_reso' ) .''
						   ,''. lang_get( 'plugin_SLA_Heure_reso' ) .''
						   ,''. lang_get( 'plugin_SLA_Delai_P_Charge' ) .''
						   ,''. lang_get( 'plugin_SLA_Delai_Cont' ) .''
						   ,''. lang_get( 'plugin_SLA_Tps_reso' ) .''
						  );
						  
		$report_excel_columns = excel_get_start_row( $p_style_id );
		foreach( $t_entetes as $t_entete ) {
			$report_excel_columns .= excel_format_column_title( column_get_title( $t_entete ) );
		}
		$report_excel_columns .= '</Row>';
		
		return $report_excel_columns;
	}
	
?>