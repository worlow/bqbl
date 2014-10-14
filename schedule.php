<?php
require_once "lib.php";

$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>$year BQBL Schedule </title></head><body>\n";

$bqbl_teamname = bqblTeams();
$matchup = array();

$query = "SELECT week, team1, team2
            FROM schedule
              WHERE year = $year;";
$result = pg_query($bqbldbconn, $query);
while(list($week,$team1,$team2) = pg_fetch_array($result)) {
    $matchup[$week][$team1] = $team2;
    $matchup[$week][$team2] = $team1;
}

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th></th>";
for ($i = 1; $i <= 8; $i++) {
    echo "<th>$bqbl_teamname[$i]</th>";
}
echo "</tr>";
for ($i = 1; $i <= 14; $i++) {
    echo "<tr><td>Week $i</td>";
    for ($j = 1; $j <= 8; $j++) {
        echo "<td>".$bqbl_teamname[$matchup[$i][$j]]."</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>