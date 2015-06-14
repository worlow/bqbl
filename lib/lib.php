<?php
// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);
session_start();
require_once("lib_db.php");
require_once("lib_auth.php");
date_default_timezone_set('America/Los_Angeles');

$nfldbconn = connect_nfldb();
$bqbldbconn = connect_bqbldb();

$CURRENT_YEAR = 2014;
$WEEK_1_THURS_DATE = "2014-09-04";
$DB_UPDATE_INTERVAL = 60;  # seconds
$REG_SEASON_END_WEEK = 14;

$timeout = $DB_UPDATE_INTERVAL - (time()-databaseModificationTime());
$week = min(17, isset($_GET['week']) ? pg_escape_string($_GET['week']) : currentWeek());
$year = isset($_GET['year']) ? pg_escape_string($_GET['year']) : currentYear();
$league = isset($_GET['league']) ? $_GET['league'] : getLeague();



function currentYear() {
    global $CURRENT_YEAR;
    return $CURRENT_YEAR;
}

function currentWeek() {
    global $WEEK_1_THURS_DATE;
    $now = time();
    $season_start = strtotime($WEEK_1_THURS_DATE) - 2*60*60*24;  # Tuesday
    $weeks = 1+floor(($now-$season_start)/(60*60*24*7));
    return $weeks;
}

function currentCompletedWeek() {
    return currentWeek() - 1;
}

function weekCutoffTime($week) {
    global $WEEK_1_THURS_DATE;
    $now = time();
    if ($week == 17) {
        $season_first_sunday = strtotime($WEEK_1_THURS_DATE . " 10:00:00") + 24*60*60 * 3;  # Sunday 10:00AM PST
        return $season_first_sunday + 7*24*60*60*($week - 1);
    } else {
        $season_start = strtotime($WEEK_1_THURS_DATE . " 17:30:00");  # Thursday 5:30PM PST
        return $season_start + 7*24*60*60*($week - 1);
    }
}

function isGameFinished($gsis_id) {
    global $nfldbconn;
    $query = "SELECT finished FROM game WHERE gsis_id='$gsis_id';";
    return pg_fetch_result(pg_query($nfldbconn, $query), 0);
}

function getLeague() {
    $year=currentYear();
    if(isset($_SESSION['league'])) {
        return $_SESSION['league'];
    } elseif(isset($_SESSION['user'])) {
        $query = "SELECT league FROM membership JOIN users ON membership.bqbl_team=users.id WHERE username='$_SESSION[user]' AND year='$year';";
        $league = pg_fetch_result(pg_query($GLOBALS['bqbldbconn'], $query),0);
        return ($league != "") ? $league : "nathans";
    } else return "nathans";
}

function getDomain() {
    return "bqbl.duckdns.org";
}

function getBqblTeam($user) {
    global $bqbldbconn;
    return pg_fetch_result(pg_query($bqbldbconn, "SELECT id FROM users WHERE username='$user';"), 0);
}

function bqblIdToTeamName($id) {
    global $bqbldbconn;
    return pg_fetch_result(pg_query($bqbldbconn, "SELECT team_name FROM users WHERE id='$id';"), 0);
}

function bqblUserImage($id) {
    global $bqbldbconn;
    return pg_fetch_result(pg_query($bqbldbconn, "SELECT image_url FROM users WHERE id='$id';"), 0);
}

function databaseModificationTime() {
    $file = "C:\\www\\bqbl\\update_time";
    return filemtime($file);
}

function gameTypeById($gsis) {
global $nfldbconn;
    $query = "SELECT start_time
      FROM game
      WHERE gsis_id='$gsis';";
    $result = pg_query($nfldbconn, $query);
    if(pg_num_rows($result) == 0) { // Bye week
        return -1;
    }
    list($gametime) = pg_fetch_array($result,0);
    if(strtotime($gametime) > time()) {
        return 2; // Future game
    }
    return 1; // Current or past game
}

function nflMatchup($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT home_team, away_team
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
              AND week='$week' AND season_type='Regular';";
    $result = pg_query($GLOBALS['nfldbconn'], $query);

    if(pg_num_rows($result) == 0) { // Bye week
        return array();
    }
    return list($home_team,$away_team) = pg_fetch_array($result,0);
}

function gameType($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT start_time
              FROM game
              WHERE (home_team='$team' or away_team='$team') AND season_year='$year' 
              AND week='$week' AND season_type='Regular';";
    $result = pg_query($GLOBALS['nfldbconn'], $query);
    if(pg_num_rows($result) == 0) { // Bye week
        return -1;
    }
    list($gametime) = pg_fetch_array($result,0);
    if(strtotime($gametime) > time()) {
        return 2; // Future game
    }
    return 1; // Current or past game
}

