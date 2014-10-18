
<?php
require_once "lib.php";
require_once "scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentCompletedWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>BQBL Season Leaders $year</title></head><body>\n";

$grandtotals = array();
$grandtotals_defense = array();
$starts = array();
$owner = array();
$bqbl_draftscore = array();
$bqbl_teamname = bqblTeams();
$nfl_draftscore = array();
$draft_pick = array();
$average = array();
echo "<br><h1>$year Season Rankings</h1>";

foreach (nflTeams() as $team) {
    $starts[$team] = 0;
    $grandtotals[$team] = 0;
    $grandtotals_defense[$team] = 0;
}

foreach ($bqbl_teamname as $key => $val) {
    $bqbl_draftscore[$key] = 0;
}

for ($i=1; $i<=$week; $i++) {
    $query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$i' AND season_type='Regular'
          ORDER BY start_time ASC;";
    $result = pg_query($GLOBALS['nfldbconn'],$query);
    while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
        $starts[$hometeam]++;
        $starts[$awayteam]++;
        $homepoints = getPoints($hometeam, $i, $year);
        $awaypoints = getPoints($awayteam, $i, $year);
        $grandtotals[$hometeam] += totalPoints($homepoints);
        $grandtotals_defense[$hometeam] += defenseScore($awaypoints);
        $grandtotals[$awayteam] += totalPoints($awaypoints);
        $grandtotals_defense[$awayteam] += defenseScore($homepoints);
    }
}

$query = "SELECT bqbl_team, nfl_team, draft_position
    FROM roster WHERE year='$year';";
$result = pg_query($GLOBALS['bqbldbconn'],$query);
while(list($bqbl_team,$nfl_team,$draft_position) = pg_fetch_array($result)) {
    $draft_pick[$nfl_team] = $draft_position;
    $owner[$nfl_team] = $bqbl_team; 
    $average[$nfl_team] = $grandtotals[$nfl_team]/$starts[$nfl_team];
    $average_defense[$nfl_team] = $grandtotals_defense[$nfl_team]/$starts[$nfl_team];
}
arsort($average);
arsort($average_defense);

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block;">';
echo "<tr><th>Rank</th><th>Team</th><th>Points</th><th>Average</th></tr>";
$rank = 0;
foreach ($average as $key => $val) {
    $rank++;
    $nfl_draftscore[$key] = $draft_pick[$key] - $rank;
    $bqbl_draftscore[$owner[$key]] += $rank;
    echo "<tr><td>$rank</td><td>$key</td><td>$grandtotals[$key]</td><td>".round($val,2)."</td></tr>";
}
echo "</table>";

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th>Rank</th><th>Team</th><th>Defensive Points</th><th>Average</th></tr>";
$rank = 0;
foreach ($average_defense as $key => $val) {
    $rank++;
    echo "<tr><td>$rank</td><td>$key</td><td>$grandtotals_defense[$key]</td><td>".round($val,2)."</td></tr>";
}
echo "</table>";

arsort($nfl_draftscore);
asort($bqbl_draftscore);

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th>Rank</th><th>Team</th><th>Pick</th><th>Draft Score</th></tr>";
$rank = 0;
foreach ($nfl_draftscore as $key => $val) {
    $rank++;
    echo "<tr><td>$rank</td><td>$key</td><td>$draft_pick[$key]</td><td>$val</td></tr>";
}
echo "</table>";

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th>Rank</th><th>Team Name</th><th>Draft Score</th></tr>";
$rank = 0;
foreach ($bqbl_draftscore as $key => $val) {
    if (($key == 4 && $year <= 2013) || ($key == 9 && $year > 2013)) {
            continue;
    }
    $rank++;
    echo "<tr><td>$rank</td><td>$bqbl_teamname[$key]</td><td>$val</td></tr>";
}
echo "</table>";
?>
