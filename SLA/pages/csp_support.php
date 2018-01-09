<?php

header("Content-Type: text/javascript"); ?>


jQuery(function () {
    jQuery("#sla").change(function () {
        location.href = jQuery(this).val();
    })
})

<?php
if ( isset( $_GET['r'] ) and !empty( $_GET['r'] ) ) {
    $current_page = strip_tags( $_GET['r'] );
} else { exit; }

if ( $current_page == 'tesla' ) { 
    if ( isset( $_SESSION['test_sla_js'] ) and !empty( $_SESSION['test_sla_js'] ) ) {
        echo $_SESSION['test_sla_js'];
    }
} elseif ( $current_page == 'ctrsla' ) { // issues by severity
if ( isset( $_SESSION['controle_sla_js'] ) and !empty( $_SESSION['controle_sla_js'] ) ) {
        echo $_SESSION['controle_sla_js'];
    }
}

?>
