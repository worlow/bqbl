<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

$completed_week = currentCompletedWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$week = isset($_GET['week']) && (pg_escape_string($_GET['week']) <= $completed_week || $year < currentYear())
        ? pg_escape_string($_GET['week']) : currentCompletedWeek();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

$games = array();
foreach (nflTeams() as $team) {
    for ($i=1; $i<=$week; $i++) {
        $games[] = array($year, $i, $team);
    }
}
$gamePoints = getPointsBatch($games);

echo "<html><head>
<title>$year BQBL Standings </title>
<style type='text/css'>
tr.thickline td {
border-bottom-width: 24px;
}
</style>
</head><body>\n";

$bqbl_teamname = bqblTeams($league, $year);
$matchup = array();
$record = array();
$score = array();

$query = "SELECT bqbl_team, nfl_team
    FROM roster WHERE year='$year';";
$result = pg_query($GLOBALS['bqbldbconn'],$query);
while(list($bqbl_team,$nfl_team) = pg_fetch_array($result)) {
    $roster[$bqbl_team][] = $nfl_team;
}

foreach ($bqbl_teamname as $key => $val) {
    $record[$key]['wins'] = 0;
    $record[$key]['losses'] = 0;
    $record[$key]['points_for'] = 0;
    $record[$key]['points_against'] = 0;
    $record[$key]['streak'] = 0;
}

for ($i = 1; $i <= $week; $i++) {
    $lineup = getLineups($year, $i, $league);
    foreach ($roster as $bqbl_team => $nfl_teams) {
        $score[$bqbl_team][$i] = 0;
        foreach ($nfl_teams as $nfl_team) {
            if ($nfl_team == $lineup[$bqbl_team][0] || $nfl_team == $lineup[$bqbl_team][1]) {
                $score[$bqbl_team][$i] += totalPoints($gamePoints[$year][$i][$nfl_team]);
            } else {
                $score[$bqbl_team][$i] += $gamePoints[$year][$i][$nfl_team]['Misc. Points'][1];
            }
        }
    }
    
    $matchup = getMatchups($year, $i, $league);
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
        } else {
            $record[$team1]['wins'] += .5;
            $record[$team1]['losses'] += .5;
            $record[$team2]['wins'] += .5;
            $record[$team2]['losses'] += .5;
            $record[$team1]['streak'] = 0;
            $record[$team2]['streak'] = 0;
            
        }
    }
}

arsort($record);
echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block;">';
echo "<tr><th></th><th>Team</th><th>Wins</th><th>Losses</th><th>Points For</th>
    <th>Points Against</th><th>Point Differential</th><th>Streak</th></tr>";
$rank = 0;
foreach ($record as $key => $val) {
    if (($key == 4 && $year <= 2013) || ($key == 9 && $year > 2013)) {
        continue;
    }
        
    $rank++;
    $point_differential = $val['points_for'] - $val['points_against'];
    $thickline = ($rank==4) ? "class='thickline'" : "";
    
    switch ($key) {
        case 1:
            $color = "class='samdwich'";
            break;
        case 2:
            $color ="class='sworls'";
            break;
        case 3:
            $color = "class='jhka3'";
            break;
        case 4:
        case 9:
            $color = "class='murphmanjr'";
            break;
        case 5:
            $color = "class='lukabear'";
            break;
        case 6:
            $color =  "class='anirbaijan'";
            break;
        case 7:
            $color = "class='kvk'";
            break;
        case 8:
            $color = "class='palc'";
            break;
        default:
            $color = "";
    }
    
    echo "<tr $thickline><td>$rank.</td><td $color>$bqbl_teamname[$key]</td><td>".$val['wins']."</td>
        <td>".$val['losses']."</td><td>".$val['points_for']."</td><td>".$val['points_against']."</td><td>";
    if ($point_differential >= 0) {
        echo "+";
    }
    echo "$point_differential</td><td>";
    if ($val['streak'] < 0) {
        echo "L".$val['streak'];
    } else {
        echo "W-".$val['streak'];
    }
    echo "</td></tr>";  
}
echo "</table>";
?>
<style>
.samdwich {
    background-color: #00FF00;
}
 
.sworls {
    color: #FFFFFF;
    background-color: #0000FF;
}
    
.jhka3 {
    color: #FFFFFF;
    background-color: #FF0000;
}
    
.murphmanjr {
    color: #FFFFFF;
    background-color: #6495ED;
}
    
.lukabear {
    color: #FFFFFF;
    background-color: #9B30FF;
}
    
.anirbaijan {
  background-image: -webkit-gradient( linear, left top, right top, color-stop(0, #f22), color-stop(0.15, #f2f), color-stop(0.3, #22f), color-stop(0.45, #2ff), color-stop(0.6, #2f2),color-stop(0.75, #2f2), color-stop(0.9, #ff2), color-stop(1, #f22) );
  background-image: gradient( linear, left top, right top, color-stop(0, #f22), color-stop(0.15, #f2f), color-stop(0.3, #22f), color-stop(0.45, #2ff), color-stop(0.6, #2f2),color-stop(0.75, #2f2), color-stop(0.9, #ff2), color-stop(1, #f22) );
  color:transparent;
  -webkit-background-clip: text;
  background-clip: text;
}

.kvk {
    background-color: #FFAA00;
}

.palc {
    color: #FFFFFF;
    background-color: #900000;
}
</style>