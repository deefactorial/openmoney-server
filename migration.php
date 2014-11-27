<?php
require ("config.php");

function email_letter($to, $from, $subject = 'no subject', $msg = 'no msg') {
	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
	$headers .= 'X-Mailer: PHP/' . phpversion ();
	return mail ( $to, $subject, $msg, $headers );
}

$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );

$tradingNameJournal_lookup_function = 'function(doc,meta){if(doc.type==\"trading_name_journal\"&&doc.from&&doc.to&&doc.currency){emit(\"trading_name,\"+doc.to+\",\"+doc.currency,doc.amount);}if(doc.type==\"trading_name_journal\"&&doc.from&&doc.to&&doc.currency){emit(\"trading_name,\"+doc.from+\",\"+doc.currency,-doc.amount);}}';

$trading_name_journal_function_name = "tradingnamejournal";

$tradingName_lookup_function = 'function(doc,meta){if(doc.type==\"trading_name\"&&doc.name&&doc.currency&&doc.steward){emit(doc.name,doc.currency);}}';

$trading_name_function_name = "tradingname";

$designDoc = '{"views":{"' . $trading_name_journal_function_name . '":{"map":"' . $tradingNameJournal_lookup_function . '"},"' . $trading_name_function_name . '":{"map":"' . $tradingName_lookup_function . '"}}}';

// echo $designDoc;
$design_doc_name = "dev_migration";

$cb->setDesignDoc ($design_doc_name,json_decode(json_encode($designDoc)));

// $options = array ();

// // do trading name journal lookup
// $tradingnamejournal_result = $cb->view ( $design_doc_name, $trading_name_journal_function_name, $options );

// //print_r( $tradingnamejournal_result );
// foreach ( $tradingnamejournal_result ['rows'] as $journal_trading_name ) {
// 	$trading_name_journal = json_decode( $cb->get( $journal_trading_name['id'] ), true );
// 	$trading_name = json_decode( $cb->get ( $journal_trading_name['key'] ), true);
	
// 	if ($trading_name['name'] == $trading_name_journal['from']) {
// 		foreach($trading_name['steward'] as $steward) {
// 			$trading_name_view = json_decode( $cb->get("trading_name_view," . $steward . "," . $trading_name_journal['to'] . "," . $trading_name_journal['currency']) , true);
// 			if (!isset($trading_name_view['trading_name'])) {
				
// 				$trading_name_from_view['type'] = "trading_name_view";
// 				$trading_name_from_view['steward'] = array( $steward );
// 				$trading_name_from_view['trading_name'] = $trading_name_journal['to'];
// 				$trading_name_from_view['currency'] = $trading_name_journal['currency'];
// 				$trading_name_from_view['created'] = intval( round(microtime(true) * 1000) );
// 				$cb->set ("trading_name_view," . $steward . "," . $trading_name_from_view['trading_name'] . "," . $trading_name_from_view['currency'] , json_encode ( $trading_name_from_view ) );
// 			}
			
// 		}
// 	} else {
// 		foreach($trading_name['steward'] as $steward) {
// 			$trading_name_view = json_decode( $cb->get("trading_name_view," . $steward . "," . $trading_name_journal['from'] . "," . $trading_name_journal['currency']) , true);
// 			if (!isset($trading_name_view['trading_name'])) {
				
// 				$trading_name_from_view['type'] = "trading_name_view";
// 				$trading_name_from_view['steward'] = array( $steward );
// 				$trading_name_from_view['trading_name'] = $trading_name_journal['from'];
// 				$trading_name_from_view['currency'] = $trading_name_journal['currency'];
// 				$trading_name_from_view['created'] = intval( round(microtime(true) * 1000) );
// 				$cb->set ("trading_name_view," . $steward. "," . $trading_name_from_view['trading_name'] . "," . $trading_name_from_view['currency'] , json_encode ( $trading_name_from_view ) );
// 			}
// 		}
// 	}
// }

// do trading name lookup
$options = array ();
$tradingname_result = $cb->view ( $design_doc_name, $trading_name_function_name, $options );

//print_r( $tradingname_result );
foreach ( $tradingname_result ['rows'] as $trading_name ) {

	//print_r($trading_name);
	//init
	$currency = $trading_name['value'];
	$trading_name = $trading_name ['key'];

	//do a lookup
	$trading_name_array = $cb->get ( "trading_name," . $trading_name . "," . $currency );
	$trading_name_array = json_decode ( $trading_name_array, true );

	foreach($trading_name_array['steward'] as $steward) {
		$currency_view = json_decode( $cb->get("currency_view," . $steward . "," . $currency), true);
		if (!isset($currency_view['currency'])) {
			$currency_view ['type'] = "currency_view";
			$currency_view ['currency'] = "cc";
			$currency_view ['steward'] = array ($steward);
			$currency_view ['created'] = intval( round(microtime(true) * 1000) );
			
			$cb->set ( "currency_view," . $steward . "," . $currency_view ['currency'], json_encode ( $currency_view ) );
		}
	}
	
}

