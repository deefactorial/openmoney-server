<?php
require 'vendor/autoload.php';

use GuzzleHttp\Exception\ClientException;

// Guzzle Docs
/*
 * $client = new GuzzleHttp\Client();
 * $response = $client->get('http://guzzlephp.org');
 * $res = $client->get('https://api.github.com/user', ['auth' => ['user', 'pass']]);
 * echo $res->getStatusCode();
 * // "200"
 * echo $res->getHeader('content-type');
 * // 'application/json; charset=utf8'
 * echo $res->getBody();
 * // {"type":"User"...'
 * var_export($res->json());
 * // Outputs the JSON decoded data
 *
 * // Send an asynchronous request.
 * $req = $client->createRequest('GET', 'http://httpbin.org', ['future' => true]);
 * $client->send($req)->then(function ($response) {
 * echo 'I completed! ' . $response;
 * });
 */
function get_http_response_code($url) {
	$headers = get_headers($url);
	return substr($headers[0], 9, 3);
}

// global functions
// https://stackoverflow.com/questions/4757392/php-fast-random-string-function
function randomString($length = 10) {
	return bin2hex(openssl_random_pseudo_bytes($length / 2));
}
function ajax_put($doc_id, $document) {
	$url = "https://cloud.openmoney.cc:4985/openmoney_shadow/" . urlencode($doc_id);
	
	$client = new GuzzleHttp\Client();
	
	$request_options = array("json" => json_decode($document, true));
	
	try {
		$response = $client->put($url, $request_options);
	} catch (ClientException $e) {
		$response = $e->getResponse();
	}
	
	$response_code = $response->getStatusCode();
	if ($response_code == 200 || $response_code == 204 || $response_code == 201) {
		return $response->json();
	} else {
		return json_decode("{}", true);
	}
}
function ajax_get($doc_id) {
	$url = "https://cloud.openmoney.cc:4985/openmoney_shadow/" . urlencode($doc_id);
	$client = new GuzzleHttp\Client();
	
	try {
		$response = $client->get($url);
	} catch (ClientException $e) {
		$response = $e->getResponse();
	}
	
	$response_code = $response->getStatusCode();
	if ($response_code == 200) {
		return $response->getBody();
	} else {
		return "{}";
	}
}
function ajax_getView($design_doc, $view, $options, $errors = false) {
	$url = "https://cloud.openmoney.cc:4985/openmoney_shadow/_design/" . urlencode($design_doc) . "/_view/" . urlencode($view);
	$client = new GuzzleHttp\Client();
	$request_options = array("query" => $options);
	
	try {
		$response = $client->get($url, $request_options);
	} catch (ClientException $e) {
		$response = $e->getResponse();
	}
	
	$response_code = $response->getStatusCode();
	if ($response_code == 200) {
		return ($response->json());
	} else {
		return json_decode("{}", true);
	}
}
function ajax_bulkPut($docs) {
	$url = "https://cloud.openmoney.cc:4985/openmoney_shadow/_bulk_docs";
	$client = new GuzzleHttp\Client();
	
	$request_options = array("json" => $docs);
	
	try {
		$response = $client->post($url, $request_options);
	} catch (ClientException $e) {
		$response = $e->getResponse();
	}
	
	$response_code = $response->getStatusCode();
	if ($response_code == 201) {
		return $response->json();
	} else {
		return $response_code;
	}
}



function updateSession(){	
	if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 86400)) {
		// last request was more than one day ago
		session_unset();     // unset $_SESSION variable for the run-time
		session_destroy();   // destroy session data in storage
	}
	$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
	
	if (!isset($_SESSION['CREATED'])) {
		ini_set('session.gc-maxlifetime', 86400);
		$_SESSION['CREATED'] = time();
	} else if (time() - $_SESSION['CREATED'] > 1800) {
		// session started more than 30 minutes ago
		session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
		$_SESSION['CREATED'] = time();  // update creation time
	}
}

