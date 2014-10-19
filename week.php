<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

$games = array();
foreach(nflTeams() as $nflTeam) $games[] = array($year, $week, $nflTeam);
$gamePoints = getPointsBatch($games);

$updateTime = date("n/j g:i:s A, T", databaseModificationTime());
echo "<html><head>
<div id='content' align='center'>
<title>BQBL Week $week $year</title></head><body>\n
<h1>$year Week $week Scoreboard</h1>
Last Updated at $updateTime ";
$timeout = $DB_UPDATE_INTERVAL - (time()-databaseModificationTime());
if (isset($_GET['autorefresh'])) {
    if ($timeout >= 0) {
        $timeout *= 1000;  # millis
        $timeout += rand(5000,12000);  # allow for update + prevent DDOS
        echo "<script type='text/javascript'>
        setTimeout(function() {location.reload();}, $timeout);
        </script>";
    } else {
        echo "There was a problem with the auto-refresh function.";
    }
} elseif ($timeout>=0 && $week==currentWeek() && $year==currentYear()) {
    echo "<a href='$_SERVER[PHP_SELF]?autorefresh'>Auto Refresh</a>";
}
$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$week' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($GLOBALS['nfldbconn'],$query);
echo "<div style='display:table;'>";
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $gameType = gameTypeById($gsis);
    echo "<div id=matchup style='display:table-row;'>\n";
    echo "<div class=score>\n";
    echo "$hometeam\n";
    printGameScore($gamePoints[$year][$week][$hometeam], $hometeam, $week, $year);
    echo "</div><div class=score>@</div>\n";
    echo "<div class=score >\n";
    echo "$awayteam\n";
    printGameScore($gamePoints[$year][$week][$awayteam], $awayteam, $week, $year);
    echo"</div></div>";
}
echo "</div>";  # table div
echo "</div>";  # content div
?>
<style>
.score {
font-size: x-large;
font-weight:bold;
text-align: center;
display:table-cell;
padding:0 20px 60px 0;
}
</style>