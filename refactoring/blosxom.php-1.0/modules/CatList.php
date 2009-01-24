<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: CatList
# DESCRIPTION: Constructs a simple linked list of categories
# MAINTAINER: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
#
# ORIGINAL PHPOSXOM MODULE: CatList
# ORIGINAL AUTHOR: Robert Daeley <robert@celsius1414.com>
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# Add a variable declaration line to your flavor file like this (sans quotes):
# "$myCategories = listCats();"
# Then concatenate $myCategories into one of your blocks.
# -------------------------------------------------------------------
# PREFERENCES

# How would you like this module to refer to the top level of your blog?
# (Examples: "Main" "Home" "All" "Top")
if ($conf_language == "hu-hu")
	$topLevelTerm = "Összes";
else
	$topLevelTerm = "All";

# Would you like individual RSS links added as well? (1 = yes, 0 = no)
$confdefs["catlist_syndicate_auto"] = false;
$confdefs["catlist_syndicate_type"] = "rss2";

# -------------------------------------------------------------------
# THE MODULE
# (don't change anything below this unless you know what you're doing.)

function listCats() {
	$cats = getCats();
	$ret = "<ul class=\"blogCats\">";
	foreach ($cats as $kitty) {
		$ret .= '<li><a href="'.$kitty['link'].'">'.$kitty['name'].'</a>';
		if (isset($kitty['syndicate']))
			$ret .= ' (<a href="'.$kitty['synlink'].'>'
				.$kitty['syndicate']	.'</a>)';
		$ret .= "</li>\n";
	}
	return $ret."\n</ul>";
}

function getCats() {
	global $whoami, $cats, $topLevelTerm;
	global $conf_catlist_syndicate_auto, $conf_catlist_syndicate_type;

	sort ($cats);
	$herd = array();
	foreach ($cats as $kitty) {
		if (!$kitty) {
			$category = "<strong>$topLevelTerm</strong>";
			$link = $whoami;
			$delim = '?';
		} else {
			$category = displaycategory($kitty);
			$link = $whoami.'?category='.$kitty;
			$delim = '&amp;';
		}
		$tcat = array(
			'category' => $kitty,
			'name' => $category,
			'url' => $link
		);
		if ($conf_catlist_syndicate_auto) {
			$tcat['syndicate'] = $link.$delim.'flav='
				.$conf_catlist_syndicate_type;
			$tcat['syntype'] = strtoupper($conf_catlist_syndicate_type);
		}
		$herd[] = $tcat;
	}
	return $herd;
}
?>
