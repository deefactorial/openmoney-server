<?php 
$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );

$tradingNameJournal_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name_journal\" && doc.from && doc.to && doc.currency) { emit( \"trading_name,\" + doc.from + \",\" + doc.currency  ,  doc.from + \" \" + doc.currency); emit( \"trading_name,\" + doc.to + \",\" + doc.currency  ,  doc.to + \" \" + doc.currency); } }';

$designDoc = '{ "views": { "tradingnamejournallookup" : { "map": "' . $tradingNameJournal_lookup_function . '" } } }';
	
// echo $designDoc;
	
$cb->setDesignDoc ( "dev_roles", $designDoc );
	
$options = array ('startkey' => array (), 'endkey' => array ( '\uefff'));
	
// do trading name lookup on
$tradingnamejournal_result = $cb->view ( 'dev_roles', 'tradingnamejournallookup', $options );

foreach ( $tradingnamejournal_result ['rows'] as $journal_trading_name ) {
	
	$cb->add( "_role/" + $journal_trading_name['id'], '{ "name": ' . $journal_trading_name['id'] . ' } ' );
	
}
?>