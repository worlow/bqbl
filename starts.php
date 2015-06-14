<?php
require_once "lib/lib.php";

ui_header($title="$year NFL Team Starts");

$bqbl_teamname = bqblTeams($league, $year);
$roster = array();
$starts = array();
$query = "SELECT bqbl_team, nfl_team
    FROM roster WHERE year='$year';";
$result = pg_query($GLOBALS['bqbldbconn'],$query);
while(list($bqbl_team,$nfl_team) = pg_fetch_array($result)) {
    $roster[$bqbl_team][] = $nfl_team;
    $starts[$nfl_team] = 0;
}

for ($i = 1; $i <= $week; $i++) {
    $lineup = getLineups($year, $i, $league);
    foreach ($lineup as $team => $starters) {
        $starts[$starters[0]]++;
        $starts[$starters[1]]++;
    }
}

foreach (bqblTeams($league, $year) as $id => $name) {
    if (($id == 9 && $year > 2013) || ($id == 4 && $year <= 2013)) {
        continue;
    }
    echo "<h4>$name</h4>";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;">';
    echo "<tr><th>Team</th><th>Starts</th></tr>";
    foreach ($roster[$id] as $nfl_team) {
        echo "<tr><td>".$nfl_team."</td> <td>$starts[$nfl_team]</td></tr>\n";
    }
    echo "</table>"; 
}

ui_footer();
?>