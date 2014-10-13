<?php
require_once "lib.php";

function getPoints($team, $week, $year=2014) {
    if (gameType($year, $week, $team) == 2) {
        return array();
    }
    $query = "SELECT gsis_id
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
                  AND week='$week' AND season_type='Regular';";
    $gsis = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    $points = array();
    $points["TAINTs"] = array(taints($gsis, $team), 0);
    $points["Interceptions"] = array(ints($gsis, $team) - $points["TAINTs"][0], 0);
    $points["FARTs"] = array(farts($gsis, $team), 0);
    $points["Fumbles Kept"] = array(fumblesNotLost($gsis, $team),0);
    $points["Fumbles Lost"] = array(fumblesLost($gsis, $team) - $points["FARTs"][0], 0);
    $points["Turnovers"] =
        array($points["Fumbles Lost"][0] + $points["Interceptions"][0] + $points["TAINTs"][0] + $points["FARTs"][0], 0);
    $points["Longest Pass"] = array(longestPass($gsis, $team), 0);
    $points["TDs"] = array(passingTDs($gsis, $team) + rushingTDs($gsis, $team), 0);
    $points["Passing Yards"] = array(passingYards($gsis, $team), 0);
    $points["Rushing Yards"] = array(rushingYards($gsis, $team), 0);
    try {
        $completionPct = number_format(@completionPct($gsis, $team),1);
    } catch (Exception $e) {
        $completionPct = -1;
    }
    $points["Completion Pct"] = array($completionPct, 0);
    $points["Safeties"] = array(safeties($gsis, $team), 0);
    $points["Overtime TAINTs"] = array(overtimeTaints($gsis, $team), 0);
    $points["Benchings"] = array(benchings($year, $week, $team), 0);
    $points["Game Winning Drive"] = array(gameWinningDrive($year, $week, $team), 0);
    $points["Misc. Points"] = array(miscPoints($year, $week, $team), 0);
     $points['TAINTs'][1] = 25*$points['TAINTs'][0];
    $points['Interceptions'][1] = 5*$points['Interceptions'][0];
    $points['Fumbles Kept'][1] = 2*$points['Fumbles Kept'][0];
    $points['Fumbles Lost'][1] = 5*$points['Fumbles Lost'][0];
    $points['FARTs'][1] = 10*$points['FARTs'][0];
    $points['Turnovers'][1] = 0;
        if($points['Turnovers'][0] == 3) $points['Turnovers'][1] = 12;
        elseif($points['Turnovers'][0] == 4) $points['Turnovers'][1] = 16;
        elseif($points['Turnovers'][0] == 5) $points['Turnovers'][1] = 24;
        elseif($points['Turnovers'][0] >= 6) $points['Turnovers'][1] = 50;
    $points['Longest Pass'][1] = $points['Longest Pass'][0] < 25 ? 10 : 0;
    $points['TDs'][1] = 0;
        if($points['TDs'][0] == 0) $points['TDs'][1] = 10;
        elseif($points['TDs'][0] == 3) $points['TDs'][1] = -5;
        elseif($points['TDs'][0] == 4) $points['TDs'][1] = -10;
        elseif($points['TDs'][0] == 5) $points['TDs'][1] = -20;
        elseif($points['TDs'][0] >= 6) $points['TDs'][1] = -40;
    $points['Passing Yards'][1] = 0;
        if($points['Passing Yards'][0] < 100) $points['Passing Yards'][1] = 25;
        elseif($points['Passing Yards'][0] < 150) $points['Passing Yards'][1] = 12;
        elseif($points['Passing Yards'][0] < 200) $points['Passing Yards'][1] = 6;
        elseif($points['Passing Yards'][0] >= 400) $points['Passing Yards'][1] = -12;
        elseif($points['Passing Yards'][0] >= 350) $points['Passing Yards'][1] = -9;
        elseif($points['Passing Yards'][0] >= 300) $points['Passing Yards'][1] = -6;
    $points['Rushing Yards'][1] = $points['Rushing Yards'][0] >= 75 ? -8 : 0;
    $points['Completion Pct'][1] = 0;
        if($points['Completion Pct'][0] < 30) $points['Completion Pct'][1] = 25;
        elseif($points['Completion Pct'][0] < 40) $points['Completion Pct'][1] = 15;
        elseif($points['Completion Pct'][0] < 50) $points['Completion Pct'][1] = 5;
    $points['Safeties'][1] = 20*$points['Safeties'][0];
    $points['Overtime TAINTs'][1] = 50*$points['Overtime TAINTs'][0];
	$points['Benchings'][1] = 35*$points['Benchings'][0];
	$points['Game Winning Drive'][1] = -12*$points['Game Winning Drive'][0];
	$points['Misc. Points'][1] = $points['Misc. Points'][0];
    return $points;
}

