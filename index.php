<?php
// adjust these parameters to match your installation
// $cb = new Couchbase("127.0.0.1:8091", "users", "", "users");
// $cb->set("a", 101);
// var_dump($cb->get("a"));
require 'vendor/autoload.php';

$app = new \Slim\Slim ();

$app->response->headers->set ( 'Content-Type', 'application/json' );

$app->notFound ( function () use($app) {
	$app->halt ( 404, json_encode ( array ('error' => true, 'msg' => 'Page Not Found') ) );
} );

// $app->view ( new \JsonApiView () );
// $app->add ( new \JsonApiMiddleware () );
$app->get ( '/', function () use($app) {
	echo json_encode ( array ('message' => "Welcome the openmoney json API! go to https://cloud.openmoney.cc/README.md for more information.") );
} );

$app->post ( '/login', function () use($app) {
	
	$username = '';
	$password = '';
	$email = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '' && $password == '' && $email == '') && (! isset ( $_POST ['username'] ) || ! isset ( $_POST ['password'] ) || ! isset ( $_POST ['email'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		if (isset($post ['username']))
			$username = $post ['username'];
		if (isset($post ['password']))
			$password = $post ['password'];
		if (isset($post ['email']))
			$email = $post ['email'];
		
		if (($username != '' || $email != '') && $password == '') {
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'Email or Username and password are required !') ) );
		}
	} else {
		if ($username == '' && $password == '' && $email == '') {
			if( isset( $_POST ['username']) )
				$username = $_POST ['username'];
			if( isset( $_POST ['password']) )
				$password = $_POST ['password'];
			if( isset( $_POST ['email']) )
				$email = $_POST ['email'];
		}
	}
	
	$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
	
	$user = $cb->get ( "users," . $username );
	
	// TODO: cytpographically decode password using cryptographic algorithms specified in the $user ['cryptographic_algorithms'] array.
	require ("password.php");
	
	if( $email != '' && ! password_verify ( $password, $user ['password'] ) ) {
		
		$profile_lookup_function = 'function (doc, meta) { if( doc.type == \"profile\" && doc.email && doc.username) {  emit( doc.email, doc.username ); } }';
		$designDoc = '{ "views": { "profileLookup" : { "map": "' . $profile_lookup_function . '" } } }';
		$cb->setDesignDoc ( "dev_profile", $designDoc );
		$options = array ('startkey' => $email, 'endkey' => $email . '\uefff');
			
		// do trading name lookup on
		$profile_result = $cb->view ( 'dev_profile', 'profileLookup', $options );
	
		
		foreach ( $profile_result ['rows'] as $row ) {
			$user = $cb->get ( "users," . $row['value'] );
		}
		
	}
	
	$user = json_decode ( $user, true );
	
	if (password_verify ( $password, $user ['password'] )) {
		$url = 'https://localhost:4985/openmoney_shadow/_session';
		// $url = 'https://localhost:4985/todos/_session';
		$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
		$json = json_encode ( $data );
		$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
		$context = stream_context_create ( $options );
		$default_context = stream_context_set_default ( $options );
		
		if (get_http_response_code ( $url ) != "404") {
			$result = file_get_contents ( $url, false, $context );
		} else {
			// user exists in db but not in sync_gateway so create the user
			$url = 'https://localhost:4985/openmoney_shadow/_user/' . $username;
			// $url = 'https://localhost:4985/todos/_user/' . $username;
			$data = array ('name' => $user ['username'], 'password' => $password);
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
			
			$url = 'https://localhost:4985/openmoney_shadow/_session';
			// $url = 'https://localhost:4985/todos/_session';
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
			$result = array ('sessionID' => $json ['session_id'], 'expires' => $json ['expires'], 'username' => $user ['username'], 'email' => $email);

			echo json_encode ( $result );
			$app->stop ();
		} else {
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'The session could not be set!') ) );
		}
	} else {
		$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'Password did not match!') ) );
	}
} );

