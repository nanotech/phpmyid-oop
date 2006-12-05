<?

/*
 *
 */

$profile = array(
	'auth_username'	=> 	'cniemira',
	'auth_password' =>	'f6e0aa8acc53e90f26ccdb2d45d04a46',
	'domain_hash' =>	'0d599f0ec05c3bda8c3b8a68c32a1b47',
);



/*
 * Internal configuration
 * DO NOT ALTER THESE SETTINGS UNLESS YOU KNOW WHAT YOU ARE DOING!
 */

$idp_url = sprintf("http://%s:%s%s",
		   $_SERVER['SERVER_NAME'],
		   $_SERVER['SERVER_PORT'],
		   $_SERVER['PHP_SELF']);

$known = array(
	'assoc_types'	=> array('HMAC-SHA1'),

	'openid_modes'	=> array('associate',
				 'checkid_immediate',
				 'checkid_setup',
				 'check_authentication',
				 'error'),

	'session_types'	=> array('',
				 'DH-SHA1'),
);

$g = 2;
$p = '15517289818147369747123225776371553991572480196691540447970779531405762' .
'9378541917580651227423698188993727816152646631438561595825688188889951272158' .
'8426754199503412587065565498035801048705376814767265132557470407658574792912' .
'9157233451064324509471500722962109419434978392598476037559498584825335930558' .
'5439638443';



/*
 * Runmode functions
 */

function associate_mode () {
	global $g, $known, $p;

	// Validate the request
	if (! isset($_POST['openid.mode']) || $_POST['openid.mode'] != 'associate')
		error_400();

	$assoc_type = (isset($_POST['openid.assoc_type'])
		    && in_array($_POST['openid.assoc_type'], $known['assoc_types']))
			? $_POST['openid.assoc_type']
			: 'HMAC-SHA1';

	$session_type = (isset($_POST['openid.session_type'])
		      && in_array($_POST['openid.session_type'], $known['session_types']))
			? $_POST['openid.session_type']
			: '';

	$dh_modulus = (isset($_POST['openid.dh_modulus']))
		? $_POST['openid.dh_modulus']
		: ($session_type == 'DH-SHA1'
			? $p
			: null);

	$dh_gen = (isset($_POST['openid.dh_gen']))
		? $_POST['openid.dh_gen']
		: ($session_type == 'DH-SHA1'
			? $g
			: null);

	$dh_consumer_public = (isset($_POST['openid.dh_consumer_public']))
		? base64_decode($_POST['openid.dh_consumer_public'])
		: ($session_type == 'DH-SHA1'
			? error_post('dh_consumer_public was not specified')
			: null);


	// Store info for this client using built-in sessions
	$assoc_handle = session_id();
	$lifetime = time() + 60;
	$shared_secret = encode($assoc_handle, $assoc_type);

	$_SESSION['dh_consumer_public'] = $dh_consumer_public;
	$_SESSION['lifetime'] = $lifetime;
	$_SESSION['session_type'] = $session_type;
	$_SESSION['shared_secret'] = $shared_secret;


	$keys = array(
		'assoc_type' => $assoc_type,
		'assoc_handle' => $assoc_handle,
		'expires_in' => $lifetime,
	);

	switch ($session_type) {
		case 'DH-SHA1':
			// create a private key
			$private_key = random($dh_modulus);

			$public_key = bcpowmod($dh_gen, $private_key, $dh_modulus);
			$ss = bcpowmod($dh_consumer_public, $private_key, $dh_modulus);
			$enc_ss = encode($ss);

			$keys['dh_server_public'] = base64_encode($public_key);
			$keys['enc_mac_key'] = base64_encode($enc_ss xor $shared_secret);
			$keys['session_type'] = $session_type;

			$_SESSION['private_key'] = $private_key;
			$_SESSION['public_key'] = $public_key;
			$_SESSION['ss'] = $ss;
			break;

		default:
			$keys['mac_key'] = base64_encode($shared_secret);

			$_SESSION['mac_key'] = $shared_secret();
	}

	wrap_kv($keys);
}

function check_authentication_mode () {
}

function check_login_mode () {
	global $idp_url;

	// If there isn't cached checkid information, this is a non-user request
	if (! isset($_SESSION['checkid']))
		wrap_html(sprintf('<p>myOpenID Server: %s</p>', $idp_url));

	//ask the user to log in
	//forward them back to the returnto in the checkid
}

function checkid_immediate_mode () {
}

function checkid_setup_mode () {
}

function error_mode () {
}

function no_mode () {
	global $idp_url;

	wrap_html(sprintf('You are currently authenticated to %s as %s', $idp_url, $_SESSION['auth_username']));
}



/*
 * Support functions
 */
function random ( $max ) {
	if (strlen($max) < 4)
		return rand(1, $max - 1);

	for($i=1; $i<strlen($max); $i++)
		$rv .= rand(0,9);

	return $rv;
}

function encode ($value, $type) {
	switch ($type) {
		default:
			return(sha1($value));
	}
}

function error_400 () {
	header("HTTP/1.1 400 Bad Request");
	wrap_html('<p>Bad Request</p>');
}

function error_post ( $message ) {
	header("HTTP/1.1 400 Bad Request");
	echo ('error:' . $message);
	exit(0);
}

function wrap_kv ( $keys ) {
	header('Content-Type: text\plain; charset=UTF-8');
	foreach ($keys as $key => $value)
		printf('%s:%s', $key, $value);

	exit(0);
}

function wrap_html ( $message ) {
	global $idp_url;

	header('Content-Type: text\xhtml; charset=UTF-8');
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>myOpenID</title>
<link rel="openid.server" href="' . $idp_url . '" />
</head>
<body>
' . $message . '
</body>
</html>
';

	exit(0);
}



/*
 *
 */

// Start the user session
session_name('myOpenID_Server_SID');
session_set_cookie_params(0, dirname($_SERVER['PHP_SELF']), $profile['domain']);
session_start();

// Decide if the user is authenticated
$user_authenticated = (isset($_SESSION['auth_username'])
		    && $_SESSION['auth_username'] == $profile['auth_username'])
		? true
		: false;

// Did the user request a known runmode? if not, decide on a default mode
$run_mode = (isset($_REQUEST['openid.mode'])
	  && in_array($_REQUEST['openid.mode'], $known['openid_modes']))
	? $_REQUEST['openid.mode']
	: ($user_authenticated
		? 'no'
		: 'check_login'
	);

// Run in the established runmode
eval($run_mode . '_mode();');
?>
