<? include "../config/core.php";
	include "lib.php";

	core::need(['owner', 'admin', 'coach']);

	function tour_get($id) {
		global $org_id;
		$t = db::row("select * from tournaments where id = ? and organization_id = ?", [(int) $id, $org_id]);
		if (!$t) fun::json(['ok' => false, 'mess' => 'Турнир табылмады']);
		return $t;
	}
	function team_of($tid, $id) {
		return db::row("select * from tournament_teams where id = ? and tournament_id = ?", [(int) $id, (int) $tid]);
	}


	// жаңа турнир
	if (isset($_GET['add'])) {
		$name = trim(fun::post('name'));
		$at = str_replace('T', ' ', fun::post('starts_at'));
		if (strlen($at) == 16) $at .= ':00';
		$ts = strtotime($at);
		$fee = (float) fun::post('entry_fee', 0);
		$max = fun::post('max_teams') === '' ? null : (int) fun::post('max_teams');
		if (mb_strlen($name) < 3 || mb_strlen($name) > 120) fun::json(['ok' => false, 'mess' => 'Турнир атауын жазыңыз']);
		if (!isset($tour_kind[fun::post('kind')]) || !isset($tour_dir[fun::post('direction')])) fun::json(['ok' => false, 'mess' => 'Түрін таңдаңыз']);
		if (!$ts || date('Y-m-d H:i:s', $ts) !== $at) fun::json(['ok' => false, 'mess' => 'Күні мен уақытын жазыңыз']);
		if ($fee < 0 || $fee > 10000000) fun::json(['ok' => false, 'mess' => 'Жарна қате']);
		if ($max !== null && ($max < 2 || $max > 64)) fun::json(['ok' => false, 'mess' => 'Команда саны 2–64']);
		$code = substr(bin2hex(random_bytes(8)), 0, 10);
		$id = db::ins("insert into tournaments (organization_id, name, kind, direction, starts_at, venue, entry_fee, max_teams, prize, join_code, created_by) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, $name, fun::post('kind'), fun::post('direction'), $at, mb_substr(fun::post('venue'), 0, 160) ?: null, $fee, $max, mb_substr(fun::post('prize'), 0, 255) ?: null, $code, $user_id]);
		fun::audit('create', 'tournaments', $id);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// мәртебе
	if (isset($_GET['status'])) {
		$t = tour_get(fun::post('id'));
		$st = fun::post('status');
		if (!isset($tour_st[$st])) fun::json(['ok' => false, 'mess' => 'Мәртебе қате']);
		db::q("update tournaments set status = ? where id = ?", [$st, (int) $t['id']]);
		fun::audit('status', 'tournaments', $t['id'], ['status' => $t['status']], ['status' => $st]);
		fun::json(['ok' => true]);
	}


	// команда қосу
	if (isset($_GET['team_add'])) {
		$t = tour_get(fun::post('id'));
		$name = trim(fun::post('name'));
		$phone = fun::post('phone') === '' ? null : fun::phone_clean(fun::post('phone'));
		if (mb_strlen($name) < 2 || mb_strlen($name) > 80) fun::json(['ok' => false, 'mess' => 'Команда атауын жазыңыз']);
		if (fun::post('phone') !== '' && !$phone) fun::json(['ok' => false, 'mess' => 'Телефон қате']);
		if (in_array($t['status'], ['finished', 'cancelled'])) fun::json(['ok' => false, 'mess' => 'Турнир жабық']);
		if ($t['max_teams'] && db::val("select count(*) from tournament_teams where tournament_id = ?", [(int) $t['id']]) >= $t['max_teams']) fun::json(['ok' => false, 'mess' => 'Орын қалмады']);
		if (db::val("select id from tournament_teams where tournament_id = ? and name = ?", [(int) $t['id'], $name])) fun::json(['ok' => false, 'mess' => 'Бұл атау тіркелген']);
		$id = db::ins("insert into tournament_teams (tournament_id, name, captain, phone, paid) values (?, ?, ?, ?, ?)",
			[(int) $t['id'], $name, mb_substr(fun::post('captain'), 0, 120) ?: null, $phone, fun::post('paid') == '1' ? 1 : 0]);
		fun::audit('team_add', 'tournaments', $t['id'], null, ['team' => $name]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// команданы өшіру (ойын болмаса ғана)
	if (isset($_GET['team_remove'])) {
		$t = tour_get(fun::post('id'));
		$tm = team_of($t['id'], fun::post('team_id'));
		if (!$tm) fun::json(['ok' => false, 'mess' => 'Команда табылмады']);
		if (db::val("select count(*) from tournament_games where home_id = ? or away_id = ?", [(int) $tm['id'], (int) $tm['id']])) fun::json(['ok' => false, 'mess' => 'Команданың ойындары бар']);
		db::q("delete from tournament_teams where id = ?", [(int) $tm['id']]);
		fun::audit('team_remove', 'tournaments', $t['id'], ['team' => $tm['name']]);
		fun::json(['ok' => true]);
	}


	// жарна төледі / төлемеді
	if (isset($_GET['team_paid'])) {
		$t = tour_get(fun::post('id'));
		$tm = team_of($t['id'], fun::post('team_id'));
		if (!$tm) fun::json(['ok' => false, 'mess' => 'Команда табылмады']);
		$v = fun::post('paid') == '1' ? 1 : 0;
		db::q("update tournament_teams set paid = ? where id = ?", [$v, (int) $tm['id']]);
		fun::audit('team_paid', 'tournaments', $t['id'], null, ['team' => $tm['name'], 'paid' => $v]);
		fun::json(['ok' => true]);
	}


	// орын (1, 2, 3)
	if (isset($_GET['team_place'])) {
		$t = tour_get(fun::post('id'));
		$tm = team_of($t['id'], fun::post('team_id'));
		$pl = fun::post('place') === '' ? null : (int) fun::post('place');
		if (!$tm) fun::json(['ok' => false, 'mess' => 'Команда табылмады']);
		if ($pl !== null && ($pl < 1 || $pl > 64)) fun::json(['ok' => false, 'mess' => 'Орын қате']);
		db::tx(function () use ($t, $tm, $pl) {
			if ($pl) db::q("update tournament_teams set place = null where tournament_id = ? and place = ?", [(int) $t['id'], $pl]);
			db::q("update tournament_teams set place = ? where id = ?", [$pl, (int) $tm['id']]);
		});
		fun::audit('team_place', 'tournaments', $t['id'], null, ['team' => $tm['name'], 'place' => $pl]);
		fun::json(['ok' => true]);
	}


	// ойын қосу
	if (isset($_GET['game_add'])) {
		$t = tour_get(fun::post('id'));
		$h = team_of($t['id'], fun::post('home_id'));
		$a = team_of($t['id'], fun::post('away_id'));
		if (!$h || !$a) fun::json(['ok' => false, 'mess' => 'Екі команданы таңдаңыз']);
		if ($h['id'] == $a['id']) fun::json(['ok' => false, 'mess' => 'Команда өзімен ойнамайды']);
		$id = db::ins("insert into tournament_games (tournament_id, home_id, away_id, stage) values (?, ?, ?, ?)", [(int) $t['id'], (int) $h['id'], (int) $a['id'], mb_substr(fun::post('stage'), 0, 40) ?: null]);
		if ($t['status'] == 'registration') db::q("update tournaments set status = 'running' where id = ?", [(int) $t['id']]);
		fun::audit('game_add', 'tournaments', $t['id'], null, ['game' => $id]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// ойын есебі
	if (isset($_GET['game_score'])) {
		$t = tour_get(fun::post('id'));
		$g = db::row("select * from tournament_games where id = ? and tournament_id = ?", [(int) fun::post('game_id'), (int) $t['id']]);
		$hg = fun::post('home_goals'); $ag = fun::post('away_goals');
		if (!$g) fun::json(['ok' => false, 'mess' => 'Ойын табылмады']);
		if (!ctype_digit((string) $hg) || !ctype_digit((string) $ag) || $hg > 50 || $ag > 50) fun::json(['ok' => false, 'mess' => 'Есепті жазыңыз (0–50)']);
		db::q("update tournament_games set home_goals = ?, away_goals = ?, status = 'finished' where id = ?", [(int) $hg, (int) $ag, (int) $g['id']]);
		fun::audit('game_score', 'tournaments', $t['id'], ['h' => $g['home_goals'], 'a' => $g['away_goals']], ['game' => (int) $g['id'], 'h' => (int) $hg, 'a' => (int) $ag]);
		fun::json(['ok' => true]);
	}


	// ойынды өшіру
	if (isset($_GET['game_remove'])) {
		$t = tour_get(fun::post('id'));
		$g = db::row("select * from tournament_games where id = ? and tournament_id = ?", [(int) fun::post('game_id'), (int) $t['id']]);
		if (!$g) fun::json(['ok' => false, 'mess' => 'Ойын табылмады']);
		db::q("delete from tournament_games where id = ?", [(int) $g['id']]);
		fun::audit('game_remove', 'tournaments', $t['id'], ['game' => (int) $g['id']]);
		fun::json(['ok' => true]);
	}


	// марапат: үздік ойыншы, үздік қақпашы т.б.
	if (isset($_GET['award_add'])) {
		$t = tour_get(fun::post('id'));
		$title = trim(fun::post('title')); $person = trim(fun::post('person'));
		$pid = fun::post('participant_id') === '' ? null : (int) fun::post('participant_id');
		if (mb_strlen($title) < 2 || mb_strlen($title) > 80) fun::json(['ok' => false, 'mess' => 'Марапат атауын жазыңыз']);
		if ($pid) {
			$p = db::row("select first_name, last_name from participants where id = ? and organization_id = ?", [$pid, $org_id]);
			if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
			if ($person === '') $person = trim($p['first_name'].' '.$p['last_name']);
		}
		if (mb_strlen($person) < 2 || mb_strlen($person) > 120) fun::json(['ok' => false, 'mess' => 'Кімге берілгенін жазыңыз']);
		$id = db::ins("insert into tournament_awards (tournament_id, title, person, participant_id) values (?, ?, ?, ?)", [(int) $t['id'], $title, $person, $pid]);
		fun::audit('award_add', 'tournaments', $t['id'], null, ['title' => $title, 'person' => $person]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['award_remove'])) {
		$t = tour_get(fun::post('id'));
		$a = db::row("select * from tournament_awards where id = ? and tournament_id = ?", [(int) fun::post('award_id'), (int) $t['id']]);
		if (!$a) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		db::q("delete from tournament_awards where id = ?", [(int) $a['id']]);
		fun::audit('award_remove', 'tournaments', $t['id'], ['title' => $a['title']]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action']);
