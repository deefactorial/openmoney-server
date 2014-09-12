<?php 
$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );

// $tradingNameJournal_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name_journal\" && doc.from && doc.to && doc.currency) { emit( \"trading_name,\" + doc.from + \",\" + doc.currency  ,  doc.from + \" \" + doc.currency); emit( \"trading_name,\" + doc.to + \",\" + doc.currency  ,  doc.to + \" \" + doc.currency); } }';

// $designDoc = '{ "views": { "tradingnamejournallookup" : { "map": "' . $tradingNameJournal_lookup_function . '" } } }';
	
// // echo $designDoc;
	
// $cb->setDesignDoc ( "dev_roles", $designDoc );
	
$options = array ();
	
// do trading name lookup on
$tradingnamejournal_result = $cb->view ( 'dev_roles', 'tradingnamejournallookup', $options );

foreach ( $tradingnamejournal_result ['rows'] as $journal_trading_name ) {
	
	$url = 'https://localhost:4985/openmoney_shadow/_role/' . $journal_trading_name['id'];
	// $url = 'https://localhost:4985/todos/_user/' . $username;
	$data = array ('name' => $journal_trading_name['id'] );
	$json = json_encode ( $data );
	$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
	$context = stream_context_create ( $options );
	$default_context = stream_context_set_default ( $options );
		
	$result = file_get_contents ( $url, false, $context );
	
	echo $result;
	//$cb->add( "_role/" + $journal_trading_name['id'], '{ "name": ' . $journal_trading_name['id'] . ' } ' );
	
}

print_r( $tradingnamejournal_result );
?>