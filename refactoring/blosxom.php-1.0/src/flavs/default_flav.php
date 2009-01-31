<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# DEFAULT FLAVOR FILE
# The purpose is to allow you to customize the look and feel of
# your PHPosxom blog without the necessity of using flav=foo in
# the URL or editing the main script.
# ----------------------------------------------------------------
# USAGE
# There are three functions below that form the head, story, and
# foot blocks on a blog page.
# ----------------------------------------------------------------
# $content_type lets the browser know what kind of content is being
# served. You''ll probably want to leave this alone.

$content_type ='text/html';

reset($conf_sources);
foreach (array_keys($conf_sources) as $src) {
	if (isset($conf_sources[$src]["author"])) {
		$cHolder = $conf_sources[$src]["author"];
		break;
	}
}

if ($conf_language == "hu-hu") {
	if (!isset($cHolder))
		$cHolder = "a szerzők";
	$msg['archives'] = "Archívum";
	$msg['atomfeed'] = "ATOM feed";
	$msg['blogroll'] = "Blogroll";
	$msg['calendar'] = "Naptár";
	$msg['cannotpost'] = "Nem lehet hozzászólást küldeni!";
	$msg['categories'] = "Kategóriák";
	$msg['copyright'] = "Copyright&copy; $cHolder az egész tartalomra";
	$msg['datefmt'] = "%Y. %B %e.";
	$msg['delentry'] = "töröl";
	$msg['lastcomments'] = "Utolsó hozzászólások";
	$msg['main'] = "Főoldal";
	$msg['next'] = "következő &raquo;";
	$msg['prev'] = "&laquo; előző";
	$msg['preview'] = "Előnézet";
	$msg['readthis'] = "Olvasd el:";
	$msg['rss1feed'] = "RSS1 feed";
	$msg['rss2feed'] = "RSS2 feed";
	$msg['send'] = "Küld";
	$msg['timefmt'] = "G:i T";
	$msg['trackback'] = "Honnan néztek:";
	$msg['writehere'] = ", írd ide:";
	$msg['wrongresp'] = "Hibás válasz!";
	$msg['yourname'] = "Neved";
	$msg['youropinion'] = "Írd meg a véleményed:";
} else {
	if (!isset($cHolder))
		$cHolder = "the authors";
	$msg['archives'] = "Archive";
	$msg['atomfeed'] = "ATOM feed";
	$msg['blogroll'] = "Blogroll";
	$msg['calendar'] = "Calendar";
	$msg['cannotpost'] = "Comments cannot be posted.";
	$msg['categories'] = "Categories";
	$msg['copyright'] = "All contents copyright&copy; by $cHolder";
	$msg['datefmt'] = "%Y-%m-%d";
	$msg['delentry'] = "delete";
	$msg['lastcomments'] = "Last Comments";
	$msg['main'] = "Main";
	$msg['next'] = "Next &raquo;";
	$msg['prev'] = "&laquo; Previous";
	$msg['preview'] = "Preview";
	$msg['readthis'] = "Read this:";
	$msg['rss1feed'] = "RSS1 feed";
	$msg['rss2feed'] = "RSS2 feed";
	$msg['send'] = "Send";
	$msg['timefmt'] = "g:i A T";
	$msg['trackback'] = "Track backs:";
	$msg['writehere'] = ", write here:";
	$msg['wrongresp'] = "You wrote a wrong response.";
	$msg['yourname'] = "Your name:";
	$msg['youropinion'] = "Write your opinion:";
}

# ---------------------------------------------------------------
# headBlock is the HTML that forms the top of a PHPosxom page.

