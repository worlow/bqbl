<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

echo "<html><head>
<title>$year BQBL Week $week </title></head><body>\n";

$bqbl_teamname = bqblTeams($league, $year);
$lineup = getLineups($year, $week, $league);
$matchup = getMatchups($year, $week, $league);

foreach ($matchup as $bqblteam1 => $bqblteam2) {
    $games[] = array($year, $week, $lineup[$bqblteam1][0]);
    $games[] = array($year, $week, $lineup[$bqblteam1][1]);
    $games[] = array($year, $week, $lineup[$bqblteam2][0]);
    $games[] = array($year, $week, $lineup[$bqblteam2][1]);
}
$gamePoints = getPointsBatch($games);

echo "<table>";
foreach ($matchup as $bqblteam1 => $bqblteam2) {
    $home_team1 = $gamePoints[$year][$week][$lineup[$bqblteam1][0]];
    $home_team2 = $gamePoints[$year][$week][$lineup[$bqblteam1][1]];
    $away_team1 = $gamePoints[$year][$week][$lineup[$bqblteam2][0]];
    $away_team2 = $gamePoints[$year][$week][$lineup[$bqblteam2][1]];
    
    echo "<tr><td colspan=2 class='teamname'>$bqbl_teamname[$bqblteam1]</td></tr>";
    echo "<tr>\n";
    echo "<td class=score>\n";
    echo $lineup[$bqblteam1][0];
    printScore($home_team1);
    echo "</td>\n";
    echo "<td class=score>\n";
    echo $lineup[$bqblteam1][1];
    printScore($home_team2);
    echo"</td></tr>\n";
    
    echo "<tr><td colspan=2 class='teamname'>VS. <br>$bqbl_teamname[$bqblteam2]</td></tr>";    
    echo "<tr>\n";
    echo "<td class=score>\n";
    echo $lineup[$bqblteam2][0];
    printScore($away_team1);
    echo "</td>\n";
    echo "<td class=score >\n";
    echo $lineup[$bqblteam2][1];
    printScore($away_team2);
    echo "</td></tr>\n";
    echo "<tr><td class='line' colspan=2></td></tr>";
}
echo "</table>";
?>
<style>
.score {
font-size: x-large;
font-weight:bold;
text-align: center;
padding:0 20px 10px 0;
}

.line {
background: #FFFFFF;
overflow: hidden;
padding: 0px 0 30px 0;
border-top: 5px solid #000000;
}

.teamname {
text-align: center;
font-weight: bold;
font-size: 25;
}
</style>