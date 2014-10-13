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

$tradingName_lookup_function = 'function(doc,meta){if(doc.type==\"trading_name\"&&doc.name&&doc.currency&&doc.steward&&!doc.spaceStewardsNotified){emit(doc.name,doc.currency);}}';

$trading_name_function_name = "tradingname";

$profile_lookup_function = 'function(doc,meta){if(doc.type==\"profile\"&&doc.username&&doc.email&&doc.notification){if(doc.notification){emit(doc.username,doc.email);}}}';

$profile_function_name = "profile";

$space_lookup_function = 'function(doc,meta){if(doc.type==\"space\"&&doc.steward&&doc.space){emit(doc.space,doc.steward);}}';

$space_function_name = "space";

$currency_lookup_function = 'function(doc,meta){if(doc.type==\"currency\"&&doc.currency&&doc.steward){emit(doc.currency,doc.steward);}}';

$currency_function_name = "currency";

$designDoc = '{"views":{"' . $trading_name_journal_function_name . '":{"map":"' . $tradingNameJournal_lookup_function . '"},"' . $trading_name_function_name . '":{"map":"' . $tradingName_lookup_function . '"},"' . $profile_function_name . '":{"map":"' . $profile_lookup_function . '"},"' . $space_function_name . '":{"map":"' . $space_lookup_function . '"},"' . $currency_function_name . '":{"map":"' . $currency_lookup_function . '"}}}';
	
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
			
		
		
		//check if username is email
		if( strpos($steward,"@") !== false ) {
				
			if($trading_name_journal['from'] == $trading_name['trading_name'] && !$trading_name_journal['from_emailed']) {
				if( email_letter($steward, $CFG->system_email, 'New Payment', $message) ) {
					echo str_replace("<br/>","\n",$message);
					$trading_name_journal['from_emailed'] = true;
					
					$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
				}
			} else if ($trading_name_journal['to'] == $trading_name['trading_name'] && !$trading_name_journal['to_emailed']) {
				if( email_letter($steward, $CFG->system_email, 'New Payment', $message) ) {
					echo str_replace("<br/>","\n",$message);
					$trading_name_journal['to_emailed'] = true;
					
					$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
				}
			}
			
		} else {
			//username not an email check if they have a profile with an email.
			$options = array('startkey' => $steward, 'endkey' => $steward . '\uefff');
			$profiles = $cb->view( $design_doc_name, $profile_function_name, $options );
			foreach ( $profiles ['rows'] as $profile ) {
				//print_r ($profiles);
				//echo "email:" . $profile ['value'] . "\n";
				if (isset( $profile ['value'] ) ) {
					if($trading_name_journal['from'] == $trading_name['trading_name'] && ( !isset($trading_name_journal['from_emailed']) || ( isset($trading_name_journal['from_emailed']) && $trading_name_journal['from_emailed'] === false ) ) ) {
						if( email_letter($profile['value'], $CFG->system_email, 'New Payment', $message) ) {
							echo str_replace("<br/>","\n",$message);
							$trading_name_journal['from_emailed'] = true;
								
							$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
						}
					} else if ($trading_name_journal['to'] == $trading_name['trading_name'] && ( !isset($trading_name_journal['to_emailed']) || ( isset($trading_name_journal['to_emailed']) && $trading_name_journal['to_emailed'] === false ) ) ) {
						if( email_letter($profile['value'], $CFG->system_email, 'New Payment', $message) ) {
							echo str_replace("<br/>","\n",$message);
							$trading_name_journal['to_emailed'] = true;
								
							$cb->set ($journal_trading_name['id'] , json_encode ( $trading_name_journal ) );
						}
					}
				} else {
					echo "profile email is not set";
				}
			}
		}
	} 
}



// do trading name lookup
$options = array ();
$tradingname_result = $cb->view ( $design_doc_name, $trading_name_function_name, $options );