function headBlock($cat)
{
	global $conf_title, $conf_description, $conf_language, $conf_charset;
	global $conf_cssURL, $whoami, $NAME, $VERSION;
	global $category;

	$ret = "<!DOCTYPE html
  PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$conf_language\" lang=\"$conf_language\">
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=$conf_charset\" />
<style type=\"text/css\">@import \"$conf_cssURL\";</style>
<meta name=\"generator\" content=\"$NAME $VERSION\" />
<link rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS\" href=\"$whoami?flav=rss2\" />
<link rel=\"alternate\" type=\"application/atom+xml\" title=\"ATOM\" href=\"$whoami?flav=atom\" />
<title>$conf_title: $conf_description</title>
</head>
<body>
<div id=\"wrapper\">
<div id=\"page\">
<div id=\"header\">$conf_title</div>
<div id=\"body\">
<div id=\"main\">
<p class=\"descr\">$conf_description</p>\n";
	if (function_exists("getFortune"))
		$ret .= '<p class="fortune">'.getFortune()."</p>\n";
	if ($cat)
		$ret .= "<h3>".displaycategory($cat, $whoami."?category=")."</h3>\n";
	return $ret;
}

# ---------------------------------------------------------------
# storyBlock is the HTML that constructs an individual blog entry.
# You can add additional lines by following the $story[] format below.

function storyBlock($blog)
{
	global $whoami, $entryFilter, $lastDoY, $msg;

	# If just one entry is shown, one can post and/or read comments

	$tbs = "";
	$path = $blog->path;
	$comments = "";
	if ($entryFilter) {
		if (function_exists("trackbackReferrer"))
			trackbackReferrer($path);
		if (function_exists("getTrackbacks")) {
			$tb = getTrackbacks($path);
			if ($tb) {
				$tbs .= "<h4 id=\"trackbacks\">".$msg['trackback']."</h4>\n"
					."<ul class=\"blogTrackbacks\">\n";
				foreach ($tb as $url=>$count)
					$tbs .= "<li><a href=\"$url\">$url</a> ($count)</li>\n";
				$tbs .= "</ul>\n";
			}
		}
		$comments = handleComments($blog);
		if ($comments != '')
			$comments = "<div class=\"blogComments\">\n".$comments."</div>\n";
	}

	$thisDoY = date("Ymd", $blog->date);
	$displaydate = strftime($msg['datefmt'], $blog->date);
	$time = date($msg['timefmt'], $blog->date);

	if (!$lastDoY or ($thisDoY-$lastDoY < 0))
		$displaydate = "<p class=\"blogdate\">$displaydate</p>\n";
	else
		$displaydate = "";
	$lastDoY = $thisDoY;
	if (function_exists("countComments"))
		$commentCount = countComments($path);
	else
		$commentCount = "#";
	$author = $blog->author;
	$alink = $blog->link;
	if (isset($alink) && strlen($alink)) {
		if (!preg_match("/^((ht|f)tp:\/\/|mailto:)/", $alink))
			$alink = "mailto:".$alink;
		$author = "<a href=\"$alink\">$author</a>";
	}

	return "$displaydate<h4>$blog->title</h4>
<div class=\"blog\">\n".$blog->getContents()
	."<p class=\"blogmeta\">$author @ $time [ <a href=\"${whoami}?category="
	.$blog->getCategory()."\">".displaycategory($blog->getCategory())."</a>"
	." | <a href=\"".$whoami.$path."\">$commentCount</a> ]</p>
</div>
$tbs$comments
";
}

function mklink($path, $offset, $val=false)
{
	global $whoami, $entryFilter;
	if ($val === false)
		$val = $offset + 1;
	return "<a href=\""
		.htmlspecialchars($whoami."$entryFilter?offset=$offset")
		."\">$val</a>";
}

