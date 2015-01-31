<?php
require_once('password.php');

$email = isset($_POST['email'])?$_POST['email']:'';

	
	$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
	
	$user = $cb->get ( "users," . $email );
	
	$user = json_decode ( $user, true );

if( isset ($user ['username']) ){
	$reset_key = (String)$user['reset_token_key'];
	
	if (password_verify( $reset_key, $_POST['reset']) ) {
	
		$username = $user ['username'];
		$password = $_POST['password2'];
		$password_hash = password_hash($password, PASSWORD_BCRYPT);
		
		$user ['password'] = $password_hash;
		$user ['password_encryption_algorithm'] = array( PASSWORD_BCRYPT );
		
		$cb->set( "users," . $email, json_encode( $user ) );
		
		header("Location: https://cloud.openmoney.cc/webclient/main.html");
		exit();
	} else {
		$error = urlencode("Could not verify link!");
		header("Location: resetPassword.php?email=" . $user['email'] . "&reset=" . $_POST['reset'] . "&error=" . $error);
		exit();
	}
} else {
	$error = urlencode("Could find user!");
	header("Location: resetPassword.php?email=" . $user['email'] . "&reset=" . $_POST['reset'] . "&error=" . $error);
	exit();
}

