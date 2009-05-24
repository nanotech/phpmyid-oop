<?php
require_once 'big_math.php';

/**
 * Implement binary x_or
 * @param string $a
 * @param string $b
 * @return string
 */
function x_or ($a, $b) {
	$r = '';

	for ($i = 0; $i < strlen($b); $i++) {
		$r .= $a[$i] ^ $b[$i];
	}

	return $r;
}

/**
 * Get a binary value
 * @param integer $n
 * @return string
 * @url http://openidenabled.com Borrowed from PHP-OpenID
 */
function bin ($n) {
	$bytes = array();
	while (bmcomp($n, 0) > 0) {
		array_unshift($bytes, bmmod($n, 256));
		$n = bmdiv($n, bmpow(2,8));
	}

	if ($bytes && ($bytes[0] > 127))
		array_unshift($bytes, 0);

	$b = '';
	foreach ($bytes as $byte) {
		$b .= pack('C', $byte);
	}

	return $b;
}

/**
 * Turn a binary back into a long
 * @param string $b
 * @return integer
 * @url http://openidenabled.com Borrowed from PHP-OpenID
 */
function long($b) {
	$bytes = array_merge(unpack('C*', $b));
	$n = 0;
	foreach ($bytes as $byte) {
		$n = bmmul($n, bmpow(2,8));
		$n = bmadd($n, $byte);
	}
	return $n;
}

/**
 * Do SHA1 20 byte encryption
 * @param string $v
 * @return string
 * @url http://openidenabled.com Borrowed from PHP-OpenID
 */
function sha1_20($v, $raw = true) {
	if (!version_compare(phpversion(), '5.0.0', 'ge')) {
		return sha1($v, $raw);
	}

	$hex = sha1($v);
	if ($raw) {
		$hex = pack('H*', $hex);
	}
	return $hex;
}

/**
 * Generate a keyed hash value using the HMAC method
 * (PHP 5 >= 5.1.2, PECL hash >= 1.1)
 *
 * @param string $algo
 * @param string $key
 * @param string $data
 * @param string $raw_output
 * @return string
 * @url http://php.net/manual/en/function.sha1.php#39492 Borrowed from
 */
if (!function_exists('hash_hmac')) {
	function hash_hmac($algo, $data, $key, $raw_output = false) {

		if ($algo == 'sha1') {
			$algo = 'sha1_20';
		}

		$blocksize = 64;

		if (strlen($key) > $blocksize) {
			$key = $algo($key, $raw_output);
		}

		$key = str_pad($key, $blocksize,chr(0x00));
		$ipad = str_repeat(chr(0x36),$blocksize);
		$opad = str_repeat(chr(0x5c),$blocksize);

		$h1 = $algo(($key ^ $ipad) . $data, $raw_output);
		$hmac = $algo(($key ^ $opad) . $h1, $raw_output);
		return $hmac;
	}
}

/**
 * Look for the point of differentiation in two strings
 * @param string $a
 * @param string $b
 * @return int
 */
function str_diff_pos($a, $b) {
	if ($a == $b) {
		return -1;
	}

	$n = min(strlen($a), strlen($b));

	for ($i = 0; $i < $n; $i++) {
		if ($a[$i] != $b[$i]) {
			return $i;
		}
	}

	return $n;
}
?>
