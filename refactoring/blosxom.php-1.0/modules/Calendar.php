<?php
# Blosxom.PHP: a rewrite of PHPosxom, which is a PHP rewrite of Blosxom
# BLOSXOM.PHP MODULE
# -------------------------------------------------------------------
# NAME: Calendar
# DESCRIPTION: Constructs a simple blog entry month calendar
# MAINTAINER: Balazs Nagy <js@iksz.hu>
# WEBSITE: http://js.hu/package/blosxom.php/
#
# ORIGINAL PHPOSXOM MODULE: Calendar
# ORIGINAL AUTHOR: Robert Daeley <robert@celsius1414.com>
# -------------------------------------------------------------------
# INSTALLATION
# Add its name to the 'modules' array under 'EXTERNAL MODULES' in conf.php.
# -------------------------------------------------------------------
# USAGE
# Add a variable declaration line to your flavor file like this (sans quotes):
# "$myCalendar = makeCal();"
# Then concatenate $myCalendar into one of the blocks.
# -------------------------------------------------------------------
# PREP
$confdefs['calendar_showcal'] = true;
$confdefs['calendar_showarchive'] = true;

if ($conf_language == 'hu-hu') {
	$myDays = array('H','K','Sz','Cs','P','Sz','V');
	$myAbbrDays = array('hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat', 'vasárnap');
	$startsWithMonday = 1;
	$fmtMonYear = "%Y. %B";
	$fmtThisday = "%d. nap";
	$strNow = "most";
} else {
	$myDays = array('S','M','T','W','T','F','S');
	$myAbbrDays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	$startsWithMonday = false;
	$fmtMonYear = "%b %Y";
	$fmtThisday = "day %d";
	$strNow = "Now";
}
# -------------------------------------------------------------------
# THE MODULE
# (don't change anything below this unless you know what you're doing.)

function makeCal() {
	
	global $strNow, $daysBlogged, $whoami, $dateFilter, $categoryFilter;
	global $myDays, $myAbbrDays, $dateFilterType, $startsWithMonday, $fmtMonYear;
	global $conf_calendar_showcal;

	if (!$conf_calendar_showcal)
		return false;

	$ourDoM = null;
	$now = time();

	switch ($dateFilterType) {
		case null:
		case 'Y':
			$ourDoM = date("d",$now);
			$numDays = date("t",$now);
			$ourYear = date("Y",$now);
			$ourMonth = date("m",$now);
			$ourYearMonth = $ourYear.$ourMonth;
			$ourMoreOrLessMonth = $now;
		break;				
		case 'Ymd':
			$ourDoM = substr($dateFilter, 6, 2);
		case 'Ym':
			$ourYear = substr($dateFilter, 0, 4);
			$ourMonth = substr($dateFilter, 4, 2);
			$ourYearMonth = substr($dateFilter, 0, 6);
			$ourMoreOrLessMonth = mktime(0,0,0,$ourMonth,1,$ourYear);
			$numDays = date("t",$ourMoreOrLessMonth);
		break;				
	}
	$ourMonYear = strftime($fmtMonYear,$ourMoreOrLessMonth);

	$lastMonthName = strftime("%b", strtotime("last month", $ourMoreOrLessMonth));
	$nextMonthName = strftime("%b", strtotime("first month", $ourMoreOrLessMonth));
	$fDoWoM = date("w",mktime(0, 0, 0, $ourMonth, 1, $ourYear));
	
	if ($ourMonth == "01") {
		$lastYearMonth = $ourYearMonth - 89;
		$nextYearMonth = $ourYearMonth + 1;
	} else if ($ourMonth == 12) {
		$lastYearMonth = $ourYearMonth - 1;
		$nextYearMonth = $ourYearMonth + 89;
	} else {
		$nextYearMonth = $ourYearMonth + 1;
		$lastYearMonth = $ourYearMonth - 1;
	}

	$startCell = $startsWithMonday? ($fDoWoM + 6) % 7: $fDoWoM;

	# START TABLE AND CREATE DAY COLUMN HEADERS
	if ($categoryFilter)
		$categ = "&category=$categoryFilter";
	else
		$categ = "";
	$cal = <<<End
<table class="blogCal">
<tr><th colspan="7"><a href="$whoami?date=$ourYearMonth$categ">$ourMonYear</a>
End;
	if ($dateFilter and $ourDoM)
		$cal .= "<br />".strftime($fmtThisday);
	if ($categoryFilter)
		$cal .= "<br />".displaycategory($categoryFilter);
	$cal .= "</th></tr>\n<thead>\n<tr>\n";
	for ($i = 0; $i < 7; ++$i) {
		$cal .= "  <th abbr=\"${myAbbrDays[$i]}\" title=\"${myAbbrDays[$i]}\" scope=\"col\">${myDays[$i]}</th>\n";
	}
	$cal .= "</tr>\n</thead>\n";
	
	# CREATE LINKED DAY ROWS

	$cal .= "<tbody>\n<tr>".str_repeat("<td>&nbsp;</td>", $startCell);
	for ($cells=$startCell, $today = 1; $cells < ($numDays + $startCell); ++$cells, ++$today) {
		if ($cells % 7 == 0)
			$cal .= "<tr>";
		$cal .= ($ourDoM == $today)? "<td class=\"today\">": "<td>";
		if ($today < 10)
			$dayThisTime = $ourYearMonth."0".$today;
		else
			$dayThisTime = $ourYearMonth.$today;
		if (count($daysBlogged) && in_array($dayThisTime, $daysBlogged))
			$cal .= "<a href=\"${whoami}?date=${dayThisTime}${categ}\">${today}</a>";
		else
			$cal .= $today;
		$cal .= "</td>";
		if ($cells % 7 == 6)
			$cal .= "</tr>\n";
	}
	$lastRowEnd = $cells % 7;
	if ($lastRowEnd)
		$cal .= str_repeat("<td>&nbsp;</td>", 7-$lastRowEnd)."<tr>\n";
	$cal .= "</tbody>\n";

	# CREATE BOTTOM OF TABLE
	$cal .= <<<End
<tr><td class="navigator" colspan="7">
<a href="${whoami}?date=${lastYearMonth}${categ}">$lastMonthName</a>
| <a href="${whoami}">$strNow</a>
| <a href="${whoami}?date=${nextYearMonth}${categ}">$nextMonthName</a>
</td></tr>
</table>
End;

	# CONSTRUCT TABLE AND RETURN

	return $cal;
}

function showArchives() {
	global $daysBlogged, $whoami, $dateFilter, $conf_calendar_showarchive;
	global $fmtMonYear;

	if (!$conf_calendar_showarchive)
		return false;

	$dates = array();

	foreach ($daysBlogged as $ymd) {
		$ym = substr($ymd, 0, 6);
		if (!isset($dates[$ym]))
			$dates[$ym] = "<a href=\"$whoami?date=$ym\">"
				.strftime($fmtMonYear, strtotime($ymd))."</a>";
	}
	return $dates;
}

?>
