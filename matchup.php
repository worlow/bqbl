<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

$week = isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek();
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();

$updateTime = date("n/j g:i:s A, T", databaseModificationTime());
echo "<html><head><title>BQBL Week $week $year</title>
<div id='content' align='center'>
<h1>$year Week $week BQBL Scoreboard</h1>
Last Updated at $updateTime";

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
    $populatedTeam = $home_team1;
    if(count($home_team2)>0) $populatedTeam = $home_team2;
    elseif(count($away_team1)>0) $populatedTeam = $away_team1;
    elseif(count($away_team2)>0) $populatedTeam = $away_team2;
    $columns = 2 + count($populatedTeam);

    echo "<tr>\n";
    echo "<td><table border=2 class='matchup'>
    <tr><td colspan=$columns class='teamname'>$bqbl_teamname[$bqblteam1]</td></tr>
    <tr><th></th>";
    foreach($populatedTeam as $name => $val) {
        echo "<th>$name</th>";
    }
    echo "<th>Total</th></tr>";
    
    echo "<tr><td class='nflteamname'>" . $lineup[$bqblteam1][0] . "</td>";
    foreach($home_team1 as $name => $val) {
        echo "<td><span class='statpoints'>$val[1]</span><span class='statvalue'>($val[0])</td>";
    }
    echo "<td>" . totalPoints($home_team1) . "</td>";
    echo "</tr>\n";
    echo "<tr><td class='nflteamname'>" . $lineup[$bqblteam1][1] . "</td>";
    foreach($home_team2 as $name => $val) {
        echo "<td><span class='statpoints'>$val[1]</span><span class='statvalue'>($val[0])</td>";
    }
    echo "<td>" . totalPoints($home_team2) . "</td>";
    echo "</tr>\n";
    
    echo "<tr style='border:0;'><td colspan=$columns class='teamname' style='border:0;'>VS.</td></tr>";
    echo "<tr style='border:0;'><td colspan=$columns class='teamname' style='border:0;'>$bqbl_teamname[$bqblteam2]</td></tr>";
    echo "<th></th>";
    foreach($populatedTeam as $name => $val) {
        echo "<th>$name</th>";
    }
    echo "<th>Total</th></tr>";
    echo "<tr><td class='nflteamname'>" . $lineup[$bqblteam2][0] . "</td>";
    foreach($home_team1 as $name => $val) {
        echo "<td><span class='statpoints'>$val[1]</span><span class='statvalue'>($val[0])</td>";
    }
    echo "<td>" . totalPoints($away_team1) . "</td>";
    echo "</tr>\n";
    echo "<tr><td class='nflteamname'>" . $lineup[$bqblteam2][1] . "</td>";
    foreach($away_team2 as $name => $val) {
        echo "<td><span class='statpoints'>$val[1]</span><span class='statvalue'>($val[0])</td>";
    }
    echo "<td>" . totalPoints($away_team2) . "</td>";
    echo "</tr>\n";
    echo "</table>";
    echo "<tr><td class='line' colspan=$columns></td></tr>";
}
echo "</table>";
echo "</div>";  # content div
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
padding: 0px 0 50px 0;
/* border-top: 5px solid #000000; */
}

.teamname {
text-align: center;
font-weight: bold;
font-size: 25;
}

.nflteamname {
font-weight: bold;
}

.statvalue {
color: #999999;
margin-left: 5px;
}

.statpoints {
font-weight: bold;
}

.matchup {
border-collapse: collapse;
background-color: #F8F8F8;
}

.matchup th {
padding: 0 5px 0 5px;
}
</style>