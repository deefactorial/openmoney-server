<?php
// adjust these parameters to match your installation
// $cb = new Couchbase("127.0.0.1:8091", "users", "", "users");
// $cb->set("a", 101);
// var_dump($cb->get("a"));
require 'vendor/autoload.php';

$app = new \Slim\Slim ();

$app->response->headers->set('Content-Type', 'application/json');

//$app->view ( new \JsonApiView () );
//$app->add ( new \JsonApiMiddleware () );
$app->get ( '/', function () use($app) {
	echo array ('message' => "Welcome the openmoney json API! go to http://cloud.openmoney.cc/README.md for more information.");
} );

$app->post ( '/login', function () use($app) {
	
	$username = '';
	$password = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '' && $password == '') && (! isset ( $_POST ['username'] ) || ! isset ( $_POST ['password'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		$username = $post ['username'];
		$password = $post ['password'];
		
		if ($username == '' && $password == '') {
			$app->render ( 401, array ('error' => true, 'msg' => 'Email and password are required !') );
			exit ();
		}
	} else {
		if ($username == '' && $password == '') {
			$username = $_POST ['username'];
			$password = $_POST ['password'];
		}
	}
	
	$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
	
	$user = $cb->get ( "users," . $username );
	
	$user = json_decode ( $user, true );
	
	// TODO: cytpographically decode password using cryptographic algorithms specified in the $user ['cryptographic_algorithms'] array.
	require ("password.php");
	
	if (password_verify ( $password, $user ['password'] )) {
		$url = 'http://localhost:4985/openmoney_shadow/_session';
		// $url = 'http://localhost:4985/todos/_session';
		$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
		$json = json_encode ( $data );
		$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
		$context = stream_context_create ( $options );
		$default_context = stream_context_set_default ( $options );
		
		if (get_http_response_code ( $url ) != "404") {
			$result = file_get_contents ( $url, false, $context );
		} else {
			// user exists in db but not in sync_gateway so create the user
			$url = 'http://localhost:4985/openmoney_shadow/_user/' . $username;
			// $url = 'http://localhost:4985/todos/_user/' . $username;
			$data = array ('name' => $user ['username'], 'password' => $password);
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
			
			$url = 'http://localhost:4985/openmoney_shadow/_session';
			// $url = 'http://localhost:4985/todos/_session';
			$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
		}

		$json = json_decode ( $result, true );
		
		if (isset ( $json ['session_id'] )) {
			
			setcookie ( $json ['cookie_name'], $json ['session_id'], strtotime ( $json ['expires'] ) );
			$result = array ('sessionID' => $json ['session_id'], 'expires' => $json ['expires']);
			
			$app->render ( 200, $result );
			
			exit ();
		} else {
			$app->render ( 401, array ('error' => true, 'msg' => 'The session could not be set!') );
			exit ();
		}
	} else {
		$app->render ( 401, array ('error' => true, 'msg' => 'Password did not match!') );
		exit ();
	}
} );

$app->post ( '/registration', function () use($app) {
	
	$username = '';
	$password = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '' && $password == '') && (! isset ( $_POST ['username'] ) || ! isset ( $_POST ['password'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		$username = $post ['username'];
		$password = $post ['password'];
		
		if ($username == '' && $password == '') {
			$app->render ( 401, array ('error' => true, 'msg' => 'Email and password are required !') );
			exit ();
		}
	} else {
		if ($username == '' && $password == '') {
			$username = $_POST ['username'];
			$password = $_POST ['password'];
		}
	}
	
	$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
	
	$user = $cb->get ( "users," . $username );
	
	$user = json_decode ( $user, true );
	
	// TODO: cytpographically decode password using cryptographic algorithms specified in the $user ['cryptographic_algorithms'] array.
	require ("password.php");
	
	if (!isset( $user ['password'] ) || $user ['password'] == '') {
		
		$user ['username'] = $username;		
		$user ['email'] = $username;
		$user ['cost'] = $options ['cost'] = 10;
		
		$password_hash = password_hash($password , PASSWORD_BCRYPT, $options);
		
		$user ['password'] = $password_hash;
		$user ['password_encryption_algorithm'] = array( PASSWORD_BCRYPT );
		
		$cb->set( "users," . $username, json_encode( $user ) );
		
		$subusername = substr( $username, 0, strpos( $username, "@" ) );
		$subusername = str_replace( ".", "", $subusername );
		$subusername = preg_replace( "/[^a-zA-Z\d]*/", "" , $subusername );
		
		$trading_name_space ['type'] = "trading_name_space";
		$trading_name_space ['space'] = $subusername;
		$trading_name_space ['steward'] = array( $username );
		
		$cb->set( "trading_name_space," . $trading_name_space ['space'], json_encode( $trading_name_space ) );
		
		$trading_name ['type'] = "trading_name";
		$trading_name ['trading_name'] = $subusername ;
		$trading_name ['trading_name_space'] = "cc";
		$trading_name ['currency'] = "cc";
		$trading_name ['steward'] = array( $username );
		
		$cb->set( "trading_name," . $trading_name ['trading_name'] . "." . $trading_name ['trading_name_space'] . "," . $trading_name ['currency'], json_encode( $trading_name ) );
		
		//TODO: send email verification or write an email bot to look for new registrations
		
		$url = 'http://localhost:4985/openmoney_shadow/_session';
		//$url = 'http://localhost:4985/todos/_session';
		$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
		$json = json_encode ( $data );
		$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
		$context = stream_context_create ( $options );
		$default_context = stream_context_set_default ( $options );
		
		if (get_http_response_code ( $url ) != "404") {
			$result = file_get_contents ( $url, false, $context );
		} else {
			// user exists in db but not in sync_gateway so create the user
			$url = 'http://localhost:4985/openmoney_shadow/_user/' . $username;
			//$url = 'http://localhost:4985/todos/_user/' . $username;
			$data = array ('name' => $user ['username'], 'password' => $password);
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
			
			$url = 'http://localhost:4985/openmoney_shadow/_session';
			//$url = 'http://localhost:4985/todos/_session';
			$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
		}
		
		$json = json_decode ( $result, true );
		
		if (isset ( $json ['session_id'] )) {
		
			setcookie ( $json ['cookie_name'], $json ['session_id'], strtotime ( $json ['expires'] ) );
			$result = array ('sessionID' => $json ['session_id'], 'expires' => $json ['expires']);
			
			$app->render ( 200, $result );
			
			exit ();
		} else {
			$app->render ( 401, array ('error' => true, 'msg' => 'The session could not be set!') );
			exit ();
		}
	} else {
		$app->render ( 401, array ('error' => true, 'msg' => 'User already exists!') );
		exit ();
	}
} );

$app->get ( '/logout', function () use($app) {
	
	unset ( $_COOKIE ['SyncGatewaySession'] );
	setcookie ( "SyncGatewaySession", '', time () - 3600, '/' );
	
	$app->render ( 200, array ('error' => false, 'msg' => 'you are now logged out') );
} );

$app->post ( '/todologin', function () use($app) {
	
	$username = '';
	$password = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '' && $password == '') && (! isset ( $_POST ['username'] ) || ! isset ( $_POST ['password'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		if (isset ( $post ['username'] ) && isset ( $post ['password'] )) {
			$username = $post ['username'];
			$password = $post ['password'];
		} else {
			$app->render ( 401, array ('error' => true, 'msg' => 'Username and password required!') );
			exit ();
		}
	} else {
		if ($username == '' && $password == '') {
			$username = $_POST ['username'];
			$password = $_POST ['password'];
		}
	}
	
	if ($password != '' && $username != '') {
		$url = 'http://localhost:4985/todos/_session';
		// $url = 'http://sync.couchbasecloud.com:4985/todolite-phonegap/_session';
		$data = array ('name' => $username, 'ttl' => 86400); // time to live 24hrs
		$json = json_encode ( $data );
		$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
		$context = stream_context_create ( $options );
		$default_context = stream_context_set_default ( $options );
		
		$responseCode = get_http_response_code ( $url );
		
		if ($responseCode == "200") {
			// ok return result
			$result = file_get_contents ( $url, false, $context );
		} else if ($responseCode == "404") {
			// user exists in db but not in sync_gateway so create the user
			$url = 'http://localhost:4985/todos/_user/' . $username;
			// $url = 'http://sync.couchbasecloud.com:4985/todolite-phonegap/_user/' . $username;
			$data = array ('name' => $username, 'password' => $password);
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
			
			$url = 'http://localhost:4985/todos/_session';
			// $url = 'http://sync.couchbasecloud.com:4985/todolite-phonegap/_session';
			$data = array ('name' => $username, 'ttl' => 86400); // time to live 24hrs
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
		} else {
			$app->render ( $responseCode, array ('error' => true) );
			exit ();
		}
		
		$json = json_decode ( $result, true );
		
		if (isset ( $json ['session_id'] )) {
			setcookie ( $json ['cookie_name'], $json ['session_id'], strtotime ( $json ['expires'] ) );
			$result = array ('sessionID' => $json ['session_id'], 'expires' => $json ['expires'], 'username' => $username, 'password' => $password);
			
			$app->render ( 200, $result );
			
			exit ();
		} else {
			
			$app->render ( 401, array ('error' => true) );
			exit ();
		}
	} else {
		$app->render ( 401, array ('error' => true) );
		exit ();
	}
} );

$app->get ( '/todologout', function () use($app) {
	if (isset ( $_SERVER ['PHP_AUTH_USER'] )) {
		$username = $_SERVER ['PHP_AUTH_USER'];
		$password = $_SERVER ['PHP_AUTH_PW'];
		
		unset ( $_COOKIE ['SyncGatewaySession'] );
		setcookie ( "SyncGatewaySession", '', time () - 3600, '/' );
	}
	$app->render ( 200, array ('error' => false, 'message' => 'You are now logged out!') );
} );

$app->post ( '/lostpw', function () use($app) {
	
	$username = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '') && (! isset ( $_POST ['username'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		if (isset ( $post ['username'] )) {
			$username = $post ['username'];
		} else {
			$app->render ( 401, array ('error' => true, 'msg' => 'Username required!') );
			exit ();
		}
	} else {
		if ($username == '') {
			$username = $_POST ['username'];
		}
	}
	
	if ($username != '') {
		
		$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
		
		$user = $cb->get ( "users," . $username );
		
		$user = json_decode ( $user, true );
		
		if (! isset ( $user ['username'] ) || $user ['username'] == '') {
			// user is undefined
			$responseCode = 404;
			$app->render ( $responseCode, array ('error' => true, 'msg' => 'Email ' . $username . ' was not found !' . $user) );
			exit ();
		}
		
		require ("password.php");
		function email_letter($to, $from, $subject = 'no subject', $msg = 'no msg') {
			$headers = "From: $from\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$headers .= 'X-Mailer: PHP/' . phpversion ();
			return mail ( $to, $subject, $msg, $headers );
		}
		// email passed check send email with password reset link
		
		$reset_key = strtotime ( "now" ) * rand ();
		$reset_hash = password_hash ( ( string ) $reset_key, PASSWORD_BCRYPT );
		
		// update key on user table, then verify in resetPassword.php
		
		$user ['reset_token_key'] = $reset_key;
		$cb->set ( "users," . $username, json_encode ( $user ) );
		
		$msg = "To Reset your password click on this link <a href='http://couchbase.triskaideca.com/resetPassword.php?email=" . urlencode ( $user ['username'] ) . "&reset=" . urlencode ( $reset_hash ) . "'>Reset Password</a>";
		$msg .= "<p>OpenMoney IT Team</p>";
		$msg .= "If you did not initiate the lost password link request then ignore this and your password will remain the same.";
		
		$subject = "openmoney: lost password reset REQUESTED for $username ";
		$dear = $username;
		
		$sentEmail = email_letter ( "\"" . $dear . "\"<" . $username . ">", "noreply@openmoney.cc", $subject, $msg );
		$app->render ( 200, array ('sentEmail' => $sentEmail) );
		exit ();
	} else {
		$app->render ( 401, array ('error' => true, msg => 'Email is required!') );
		exit ();
	}
} );

$app->run ();
?>
