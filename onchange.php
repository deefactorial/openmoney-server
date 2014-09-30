<?php 
$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );

// $tradingNameJournal_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name_journal\" && doc.from && doc.to && doc.currency) { emit( \"trading_name,\" + doc.from + \",\" + doc.currency  ,  doc.from + \"_\" + doc.currency); emit( \"trading_name,\" + doc.to + \",\" + doc.currency  ,  doc.to + \"_\" + doc.currency); } }';

// $designDoc = '{ "views": { "tradingnamejournallookup" : { "map": "' . $tradingNameJournal_lookup_function . '" } } }';
	
// // echo $designDoc;
	
// $cb->setDesignDoc ( "dev_roles", $designDoc );
	
$options = array ();
	
// do trading name lookup on
$tradingnamejournal_result = $cb->view ( 'dev_roles', 'tradingnamejournallookup', $options );

foreach ( $tradingnamejournal_result ['rows'] as $journal_trading_name ) {
	echo "get " . $journal_trading_name['id'] . "<br/>";
	$trading_name_journal = json_decode( $cb->get( $journal_trading_name['id'] ), true );
	
	$trading_name = json_decode( $cb->get ( $journal_trading_name['key'] ), true);
	//print_r($trading_name);
	
	//echo $trading_name;
	
	foreach($trading_name['steward'] as $steward) {
		echo "<br/>Email:" . $steward . " Payment Made:" . " From:" . $trading_name_journal['from'] . " To:" . $trading_name_journal['to'] . " Amount:" . $trading_name_journal['amount'] . " in " . $trading_name_journal['currency'] . "<br/>";
	} 
	
// 	$url = 'https://localhost:4985/openmoney_shadow/' . $journal_trading_name['id'];
// 	// $url = 'https://localhost:4985/todos/_user/' . $username;
// 	$data = array ('name' => $journal_trading_name['value'] );
// 	$json = json_encode ( $data );
// 	$options = array ('http' => array ('method' => 'GET', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
// 	$context = stream_context_create ( $options );
// 	$default_context = stream_context_set_default ( $options );
		
// 	$result = file_get_contents ( $url, false, $context );
	
// 	echo $result;
	//$cb->add( "_role/" + $journal_trading_name['id'], '{ "name": ' . $journal_trading_name['id'] . ' } ' );
	
}

print_r( $tradingnamejournal_result );
?>