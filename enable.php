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

$trading_name = null;
if( isset( $_GET['trading_name'] ) ) {
	$trading_name = urldecode( $_GET['trading_name'] );
	$trading_name = str_replace(" ","+",$trading_name);//plus sign gets replaced with a space
}

$currency = null;
if( isset( $_GET['currency'] ) ) {
	$currency = urldecode( $_GET['currency'] );
	$currency = str_replace(" ","+",$currency);//plus sign gets replaced with a space
}

$space = null;
if( isset( $_GET['space'] ) ) {
	$space = urldecode( $_GET['space'] );
	$space = str_replace(" ","+",$space);//plus sign gets replaced with a space
}

$auth = null;
if( isset( $_GET['auth'] ) ) {
	$auth = urldecode( $_GET['auth'] );
	$auth = str_replace(" ","+",$auth);//plus sign gets replaced with a space
}

if( $trading_name != null && $currency != null) {
	$trading_name = $cb->get ( "trading_name," . $trading_name . "," . $currency);
	$trading_name = json_decode ( $trading_name, true );
	
	if( isset( $trading_name ['key'] ) && $auth != null ) {
		require ("password.php");
		
		if( password_verify ( $auth, $trading_name ['key'] ) ) {
			$trading_name['enabled'] = true;
			$cb->set ( "trading_name," . $trading_name['name'] . "," . $trading_name['currency'], json_encode ( $trading_name ) );
			echo "Trading Name " . $trading_name['name'] . " in currency " . $trading_name['currency'] . " is enabled.";
		} else {
			echo "Authentication Failed!";
		}
	} else {
		echo "Authentication Failed!";
	}
} else if ( $currency != null ) {
	$currency = $cb->get ( "currency," . $currency);
	$currency = json_decode ( $currency, true );
	
	if( isset( $currency ['key'] ) && $auth != null ) {
		require ("password.php");
	
		if( password_verify ( $auth, $currency ['key'] ) ) {
			$currency['enabled'] = true;
			$cb->set ( "currency," . $currency['currency'], json_encode ( $currency ) );
			echo "Currency " . $currency['currency'] . " is enabled.";
		} else {
			echo "Authentication Failed!";
		}
	} else {
		echo "Authentication Failed!";
	}
} else if ( $space != null ) {
	$space = $cb->get ( "space," . $space);
	$space = json_decode ( $space, true );
	
	if( isset( $space ['key'] ) && $auth != null ) {
		require ("password.php");
	
		if( password_verify ( $auth, $space ['key'] ) ) {
			$space['enabled'] = true;
			$cb->set ( "space," . $space['space'], json_encode ( $space ) );
			echo "Space " . $space['space'] . " is enabled.";
		} else {
			echo "Authentication Failed!";
		}
	} else {
		echo "Authentication Failed!";
	}
}