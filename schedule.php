<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$week_complete = $year < currentYear() ? 15 : currentCompletedWeek();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

$games = array();
foreach (nflTeams() as $team) {
    for ($i=1; $i<=$week_complete; $i++) {
        $games[] = array($year, $i, $team);
    }
}

$gamePoints = getPointsBatch($games);

echo "<html><head>
<title>$year BQBL Schedule </title></head><body>\n";

$bqbl_teamname = bqblTeams($league, $year);
$matchup = array();
$score = array();

$query = "SELECT week, team1, team2
            FROM schedule
              WHERE year='$year' AND league='$league';";
$result = pg_query($bqbldbconn, $query);
while(list($week,$team1,$team2) = pg_fetch_array($result)) {
    $matchup[$week][$team1] = $team2;
    $matchup[$week][$team2] = $team1;
}

echo '<table border=2 cellpadding=4 style="border-collapse:collapse;display:inline-block; margin-left:20px;">';
echo "<tr><th></th>";
foreach($bqbl_teamname as $teamName) {
    if ($teamName == "Anirbaijan") {
        echo "<th><span class='rainbow'>$teamName</span></th>";
    } else {
        echo "<th>$teamName</th>";
    }
}
echo "</tr>";

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
    
    echo "<tr><td><a href='/bqbl/matchup.php?week=$i'>Week $i</a></td>";
    foreach ($bqbl_teamname as $teamId => $teamName) {
        if ($score[$teamId][$i] > $score[$matchup[$i][$teamId]][$i]) {
            echo '<td bgcolor="#00FF00">'.$bqbl_teamname[$matchup[$i][$teamId]]."</td>";
        } elseif ($score[$teamId][$i] < $score[$matchup[$i][$teamId]][$i]) {
            echo '<td bgcolor="FF0000">'.$bqbl_teamname[$matchup[$i][$teamId]]."</td>";
        } else {
            echo "<td>".$bqbl_teamname[$matchup[$i][$teamId]]."</td>";
        }
    }
    echo "</tr>";
}
echo "</table>";
footer();
exit();
die();
?>
<style>
.rainbow {
  background-image: -webkit-gradient( linear, left top, right top, color-stop(0, #f22), color-stop(0.15, #f2f), color-stop(0.3, #22f), 
      color-stop(0.45, #2ff), color-stop(0.6, #2f2),color-stop(0.75, #2f2), color-stop(0.9, #ff2), color-stop(1, #f22) );
  background-image: gradient( linear, left top, right top, color-stop(0, #f22), color-stop(0.15, #f2f), color-stop(0.3, #22f), color 
      stop(0.45, #2ff), color-stop(0.6, #2f2),color-stop(0.75, #2f2), color-stop(0.9, #ff2), color-stop(1, #f22) );
  color:transparent;
  -webkit-background-clip: text;
  background-clip: text;
}
</style>
