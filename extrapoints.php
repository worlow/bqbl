<?php
// Report simple running errors
error_reporting(E_ERROR);
require_once "lib/lib.php";

ui_header($title="$year Week $week Extra Points", $showWeekDropdown=true);

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
echo "<paper-material><form method=post action='$formaction'>
<div class='table'><div class=\"header row\"><div class=\"cell\">Team</div><div class=\"cell\">Benchings</div><div class=\"cell\">Other</div><div class=\"cell\">Explanation For Other Points</div></div>\n";
foreach (nflTeams() as $team) {
    $query = "SELECT benching, points, explanation FROM extra_points 
              WHERE year='$year' AND week='$week' AND nfl_team='$team';";
    list($benching, $points, $explanation) = pg_fetch_array(pg_query($bqbldbconn, $query));
    echo "<div class=\"row\"><div class=\"cell\">$team</div><div class=\"cell\"><input type='text' name='benching_$team' size='3' maxlength='1' value='$benching'></div><div class=\"cell\"><input type='text' name='points_$team' size='3' value='$points'></div><div class=\"cell\"><input type='text' name='explanation_$team' size='80' value='$explanation'></div></div>\n";
}
echo "</div>
<input type='submit' name='submit' value='Update'></form></paper-material>";
?>

<style is="custom-style">
paper-material {
    display: inline-block;
    background-color: #FFFFFF;
    padding: 32px;
    margin: 32px 24px 0 24px;
}

.loss {
    background-color: var(--paper-red-500);
}

.win {
    background-color: var(--paper-green-500);
}

.row {
    display: table-row;
}

.cell {
    display: table-cell;
}

.table {
  display: table;
  border-collapse: separate;
  font-size: 1vw;
  text-align: center;
}

.table .cell {
  border-top: 0;
  padding: 8px;
}

.table .thickline .cell {
  border-bottom: 5px solid #000000;
}

.table .header .cell {
    border-top: 0;
    font-weight: bold;
    font-size: 110%;
    padding-top: 0;
}

.cardheader {
    display:inline-block;
    font-weight: bold;
    font-size: 150%;
    padding-bottom: 16px;
}
</style>

<?php
ui_footer();
?>
