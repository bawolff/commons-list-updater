<?php
$token = null;
$ch = curl_init( 'https://commons.wikimedia.org/w/api.php');
function setup() {
	global $token;

	$tokens = sendRequest( [ 'action' => 'query', 'meta' => 'tokens', 'type' => 'login' ] );
	if ( !isset( $tokens['query']['tokens']['logintoken'] ) ) {
		die( "Could not get token" );
	}
	$loginToken = $tokens['query']['tokens']['logintoken'];

	$username = getenv( 'WIKI_USER' );
	$password = getenv( 'WIKI_PASS' ); 
	$loginRes = sendRequest( [ 'action' => 'login', 'lgname' => $username, 'lgpassword' => $password, 'lgtoken' => $loginToken ] );
	if ( ($loginRes['login']['result'] ?? '' ) !== 'Success' ) {
		var_dump( $loginRes );
		die( "Could not login" );
	}

	$tokens = sendRequest( [ 'action' => 'query', 'meta' => 'tokens', 'type' => 'csrf' ] );
	if ( !isset( $tokens['query']['tokens']['csrftoken'] ) ) {
		die( "Could not get csrf token" );
	}
	$token = $tokens['query']['tokens']['csrftoken'];
	if ( strlen( $token ) < 8 ) {
		die( 'invalid token' );
	}
}

function sendRequest( $args ) {
	global $ch;
	curl_setopt($ch, CURLOPT_COOKIEFILE, '');


	$formData = http_build_query( $args + [ 'format' => 'json', 'formatversion' => '2' ] );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $formData );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'commons-list-updater (https://commons.wikimedia.org/wiki/User:Bawolff_bot)' );
	$resp = curl_exec( $ch );
//	curl_close( $ch );
	$json = json_decode( $resp, true );
	if ( !$json ) {
		die( "Error bad server resp: $resp.\n Req was $formData" );
	}
	return $json;
}
function savePage( $pageName, $contents ) {
	global $token;
	if ( !$token ) {
		setup();
	}
	$res = sendRequest( [
		'action' => 'edit',
		'title' => $pageName,
		'text' => $contents,
		'summary' => 'Automatic update of image lists',
		'nocreate' => 1,
		'token' => $token,
		'bot' => 1,
		'assert' => 'user'
	] );
	if ( $res['edit']['result'] !== 'Success' ) {
		var_dump( $res );
	}
}
