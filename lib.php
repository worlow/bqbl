<?php
	function connect_db() {
		$dbconn = pg_connect("host=localhost dbname=nfldb user=nfldb password=password")
			or die('Could not connect: ' . pg_last_error());
		return $dbconn;
	}
?>