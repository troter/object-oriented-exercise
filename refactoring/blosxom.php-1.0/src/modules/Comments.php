<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: Comments
# DESCRIPTION: Handles talkbacks
# AUTHOR: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
# -------------------------------------------------------------------
# REQUIREMENTS
#   DBA support in PHP (not required)
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# Add a variable declaration line to your flavor file like this (sans quotes):
# "$myComments = countComments($path);"
# in storyBlock, then put it to blogmeta paragraph, in place of the pound sign.
# -------------------------------------------------------------------
# PREP
$confdefs['comments_db'] = "db4:///comments.db";

# Show comments first to last? true: yes, false: last to first
$confdefs['comments_firsttolast'] = false;

# Default number of comments put on a page
$confdefs['comments_maxnum'] = 6;

if ($conf_language == "hu-hu") {
	$msg['comments0'] = "Új hozzászólás";
	$msg['comments1'] = "1 hozzászólás";
	$msg['commentsn'] = "%d hozzászólás";
	$msg['datefmt'] = "%Y. %B %e.";
	$msg['timefmt'] = "G:i T";
} else {
	$msg['comments0'] = "No comments";
	$msg['comments1'] = "1 comment";
	$msg['commentsn'] = "%d comments";
	$msg['datefmt'] = "%Y-%m-%d";
	$msg['timefmt'] = "g:i A T";
}

# -------------------------------------------------------------------
# THE MODULE
# (don't change anything below this unless you know what you're doing.)

function &__openCommentsDB()
{
	global $conf_comments_db;
	return DBA::singleton($conf_comments_db);
}

function countComments($path)
{
	global $msg;

	$comments = getComments($path);
	if (!isset($comments) || $comments === false || $comments == '')
		return $msg['comments0'];
	$c = count($comments);
	return $c == 1? $msg['comments1']: sprintf($msg['commentsn'], $c);
}

function &getComments($path)
{
	global $commentCache;

	if ($path == "last")
		return false;
	if (isset($commentCache[$path]))
		return $commentCache[$path];
	$db =& __openCommentsDB();
	if ($db === false)
		return false;
	$comments = $db->fetch($path);
	if (!is_array($comments))
		return false;
	$date = array_keys($comments);
	sort($date);
	if ($date[0] < 100000) {
		foreach ($comments as $date=>$comm)
			$cmnt[$comm[2]] = $comm;
		$db->replace($path, $cmnt);
		$comments = &$cmnt;
	}
	$commentCache[$path] = $comments;
	return $comments;
}

function addComment($path, $who, $what)
{
	global $cmts, $commentCache;

	$time = time();
	$db =& __openCommentsDB();
	if ($db === false)
		return false;
	$newcmt = array($who, $what, $time);
	$comments = getComments($path);
	if (!is_array($comments))
		$comments = array($time => $newcmt);
	else
		$comments[$time] = $newcmt;
	$commentCache[$path] = $comments;
	$err = $db->replace($path, $comments);
	if (!$err)
		return false;
	__readLastdb();
	$cmts[$time] = array($who, $what, $time, $path);
	return __writeLastdb();
}

function delComment($path, $time)
{
	global $cmts, $commentCache;

	if (!defined("USER"))
		return false;
	$comments = getComments($path);
	if (!is_array($comments) || !isset($comments[$time]))
		return false;
	unset($comments[$time]);
	$db =& __openCommentsDB();
	if ($db === false)
		return false;
	$err = $db->replace($path, $comments);
	if (!$err)
		return false;
	
	__readLastdb();
	if (isset($cmts[$time])) {
		unset($cmts[$time]);
		__writeLastdb();
	}
	return true;
}

function lastComments()
{
	global $conf_datadir, $conf_comments_db;
	global $conf_comments_db;
	global $force, $conf_comments_maxnum;
	global $cmts, $mfdb;

	if ($force) {
		$cmts = array();
		$db =& __openCommentsDB();
		if ($db === false)
			return false;
		$key = $db->first();
		while ($key !== false) {
			$path = $key;
			$key = $db->next();
			if ($path == "last")
				continue;
			$comments = $db->fetch($path);
			if (!is_array($comments))
				continue;
			foreach (array_values($comments) as $cmt) {
				$time =& $cmt[2];
				$cmts[$time] = $cmt;
				$cmts[$time][3] = $path;
			}
		}
		__writeLastdb();
	} else
		__readLastdb();

	if ($mfdb && is_array($cmts)) {
		$mfCache = array();
		foreach (array_keys($cmts) as $date) {
			$where = $cmts[$date][3];
			if (array_key_exists($where, $mfCache)) {
				$cmts[$date][4] = $mfCache[$where];
			} else {
				$mdata = $mfdb->fetch($where);
				$cmts[$date][4] = $mfCache[$where] = trim($mdata[2]);
			}
		}
	}
	return $cmts;
}

function __sortLastdb($a, $b)
{
	return $b[2] - $a[2];
}

function __optimizeLastdb()
{
	global $conf_comments_maxnum, $cmts;
	if (!is_array($cmts) || !count($cmts))
		return false;
	usort($cmts, "__sortLastdb");

	if (count($cmts) > $conf_comments_maxnum)
		$cmts = array_slice($cmts, 0, $conf_comments_maxnum);
	return true;
}

function __writeLastdb()
{
	global $cmts, $conf_comments_db;

	if (__optimizeLastdb() === false)
		return false;
	$db =& __openCommentsDB();
	if ($db === false)
		return false;

	$db->replace("last", $cmts);
	$db->close();
	return true;
}

function __readLastdb()
{
	global $cmts;

	$db =& __openCommentsDB();
	if (!$db)
		return false;
	$cmts = $db->fetch("last");
	return __optimizeLastdb();
}
?>
