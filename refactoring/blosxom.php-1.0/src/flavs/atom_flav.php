<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# ATOM v. 0.3 FLAVOR FILE
# The purpose is to allow you to customize the look and feel of
# your PHPosxom blog without the necessity of using flav=foo in
# the URL or editing the main script.
#
# See: http://www.intertwingly.net/wiki/pie/FrontPage
# ----------------------------------------------------------------
# USAGE
# There are three functions below that form the head, story, and
# foot blocks on a blog page.
# ----------------------------------------------------------------
# $content_type lets the browser know what kind of content is being
# served. You''-ll probably want to leave this alone.

$content_type = "application/atom+xml";

function headBlock($category) {
	global $conf_title, $conf_description, $conf_language, $conf_charset;
	global $lastmod, $whoami, $NAME, $VERSION;

	$thetitle = $conf_title;
	if ($category)
		$thetitle .= " ($category)";
	$head = '<?xml version="1.0"';
	if (isset($conf_charset) and $conf_charset != 'utf-8')
		$head .= " encoding=\"$conf_charset\"";
	$head .= "?".">\n";
	$lastpub = w3cdate($lastmod);
	return <<<End
${head}
<feed version="0.3" xmlns="http://purl.org/atom/ns#" xml:lang="$conf_language">
  <title>$thetitle</title>
  <link rel="alternate" type="text/html" href="$whoami"/>
  <modified>$lastpub</modified>
  <tagline>$conf_description</tagline>
  <generator>$NAME $VERSION</generator>

End;
}

function storyBlock($blog)
{
	global $whoami, $conf_cssURL;

	$title = htmlspecialchars($blog->title);
	$text = $blog->getContents(true);
	$html = htmlspecialchars('<'.<<<End
html>
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
	$authorlink = $blog->link;
	if (!strncmp($authorlink, "mailto:", 7))
		$authorlink = "<email>".html_entity_decode(substr($authorlink, 7))."</email>\n";
	else
		$authorlink = "<url>$authorlink</url>\n";
	$displaycat = htmlspecialchars(displaycategory($blog->getCategory()));
	$path = htmlspecialchars($blog->path);
	$date = w3cdate($blog->date);
	$mod = $blog['mod']? w3cdate($blog->mod): $date;
	$url = $whoami;
	$link = $url.htmlspecialchars($blog->path);
	$href = $url.rawurlencode($blog->path);
	return <<<End
<entry>
  <title>$title</title>
  <link rel="alternate" type="text/html" href="$link"/>
  <id>$whoami:$path</id>
  <author>
    <name>$author</name>
    $authorlink
  </author>
  <issued>$date</issued>
  <modified>$mod</modified>
  <content type="text/html" mode="escaped">$html</content>
</entry>

End;
}

function footBlock() {

	return <<<End
</feed>
End;
}
?>