$app->post ( '/registration', function () use($app) {
	
	$username = '';
	$password = '';
	$email = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '' && $password == '' && $email == '') && (! isset ( $_POST ['username'] ) || ! isset ( $_POST ['password'] ) || ! isset ( $_POST ['email'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		if (isset($post ['username']))
			$username = $post ['username'];
		if (isset($post ['password']))
			$password = $post ['password'];
		if (isset($post ['email']))
			$email = $post ['email'];
		
		if (($username != '' || $email != '') && $password == '') {
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'Email or Username and password are required !') ) );
		}
	} else {
		if ($username == '' && $password == '' && $email == '') {
			if( isset( $_POST ['username']) )
				$username = $_POST ['username'];
			if( isset( $_POST ['password']) )
				$password = $_POST ['password'];
			if( isset( $_POST ['email']) )
				$email = $_POST ['email'];
		}
	}
	
	$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
	$user = $cb->get ( "users," . $username );
	
	if( $email != null && $user ['password'] == '') {
		
		$profile_lookup_function = 'function (doc, meta) { if( doc.type == \"profile\" && doc.email && doc.username) {  emit( doc.email, doc.username ); } }';
		$designDoc = '{ "views": { "profileLookup" : { "map": "' . $profile_lookup_function . '" } } }';
		$cb->setDesignDoc ( "dev_profile", $designDoc );
		$options = array ('startkey' => $email, 'endkey' => $email . '\uefff');
			
		// do trading name lookup on
		$profile_result = $cb->view ( 'dev_profile', 'profileLookup', $options );
	

		foreach ( $profile_result ['rows'] as $row ) {
			$user = $cb->get ( "users," . $row['value'] );
		}
		
	}
	
	$user = json_decode ( $user, true );
	
	// TODO: cytpographically decode password using cryptographic algorithms specified in the $user ['cryptographic_algorithms'] array.
	require ("password.php");
	
	if (! isset ( $user ['password'] ) || $user ['password'] == '') {
		
		$user ['username'] = $username;
		$user ['email'] = $email;
		$user ['cost'] = $options ['cost'] = 10;
		$user ['type'] = "users";
		
		$password_hash = password_hash ( $password, PASSWORD_BCRYPT, $options );
		
		$user ['password'] = $password_hash;
		$user ['password_encryption_algorithm'] = array (PASSWORD_BCRYPT);
		$user ['created'] = intval( round(microtime(true) * 1000) );
		
		$cb->set ( "users," . $username, json_encode ( $user ) );
		$subusername = $username;
		if( strpos ( $username, "@" ) )
			$subusername = substr ( $username, 0, strpos ( $username, "@" ) );
		//$subusername = str_replace ( ".", "", $subusername );
		$subusername = preg_replace ( "/[^a-zA-Z\d]*/", "", $subusername );
		
		$trading_name_space ['type'] = "space";
		$trading_name_space ['space'] = $subusername;
		$trading_name_space ['subspace'] = '';
		$trading_name_space ['steward'] = array ($username);
		$trading_name_space ['created'] = intval( round(microtime(true) * 1000) );
		
		$cb->set ( "space," . $trading_name_space ['space'], json_encode ( $trading_name_space ) );
		
		$trading_name ['type'] = "trading_name";
		$trading_name ['trading_name'] = $subusername;
		$trading_name ['name'] = $subusername;
		$trading_name ['space'] = "";
		$trading_name ['currency'] = "cc";
		$trading_name ['steward'] = array ($username);
		$trading_name ['created'] = intval( round(microtime(true) * 1000) );
		
		$cb->set ( "trading_name," . $trading_name ['trading_name'] . "," . $trading_name ['currency'], json_encode ( $trading_name ) );
		
		$profile ['type'] = "profile";
		$profile ['email'] = $email;
		$profile ['notification'] = true;
		$profile ['mode'] = false;
		$profile ['theme'] = true;
		$profile ['created'] = intval( round(microtime(true) * 1000) );
		
		$cb->set ( "profile," . $username , json_encode ( $profile ) );
		
		// TODO: send email verification or write an email bot to look for new registrations
		
		$url = 'https://localhost:4985/openmoney_shadow/_session';
		// $url = 'https://localhost:4985/todos/_session';
		$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
		$json = json_encode ( $data );
		$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
		$context = stream_context_create ( $options );
		$default_context = stream_context_set_default ( $options );
		
		if (get_http_response_code ( $url ) != "404") {
			$result = file_get_contents ( $url, false, $context );
		} else {
			// user exists in db but not in sync_gateway so create the user
			$url = 'https://localhost:4985/openmoney_shadow/_user/' . $username;
			// $url = 'https://localhost:4985/todos/_user/' . $username;
			$data = array ('name' => $user ['username'], 'password' => $password);
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
			
			$url = 'https://localhost:4985/openmoney_shadow/_session';
			// $url = 'https://localhost:4985/todos/_session';
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
			
			echo json_encode ( $result );
			
			$app->stop ();
		} else {
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'The session could not be set!') ) );
		}
	} else {
		$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'User already exists!') ) );
	}
} );

