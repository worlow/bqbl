<?php
require_once "lib.php";
require_once "scoring.php";

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='2013' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($query);
$tot = 0;
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
        $tot++;
	echo "$hometeam: number_format(completionPct($gsis, $hometeam),1)\n";
        echo "$awayteam: number_format(completionPct($gsis, $awayteam),1)\n";
}
echo "\n$tot";