function nflTeams() {
    global $bqbldbconn;
    $teams = array();
    $query = "SELECT id from nfl_teams;";
    $result = pg_query($bqbldbconn, $query);
    while(list($team) = pg_fetch_array($result)) {
        $teams[] = $team;
    }
    return $teams;
}

function nflIdToCityTeamName($id) {
    global $bqbldbconn;
    $query = "SELECT city, name FROM nfl_teams WHERE id='$id';";
    $result = pg_query($bqbldbconn, $query);
    return pg_fetch_array($result);
}

function bqblTeams($league, $year, $sortByDraftOrder=false) {
    global $bqbldbconn;
    $bqbl_teamname = array();
    $orderClause = $sortByDraftOrder ? "ORDER BY draft_order ASC" : "ORDER BY team_name ASC";
    $query = "SELECT bqbl_team, team_name 
              FROM membership JOIN users ON membership.bqbl_team=users.id 
              WHERE league='$league' AND year='$year' $orderClause;";
    $result = pg_query($bqbldbconn, $query);
    while(list($id,$team_name) = pg_fetch_array($result)) {
        $bqbl_teamname[$id] = $team_name;
    }
    return $bqbl_teamname;
}


function getRosters($year, $league, $playoffs=false) {
    global $bqbldbconn;
    $roster = array();
    foreach (nflTeams() as $team) {
        $roster[$team] = array();
    }
    $roster_table = $playoffs ? "playoff_roster" : "roster";
    $query = "SELECT bqbl_team, nfl_team
              FROM $roster_table
              WHERE year = $year AND league='$league';";
    $result = pg_query($bqbldbconn, $query);
    while(list($bqbl_team, $nfl_team) = pg_fetch_array($result)) {
        $roster[$bqbl_team][] = $nfl_team;
    }
    return $roster;
}

function getLineups($year, $week, $league) {
    global $bqbldbconn;
    $lineup = array();
    foreach (bqblTeams($league, $year) as $id => $name) {
        $lineup[$id] = array();
    }
    $query = "SELECT bqbl_team, starter1, starter2
                FROM lineup
                  WHERE year = $year AND week = $week AND league='$league';";
    $result = pg_query($bqbldbconn, $query);
    while(list($bqbl_team,$starter1,$starter2) = pg_fetch_array($result)) {
        $lineup[$bqbl_team][] = $starter1;
        $lineup[$bqbl_team][] = $starter2;
    }
    return $lineup;
}

function getMatchups($year, $week, $league) {
    global $bqbldbconn;
    $matchup = array();
    $query = "SELECT team1, team2
            FROM schedule
              WHERE year = $year AND week = $week AND league='$league';";
    $result = pg_query($bqbldbconn, $query);
    while(list($team1,$team2) = pg_fetch_array($result)) {
        $matchup[$team1] = $team2;
    }
    return $matchup;
}

function gameTime($year, $week, $team) {
    global $nfldbconn;
    $query = "SELECT start_time FROM game
              WHERE year='$year' AND week='$week' AND (home_team='$team' OR away_team='$team');";
    $result = pg_query($nfldbconn, $query);
    if(pg_num_rows($result) == 0) {
        return 0;
    } else {
        return pg_fetch_result($result, 0);
    }
}

function bqblTeamStrToInt($bqblTeam) {
    global $bqbldbconn;
    $query = "SELECT id FROM users WHERE username='$bqblTeam';";
    return pg_fetch_result(pg_query($bqbldbconn, $query), 0);
}

function isTed($bqblTeam) {
    global $bqbldbconn;
    $query = "SELECT * FROM users WHERE id='$bqblTeam' AND username LIKE 'eltedador%';";
    return pg_num_rows(pg_query($bqbldbconn, $query)) > 0;
}

function footer() {
    global $bqbldbconn, $nfldbconn;
    pg_close($bqbldbconn);
    pg_close($nfldbconn);
}


function getUrl($league, $year, $week, $autorefresh=false) {
    $url = "$_SERVER[PHP_SELF]?league=$league&year=$year&week=$week";
    if ($autorefresh) {
        $url .= "&autorefresh";
    }
    return $url;
}

function urlWithParameters($url, $parameters) {
    if ($parameters == null || count($parameters) == 0) {
        return url;
    }
    $paramstrings = array();
    foreach ($parameters as $key => $val) {
        $paramstrings[] = "$key=$val";
    }
    return $url . "?" . implode("&", $paramstrings);
}