//print_r( $tradingname_result );
foreach ( $tradingname_result ['rows'] as $trading_name ) {
	
	//init
	$trading_name = $trading_name ['key'];
	$currency = $trading_name['value'];
	
	//do a lookup
	$trading_name_array = $cb->get ( "trading_name," . $trading_name . "," . $currency );
	$trading_name_array = json_decode ( $trading_name_array, true );
	if(!isset($trading_name_array['notified'])) {
		
		//generate the key and hash
		$key = strtotime ( "now" ) * rand ();
		$hash = password_hash ( ( string ) $key, PASSWORD_BCRYPT );
		
		//store the key
		$trading_name_array['key'] = $key;
		$cb->set ( "trading_name," . $trading_name . "," . $currency, json_encode ( $trading_name_array ) );
		
		//generate the message
		$message =
		"<br/>Trading Name Created: " .
		"<br/>Trading Name: " . $trading_name .
		"<br/>Currency: " . $currency .
		"<br/>".
		"<br/><a href='https://cloud.openmoney.cc/enable.php?trading_name=" . urlencode($trading_name) . "&currency=" . urlencode($currency) . "&auth=" . urlencode($hash) . "'>Click here to enable this account to trade</a>".
		"<br/><a href='https://cloud.openmoney.cc/disable.php?trading_name=" . urlencode($trading_name) . "&currency=" . urlencode($currency) . "&auth=" . urlencode($hash) . "'>Click here to disable this account from trading</a>".
		"<br/>".
		"<br/>Thank you,<br/>openmoney<br/>";
		
		
		//lookup currency stewards and notify them.
		$options = array('startkey' => $currency, 'endkey' => $currency . '\uefff');
		$currencies = $cb->view( $design_doc_name, $currency_function_name, $options );
		foreach ( $currencies ['rows'] as $currency_array ) {
			foreach( $currency_array ['value'] as $currency_steward ) {
				if (! in_array( $currency_steward, $stewards ) ) {
					if( strpos($currency_steward,"@") !== false ) {
						if( email_letter($currency_steward, $CFG->system_email, 'New Trading Name Created', $message) ) {
							echo str_replace("<br/>","\n",$message);
							$trading_name_array['notified'] = true;
							$cb->set ( "trading_name," . $trading_name . "," . $currency, json_encode ( $trading_name_array ) );
						}
					} else {
						//username not an email check if they have a profile with an email.
						$options = array('startkey' => $currency_steward, 'endkey' => $currency_steward . '\uefff');
						$profiles = $cb->view( $design_doc_name, $profile_function_name, $options );
						foreach ( $profiles ['rows'] as $profile ) {
							if (isset( $profile ['value'] ) ) {
								if( email_letter($profile ['value'], $CFG->system_email, 'New Trading Name Created', $message) ) {
									echo str_replace("<br/>","\n",$message);
									$trading_name_array['notified'] = true;
									$cb->set ( "trading_name," . $trading_name . "," . $currency, json_encode ( $trading_name_array ) );
								}
							} else {
								echo "profile email is not set";
							}
						}
					}
				}
			}
		}	
	}
}

