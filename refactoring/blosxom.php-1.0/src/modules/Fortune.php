<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: Fortune
# DESCRIPTION: Adds random fortune cookie
# AUTHOR: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# Add a variable declaration line to your flavor file like this (sans quotes):
# "$myFortune = getFortune();" in headBlock.
# -------------------------------------------------------------------
# PREP

# Database path
$confdefs['fortune'] = "fortunes.lst";

function getFortune()
{
	global $conf_datadir, $conf_fortune;

	if (!is_string($conf_fortune))
		return "";
	$fname = rpath($conf_datadir, $conf_fortune);
	if (!file_exists($fname) || !($f = @file($fname)))
		return "";
	$line = mt_rand(0, count($f)-1);
	return trim($f[$line]);
}
?>
