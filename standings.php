<?php
require_once "lib.php";
require_once "scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>$year BQBL Standings </title>
<style type='text/css'>
tr.thickline td {
border-bottom-width: 6px;
}
</style>
</head><body>\n";

$bqbl_teamname = bqblTeams();
$matchup = array();
$record = array();
$score = array();
foreach ($bqbl_teamname as $key => $val) {
    $record[$key]['wins'] = 0;
    $record[$key]['losses'] = 0;
    $record[$key]['points_for'] = 0;
    $record[$key]['points_against'] = 0;
    $record[$key]['streak'] = 0;
}

for ($i = 1; $i <= $week; $i++) {
    $lineup = getLineups($year, $i);
    foreach ($lineup as $team => $starters) {
            $score[$team][$i] =
                totalPoints(getPoints($starters[0], $i, $year)) + totalPoints(getPoints($starters[1], $i, $year));
    }
    
    $matchup = getMatchups($year, $i);
    foreach ($matchup as $team1 => $team2) {
        $record[$team1]['points_for'] += $score[$team1][$i];
        $record[$team1]['points_against'] += $score[$team2][$i];
        
        $record[$team2]['points_for'] += $score[$team2][$i];
        $record[$team2]['points_against'] += $score[$team1][$i];
        if ($score[$team1][$i] > $score[$team2][$i]) {
            $record[$team1]['wins']++;
            $record[$team2]['losses']++;
            
            if ($record[$team1]['streak'] < 0) {
                $record[$team1]['streak'] = 1;
            } else {
                $record[$team1]['streak']++;
            }
            if ($record[$team2]['streak'] > 0) {
                $record[$team2]['streak'] = -1;
            } else {
                $record[$team2]['streak']--;
            }
        } elseif ($score[$team1][$i] < $score[$team2][$i]) {
            $record[$team1]['losses']++;
            $record[$team2]['wins']++;
            
            if ($record[$team1]['streak'] > 0) {
                $record[$team1]['streak'] = -1;
            } else {
                $record[$team1]['streak']--;
            }
            if ($record[$team2]['streak'] < 0) {
                $record[$team2]['streak'] = 1;
            } else {
                $record[$team2]['streak']++;
            }
        }
    }
}

arsort($record);
echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block;">';
echo "<tr><th></th><th>Team</th><th>Wins</th><th>Losses</th><th>Points For</th>
    <th>Points Against</th><th>Point Differential</th><th>Streak</th></tr>";
$rank = 0;
foreach ($record as $key => $val) {
    $rank++;
    $point_differential = $val['points_for'] - $val['points_against'];
    $thickline = ($rank==4) ? "class='thickline'" : "";
    echo "<tr $thickline><td>$rank.</td><td>$bqbl_teamname[$key]</td><td>".$val['wins']."</td>
        <td>".$val['losses']."</td><td>".$val['points_for']."</td><td>".$val['points_against']."</td>
        <td>$point_differential</td><td>";
    if ($val['streak'] < 0) {
        echo "L".$val['streak'];
    } else {
        echo "W-".$val['streak'];
    }
    echo "</td></tr>";  
}
echo "</table>";




