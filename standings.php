<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

$completed_week = currentCompletedWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$week = min(15, isset($_GET['week']) && (pg_escape_string($_GET['week']) <= $completed_week || $year < currentYear())
        ? pg_escape_string($_GET['week']) : currentCompletedWeek());
$week = min($week, $REG_SEASON_END_WEEK);
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

$games = array();
foreach (nflTeams() as $team) {
    for ($i=1; $i<=$week; $i++) {
        $games[] = array($year, $i, $team);
    }
}
$gamePoints = getPointsBatch($games);

ui_header("$year Standings");

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
echo '<paper-material elevation="2">';
echo '<div id="standings-table">';
echo "<div class='header row' style='display:table-row;'>
<div class='cell' style='display:table-cell;'></div>
<div class='cell' style='display:table-cell;'>Team</div>
<div class='cell' style='display:table-cell;'>Wins</div>
<div class='cell' style='display:table-cell;'>Losses</div>
<div class='cell' style='display:table-cell;'>Points For</div>
<div class='cell' style='display:table-cell;'>Points Against</div>
<div class='cell' style='display:table-cell;'>Point Differential</div>
<div class='cell' style='display:table-cell;'>Streak</div></div>";
$rank = 0;
foreach ($record as $key => $val) {
    if (($key == 4 && $year <= 2013) || ($key == 9 && $year > 2013)) {
        continue;
    }
        
    $rank++;
    $point_differential = $val['points_for'] - $val['points_against'];
    $thickline = ($rank==4) ? "thickline" : "";
    
    switch ($key) {
        case 1:
            $color = "samdwich";
            break;
        case 2:
            $color = "sworls";
            break;
        case 3:
            $color = "jhka3";
            break;
        case 4:
        case 9:
            $color = "murphmanjr";
            break;
        case 5:
            $color = "lukabear";
            break;
        case 6:
            $color = "anirbaijan";
            break;
        case 7:
            $color = "kvk";
            break;
        case 8:
            $color = "palc";
            break;
        default:
            $color = "";
    }
    
    echo "<div class='row $thickline' style='display:table-row;'><div class='cell' style='display:table-cell;'>$rank.</div><div class='cell $color' style='display:table-cell;' $color>$bqbl_teamname[$key]</div><div class='cell' style='display:table-cell;'>".$val['wins']."</div>
        <div class='cell' style='display:table-cell;'>".$val['losses']."</div><div class='cell' style='display:table-cell;'>".$val['points_for']."</div><div class='cell' style='display:table-cell;'>".$val['points_against']."</div><div class='cell' style='display:table-cell;'>";
    if ($point_differential >= 0) {
        echo "+";
    }
    echo "$point_differential</div><div class='cell' style='display:table-cell;'>";
    if ($val['streak'] < 0) {
        echo "L".$val['streak'];
    } else {
        echo "W-".$val['streak'];
    }
    echo "</div></div>";  
}
echo "</div>";
ui_footer();
?>
<style>
* {
font-family:'Roboto', sans-serif;
}

paper-material {
    display: inline-block;
    background-color: #FFFFFF;
    padding: 32px;
    margin: 32px;
}

#standings-table {
  display: table;
  border-collapse: separate;
  font-size: 1vw;
  text-align: center;
}

#standings-table .cell {
  border-top: 1px solid #e5e5e5;
  padding: 16px;
}

#standings-table .thickline .cell {
  border-bottom: 5px solid #000000;
}

#standings-table .header .cell {
    font-weight: bold;
    font-size: 110%;
    padding-top: 0;
    border-top: 0;
}

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

.kvk {
    background-color: #FFAA00;
}

.palc {
    color: #FFFFFF;
    background-color: #900000;
}
</style>