function authenticate($app){
	$username = '';
	$password = '';
	$email = '';
	
	$session = false;
	session_start();
	updateSession();//&& $_SESSION['expires'] > time()
	if (isset($_SESSION['username']) && isset($_SESSION['expires']) && isset($_SESSION['password']) ) {
		$username = $_SESSION['username'];
		$password = $_SESSION['password'];
		$expires = $_SESSION['expires'];
		$session = true;
	} else {
		// remove all session variables
		session_unset();
		// destroy the session
		session_destroy();
	}
	session_write_close();
	
	if (($username == '' && $password == '' && $email == '') && (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['email']))) {
		$post = json_decode(file_get_contents('php://input'), true);
	
		if (isset($post['username']))
			$username = $post['username'];
		if (isset($post['password']))
			$password = $post['password'];
		if (isset($post['email']))
			$email = $post['email'];
	
	} else {
		if ($username == '' && $password == '' && $email == '' && $session == false) {
			if (isset($_POST['username']))
				$username = $_POST['username'];
			if (isset($_POST['password']))
				$password = $_POST['password'];
			if (isset($_POST['email']))
				$email = $_POST['email'];
		}
	}
	
	if (($username != '' || $email != '') && $password == '') {
		return array("error"=>'Email or Username and password are required !');
	}

	$user = json_decode(ajax_get("users," . $username), true);
	
	// TODO: cytpographically decode password using cryptographic algorithms specified in the $user ['cryptographic_algorithms'] array.
	require ("password.php");
	
	if(isset($user['password'])) {
	
		if ($email != '' && !password_verify($password, $user['password'])) {
		
			//this needs to be switched to use the sync_gateway
			$cb = new Couchbase("127.0.0.1:8091", "openmoney", "", "openmoney");
// 			$profile_lookup_function = 'function (doc, meta) { if( doc.type == \"profile\" && doc.email && doc.username) {  emit( doc.email, doc.username ); } }';
// 			$designDoc = '{ "views": { "profileLookup" : { "map": "' . $profile_lookup_function . '" } } }';
// 			$cb->setDesignDoc("dev_profile", $designDoc);
			$options = array('startkey' => $email,'endkey' => $email . '\uefff');
		
			// do trading name lookup on
			$profile_result = $cb->view('dev_profile', 'profileLookup', $options);
		
			foreach($profile_result['rows'] as $row) {
				$user = json_decode(ajax_get("users," . $row['value']), true);
			}
		}
		
		if ((isset($user['password']) && password_verify($password, $user['password'])) || $session) {
			return $user;
		} else {
			return array("error"=>"Password did not match!");
		}
	} else {
		$newUser = array();
		$newUser['username'] = strtolower($username);
		$newUser['email'] = strtolower($email);
		$newUser['password'] = $password;
		$newUser['new_user'] = true; 
		return $newUser;
	}
}

