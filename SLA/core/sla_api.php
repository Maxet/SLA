<?php

require_api( 'project_api.php' );
require_api( 'user_api.php' );

function project_full( $p_project_id, $p_user_id = null ) {
	//renvoi un tableau de tous les projets accessible pour l'utilisateur connecté 
	if( null === $p_user_id ) {
		$p_user_id = auth_get_current_user_id();
	}

	$t_project_ids = user_get_all_accessible_projects( $p_user_id, $p_project_id );

	if( 0 == count( $t_project_ids ) ) {
		$t_project_filter = ' 1<>1';
	} else if( 1 == count( $t_project_ids ) ) {
		$t_project_filter =  reset( $t_project_ids );
	} else {
		$t_project_filter = explode(',',join( ',', $t_project_ids ));
	}

	return $t_project_filter;
}

// Fonction permettant de compter le nombre de jours ouvrés entre deux dates
function get_nb_open_days($date_start, $date_stop) {
	$date_star = date('Y-m-d',$date_start);
	$arr_bank_holidays = array(); // Tableau des jours feriés	
	
	// On boucle dans le cas où l'année de départ serait différente de l'année d'arrivée
	$diff_year = date('Y', $date_stop) - date('Y', $date_start);
	for ($i = 0; $i <= $diff_year; $i++) {			
		$year = (int)date('Y', $date_start) + $i;
		// Liste des jours feriés
		$arr_bank_holidays[] = '1_1_'.$year; // Jour de l'an
		$arr_bank_holidays[] = '1_5_'.$year; // Fete du travail
		$arr_bank_holidays[] = '8_5_'.$year; // Victoire 1945
		$arr_bank_holidays[] = '14_7_'.$year; // Fete nationale
		$arr_bank_holidays[] = '15_8_'.$year; // Assomption
		$arr_bank_holidays[] = '1_11_'.$year; // Toussaint
		$arr_bank_holidays[] = '11_11_'.$year; // Armistice 1918
		$arr_bank_holidays[] = '25_12_'.$year; // Noel
				
		// Récupération de paques. Permet ensuite d'obtenir le jour de l'ascension et celui de la pentecote	
		$easter = easter_date($year);
		$arr_bank_holidays[] = date('j_n_'.$year, $easter + 86400); // Paques
		$arr_bank_holidays[] = date('j_n_'.$year, $easter + (86400*39)); // Ascension
		$arr_bank_holidays[] = date('j_n_'.$year, $easter + (86400*50)); // Pentecote	
	}
	
	$nb_days_open = -1;
	// Mettre <= si on souhaite prendre en compte le dernier jour dans le décompte	
	while ($date_start < $date_stop) {
		// Si le jour suivant n'est ni un dimanche (0) ou un samedi (6), ni un jour férié, on incrémente les jours ouvrés	
		if (!in_array(date('w', $date_start), array(0, 6)) 
		&& !in_array(date('j_n_'.date('Y', $date_start), $date_start), $arr_bank_holidays)) {
			$nb_days_open++;		
		}
		$date_start = mktime(date('H', $date_start), date('i', $date_start), date('s', $date_start), date('m', $date_start), date('d', $date_start) + 1, date('Y', $date_start));			
	}
	//return $date_star;
	return $nb_days_open;
}

