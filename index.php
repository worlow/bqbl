<?php
require_once "lib/lib.php";
?>
<html><head><title>BQBL</title>
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
</head>
<audio autoplay loop><source src="media/GetLow.mp3" type="audio/mpeg" /></audio>
<marquee scrollamount=18><h1 class="blink">Welcome to the BQBL page!!!</h1></marquee>

<?php
if(isset($_SESSION['user'])) {
    echo "<a href='lineup.php'>View and set lineups</a><br>";
} else {
    echo "<a href='auth/login.php'>Log in</a><br>";
}
?>
<a href='standings.php'>Standings</a><br>
<a href='schedule.php'>Schedule</a><br>
<a href='matchup.php'>Weekly Matchups</a><br>
<a href='week.php'>Scoreboard</a><br>
<a href='leaderboard.php'>Weekly Leaders</a><br>
<a href='seasonleaderboard.php'>Season Rankings</a><br>
<a href='extrapoints.php'>Input Extra BQBL Points</a><br>
<a href='champions.html'>Past Champions</a><br>
<a href='cares/'>BQBL Cares</a><br>