function handleComments(&$blog)
{
	global $defwho, $defwhat, $valid, $conf_comments_maxnum;
	global $conf_comments_firsttolast, $msg, $cmd;

	$defwho = '';
	$defwhat = '';

	if (!function_exists("getComments"))
		return '';
	if (defined('USER') && $cmd == 'del') {
		$when = intval($_GET['when']);
		delComment($blog->path, $when);
	}

	if (isset($_COOKIE['who'])) {
		$defwho = $_COOKIE['who'];
		if (!get_magic_quotes_gpc())
			$defwho = addslashes($defwho);
	}

	$ret = '';
	if (isset($_POST['addcomment'])) {
		if (function_exists("validateATuring"))
			$valid = validateATuring($_POST['challenge'], $_POST['response']);
		else
			$valid = null;
		$defwho = $_POST['who'];
		$defwhat = $_POST['what'];
		$defwhat = strip_tags($defwhat, "<a><b><i><em><strong><code>");
		if (get_magic_quotes_gpc()) {
			$defwho = stripslashes($defwho);
			$defwhat = stripslashes($defwhat);
		}
		if ((!isset($_POST['preview']) || !strlen($_POST['preview'])) && $valid === true) {
			if (function_exists("addComment")) {
				addComment($blog->path, $defwho, $defwhat);
				$defwhat = "";
			} else
				$ret .= "<p class=\"error\">".$msg['cannotpost']."</p>";
		}
		setcookie("who", $_POST['who'], time()+7776000);
	}

	$comments = getComments($blog->path);
	if (!is_array($comments) || !count($comments))
		return $ret;
	if ($conf_comments_firsttolast)
		ksort($comments);
	else
		krsort($comments);
	$l =& $conf_comments_maxnum;
	if ($l && $l < count($comments)) {
		$len = count($comments);
		$navi = array();
		$o = isset($_GET['offset'])? intval($_GET['offset']): 0;
		$comments = array_slice($comments, $o*$l, $l);
		$last = intval(($len-1)/$l);
		$links = false;
		if ($o > 0)
			$links[] = mklink($blog->path, $o - 1, $msg['prev']);
		for ($i = 0; $i < 7; ++$i) {
			if ((!$i && $o > 2) || ($i == 6 && $o < ($last-2))) {
				$links[] = "&hellip;";
				continue;
			}
			$num = $o + $i - 3;
			if ($i == 3)
				$links[] = $o+1;
			elseif ($num >= 0 && $num <= $last)
				$links[] = mklink($blog->path, $o+$i-3);
		}
		if ($o < $last)
			$links[] = mklink($blog->path,  $o+1, $msg['next']);
		$ret .= "<p class=\"navi\">".join(" | ", $links)."</p>\n";
	}
	if (is_array($comments) && count($comments)) {
		foreach ($comments as $comment)
			$ret .= commentBlock($blog, $comment);
	}
	return $ret;
}

function commentBlock(&$blog, $comment, $short=false)
{
	global $whoami, $msg;

	switch (count($comment)) {
	case 5:
		$title = $comment[4];
	case 4:
		$path = $comment[3];
	case 3:
		$when = $comment[2];
	case 2:
		$what = $comment[1];
	case 1:
		$who = $comment[0];
	}
	$date = strftime($msg['datefmt'],$when);
	$time = date($msg['timefmt'], $when);
	if ($short) {
		$after = false;
		if (strlen($what) > 80) {
			$after = true;
			$what = substr($what, 0, 80);
			$p = strrpos($what, " ");
			if ($p !== false)
				$what = substr($what, 0, $p);
		}
		$p = strpos($what, "\n");
		if ($p !== false) {
			$after = true;
			$what = substr($what, 0, $p);
		}

		if ($after)
			$what = $what."&hellip;";
	}
	$what = parseText($what);

	$meta = "$who @ $date $time";
	if (isset($path))
		$meta = "$meta [<a href=\"$whoami$path\">$title</a>]";
	if (defined("USER") && $blog->src == USER)
		$meta .= " <a href=\"$whoami$blog->path?cmd=del&when=$when\">"
			.$msg['delentry']."</a>";

	$ret = <<<End
<div class="comment">
$what
<p class="blogmeta">$meta</p>
</div>

End;
	return $ret;
}

# ---------------------------------------------------------------
# footBlock is the HTML that forms the bottom of the page.

