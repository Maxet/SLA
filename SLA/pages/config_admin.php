<?php
	if( !defined( 'MANTIS_VERSION' ) ) { exit(); }
	
	auth_reauthenticate( );
	access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
	require_api( '../plugins/SLA/core/sla_api.php' );
	
	layout_page_header( lang_get( 'plugin_format_title' ) );
	layout_page_begin( 'manage_overview_page.php' );
	print_manage_menu( 'manage_plugin_page.php' );
	
	$specific_where             = helper_project_specific_where( $project_id );
	$mantis_project_table		= 'mantis_project_table';
	require_once 'common_includes.php';
	
	if ( isset( $_POST['sla_proj'] ) ) {
		$projet = $_POST['sla_proj'];
	}
	
	$project_list = project_names(); 
	foreach ( $project_list as $key=>$val) {
		if ( $key == $projet ) { $selected = ' selected '; } else { $selected = ''; }		
		if ( !isset($projet)){$projet = $key;}
		$whichProject .= "<option " . $selected . " value='" . $key . "'>" . $project_list[$key] . "</option>\n";
	}
		
	function affiche_sla_by_project(){
		global $projet;
		$table_sla_proj  = $projet;
		if ( isset($projet ) ) {
			
			$table_sla_proj  = "<table class='table table-striped table table-bordered'>";
			$table_sla_proj .= "<thead><tr>";
			$table_sla_proj .= "<td rowspan = 2 colspan = 2 style='visibility:hidden'></td>
								<td colspan = 4  align = 'center'>".lang_get('plugin_SLA_head_delai')."</td></tr><tr>";
			$table_sla_proj .= "<td scope='col' align = 'center'>".lang_get('plugin_SLA_Prise_en_charge')."</td>
								<td scope='col' align = 'center'>".lang_get('plugin_SLA_Contournement')."</td>
								<td scope='col' align = 'center'>".lang_get('plugin_SLA_Resolution')."</td>";
			$table_sla_proj .= "</tr></thead>";
			$table_sla_proj .= "<tr><td rowspan = 7 >".lang_get('plugin_SLA_head_gravite')."</td>";
			$query_sla="
						SELECT priorite,
							   SUM(IF(delai = 'Prise en Charge', valeur, 0)) AS pc,
							   SUM(IF(delai = 'Contournement', valeur, 0)) AS co,
							   SUM(IF(delai = 'Résolution', valeur, 0)) AS re
						  FROM mantis_plugin_SLA_struct_table 
						 WHERE project_id = ". $projet ."
						 GROUP BY priorite
						 ORDER BY priorite desc
			";
			$result_sla = db_query( $query_sla );
			$row_count_sla = db_num_rows($result_sla);
			while( $t_row_sla = db_fetch_array($result_sla) ) {
				$t_sla_prio	= $t_row_sla['priorite'];
				$t_sla_pc	= $t_row_sla['pc'];
				$t_sla_co	= $t_row_sla['co'];
				$t_sla_re	= $t_row_sla['re'];
				//recherche du libelle
				$t_sla_libprio = get_enum_element( 'severity', $t_sla_prio );
				
				switch ($t_sla_pc){
					case 0:
					$t_sla_pc = lang_get("plugin_SLA_no_applicable");
					break;
					case -1:
					$t_sla_pc = lang_get("plugin_SLA_en_accord");
					break;
				}
				switch ($t_sla_co){
					case 0:
					$t_sla_co = lang_get("plugin_SLA_no_applicable");
					break;
					case -1:
					$t_sla_co = lang_get("plugin_SLA_en_accord");
					break;
				}			
				switch ($t_sla_re) {
					case 0:
					$t_sla_re = lang_get("plugin_SLA_no_applicable");
					break;
					case -1:
					$t_sla_re = lang_get("plugin_SLA_en_accord");
					break;
				}
				
				
				
				$table_sla_proj .= "<tr>";
				$table_sla_proj .= "<td scope='row'>". $t_sla_libprio ."</td>
									<td scope='row' align = 'center'>". $t_sla_pc ."</td>
									<td scope='row' align = 'center'>". $t_sla_co ."</td>
									<td scope='row' align = 'center'>". $t_sla_re ."</td>
				";
				$table_sla_proj .= "</tr>";
			}//fin while $t_row_date_changed
			$table_sla_proj .= "</table>";
			
			return $table_sla_proj;
		}
	}
	
	function whichStatus($etat){
		global $projet;
		$query_co  = "select statut 
						from " . plugin_table( 'statut' ) . " 
					   where project_id = ".$projet."
						 and etat = '". $etat ."'
		";
		$result_co = db_query( $query_co );
		while ($t_donnees = db_fetch_array($result_co)){
			$statut = $t_donnees['statut'];
		}
		$t_config_var_value = config_get( 'status_enum_string', null, null, 1 ); //récupération de tous les statuts
		
		$t_enum_values = MantisEnum::getValues( $t_config_var_value ); // récupération des libellés des statuts
		foreach ( $t_enum_values as $t_enum_value ) {
			// contruction du tableau avec l'id du statut et son libellé
			$t_enum_list[$t_enum_value] = get_enum_element( 'status', $t_enum_value );
		}
		
		foreach ( $t_enum_list as $key=>$val) {
			if ( $key == $statut ) { $selected = ' selected '; } else { $selected = ''; }			
			if ( !isset($statut)){$statut = $key;}
			$StatutList .= "<option " . $selected . " value='" . $key . "'>" . $val ."</option>\n";
		}
		
		return $StatutList;
	}
	
	function WhichPriorite(){
		global $projet;
		
		$t_config_value_prio = config_get( 'severity_enum_string', null, null, 1); //récupération de toutes les sévérités
		
		$t_enum_prios = MantisEnum::getValues( $t_config_value_prio ); // récupération des libellés des sévérités
		foreach ( $t_enum_prios as $t_enum_prio ) {
			$t_enum_prio_list[$t_enum_prio] = get_enum_element( 'severity', $t_enum_prio );// contruction du tableau avec l'id de la sévérité et son libellé
		}
		$selected = 'selected';
		foreach ( $t_enum_prio_list as $key=>$val) {
			$PrioriteList .= "<option " . $selected . " value='" . $key . "'>" . $val ."</option>\n";
			$selected = '';
		}
		return $PrioriteList;
	}
	
	function which_heure($select){
		global $projet;
		$q_heure = "
					 SELECT ".$select."
					   FROM " . plugin_table( 'horaire' ) . "
					  where project_id = ".$projet." 
					";
		$r_heure = db_query( $q_heure );
		$horo = 7;
		While ($t_heure = db_fetch_array($r_heure)){
			$horo = $t_heure['hjour'];
		}
		for ($i = 1; $i <= 24; $i++) {
			if ( $i == $horo ) { $selected = ' selected '; } else { $selected = ''; }
			$h_jour .= "<option " . $selected . " value='" . $i . "'>" . $i ." ".lang_get('plugin_SLA_heure')." </option>\n";
		}
		return $h_jour;
	}
	
	function recup_heure($select){
		global $projet;
		$q_recup = "
					SELECT ".$select."
					   FROM " . plugin_table( 'horaire' ) . "
					  where project_id = ".$projet." 
					";
		$r_recup = db_query( $q_recup );
		While ($t_recup = db_fetch_array($r_recup)){
			$recup = $t_recup[$select];
		}
		return $recup;
	}
	
