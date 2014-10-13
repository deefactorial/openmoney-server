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

$tradingNameJournal_lookup_function = 'function(doc,meta){if(doc.type==\"trading_name_journal\"&&doc.from&&doc.to&&doc.currency&&!doc.to_emailed){emit(\"trading_name,\"+doc.to+\",\" +doc.currency,doc.to+\"_\"+doc.currency);}if(doc.type==\"trading_name_journal\"&&doc.from&&doc.to&&doc.currency&&!doc.from_emailed){emit(\"trading_name,\"+doc.from+\",\"+doc.currency,doc.from+\"_\"+doc.currency);}}';

$trading_name_journal_function_name = "tradingnamejournal";

$tradingName_lookup_function = 'function(doc,meta){if(doc.type==\"trading_name\"&&doc.space&&doc.name&&doc.currency&&doc.steward&&!doc.spaceStewardsNotified){emit(doc.space,[doc.steward,doc.name,doc.currency]);}}';

$trading_name_function_name = "tradingname";

$profile_lookup_function = 'function(doc,meta){if(doc.type==\"profile\"&&doc.username&&doc.email){emit(doc.username,doc.email);}}';

$profile_function_name = "profile";

$designDoc = '{"views":{"' . $trading_name_journal_function_name . '":{"map":"' . $tradingNameJournal_lookup_function . '"},"' . $trading_name_function_name . '":{"map":"' . $tradingName_lookup_function . '"},"' . $profile_function_name . '":{"map":"' . $profile_lookup_function . '"}}}';
	
// echo $designDoc;
$design_doc_name = "dev_changes";

$cb->setDesignDoc ($design_doc_name,json_decode(json_encode($designDoc)));
	
$options = array ();
	
// do trading name journal lookup 
$tradingnamejournal_result = $cb->view ( $design_doc_name, $trading_name_journal_function_name, $options );

//print_r( $tradingnamejournal_result );
foreach ( $tradingnamejournal_result ['rows'] as $journal_trading_name ) {
	//echo "get " . $journal_trading_name['id'] . "<br/>";
	$trading_name_journal = json_decode( $cb->get( $journal_trading_name['id'] ), true );
	//print_r($trading_name_journal);
	
	$trading_name = json_decode( $cb->get ( $journal_trading_name['key'] ), true);
	//print_r($trading_name);
	
	//echo $trading_name;
	foreach($trading_name['steward'] as $steward) {
		$message =
		"<br/>Payment Made: " .
		"<br/>From: " . $trading_name_journal['from'] .
		"<br/>To: " . $trading_name_journal['to'] .
		"<br/>Amount: " . $trading_name_journal['amount'] . " " . $trading_name_journal['currency'] ;
		isset($trading_name_journal['description']) ? $message .= "<br/>Description: " .  $trading_name_journal['description'] : $message .= ''; ;
		$message .=	"<br/>Timestamp: " . date( DATE_RFC2822, strtotime( $trading_name_journal['timestamp'] ) ).
		"<br/>" .
		"<br/>Thank you,<br/>openmoney<br/>";
			
		echo str_replace("<br/>","\n",$message);
		
		//check if username is email
		if( strpos($steward,"@") !== false ) {

				
			if($trading_name_journal['from'] == $trading_name['trading_name'] && !$trading_name_journal['from_emailed']) {
				if( email_letter($steward, $CFG->system_email, 'New Payment', $message) ) {
					
					$trading_name_journal['from_emailed'] = true;
					
					$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
				}
			} else if ($trading_name_journal['to'] == $trading_name['trading_name'] && !$trading_name_journal['to_emailed']) {
				if( email_letter($steward, $CFG->system_email, 'New Payment', $message) ) {
					
					$trading_name_journal['to_emailed'] = true;
					
					$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
				}
			}
		} else {
			//username not an email check if they have a profile with an email.
			
			$options = array('startkey' => $steward, 'endkey' => $steward . '\uefff');
			
			$profiles = $cb->view( $design_doc_name, $profile_function_name, $options );
			
			print_r ($profiles);
			foreach ( $profiles ['rows'] as $profile ) {
				if (isset( $profile ['value'] ) ) {
					if($trading_name_journal['from'] == $trading_name['trading_name'] && !isset($trading_name_journal['from_emailed'])) {
						if( email_letter($profile['value'], $CFG->system_email, 'New Payment', $message) ) {
								
							$trading_name_journal['from_emailed'] = true;
								
							$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
						}
					} else if ($trading_name_journal['to'] == $trading_name['trading_name'] && !isset($trading_name_journal['to_emailed'])) {
						if( email_letter($profile['value'], $CFG->system_email, 'New Payment', $message) ) {
								
							$trading_name_journal['to_emailed'] = true;
								
							$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
						}
					}
				}
			}
		}
	} 
}


?>