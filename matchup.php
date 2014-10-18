<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

echo "<html><head>
<title>$year BQBL Week $week </title></head><body>\n";

$bqbl_teamname = bqblTeams($league, $year);
$lineup = getLineups($year, $week, $league);
$matchup = getMatchups($year, $week, $league);

foreach ($matchup as $bqblteam1 => $bqblteam2) {
    $games[] = array($year, $week, $lineup[$bqblteam1][0]);
    $games[] = array($year, $week, $lineup[$bqblteam1][1]);
    $games[] = array($year, $week, $lineup[$bqblteam2][0]);
    $games[] = array($year, $week, $lineup[$bqblteam2][1]);
}
$gamePoints = getPointsBatch($games);

foreach ($matchup as $bqblteam1 => $bqblteam2) {
    $home_team1 = $gamePoints[$year][$week][$lineup[$bqblteam1][0]];
    $home_team2 = $gamePoints[$year][$week][$lineup[$bqblteam1][1]];
    $away_team1 = $gamePoints[$year][$week][$lineup[$bqblteam2][0]];
    $away_team2 = $gamePoints[$year][$week][$lineup[$bqblteam2][1]];
    
    $home1_total = gameType($year, $week, $lineup[$bqblteam1][0]) != -1 ? totalPoints($home_team1) : 0;
    $home2_total = gameType($year, $week, $lineup[$bqblteam1][1]) != -1 ? totalPoints($home_team2) : 0;
    $home_total = $home1_total + $home2_total;
    $away1_total = gameType($year, $week, $lineup[$bqblteam2][0]) != -1 ? totalPoints($away_team1) : 0;
    $away2_total = gameType($year, $week, $lineup[$bqblteam2][1]) != -1 ? totalPoints($away_team2) : 0;
    $away_total = $away1_total + $away2_total;
    
    
    echo "<div>";
    echo "$bqbl_teamname[$bqblteam1]";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Points</th></tr>";
    echo "<tr><td>".$lineup[$bqblteam1][0]."</td> <td>$home1_total</td></tr>\n";
    echo "<tr><td>".$lineup[$bqblteam1][1]."</td> <td>$home2_total</td></tr>\n";
    echo "<tr><td>Total</td> <td>$home_total</td></tr>\n";
    echo "</table>";
    
    echo "$bqbl_teamname[$bqblteam2]";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Points</th></tr>";
    echo "<tr><td>".$lineup[$bqblteam2][0]."</td> <td>$away1_total</td></tr>\n";
    echo "<tr><td>".$lineup[$bqblteam2][1]."</td> <td>$away2_total</td></tr>\n";
    echo "<tr><td>Total</td> <td>$away_total</td></tr>\n";
    echo "</table>";
    echo "</div>";
}
?>