function nb_minutes($project_id, $bogue, $statut, $precision = '>=', $affichage = '1'){
	/* renvoi un nombre de minutes correspondant à la différnece entre la date et l'heure de soummission du bogue
		et la date et l'heure de passage au statut donné en paramètre.
	*/	
	$q_var_h = "
				SELECT hjour, hdeb, hfin, pdej
				  FROM mantis_plugin_SLA_horaire_table
				 where project_id = '".$project_id."'
				";
	$r_var_h = db_query($q_var_h);
	while ( $t_var_h = db_fetch_array($r_var_h)){
		$hjour = $t_var_h['hjour'];
		$hdeb = $t_var_h['hdeb'];
		$hfin = $t_var_h['hfin'];
		$pdej = $t_var_h['pdej'];
	}
	
	$q_diff = "
				SELECT b.date_submitted as 'date_cre',h.date_modified as 'date_mod'
				  FROM mantis_bug_history_table h 
					INNER JOIN mantis_bug_table b on (h.bug_id = b.id)
				 WHERE h.bug_id=". $bogue ."
				   AND h.field_name = 'status'
				   AND h.new_value". $precision ." ". $statut ."
				 ORDER BY h.date_modified 
				 LIMIT 1
			   ";
	$r_diff = db_query( $q_diff);
	$count = 0;
	while ( $t_date = db_fetch_array($r_diff)){
		$count +=1;
		$date1 = date('Y-m-d H:i:s',$t_date['date_cre']);		
		$date2 = date('Y-m-d H:i:s',$t_date['date_mod']);
		
		$datem1 = date('Y-m-d',$t_date['date_cre']);
		$datem2 = date('Y-m-d',$t_date['date_mod']);
		$dateh1 = date('H:i',$t_date['date_cre']);
		$dateh2 = date('H:i',$t_date['date_mod']);
	}	
	IF ($count != 0 || $affichage != '0'){
		IF ($count == 0){
			//on utilise la date et l'heure actuelle
			$q_dat_cre = "
							SELECT date_submitted
							  FROM mantis_bug_table
							 WHERE id = ". $bogue ."
						 ";
			$r_dat_cre = db_query ( $q_dat_cre );
			while ( $t_dat_cre = db_fetch_array( $r_dat_cre )){
				$date1 = date('Y-m-d H:i:s',$t_dat_cre['date_submitted']);
			}
			$date2 = date('Y-m-d H:i:s');//datetime soit de la modification soit de la date du jour		
			$datem1 = date('Y-m-d',strtotime($date1)); // date uniquement de la date de creation du bogue
			$datem2 = date('Y-m-d'); //date uniquement de la modification soit de la date du jour
			$dateh1 = date('H:i',strtotime($date1)); //heures uniquement de la date de creation du bogue
			$dateh2 = date('H:i'); //heures uniquement de la modification soit de la date du jour
		}
		//calcul nombre de jours pleins :
		if ($datem1 != $datem2){
			$nb_jours = get_nb_open_days(strtotime($datem1), strtotime($datem2));
			$heures = $hjour * $nb_jours;
			$temps += $heures * 60;
			//calcul du nombre d'heure le premier jour (on converti en minutes pour le calcul)
			$hf = heure_to_minute(strtotime($hfin)); 
			$hc = heure_to_minute(strtotime($dateh1));
			$temps += $hf - $hc;
			//calcul du nombre d'heure le dernier jour
			$hd = heure_to_minute(strtotime($hdeb));
			$hc = heure_to_minute(strtotime($dateh2));
			$temps += $hc - $hd;

			$affiche = str_pad($temps, 2, '0', STR_PAD_LEFT);
			//######################################################### A MODIFIER intégrer la pause déjeuner????
		}else{
			$hdep = heure_to_minute(strtotime($dateh1));
			$hfin = heure_to_minute(strtotime($dateh2));
			$tps = $hfin - $hdep;
			
			$affiche = str_pad($tps, 2, '0', STR_PAD_LEFT);	
			
		}
		$ecart = $affiche;
		
	}else {
		$ecart = '/';
	}
	return $ecart;	
}

function recup_statut_client($id_proj){
	// fonction qui récupère les statut qui correspondent à tu temps d'attente coté client
	$q_stat_cli = "
					SELECT statut
					  FROM mantis_plugin_SLA_statut_table
					 WHERE project_id = '". $id_proj ."'
					   AND etat = 'Statut_client'
				  ";
	$r_stat_cli = db_query( $q_stat_cli );
	while ($t_stat_cli = db_fetch_array($r_stat_cli)){
		$stat_cli = $t_stat_cli['statut'];
	}
	return $stat_cli;
}

