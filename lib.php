<?php
// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);
date_default_timezone_set('America/Los_Angeles');

$dbconn = connect_db();
	function connect_db() {
		$dbconn = pg_connect("host=localhost dbname=nfldb user=nfldb password=password")
			or die('Could not connect: ' . pg_last_error());
		return $dbconn;
	}
    
    function gameTypeById($gsis) {
        $query = "SELECT start_time
		  FROM game
		  WHERE gsis_id='$gsis';";
        $result = pg_query($query);
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
        $query = "SELECT start_time
                  FROM game
                  WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
                  AND week='$week' AND season_type='Regular';";
        $result = pg_query($query);
        if(pg_num_rows($result) == 0) { // Bye week
            return -1;
        }
        list($gametime) = pg_fetch_array($result,0);
        if(strtotime($gametime) > time()) {
            return 2; // Future game
        }
        return 1; // Current or past game
    }
?>