function login($user, $app) {
	$session_token = randomString(64);
	
	if (isset($user['session_expires']) ){
		// example format 2015-05-05T19:42:24.349453085Z
		$expiryseconds = strtotime( substr($user['session_expires'],0,strlen($user['session_expires'])-8) . "Z");
		$nowseconds = strtotime("NOW");
	}
	
	//note check expiry has not happened.
	if( isset($user['session_expires']) && $expiryseconds > $nowseconds){
		$session_token = $user['session_token'];
		$sessionID = $user['session_id'];
		$expiry = $user['session_expires'];
		$cookie_name = $user['session_cookie_name'];
	} else {
	
		if(isset($user['username'])) {
			
			$url = 'https://localhost:4985/openmoney_shadow/_user/' . $user['username'];
		
			// update user
			$data = array('name' => $user['username'],'password' => $session_token);
			$json = json_encode($data);
			$options = array('http' => array('method' => 'PUT','content' => $json,'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create($options);
			$default_context = stream_context_set_default($options);
			$result = file_get_contents($url, false, $context);
		
			$url = 'https://localhost:4985/openmoney_shadow/_session';
			$data = array('name' => $user['username'],'password' => $session_token); // time to live 24hrs
			$json = json_encode($data);
			$options = array('http' => array('method' => 'POST','content' => $json,'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
			$context = stream_context_create($options);
			$default_context = stream_context_set_default($options);
		
			$result = file_get_contents($url, false, $context);
		
			$json = json_decode($result, true);
		
			$sessionID = $json['session_id'];
			$expiry = $json['expires'];
			$cookie_name = $json['cookie_name'];
		
			$user['session_id'] = $sessionID;
			$user['session_token'] = $session_token;
			$user['session_expires'] = $expiry;
			$user['session_cookie_name'] = $json['cookie_name'];
				
			ajax_put( "users," . strtolower( $user['username'] ), json_encode ( $user ) );
		
		}
	
	}
	
	if (isset($sessionID)) {
	
		session_start();
		$_SESSION['username'] = strtolower($user['username']);
		$_SESSION['password'] = $session_token;
		$_SESSION['session_id'] = $sessionID;
		$_SESSION['expires'] = strtotime($expiry);
		session_write_close();
	
		setcookie($cookie_name, $sessionID, strtotime($expiry));
		$result = array('cookie_name' => $cookie_name,'sessionID' => $sessionID,'expires' => $expiry,'username' => $user['username'],'session_token' => $session_token,'email' => $user['email']);
	
		return json_encode($result);
		
	} else {
		$app->halt(401, json_encode(array('error' => true,'msg' => 'The session could not be set!')));
	}
}


$app = new \Slim\Slim();

$app->response->headers->set('Content-Type', 'application/json');

$app->notFound(function () use($app) {
	$app->halt(404, json_encode(array('error' => true,'msg' => 'Page Not Found')));
});

// $app->view ( new \JsonApiView () );
// $app->add ( new \JsonApiMiddleware () );
$app->get('/', function () use($app) {
	$app->redirect('/webclient/');
	// echo json_encode ( array ('message' => "Welcome the openmoney json API! go to https://cloud.openmoney.cc/README.md for more information.") );
});

$app->post('/login', function () use($app) {
	
	$user = authenticate($app);
	if(isset($user['error'])){
		$app->halt(401, json_encode(array('error' => true,'msg' => $user['error'])));
		exit();
	} else {
		
		if(isset($user['new_user'])){
			$app->halt(401, json_encode(array('error' => true,'msg' => "No user found!")));
			exit();
		} else {
			echo login($user, $app);
			
			$app->stop();
		}
	}
});

$app->post('/registration', function () use($app) {
	
	$user = authenticate($app);
	
	if(isset($user['error'])){
		if($user['error'] == 'Password did not match!'){
			$app->halt(401, json_encode(array('error' => true,'msg' => 'User already exists!')));
			exit();
		} else {
			$app->halt(401, json_encode(array('error' => true,'msg' => $user['error'])));
			exit();
		}
	}
	
	if (isset($user['new_user']) && $user['new_user']) {
		unset($user['new_user']);
		
		$user['cost'] = $options['cost'] = 10;
		$user['type'] = "users";
		
		require ("password.php");
		
		$password_hash = password_hash($user['password'], PASSWORD_BCRYPT, $options);
		
		$user['password'] = $password_hash;
		$user['password_encryption_algorithm'] = array(PASSWORD_BCRYPT);
		$user['created'] = intval(round(microtime(true) * 1000));
		
		ajax_put( "users," . strtolower( $user['username'] ), json_encode ( $user ) );
		//$cb->set ( "users," . strtolower( $username ), json_encode ( $user ) );
		
 		$bulk_docs = array();
		$user['_id'] = "users," . $user['username'];
		array_push($bulk_docs, $user);
		
		$subusername = $user['username'];
		if (strpos($user['username'], "@") !== false)
			$subusername = substr($user['username'], 0, strpos($user['username'], "@"));
			// $subusername = str_replace ( ".", "", $subusername );
		$subusername = preg_replace("/[^a-zA-Z\d_\.]*/", "", $subusername);
		
		$subspace = "";
		$tradingName = $subusername;
		
		if (strpos($subusername, ".") !== false) {
			$tradingName = substr($subusername, 0, strpos($subusername, "."));
			$subspace = substr($subusername, strpos($subusername, ".") + 1, strlen($subusername));
			unset($space);
			$space = json_decode(ajax_get("space," . strtolower($subspace)), true);
			if (isset($space['steward'])) {
				
				$spaces_array = explode(".", $subspace);
				
				$current_space = $spaces_array[count($spaces_array) - 1]; // last element
				
				$trading_name_space_view['type'] = "space_view";
				$trading_name_space_view['space'] = $current_space;
				$trading_name_space_view['steward'] = array($user['username']);
				$trading_name_space_view['created'] = intval(round(microtime(true) * 1000));
				
				// ajax_put ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
				// $cb->set ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
				
				$trading_name_space_view['_id'] = "space_view," . $user['username'] . "," . strtolower($trading_name_space_view['space']);
				array_push($bulk_docs, $trading_name_space_view);
				
				for($i = count($spaces_array) - 2; $i >= 0; $i--) {
					$current_space = $spaces_array[$i] . "." . $current_space;
					
					$trading_name_space_view['type'] = "space_view";
					$trading_name_space_view['space'] = $current_space;
					$trading_name_space_view['steward'] = array($user['username']);
					$trading_name_space_view['created'] = intval(round(microtime(true) * 1000));
					
					// ajax_put ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
					// $cb->set ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
					
					$trading_name_space_view['_id'] = "space_view," . $user['username'] . "," . strtolower($trading_name_space_view['space']);
					array_push($bulk_docs, $trading_name_space_view);
				}
				
				$subspace = $current_space;
			} else {
				
				// create or add view of the space
				$spaces_array = explode(".", $subspace);
				$current_space = "cc";
				$subspace = "cc";
				for($i = count($spaces_array) - 1; $i >= 0; $i--) {
					// check if the root of what they asked for is cc and ignore
					if ($i == count($spaces_array) - 1 && $spaces_array[$i] == ".cc") {} else {
						$current_space = $spaces_array[$i] . "." . $current_space;
						// if space doesn't exist create it.
						unset($space);
						$space = json_decode(ajax_get("space," . strtolower($current_space)), true);
						
						if (!isset($space['steward'])) {
							
							// create the space as this users
							$trading_name_space['type'] = "space";
							$trading_name_space['space'] = $current_space;
							$trading_name_space['subspace'] = $subspace;
							$trading_name_space['steward'] = array($user['username']);
							$trading_name_space['created'] = intval(round(microtime(true) * 1000));
							
							// $space = ajax_get ( "space," . strtolower( $trading_name_space ['space'] ) );
							
							// ajax_put ( "space," . strtolower( $trading_name_space ['space'] ), json_encode ( $trading_name_space ) );
							// $cb->set ( "space," . strtolower( $trading_name_space ['space'] ), json_encode ( $trading_name_space ) );
							
							$trading_name_space['_id'] = "space," . strtolower($trading_name_space['space']);
							array_push($bulk_docs, $trading_name_space);
						}
						
						$trading_name_space_view['type'] = "space_view";
						$trading_name_space_view['space'] = $current_space;
						$trading_name_space_view['steward'] = array($user['username']);
						$trading_name_space_view['created'] = intval(round(microtime(true) * 1000));
						
						// ajax_put ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
						// $cb->set ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
						
						$trading_name_space_view['_id'] = "space_view," . $user['username'] . "," . strtolower($trading_name_space_view['space']);
						array_push($bulk_docs, $trading_name_space_view);
						
						$subspace = $current_space;
					}
				}
			}
		}
		
		$trading_name_space['type'] = "space";
		$trading_name_space['space'] = $subspace != "" ? $tradingName . "." . $subspace : $tradingName . ".cc";
		$trading_name_space['subspace'] = $subspace != "" ? $subspace : "cc";
		$trading_name_space['steward'] = array($user['username']);
		$trading_name_space['created'] = intval(round(microtime(true) * 1000));
		
		// ajax_put ( "space," . strtolower( $trading_name_space ['space'] ), json_encode ( $trading_name_space ) );
		
		$trading_name_space['_id'] = "space," . strtolower($trading_name_space['space']);
		array_push($bulk_docs, $trading_name_space);
		
		$trading_name_space_view['type'] = "space_view";
		$trading_name_space_view['space'] = $subspace != "" ? $tradingName . "." . $subspace : $tradingName . ".cc";
		$trading_name_space_view['steward'] = array($user['username']);
		$trading_name_space_view['created'] = intval(round(microtime(true) * 1000));
		
		// ajax_put ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
		
		$trading_name_space_view['_id'] = "space_view," . $user['username'] . "," . strtolower($trading_name_space_view['space']);
		array_push($bulk_docs, $trading_name_space_view);
		
		$trading_name_space_view['type'] = "space_view";
		$trading_name_space_view['space'] = "cc";
		$trading_name_space_view['steward'] = array($user['username']);
		$trading_name_space_view['created'] = intval(round(microtime(true) * 1000));
		
		// ajax_put ( "space_view," . strtolower($username) . "," . strtolower( $trading_name_space_view ['space'] ), json_encode ( $trading_name_space_view ) );
		
		$trading_name_space_view['_id'] = "space_view," . $user['username'] . "," . strtolower($trading_name_space_view['space']);
		array_push($bulk_docs, $trading_name_space_view);
		
		$trading_name['type'] = "trading_name";
		$trading_name['trading_name'] = $tradingName;
		$trading_name['name'] = $subspace != "" ? $tradingName . "." . $subspace : $tradingName . ".cc";
		$trading_name['space'] = $subspace != "" ? $subspace : "cc";
		$trading_name['currency'] = "cc";
		$trading_name['steward'] = array($user['username']);
		$trading_name['created'] = intval(round(microtime(true) * 1000));
		
		$exists = json_decode(ajax_get("trading_name," . strtolower($trading_name['name']) . "," . strtolower($trading_name['currency'])), true);
		
		if (isset($exists['steward'])) {
			$app->halt(401, json_encode(array('error' => true,'msg' => 'User already exists!')));
		} else {
			// ajax_put ( "trading_name," . strtolower( $trading_name ['name'] ) . "," . strtolower( $trading_name ['currency'] ), json_encode ( $trading_name ) );
			
			$trading_name['_id'] = "trading_name," . strtolower($trading_name['name']) . "," . strtolower($trading_name['currency']);
			array_push($bulk_docs, $trading_name);
		}
		
		$currency_view['type'] = "currency_view";
		$currency_view['currency'] = "cc";
		$currency_view['steward'] = array($user['username']);
		$currency_view['created'] = intval(round(microtime(true) * 1000));
		
		// ajax_put ( "currency_view," . strtolower( $username ) . "," . strtolower( $currency_view ['currency'] ), json_encode ( $currency_view ) );
		
		$currency_view['_id'] = "currency_view," .$user['username'] . "," . strtolower($currency_view['currency']);
		array_push($bulk_docs, $currency_view);
		
		$subspace_document = json_decode(ajax_get("space," . $user['username']), true);
		$defaultcurrency = strtolower($subspace);
		if (isset($subspace_document['defaultcurrency'])) {
			$defaultcurrency = strtolower($subspace_document['defaultcurrency']);
		}
		
		unset($currency);
		$currency = json_decode(ajax_get("currency," . strtolower($defaultcurrency)), true);
		if (isset($currency['steward'])) {
			$trading_name['currency'] = $currency['currency'];
			
			$exists = json_decode(ajax_get("trading_name," . strtolower($trading_name['name']) . "," . strtolower($trading_name['currency'])), true);
			
			if (isset($exists['steward'])) {
				if ($exists['steward'] != $trading_name['steward']) {
					$app->halt(401, json_encode(array('error' => true,'msg' => 'User already exists!')));
				}
			} else {
				// ajax_put ( "trading_name," . strtolower( $trading_name ['name'] ) . "," . strtolower( $trading_name ['currency'] ), json_encode ( $trading_name ) );
				
				$trading_name['_id'] = "trading_name," . strtolower($trading_name['name']) . "," . strtolower($trading_name['currency']);
				array_push($bulk_docs, $trading_name);
			}
			
			$currency_view['type'] = "currency_view";
			$currency_view['currency'] = strtolower($trading_name['currency']);
			$currency_view['steward'] = array($user['username']);
			$currency_view['created'] = intval(round(microtime(true) * 1000));
			
			// ajax_put ( "currency_view," . strtolower( $username ) . "," . strtolower( $currency_view ['currency'] ), json_encode ( $currency_view ) );
			
			$currency_view['_id'] = "currency_view," . $user['username'] . "," . strtolower($currency_view['currency']);
			array_push($bulk_docs, $currency_view);
		}
		
		$profile['type'] = "profile";
		$profile['username'] = $user['username'];
		$profile['email'] = $user['email'];
		$profile['notification'] = true;
		$profile['mode'] = false;
		$profile['theme'] = false;
		$profile['created'] = intval(round(microtime(true) * 1000));
		
		// ajax_put ( "profile," . strtolower( $username ) , json_encode ( $profile ) );
		
		$profile['_id'] = "profile," . $user['username'];
		array_push($bulk_docs, $profile);
		
		$bulk = array("docs" => $bulk_docs);
		
		$bulk_result = ajax_bulkPut($bulk);
		
		echo login($user, $app);
	
		$app->stop();
		
	}
});

$app->get('/logout', function () use($app) {
	
	session_start();
// 	if (isset($_SESSION['session_id'])) {
// 		$url = 'https://localhost:4985/openmoney_shadow/_session/' . $_SESSION['session_id'];
// 		// $url = 'https://localhost:4985/todos/_session';
// 		// $data = array ('name' => $user ['username'], 'ttl' => 86400); // time to live 24hrs
// 		// $json = json_encode ( $data );
// 		$options = array('http' => array('method' => 'DELETE','header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
// 		$context = stream_context_create($options);
// 		$default_context = stream_context_set_default($options);
		
// 		// $response_code = get_http_response_code ( $url );
		
// 		$result = file_get_contents($url, false, $context);
		
// 		$json = json_decode($result, true);
// 	}
	
	// remove all session variables
	session_unset();
	// destroy the session
	session_destroy();
	
	session_write_close();
	
	// unset ( $_COOKIE ['SyncGatewaySession'] );
	// setcookie ( "SyncGatewaySession", '', time () - 3600, '/' );
	
	// unset cookies
	if (isset($_SERVER['HTTP_COOKIE'])) {
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
		foreach($cookies as $cookie) {
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			setcookie($name, '', time() - 1000);
			setcookie($name, '', time() - 1000, '/');
		}
	}
	
	echo json_encode(array('error' => false,'msg' => 'you are now logged out'));
	$app->stop();
});

$app->post('/lostpw', function () use($app) {
	
	$username = '';
	$email = '';
	
	if (($username == '') && (!isset($_POST['username']))) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		if (isset($post['username'])) {
			$username = $post['username'];
		} else {
			$app->render(401, array('error' => true,'msg' => 'Username or Email required!'));
			exit();
		}
	} else {
		if ($username == '') {
			$username = $_POST['username'];
		}
	}
	
	if ($username != '') {
		
		require ("password.php");
		function email_letter($to, $from, $subject = 'no subject', $msg = 'no msg') {
			$headers = "From: $from\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$headers .= 'X-Mailer: PHP/' . phpversion();
			return mail($to, $subject, $msg, $headers);
		}
		
		$cb = new Couchbase("127.0.0.1:8091", "openmoney", "", "openmoney");
		
		$user = json_decode(ajax_get("users," . $username), true);
		
		if (!isset($user['username']) || $user['username'] == '') {
			
			// $profile_lookup_function = 'function (doc, meta) { if( doc.type == \"profile\" && doc.email && doc.username) { emit( doc.email, doc.username ); } }';
			// $designDoc = '{ "views": { "profileLookup" : { "map": "' . $profile_lookup_function . '" } } }';
			// $cb->setDesignDoc ( "dev_profile", $designDoc );
			$options = array('startkey' => $username,'endkey' => $username . '\uefff','stale' => false);
			
			// do profile email lookup
			$profile_result = $cb->view('dev_profile', 'profileLookup', $options);
			
			foreach($profile_result['rows'] as $row) {
				$user = json_decode(ajax_get("users," . $row['value']), true);
				$email = $username;
			}
			
			if (!isset($user['username']) || $user['username'] == '') {
				// user is undefined
				$responseCode = 404;
				$app->halt($responseCode, json_encode(array('error' => true,'msg' => 'Email ' . $username . ' was not found !' . $user)));
			}
		}
		
		// email passed check send email with password reset link
		
		$reset_key = randomString(64);
		$reset_hash = password_hash($reset_key, PASSWORD_BCRYPT);
		
		// update key on user table, then verify in resetPassword.php
		
		$user['reset_token_key'] = $reset_key;
		ajax_put("users," . $user['username'], json_encode($user));
		
		$msg = "To Reset your password click on this link <a href='https://cloud.openmoney.cc/resetPassword.php?email=" . urlencode($user['username']) . "&reset=" . urlencode($reset_hash) . "'>Reset Password</a>";
		$msg .= "<p>OpenMoney IT Team</p>";
		$msg .= "If you did not initiate the lost password link request then ignore this and your password will remain the same.";
		
		$subject = "openmoney: lost password reset REQUESTED for " . $user['username'];
		$dear = $user['username'];
		
		if ($email != '') {
			$sentEmail = email_letter("\"" . $dear . "\"<" . $email . ">", "noreply@openmoney.cc", $subject, $msg);
		} else {
			$profile = json_decode(ajax_get("profile," . strtolower($user['username'])), true);
			$sentEmail = email_letter("\"" . $dear . "\"<" . $profile['email'] . ">", "noreply@openmoney.cc", $subject, $msg);
		}
		echo json_encode(array('sentEmail' => $sentEmail));
		$app->stop();
	} else {
		$app->halt(401, json_encode(array('error' => true,msg => 'Email is required!')));
	}
});

$app->post('/lookupTag', function () use($app) {
	
	$user = authenticate($app);
	
	$post = json_decode(file_get_contents('php://input'), true);
	$key = $post['key'];
	
	$beamlookup_function = 'function (doc, meta) { if( doc.type == \"beamtag\" ) { if(typeof doc.archived == \"undefined\" || doc.archived === false) { emit(doc.hashTag, doc.trading_names); } } }';
	
	$tradingname_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name\" && doc.steward && doc.name && doc.currency && !doc.archived && !doc.disabled) { emit( \"trading_name,\"+doc.name+\",\"+doc.currency, { \"trading_name\": doc.name, \"currency\": doc.currency } ); } }';
	
	$designDoc = '{ "views": { "tradingnamelookup4" : { "map": "' . $tradingname_lookup_function . '" }, "beamlookup2": { "map": "' . $beamlookup_function . '" } } }';
	
	// echo $designDoc;
	
	// $cb->setDesignDoc("dev_nfctag", $designDoc);
	
	// $result = $cb->view('dev_nfctag', 'beamlookup2', array('startkey' => $key,'endkey' => $key . '\uefff'));
	
	$viewname = 'beamtag';
	$options = array('startkey' => '"' . $key . '"','endkey' => '"' . $key . '\uefff"');
	$options['stale'] = 'false';
	
	$result = ajax_getView('dev_rest', $viewname, $options);
	
	$trading_names_array = array();
	
	foreach($result['rows'] as $row) {
		// remove users, from id
		$trading_names = $row['value'];
		
		foreach($trading_names as $trading_name) {
			array_push($trading_names_array, $trading_name);
			// $options = array('startkey' => "trading_name," . $trading_name['trading_name'] . "," . $trading_name['currency'],'endkey' => "trading_name," . $trading_name['trading_name'] . "," . $trading_name['currency'] . '\uefff');
			
			if (isset($trading_name['trading_name']) && $trading_name['trading_name'] != null) {
				
				$trading_name_view = json_decode(ajax_get("trading_name_view," . $user['username'] . "," . $trading_name['trading_name'] . "," . $trading_name['currency']), true);
				if (!isset($trading_name_view['trading_name'])) {
					
					// add them to their list of senders
					$trading_name_from_view['type'] = "trading_name_view";
					$trading_name_from_view['steward'] = array($username);
					$trading_name_from_view['trading_name'] = $trading_name['trading_name'];
					$trading_name_from_view['currency'] = $trading_name['currency'];
					$trading_name_from_view['created'] = intval(round(microtime(true) * 1000));
					ajax_put("trading_name_view," . $username . "," . $trading_name_from_view['trading_name'] . "," . $trading_name_from_view['currency'], json_encode($trading_name_from_view));
				}
			}
		}
	}
	
	if (empty($trading_names_array)) {
		
		$app->halt(404, json_encode(array('error' => true,msg => 'No Trading Names Found!')));
	} else {
		// output array
		echo json_encode($trading_names_array);
		
		$app->stop();
	}

});

$app->post('/customerLookup', function () use($app) {
	
	$username = '';
	$password = '';
	
	if (($username == '' && $password == '') && (!isset($_POST['username']) || !isset($_POST['password']))) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		$username = $post['username'];
		$password = $post['password'];
		
		if ($username == '' && $password == '') {
			$app->halt(401, json_encode(array('error' => true,'msg' => 'Email and password are required !')));
		}
	} else {
		if ($username == '' && $password == '') {
			$username = $_POST['username'];
			$password = $_POST['password'];
		}
	}
	
	$cb = new Couchbase("127.0.0.1:8091", "openmoney", "", "openmoney");
	
	$user = ajax_get("users," . $username);
	
	$user = json_decode($user, true);
	
	// TODO: cytpographically decode password using cryptographic algorithms specified in the $user ['cryptographic_algorithms'] array.
	require ("password.php");
	
	if (password_verify($password, $user['password'])) {
		
		$tradingname_lookup_function = 'function (doc, meta) { if( doc.type == \"trading_name\" && doc.steward && doc.name && doc.currency && !doc.archived) { doc.steward.forEach(function( steward ) { emit( [steward, doc.currency, doc.name], { \"name\": doc.name, \"currency\": doc.currency } ); } ); } }';
		
		$designDoc = '{ "views": { "tradingnamelookup" : { "map": "' . $tradingname_lookup_function . '" } } }';
		
		// echo $designDoc;
		
		$cb->setDesignDoc("dev_customer", $designDoc);
		
		$options = array('startkey' => array($username),'endkey' => array($username . '\uefff','\uefff','\uefff'));
		
		// do trading name lookup on
		$tradingname_result = $cb->view('dev_customer', 'tradingnamelookup', $options);
		
		$tradingname_array = array();
		foreach($tradingname_result['rows'] as $row) {
			unset($object);
			$object['id'] = $row['id'];
			$object['value'] = $row['value'];
			array_push($tradingname_array, $object);
		}
		
		echo json_encode($tradingname_array);
		
		$app->stop();
	} else {
		$app->halt(401, json_encode(array('error' => true,'msg' => 'Password did not match!')));
	}
});


$app->run();
?>
