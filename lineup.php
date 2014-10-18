<?php
require_once "lib.php";
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();
$week = isset($_GET['week']) ? $_GET['week'] : currentWeek();
if(isset($_GET['team'])) {
    $user = $_GET['team'];
} elseif(isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    echo "Please set the 'team' parameter!";
    exit(0);
}

echo "<html><head>
<title>$year NFL Team Starts </title>
<style type='text/css'>
tr.thickline td {
border-bottom-width: 6px;
}
</style>
</head>
<body>\n";

$allowediting = ($_SESSION['user'] == $user) && ($week == currentWeek());
$bqblTeam = getBqblTeam($user);
$starts = getStarts($year, $bqblTeam, $league);

$starter1 = $starter2 = "";
$query = "SELECT starter1, starter2 FROM lineup
          WHERE year='$year' AND week='$week' AND league='$league' AND bqbl_team='$bqblTeam';";
$result = pg_query($bqbldbconn, $query);
if(pg_num_rows($result) > 0) {
    list($starter1, $starter2) = pg_fetch_array($result);
}
if($allowediting) echo "<form method='post' action='$_SERVER[PHP_SELF]'>";
echo "<table border=1 style='border-collapse: collapse;'>";
echo "<tr><th>Team</th><th>Starts</th><th>Wk$week Opponent<th>Wk$week Starter 1</th><th>Wk$week Starter 2</th></tr>";

$query = "SELECT nfl_team FROM roster
          WHERE league='$league' AND year='$year' AND bqbl_team='$bqblTeam';";
$result = pg_query($bqbldbconn, $query);
while (list($nflTeam) = pg_fetch_array($result)) {
    $opponent = getOpponent($year, $week, $nflTeam);
    $disabled = !$allowediting || ($opponent == "BYE") ? "disabled" : "";
    $selected1 = $nflTeam == $starter1 ? "checked" : "";
    $selected2 = $nflTeam == $starter2 ? "checked" : "";
    echo "<tr><td>$nflTeam</td>
          <td>$starts[$nflTeam]</td>
          <td>$opponent</td>
          <td align='center'><input type='radio' name='starter1' value='$nflTeam' $disabled $selected1></td>
          <td align='center'><input type='radio' name='starter2' value='$nflTeam' $disabled $selected2></td>
          </tr>\n";
}
echo "</table>";
if($allowediting) echo "<input type='submit' name='submit' value='Update Lineup' />";
elseif(!isset($_SESSION['user'])) echo "<a href='/bqbl/auth/login.php'>Log in to edit lineup</a>";
echo "</form>";


function getStarts($year, $bqblTeam, $league) {
    global $bqbldbconn;
    $starts = array();
    $result = pg_query($bqbldbconn, "SELECT starter1, starter2 FROM lineup 
        WHERE year='$year' AND league='$league' AND bqbl_team='$bqblTeam';");
    while(list($starter1, $starter2) = pg_fetch_array($result)) {
        $starts[$starter1] = isset($starts[$starter1]) ? $starts[$starter1] + 1 : 1;
        $starts[$starter2] = isset($starts[$starter2]) ? $starts[$starter2] + 1 : 1;
    }
    return $starts;
}

function getOpponent($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT home_team, away_team FROM game 
    WHERE (home_team='$team' OR away_team='$team') AND season_year='$year' AND week='$week';";
    $result = pg_query($nfldbconn, $query);
    if(pg_num_rows($result) == 0) return "BYE";
    list($home, $away) = pg_fetch_array($result);
    return $home == $team ? $away : $home;
}
?>