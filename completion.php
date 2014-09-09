<?php
require_once "lib.php";
require_once "scoring.php";

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='2013' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($query);
$tot = 0;
$tot35 = 0;
$tot40 = 0;
$tot45 = 0;
$tot50 = 0;
$tot55 = 0;
$tot60 = 0;
$tot65 = 0;
$tot70 = 0;
$tot75 = 0;
$tot80 = 0;
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
	echo number_format(completionPct($gsis, $hometeam),1).":".passingTDs($gsis, $hometeam).":".passingYards($gsis, $hometeam)."\n";
	echo number_format(completionPct($gsis, $awayteam),1).":".passingTDs($gsis, $awayteam).":".passingYards($gsis, $awayteam)."\n";
	if (number_format(completionPct($gsis, $hometeam),1) <= 35) {
		$tot35++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 40) {
		$tot40++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 45) {
		$tot45++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 50) {
		$tot50++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 55) {
		$tot55++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 60) {
		$tot60++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 65) {
		$tot65++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 70) {
		$tot70++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 75) {
		$tot75++;
	} elseif (number_format(completionPct($gsis, $hometeam),1) <= 80) {
		$tot80++;
	} else {
		$tot++;
	}
	if (number_format(completionPct($gsis, $awayteam),1) <= 35) {
		$tot35++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 40) {
		$tot40++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 45) {
		$tot45++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 50) {
		$tot50++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 55) {
		$tot55++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 60) {
		$tot60++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 65) {
		$tot65++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 70) {
		$tot70++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 75) {
		$tot75++;
	} elseif (number_format(completionPct($gsis, $awayteam),1) <= 80) {
		$tot80++;
	} else {
		$tot++;
	}
}
echo "Less than 35 $tot35 \n";
echo "Less than 40 $tot40 \n";
echo "Less than 45 $tot45 \n";
echo "Less than 50 $tot50 \n";
echo "Less than 55 $tot55 \n";
echo "Less than 60 $tot60 \n";
echo "Less than 65 $tot65 \n";
echo "Less than 70 $tot70 \n";
echo "Less than 75 $tot75 \n";
echo "Less than 80 $tot80 \n";
echo "More than 80 $tot \n";

