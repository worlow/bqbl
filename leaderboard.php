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
$grandtotals = array();
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $grandtotals[$hometeam] = 0;
    $grandtotals[$awayteam] = 0;
    $totals[$hometeam] = totalScore($hometeam, $week, $year);
    $totals[$awayteam] = totalScore($awayteam, $week, $year);    
}
arsort($totals);

echo '<table border=2 cellpadding=4 style="border-collapse: collapse;">';
echo "<tr><th>Team Name</th><th>Total Points</th></tr>";
foreach ($totals as $key => $val) {
    echo "<tr><th>$key</th><th>$val</th></tr>";
}
echo "</table>";


for ($x=1; $x<=$week; $x++) {
    $query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$x' AND season_type='Regular'
          ORDER BY start_time ASC;";
    $result = pg_query($GLOBALS['nfldbconn'],$query);
    while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
        $grandtotals[$hometeam] += totalScore($hometeam, $x, $year);
        $grandtotals[$awayteam] += totalScore($awayteam, $x, $year);    
    }
}
arsort($grandtotals);

echo '<table border=2 cellpadding=4 style="border-collapse: collapse;">';
echo "<tr><th>Team Name</th><th>Total Points</th></tr>";
foreach ($grandtotals as $key => $val) {
    echo "<tr><th>$key</th><th>$val</th></tr>";
}
echo "</table>";

function totalScore($team, $week, $year=2014) {
    if (gameType($year, $week, $team) == 2) {
        return 0;
    }
    $query = "SELECT gsis_id
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
                  AND week='$week' AND season_type='Regular';";
    $gsis = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    $taints = taints($gsis, $team);
    $ints = ints($gsis, $team) - $taints;
    $farts = farts($gsis, $team);
    $fumblesNotLost = fumblesNotLost($gsis, $team);
    $fumblesLost = fumblesLost($gsis, $team) - $farts;
    $turnovers = $fumblesLost + $ints + $taints + $farts;
    $longestPass = longestPass($gsis, $team);
    $passingTDs = passingTDs($gsis, $team);
    $rushingTDs = rushingTDs($gsis, $team);
    $TDs = $passingTDs + $rushingTDs;
    $passingYards = passingYards($gsis, $team);
    $rushingYards = rushingYards($gsis, $team);
    try {
        $completionPct = number_format(@completionPct($gsis, $team),1);
    } catch (Exception $e) {
        $completionPct = -1;
    }
    $safeties = safeties($gsis, $team);
    $overtimeTaints = overtimeTaints($gsis, $team);

    $points = array();
    $points['taints'] = 25*$taints;
    $points['ints'] = 5*$ints;
    $points['fumblesNotLost'] = 2*$fumblesNotLost;
    $points['fumblesLost'] = 5*$fumblesLost;
    $points['farts'] = 10*$farts;
    $points['turnovers'] = 0;
        if($turnovers == 3) $points['turnovers'] = 12;
        elseif($turnovers == 4) $points['turnovers'] = 16;
        elseif($turnovers == 5) $points['turnovers'] = 24;
        elseif($turnovers >= 6) $points['turnovers'] = 50;
    $points['longestPass'] = $longestPass < 25 ? 10 : 0;
    $points['TDs'] = 0;
        if($TDs == 0) $points['TDs'] = 10;
        elseif($TDs == 3) $points['TDs'] = -5;
        elseif($TDs == 4) $points['TDs'] = -10;
        elseif($TDs == 5) $points['TDs'] = -20;
        elseif($TDs >= 6) $points['TDs'] = -40;
    $points['passingYards'] = 0;
        if($passingYards < 100) $points['passingYards'] = 25;
        elseif($passingYards < 150) $points['passingYards'] = 12;
        elseif($passingYards < 200) $points['passingYards'] = 6;
        elseif($passingYards > 400) $points['passingYards'] = -12;
        elseif($passingYards > 350) $points['passingYards'] = -9;
        elseif($passingYards > 300) $points['passingYards'] = -6;
    $points['rushingYards'] = $rushingYards >= 75 ? -8 : 0;
    $points['completionPct'] = 0;
        if($completionPct < 30) $points['completionPct'] = 25;
        elseif($completionPct < 40) $points['completionPct'] = 15;
        elseif($completionPct < 50) $points['completionPct'] = 5;
    $points['safeties'] = 20*$safeties;
    $points['overtimeTaints'] = 50*$overtimeTaints;
    $total_points = array_sum($points);
    return $total_points;
}
?>
