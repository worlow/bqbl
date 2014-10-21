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
    $total = 0;
    list($city, $name) = nflIdToCityTeamName($nfl_team);
    echo "<h2>$city $name</h2>";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th></th><th>Opponent</th><th>Score</th></tr>";
    for ($i = 1; $i <= 17; $i++) {
        echo "<tr><td>Week $i</td>";
        
        list($home_team,$away_team) = nflMatchup($year, $i, $nfl_team);
        if ($home_team == $nfl_team) {
            echo "<td><a href='/bqbl/nfl.php?team=$away_team&year=$year'>vs $away_team</a></td>";
        } elseif ($away_team == $nfl_team) {
            echo "<td><a href='/bqbl/nfl.php?team=$home_team&year=$year'>@ $home_team</a></td>";
        } else {
            echo "<td>BYE</td>";
        }
        $points = totalPoints(getPoints($nfl_team, $i, $year));
        $total += $points;
        echo "<td>$points</td></tr>";
    }
    echo "<tr><td>Total</td><td> -- </td><td>$total</td></tr>";
    echo "</table>";
    echo "<div><a href='/bqbl/nfl.php?year=$year'>Back</a></div>";
}
?>