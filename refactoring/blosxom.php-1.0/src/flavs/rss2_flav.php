<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# RSS2 FLAVOR FILE
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

$content_type = "text/xml";

function headBlock($category) {
	global $conf_title, $conf_description, $conf_language, $conf_charset;
	global $etag;
	global $whoami;

	$title = $conf_title;
	if ($category)
		$title .= " ($category)";
	$head = '<?xml version="1.0"';
	if (isset($conf_charset) and $conf_charset != 'utf-8')
		$head .= " encoding=\"$conf_charset\"";
	$head .= "?".">\n";
	$head .= <<<End
<rss version="2.0">
<channel>
  <title>$title</title>
  <link>$whoami</link>
  <description>$conf_description</description>

End;
	if ($etag) {
		$head .= "  <pubDate>$etag</pubDate>\n";
		$head .= "  <lastBuildDate>$etag</lastBuildDate>\n";
	}
	$head .= <<<End
  <language>$conf_language</language>

End;
	return $head;
}

function storyBlock($blog)
{
	global $whoami, $conf_cssURL;

	$title = htmlspecialchars($blog->title);
	$text = $blog->getContents(true);
	$html = htmlspecialchars(<<<End
<html>
<head>
<base href="$whoami" />
<style>@import "$conf_cssURL";</style>
<title>$title</title>
</head>
<body id="rss">
$text
</body>
</html>
End
);
	$author = htmlspecialchars($blog->author);
	$displaycat = htmlspecialchars(displaycategory($blog->getCategory()));
	$path = htmlspecialchars($blog->path);
	$date = date("r", $blog->date);
	$url = $whoami;
	$link = $url.htmlspecialchars($blog->path);
	$href = $url.rawurlencode($blog->path);
	return <<<End
<item>
  <title>$title</title>
  <category>$displaycat</category>
  <author>$author</author>
  <pubDate>$date</pubDate>
  <link>$link</link>
  <description>$html</description>
</item>

End;
}

function footBlock() {

	return <<<End
</channel>
</rss>
End;
}
?>
