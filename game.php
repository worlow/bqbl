<?php
require "lib.php";
date_default_timezone_set('America/Los_Angeles');

$dbconn = connect_db();

$team = pg_escape_string($_GET['team']);
$week = pg_escape_string($_GET['week']);
$year = pg_escape_string($_GET['year']);

// Get the game that's specified
$query = "SELECT gsis_id, home_team, away_team, start_time
		  FROM game
		  WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
			  AND week='$week' AND season_type='Regular';";
$result = pg_query($query);
if(pg_num_rows($result) == 0) { // Bye week
    echo "<h3>This game does not exist.</h3>";
    exit();
}
list($gsis,$hometeam,$awayteam,$gametime) = pg_fetch_array($result,0);
if(strtotime($gametime) > time()) {
    echo "<h3>This game is in the future.</h3>";
    exit();
}

echo $hometeam == $team ? "$awayteam at <b>$hometeam</b>" : "<b>$awayteam</b> at $hometeam";
echo ", Week $week of $year:<br>\n";

print_game_score($team, $week, $year);

function print_game_score($team, $week, $year=2014) {
    $query = "SELECT gsis_id
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
                  AND week='$week' AND season_type='Regular';";
    $gsis = pg_fetch_result(pg_query($query),0);
    $taints = taints($gsis, $team);
    $ints = ints($gsis, $team);
    $fumblesNotLost = fumblesNotLost($gsis, $team);
    $fumblesLost = fumblesLost($gsis, $team);
    $farts = farts($gsis, $team);
    $turnovers = $fumblesLost + $ints;
    $longPasses = longPasses($gsis, $team, 25);
    $passingTDs = passingTDs($gsis, $team);
    $rushingTDs = rushingTDs($gsis, $team);
    $TDs = $passingTDs + $rushingTDs;
    $passingYards = passingYards($gsis, $team);
    $rushingYards = rushingYards($gsis, $team);
    $completionPct = number_format(completionPct($gsis, $team),1);
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
    $points['longPasses'] = $longPasses == 0 ? 10 : 0;
    $points['TDs'] = 0;
        if($TDs == 3) $points['TDs'] = -5;
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

echo <<<END
<table border=2 cellpadding=4 style="border-collapse: collapse;">
<tr><th>Stat Type</th> <th>Stat Value</th> <th>BQBL Points</th></tr>
<tr><td>TAINTS</td> <td>$taints</td> <td>$points[taints]</td></tr>
<tr><td>Interceptions</td> <td>$ints</td> <td>$points[ints]</td></tr>
<tr><td>Fumbles Kept</td> <td>$fumblesNotLost</td> <td>$points[fumblesNotLost]</td></tr>
<tr><td>Fumbles Lost</td> <td>$fumblesLost</td> <td>$points[fumblesLost]</td></tr>
<tr><td>FARTS</td> <td>$farts</td> <td>$points[farts]</td></tr>
<tr><td>Turnovers</td> <td>$turnovers</td> <td>$points[turnovers]</td></tr>
<tr><td>Passes 25+ yds</td> <td>$longPasses</td> <td>$points[longPasses]</td></tr>
<tr><td>TDs</td> <td>$TDs</td> <td>$points[TDs]</td></tr>
<tr><td>Passing Yards</td> <td>$passingYards</td> <td>$points[passingYards]</td></tr>
<tr><td>Rushing Yards</td> <td>$rushingYards</td> <td>$points[rushingYards]</td></tr>
<tr><td>Completion %</td> <td>$completionPct</td> <td>$points[completionPct]</td></tr>
<tr><td>Safeties</td> <td>$safeties</td> <td>$points[safeties]</td></tr>
<tr><td>TAINTS in OT</td> <td>$overtimeTaints</td> <td>$points[overtimeTaints]</td></tr>
<tr><th colspan=2>TOTAL</th> <td>$total_points</td>
</table>
END;
}

function taints($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player
              WHERE gsis_id='$gsis' AND team!='$team' AND defense_int_tds > 0;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function ints($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player
              WHERE gsis_id='$gsis' AND team!='$team' AND defense_int > 0;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function fumblesNotLost($gsis, $team) {
    $query = "SELECT COUNT(*)
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB'
                  AND (fumbles_forced > 0 OR fumbles_notforced > 0) AND fumbles_lost = 0;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function fumblesLost($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                  AND fumbles_lost > 0;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function farts($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='2013090900' AND defense_frec_tds > 0 AND play_id IN 
                  (SELECT play_id
                      FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
                      WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                      AND fumbles_lost > 0);";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function turnovers($gsis, $team) {
    return ints($gsis, $team) + fumblesLost($gsis, $team);
}

function longPasses($gsis, $team, $passLength) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team' AND passing_yds > $passLength;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function passingTDs($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team' AND passing_tds > 0;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function rushingTDs($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                  AND rushing_tds > 0;";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function passingYards($gsis, $team) {
    $query = "SELECT SUM(passing_yds) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function rushingYards($gsis, $team) {
    $query = "SELECT SUM(rushing_yds)
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB';";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}


function passAttempts($gsis, $team) {
    $query = "SELECT SUM(passing_att) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function passCompletions($gsis, $team) {
    $query = "SELECT SUM(passing_cmp) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function completionPct($gsis, $team) {
    return 100*floatval(passCompletions($gsis, $team))/passAttempts($gsis, $team);
}

function safeties($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='2013090900' AND defense_safe > 0 AND play_id IN 
                  (SELECT play_id
                      FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
                      WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                      AND passing_sk > 0);";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

function overtimeTaints($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player LEFT JOIN play USING (gsis_id, play_id)
              WHERE gsis_id='$gsis' AND team!='$team' 
              AND defense_int_tds > 0 AND (\"time\").phase IN ('OT', 'OT2');";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}

/* TODO: Implement this...probably need an index of plays to game score to do it
function overtimeGWDrive($gsis, $team) {
    $query = "SELECT (COUNT(*) > 0)
              FROM play_player LEFT JOIN play USING (gsis_id, play_id) LEFT JOIN game USING (gsis_id)
              WHERE gsis_id='$gsis' AND team='$team' 
              AND defense_int_tds > 0 AND (\"time\").phase IN ('OT', 'OT2');";
    $result = pg_fetch_result(pg_query($query),0);
    return $result;
}
*/











































