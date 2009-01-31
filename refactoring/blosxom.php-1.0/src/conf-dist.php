<?php
# PHPosxom configuration file
# included by Blosxom.PHP

$conf = array(
# What's this blog's title?
	 'title' => "$NAME Blog"
# What's this blog's description? (for blog pages and outgoing RSS feed)
	,'description' => "Playing with $NAME"
# What's this blog's primary language? (for language settings, blog pages and outgoing RSS feed)
	,'language' => "en"
# What's this blog's character set?
# it can be utf-8, us-ascii, iso-8859-1, iso-8859-15, iso-8859-2 etc.
	,'charset' => "utf-8"
# Are blog bodies in (x)html?
	,'html_format' => false
# Where are this blog''s data kept?
	,'datadir' => "/Library/WebServer/Documents/blog"
# Where are your blog entries kept?  You can use relative path from where your datadir is.
	,'category_delimiter' => " » "
# Where are your modules kept?  You can use relative path from where index.php is.
	,'moddir' => "modules"
# Where are your flavours kept?  You can use relative path from where index.php is.
	,'flavdir' => "flavs"
# Full URL to CSS file (if you have one)
	,'cssURL' => "blog.css"
# Metadata system keeps track of blog entries and their issue date to keep
# original order. To use this system, set metafile from false to an array
# (path is the database file, type is its type).
	,'metafile' => false #"db4:///metadata.db"
# Blog entry sources.  All sources are read, each for an author.
# Each source entry is a name-description pair.
#  Name is usually the author''s short name.
#  Description is a list of key-value pairs:
#    author: author''s full name
#    link: link to author (any URL; use mailto: for email links)
#    path: where author''s blog entries are kept (can be relative to datadir)
	,'sources' => array(
		 'default' => array(
			 'author' => "Default Author"
			,'link' => "mailto:foo&#40;bar.com"
			,'path' => ""
		)
	)
# Do you want to use the categories file system?  Type false or a file name.
# If yes, list them in
#    path = name
# format.
	,'categoriesfile' => false # "categories"
# An author can force recheck directories and put updates to metafile
# if a GET variable is set (eg. http://.../index.php?force=1) or
# a force file is in its place.  The file will be deleted after the first
# update.  The path is relative to datadir.
	,'forcefile' => "force"
# Should I stick only to the datadir for items or travel down the
# directory hierarchy looking for items?  If so, to what depth?
# 0 = infinite depth (aka grab everything), 1 = source dir only, n = n levels
# down
	,'depth' => 0
# How many entries should be shown per page?
	,'entries' => 10
# EXTERNAL MODULES
# (should be in moddir and named with .php extension)
	,'modules' => array()
);
?>
