<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# RSS FLAVOR FILE
# The purpose is to allow you to customize the look and feel of
# your PHPosxom blog without the necessity of using flav=foo in
# the URL or editing the main script.
#
# See http://purl.org/rss/1.0
# ----------------------------------------------------------------
# USAGE
# There are three functions below that form the head, story, and
# foot blocks on a blog page.

function headBlock($category)
{
	global $conf_title, $conf_charset, $conf_description, $conf_language, $lastmod;
	global $whoami;

	$thetitle = $conf_title;
	if ($category)
		$thetitle .= " ($category)";
	$head = '<?xml version="1.0"';
	if (isset($conf_charset) && $conf_charset != 'utf-8')
		$head .= " encoding=\"$conf_charset\"";
	$head .= "?".">".<<<End
<rdf:RDF
 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 xmlns="http://purl.org/rss/1.0/"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
>
<channel rdf:about="$whoami">
  <title>$thetitle</title>
  <link>$whoami</link>
  <description>$conf_description</description>

End;
	if ($lastmod)
		$head .= "  <dc:date>".w3cdate($lastmod)."</dc:date>\n";
	$head .= <<<End
  <language>$conf_language</language>
  <items>
    <rdf:Seq>

End;
	return $head;
}

function storyBlock($blog)
{
	global $whoami, $conf_cssURL;
	global $rss_stories;

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
	$author  = htmlspecialchars($blog->author);
	$displaycat  = htmlspecialchars(displaycategory($blog->getCategory()));
	$path = htmlspecialchars($blog->path);
	$date = w3cdate($blog->date);
	$url = $whoami;
	$link = $url.htmlspecialchars($blog->path);
	$href = $url.rawurlencode($blog->path);
	if (!isset($rss_stories))
		$rss_stories = ''; # to be strict
	$rss_stories .= <<<End
<item rdf:about="$href">
<title>$title</title>
<dc:subject>$displaycat</dc:subject>
<dc:creator>$author</dc:creator>
<dc:date>$date</dc:date>
<link>$link</link>
<description>$html</description>
</item>

End;
	return "      <rdf:li rdf:resource=\"$href\" />\n";
}

function footBlock() {
	global $rss_stories;

	return <<<End
    </rdf:Seq>
  </items>
</channel>
$rss_stories
</rdf:RDF>
End;
}
?>