function ui_header($title="", $showLastUpdated=false, $showAutorefresh=false, $showWeekDropdown=false) {
global $year, $week, $league, $timeout;
$refresh_visibility = $showAutorefresh ? "inherit" : "hidden";
$autorefresh = isset($_GET['autorefresh']);
$autorefresh_url = getUrl($league, $year, $week, !$autorefresh);
if ($autorefresh) {
    if ($timeout < 0 && $timeout > -$DB_UPDATE_INTERVAL) $timeout=0;
    if ($timeout >= 0) {
        $timeout *= 1000;  # millis
        $timeout += rand(15000,20000);  # allow for update + prevent DDOS
        echo "<script type='text/javascript'>
        setTimeout(function() {location.reload();}, $timeout);
        </script>";
    }
}
$refreshicon = $autorefresh ? "av:pause-circle-outline" : "autorenew";

# TODO(sam): When paper-dropdown-menu comes out, replace <select> with it,
# then replace the true ternary with "inherit"
$week_visibility = $showAutorefresh ? "hidden" : "hidden";
$weekdropdown = "<div class='week-dropdown' style='visibility:$week_visibility;'>
<select>";
for ($i = 1; $i <= $week; $i++) {
    $selected = ($i == $week) ? "selected" : "";
    $urlparams = $_GET;
    $urlparams['week'] = $i;
    $url = urlWithParameters($_SERVER['PHP_SELF'], $urlparams);
    $weekdropdown .= "<option onclick=\"window.location.assign('$url')\" $selected>Week $i</option>";
}
$weekdropdown .= "</select></div>";

$updateTime = date("n/j g:i:s A, T", databaseModificationTime());
$lastUpdatedString = $showLastUpdated ? "Last Updated at $updateTime" : "&nbsp;";

$nav_items = array(
    "BQBL Scoreboard" => "/bqbl/matchup.php",
    "NFL Scoreboard" => "/bqbl/week.php",
    "Standings" => "/bqbl/standings.php",
    "Schedule" => "/bqbl/schedule.php",
    "Weekly Rankings" => "/bqbl/leaderboard.php",
    "Season Rankings" => "/bqbl/seasonleaderboard.php",
    "Set Lineup" => "/bqbl/lineup.php",
    "Input Extra Points" => "/bqbl/extrapoints.php",
    "Rantland" => "/rantland",
    "League Management" => "/bqbl/leaguemanagement.php",
    "Past Champions" => "/bqbl/champions.php",
    "BQBL Cares" => "/bqbl/cares",
    "Logout" => "/bqbl/auth/logout.php"
);

if (!isset($_SESSION['user'])) {
    unset($nav_items['Input Extra Points']);
    unset($nav_items['Logout']);
    unset($nav_items['Set Lineup']);
}

$nav_block = "";
$selected_nav_index = -1;
$nav_index = 0;
foreach ($nav_items as $label => $url) {
    if ($url == $_SERVER['PHP_SELF']) {
        $selected_nav_index = $nav_index;
    }
    $nav_index++;
    $nav_block .= "
        <paper-item style='min-height: 64px;'
          onclick=\"window.location.assign('$url');document.getElementById('week-drawer').closeDrawer();\">
            $label
        </paper-item>";
}

if(isset($_GET['teamnum'])) {
    $authbqblteam = pg_escape_string($_GET['teamnum']);
} elseif(isset($_SESSION['user'])) {
    $authbqblteam= getBqblTeam($_SESSION['user']);
	if(!isset($_SESSION['bqbl_team'])) {
		$_SESSION['bqbl_team'] = $authbqblteam;
	}
}

if ($authbqblteam != null) {
    $teamname = bqblIdToTeamName($authbqblteam);
    $image_url = bqblUserImage($authbqblteam);
    $userheader = "<div id='user-avatar' style='margin:8px 0;background-image:url($image_url);margin-right:8px;'></div><div>$teamname</div>";
} else {
$image_url = "/bqbl/media/avatar_default.jpg";
    $userheader = "<div id='user-avatar' style='background-image:url($image_url);margin-right:8px;'></div><div style='margin-left:3%;'><a href='/bqbl/auth/login.php' style='color:white;font-size:125%;font-weight:400;'>Login</a></div>";

}

$onload = "document.getElementById('drawer-header').style.height=68;
document.getElementById('user-avatar').style.width = document.getElementById('user-avatar').clientHeight;";

echo <<<END
<html><head>
<link rel="import" href="/bower_components/polymer/polymer.html">
<link rel="import" href="/bower_components/paper-material/paper-material.html">
<link rel="import" href="/bower_components/iron-icons/av-icons.html">
<link rel="import" href="/bower_components/iron-icons/iron-icons.html">
<link rel="import" href="/bower_components/paper-styles/paper-styles.html">
<link rel="import" href="/bower_components/paper-input/paper-input.html">
<link rel="import" href="/bower_components/paper-input/paper-input-error.html">
<link rel="import" href="/bower_components/paper-icon-button/paper-icon-button.html">
<link rel="import" href="/bower_components/paper-menu/paper-menu.html">
<link rel="import" href="/bower_components/paper-item/paper-item.html">
<link rel="import" href="/bower_components/paper-button/paper-button.html">
<link rel="import" href="/bower_components/paper-header-panel/paper-header-panel.html">
<link rel="import" href="/bower_components/paper-drawer-panel/paper-drawer-panel.html">
<link href='http://fonts.googleapis.com/css?family=Roboto:400,500' rel='stylesheet' type='text/css'>

<title>$title</title>
</head>
<body style='margin:0px;' bgcolor='#F8F8F8'" onload="$onload">
<paper-drawer-panel id="week-drawer" force-narrow="false" drawer-width="20%">
    <div drawer> 
    <paper-header-panel class="scrollable-panel">
        <div id="drawer-header" class='paper-header' style="padding-left:0;">
            <div style="display:flex;justify-content:flex-start;align-items:center;height:100%;">
                <paper-icon-button style="padding:4.5mm;" icon="chevron-left" role="button" class="huge x-scope paper-icon-button-0" paper-drawer-toggle>
                </paper-icon-button>
                $userheader
            </div>
        </div>
        <paper-menu selected="$selected_nav_index">
            $nav_block
        </paper-menu>
    </paper-header-panel>
    </div>
    
    <div main>
    <paper-header-panel class="scrollable-panel">
        <div id="main-header" class='paper-header'>
            <div style="display:table-cell;">
                <paper-icon-button style="padding:4.5mm;" icon="menu" role="button" class="huge x-scope paper-icon-button-0" paper-drawer-toggle  >
                </paper-icon-button>
            </div>
            <div class="week-dropdown" style="visibility:hidden;"></div>
            <div class="header-title">
                <h1 style="margin:0px;">$title</h1>
                $lastUpdatedString
            </div>
            $weekdropdown
            <div style="display:table-cell;visibility:$refresh_visibility;">
                <paper-icon-button style="padding:4.5mm;" icon="$refreshicon" role="button" class="huge x-scope paper-icon-button-0" onClick="window.location.assign('$autorefresh_url');">
                </paper-icon-button>
            </div>
        </div>
        <div id='content' align='center' style="padding-bottom:32px;">
END;
}

