<?php
require_once "lib.php";
require_once "scoring.php";
$team = pg_escape_string($_GET['team']);
$week = pg_escape_string($_GET['week']);
$year = pg_escape_string($_GET['year']);

// Get the game that's specified
$gameType = gameType($year, $week, $team);
if ($gameType == -1) {
    echo "<h3>This game does not exist</h3>\n";
    exit();
} else if ($gameType == 2) {
    echo "<h3>This game is in the future.</h3>\n";
    printBlankScore();
    exit();
}

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
			  AND week='$week' AND season_type='Regular';";
$result = pg_query($nfldbconn,$query);
list($gsis,$hometeam,$awayteam) = pg_fetch_array($result,0);

echo $hometeam == $team ? "$awayteam at <b>$hometeam</b>" : "<b>$awayteam</b> at $hometeam";
echo ", Week $week of $year:<br>\n";

printGameScore($team, $week, $year);



















