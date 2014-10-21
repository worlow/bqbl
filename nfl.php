<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$nfl_team = isset($_GET['team']) ? pg_escape_string($_GET['team']) : null;

if ($nfl_team == null) {
   foreach (nflTeams() as $team) {
       echo "<div><a href='/bqbl/nfl.php?team=$team&year=$year'>$team</a></div>";
   }
} else {
    echo "Hello";
}
?>