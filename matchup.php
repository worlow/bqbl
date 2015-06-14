<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

ui_header($title="$year Week $week BQBL Scoreboard", $showLastUpdated=true, $showAutoRefresh=true, $showWeekDropdown=true);

$bqbl_teamname = bqblTeams($league, $year);
$starters = getLineups($year, $week, $league);
$lineup = $starters;
foreach (getRosters($year, $league, $week > $REG_SEASON_END_WEEK) as $bqbl_team => $roster) {
    foreach ($roster as $nfl_team) {
        if (!$lineup[$bqbl_team] or !in_array($nfl_team, $lineup[$bqbl_team])) {
            $lineup[$bqbl_team][] = $nfl_team;
        }
    }
}
$unsortedmatchup = getMatchups($year, $week, $league);
$matchup=array();
foreach ($unsortedmatchup as $bqblteam1 => $bqblteam2) {
    if ($_SESSION['bqbl_team']==$bqblteam1 || $_SESSION['bqbl_team']==$bqblteam2) {
        $matchup[$bqblteam1] = $bqblteam2;
    }
}
foreach ($unsortedmatchup as $bqblteam1 => $bqblteam2) {
    if ($_SESSION['bqbl_team']!=$bqblteam1 && $_SESSION['bqbl_team']!=$bqblteam2) {
        $matchup[$bqblteam1] = $bqblteam2;
    }
}

$games = array();
foreach(nflTeams() as $nflTeam) $games[] = array($year, $week, $nflTeam);
$gamePoints = getPointsBatch($games);

foreach ($matchup as $bqblteam1 => $bqblteam2) {
    $home_team1 = $gamePoints[$year][$week][$lineup[$bqblteam1][0]];
    $home_team2 = $gamePoints[$year][$week][$lineup[$bqblteam1][1]];
    $home_team3 = $gamePoints[$year][$week][$lineup[$bqblteam1][2]];
    $home_team4 = $gamePoints[$year][$week][$lineup[$bqblteam1][3]];
    $away_team1 = $gamePoints[$year][$week][$lineup[$bqblteam2][0]];
    $away_team2 = $gamePoints[$year][$week][$lineup[$bqblteam2][1]];
    $away_team3 = $gamePoints[$year][$week][$lineup[$bqblteam2][2]];
    $away_team4 = $gamePoints[$year][$week][$lineup[$bqblteam2][3]];
    $populatedTeam = $home_team1;
    if(count($home_team2)>0 && $home_team2["Interceptions"] != '') $populatedTeam = $home_team2;
    elseif(count($away_team1)>0 && $away_team1["Interceptions"] != '') $populatedTeam = $away_team1;
    elseif(count($away_team2)>0 && $away_team2["Interceptions"] != '') $populatedTeam = $away_team2;
    $columns = 2 + count($populatedTeam);
    $statcolumns = $columns - 1;

    echo "<paper-material elevation='5' class='matchuppaper x-scope paper-material-0'><div style=\"display:inline-table;font-family:'Roboto', sans-serif;font-size: 1vw;max-width:100%;\" class='matchup'>";

    echo "<paper-material elevation='1' class='teampaper x-scope paper-material-0'><span class='teamname'>$bqbl_teamname[$bqblteam1]</span>";
    echo "<div style='display:table-row;'>";
    echo "<div class='cell'></div>";
    foreach($populatedTeam as $name => $val) {
        echo "<div class='cell'>$name</div>";
    }
    echo "<div class='cell'>Total</div></div>";

    printTeamRow($lineup[$bqblteam1][0], $home_team1, in_array($lineup[$bqblteam1][0], $starters[$bqblteam1]));
    printTeamRow($lineup[$bqblteam1][1], $home_team2, in_array($lineup[$bqblteam1][1], $starters[$bqblteam1]));
    printTeamRow($lineup[$bqblteam1][2], getPointsOnlyMisc($home_team3));
    printTeamRow($lineup[$bqblteam1][3], getPointsOnlyMisc($home_team4));

    echo "</paper-material><br>";
    echo "<paper-material elevation='1' class='teampaper x-scope paper-material-0'><span class='teamname'>$bqbl_teamname[$bqblteam2]</span>";

    echo "<div style='display:table-row'>";
    echo "<div class='cell'></div>";
    foreach($populatedTeam as $name => $val) {
        echo "<div class='cell'>$name</div>";
    }
    echo "<div class='cell'>Total</div></div>";

    printTeamRow($lineup[$bqblteam2][0], $away_team1, in_array($lineup[$bqblteam2][0], $starters[$bqblteam2]));
    printTeamRow($lineup[$bqblteam2][1], $away_team2, in_array($lineup[$bqblteam2][1], $starters[$bqblteam2]));
    printTeamRow($lineup[$bqblteam2][2], getPointsOnlyMisc($away_team3));
    printTeamRow($lineup[$bqblteam2][3], getPointsOnlyMisc($away_team4));

    echo "</div></paper-material></paper-material>";
}

ui_footer();

function cmp_isTeamUser($a, $b) {
    if (isset($_SESSION['bqbl_team'])) {
        if ($a == $_SESSION['bqbl_team']) {
            return -1;
        } elseif ($b == $_SESSION['bqbl_team']) {
            return 1;
        }
    }
    return 0;
}
function printTeamRow($team, $points, $starting=false) {
    global $statcolumns, $year, $week;
    $style = $starting ? "background: #00CC66;" : "";
    echo "<div style='display:table-row;'><div class='cell 'nflteamname' style='$style'>
        <a href='/bqbl/nfl.php?team=$team&year=$year'>$team</a></div>";
    foreach($points as $name => $val) {
        if ($val == '') {
            echo "<div class='cell'></div>";
        } else {
            echo "<div class='cell'><span class='statpoints'>$val[1]</span><span class='statvalue'>";
            if ($name != "Misc. Points") {
                echo "($val[0])";
            }
            echo "</div>";
        }
    }
    echo "<div class='cell' class='totalpoints'>" . totalPoints($points) . "</div>";
    echo "</div>\n";
}

function tableCells($cells) {
    for ($i = 0; $i < $cells; $i++) {
        echo "<div style='width:0px;'></div>";
    }
}
?>
<style>
paper-material {
display: inline-block;
background: white;
box-sizing: border-box;
margin: 16px;
padding: 16px;
border-radius: 2px;
}

.teampaper {
background-color: #FFFFFF;
margin:16px 0px 16px 0px;
display: inline-block;
max-width: 100%;
x-overflow: hidden;
}

.matchuppaper {
background-color: #F8F8F8;
padding:0px 16px 0px 16px;
margin:32px 32px 0px 32px;
display: inline-block;
}

.cell {
display:table-cell;
padding:2px 4px 2px 4px;
word-wrap: break-word;
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
}

.matchup th {
padding: 0 5px 0 5px;
}

.totalpoints {
font-weight:bold;
}
</style>
