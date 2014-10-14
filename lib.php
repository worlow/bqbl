<?php
// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once("lib_db.php");
date_default_timezone_set('America/Los_Angeles');

$nfldbconn = connect_nfldb();
$bqbldbconn = connect_bqbldb();

function currentYear() {
    return 2014;
}

function currentWeek() {
    $now = time();
    $season_start = strtotime("2014-09-04");
    $weeks = 1+floor(($now-$season_start)/(60*60*24*7));
    return $weeks;
}

function databaseModificationTime() {
    $file = "C:\www\bqbl\update_time";
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

function gameType($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT start_time
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
              AND week='$week' AND season_type='Regular';";
    $result = pg_query($nfldbconn,$query);
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

function bqblTeams() {
    global $bqbldbconn;
    $bqbl_teamname = array();
    $query = "SELECT id, team_name FROM users;";
    $result = pg_query($bqbldbconn, $query);
    while(list($id,$team_name) = pg_fetch_array($result)) {
        $bqbl_teamname[$id] = $team_name;
    }
    return $bqbl_teamname;
}

function getLineups($year, $week) {
    global $bqbldbconn;
    $lineup = array();
    $query = "SELECT bqbl_team, starter1, starter2
                FROM lineup
                  WHERE year = $year AND week = $week;";
    $result = pg_query($bqbldbconn, $query);
    while(list($bqbl_team,$starter1,$starter2) = pg_fetch_array($result)) {
        $lineup[$bqbl_team][0] = $starter1;
        $lineup[$bqbl_team][1] = $starter2;
    }
    return $lineup;
}

function getMatchups($year, $week) {
    global $bqbldbconn;
    $matchup = array();
    $query = "SELECT team1, team2
            FROM schedule
              WHERE year = $year AND week = $week;";
    $result = pg_query($bqbldbconn, $query);
    while(list($team1,$team2) = pg_fetch_array($result)) {
        $matchup[$team1] = $team2;
    }
    return $matchup;
}
?>