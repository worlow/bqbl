<?php
// Report simple running errors
error_reporting(E_ERROR);
require_once("lib.php");
if(!isset($_GET['week'])) {
    echo "ERROR: week variable not set in URL";
    exit(0);
}
$year = isset($_GET['year']) ? $_GET['year'] : currentYear();
$week = $_GET['week'];
if (isset($_POST['submit'])) {
    foreach (nflTeams() as $team) {
        $benching = $_POST["benching_$team"];
        $points = $_POST["points_$team"];
        $explanation = $_POST["explanation_$team"];
        $query = "UPDATE extra_points 
                  SET benching='$benching', points='$points', explanation='$explanation'
                  WHERE year='$year' AND week='$week' AND nfl_team='$team';";
        pg_query($bqbldbconn, $query);
    }
    echo "Updated.<br>\n";
}

$formaction=$_SERVER['PHP_SELF'] . "?week=$week&year=$year";
echo "<form method=post action='$formaction'>
<table><tr><th>Team</th><th>Benchings</th><th>Other Points</th><th>Explanation</th></tr>\n";
foreach (nflTeams() as $team) {
    $query = "SELECT benching, points, explanation FROM extra_points 
              WHERE year='$year' AND week='$week' AND nfl_team='$team';";
    list($benching, $points, $explanation) = pg_fetch_array(pg_query($bqbldbconn, $query));
    echo "<tr><td>$team</td><td><input type='text' name='benching_$team' size='3' value='$benching'></td><td><input type='text' name='points_$team' size='3' value='$points'></td><td><input type='text' name='explanation_$team' value='$explanation'></td></tr>\n";
}
echo "</table>
<input type='submit' name='submit'></form>";
?>