function ui_footer() {
?>
</div>
</div>
</paper-header-panel>
</div>
</paper-drawer-panel>
</body>
<style is="custom-style">
.paper-header {
    background-color: var(--paper-red-500);
}

* {
    font-family:'Roboto', sans-serif;
}
    paper-header-panel div#mainContainer {
    overflow-x:visible !important;
}

.header-title {
    display:table-cell;
    text-align:center;
    width:100%;
}

.week-dropdown {
    display:table-cell;
    width:10%;
    white-space: nowrap;
}

#user-avatar {
	width: 100%;
	height: 100%;
	border-radius: 50%;
	-webkit-border-radius: 50%;
	-moz-border-radius: 50%;
  -webkit-background-size: 100% 100%;           /* Safari 3.0 */
     -moz-background-size: 100% 100%;           /* Gecko 1.9.2 (Firefox 3.6) */
       -o-background-size: 100% 100%;           /* Opera 9.5 */
          background-size: 100% 100%;           /* Gecko 2.0 (Firefox 4.0) and other CSS3-compliant browsers */
 
}

.paper-header {
    padding:16px 8px;
    color:#FFFFFF;
}

paper-icon-button:not([style-scope]):not(.style-scope) {
    display: block;
    text-align: center;
}


paper-icon-button.huge {
    margin-right: 0px;
    width: 9mm;
    --paper-icon-button-ink-color: var(--paper-indigo-500);
}

paper-icon-button.huge::shadow #icon {
    width: 9mm;
    height: 9mm;
}

paper-icon-button.huge #icon {
    width: 9mm;
    height: 9mm;
}

.rainbow, .anirbaijan {
  background-image: -webkit-gradient( linear, left top, right top, color-stop(0, #f22), color-stop(0.15, #f2f), color-stop(0.3, #22f), color-stop(0.45, #2ff), color-stop(0.6, #2f2),color-stop(0.75, #2f2), color-stop(0.9, #ff2), color-stop(1, #f22) );
  background-image: gradient( linear, left top, right top, color-stop(0, #f22), color-stop(0.15, #f2f), color-stop(0.3, #22f), color-stop(0.45, #2ff), color-stop(0.6, #2f2),color-stop(0.75, #2f2), color-stop(0.9, #ff2), color-stop(1, #f22) );
  color:transparent;
  -webkit-background-clip: text;
  background-clip: text;
}
</style>
<?php
}
?>
