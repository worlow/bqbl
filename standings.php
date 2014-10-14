<?php
require_once "lib.php";
require_once "scoring.php";
require_once "matchup.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>$year BQBL Standings </title></head><body>\n";

$bqbl_teamname = bqblTeams();
$lineup = array();
$matchup = array();
$record = array();
$score = array();
foreach ($bqbl_teamname as $key => $val) {
    $lineup[$key] = array();
    $score[$key] = array();
    $record[$key][0] = 0;
    $record[$key][1] = 0;
}

for ($i = 1; $i <= $week; $i++) {
    $query = "SELECT bqbl_team, starter1, starter2
                FROM lineup
                  WHERE year = $year AND week = $i;";
    $result = pg_query($bqbldbconn, $query);
    while(list($bqbl_team,$starter1,$starter2) = pg_fetch_array($result)) {
        $score[$bqbl_team][$i] =
            totalPoints(getPoints($starter1, $i, $year)) + totalPoints(getPoints($starter2, $i, $year));
    }
    $query = "SELECT team1, team2
            FROM schedule
              WHERE year = $year AND week = $i;";
    $result = pg_query($bqbldbconn, $query);
    while(list($team1,$team2) = pg_fetch_array($result)) {
        if ($score[$team1][$i] > $score[$team2][$i]) {
            $record[$team1][0]++;
            $record[$team2][1]++;
        } elseif ($score[$team1][$i] < $score[$team2][$i]) {
            $record[$team1][1]++;
            $record[$team2][0]++;
        } else {
            $record[$team1][0] += .5;
            $record[$team2][0] += .5;
            $record[$team1][1] += .5;
            $record[$team2][1] += .5;
        }
    }
}

arsort($record);
echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block;">';
echo "<tr><th>Team</th><th>W</th><th>L</th></tr>";
$rank = 0;
foreach ($record as $key => $val) {
    $rank++;
    echo "<tr><td>$rank. $bqbl_teamname[$key]</td><td>$val[0]</td><td>$val[1]</td></tr>";
}
echo "</table>";




