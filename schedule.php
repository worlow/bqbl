<?php
require_once "lib.php";
require_once "scoring.php";

$week = currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();

echo "<html><head>
<title>$year BQBL Schedule </title></head><body>\n";

$bqbl_teamname = bqblTeams();
$matchup = array();
$score = array();

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
    if ($i <= $week) {
        $lineup = getLineups($year, $i);
        foreach ($lineup as $team => $starters) {
                $score[$team] =
                    totalPoints(getPoints($starters[0], $i, $year)) + totalPoints(getPoints($starters[1], $i, $year));
        }
    }
    
    echo "<tr><td>Week $i</td>";
    for ($j = 1; $j <= 8; $j++) {
        if ($i <= $week && $score[$j] > $score[$matchup[$i][$j]]) {
            echo '<td bgcolor="green">'.$bqbl_teamname[$matchup[$i][$j]]."</td>";
        } elseif ($i <= $week && $score[$j] < $score[$matchup[$i][$j]]) {
            echo '<td bgcolor="red">'.$bqbl_teamname[$matchup[$i][$j]]."</td>";
        } else {
            echo "<td>".$bqbl_teamname[$matchup[$i][$j]]."</td>";
        }
    }
    echo "</tr>";
}
echo "</table>";
?>