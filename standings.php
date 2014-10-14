<?php
require_once "lib.php";
require_once "scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>$year BQBL Standings </title></head><body>\n";

$bqbl_teamname = bqblTeams();
$matchup = array();
$record = array();
$score = array();
$points_for = array();
$points_against = array();
foreach ($bqbl_teamname as $key => $val) {
    $record[$key][0] = 0;
    $record[$key][1] = 0;
    $record[$key][2] = 0;
    $points_for[$key] = 0;
    $points_against[$key] = 0;
}

for ($i = 1; $i <= $week; $i++) {
    $lineup = getLineups($year, $i);
    foreach ($lineup as $team => $starters) {
            $score[$team][$i] =
                totalPoints(getPoints($starters[0], $i, $year)) + totalPoints(getPoints($starters[1], $i, $year));
    }
    
    $matchup = getMatchups($year, $i);
    foreach ($matchup as $team1 => $team2) {
        $record[$team1][2] += $score[$team1][$i];
        $points_against[$team1] += $score[$team2][$i];
        
        $record[$team2][2] += $score[$team2][$i];
        $points_against[$team2] += $score[$team1][$i];
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
echo "<tr><th>Team</th><th>W</th><th>L</th><th>PF</th><th>PA</th><th>PD</th></tr>";
$rank = 0;
foreach ($record as $key => $val) {
    $rank++;
    $point_differential = $points_for[$key] - $points_against[$key];
    echo "<tr><td>$rank. $bqbl_teamname[$key]</td><td>$val[0]</td><td>$val[1]</td><td>$val[2]</td><td>$points_against[$key]</td><td>$point_differential</td></tr>";
}
echo "</table>";