function totalPoints($points) {
    $total = 0;
    foreach($points as $key => $val) {
        $total = $total + $val[1];
    }
    return $total;
}

function printGameScore($team, $week, $year=2014) {
    if (gameType($year, $week, $team) == 2) {
        printBlankScore($team);
        return;
    }
    $points = getPoints($team, $week, $year);
    printScore($points);
}

function printBlankScore($team) {
echo <<< END
<table border=2 cellpadding=4 style="border-collapse: collapse;">
<tr><th>Stat Type</th> <th>Stat Value</th> <th>BQBL Points</th></tr>
<tr><th colspan=2>TOTAL</th> <td>0</td>
</table>
END;
}

function printScore($points) {
echo <<< END
<table border=2 cellpadding=4 style="border-collapse: collapse;">
<tr><th>Stat Type</th> <th>Stat Value</th> <th>BQBL Points</th></tr>
END;
foreach ($points as $name => $val) {
    echo "<tr><td>$name</td> <td>$val[0]</td> <td>$val[1]</td></tr>\n";
}
$total = totalPoints($points);
echo "<tr><th colspan=2>TOTAL</th> <td>$total</td>
</table>";
}

function taints($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player
              WHERE gsis_id='$gsis' AND team!='$team' AND defense_int_tds > 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function ints($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player
              WHERE gsis_id='$gsis' AND team!='$team' AND defense_int > 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function fumblesNotLost($gsis, $team) {
    $query = "SELECT COUNT(*)
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB'
                  AND (fumbles_forced > 0 OR fumbles_notforced > 0) AND fumbles_lost = 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function fumblesLost($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                  AND fumbles_lost > 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function farts($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND defense_frec_tds > 0 AND play_id IN 
                  (SELECT play_id
                      FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
                      WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                      AND fumbles_lost > 0);";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function turnovers($gsis, $team) {
    return ints($gsis, $team) + fumblesLost($gsis, $team);
}

function longPasses($gsis, $team, $passLength) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team' AND passing_yds > $passLength;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function longestPass($gsis, $team) {
    $query = "SELECT MAX(passing_yds)
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function longestRush($gsis, $team) {
    $query = "SELECT MAX(rushing_yds)
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team'  AND player.position='QB';";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function passingTDs($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team' AND passing_tds > 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function rushingTDs($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' 
                  AND rushing_tds > 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function passingYards($gsis, $team) {
    $query = "SELECT SUM(passing_yds), SUM(passing_sk_yds) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    list($passingYards, $sackYards) = pg_fetch_array(pg_query($GLOBALS['nfldbconn'],$query));
    return $passingYards + $sackYards;
}

function passingYardsNoSacks($gsis, $team) {
    $query = "SELECT SUM(passing_yds), SUM(passing_sk_yds) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    list($passingYards, $sackYards) = pg_fetch_array(pg_query($GLOBALS['nfldbconn'],$query));
    return $passingYards;
}

function rushingYards($gsis, $team) {
    $query = "SELECT SUM(rushing_yds)
              FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
              WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB';";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function totalYards($gsis, $team) {
    return passingYardsNoSacks($gsis, $team) + rushingYards($gsis, $team);
}

function passAttempts($gsis, $team) {
    $query = "SELECT SUM(passing_att) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function passCompletions($gsis, $team) {
    $query = "SELECT SUM(passing_cmp) 
              FROM play_player 
              WHERE gsis_id='$gsis' AND team='$team';";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function completionPct($gsis, $team) {
    return 100*floatval(passCompletions($gsis, $team))/passAttempts($gsis, $team);
}

function sacks($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player
              WHERE gsis_id='$gsis' AND team='$team' AND passing_sk > 0;";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function safeties($gsis, $team) {
    $query = " SELECT COUNT(*)
                 FROM play_player LEFT JOIN player on play_player.player_id = player.player_id
                 WHERE gsis_id='$gsis' AND play_player.team='$team' AND player.position='QB' AND play_id IN 
                  (SELECT play_id
                     FROM play_player 
                     WHERE gsis_id='$gsis' AND defense_safe > 0);";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function overtimeTaints($gsis, $team) {
    $query = "SELECT COUNT(*) 
              FROM play_player LEFT JOIN play USING (gsis_id, play_id)
              WHERE gsis_id='$gsis' AND team!='$team' 
              AND defense_int_tds > 0 AND (\"time\").phase IN ('OT', 'OT2');";
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    return $result;
}

function benchings($year, $week, $team) {
    $query = "SELECT benching FROM extra_points 
              WHERE nfl_team='$team' AND week='$week' AND year='$year';";
    
    $result = pg_fetch_result(pg_query($GLOBALS['bqbldbconn'],$query),0);
	return $result;
}

function miscPoints($year, $week, $team) {
    $query = "SELECT points FROM extra_points 
              WHERE nfl_team='$team' AND week='$week' AND year='$year';";
    
    $result = pg_fetch_result(pg_query($GLOBALS['bqbldbconn'],$query),0);
	return $result;
}

function gameWinningDrive($year, $week, $team) {
	$query = "SELECT not_winning = last_drive_qualifies = won_game = TRUE as game_winning_drive FROM
(SELECT SUM(score) <= 0 as not_winning
FROM (SELECT CASE WHEN pos_team = '$team' AND note = 'TD' AND def_td = 0 THEN 6 
                  WHEN pos_team != '$team' AND note = 'TD' AND def_td = 0 THEN -6 
                  WHEN pos_team = '$team' AND note = 'TD' AND def_td = 1 THEN -6 
                  WHEN pos_team != '$team' AND note = 'TD' AND def_td = 1 THEN 6
                  WHEN pos_team = '$team' AND note = 'XP' THEN 1
                  WHEN pos_team != '$team' AND note = 'XP' THEN -1
                  WHEN pos_team = '$team' AND note = 'FG' THEN 3
                  WHEN pos_team != '$team' AND note = 'FG' THEN -3
                  WHEN pos_team = '$team' AND note = 'SAF' THEN -2
                  WHEN pos_team != '$team' AND note = 'SAF' THEN 2
                  WHEN pos_team = '$team' AND note = '2PS' THEN 2
                  WHEN pos_team != '$team' AND note = '2PS' THEN -2
                  WHEN pos_team = '$team' AND note = '2PR' THEN 2
                  WHEN pos_team != '$team' AND note = '2PR' THEN -2
                  ELSE 0 
             END AS score
      FROM (SELECT note, pos_team, SUM(defense_int_tds + defense_frec_tds + defense_misc_tds) AS def_td
            FROM (SELECT gsis_id 
            	  FROM game 
            	  WHERE (home_team = '$team' 
            		     OR away_team = '$team') 
            	        AND week = $week 
            			AND season_year = $year 
            			AND season_type = 'Regular')
            	  AS g 
            NATURAL JOIN (SELECT * 
            	          FROM play 
            			  WHERE (note = 'TD' 
            				     OR note = 'XP' 
            					 OR note = 'FG' 
            					 OR note = 'SAF' 
            					 OR note = '2PS' 
            					 OR note = '2PR') 
            					AND (((\"time\").phase != 'Q4') 
            					     OR ((\"time\").phase = 'Q4' 
            						     AND (\"time\").elapsed <= 780))) 
            	          AS p
            NATURAL JOIN play_player
            GROUP BY time, note, description, pos_team
            ORDER BY time) AS pl) as sc) AS nw,
(SELECT CAST(CASE WHEN pos_team = '$team' AND (note = 'TD' OR note = 'FG') AND def_td = 0 THEN 1 
            ELSE 0 
	   END AS bool) AS last_drive_qualifies
FROM (SELECT note, pos_team, SUM(defense_int_tds + defense_frec_tds + defense_misc_tds) AS def_td
	  FROM (SELECT gsis_id 
			FROM game 
			WHERE (home_team = '$team' 
				   OR away_team = '$team') 
				  AND week = $week
				  AND season_year = $year 
				  AND season_type = 'Regular')
			AS g 
	  NATURAL JOIN (SELECT * 
					FROM play 
					WHERE (note = 'TD' 
						   OR note = 'FG' 
						   OR note = 'SAF'))
					AS p
	  NATURAL JOIN play_player
	  GROUP BY time, note, description, pos_team
	  ORDER BY time DESC) AS pl 
LIMIT 1) AS ldq,
(SELECT CAST(CASE WHEN home_team = '$team' AND home_score > away_score THEN 1 
            WHEN away_team = '$team' AND away_score > home_score THEN 1 
            ELSE 0 
       END AS bool) AS won_game
FROM game 
WHERE (home_team = '$team' 
	   OR away_team = '$team') 
	  AND week = $week 
	  AND season_year = $year 
	  AND season_type = 'Regular') AS wg;";
	  
    $result = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
  	return $result ? 1 : 0;
}


function defenseScore($points) {
    $total = totalPoints($points);
    $total -= $points["Misc. Points"][1];
    $total -= $points["Benchings"][1];
    return $total;
}
