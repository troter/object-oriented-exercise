<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: Trackback
# DESCRIPTION: Tracks blog entries using referrer
# AUTHOR: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# -------------------------------------------------------------------
# PREP

# Database path
$confdefs['trackback'] = "db4:///trackback.db";

function trackbackReferrer($path)
{
	global $conf_trackback, $whoami;

	fixTrackbacks();
	if (!isset($_SERVER['HTTP_REFERER']))
		return true;
	$ref = $_SERVER['HTTP_REFERER'];
	$wa = dirname($whoami);
	if ($ref == '' || !strncmp($ref, $wa, strlen($wa)))
		return true;
	$db =& DBA::singleton($conf_trackback);
	if ($db === false)
		return false;

	$tb = $db->fetch($path);
	if ($tb) {
		$tbp = unserialize($tb);
		if (array_key_exists($ref, $tbp))
			$tbp[$ref] ++;
		else
			$tbp[$ref] = 1;
	} else
		$tbp = array($ref => 1);
	$db->replace($path, $tbp);
	$db->sync();
	$db->close();
	return true;
}

function getTrackbacks($path)
{
	global $conf_trackback;

	$db =& DBA::singleton($conf_trackback."?mode=r");
	if ($db === false)
		return false;

	$tb = $db->fetch($path);
	$db->close();
	if (!$tb)
		return false;

	return $tb;
}

function fixTrackbacks()
{
	global $conf_trackback, $whoami;

	$db =& DBA::singleton($conf_trackback);
	if ($db === false)
		return false;

	$wa = dirname($whoami);
	$tb = $db->first();
	do {
		$dirty = false;
		$tbp = $db->fetch($tb);
		if ($tbp === null)
			continue;
		foreach ($tbp as $url=>$count) {
			if (!strncmp($url, $wa, strlen($wa))) {
				unset($tbp[$url]);
				$dirty = true;
			}
		}
		if ($dirty)
			$db->replace($tb, $tbp);
	} while (($tb = $db->next()));
	$db->close();
}
?>
