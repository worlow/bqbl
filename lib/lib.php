<?php
// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);
session_start();
require_once("lib_db.php");
require_once("lib_auth.php");
date_default_timezone_set('America/Los_Angeles');

$nfldbconn = connect_nfldb();
$bqbldbconn = connect_bqbldb();

$CURRENT_YEAR = 2014;
$WEEK_1_THURS_DATE = "2014-09-04";
$DB_UPDATE_INTERVAL = 60;  # seconds
$REG_SEASON_END_WEEK = 14;

function currentYear() {
    global $CURRENT_YEAR;
    return $CURRENT_YEAR;
}

function currentWeek() {
    global $WEEK_1_THURS_DATE;
    $now = time();
    $season_start = strtotime($WEEK_1_THURS_DATE) - 2*60*60*24;  # Tuesday
    $weeks = 1+floor(($now-$season_start)/(60*60*24*7));
    return $weeks;
}

function currentCompletedWeek() {
    return currentWeek() - 1;
}

function weekCutoffTime($week) {
    global $WEEK_1_THURS_DATE;
    $now = time();
    $season_start = strtotime($WEEK_1_THURS_DATE . " 17:30:00");  # Tuesday
    return $season_start + 7*24*60*60*($week - 1);
}

function isGameFinished($gsis_id) {
    global $nfldbconn;
    $query = "SELECT finished FROM game WHERE gsis_id='$gsis_id';";
    return pg_fetch_result(pg_query($nfldbconn, $query), 0);
}

function getLeague() {
    $year=currentYear();
    if(isset($_SESSION['league'])) {
        return $_SESSION['league'];
    } elseif(isset($_SESSION['user'])) {
        $query = "SELECT league FROM membership JOIN users ON membership.bqbl_team=users.id WHERE username='$_SESSION[user]' AND year='$year';";
        $league = pg_fetch_result(pg_query($GLOBALS['bqbldbconn'], $query),0);
        return ($league != "") ? $league : "nathans";
    } else return "nathans";
}

function getDomain() {
    return "bqbl.duckdns.org";
}

function getBqblTeam($user) {
    global $bqbldbconn;
    return pg_fetch_result(pg_query($bqbldbconn, "SELECT id FROM users WHERE username='$user';"), 0);
}

function databaseModificationTime() {
    $file = "C:\\www\\bqbl\\update_time";
    return filemtime($file);
}

function gameTypeById($gsis) {
global $nfldbconn;
    $query = "SELECT start_time
      FROM game
      WHERE gsis_id='$gsis';";
    $result = pg_query($nfldbconn, $query);
    if(pg_num_rows($result) == 0) { // Bye week
        return -1;
    }
    list($gametime) = pg_fetch_array($result,0);
    if(strtotime($gametime) > time()) {
        return 2; // Future game
    }
    return 1; // Current or past game
}

function nflMatchup($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT home_team, away_team
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
              AND week='$week' AND season_type='Regular';";
    $result = pg_query($GLOBALS['nfldbconn'], $query);

    if(pg_num_rows($result) == 0) { // Bye week
        return array();
    }
    return list($home_team,$away_team) = pg_fetch_array($result,0);
}

function gameType($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT start_time
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
              AND week='$week' AND season_type='Regular';";
    $result = pg_query($GLOBALS['nfldbconn'], $query);
    if(pg_num_rows($result) == 0) { // Bye week
        return -1;
    }
    list($gametime) = pg_fetch_array($result,0);
    if(strtotime($gametime) > time()) {
        return 2; // Future game
    }
    return 1; // Current or past game
}

function nflTeams() {
    global $bqbldbconn;
    $teams = array();
    $query = "SELECT id from nfl_teams;";
    $result = pg_query($bqbldbconn, $query);
    while(list($team) = pg_fetch_array($result)) {
        $teams[] = $team;
    }
    return $teams;
}

function nflIdToCityTeamName($id) {
    global $bqbldbconn;
    $query = "SELECT city, name FROM nfl_teams WHERE id='$id';";
    $result = pg_query($bqbldbconn, $query);
    return pg_fetch_array($result);
}

function bqblTeams($league, $year, $sortByDraftOrder=false) {
    global $bqbldbconn;
    $bqbl_teamname = array();
    $orderClause = $sortByDraftOrder ? "ORDER BY draft_order ASC" : "ORDER BY team_name ASC";
    $query = "SELECT bqbl_team, team_name 
              FROM membership JOIN users ON membership.bqbl_team=users.id 
              WHERE league='$league' AND year='$year' $orderClause;";
    $result = pg_query($bqbldbconn, $query);
    while(list($id,$team_name) = pg_fetch_array($result)) {
        $bqbl_teamname[$id] = $team_name;
    }
    return $bqbl_teamname;
}


function getRosters($year, $league, $playoffs=false) {
    global $bqbldbconn;
    $roster = array();
    foreach (nflTeams() as $team) {
        $roster[$team] = array();
    }
    $roster_table = $playoffs ? "playoff_roster" : "roster";
    $query = "SELECT bqbl_team, nfl_team
              FROM $roster_table
              WHERE year = $year AND league='$league';";
    $result = pg_query($bqbldbconn, $query);
    while(list($bqbl_team, $nfl_team) = pg_fetch_array($result)) {
        $roster[$bqbl_team][] = $nfl_team;
    }
    return $roster;
}

function getLineups($year, $week, $league) {
    global $bqbldbconn;
    $lineup = array();
    foreach (bqblTeams($league, $year) as $id => $name) {
        $lineup[$id] = array();
    }
    $query = "SELECT bqbl_team, starter1, starter2
                FROM lineup
                  WHERE year = $year AND week = $week AND league='$league';";
    $result = pg_query($bqbldbconn, $query);
    while(list($bqbl_team,$starter1,$starter2) = pg_fetch_array($result)) {
        $lineup[$bqbl_team][] = $starter1;
        $lineup[$bqbl_team][] = $starter2;
    }
    return $lineup;
}

function getMatchups($year, $week, $league) {
    global $bqbldbconn;
    $matchup = array();
    $query = "SELECT team1, team2
            FROM schedule
              WHERE year = $year AND week = $week AND league='$league';";
    $result = pg_query($bqbldbconn, $query);
    while(list($team1,$team2) = pg_fetch_array($result)) {
        $matchup[$team1] = $team2;
    }
    return $matchup;
}

function gameTime($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT start_time FROM game
              WHERE year='$year' AND week='$week' AND (home_team='$team' OR away_team='$team');";
    $result = pg_query($nfldbconn, $query);
    if(pg_num_rows($result) == 0) {
        return 0;
    } else {
        return pg_fetch_result($result, 0);
    }
}

function bqblTeamStrToInt($bqblTeam) {
    global $bqbldbconn;
    $query = "SELECT id FROM users WHERE username='$bqblTeam';";
    return pg_fetch_result(pg_query($bqbldbconn, $query), 0);
}

function isTed($bqblTeam) {
    global $bqbldbconn;
    $query = "SELECT * FROM users WHERE id='$bqblTeam' AND username LIKE 'eltedador%';";
    return pg_num_rows(pg_query($bqbldbconn, $query)) > 0;
}

function footer() {
    global $bqbldbconn, $nfldbconn;
    pg_close($bqbldbconn);
    pg_close($nfldbconn);
}
?>