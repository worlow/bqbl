<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";
$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

$games = array();
foreach(nflTeams() as $nflTeam) $games[] = array($year, $week, $nflTeam);
$gamePoints = getPointsBatch($games);

echo "<html><head>
<title>BQBL Week $week $year</title></head><body>\n
<h1>Week $week $year Leaderboard</h1>";

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$week' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($GLOBALS['nfldbconn'],$query);

$totals = array();
$totals_defense = array();
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $homepoints = $gamePoints[$year][$week][$hometeam];
    $awaypoints = $gamePoints[$year][$week][$awayteam];
    $totals[$hometeam] = totalPoints($homepoints);
    $totals_defense[$hometeam] = defenseScore($awaypoints);
    $totals[$awayteam] = totalPoints($awaypoints);
    $totals_defense[$awayteam] = defenseScore($homepoints);
}
arsort($totals);
arsort($totals_defense);

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block;">';
echo "<tr><th>Rank</th><th>Team Name</th><th>Total Points</th></tr>";
$rank = 0;
foreach ($totals as $key => $val) {
    $rank++;
    echo "<tr><td>$rank</td><td>$key</td><td>$val</td></tr>";
}
echo "</table>";

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th>Rank</th><th>Team Name</th><th>Total Defensive Points</th></tr>";
$rank = 0;
foreach ($totals_defense as $key => $val) {
    $rank++;
    echo "<tr><td>$rank</td><td>$key</td><td>$val</td></tr>";
}
echo "</table>";
?>
