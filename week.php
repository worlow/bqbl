<?php
require_once "lib.php";
require_once "scoring.php";
if(!isset($_GET['week'])) {
    echo "Error: week variable not set!";
    exit();
}

$week = pg_escape_string($_GET['week']);
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : 2014;

echo "<html><head><title>BQBL Week $week $year</title></head>\n";

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$week' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($query);
echo "<div style='display:table;'>";
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $gameType = gameTypeById($gsis);
    echo "<div id=matchup style='display:table-row;'>\n";
    echo "<div id=score style='display:table-cell;'>\n";
    echo "$hometeam\n";
    printGameScore($awayteam, $week, $year);
    echo "</div><div style='display:table-cell;vertical-align:middle;'>AT</div>\n";
    echo "$awayteam\n";
    echo "<div id=score style='style='display:table-cell;'>\n";
    printGameScore($hometeam, $week, $year);
    echo"</div></div>";
}
echo "</div>";