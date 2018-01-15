<?php
	require_once 'common_includes.php';
	require_api( '../plugins/SLA/core/sla_api.php' );
	layout_page_header();
	layout_page_begin( 'plugin.php?page=SLA/start_page' );
	//date_default_timezone_set ('Europe/Paris');
	
	$project_id                 = helper_get_current_project();
	$specific_where             = helper_project_specific_where( $project_id );
	$project_ids                = project_full( $project_id );
	$mantis_bug_table           = db_get_table( 'bug' );
	
	if ( isset( $_POST['id_sla_proj'] ) ) {
		$projet = $_POST['id_sla_proj'];
	}
	// start and finish dates and times
	$db_datetimes = array();

	$db_datetimes['start']  = strtotime( cleanDates( 'date_from', $dateFrom ) . " 00:00:00" );
	$db_datetimes['finish'] = strtotime( cleanDates( 'date_to', $dateTo ) . " 23:59:59" );
	
	// Création de la liste des projets autorisés
	function Which_projects (){
		global $project_ids;
		global $project_id;		
		global $projet;
		$count = 0;
		foreach ($project_ids as $proj){
			$count += 1;
			$name = project_get_name( $proj);
			if ( $proj == $projet ) { $selected = ' selected '; } else { $selected = ''; }		
			if ( !isset($projet)){$projet = $proj;}
			$WhichProject .= "<option " . $selected . " value='" . $proj . "'>" . $name . "</option>\n";
		}
		if($count == 0){
			$name = project_get_name( $project_id);
			$WhichProject .= "<option " . $selected . " value='" . $project_id . "'>" . $name . "</option>\n";
		}		
		
		return $WhichProject;		
	}
		
	function recup_Bogue($proj_id){
		global $db_datetimes;
		// Renvoi un tableau de Bogues
		$q_bogues = "
					SELECT id
					  FROM mantis_bug_table
					 WHERE project_id = ".$proj_id."
					   AND status not in ('5')
					   AND (date_submitted BETWEEN ". db_prepare_string($db_datetimes['start']) ." and ".db_prepare_string($db_datetimes['finish']) ." 
							OR 
							last_updated BETWEEN ". db_prepare_string($db_datetimes['start']) ." and ".db_prepare_string($db_datetimes['finish']) .")
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
			$detail_statut		= $t_details['statut'];
			$detail_lib_statut	= get_enum_element( 'status', $detail_statut);
		}
		$ligne .= "<td scope='col' align = 'center'>". $detail_lib_proj ."</td>
				   <td scope='col' align = 'center'>". $detail_lib_grav ."</td>
				   <td scope='col' align = 'center'>". $detail_date_crea ."</td>
				   <td scope='col' align = 'center'>". $detail_heure_crea ."</td>
				   <td scope='col' align = 'center'>". $detail_lib_statut ."</td>
				  ";		
		return $ligne;
	}

	function etat_bogue($etat){
		//renvoi le statut associé a un type de délai
		global $projet;
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
		$ligne .= "<td scope='col' align = 'center'>".$date."</td>";
		$ligne .= "<td scope='col' align = 'center'>".$heure."</td>";
		return $ligne;
	}
	

	
	function controle_sla_by_project(){
		global $projet;
		//récupération des statuts du projets
		$statut_pc = etat_bogue('Prise_en_Charge');
		$statut_co = etat_bogue('Contournement');
		$statut_re = etat_bogue('Resolution');
		$statu_client = recup_statut_client();
		
		if ( isset($projet ) ) {
			$table_ctrl_proj  = "<table id='sla_project' class='display nowrap table table-striped table table-bordered' cellspacing='0' width='100%'>";
			$table_ctrl_proj .= "<thead><tr>";
			$table_ctrl_proj .= "
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Bogue_ID') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Proj_lib') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Gravite') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Date_crea') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Heure_crea') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_statut') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Date_P_Charge') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Heure_P_Charge') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Date_Cont') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Heure_Cont') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Date_reso') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Heure_reso') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Delai_P_Charge') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Delai_Cont') ."</td>
								 <td scope='col' align = 'center'>". lang_get('plugin_SLA_Tps_reso') ."</td>
								";
			$table_ctrl_proj .= "</tr></thead>";			
		//fin des entêtes
		//Récupération de la lise des bogues du projet
			$tab_bogues = recup_Bogue($projet);
		//Récupération des différents données Bogues par bogues			
			foreach ($tab_bogues as $key => $bug)
			{
				$bogue = $tab_bogues[$bug];
				$table_ctrl_proj .= "<tr><td scope='col' align = 'center'><a href='/mantisbt/view.php?id=".$bogue."'>".str_pad($bogue, 7, '0', STR_PAD_LEFT)."</a></td>";
				$table_ctrl_proj .= Detail_bogue($bogue);
				
				//Récupération de la date et de l'heure de prise en charge
				$table_ctrl_proj .= recup_hd ($bogue, $statut_pc);
								
				//Récupération de la date et de l'heure de contournement
				$precision = '=';
				$table_ctrl_proj .= recup_hd ($bogue, $statut_co, $precision);
				
				//récupération de la date et de l'heure de résolution
				$table_ctrl_proj .= recup_hd ($bogue, $statut_re);
				
				//Calcul du délai de prise en charge
				$time_pc = nb_minutes($projet, $bogue, $statut_pc);
				$tot_affiche = minute_to_heure($time_pc);
				$pastille = '';
				if ($time_pc != '/'){
					$pastille = delai_ok($projet, $bogue, 'Prise en charge', $time_pc);
				}				
				$table_ctrl_proj .= "<td scope='col' align = 'center'>". $tot_affiche ." ". $pastille ."</td>";
				
				//Calcul du délai de countournement
				$precision = '=';
				$affichage = '0'; // on ne le calcule que si le statut est utilisé
				$time_co = nb_minutes($projet, $bogue, $statut_co, $precision, $affichage);
				$time_cli_co = '';
				$tot_affiche = $time_co;
				$pastille = '';
				if ($time_co != '/'){
					$time_cli_co = nb_h_client($projet, $bogue, $statut_co,0);
					$total = $time_co - $time_cli_co;
					$tot_affiche = minute_to_heure($total);
					$pastille = delai_ok($projet, $bogue, 'Contournement', $total);
				}
				$table_ctrl_proj .= "<td scope='col' align = 'center'>". $tot_affiche ." ". $pastille ."</td>";
				
				//Calcul du délai de résolution
				$time_re = nb_minutes($projet, $bogue, $statut_re);
				$time_cli_re = '';
				$tot_affiche = $time_re;
				$pastille = '';
				if ($time_re != '/'){
					$time_cli_re = nb_h_client($projet, $bogue, $statut_re);
					$total = $time_re - $time_cli_re;
					$tot_affiche = minute_to_heure($total);
					$pastille = delai_ok($projet, $bogue, 'Résolution', $total);
				}				
				$table_ctrl_proj .= "<td scope='col' align = 'center'>". $tot_affiche ." ". $pastille ."</td>";
				
				$table_ctrl_proj .= "</tr>";
			}		
		}
		$table_ctrl_proj .= "</table>";
		
		return $table_ctrl_proj;
	}
	
	