$app->get ( '/logout', function () use($app) {
	
	unset ( $_COOKIE ['SyncGatewaySession'] );
	setcookie ( "SyncGatewaySession", '', time () - 3600, '/' );
	
	echo json_encode ( array ('error' => false, 'msg' => 'you are now logged out') );
	$app->stop ();
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
			$app->render ( 401, array ('error' => true, 'msg' => 'Username or Email required!') );
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
			
			
			
			$profile_lookup_function = 'function (doc, meta) { if( doc.type == \"profile\" && doc.email && doc.username) {  emit( doc.email, doc.username ); } }';
			$designDoc = '{ "views": { "profileLookup" : { "map": "' . $profile_lookup_function . '" } } }';
			$cb->setDesignDoc ( "dev_profile", $designDoc );
			$options = array ('startkey' => $username, 'endkey' => $username . '\uefff');
				
			// do trading name lookup on
			$profile_result = $cb->view ( 'dev_profile', 'profileLookup', $options );
		
		
			foreach ( $profile_result ['rows'] as $row ) {
				$user = $cb->get ( "users," . $row['value'] );
			}
			
			$user = json_decode ( $user, true );
			
			
			
			
			if (! isset ( $user ['username'] ) || $user ['username'] == '') {
				// user is undefined
				$responseCode = 404;
				$app->halt ( $responseCode, json_encode ( array ('error' => true, 'msg' => 'Email ' . $username . ' was not found !' . $user) ) );
			}
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
		$cb->set ( "users," . $user ['username'], json_encode ( $user ) );
		
		$msg = "To Reset your password click on this link <a href='https://cloud.openmoney.cc/resetPassword.php?email=" . urlencode ( $user ['username'] ) . "&reset=" . urlencode ( $reset_hash ) . "'>Reset Password</a>";
		$msg .= "<p>OpenMoney IT Team</p>";
		$msg .= "If you did not initiate the lost password link request then ignore this and your password will remain the same.";
		
		$subject = "openmoney: lost password reset REQUESTED for " . $user ['username'];
		$dear = $user ['username'];
		
		$sentEmail = email_letter ( "\"" . $dear . "\"<" . $user ['email'] . ">", "noreply@openmoney.cc", $subject, $msg );
		echo json_encode ( array ('sentEmail' => $sentEmail) );
		$app->stop ();
	} else {
		$app->halt ( 401, json_encode ( array ('error' => true, msg => 'Email is required!') ) );
	}
} );

$app->post ( '/lookupTag', function () use($app) {
	
	$username = '';
	$password = '';
	$key = '';
	function get_http_response_code($url) {
		$headers = get_headers ( $url );
		return substr ( $headers [0], 9, 3 );
	}
	
	if (($username == '' && $password == '') && (! isset ( $_POST ['username'] ) || ! isset ( $_POST ['password'] ))) {
		$post = json_decode ( file_get_contents ( 'php://input' ), true );
		
		$username = $post ['username'];
		$password = $post ['password'];
		$key = $post ['key'];
		
		if ($username == '' || $password == '' || $key == '') {
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'Email, password and key are required !') ) );
		}
	} else {
		if ($username == '' && $password == '') {
			$username = $_POST ['username'];
			$password = $_POST ['password'];
			$key = $_POST ['key'];
		}
	}
	
	if ($username != '') {
		
		$cb = new Couchbase ( "127.0.0.1:8091", "openmoney", "", "openmoney" );
		
		$user = $cb->get ( "users," . $username );
		
		$user = json_decode ( $user, true );
		
		if (! isset ( $user ['username'] ) || $user ['username'] == '') {
			// user is undefined
			$responseCode = 404;
			$app->halt ( $responseCode, json_encode ( array ('error' => true, 'msg' => 'Email ' . $username . ' was not found !' . $user) ) );
		}
		
		require ("password.php");
		
		if (password_verify ( $password, $user ['password'] )) {
			// user is verified
			// do lookup on tag
			
			$beamlookup_function = 'function (doc, meta) { if( doc.type == \"beamtag\" ) { emit(doc.hashTag, doc.username); } }';
			
			$taglookup_function = 'function (doc, meta) { if( doc.type == \"users\" && doc.tags ) { doc.tags.forEach(function(tag) { emit(tag.hashTag, tag); } ); } }';
			$tradingname_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name\" && doc.steward && doc.name && doc.currency) { doc.steward.forEach(function( steward ) { emit( [steward, doc.currency, doc.name], { \"name\": doc.name, \"currency\": doc.currency } ); } ); } }';
			
			$designDoc = '{ "views": { "taglookup": { "map": "' . $taglookup_function . '" }, "tradingnamelookup1" : { "map": "' . $tradingname_lookup_function . '" }, "beamlookup": { "map": "' . $beamlookup_function . '" } } }';
			
			// echo $designDoc;
			
			$cb->setDesignDoc ( "dev_nfctag", $designDoc );
			
			// echo $cb->getDesignDoc( "dev_nfctag" );
			
			// , array('startkey' => $key, 'endkey' => $key)
			// startkey : [ id, {} ], endkey : [ id ], descending : true, include_docs : true
			
			$result = $cb->view ( 'dev_nfctag', 'taglookup', array ('startkey' => $key, 'endkey' => $key . '\uefff') );
			
			$tradingname_array = array ();
			
			foreach ( $result ['rows'] as $row ) {
				// remove users, from id
				$username = substr ( $row ['id'], 6, strlen ( $row ['id'] ) );
				
				// echo $username;
				
				$options = array ('startkey' => array ($username), 'endkey' => array ($username . '\uefff', '\uefff', '\uefff'));
				
				// do trading name lookup on
				$tradingname_result = $cb->view ( 'dev_nfctag', 'tradingnamelookup1', $options );
				
				
				foreach ( $tradingname_result ['rows'] as $row ) {
					unset ( $object );
					$object ['id'] = $row ['id'];
					$object ['value'] = $row ['value'];
					array_push ( $tradingname_array, $object );
				}
				
				
			}
			
			$result = $cb->view ( 'dev_nfctag', 'beamlookup', array ('startkey' => $key, 'endkey' => $key . '\uefff') );
			
			foreach ( $result ['rows'] as $row ) {
				// remove users, from id
				$username = $row ['value'];
			
				// echo $username;
			
				$options = array ('startkey' => array ($username), 'endkey' => array ($username . '\uefff', '\uefff', '\uefff'));
			
				// do trading name lookup on
				$tradingname_result = $cb->view ( 'dev_nfctag', 'tradingnamelookup1', $options );
			
				
				foreach ( $tradingname_result ['rows'] as $row ) {
					unset ( $object );
					$object ['id'] = $row ['id'];
					$object ['value'] = $row ['value'];
					array_push ( $tradingname_array, $object );
				}
			
				
			}
			
			echo json_encode ( $tradingname_array );
			
			$app->stop ();
		}
	} else {
		$app->halt ( 401, json_encode ( array ('error' => true, msg => 'Email is required!') ) );
	}
} );

