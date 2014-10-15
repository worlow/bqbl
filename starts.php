<?php
require_once "lib.php";

$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$week = currentWeek();

echo "<html><head>
<title>$year NFL Team Starts </title>
<style type='text/css'>
tr.thickline td {
border-bottom-width: 6px;
}
</style>
</head><body>\n";

$bqbl_teamname = bqblTeams();
$roster = array();
$starts = array();
$query = "SELECT bqbl_team, nfl_team
    FROM roster;";
$result = pg_query($GLOBALS['bqbldbconn'],$query);
while(list($bqbl_team,$nfl_team) = pg_fetch_array($result)) {
    $roster[$bqbl_team][] = $nfl_team;
    $starts[$nfl_team] = 0;
}

for ($i = 1; $i <= $week; $i++) {
    $lineup = getLineups($year, $i);
    foreach ($lineup as $team => $starters) {
        $starts[$starters[0]]++;
        $starts[$starters[1]]++;
    }
}

foreach (bqblTeams() as $id => $name) {
    echo "$name";
    echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
    echo "<tr><th>Team</th><th>Starts</th></tr>";
    foreach ($roster[$id] as $nfl_team) {
        echo "<tr><td>".$nfl_team."</td> <td>$starts[$nfl_team]</td></tr>\n";
    }
    echo "</table>"; 
}
?>