?>
<div id="wrapper">	
	<div class="col-md-12 col-xs-12">
		<div class="space-10"></div>
		<div class="form-container">
			<form action="<?php echo plugin_page( 'config_admin' ) ?>" method="post">
				<fieldset>
					<div>
						<h4  class="font-weight-bold" class="widget-title lighter" style='display: inline'>
							<?php echo lang_get( 'plugin_SLA_project_select' ) ?>
						</h4>
						<select id="sla_proj" name='sla_proj' >
							<?php echo $whichProject;?>
						</select>
						<input id="project_update" name="project_update" type="submit" class="btn btn-primary btn-white btn-round" value=<?php echo lang_get( 'plugin_SLA_display' ); ?> class="button" />
					</div>
					<div class="space-10"></div>
					<div class="widget-box widget-color-blue2">
						<div class="widget-header widget-header-small">
							<h4 class="widget-title lighter">
								<i class="ace-icon fa fa-exchange"></i>
								<?php echo lang_get( 'plugin_SLA_config_Tab' ) ?>
							</h4>
						</div>						
						<div class="widget-body">
						</form>
						<form action="<?php echo plugin_page( 'config_admin_projet' ) ?>" method="post">
							<?php echo form_security_field( 'config_admin' ) ?>
							<input type="hidden" value="<?php echo $projet?>" name="idpro"/>
							<div class="space-10"></div>
							<div class="container">
								<div class="col align-self-center">
									<?php echo affiche_sla_by_project()?>
								</div>
							</div>
							<div class="space-10"></div>
							<div class="container">
								<div class="col align-self-center">
									<strong><?php echo lang_get('plugin_SLA_maj_valeur'); ?></strong>
									<div class="space-10"></div>
									<table class="table table-sm table table-bordered">
										<thead>
											<tr>
												<th scope="col"><?php echo lang_get('plugin_SLA_head_delai'); ?></th>
												<th scope="col"><?php echo lang_get('plugin_SLA_head_gravite'); ?></th>
												<th scope="col"><?php echo lang_get('plugin_SLA_head_valeur'); ?></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td class="row">
													<select id="Delai" name="Delai" class="input-sm">
														<option "selected" value='1'><?php echo lang_get('plugin_SLA_Prise_en_charge'); ?></option>
														<option value='2'><?php echo lang_get('plugin_SLA_Contournement'); ?></option>
														<option value='3'><?php echo lang_get('plugin_SLA_Resolution'); ?></option>
													</select>
												</td>										
												<td class="row">
													<select id="Priorite" name="Priorite" class="input-sm">
														<?php echo WhichPriorite() ;?>
													</select>
												</td>										
												<td class="row">
													<input type="text" id="Valeur" name="Valeur" />
													<p style='display: inline'><i><font size="1"><?php echo lang_get('plugin_SLA_info_valeur'); ?></font></i></p>
												</td>
											</tr>
										</tbody>
									</table>										
									<input id="project_update" name="project_update" type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'plugin_SLA_action_update' ) ?>" />
									<div class="space-10"></div>
								</div>
							</div>
						</div>					
					</div>
				</fieldset>
			</form>
		</div>
		<div class="form-container">
			<form action="<?php echo plugin_page( 'config_admin_projet' )?>" method="post">
				<input type="hidden" value="<?php echo $projet?>" name="idpro"/>
				<fieldset>
					<div class="widget-box widget-color-blue2">
						<div class="widget-header widget-header-small">
							<h4 class="widget-title lighter">
								<i class="ace-icon fa fa-exchange"></i>
								<?php echo lang_get( 'plugin_SLA_config_out_title' ) ?>
							</h4>
						</div>
						<?php echo form_security_field( 'config_admin' ) ?>
						<div class="widget-body">
							<div class="widget-main no-padding">
								<div class="table-responsive">
									<table class="table table-bordered table-condensed table-striped">										
										<tr>
											<td class="category"><?php echo lang_get('plugin_SLA_Prise_en_charge'); ?></td>
											<td>
												<select id="Prise_en_Charge" name="Prise_en_Charge" class="input-sm">
													<?php echo whichStatus('Prise_en_Charge');?>
												</select>
											</td>
										</tr>
										<tr>
											<td class="category"><?php echo lang_get('plugin_SLA_Contournement'); ?></td>	
											<td>
												<select id="Contournement" name="Contournement" class="input-sm">
													<?php echo whichStatus('Contournement');?>
												</select>
											</td>
										</tr>
										<tr>
											<td class="category"><?php echo lang_get('plugin_SLA_Resolution'); ?></td>	
											<td>
												<select id="Resolution" name="Resolution" class="input-sm">
													<?php echo whichStatus('Resolution');?>
												</select>
											</td>
										</tr>
										<tr>
											<td class="category"><?php echo lang_get('plugin_SLA_statut_client'); ?></td>	
											<td>
												<input type="text" id="stat_cli" name="stat_cli" value="<?php echo recup_statut_client($projet); ?>"/>
												<p style='display: inline'><i><font size="1"><?php echo lang_get('plugin_SLA_info_statut_client'); ?></font></i></p>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="widget-toolbox padding-8 clearfix">
								<strong><?php echo plugin_config_get( 'status_enum_string') ?>
								<input id="deadlines_update" name="deadlines_update" type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'plugin_SLA_action_update' ) ?>" />
							</div>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
		<div class="form-container">
			<form action="<?php echo plugin_page( 'config_admin_projet' )?>" method="post">
				<input type="hidden" value="<?php echo $projet?>" name="idpro"/>
				<fieldset>
					<div class="widget-box widget-color-blue2">
						<div class="widget-header widget-header-small">
							<h4 class="widget-title lighter">
								<i class="ace-icon fa fa-exchange"></i>
								<?php echo lang_get( 'plugin_SLA_config_Horaire' ) ?>
							</h4>
						</div>
						<?php echo form_security_field( 'config_admin' ) ?>
						<div class="widget-body">
							<div class="widget-main no-padding">
								<div class="table-responsive">
									<table class="table table-bordered table-condensed table-striped">										
										<tr>
											<td class="category"><?php echo lang_get( 'plugin_SLA_h_jour' ) ?></td>
											<td>
												<select id="h_jour" name="h_jour" class="input-sm">
													<?php echo which_heure('hjour');?>
												</select>
											</td>
										</tr>
										<tr>
											<td class="category"><?php echo lang_get( 'plugin_SLA_h_deb_jour' ) ?></td>	
											<td>
												<input type="text" id="hdeb" name="hdeb" value="<?php echo recup_heure('hdeb'); ?>"/>
												<p style='display: inline'><i><font size="1"><?php echo lang_get('plugin_SLA_info_heure'); ?></font></i></p>
											</td>
										</tr>
										<tr>
											<td class="category"><?php echo lang_get( 'plugin_SLA_h_fin_jour' ) ?></td>	
											<td>
												<input type="text" id="hfin" name="hfin" value="<?php echo recup_heure('hfin'); ?>"/>
												<p style='display: inline'><i><font size="1"><?php echo lang_get('plugin_SLA_info_heure'); ?></font></i></p>
											</td>
										</tr>
										<tr>
											<td class="category"><?php echo lang_get( 'plugin_SLA_h_pause_dej' ) ?></td>	
											<td>
												<select id="pdej" name="pdej" class="input-sm">
													<?php echo which_heure('pdej');?>
												</select>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<div class="widget-toolbox padding-8 clearfix">
								<strong><?php echo plugin_config_get( 'status_enum_string') ?>
								<input id="horaire_update" name="horaire_update" type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'plugin_SLA_action_update' ) ?>" />
							</div>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</div>
<?php layout_page_end();
																				