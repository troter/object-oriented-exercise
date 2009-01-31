<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: Blogroll
# DESCRIPTION: Constructs a list of most readed blogs
# AUTHOR: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# Add a variable declaration line to your flavor file like this (sans quotes):
# "$myBlogroll = makeBlogroll();"
# Then concatenate $myBlogroll into one of your blocks.
# -------------------------------------------------------------------
# PREFERENCES
# blogroll: hash of hashes
#   array (
#     "Type" => array (
#       "Blog name" => "Blog Url"
#     )
#   )

# -------------------------------------------------------------------
# THE MODULE
# (don''t change anything below this unless you know what you''re doing.)

$confdefs['blogroll_delimiter'] = " » ";

function makeBlogroll()
{
	global $conf_blogroll, $conf_blogroll_delimiter;

	if (!count($conf_blogroll))
		return false;

	$ret = "";

	foreach ($conf_blogroll as $type=>$list) {
		if (!count($list))
			continue;
		$l = array();
		foreach ($list as $name => $url) {
			$l[] = "<a href=\"$url\">$name</a>";
		}
		$ret .= "<p>$type".$conf_blogroll_delimiter.join($conf_blogroll_delimiter, $l)."</p>\n";
	}
	return $ret;
}
?>