// do currency lookup
$options = array ();
$currencies = $cb->view( $design_doc_name, $currency_function_name, $options );
foreach ( $currencies ['rows'] as $currency ) {

	//init
	$currency_name = $currency ['key'];
	$currency_stewards = $currency ['value'];
	$currency = $cb->get ( "currency," . $currency_name );
	$currency = json_decode( $currency , true );
	
	if(!isset($currency['notified'])) {
		
		//generate the key and hash
		$key = strtotime ( "now" ) * rand ();
		$hash = password_hash ( ( string ) $key, PASSWORD_BCRYPT );
		
		//store the key
		$currency['key'] = $key;
		$cb->set ( "currency," . $currency['currency'], json_encode ( $currency ) );

		//generate the message
		$message =
		"<br/>Currency Created: " .
		"<br/>Currency: " . $currency['currency'] .
		"<br/>Description: " . $currency['name'] .
		"<br/>".
		"<br/><a href='https://cloud.openmoney.cc/enable.php?&currency=" . urlencode($currency['currency']) . "&auth=" . urlencode($hash) . "'>Click here to enable this currency</a>".
		"<br/><a href='https://cloud.openmoney.cc/disable.php?&currency=" . urlencode($currency['currency']) . "&auth=" . urlencode($hash) . "'>Click here to disable this currency</a>".
		"<br/>".
		"<br/>Thank you,<br/>openmoney<br/>";

		//lookup space stewards and notify them.
		$options = array('startkey' => $currency['space'], 'endkey' => $currency['space'] . '\uefff');
		$spaces = $cb->view( $design_doc_name, $space_function_name, $options );
		foreach ( $spaces ['rows'] as $space ) {
			foreach( $space ['value'] as $space_steward ) {
				//if the user is the same don't email them.
				if (! in_array($space_steward, $currency_stewards ) ) {
					if( strpos($space_steward,"@") !== false ) {
						if( email_letter($space_steward, $CFG->system_email, 'New Currency Created', $message) ) {
							echo str_replace("<br/>","\n",$message);
							$currency['notified'] = true;
							$cb->set ( "currency," . $currency['currency'], json_encode ( $currency ) );
						}
					} else {
						//username not an email check if they have a profile with an email.
						$options = array('startkey' => $space_steward, 'endkey' => $space_steward . '\uefff');
						$profiles = $cb->view( $design_doc_name, $profile_function_name, $options );
						foreach ( $profiles ['rows'] as $profile ) {
							if (isset( $profile ['value'] ) ) {
								if( email_letter($profile ['value'], $CFG->system_email, 'New Currency Created', $message) ) {
									echo str_replace("<br/>","\n",$message);
									$currency['notified'] = true;
									$cb->set ( "currency," . $currency['currency'], json_encode ( $currency ) );
								}
							} else {
								echo "profile email is not set";
							}
						}
					}
				}
			}
		}
	}
}

// do space lookup
$options = array ();
$spaces = $cb->view( $design_doc_name, $space_function_name, $options );
foreach ( $spaces ['rows'] as $space ) {

	//init
	$space = $currency ['key'];
	$space_stewards = $currency ['value'];
	$space = $cb->get ( "space," . $space );
	$space = json_decode( $space , true );

	if(!isset($space['notified'])) {

		//generate the key and hash
		$key = strtotime ( "now" ) * rand ();
		$hash = password_hash ( ( string ) $key, PASSWORD_BCRYPT );

		//store the key
		$space['key'] = $key;
		$cb->set ( "space," . $space['space'], json_encode ( $space ) );

		//generate the message
		$message =
		"<br/>Space Created: " .
		"<br/>Space: " . $space['space'] .
		"<br/>".
		"<br/><a href='https://cloud.openmoney.cc/enable.php?&space=" . urlencode($space['space']) . "&auth=" . urlencode($hash) . "'>Click here to enable this space</a>".
		"<br/><a href='https://cloud.openmoney.cc/disable.php?&space=" . urlencode($space['space']) . "&auth=" . urlencode($hash) . "'>Click here to disable this space</a>".
		"<br/>".
		"<br/>Thank you,<br/>openmoney<br/>";

		//lookup space stewards and notify them.
		$options = array('startkey' => $space['subspace'], 'endkey' => $space['subspace'] . '\uefff');
		$spaces = $cb->view( $design_doc_name, $space_function_name, $options );
		foreach ( $spaces ['rows'] as $space ) {
			foreach( $space ['value'] as $space_steward ) {
				//if the user is the same don't email them.
				if (! in_array($space_steward, $space_stewards ) ) {
					if( strpos($space_steward,"@") !== false ) {
						if( email_letter($space_steward, $CFG->system_email, 'New Space Created', $message) ) {
							echo str_replace("<br/>","\n",$message);
							$space['notified'] = true;
							$cb->set ( "space," . $space['space'], json_encode ( $space ) );
						}
					} else {
						//username not an email check if they have a profile with an email.
						$options = array('startkey' => $space_steward, 'endkey' => $space_steward . '\uefff');
						$profiles = $cb->view( $design_doc_name, $profile_function_name, $options );
						foreach ( $profiles ['rows'] as $profile ) {
							if (isset( $profile ['value'] ) ) {
								if( email_letter($profile ['value'], $CFG->system_email, 'New Space Created', $message) ) {
									echo str_replace("<br/>","\n",$message);
									$space['notified'] = true;
									$cb->set ( "space," . $space['space'], json_encode ( $space ) );
								}
							} else {
								echo "profile email is not set";
							}
						}
					}
				}
			}
		}
	}
}
?>