<?php
require_once "lib.php";
require_once "scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

echo "<html><head>
<title>$year BQBL Week $week </title></head><body>\n";

$bqbl_teamname = bqblTeams($league, $year);
$lineup = getLineups($year, $week, $league);
$matchup = getMatchups($year, $week, $league);

foreach ($matchup as $key => $val) {
    $home_team1 = getPoints($lineup[$key][0], $week, $year);
    $home_team2 = getPoints($lineup[$key][1], $week, $year);
    $away_team1 = getPoints($lineup[$val][0], $week, $year);
    $away_team2 = getPoints($lineup[$val][1], $week, $year);
    
    $home1_total = gameType($year, $week, $lineup[$key][0]) != -1 ? totalPoints($home_team1) : 0;
    $home2_total = gameType($year, $week, $lineup[$key][1]) != -1 ? totalPoints($home_team2) : 0;
    $home_total = $home1_total + $home2_total;
    $away1_total = gameType($year, $week, $lineup[$val][0]) != -1 ? totalPoints($away_team1) : 0;
    $away2_total = gameType($year, $week, $lineup[$val][1]) != -1 ? totalPoints($away_team2) : 0;
    $away_total = $away1_total + $away2_total;
    
    
    echo "<div>";
    echo "$bqbl_teamname[$key]";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Points</th></tr>";
    echo "<tr><td>".$lineup[$key][0]."</td> <td>$home1_total</td></tr>\n";
    echo "<tr><td>".$lineup[$key][1]."</td> <td>$home2_total</td></tr>\n";
    echo "<tr><td>Total</td> <td>$home_total</td></tr>\n";
    echo "</table>";
    
    echo "$bqbl_teamname[$val]";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Points</th></tr>";
    echo "<tr><td>".$lineup[$val][0]."</td> <td>$away1_total</td></tr>\n";
    echo "<tr><td>".$lineup[$val][1]."</td> <td>$away2_total</td></tr>\n";
    echo "<tr><td>Total</td> <td>$away_total</td></tr>\n";
    echo "</table>";
    echo "</div>";
}
?>