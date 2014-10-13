<?php
require_once "lib.php";
require_once "scoring.php";

if(!isset($_GET['week'])) {
    echo "Error: week variable not set!";
    exit();
}

$week = pg_escape_string($_GET['week']);
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : 2014;

echo "<html><head>
<div id='content' align='center'>
<title>BQBL Week $week $year</title></head><body>\n
<h1>Week $week $year Scoreboard</h1>";

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
    printGameScore($hometeam, $week, $year);
    echo "</div><div class=score>@</div>\n";
    echo "<div class=score >\n";
    echo "$awayteam\n";
    printGameScore($awayteam, $week, $year);
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