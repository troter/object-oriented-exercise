<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# Author: Balazs Nagy <js@iksz.hu>
# Web: http://js.hu/package/blosxom.php/

# PHPosxom:
# Author: Robert Daeley <robert@celsius1414.com>
# Version 0.7
# Web: http://www.celsius1414.com/phposxom/

# Blosxom:
# Author: Rael Dornfest <rael@oreilly.com>
# Web: http://blosxom.com/

$NAME = "Blosxom.PHP";
$VERSION = "1.0";

$pubdates[] = filemtime(__FILE__);
require_once "./conf.php"; $pubdates[] = filemtime("./conf.php");
require_once "./blosxom.php"; $pubdates[] = filemtime("./blosxom.php");
require_once "./dbahandler.php"; $pubdates[] = filemtime("./dbahandler.php");

readconf();

# -------------------------------------------------------------------
# FILE EXISTENCE CHECKING AND PREP
# In which we establish a URL to this document, make sure all the files
# that are supposed to be available are, figure out what time it is,
# initialize some variables, etc. We also prove the existence of UFOs,
# but that part''s hidden.

$https = array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "ON";
$whoami = "http".($https? "s": "")."://".$_SERVER['SERVER_NAME'];
$sp =& $_SERVER['SERVER_PORT'];
if (($https && $sp != 433) || $sp != 80)
	$whoami .= ":$sp";
$whoami .= $_SERVER['SCRIPT_NAME'];
$lastDoY = null;

if ($conf_language != 'en') {
	if (($str = strchr($conf_language, "-")))
		$locale = substr($conf_language, 0, strlen($str) - 1)."_".strtoupper(substr($str,1));
	$locale .= ".".$conf_charset;
	setlocale(LC_TIME, $locale);
}

$force = false;
function forceonopen()
{
	$GLOBALS["force"] = true;
}

if ($conf_metafile)
	$mfdb =& DBA::singleton($conf_metafile."?onopen=forceopen");

if (is_string($conf_categoriesfile)) {
	$categories = false;
	$fname = rpath($conf_datadir, $conf_categoriesfile);
	if (file_exists($fname)) {
		$data = file($fname);
		foreach ($data as $categ) {
			$matches = explode("=", $categ);
			$categories[trim($matches[0])] = trim($matches[1]);
		}
	}
}

$shows = array();

if (is_array($conf_modules) && count($conf_modules)) {
	if (!isset($conf_moddir) || $conf_moddir == '')
		$conf_moddir = ".";
	foreach($conf_modules as $modval) {
		if (substr($modval, -4) != '.php')
			$modval .= ".php";
		$modfn = rpath($conf_moddir, $modval);
		$moduleThere = file_exists($modfn);
		if (!$moduleThere) {
			echo explainError("nomod", $modval);
			exit;
		} else {
			require_once $modfn;
			$pubdates[] = filemtime($modfn);
		}
	}
}
$confdefs['category_delimiter'] = '/';
readconfdefs();

# -------------------------------------------------------------------
# URL-BASED FILTERS...NOW IN TRANSLUCENT COLORS!

$showFilter = (isset($_GET["show"])
	&& isset($shows[$_GET["show"]]))? $shows[$_GET["show"]]: null;

$flavFilter = (isset($_GET["flav"])
	&& isSaneFilename($_GET["flav"]))? $_GET["flav"]: null;

$categoryFilter = isset($_GET["category"])? $_GET["category"]:
	(isset($_GET["cat"])? $_GET["cat"]: null);

$authorFilter = isset($_GET["author"])? $_GET["author"]:
	(isset($_GET["by"])? $_GET["by"]: null);

$entryFilter = isset($_GET["entry"])? $_GET["entry"]:
	(isset($_SERVER["PATH_INFO"])? $_SERVER["PATH_INFO"]: null);

$dateFilter = isset($_GET["date"])? checkDateFilter($_GET["date"]): null;

$startingEntry = isset($_GET["start"])? $_GET["start"]: 0;

$cmd = isset($_GET["cmd"])? $_GET["cmd"]: false;

$forcefile = strlen($conf_forcefile)? rpath($conf_datadir, $conf_forcefile): false;

$force = $force || $cmd == 'force' || ($forcefile && file_exists($forcefile) && unlink($forcefile));

# Early command check
authUser();

# -------------------------------------------------------------------
# CALL MODULE INITS
if (isset($inits) && is_array($inits) && count($inits)) foreach ($inits as $init)
	if (function_exists($init))
		call_user_func($init);

# -------------------------------------------------------------------
# SHOW FUNCTION EXITS
if ($showFilter && function_exists($showFilter)) {
	call_user_func($showFilter);
	exit;
}

# -------------------------------------------------------------------
# FIRST FILE LOOP

$blogs = array();
$cats = array();
$daysBlogged = array();

checkSources(($force && !$entryFilter) || !isset($mfdb) || !$mfdb);
if ($force && $forcefile && file_exists($forcefile))
	unlink($forcefile);

if (count($daysBlogged)) {
	$daysBlogged = array_keys($daysBlogged);
	rsort($daysBlogged);
}

# -------------------------------------------------------------------
# DETERMINE THE DOCUMENT TYPE AND SEND INITIAL HEADER()

$content_type = "text/html";
if (!$flavFilter)
	$flavFilter = "default";

$flavThere = false;
if (!isset($conf_flavdir) || $conf_flavdir == "")
	$conf_flavdir = ".";

foreach (array($flavFilter, "default") as $flav) {
	$flavfile = $conf_flavdir."/".$flav.'_flav.php';
	if (file_exists($flavfile)) {
		$flavThere = true;
		require $flavfile;
		$pubdates[] = filemtime($flavfile);
		break;
	}
}
if (!$flavThere) {
	echo explainError("noflavs");
	exit;
}

$lastmod = max($pubdates);
$etag = date("r", $lastmod);
header("Last-modified: ".$etag);
$theHeaders = getallheaders();
if (!$force && ((array_key_exists("If-Modified-Since", $theHeaders)
  && $theHeaders["If-Modified-Since"] == $etag)
 || (array_key_exists("If-None-Match", $theHeaders)
  && $theHeaders["If-Modified-Since"] == $etag))) {
	header('HTTP/1.0 304 Not Modified');
	exit;
}

header("Content-Type: ".$content_type);

# -------------------------------------------------------------------
# DETERMINE WHERE TO START AND STOP IN THE UPCOMING STORY ARRAYS

$entries = count($blogs);

if ($conf_entries < $entries)
	$thisManyEntries = $conf_entries;
else
	$thisManyEntries = $entries;

if ($entryFilter) {
	$thisManyEntries = $entries;
	$startingEntry = 0;
}

# -------------------------------------------------------------------
# SECOND FILE LOOP
# In which we format the actual entries and do more filtering.

$stories = "";
usort($blogs, create_function('$a,$b', 'return $b->date - $a->date;'));
reset($blogs);

foreach (array_splice($blogs, $startingEntry, $thisManyEntries) as $blog) {
	# deprecated
	if (!$conf_html_format)
		$blog->contents = parseText($blog->getContents());
	$stories .= storyBlock($blog);
}

# END SECOND FILE LOOP
# -------------------------------------------------------------------
# PATIENT BROWSER NOW BEGINS TO RECEIVE CONTENT

print headBlock($categoryFilter);

print (isset($stories) && strlen($stories))? $stories: explainError("noentries");

print footBlock();

# END OF PRINT, CLEANING UP

DBA::closeall();
?>