function nb_h_client($pro_id, $bogue, $statut, $affichage = '1'){
	// function qui renvoi le nmbre de minutes ou le bogue était sur un statut côté client.
	$stat_cli = recup_statut_client($pro_id);
	
	$t_stat_cli = explode ( ";", $stat_cli);
	
	$q_var_ho = "
				SELECT hjour, hdeb, hfin, pdej
				  FROM mantis_plugin_SLA_horaire_table
				 where project_id = ".$pro_id."
				";
	$r_var_ho = db_query($q_var_ho);
	while ( $t_var_h = db_fetch_array($r_var_ho)){
		$hjour = $t_var_ho['hjour'];
		$hdeb = $t_var_ho['hdeb'];
		$hfin = $t_var_ho['hfin'];
		$pdej = $t_var_ho['pdej'];
	}
	
	
	foreach ($t_stat_cli as $cli_stat){
		IF ( $cli_stat < $statut){
			$q_n_val = "
						SELECT date_modified as 'deb_stat'
						  FROM mantis_bug_history_table
						 WHERE bug_id = ". $bogue ."
						   AND field_name = 'status'
						   AND new_value = ". $cli_stat ."
						 ORDER BY date_modified
					   ";
			$r_q_n_val = db_query( $q_n_val );
			while( $t_n_val = db_fetch_array( $r_q_n_val )){
				$date_n_val = $t_n_val['deb_stat'];
				
				$q_o_val = "
							SELECT min(date_modified) as 'fin_stat'
							  FROM mantis_bug_history_table
							 WHERE bug_id = ". $bogue ."
							   AND field_name = 'status'
							   AND old_value = ". $cli_stat ."
							   AND date_modified > ". $date_n_val ."
							 ORDER BY date_modified
						   ";
				$r_o_val = db_query( $q_o_val );
				$count = 0;
				while( $t_o_val = db_fetch_array( $r_o_val)){
					$count +=1;
					$date_o_val = $t_o_val['fin_stat'];
				}
				
				IF ($count != 0 || $affichage != '0'){
					IF ($count == 0){
						$date_o_val = date('Y-m-d H:i:s');
					}
					$date1 = date('Y-m-d H:i:s',$date_n_val);		
					$date2 = date('Y-m-d H:i:s',$date_o_val);
					
					$datem1 = date('Y-m-d',$date_n_val);
					$datem2 = date('Y-m-d',$date_o_val);
					$dateh1 = date('H:i',$date_n_val);
					$dateh2 = date('H:i',$date_o_val);
					
					//calcul nombre de jours pleins :
					if ($datem1 != $datem2){
						$nb_jours = get_nb_open_days(strtotime($datem1), strtotime($datem2));
						$heure += $hjour * $nb_jours; 
						$temps += $heure * 6;
						//calcul du nombre d'heure le premier jour
						$hf = heure_to_minute(strtotime($hfin)); 
						$hc = heure_to_minute(strtotime($dateh1));
						$temps += $hf - $hc;
						//calcul du nombre d'heure le dernier jour
						$hd = heure_to_minute(strtotime($hdeb));
						$hc = heure_to_minute(strtotime($dateh2));
						$temps += $hc - $hd;
					}else{
						$hdep = heure_to_minute(strtotime($dateh1));
						$hfin = heure_to_minute(strtotime($dateh2));
						$temps += $hfin - $hdep;	
					}
				}// fin calcul ecart				
			}//fin while n val
			$affiche = $temps;
		}//fin du if ($cli_stat < $statut)
	}//fin foreach
	return $affiche;	
}//fin function

function delai_ok($project_id, $bogue, $delai, $temps){
	// fonction qui contrôle si le délai est respecte les SLA.
	
	// Récupération de la gravité du bogue.
	$q_detail="
			SELECT b.severity as 'gravit'				      
			  FROM mantis_bug_table b 			 
			 WHERE b.id= ".$bogue
		;
	$r_detail = db_query( $q_detail );
	while ($t_details = db_fetch_array($r_detail)){
		$detail_gravite		= $t_details['gravit'];
		$detail_lib_grav    = get_enum_element( 'severity', $detail_gravite );			
	}
	// recuperation du delai accorde pour la gravite du bug et le statut demandé	
	$q_delai = "
				SELECT valeur
				  FROM ". plugin_table('struct') ."
				 WHERE project_id = '". $project_id ."'
				   AND delai = '". $delai ."'
				   AND priorite = '". $detail_gravite ."'				   
				";
	$r_delai = db_query( $q_delai );
	while ($t_delai = db_fetch_array( $r_delai )){
		$h_delai = $t_delai['valeur'];
	}
	$pastille = '';
	IF ($h_delai > 0 ){
		$m_total = $h_delai * 60;
		IF ($m_total >= $temps){
			// pastille verte
			$pastille= "<span class='glyphicon glyphicon-ok-sign' aria-hidden='true' style='color:green'></span>";
		}ELSE{
			//pastille rouge
			$pastille= "<span class='glyphicon glyphicon-remove-sign' aria-hidden='true' style='color:red'></span>";
		}
	}
	return $pastille;
	
}

function heure_to_minute ($heure){
	// fonction qui converti les heures en une somme de mminutes
	$h_base = idate('H',$heure);
	$m_base = idate('i',$heure);	
	$m_heure = $h_base * 60;	
	$minutes = $m_base + $m_heure;
	
	return $minutes;
}

function minute_to_heure ($minutes){
	// focntion qui converti les minutes en heures.
	IF ($minutes > 60){
		$h = floor($minutes/60);
		$m = $minutes%60;
		$heure = str_pad($h, 2, '0', STR_PAD_LEFT).':'.str_pad($m, 2, '0', STR_PAD_LEFT);
	}ELSEIF($minutes < 0){
		$heure = '00:00';
	}ELSE{
		$heure = '00:'.str_pad($minutes, 2, '0', STR_PAD_LEFT);
	}
	return $heure;
}

?>