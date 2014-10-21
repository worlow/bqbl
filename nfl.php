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
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th></th><th>Opponent</th><th>Score</th></tr>";
    for ($i = 1; $i <= 17; $i++) {
        echo "<tr><td>Week $i</td>";
        
        list($home_team,$away_team) = nflMatchup($year, $i, $nfl_team);
        echo $home_team;
        if ($home_team == $nfl_team) {
            echo "<td>".$away_team."</td>";
        } else {
            echo "<td>".$home_team."</td>";
        }
        
        echo "<td>".totalPoints(getPoints($nfl_team, $i, $year))."</td></tr>";
    }
    echo "</table>";
}
?>