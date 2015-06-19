CREATE OR REPLACE FUNCTION play_score_offense(gsisid gameid, playid usmallint, playerid character varying) RETURNS integer AS $$
DECLARE td integer;
DECLARE fg integer;
DECLARE xp integer;
DECLARE twop integer;
BEGIN
SELECT (fumbles_rec_tds + passing_tds + receiving_tds + rushing_tds), kicking_fgm, kicking_xpmade, (passing_twoptm + receiving_twoptm + rushing_twoptm)
    INTO td, fg, xp, twop
    FROM play_player 
    WHERE play_id=playid AND gsis_id=gsisid AND player_id=playerid;
IF td > 0 THEN RETURN 6;
ELSEIF fg > 0 THEN RETURN 3;
ELSEIF xp > 0 THEN RETURN 1;
ELSEIF twop > 0 THEN RETURN 2;
ELSE RETURN 0;
END IF;
END; $$
LANGUAGE PLPGSQL;
