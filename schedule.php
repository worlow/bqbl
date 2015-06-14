<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$week_complete = min(15, $year < currentYear() ? 15 : currentCompletedWeek());
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

$games = array();
foreach (nflTeams() as $team) {
    for ($i=1; $i<=$week_complete; $i++) {
        $games[] = array($year, $i, $team);
    }
}

$gamePoints = getPointsBatch($games);

ui_header("$year BQBL Schedule");

$bqbl_teamname = bqblTeams($league, $year);
$matchup = array();
$score = array();

$query = "SELECT week, team1, team2
            FROM schedule
              WHERE year='$year' AND league='$league' AND week <= '$REG_SEASON_END_WEEK';";
$result = pg_query($bqbldbconn, $query);
while(list($week,$team1,$team2) = pg_fetch_array($result)) {
    $matchup[$week][$team1] = $team2;
    $matchup[$week][$team2] = $team1;
}

echo '<paper-material elevation="2">';
echo '<div id="schedule-table">';
echo "<div class='header row'><div class='cell'></div>";
foreach($bqbl_teamname as $teamName) {
    if ($teamName == "Anirbaijan") {
        echo "<div class='cell'><span class='rainbow'>$teamName</span></div>";
    } else {
        echo "<div class='cell'>$teamName</div>";
    }
}
echo "</div>";

for ($i = 1; $i <= 15; $i++) {
    if ($i == 15 && $year > 2013) {
        continue;
    }
    $lineup = getLineups($year, $i, $league);
    foreach ($lineup as $team => $starters) {
        if ($i <= $week_complete) {
            $score[$team][$i] =
                totalPoints($gamePoints[$year][$i][$starters[0]]) + totalPoints($gamePoints[$year][$i][$starters[1]]);
        } else {
            $score[$team][$i] = 0;
        }   
    }
    
    echo "<div class='row'><div class='cell'><a href='/bqbl/matchup.php?week=$i'>Week $i</a></div>";
    foreach ($bqbl_teamname as $teamId => $teamName) {
        if ($score[$teamId][$i] > $score[$matchup[$i][$teamId]][$i]) {
            echo "<div class='cell win'>".$bqbl_teamname[$matchup[$i][$teamId]]."</div>";
        } elseif ($score[$teamId][$i] < $score[$matchup[$i][$teamId]][$i]) {
            echo "<div class='cell loss'>".$bqbl_teamname[$matchup[$i][$teamId]]."</div>";
        } else {
            echo "<div class='cell'>".$bqbl_teamname[$matchup[$i][$teamId]]."</div>";
        }
    }
    echo "</div>";
}
echo "</div>";
ui_footer();
?>
<style is="custom-style">

paper-material {
    display: inline-block;
    background-color: #FFFFFF;
    padding: 32px;
    margin: 32px 32px 0 32px;
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

#schedule-table {
  border-collapse: separate;
  font-size: .75vw;
  text-align: center;
}

#schedule-table .cell {
  border-top: 1px solid #e5e5e5;
  padding: 16px;
}

#schedule-table .thickline .cell {
  border-bottom: 5px solid #000000;
}

#schedule-table .header .cell {
    font-weight: bold;
    font-size: 110%;
    padding-top: 0;
    border-top: 0;
}
</style>

<?
footer();
exit();
die();
?>
