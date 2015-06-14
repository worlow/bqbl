<?php
require_once "lib/lib.php";
ui_header($title="BQBL");

echo <<<END
<style type="text/css">
.blink {
  animation: blink 1s steps(3, start) infinite;
  -webkit-animation: blink 1s steps(3, start) infinite;
  color:red;
}
@keyframes blink {
  to { visibility: hidden; }
}
@-webkit-keyframes blink {
  to { visibility: hidden; }
}
</style>
<marquee scrollamount=18><h1 class="blink">Welcome to the BQBL page!!!</h1></marquee>
END;

if(isset($_SESSION['user'])) {
    echo "<a href='lineup.php'>View and set lineup</a><br>";
} else {
    echo "<a href='auth/login.php'>Log in</a><br>";
}
echo <<<END
<a href='matchup.php'>BQBL Matchup Scoreboard</a><br>
<a href='week.php'>BQBL NFL Scoreboard</a><br>
<a href='standings.php'>Standings</a><br>
<a href='schedule.php'>Schedule</a><br>
<a href='leaderboard.php'>Weekly Leaders</a><br>
<a href='seasonleaderboard.php'>Season Rankings</a><br>
<a href='extrapoints.php'>Input Extra BQBL Points</a><br>
<a href='nfl.php'>NFL Teams</a><br> 
<a href='bios.html'>League Management</a><br>
<a href='cares/'>BQBL Cares</a><br>
END;

ui_footer();
?>
