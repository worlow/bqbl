<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

ui_header($title="BQBL Week $week $year", $showLastUpdated=true, $showAutorefresh=true, $showWeekDropdown=true);

$games = array();
foreach(nflTeams() as $nflTeam) $games[] = array($year, $week, $nflTeam);
$gamePoints = getPointsBatch($games);

if (isset($_GET['autorefresh'])) {
    if ($timeout < 0 && $timeout > -$DB_UPDATE_INTERVAL) $timeout=0;
    if ($timeout >= 0) {
        $timeout *= 1000;  # millis
        $timeout += rand(15000,20000);  # allow for update + prevent DDOS
        echo "<script type='text/javascript'>
        setTimeout(function() {location.reload();}, $timeout);
        </script>";
    } else {
        echo "<br /><span style='color: #FF0000'>The auto-refresh function is not available at this time.</span>";
    }
} elseif ($timeout>=0 && $week==currentWeek() && $year==currentYear()) {

    echo "<br/><a href='$_SERVER[PHP_SELF]?autorefresh'>Auto Refresh</a>";
}
$query = "SELECT gsis_id, home_team, away_team
		  FROM game
		  WHERE season_year='$year' AND week='$week' AND season_type='Regular'
          ORDER BY start_time ASC;";
$result = pg_query($GLOBALS['nfldbconn'],$query);
echo "<div style='display:table;'>";
while(list($gsis,$hometeam,$awayteam) = pg_fetch_array($result)) {
    $gameType = gameTypeById($gsis);
    echo "<paper-material elevation='5' class='matchuppaper x-scope paper-material-0'>";
    echo "<div id=matchup style='display:table-row;font-family:'Roboto', sans-serif;font-size: 1vw;max-width:100%;'>\n";
    echo "<div class=score>\n";
    echo "<a href='/bqbl/nfl.php?team=$hometeam&year=$year'>$hometeam</a>\n";
    printGameScore($gamePoints[$year][$week][$hometeam], $hometeam, $week, $year);
    echo "</div><div style='display:table-cell;min-width:32px;font-size:x-large;font-weight:400;text-align:center;'>@</div>\n";
    echo "<div class=score >\n";
    echo "<a href='/bqbl/nfl.php?team=$awayteam&year=$year'>$awayteam</a>\n";
    printGameScore($gamePoints[$year][$week][$awayteam], $awayteam, $week, $year);
    echo"</div></div></paper-material>";
}

ui_footer();
?>
<style>
* {
font-family:'Roboto', sans-serif;
}

paper-material {
display: inline-block;
background: white;
box-sizing: border-box;
margin: 16px;
padding: 16px;
border-radius: 2px;
}

.score {
font-size: 90%;
}

.matchuppaper {
background-color: #FFFFFF;
padding:24px;
margin:32px;
display: inline-block;
}
</style>
