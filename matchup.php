<?php
require_once "lib.php";
require_once "scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>$year BQBL Week $week </title></head><body>\n";

$bqbl_teamname = array();
$lineup = array();
$matchup = array();

$query = "SELECT id, team_name FROM users;";
$result = pg_query($bqbldbconn, $query);
while(list($id,$team_name) = pg_fetch_array($result)) {
    $bqbl_teamname[$id] = $team_name;
    $lineup[$id] = array();
}

$query = "SELECT bqbl_team, starter1, starter2
            FROM lineup
              WHERE year = $year AND week = $week;";
$result = pg_query($bqbldbconn, $query);
while(list($bqbl_team,$starter1,$starter2) = pg_fetch_array($result)) {
    $lineup[$bqbl_team][0] = $starter1;
    $lineup[$bqbl_team][1] = $starter2;
}

$query = "SELECT team1, team2
            FROM schedule
              WHERE year = $year AND week = $week;";
$result = pg_query($bqbldbconn, $query);
while(list($team1,$team2) = pg_fetch_array($result)) {
    $matchup[$team1] = $team2;
}

foreach ($matchup as $key => $val) {
    $home_team1 = getPoints($lineup[$key][0], $week, $year);
    $home_team2 = getPoints($lineup[$key][1], $week, $year);
    $away_team1 = getPoints($lineup[$val][0], $week, $year);
    $away_team2 = getPoints($lineup[$val][1], $week, $year);
    
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Points</th></tr>";
    echo "<tr><td>".$lineup[$key][0]."</td> <td>".totalPoints($home_team1)."</td></tr>\n";
    echo "<tr><td>".$lineup[$key][1]."</td> <td>".totalPoints($home_team2)."</td></tr>\n";
    echo "</table>";
    
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Points</th></tr>";
    echo "<tr><td>".$lineup[$val][0]."</td> <td>".totalPoints($away_team1)."</td></tr>\n";
    echo "<tr><td>".$lineup[$val][1]."</td> <td>".totalPoints($away_team2)."</td></tr>\n";
    echo "</table>";
}
?>