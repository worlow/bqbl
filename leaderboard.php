
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
<title>BQBL Week $week $year</title></head><body>\n
<h1>Week $week $year Leaderboard</h1>";

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$week' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($GLOBALS['nfldbconn'],$query);

$totals = array();
$totals_defense = array();
$grandtotals = array();
$grandtotals_defense = array();
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $grandtotals[$hometeam] = 0;
    $grandtotals[$awayteam] = 0;
    $grandtotals_defense[$hometeam] = 0;
    $grandtotals_defense[$awayteam] = 0;
    $homepoints = getPoints($hometeam, $week, $year);
    $awaypoints = getPoints($awayteam, $week, $year);
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

echo "<br><h1>$year Season Rankings</h1>";

for ($i=1; $i<=$week; $i++) {
    $query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$i' AND season_type='Regular'
          ORDER BY start_time ASC;";
    $result = pg_query($GLOBALS['nfldbconn'],$query);
    while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
        $homepoints = getPoints($hometeam, $i, $year);
        $awaypoints = getPoints($awayteam, $i, $year);
        $grandtotals[$hometeam] += totalPoints($homepoints);
        $grandtotals_defense[$hometeam] += defenseScore($awaypoints);
        $grandtotals[$awayteam] += totalPoints($awaypoints);
        $grandtotals_defense[$awayteam] += defenseScore($homepoints);
    }
}
arsort($grandtotals);
arsort($grandtotals_defense);

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block;">';
echo "<tr><th>Rank</th><th>Team Name</th><th>Total Points</th></tr>";
$rank = 0;
foreach ($grandtotals as $key => $val) {
    $rank++;
    echo "<tr><td>$rank</td><td>$key</td><td>$val</td></tr>";
}
echo "</table>";

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th>Rank</th><th>Team Name</th><th>Total Defensive Points</th></tr>";
$rank = 0;
foreach ($grandtotals_defense as $key => $val) {
    $rank++;
    echo "<tr><td>$rank</td><td>$key</td><td>$val</td></tr>";
}
echo "</table>";
?>
