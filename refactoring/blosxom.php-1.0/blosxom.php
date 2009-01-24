<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# HELPER FUNCTIONS

# Returns real path
function rpath($wd, $path)
{
	if (is_string($path) && strlen($path)) {
		if ($path[0] != "/")
			$path = $wd."/".$path;
	} else	
		$path = $wd;
	return realpath($path);
}

# Reads config
function readconf()
{
	global $conf;

	if (isset($conf) && is_array($conf) && count($conf))
		foreach (array_keys($conf) as $key)
			if (isSaneFilename($key))
				$GLOBALS["conf_".$key] = &$conf[$key];
}

# Reads config defaults
function readconfdefs()
{
	global $confdefs;

	if (isset($confdefs) && is_array($confdefs) && count($confdefs))
		foreach (array_keys($confdefs) as $key)
			if (isSaneFilename($key) && !array_key_exists("conf_".$key, $GLOBALS))
				$GLOBALS["conf_".$key] = &$confdefs[$key];
}

# Checks whether file name is valid
function isSaneFilename($txt)
{
	return !strcspn($txt, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_-.");
}

function parseText($txt)
{
	$txt = str_replace("\n\n", "</p><crlf><p class=\"blog\">", $txt);
	$txt = preg_replace("'>\s*\n\s*<'", "><crlf><", $txt);
	$txt = str_replace("\n", "<br />", $txt);
	$txt = str_replace("<crlf>", "\n", $txt);
	return "<p>$txt</p>";
}

function w3cdate($time)
{
	$date = date("Y-m-d\TH:i:sO", $time);
	return substr($date, 0, 22).":".substr($date, 22, 2);
}

# Generates GET URL from GET variables, and add/del arrays
function genurl($add = false, $del = array("cmd"))
{
	global $whoami;

	$arr = $_GET;
	if (is_array($del)) foreach ($del as $d)
		if (isset($arr[$d]))
			unset ($arr[$d]);
	if (is_array($add)) foreach ($add as $a=>$key)
		$arr[$a] = $key;
	$url = array();
	foreach ($arr as $key=>$val)
		$url[] = "$key=".rawurlencode($val);
	return "$whoami?".implode("&amp;", $url);
}

$GLOBALS['__catcache'] = array();

# Returns category name
function displaycategory($cat, $href=false)
{
	global $__catcache, $categories, $conf_category_delimiter;

	$ccindex = $cat.":".$href;
	if (@array_key_exists($ccindex, $__catcache))
		return $__catcache[$ccindex];
	$cats = explode("/", $cat);
	$fullcat = "";
	for ($i = 0; $i < count($cats); ++$i) {
		$fullcat .= $cats[$i];
		if (@array_key_exists($fullcat, $categories))
			$cats[$i] = $categories[$fullcat];
		if ($href)
			$cats[$i] = "<a href=\"$href".$fullcat."\">".$cats[$i]."</a>";
		$fullcat .= "/";
	}
	$ret = implode($conf_category_delimiter, $cats);
	$__catcache[$ccindex] = $ret;
	return $ret;
}

class Blog {
	var $src;
	var $path;
	var $fp;
	var $title;
	var $date;
	var $mod;
	var $author;
	var $link;
	var $alias;
	var $valid;
	var $lead;
	var $contents;
	var $cat;
	var $displaycat;

	function Blog($key, $src=false)
	{
		global $conf_datadir, $conf_sources;
		global $blogs, $mfdb, $pubdates, $force;
		global $daysBlogged, $dateFilter, $dateFilterType;
		global $authorFilter, $entryFilter;

		$this->valid = false;
		$this->path = $key;


		$indb = $mdata = false;
		if (is_object($mfdb))
			$mdata = $mfdb->fetch($key);
		if (is_array($mdata)) {
			if ($src !== false && $mdata[1] != $src) {
				if (!file_exists(rpath($conf_datadir,
						$conf_sources[$mdata[1]]['path'].$key)))
					$mfdb->delete($key);
				else {
					# concurring blog entries: drop it
					error_log("Concurring blog entries.  New: $fp, orig: $origfp");
					return;
				}
			} else {
				$indb = true;
				$date = $mdata[0];
				$src = $mdata[1];
				$title = $mdata[2];
				$fd =& $conf_sources[$src]['fp'];
				$fp = $fd.$key;
			}
		}
		if (!isset($fp)) {
			if (!$src) {
				foreach (array_keys($conf_sources) as $s) {
					$fd =& $conf_sources[$s]['fp'];
					$fp = $fd.$key;
					if (file_exists($fp)) {
						$src = $s;
						break;
					}
				}
			} else {
				$fd =& $conf_sources[$src]['fp'];
				$fp = $fd.$key;
			}
		}
		if (!strlen($fp) || !file_exists($fp)) {
			if ($mfdb)
				$mfdb->delete($key);
			return false;
		}
		if (is_link($fp)) {
			$rfp = realpath($fp);
			if (file_exists($rfp) && !strncmp($fd, $rfp, strlen($fd))) {
				$nkey = substr($rfp, strlen($fd));
				if ($indb) {
					$mfdb->replace($nkey, $mdata);
					$mfdb->delete($key);
				}
			}
			return false;
		}

		$mod = filemtime($fp);

		$this->date = ($indb && $date)? $date: $mod;
		$this->mod = $mod;
		$this->src = $src;
		$this->title = isset($title)? trim($title): "";
		$this->fp = $fp;
		$this->author = $conf_sources[$src]["author"];
		$this->link = $conf_sources[$src]["link"];
		$this->alias = $src;

		$daysBlogged[date("Ymd", $this->date)] = 1;
		if (($dateFilter && date($dateFilterType, $this->date) != $dateFilter)
			|| ($entryFilter && $key != $entryFilter)
			|| ($authorFilter && $src != $authorFilter))
			return;

		# Everything is OK, mark it as valid data
		$this->valid = true;

		if ($force || !$indb) {
			$fh = fopen($fp, "r");
			$this->title = fgets($fh, 1024);
			fclose($fh);
			if ($mfdb)
				$mfdb->replace($key, array($this->date,$src,$this->title));
		}
		$pubdates[] = $mod;
		$blogs[$this->path] = $this;
	}

	function isValid() { return $this && $this->valid; }
	function getContents($short = false)
	{
		if (!$this || !$this->valid)
			return;
		if (!$this->contents) {
			if (!file_exists($this->fp))
				return false;
			$contents = file($this->fp);
			$this->title = array_shift($contents);
			$this->format = array_shift($contents);
			$this->short = $contents[0];
			$this->contents = join("", $contents);
		}
		return $short? $this->short: $this->contents;
	}
	function getCategory()
	{
		if (!$this || !$this->valid)
			return;
		if (!$this->cat)
			$this->cat = substr($this->path, 1, strrpos($this->path, '/')-1);
		return $this->cat;
	}
}

function checkDir($src, $dir = "", $level = 1)
{
	global $conf_depth, $conf_sources;
	global $cats, $categoryFilter;

	if ($conf_depth && $level > $conf_depth)
		return;
	$cats[] = $dir;
	$inCatFilter = !$categoryFilter || !strncmp($dir, $categoryFilter, strlen($categoryFilter));
	$datadir = rpath($conf_sources[$src]['fp'], $dir);
	$dh = opendir($datadir);
	if (!is_resource($dh))
		return;
	if ($level > 1)
		$dir .= "/";
	$datadir .= "/";
	while (($fname = readdir($dh)) !== false) {
		if ($fname[0] == '.')
			continue;
		$path = $dir.$fname;
		$opath = false;
		$fulln = $datadir.$fname;
		if (is_dir($fulln))
			checkDir($src, $path, $level+1);
		elseif (substr($fname, -4) == '.txt' && $inCatFilter)
			new Blog("/".$path, $src);
	}
}

function checkSources($force)
{
	global $conf_datadir, $conf_sources;
	global $mfdb, $cats, $categoryFilter;
	global $entryFilter, $blogs, $whoami;

	if (!count($conf_sources))
		return;
	foreach (array_keys($conf_sources) as $src)
		$conf_sources[$src]['fp'] = rpath($conf_datadir, $conf_sources[$src]['path']);
	if ($force) {
		foreach (array_keys($conf_sources) as $src)
			checkDir($src);
	} elseif ($key = $mfdb->first()) {
		$cats[] = "";
		do {
			if ($key[0] != "/")
				$key = "/$key";
			$dir = substr($key, 1, strrpos($key, "/")-1);
			$cats[] = $dir;
			if (!$categoryFilter || !strncmp($dir, $categoryFilter, strlen($categoryFilter)))
				new Blog($key);
		} while ($key = $mfdb->next());
		$cats = array_unique($cats);
		sort($cats);
	}
	if ($entryFilter && !isset($blogs[$entryFilter])) {
		foreach (array_keys($conf_sources) as $s) {
			$fd =& $conf_sources[$s]['fp'];
			$fp = realpath($fd.$entryFilter);
			if (file_exists($fp) && !strncmp($fd, $fp, strlen($fd))) {
				$nkey = substr($fp, strlen($fd));
				header("Location: ".genurl(
					array("entry"=>$nkey),
					array("cmd")
				));
				exit;
			}
		}
	}
}

function checkDateFilter($filter)
{
	global $dateFilterType;
	$dateFilterLen = strlen($filter);
	if ($dateFilterLen == 4 or $dateFilterLen == 6 or $dateFilterLen == 8) {
		$dateFilterType = substr("Ymd", 0, ($dateFilterLen-2)/2);
		return $filter;
	} else {
		if ($dateFilterLen < 4)
			print explainError("shortdate");
		elseif ($dateFilterLen > 8)
			print explainError("longdate");
		elseif ($dateFilterLen == 5 or $dateFilterLen == 7 or is_numeric($dateFilter) == false)
			print explainError("wrongdate");
		return false;
	}
}

/**
 * authenticates user
 * This is a basic authentication method for authenticating source
 * writers.
 */

function authUser()
{
	global $NAME, $conf_sources, $cmd;

	$src =& $_SERVER["PHP_AUTH_USER"];
	$pass =& $_SERVER["PHP_AUTH_PW"];

	if ((!isset($src) && $cmd == "login")
	 || (isset($src) && $cmd == "logout")) {
		echo explainError("autherr");
		exit;
	}

	if ($cmd == "login" && (!isset($conf_sources[$src])
	 || !isset($conf_sources[$src]['pass'])
	 || $pass != $conf_sources[$src]['pass'])) {
		echo explainError("autherr");
		exit;
	}
	if (isset($src))
		define("USER", $src);
}

function explainError($x, $p = false)
{
	switch ($x) {
	case "autherr":
		header('WWW-Authenticate: Basic realm="'.$NAME.'"');
		header('HTTP/1.0 401 Unauthorized');
		return <<<End
<html>
<head>
<title>Authentication Error</title>
</head>
<body><h1>Authentication Error</h1>

<p>You have to log in to access this page.</p>
</body>
</html>
End;
	case "nomod":
		return <<<End
<p class="error"><strong>Cannot find module $p.</strong>  Please check filenames.</p>
End;
	case "noflavs":
		return <<<End
<p class="error"><strong>Warning: Couldn&apos;t find a flavour file.</strong>
It means that the blog is in an inconsistent stage.  Please alert the blog
maintainer.</p>
End;
	case "permissions":
		return <<<End
<p class="error"><strong>Warning: There&apos;s something wrong with the $ff
folder&apos;s permissions.</strong> Please make sure it is readable by the www
user and not just your selfish self.</p>
End;
	case "shortdate":
		return <<<End
<p class="error"><strong>The date you entered as a filter seems to be too short
to work.</strong> Either that or this server has gone back in time. Please
check the date and try again. In the case of the latter, the Temporal
Maintenance Agency will have had knocked on your door by now.</p>
End;
	case "longdate":
		return <<<End
<p class="error"><strong>Either the date you entered as a filter is too long to
work, or we&apos;re having a Y10K error.</strong> If it&apos;s not the year
10,000, please check the date and try again. If it is, tell Hackblop-4 from
Pod Alpha -22-Pi we said hey.</p>
End;
	case "wrongdate":
		return <<<End
<p class="error"><strong>Something is screwy with the date you entered.</strong>
It has the wrong number of digits, like that famous sideshow attraction
Eleven-Fingered Elvira, or contains non-numeric characters. Speaking of
characters, you should check the date and try again.</p>
End;
	case "noentries":
		return <<<End
<p class="error"><strong>No Results.</strong> The combination of filters for
this page has resulted in no entries. Try another combination of dates,
categories, and authors.</p>
End;
	}
}
?>
