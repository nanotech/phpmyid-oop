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
	foreach ($bytes as $byte)
		$b .= pack('C', $byte);

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
function sha1_20 ($v) {
	if (version_compare(phpversion(), '5.0.0', 'ge')) {
		return sha1($v, true);
	}

	$hex = sha1($v);
	$r = '';
	for ($i = 0; $i < 40; $i += 2) {
		$hexcode = substr($hex, $i, 2);
		$charcode = base_convert($hexcode, 16, 10);
		$r .= chr($charcode);
	}
	return $r;
}

/**
 * Do an HMAC
 * @param string $key
 * @param string $data
 * @param string $hash
 * @return string
 * @url http://php.net/manual/en/function.sha1.php#39492 Borrowed from
 */
function hmac($key, $data, $hash = 'sha1_20') {
	$blocksize=64;

	if (strlen($key) > $blocksize) {
		$key = $hash($key);
	}

	$key = str_pad($key, $blocksize,chr(0x00));
	$ipad = str_repeat(chr(0x36),$blocksize);
	$opad = str_repeat(chr(0x5c),$blocksize);

	$h1 = $hash(($key ^ $ipad) . $data);
	$hmac = $hash(($key ^ $opad) . $h1);
	return $hmac;
}
?>
