
<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";
$week = min(17, isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentCompletedWeek());
ui_header($title="BQBL Season Rankings $year");

$grandtotals = array();
$grandtotals_defense = array();
$starts = array();
$owner = array();
$bqbl_draftscore = array();
$bqbl_teamname = bqblTeams($league, $year);
$nfl_draftscore = array();
$draft_pick = array();
$average = array();

$games = array();
foreach (nflTeams() as $team) {
    $starts[$team] = 0;
    $grandtotals[$team] = 0;
    $grandtotals_defense[$team] = 0;
    for ($i=1; $i<=$week; $i++) {
        $games[] = array($year, $i, $team);
    }
}
$gamePoints = getPointsBatch($games);

foreach ($bqbl_teamname as $key => $val) {
    $bqbl_draftscore[$key] = 0;
}

for ($i=1; $i<=$week; $i++) {
    $query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$i' AND season_type='Regular'
          ORDER BY start_time ASC;";
    $result = pg_query($GLOBALS['nfldbconn'],$query);
    while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
        $starts[$hometeam]++;
        $starts[$awayteam]++;
        $homepoints = $gamePoints[$year][$i][$hometeam];
        $awaypoints = $gamePoints[$year][$i][$awayteam];
        $grandtotals[$hometeam] += totalPoints($homepoints);
        $grandtotals_defense[$hometeam] += defenseScore($awaypoints);
        $grandtotals[$awayteam] += totalPoints($awaypoints);
        $grandtotals_defense[$awayteam] += defenseScore($homepoints);
    }
}

$query = "SELECT bqbl_team, nfl_team, draft_position
    FROM roster WHERE year='$year';";
$result = pg_query($GLOBALS['bqbldbconn'],$query);
while(list($bqbl_team,$nfl_team,$draft_position) = pg_fetch_array($result)) {
    $draft_pick[$nfl_team] = $draft_position;
    $owner[$nfl_team] = $bqbl_team; 
    $average[$nfl_team] = $grandtotals[$nfl_team]/$starts[$nfl_team];
    $average_defense[$nfl_team] = $grandtotals_defense[$nfl_team]/$starts[$nfl_team];
}
arsort($average);
arsort($average_defense);

echo "<paper-material elevation=2>";
echo "<div class='cardheader'>Offense</div>";
echo '<div class="table">';
echo "<div class=\"header row\"><div class=\"cell\">Rank</div><div class=\"cell\">Team</div><div class=\"cell\">Points</div><div class=\"cell\">Average</div></div>";
$rank = 0;
foreach ($average as $key => $val) {
    $rank++;
    $nfl_draftscore[$key] = $draft_pick[$key] - $rank;
    $bqbl_draftscore[$owner[$key]] += $rank;
    echo "<div class=\"row\"><div class=\"cell\">$rank</div><div class=\"cell\"><a href='/bqbl/nfl.php?team=$key&year=$year'>$key</a></div>
        <div class=\"cell\">$grandtotals[$key]</div><div class=\"cell\">".round($val,2)."</div></div>";
}
echo "</div>";
echo "</paper-material>";

echo "<paper-material elevation=2>";
echo "<div class='cardheader'>Defense</div>";
echo '<div class="table">';
echo "<div class=\"header row\"><div class=\"cell\">Rank</div><div class=\"cell\">Team</div><div class=\"cell\">Points</div><div class=\"cell\">Average</div></div>";
$rank = 0;
foreach ($average_defense as $key => $val) {
    $rank++;
    echo "<div class=\"row\"><div class=\"cell\">$rank</div><div class=\"cell\"><a href='/bqbl/nfl.php?team=$key&year=$year'>$key</a></div>
        <div class=\"cell\">$grandtotals_defense[$key]</div><div class=\"cell\">".round($val,2)."</div></div>";
}
echo "</div>";
echo "</paper-material>";

arsort($nfl_draftscore);
asort($bqbl_draftscore);

echo "<paper-material elevation=2>";
echo "<div class='cardheader'>Draft Score (NFL)</div>";
echo '<div class="table">';
echo "<div class=\"header row\"><div class=\"cell\">Rank</div><div class=\"cell\">Team</div><div class=\"cell\">Pick</div><div class=\"cell\">Draft Score</div></div>";
$rank = 0;
foreach ($nfl_draftscore as $key => $val) {
    $rank++;
    echo "<div class=\"row\"><div class=\"cell\">$rank</div><div class=\"cell\"><a href='/bqbl/nfl.php?team=$key&year=$year'>$key</a></div>
        <div class=\"cell\">$draft_pick[$key]</div><div class=\"cell\">$val</div></div>";
}
echo "</div>";
echo "</paper-material>";

echo "<paper-material elevation=2>";
echo "<div class='cardheader'>Draft Score (BQBL)</div>";
echo '<div class="table">';
echo "<div class=\"header row\"><div class=\"cell\">Rank</div><div class=\"cell\">Team Name</div><div class=\"cell\">Draft Score</div></div>";
$rank = 0;
foreach ($bqbl_draftscore as $key => $val) {
    if (($key == 4 && $year <= 2013) || ($key == 9 && $year > 2013)) {
            continue;
    }
    $rank++;
    echo "<div class=\"row\"><div class=\"cell\">$rank</div><div class=\"cell\">$bqbl_teamname[$key]</div><div class=\"cell\">$val</div></div>";
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
    font-weight: bold;
    font-size: 110%;
    padding-top: 0;
    border-top: 0;
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
