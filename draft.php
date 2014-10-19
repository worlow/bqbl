<?php
require_once "lib/lib.php";
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? pg_escape_string($_GET['league']) : getLeague();

if(isset($_POST['submit'])) {
    $teamPicked = pg_escape_string($_POST['pick']);
    $teamPicking = pg_escape_string($_POST['bqbl_team']);
    $pickNumber = pg_escape_string($_POST['draft_position']);
    $query = "INSERT INTO roster (year, bqbl_team, nfl_team, draft_position, league)
              VALUES ($year, $teamPicking, $teamPicked, $pickNumber, $league);";
    pg_query($bqbldbconn, $query);
}
$query = "SELECT id, city, name FROM nfl_teams
          WHERE id NOT IN 
                (SELECT nfl_team FROM roster WHERE year='$year' AND league='$league');";
$result = pg_query($bqbldbconn, $query);
$pickNum = 33 - pg_num_rows($result);
$firstPick = getFirstPick($pickNum);
$query2 = "SELECT bqbl_team, team_name FROM membership JOIN users ON membership.bqbl_team=users.id
          WHERE year='$year' AND league='$league' AND draft_order='$firstPick';";
list($bqblTeam, $teamName) = pg_fetch_array(pg_query($bqbldbconn, $query2));
echo "<h1>Pick $pickNum: <span style='color:red;'>$teamName</span> is on the clock</h1>
<div id='selection'>";
echo "<form action='$_SERVER[PHP_SELF]?year=$year&league=$league' method=post>\n";
echo "<table><tr><th>Team</th><th>Pick</th></tr>\n";
while(list($id, $city, $name) = pg_fetch_array($result)) {
    echo "<tr><td>$city $name</td><td><input type=radio name='pick' value='$id'><br>";
}
echo "</table>\n";
echo "<input type=hidden name='bqbl_team' value='$bqblTeam' />
<input type=hidden name='draft_position' value='$pickNum' />\n";
/*echo "<select name='bqbl_team'>\n";
foreach(bqblTeams($league, $year, true) as $teamId=>$teamName) {
    $selected = $bqblTeam == $teamId ? 'selected' : '';
    echo "<option value='$teamId' $selected>$teamName</option>\n";
}*/
echo "<input type=submit name='submit'></form>\n";
echo "</div><div id='rosters'>
<table border=1 style='border-collapse:collapse;'><tr><th></th><th>Team 1</th><th>Team 2</th><th>Team 3</th><th>Team 4</th></tr>\n";
foreach(bqblTeams($league, $year, true) as $teamId=>$teamName) {
    $query = "SELECT city, name FROM roster JOIN nfl_teams on nfl_teams.id=roster.nfl_team
              WHERE bqbl_team='$teamId' AND year='$year' AND league='$league'
              ORDER BY draft_position;";
    $result = pg_query($bqbldbconn, $query);
    $teams = array();
    while(list($city, $name) = pg_fetch_array($result)) {
        $teams[] = "$city $name";
    }
    echo "<tr><td style='font-weight:bold;'>$teamName</td><td>$teams[0]</td><td>$teams[1]</td><td>$teams[2]</td><td>$teams[3]</td></tr>\n";
}

echo "<style type='text/css'>
#selection {
    float:left;
    margin-right:40px;
}
#rosters {
    float:left;
}
</style>
";

function getFirstPick($pick) {
    if ($pick <= 8) return $pick;
    elseif ($pick <= 16) return 17-$pick;
    elseif ($pick <= 24) return $pick-16;
    elseif ($pick <= 32) return 33-$pick;
    else return -1;
}
?>