$app->post ( '/customerLookup', function () use($app) {
	
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
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'Email and password are required !') ) );
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
		$url = 'https://localhost:4985/openmoney_shadow/_session';
		// $url = 'https://localhost:4985/todos/_session';
		$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
		$json = json_encode ( $data );
		$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
		$context = stream_context_create ( $options );
		$default_context = stream_context_set_default ( $options );
		
		if (get_http_response_code ( $url ) != "404") {
			$result = file_get_contents ( $url, false, $context );
		} else {
			// user exists in db but not in sync_gateway so create the user
			$url = 'https://localhost:4985/openmoney_shadow/_user/' . $username;
			// $url = 'https://localhost:4985/todos/_user/' . $username;
			$data = array ('name' => $user ['username'], 'password' => $password);
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'PUT', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
			
			$url = 'https://localhost:4985/openmoney_shadow/_session';
			// $url = 'https://localhost:4985/todos/_session';
			$data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
			$json = json_encode ( $data );
			$options = array ('http' => array ('method' => 'POST', 'content' => $json, 'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create ( $options );
			$default_context = stream_context_set_default ( $options );
			
			$result = file_get_contents ( $url, false, $context );
		}
		
		$json = json_decode ( $result, true );
		
		if (isset ( $json ['session_id'] )) {
			
			// setcookie ( $json ['cookie_name'], $json ['session_id'], strtotime ( $json ['expires'] ) );
			// $result = array ('sessionID' => $json ['session_id'], 'expires' => $json ['expires']);
			
			$tradingname_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name\" && doc.steward && doc.name && doc.currency) { doc.steward.forEach(function( steward ) { emit( [steward, doc.currency, doc.name], { \"name\": doc.name, \"currency\": doc.currency } ); } ); } }';
			
			$designDoc = '{ "views": { "tradingnamelookup" : { "map": "' . $tradingname_lookup_function . '" } } }';
			
			// echo $designDoc;
			
			$cb->setDesignDoc ( "dev_customer", $designDoc );
			
			$options = array ('startkey' => array ($username), 'endkey' => array ($username . '\uefff', '\uefff', '\uefff'));
			
			// do trading name lookup on
			$tradingname_result = $cb->view ( 'dev_customer', 'tradingnamelookup', $options );
			
			$tradingname_array = array ();
			foreach ( $tradingname_result ['rows'] as $row ) {
				unset ( $object );
				$object ['id'] = $row ['id'];
				$object ['value'] = $row ['value'];
				array_push ( $tradingname_array, $object );
			}
			
			echo json_encode ( $tradingname_array );
			
			$app->stop ();
		} else {
			$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'The session could not be set!') ) );
		}
	} else {
		$app->halt ( 401, json_encode ( array ('error' => true, 'msg' => 'Password did not match!') ) );
	}
} );

$app->run ();
?>