function footBlock() {
	global $whoami, $defwho, $defwhat, $entryFilter, $conf_comments_firsttolast,
		$conf_comments_maxnum, $conf_entries, $startingEntry, $entries;
	global $categoryFilter, $NAME, $VERSION, $valid, $msg;

	$ret = "";
	if ($entryFilter) {
		$myurl = "$entryFilter";
		if (isset($conf_comments_firsttolast)
		 && $conf_comments_firsttolast === true
		 && $conf_comments_maxnum
		 && ($len = count(getComments($entryFilter))) > $conf_comments_maxnum)
			$myurl .= "?offset=".intval($len/$conf_comments_maxnum);
		$ret .= "<h4 id=\"post\">$msg[youropinion]</h4>\n"
			."<form method=\"post\" action=\"$whoami$myurl#post\">";
		if (function_exists("getATuring")) {
			$c =& $_REQUEST['challenge'];
			$r =& $_REQUEST['response'];
			$data = getATuring($c, $r);
			if ($valid === false)
				$ret .= "\n<p class=\"error\">".$msg['wrongresp']."</p>\n";
			$ret .= "\n".$msg['readthis']." <img src=\"$data[0]\" />"
				."<input type=\"hidden\" name=\"challenge\" value=\"$data[1]\" />"
				.$msg['writehere']." <input type=\"text\" name=\"response\" "
				.(isset($r)? ('value="'.$r.'"'):'')
				."/><br />\n";
		}

		$ret .= <<<End
$msg[yourname]: <input type="text" name="who" value="$defwho" />
$msg[preview]: <input type="checkbox" name="preview" checked="checked" />
<input type="submit" name="addcomment" value="$msg[send]" /><br />
<textarea name="what" wrap="soft" cols="50" rows="5">$defwhat</textarea>
</form>

End;
		if (is_string($defwhat) and strlen($defwhat)) {
			$blog = null;
			$ret .= "<div class=\"blogComments\">\n"
				."<h4>".$msg['preview'].":</h4>\n"
				. commentBlock($blog, array(stripslashes($defwho), stripslashes($defwhat), time()))
				. "</div>\n";
			$defwhat = stripslashes($defwhat);
		}
	} else {
		$navi = array();
		if (($startingEntry+$conf_entries) < $entries)
			$navi[] = "<a href=\"".genurl(array("start" => $startingEntry + $conf_entries))."\">".$msg['prev']."</a>";
		if ($startingEntry >= $conf_entries)
			$navi[] = "<a href=\"".genurl(array("start" => $startingEntry - $conf_entries))."\">".$msg['next']."</a>";
		if (count($navi))
			$ret .= "<p class=\"navi\">".join(" | ", $navi)."</p>\n";
	}

	$ret .= <<<End
</div>
<div id="navi">
End;
	if (function_exists("listCats")) {
		$ret .= "<h2>$msg[categories]:</h2>\n"
			."<form method=\"GET\"><select class=\"blogCats\" "
			."name=\"category\" onchange=\"submit()\">\n";
		$dcats = getCats();
		foreach ($dcats as $kitty) {
			if ($categoryFilter == $kitty['category'])
				$sel = ' selected="selected"';
			else
				$sel = "";
			$ret .= '<option value="'.$kitty['category'].'"'.$sel.'>'
				.$kitty['name']."</option>\n";
		}
		$ret .= "</select></form>\n";
	}
	if (function_exists("makeCal") && ($cal = makeCal()) !== false)
		$ret .= "<h2>$msg[calendar]:</h2>\n" . $cal;
	if (function_exists("makeBlogroll"))
		$ret .= "<h2>$msg[blogroll]:</h2>\n" . makeBlogroll();
	if (function_exists("lastComments")) {
		$cmts = lastComments();
		if (is_array($cmts) && count($cmts)) {
			$ret .= "<h2>$msg[lastcomments]:</h2>\n";
			$blog = false;
			foreach ($cmts as $cmt)
				$ret .= commentBlock($blog, $cmt, true);
		}
	}
	if (function_exists("showArchives")) {
		$archs = showArchives();
		if (count($archs))
			$ret .= "<h2>$msg[archives]:</h2>\n<ul>\n<li>"
				.join("</li>\n<li>", $archs)."</li>\n</ul>\n";
	}
	return $ret . <<<End
</div>
<div id="mainfooter">&nbsp;</div>
</div>
<div id="footer">
<ul>
	<li class="first"><a href="/">$msg[main]</a></li>
	<li><a href="${whoami}?flav=rss">$msg[rss1feed]</a></li>
	<li><a href="${whoami}?flav=rss2">$msg[rss2feed]</a></li>
	<li><a href="${whoami}?flav=atom">$msg[atomfeed]</a></li>
	<li>Powered by <a href="http://js.hu/package/blosxom.php/">$NAME $VERSION</a></li>
</ul>
<p>$msg[copyright]</p>
</div>
</div>
</div>
</body>
</html>

End;
}
?>