$main_js = <<<EOT
$(document).ready(function() {
    $('#sla_project').DataTable( {
        "scrollX": true
    } );
} );

EOT;

$main_js .= <<<EOT
		],
		$dt_language_snippet
		} );

	} );
EOT;

	$_SESSION['controle_sla_main_js'] = $main_js;
?>
<script type='text/javascript' src="<?php echo plugin_page( 'csp_support&r=ctrsla' ); ?>"></script>

<div id="wrapper">	
	<div class="col-md-12 col-xs-12">
		<div class="form-container">
			<form action="<?php echo plugin_page( 'controle_sla' ) ?>" method="post">
				<?php echo form_security_field( 'date_picker' ) ?>
				<fieldset>
					<div id="filter">
						<p  class="font-weight-bold" class="widget-title lighter" style='display: inline'>
							<?php echo lang_get( 'plugin_SLA_project_select' ) ?>
						</p>
						<select id = 'id_sla_proj' name = 'id_sla_proj'>
							<?php echo Which_projects ();?>
						</select>
						<div>
							<p  class="font-weight-bold" class="widget-title lighter" style='display: inline'>
								<?php echo lang_get( 'plugin_SLA_date_select' ) ?>
							</p>
							<input type="text" id="date_from" name="date_from" class="datetimepicker input-sm"
								data-picker-locale="<?php echo lang_get_current_datetime_locale() ?>"
								data-picker-format="Y-MM-DD"
								size="12" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
							<i class="fa fa-calendar fa-xlg datetimepicker"></i>
							<span class="widen20">-</span>
							<input type="text" id="date_to" name="date_to" class="datetimepicker input-sm"
								data-picker-locale="<?php echo lang_get_current_datetime_locale() ?>"
								data-picker-format="Y-MM-DD"
								size="12" value="<?php echo cleanDates('date-to', $dateTo); ?>" />
							<i class="fa fa-calendar fa-xlg datetimepicker"></i>
						</div>
						<span class="widen10">&nbsp;</span>
						<input id="ctr_SLA_display" name="ctr_SLA_display" type="submit" class="btn btn-primary btn-white btn-round" value=<?php echo lang_get( 'plugin_SLA_display' ); ?> class="button" />
					</div>
				</fieldset>
			</form>
			<div class="space-10"></div>
			<div class="container">
				<div class="widget-toolbox padding-8 clearfix">
					<div class="btn-toolbar">
						<div class="btn-group pull-right">
							<?php
								# -- Print and Export links --
								print_small_button( 'plugins/SLA/pages/extract_csv.php?idproj='.$projet.'', lang_get( 'csv_export' ) );
								print_small_button( 'plugins/SLA/pages/extract_excel_xml.php?idproj='.$projet.'', lang_get( 'excel_export' ) ); 
							?>
						</div>
					</div>
				</div>
				<div id="sla_project_wrapper" class="dataTables_wrapper no-footer" style="overflow-x : auto">
					<?php echo controle_sla_by_project()?>
				</div>
			</div>
			<div class="space-10"></div>
		</div>
	</div>
</div>
<?php layout_page_end();