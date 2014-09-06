<?php
require "lib.php";
echo "<html><head><title>BQBL Scoring</title></head>\n";
$dbconn = connect_db();

echo "<select id=\"year\" onchange=\"updateFrame();\">";
$query = "SELECT DISTINCT(season_year) FROM game ORDER BY season_year DESC";
$result = pg_query($query);
while (list($year) = pg_fetch_array($result)) {
    echo "<option value=\"$year\">$year</option>\n";
}
echo "</select>\n";

echo "<select id=\"week\" onchange=\"updateFrame();\">
<option selected disabled hidden value=''>Week</option>";
$query = "SELECT DISTINCT(week) FROM game WHERE season_type='Regular' ORDER BY week ASC";
$result = pg_query($query);
while (list($week) = pg_fetch_array($result)) {
    echo "<option value=\"$week\">$week</option>\n";
}
echo "</select>\n";

echo "<select id=\"team\" onchange=\"updateFrame();\">
<option selected disabled hidden value=''>Team</option>";
$query = "SELECT team_id FROM team ORDER BY team ASC";
$result = pg_query($query);
while (list($team) = pg_fetch_array($result)) {
    echo "<option value=\"$team\">$team</option>\n";
}
echo "</select>";
?>
<br>
<script type="text/javascript">
function updateFrame() {
    var s = document.getElementById("year");
    var year = s.options[s.selectedIndex].value;
    s = document.getElementById("week");
    var week = s.options[s.selectedIndex].value;
    s = document.getElementById("team");
    var team = s.options[s.selectedIndex].value;   
    if (year != '' && week != '' && team != '') {
        document.getElementById('score_frame').src = 
            "game.php?year=" + year + "&week=" + week + "&team=" + team;
    }
}
</script>
<iframe id='score_frame' width=100% height=90% style="overflow:hidden;" scrolling=no frameborder=0>