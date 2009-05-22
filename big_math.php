<?php
/**
 * Create a big math addition function
 * @param string $l
 * @param string $r
 * @return string
 * @url http://www.icosaedro.it/bigint Inspired by
 */
function bmadd($l, $r) {
	if (function_exists('bcadd')) {
		return bcadd($l, $r);
	}

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_add($l, $r));
	}

	$l = strval($l); $r = strval($r);
	$ll = strlen($l); $rl = strlen($r);
	if ($ll < $rl) {
		$l = str_repeat("0", $rl-$ll) . $l;
		$o = $rl;

	} elseif ( $ll > $rl ) {
		$r = str_repeat("0", $ll-$rl) . $r;
		$o = $ll;

	} else {
		$o = $ll;
	}

	$v = '';
	$carry = 0;

	for ($i = $o-1; $i >= 0; $i--) {
		$d = (int)$l[$i] + (int)$r[$i] + $carry;
		if ($d <= 9) {
			$carry = 0;

		} else {
			$carry = 1;
			$d -= 10;
		}
		$v = (string) $d . $v;
	}

	if ($carry > 0)
		$v = "1" . $v;

	return $v;
}

/**
 * Create a big math comparison function
 * @param string $l
 * @param string $r
 * @return string
 */
function bmcomp($l, $r) {
	if (function_exists('bccomp'))
		return bccomp($l, $r);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_cmp($l, $r));
	}

	$l = strval($l); $r = strval($r);
	$ll = strlen($l); $lr = strlen($r);
	if ($ll != $lr)
		return ($ll > $lr) ? 1 : -1;

	return strcmp($l, $r);
}

/**
 * Create a big math division function
 * @param string $l
 * @param string $r
 * @param int $z
 * @return string
 * @url http://www.icosaedro.it/bigint Inspired by
 */
function bmdiv($l, $r, $z = 0) {
	if (function_exists('bcdiv'))
		return ($z == 0) ? bcdiv($l, $r) : bcmod($l, $r);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(($z == 0) ? gmp_div_q($l, $r) : gmp_mod($l, $r));
	}

	$l = strval($l); $r = strval($r);
	$v = '0';

	while (true) {
		if (bmcomp($l, $r) < 0) {
			break;
		}

		$delta = strlen($l) - strlen($r);
		if ($delta >= 1) {
			$zeroes = str_repeat("0", $delta);
			$r2 = $r . $zeroes;

			if (strcmp($l, $r2) >= 0) {
				$v = bmadd($v, "1" . $zeroes);
				$l = bmsub($l, $r2);

			} else {
				$zeroes = str_repeat("0", $delta - 1);
				$v = bmadd($v, "1" . $zeroes);
				$l = bmsub($l, $r . $zeroes);
			}

		} else {
			$l = bmsub($l, $r);
			$v = bmadd($v, "1");
		}
	}

	return ($z == 0) ? $v : $l;
}

/**
 * Create a big math multiplication function
 * @param string $l
 * @param string $r
 * @return string
 * @url http://www.icosaedro.it/bigint Inspired by
 */
function bmmul($l, $r) {
	if (function_exists('bcmul'))
		return bcmul($l, $r);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_mul($l, $r));
	}

	$l = strval($l); $r = strval($r);

	$v = '0';
	$z = '';

	for( $i = strlen($r)-1; $i >= 0; $i-- ){
		$bd = (int) $r[$i];
		$carry = 0;
		$p = "";
		for( $j = strlen($l)-1; $j >= 0; $j-- ){
			$ad = (int) $l[$j];
			$pd = $ad * $bd + $carry;
			if( $pd <= 9 ){
				$carry = 0;
			} else {
				$carry = (int) ($pd / 10);
				$pd = $pd % 10;
			}
			$p = (string) $pd . $p;
		}
		if( $carry > 0 )
			$p = (string) $carry . $p;
		$p = $p . $z;
		$z .= "0";
		$v = bmadd($v, $p);
	}

	return $v;
}

/**
 * Create a big math modulus function
 * @param string $value
 * @param string $mod
 * @return string
 */
function bmmod( $value, $mod ) {
	if (function_exists('bcmod'))
		return bcmod($value, $mod);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_mod($value, $mod));
	}

	$r = bmdiv($value, $mod, 1);
	return $r;
}

/**
 * Create a big math power function
 * @param string $value
 * @param string $exponent
 * @return string
 */
function bmpow ($value, $exponent) {
	if (function_exists('bcpow'))
		return bcpow($value, $exponent);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_pow($value, $exponent));
	}

	$r = '1';
	while ($exponent) {
		$r = bmmul($r, $value, 100);
		$exponent--;
	}
	return (string)rtrim($r, '0.');
}

/**
 * Create a big math 'powmod' function
 * @param string $value
 * @param string $exponent
 * @param string $mod
 * @return string
 * @url http://php.net/manual/en/function.bcpowmod.php#72704 Borrowed from
 */
function bmpowmod ($value, $exponent, $mod) {
	if (function_exists('bcpowmod'))
		return bcpowmod($value, $exponent, $mod);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_powm($value, $exponent, $mod));
	}

	$r = '';
	while ($exponent != '0') {
		$t = bmmod($exponent, '4096');
		$r = substr("000000000000" . decbin(intval($t)), -12) . $r;
		$exponent = bmdiv($exponent, '4096');
	}

	$r = preg_replace("!^0+!","",$r);

	if ($r == '')
		$r = '0';
	$value = bmmod($value, $mod);
	$erb = strrev($r);
	$q = '1';
	$a[0] = $value;

	for ($i = 1; $i < strlen($erb); $i++) {
		$a[$i] = bmmod( bmmul($a[$i-1], $a[$i-1]), $mod );
	}

	for ($i = 0; $i < strlen($erb); $i++) {
		if ($erb[$i] == "1") {
			$q = bmmod( bmmul($q, $a[$i]), $mod );
		}
	}

	return($q);
}

/**
 * Create a big math subtraction function
 * @param string $l
 * @param string $r
 * @return string
 * @url http://www.icosaedro.it/bigint Inspired by
 */
function bmsub($l, $r) {
	if (function_exists('bcsub'))
		return bcsub($l, $r);

	if ($GLOBALS['profile']['use_gmp']) {
		return gmp_strval(gmp_sub($l, $r));
	}

	$l = strval($l); $r = strval($r);
	$ll = strlen($l); $rl = strlen($r);

	if ($ll < $rl) {
		$l = str_repeat("0", $rl-$ll) . $l;
		$o = $rl;
	} elseif ( $ll > $rl ) {
		$r = str_repeat("0", $ll-$rl) . (string)$r;
		$o = $ll;
	} else {
		$o = $ll;
	}

	if (strcmp($l, $r) >= 0) {
		$sign = '';
	} else {
		$x = $l; $l = $r; $r = $x;
		$sign = '-';
	}

	$v = '';
	$carry = 0;

	for ($i = $o-1; $i >= 0; $i--) {
		$d = ($l[$i] - $r[$i]) - $carry;
		if ($d < 0) {
			$carry = 1;
			$d += 10;
		} else {
			$carry = 0;
		}
		$v = (string) $d . $v;
	}

	return $sign . ltrim($v, '0');
}
?>
