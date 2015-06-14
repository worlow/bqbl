<?php
require_once "lib/lib.php";
require_once "lib/scoring.php";

ui_header($title="NFL Teams");

$nfl_team = isset($_GET['team']) ? pg_escape_string($_GET['team']) : null;

if ($nfl_team == null) {
   foreach (nflTeams() as $team) {
       echo "<paper-material><a href='/bqbl/nfl.php?team=$team&year=$year'>$team</a></paper-material>";
   }
} else {
    $total = 0;
    list($city, $name) = nflIdToCityTeamName($nfl_team);
    echo "<h2>$city $name</h2>";
    echo '<div class="table">';
    echo "<div class=\"header row\"><div class=\"cell\"></div><div class=\"cell\">Opponent</div><div class=\"cell\">Score</div></div>";
    for ($i = 1; $i <= 17; $i++) {
        echo "<div class=\"row\"><div class=\"cell\">Week $i</div>";
        
        list($home_team,$away_team) = nflMatchup($year, $i, $nfl_team);
        if ($home_team == $nfl_team) {
            echo "<div class=\"cell\">vs <a href='/bqbl/nfl.php?team=$away_team&year=$year'>$away_team</a></div>";
        } elseif ($away_team == $nfl_team) {
            echo "<div class=\"cell\">@ <a href='/bqbl/nfl.php?team=$home_team&year=$year'>$home_team</a></div>";
        } else {
            echo "<div class=\"cell\">BYE</div>";
        }
        $points = totalPoints(getPoints($nfl_team, $i, $year));
        $total += $points;
        echo "<div class=\"cell\">$points</div></div>";
    }
    echo "<div class=\"row\"><div class=\"cell\">Total</div><div class=\"cell\"> -- </div><div class=\"cell\">$total</div></div>";
    echo "</div>";
    echo "<div><a href='/bqbl/nfl.php?year=$year'>Back</a></div>";
}

?>

<style is="custom-style">
paper-material {
    display: inline-block;
    background-color: #FFFFFF;
    padding: 8px;
    margin: 12px;
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

.table {
  display: table;
  border-collapse: separate;
  font-size: 1vw;
  text-align: center;
}

.table .cell {
  border-top: 1px solid #e5e5e5;
  padding: 8px;
}

.table .thickline .cell {
  border-bottom: 5px solid #000000;
}

.table .header .cell {
    border-top: 0;
    font-weight: bold;
    font-size: 110%;
    padding-top: 0;
}

.cardheader {
    display:inline-block;
    font-weight: bold;
    font-size: 150%;
    padding-bottom: 16px;
}
</style>

<?php
ui_footer();
?>
