<?php
require_once "lib.php";

function getPoints($team, $week, $year=2014) {
    if ($year < 2015) {
        return getPointsV2($team, $week, $year);
    }
    return getPointsV2($team, $week, $year);
}

function getPointsV1($team, $week, $year=2014) {
    $points = array();
    if (gameType($year, $week, $team) == 2 || gameType($year, $week, $team) == -1) {
        $points["Game Winning Drive"] =
        $points["Benchings"] =
        $points["Overtime TAINTs"] =
        $points["Safeties"] =
        $points["Completion Pct"] =
        $points["Rushing Yards"] =
        $points["Passing Yards"] =
        $points["TDs"] =
        $points["Longest Pass"] =
        $points["Turnovers"] =
        $points["Fumbles Lost"] =
        $points["Fumbles Kept"] =
        $points["FARTs"] =
        $points["Interceptions"] =
        $points["TAINTs"] =
        '';
        $miscpoints = miscPoints($year, $week, $team);
        $points["Misc. Points"] = array($miscpoints, $miscpoints);
        return $points;
    }
    $query = "SELECT gsis_id
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
                  AND week='$week' AND season_type='Regular';";
    $gsis = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
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
    $points["Game Winning Drive"] = array(gameWinningDrive($gsis, $team), 0);
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

function getPointsV2($team, $week, $year=2015) {
    $points = array();
    if (gameType($year, $week, $team) == 2 || gameType($year, $week, $team) == -1) {
        $points["Game Winning Drive"] =
        $points["Benchings"] =
        $points["Overtime TOs"] =
        $points["Safeties"] =
        $points["Completion Pct"] =
        $points["Total Yards"] =
        $points["TDs"] =
        $points["Longest Play"] =
        $points["Long Plays"] =
        $points["Sacks"] =
        $points["Turnovers"] =
        $points["Fumbles Lost"] =
        $points["Fumbles Kept"] =
        $points["FARTs"] =
        $points["Interceptions"] =
        $points["TAINTs"] =
        '';
        $miscpoints = miscPoints($year, $week, $team);
        $points["Misc. Points"] = array($miscpoints, $miscpoints);
        return $points;
    }
    $query = "SELECT gsis_id
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
                  AND week='$week' AND season_type='Regular';";
    $gsis = pg_fetch_result(pg_query($GLOBALS['nfldbconn'],$query),0);
    $points["TAINTs"] = array(taints($gsis, $team), 0);
    $points["Interceptions"] = array(ints($gsis, $team) - $points["TAINTs"][0], 0);
    $points["FARTs"] = array(farts($gsis, $team), 0);
    $points["Fumbles Kept"] = array(fumblesNotLost($gsis, $team),0);
    $points["Fumbles Lost"] = array(fumblesLost($gsis, $team) - $points["FARTs"][0], 0);
    $points["Turnovers"] =
        array($points["Fumbles Lost"][0] + $points["Interceptions"][0] + $points["TAINTs"][0] + $points["FARTs"][0], 0);
    $points["Longest Play"] = array(longestPass($gsis, $team), 0);
    $points["TDs"] = array(passingTDs($gsis, $team) + rushingTDs($gsis, $team), 0);
    $points["Total Yards"] = array(passingYards($gsis, $team), 0)
        + array(rushingYards($gsis, $team), 0);
    try {
        $completionPct = number_format(@completionPct($gsis, $team),1);
    } catch (Exception $e) {
        $completionPct = -1;
    }
    $points["Completion Pct"] = array($completionPct, 0);
    $points["Safeties"] = array(safeties($gsis, $team), 0);
    $points["Overtime TOs"] = array(overtimeTaints($gsis, $team), 0);
    $points["Benchings"] = array(benchings($year, $week, $team), 0);
    $points["Game Winning Drive"] = array(gameWinningDrive($gsis, $team), 0);
    $points["Misc. Points"] = array(miscPoints($year, $week, $team), 0);

    // TOs
    $points['TAINTs'][1] = 20*$points['TAINTs'][0];
    $points['FARTs'][1] = 20*$points['FARTs'][0];
    $points['Safeties'][1] = 20*$points['Safeties'][0];
    
    $points['Interceptions'][1] = 5*$points['Interceptions'][0];
    $points['Fumbles Kept'][1] = 2*$points['Fumbles Kept'][0];
    $points['Fumbles Lost'][1] = 5*$points['Fumbles Lost'][0];
    
    $points['Turnovers'][1] = 0;
        if($points['Turnovers'][0] >= 2)
            $points['Turnovers'][1] = 2 ** $points['Turnovers'][0];
    
    $points['Overtime TAINTs'][1] = 50*$points['Overtime TAINTs'][0];
    
    // Longest Play
    $points['Longest Play'][1] = 0;
        if($points['Longest Play'][0] <= 30) $points['Longest Play'][1] = 30 - $points['Longest Play'][0];

    // TDs
    $points['TDs'][1] = 0;
        if($points['TDs'][0] == 0) $points['TDs'][1] = 10;
        elseif($points['TDs'][0] >= 3) $points['TDs'][1] = -5 * (2 ** ($points['TDs'][0] - 3));

    // Sacks
    
    // Completion Percentage
    $points['Completion Pct'][1] = (-1) ** intval($points['Completion Pct'][0] / 60)
        * (intval($points['Completion Pct'][0] / 5) - 12) ** 2;

    // Total Yards
    $yards = $points['Total Yards'][0];
    if ($yards >= 250)
        $points['Total Yards'][1] = -2 * fibbi(intval($yards / 50) - 5) + 0;
    else
        $points['Total Yards'][1] = fibbi(12 - intval($yards / 25));
    
    // Others
	$points['Benchings'][1] = 35*$points['Benchings'][0];
	$points['Game Winning Drive'][1] = -12*$points['Game Winning Drive'][0];
	$points['Misc. Points'][1] = $points['Misc. Points'][0];
    return $points;
}

function fibbi($num) {
    $phi = (1 + sqrt(5))/2;
    return round((($phi ** $num - (1 - $phi) ** $num)) / sqrt(5));
}

function getPointsOnlyMisc($points) {
    $newPoints = array();
    foreach ($points as $key => $val) {
        if ($key == 'Misc. Points') {
            $newPoints[$key] = $val;
        }
        else {
            $newPoints[$key] = '';
        }
    }
    return $newPoints;
}

function totalPoints($points) {
    $total = 0;
    foreach($points as $key => $val) {
        $total = $total + $val[1];
    }
    return $total;
}

function printGameScore($points, $team, $week, $year=2014) {
    if (gameType($year, $week, $team) == 2) {
        printBlankScore($team);
        return;
    }
    printScore($points);
}

function printBlankScore($team) {
echo <<< END
<table class="score" border=2 cellpadding=4 style="border-collapse: collapse;">
<tr><th>Stat Type</th> <th>Stat Value</th> <th>BQBL Points</th></tr>
<tr><th colspan=2>TOTAL</th> <td>0</td>
</table>
END;
}

function printScore($points) {
echo <<< END
<table class="score" border=2 cellpadding=4 style="border-collapse: collapse;">
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

# Returns 1 if the last scoring play of the game was scored by $team,
# it was an offensive score, that score put the team ahead, and it was
# either in OT or the last 2 minutes of the game
function gameWinningDrive($gsis, $team) {
    global $nfldbconn;
    # Gets final game score from database
    $query = "SELECT home_team, home_score, away_team, away_score
              FROM game WHERE gsis_id='$gsis';";
    list($home_team, $home_score, $away_team, $away_score) = pg_fetch_array(pg_query($GLOBALS['nfldbconn'],$query));
    $team_score = ($team==$home_team) ? $home_score : $away_score;
    $other_score = ($team==$home_team) ? $away_score : $home_score;
    if ($team_score <= $other_score) return 0;
    
    $query = 
    "SELECT play_score_offense(play.gsis_id, play.play_id, player_id) AS score_offense, team, drive_id, (\"time\").phase, (\"time\").elapsed
     FROM (SELECT play_has_score(gsis_id, play_id, player_id) AS has_score,
                  gsis_id, play_id, player_id, team
           FROM play_player
           WHERE gsis_id='$gsis') AS scores JOIN play ON (play.gsis_id = scores.gsis_id AND play.play_id = scores.play_id)
     WHERE has_score
     ORDER BY play.play_id DESC
     LIMIT 1";
    list($score_offense, $scoringteam, $drive, $phase, $elapsed) = pg_fetch_array(pg_query($GLOBALS['nfldbconn'],$query));
    # team in question scored
    if ($scoringteam == $team) {
        # OT or last 2 minutes
        if ($phase == "OT" || $phase == "OT2" || ($phase == "Q4" && $elapsed >= (15*60-2*60))) {
            # team was not winning before this play
            if ($team_score-offensivePlayScoreToDriveScore($score_offense) <= $other_score) {
                $query = "SELECT COUNT(*) FROM play_player
                          WHERE gsis_id='$gsis' AND drive_id='$drive' AND (passing_att > 0 OR rushing_att > 0 OR passing_sk > 0 OR fumbles_rec > 0);";
                if (pg_fetch_result(pg_query($GLOBALS['nfldbconn'], $query), 0) > 0) {
                    return 1;
                }
            }
        }
    }
    return 0;
}

# We get the last scoring play, so if it is an extra point or 2pt conversion we add 6
function offensivePlayScoreToDriveScore($last_play_score) {
    if ($last_play_score == 1) return 7;
    if ($last_play_score == 2) return 8;
    return $last_play_score;
}

function defenseScore($points) {
    $total = totalPoints($points);
    $total -= $points["Misc. Points"][1];
    $total -= $points["Benchings"][1];
    return $total;
}


function getPointsBatch($games) {
    global $bqbldbconn, $nfldbconn;
    $points = array();
    foreach($games as $key=>list($year, $week, $team)) { 
        if(!isset($points[$year])) $points[$year] = array();
        if(!isset($points[$year][$week])) $points[$year][$week] = array();
    }

    $pool = new Pool(8, \PointsWorker::class);
    $work = array();
    array_unshift($games, $games[0]);  // fixes weird bug where first element would be replaced by last element
    foreach($games as $game) {
        $newWork = new PointsWork($game);
        $work[] = $newWork;
        $pool->submit($newWork);
    }
    $pool->shutdown();
    foreach($work as $gameWork) {
        $total = totalPoints($gameWork->points);
        $points[$gameWork->year][$gameWork->week][$gameWork->team] = $gameWork->points;
        unset($gameWork);
    }
    unset($pool);
    return $points;
}

class PointsWork extends Threaded {
    public $year;
    public $week;
    public $team;
    public $points;

    public function __construct($game) {
        list($year, $week, $team) = $game;
        $this->year=$year;
        $this->week=$week;
        $this->team=$team;
        $this->points=null;
    }

    public function run() {
        if(!isset($GLOBALS['bqbldbconn'])) $GLOBALS['bqbldbconn'] = connect_bqbldb();
        if(!isset($GLOBALS['nfldbconn'])) $GLOBALS['nfldbconn'] = connect_nfldb();
        date_default_timezone_set('America/Los_Angeles');
        if($this->week > 0) {
            $this->points = getPoints($this->team, $this->week, $this->year);
            $total = totalPoints($this->points);
        }
        #pg_close($GLOBALS['bqbldbconn']);
        #pg_close($GLOBALS['nfldbconn']);
        exit();
    }
}

class PointsWorker extends Worker {
    public function run() {}
}
