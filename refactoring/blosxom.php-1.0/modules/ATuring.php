<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: ATuring
# DESCRIPTION: Generates an Anti Turing Test to check commenter is
#   a human being
# MAINTAINER: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
# -------------------------------------------------------------------
# REQUIREMENTS
#   GD support in PHP
#   DBA support in PHP (not required)
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# Add a variable declaration line to your flavor file like this (sans quotes):
# "$turingData = getATuring();"
# which is an array contains image URL and challenge data.
# You can insert it to the form like
#   <form...>...
#     <input type="hidden" name="challenge" value="$turingData[1]" />
#     Read this: <img src="$turingData[0]" />, write here:
#     <input type="text" name="response" />
#   ...</form>
#
# You can check validity with (sans quotes)
# "$valid_p = validateATuring($challenge, $response)"
# -------------------------------------------------------------------
# PREFERENCES

# How Anti Turing should work?  It can be "session" or "database".
# In session mode, challenge-response values are stored in session variables,
# in database mode, they stored in the Anti Turing database.
$confdefs['aturing_mode'] = "session";

# Where are Anti Turing database kept?  The filename can be relative to $conf_datadir.
# In session mode it is a no-op.
$confdefs['aturing_db'] = "db4:///aturing.db";

# Expire time of Challenge/Responses [in seconds]
# In session mode it is a no-op.
$confdefs['aturing_expire'] = 1200; # 20 minutes

# -------------------------------------------------------------------
# THE MODULE
# (don't change anything below this unless you know what you're doing.)

$shows["aturing"] = "ATshow";
$inits[] = "ATinit";

$__gcdone = false;

function ATinit()
{
	global $conf_aturing_mode;
	if ($conf_aturing_mode == "session")
		session_start();
}

function ATshow()
{

	$c = 0;
	if (array_key_exists("challenge", $_GET))
		$c = $_GET['challenge'];
	$data = $c? __fetchATuring($c): false;
	__closeATuringdb();
	if (!$data) {
		header("Content-Type: text/plain");
		print "Challenge ($c) not found\n";
		return;
	} elseif (is_array($data))
		$data = $data[0];
	header("Content-Type: image/png");

	$xl = 76;
	$yl = 21;
	$im = imagecreate($xl, $yl);
	$wh = imagecolorallocate($im, 0xee, 0xee, 0xdd);
	$bl = imagecolorallocate($im, 0, 0, 0);
	$gr = imagecolorallocate($im, 0x99, 0x99, 0x99);
	$bu = imagecolorallocate($im, 0x99, 0xaa, 0xaa);

	for ($y = 0; $y < $yl; $y += 4) {
		imageline($im, 0, $y, $xl, $y, $gr);
	}

	for ($x = 0; $x < ($xl - $yl/2); $x += 8) {
		imageline($im, $x, 0, $x + $yl/2, $yl, $bu);
		imageline($im, $x+$yl/2, 0, $x, $yl, $bu);
	}
	imagestring($im, 5, 2, 3, $data, $bl);
	imagepng($im);
	imagedestroy($im);
	exit;
}

function getATuring($challenge = 0, $response = 0)
{
	global $whoami, $__atdb, $conf_aturing_mode;
	__openATuringdb();
	if (!$challenge) {
		switch ($conf_aturing_mode) {
		case "session":
			if (isset($_SESSION["aturing_last"]))
				$challenge = $_SESSION["aturing_last"] + 1;
			if (!$challenge || $challenge >= 1073741823)
				$challenge = 1;
			break;
		case "database":
			if (!$__atdb->fetch($challenge)) {
				$challenge = intval($__atdb->fetch("last"))+1;
				if ($challenge >= 1073741823)
					$challenge = 1;
			}
		}
	} else
		$lastresponse = __fetchATuring($challenge);
	if (isset($lastresponse) && is_integer($lastresponse) && $response == $lastresponse)
		$rand = $lastresponse;
	elseif (isset($lastresponse) && $response == $lastresponse[0])
		$rand = $response;
	else
		$rand = mt_rand(10000000, 99999999);
	$expr = time();
	switch ($conf_aturing_mode) {
	case "session":
		$_SESSION["aturing"][$challenge] = $rand;
		$_SESSION["aturing_last"] = $challenge;
		break;
	case "database":
		dba_replace("$challenge", "$rand,$expr", $__atdb);
		dba_replace("last", "$challenge", $__atdb);
		dba_sync($__atdb);
		__closeATuringdb();
	}
	return array("$whoami?show=aturing&challenge=$challenge", $challenge);
}

function validateATuring($challenge, $response)
{
	global $conf_aturing_expire;

	__openATuringdb();
	$data = __fetchATuring($challenge);
	__closeATuringdb();
	if (is_array($data))
		return ($data[0] == $response && ($data[1] + $conf_aturing_expire) >= time());
	return $data == $response;    
}

function __fetchATuring($challenge)
{
	global $__atdb, $conf_aturing_mode;
	if ($conf_aturing_mode == "session")
		return $_SESSION["aturing"][$challenge];
	$challenge = $__atdb->fetch($challenge);
	if (!$challenge)
		return false;
	return explode(",", $challenge, $__atdb);
}

function __openATuringdb()
{
	global $conf_aturing_mode, $conf_aturing_db, $__atdb;
	if ($conf_aturing_mode != "database")
		return;
	if (!$__atdb)
		$__atdb = DBA::singleton($conf_aturing_db);
}

function __closeATuringdb()
{
	global $conf_aturing_mode, $__atdb;
	if ($conf_aturing_mode != "database" || !$__atdb)
		return;
	__gcATuringdb();
	dba_close($__atdb);
	$__atdb = null;
}

function __gcATuringdb()
{
	global $__atdb, $__gcdone, $conf_aturing_expire;
	if ($__gcdone)
		return;
	$__gcdone = true;
	$now = time() - $conf_aturing_expire;
	if ($key = $__atdb->first()) do {
		if ($key == "last")
			continue;
		$data = __fetchATuring($key);
		if ($data && $data[1] < $now)
			$__atdb->delete($key);
	} while ($key = $__atdb->next());
}
?>
