<?php 
require ("config.php");

$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
//re-index stale views for the webclient
$options = array();
$options['stale'] = false;

$view = $cb->view( "dev_openmoney", "account_balance", $options );
$view = $cb->view( "dev_openmoney", "account_details", $options );
$view = $cb->view( "dev_openmoney", "accounts", $options );
$view = $cb->view( "dev_openmoney", "currencies", $options );
$view = $cb->view( "dev_openmoney", "spaces", $options );
$view = $cb->view( "dev_openmoney_helper", "trading_name_view", $options );
$view = $cb->view( "dev_openmoney_helper", "currency_view", $options );
$view = $cb->view( "dev_openmoney_helper", "space_view", $options );
$view = $cb->view( "dev_openmoney", "nfc_tags", $options );

exit();

?>