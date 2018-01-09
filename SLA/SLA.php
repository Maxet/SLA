<?php
	
	if( !defined( 'MANTIS_VERSION' ) ) { exit(); }
	
	class SLAPlugin extends MantisPlugin {
		# Plugin definition
		function register() {
			$this->name         = lang_get( 'plugin_SLA_title' );
			$this->description  = lang_get ( 'plugin_SLA_description' );
			$this->page         = 'config_admin';
			
			$this->version      = '1.0.0';
			$this->requires = array(
				'MantisCore' => '2.6.0',
				# ajouter le plugin ArrayExportExcel
			);

			$this->author       = 'Maxime GODET';
			$this->contact      = '...';
			$this->url          = '...';
		}
		# Plugin configuration
		function config() {
			return array(
				'access_threshold'  => MANAGER, // Set global access level requireed to access plugin
			);
		}
		# Add start menu item
		function showreport_menu() {
			if ( access_has_global_level( plugin_config_get( 'access_threshold' ) ) ) {
				return array(
					array( 
						'title'         => lang_get( 'plugin_SLA_title_full' ),
						'access_level'  => plugin_config_get( 'access_threshold' ),
						'url'           => 'plugin.php?page=SLA/start_page',
						'icon'          => 'fa fa-sliders'
					),
				);
			}
		}
		# Schema definition
		function schema() {
			return array(


				array( 'CreateTableSQL', 
					array( plugin_table( 'config' ), "
						id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
						config_name         C(255)  NOTNULL,
						config_int_value    I       DEFAULT NULL,
						config_char_value   XL      DEFAULT NULL,
						config_extra_value  C(255)  DEFAULT NULL,
						report_id           I       DEFAULT NULL UNSIGNED,
						project_id          I       DEFAULT NULL UNSIGNED,
						user_id             I       DEFAULT NULL UNSIGNED,
						is_default          I       NOTNULL UNSIGNED
					" )
				),

				array( 'CreateTableSQL', 
					array( plugin_table( 'struct' ), "
						project_id          I       NOTNULL UNSIGNED PRIMARY,
						priorite            C(255)  NOTNULL PRIMARY,
						delai               C(255)  NOTNULL PRIMARY,
						valeur              I       DEFAULT \" '0' \" 
					" )
				),
				array( 'CreateTableSQL', 
					array( plugin_table( 'statut' ), "
						project_id          I       NOTNULL UNSIGNED PRIMARY,
						etat                C(255)  NOTNULL PRIMARY,
						statut              C(255)  NOTNULL
					" )
				),
				array( 'CreateTableSQL', 
					array( plugin_table( 'horaire' ), "
						project_id			I		NOTNULL UNSIGNED PRIMARY,
						hjour				I		NOTNULL,
						hdeb				C(255)	NOTNULL,
						hfin				C(255)	NOTNULL,
						pdej				C(255)	NOTNULL
					")
				),
			);
		}
		# Plugin hooks
		function hooks() {
			return array(
				'EVENT_MENU_MAIN'           => 'showreport_menu',
				'EVENT_LAYOUT_RESOURCES'    => 'resources',
			);
		}

		# Loading needed styles and javascripts
		function resources() {
			if ( is_page_name( 'plugin.php' ) ) {
				return
					"
						<link rel='stylesheet' type='text/css' href='" . plugin_file( 'reporting_main.css' ) . "'>
						<link rel='stylesheet' type='text/css' href='" . plugin_file( 'datatables-min.css' ) . "'>
						<script src='" . plugin_file( 'datatables-min.js' ) . "'></script>
					";
			
			
			}
		}
	}