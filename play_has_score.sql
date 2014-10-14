CREATE OR REPLACE FUNCTION play_has_score(gsisid gameid, playid usmallint, playerid character varying) RETURNS BOOLEAN AS $$
BEGIN
IF (SELECT (defense_frec_tds + defense_int_tds + defense_misc_tds + kicking_rec_tds + fumbles_rec_tds + passing_tds + kickret_tds + puntret_tds + receiving_tds + rushing_tds + defense_safe + kicking_fgm + kicking_xpmade + passing_twoptm + receiving_twoptm + rushing_twoptm) > 0 FROM play_player WHERE play_id=playid AND gsis_id=gsisid AND player_id=playerid) THEN RETURN TRUE;
ELSE RETURN FALSE;
END IF;
END; $$
LANGUAGE PLPGSQL;