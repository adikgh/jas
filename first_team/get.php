<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);

	function team_get() {
		global $org_id;
		return db::row("select * from teams where organization_id = ? and type = 'first_team' order by id limit 1", [$org_id]);
	}


	// бірінші құрамды құру
	if (isset($_GET['team_add'])) {
		core::need(['owner', 'admin']);
		if (team_get()) fun::json(['ok' => false, 'mess' => 'Бірінші құрам бар']);
		$id = db::ins("insert into teams (organization_id, name, type) values (?, 'JAS FIRST TEAM', 'first_team')", [$org_id]);
		fun::audit('create', 'teams', $id);
		fun::json(['ok' => true]);
	}


	// құрамға қосу: тек JAS ADULT, шешімді бапкер қабылдайды
	if (isset($_GET['roster_add'])) {
		$t = team_get();
		$p = db::row("select * from participants where id = ? and organization_id = ? and archived_at is null", [(int) fun::post('participant_id'), $org_id]);
		$num = fun::post('number') === '' ? null : (int) fun::post('number');
		$pos = fun::post('position');
		if (!$t) fun::json(['ok' => false, 'mess' => 'Команда жоқ']);
		if (!$p) fun::json(['ok' => false, 'mess' => 'Футболшыны таңдаңыз']);
		if ($p['direction'] != 'adult') fun::json(['ok' => false, 'mess' => 'Бірінші құрамға тек JAS ADULT қатысушысы кіреді']);
		if ($num !== null && ($num < 1 || $num > 99)) fun::json(['ok' => false, 'mess' => 'Нөмір 1–99']);
		if ($pos !== '' && !in_array($pos, ['gk', 'df', 'mf', 'fw'], true)) fun::json(['ok' => false, 'mess' => 'Позиция қате']);
		if (db::val("select id from team_rosters where team_id = ? and participant_id = ? and left_at is null", [(int) $t['id'], (int) $p['id']])) fun::json(['ok' => false, 'mess' => 'Құрамда бар']);
		if ($num && db::val("select id from team_rosters where team_id = ? and number = ? and left_at is null", [(int) $t['id'], $num])) fun::json(['ok' => false, 'mess' => 'Бұл нөмір бос емес']);
		$id = db::ins("insert into team_rosters (organization_id, team_id, participant_id, number, position, joined_at) values (?, ?, ?, ?, ?, ?)", [$org_id, (int) $t['id'], (int) $p['id'], $num, $pos ?: null, $date]);
		fun::audit('roster_add', 'participants', $p['id'], null, ['team' => $t['name'], 'number' => $num]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// құрамнан шығару (тарих сақталады)
	if (isset($_GET['roster_remove'])) {
		$r = db::row("select * from team_rosters where id = ? and organization_id = ? and left_at is null", [(int) fun::post('id'), $org_id]);
		if (!$r) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		db::q("update team_rosters set left_at = ? where id = ?", [$date, (int) $r['id']]);
		fun::audit('roster_remove', 'participants', $r['participant_id']);
		fun::json(['ok' => true]);
	}


	// матч қосу (жаңа турнир / қарсылас бірге құрылады)
	if (isset($_GET['match_add'])) {
		$t = team_get();
		if (!$t) fun::json(['ok' => false, 'mess' => 'Команда жоқ']);
		$comp = fun::post('competition_id'); $opp = fun::post('opponent_id');
		$at = str_replace('T', ' ', fun::post('match_at'));
		if (strlen($at) == 16) $at .= ':00';
		$ts = strtotime($at);
		if (!$ts || date('Y-m-d H:i:s', $ts) !== $at) fun::json(['ok' => false, 'mess' => 'Күні мен уақытын жазыңыз']);
		if ($opp == 'new' && mb_strlen(fun::post('opponent_name')) < 2) fun::json(['ok' => false, 'mess' => 'Қарсылас атауын жазыңыз']);
		if ($opp != 'new' && !db::val("select id from opponents where id = ? and organization_id = ?", [(int) $opp, $org_id])) fun::json(['ok' => false, 'mess' => 'Қарсыласты таңдаңыз']);
		if ($comp == 'new' && mb_strlen(fun::post('competition_name')) < 2) fun::json(['ok' => false, 'mess' => 'Турнир атауын жазыңыз']);
		if ($comp && $comp != 'new' && !db::val("select id from competitions where id = ? and organization_id = ?", [(int) $comp, $org_id])) fun::json(['ok' => false, 'mess' => 'Турнир табылмады']);
		$id = db::tx(function () use ($t, $comp, $opp, $at, $org_id) {
			if ($opp == 'new') {
				$name = fun::post('opponent_name');
				$opp = db::val("select id from opponents where organization_id = ? and name = ?", [$org_id, $name]) ?: db::ins("insert into opponents (organization_id, name) values (?, ?)", [$org_id, $name]);
			}
			if ($comp == 'new') {
				$name = fun::post('competition_name'); $season = date('Y', strtotime($at));
				$comp = db::val("select id from competitions where organization_id = ? and name = ? and season = ?", [$org_id, $name, $season]) ?: db::ins("insert into competitions (organization_id, name, season) values (?, ?, ?)", [$org_id, $name, $season]);
			}
			return db::ins("insert into matches (organization_id, competition_id, team_id, opponent_id, round, match_at, venue, is_home) values (?, ?, ?, ?, ?, ?, ?, ?)",
				[$org_id, $comp ? (int) $comp : null, (int) $t['id'], (int) $opp, fun::post('round') ?: null, $at, fun::post('venue') ?: null, fun::post('is_home') == '0' ? 0 : 1]);
		});
		fun::audit('create', 'matches', $id);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// нәтиже + футболшы статистикасы. Матч қатысу мен абонементке әсер етпейді.
	if (isset($_GET['match_save'])) {
		$m = db::row("select * from matches where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		if (!$m) fun::json(['ok' => false, 'mess' => 'Матч табылмады']);
		$st = fun::post('status');
		$gf = fun::post('goals_for'); $ga = fun::post('goals_against');
		$mvp = (int) fun::post('mvp');
		if (!in_array($st, ['scheduled', 'live', 'finished', 'cancelled'], true)) fun::json(['ok' => false, 'mess' => 'Мәртебе қате']);
		if (in_array($st, ['live', 'finished']) && ($gf === '' || $ga === '' || $gf < 0 || $ga < 0 || $gf > 50 || $ga > 50)) fun::json(['ok' => false, 'mess' => 'Есепті жазыңыз']);
		if ($st == 'finished' && strtotime($m['match_at']) > time() + 3600) fun::json(['ok' => false, 'mess' => 'Матч әлі басталмаған']);

		$valid = array_flip(array_map('intval', array_column(db::all("select participant_id from team_rosters where team_id = ? and left_at is null union select participant_id from match_player_stats where match_id = ?", [(int) $m['team_id'], (int) $m['id']]), 'participant_id')));
		$rows = isset($_POST['players']) && is_array($_POST['players']) ? $_POST['players'] : [];
		$set = []; $goals = 0;
		foreach ($rows as $pid => $r) {
			$pid = (int) $pid;
			if (!isset($valid[$pid])) fun::json(['ok' => false, 'mess' => 'Футболшы құрамда емес']);
			$x = [
				'is_starter' => empty($r['is_starter']) ? 0 : 1,
				'minute_in' => max(0, min(130, (int) @$r['minute_in'])), 'minute_out' => max(0, min(130, (int) @$r['minute_out'])),
				'goals' => max(0, min(20, (int) @$r['goals'])), 'assists' => max(0, min(20, (int) @$r['assists'])),
				'yellow' => max(0, min(2, (int) @$r['yellow'])), 'red' => max(0, min(1, (int) @$r['red'])),
			];
			if ($x['minute_out'] < $x['minute_in']) fun::json(['ok' => false, 'mess' => 'Минуттар қате']);
			$goals += $x['goals'];
			$set[$pid] = $x;
		}
		if ($gf !== '' && $goals > (int) $gf) fun::json(['ok' => false, 'mess' => 'Футболшылардың голы ('.$goals.') команда голынан көп']);
		if ($mvp && !isset($set[$mvp])) fun::json(['ok' => false, 'mess' => 'MVP ойнағандардың ішінде болуы керек']);

		db::tx(function () use ($m, $st, $gf, $ga, $mvp, $set, $org_id) {
			db::q("update matches set status = ?, goals_for = ?, goals_against = ?, mvp_participant_id = ? where id = ?",
				[$st, $gf === '' ? null : (int) $gf, $ga === '' ? null : (int) $ga, $mvp ?: null, (int) $m['id']]);
			$keep = array_keys($set);
			db::q("delete from match_player_stats where match_id = ?".($keep ? " and participant_id not in (".implode(',', array_map('intval', $keep)).")" : ""), [(int) $m['id']]);
			foreach ($set as $pid => $x)
				db::q("insert into match_player_stats (organization_id, match_id, participant_id, is_starter, minute_in, minute_out, goals, assists, yellow, red) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
					on duplicate key update is_starter = values(is_starter), minute_in = values(minute_in), minute_out = values(minute_out), goals = values(goals), assists = values(assists), yellow = values(yellow), red = values(red)",
					[$org_id, (int) $m['id'], $pid, $x['is_starter'], $x['minute_in'], $x['minute_out'], $x['goals'], $x['assists'], $x['yellow'], $x['red']]);
		});
		fun::audit('update', 'matches', $m['id'], ['status' => $m['status'], 'score' => $m['goals_for'].':'.$m['goals_against']], ['status' => $st, 'score' => $gf.':'.$ga, 'players' => count($set)]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
