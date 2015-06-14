<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

ui_header($title="$year Week $week Rankings", $showLastUpdated=($week == currentWeek()), $showWeekDropdown=true);

$games = array();
foreach(nflTeams() as $nflTeam) $games[] = array($year, $week, $nflTeam);
$gamePoints = getPointsBatch($games);

$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$week' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($GLOBALS['nfldbconn'],$query);

$totals = array();
$totals_defense = array();
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $homepoints = $gamePoints[$year][$week][$hometeam];
    $awaypoints = $gamePoints[$year][$week][$awayteam];
    $totals[$hometeam] = totalPoints($homepoints);
    $totals_defense[$hometeam] = defenseScore($awaypoints);
    $totals[$awayteam] = totalPoints($awaypoints);
    $totals_defense[$awayteam] = defenseScore($homepoints);
}
arsort($totals);
arsort($totals_defense);

echo "<paper-material elevation=2>";
echo "<div class='cardheader'>Offense</div>";
echo '<div class="table">';
echo '<div class="header row"><div class="cell">Rank</div><div class="cell">Team Name</div><div class="cell">Total Points</div></div>';
$rank = 0;
foreach ($totals as $key => $val) {
    $rank++;
    echo "<div class=\"row\"><div class=\"cell\">$rank</div><div class=\"cell\"><a href='/bqbl/nfl.php?team=$key&year=$year'>$key</a></div><div class=\"cell\">$val</div></div>";
}
echo "</div>";
echo "</paper-material>";

echo "<paper-material elevation=2>";
echo "<div class='cardheader'>Defense</div>";
echo '<div class="table">';
echo "<div class=\"header row\"><div class=\"cell\">Rank</div><div class=\"cell\">Team Name</div><div class=\"cell\">Total Points</div></div>";
$rank = 0;
foreach ($totals_defense as $key => $val) {
    $rank++;
    echo "<div class=\"row\"><div class=\"cell\">$rank</div><div class=\"cell\"><a href='/bqbl/nfl.php?team=$key&year=$year'>$key</a></div><div class=\"cell\">$val</div></div>";
}
echo "</div>";
echo "</paper-material>";
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
  border-top: 1px solid #e5e5e5;
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
