<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);
	$admin = core::is(['owner', 'admin']);

	// бапкер тек өз тобына
	function group_access($group_id) {
		global $org_id, $user_id, $admin;
		$g = db::row("select * from `groups` where id = ? and organization_id = ?", [(int) $group_id, $org_id]);
		if (!$g) return null;
		if (!$admin && (int) $g['coach_id'] !== $user_id) return null;
		return $g;
	}
	function session_access($id) {
		global $org_id, $user_id, $admin;
		$s = db::row("select * from sessions where id = ? and organization_id = ?", [(int) $id, $org_id]);
		if (!$s) return null;
		if (!$admin && (int) $s['coach_id'] !== $user_id) return null;
		return $s;
	}
	function conflict_text($c) {
		$t = [];
		foreach ($c as $x) $t[] = date('d.m H:i', strtotime($x['starts_at'])).' '.($x['group_name'] ?: 'матч').' ('.$x['venue_name'].', '.$x['coach_name'].')';
		return 'Қақтығыс: '.implode('; ', $t);
	}
	function time_ok($t) { return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t); }



	// қосымша жаттығу
	if (isset($_GET['session_add'])) {
		$g = group_access((int) fun::post('group_id'));
		$day = fun::post('date'); $st = fun::post('start'); $en = fun::post('end');
		$venue = (int) fun::post('venue_id') ?: ($g ? (int) $g['venue_id'] : 0);
		$coach = $admin ? ((int) fun::post('coach_id') ?: ($g ? (int) $g['coach_id'] : 0)) : $user_id;
		$e = [];
		if (!$g) $e['group_id'] = 'Топты таңдаңыз';
		if (!fun::valid_date($day)) $e['date'] = 'Күні қате';
		if (!time_ok($st)) $e['start'] = 'Уақыт қате';
		if (!time_ok($en) || $en <= $st) $e['end'] = 'Аяқталу уақыты қате';
		if ($venue && !db::val("select id from venues where id = ? and organization_id = ?", [$venue, $org_id])) $e['venue_id'] = 'Алаң табылмады';
		if ($e) fun::json(['ok' => false, 'errors' => $e]);
		$c = academy::conflicts($org_id, "$day $st:00", "$day $en:00", $venue, $coach);
		if ($c) fun::json(['ok' => false, 'mess' => conflict_text($c)]);
		$id = db::ins("insert into sessions (organization_id, group_id, type, starts_at, ends_at, venue_id, coach_id) values (?, ?, 'extra', ?, ?, ?, ?)",
			[$org_id, (int) $g['id'], "$day $st:00", "$day $en:00", $venue ?: null, $coach ?: null]);
		academy::notify_group($org_id, $g['id'], 'schedule_changed', 'Қосымша жаттығу: '.$g['name'], date('d.m.Y', strtotime($day))." $st–$en", '/schedule/', 'sess_add_'.$id);
		fun::audit('create', 'sessions', $id);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// болдырмау
	if (isset($_GET['session_cancel'])) {
		$s = session_access((int) fun::post('id'));
		$reason = fun::post('reason');
		if (!$s) fun::json(['ok' => false, 'mess' => 'Жаттығу табылмады']);
		if ($s['status'] == 'cancelled') fun::json(['ok' => false, 'mess' => 'Бұрын болдырылмаған']);
		if (db::val("select count(*) from attendance where session_id = ?", [(int) $s['id']])) fun::json(['ok' => false, 'mess' => 'Қатысу белгіленген жаттығуды болдыруға болмайды']);
		if (mb_strlen($reason) < 3) fun::json(['ok' => false, 'mess' => 'Себебін жазыңыз']);
		db::q("update sessions set status = 'cancelled', cancel_reason = ? where id = ?", [$reason, (int) $s['id']]);
		if ($s['group_id']) academy::notify_group($org_id, $s['group_id'], 'schedule_changed', 'Жаттығу болдырылмады', date('d.m.Y H:i', strtotime($s['starts_at'])).'. '.$reason, '/schedule/', 'sess_cancel_'.$s['id']);
		fun::audit('cancel', 'sessions', $s['id'], null, ['reason' => $reason]);
		fun::json(['ok' => true]);
	}


	// ауыстыру: ескісі болдырылмайды, жаңасы moved_from_id арқылы байланысады
	if (isset($_GET['session_move'])) {
		$s = session_access((int) fun::post('id'));
		$day = fun::post('date'); $st = fun::post('start'); $en = fun::post('end');
		$venue = (int) fun::post('venue_id') ?: (int) $s['venue_id'];
		if (!$s || $s['status'] == 'cancelled') fun::json(['ok' => false, 'mess' => 'Жаттығу табылмады']);
		if (db::val("select count(*) from attendance where session_id = ?", [(int) $s['id']])) fun::json(['ok' => false, 'mess' => 'Қатысу белгіленген жаттығуды ауыстыруға болмайды']);
		if (!fun::valid_date($day) || !time_ok($st) || !time_ok($en) || $en <= $st) fun::json(['ok' => false, 'mess' => 'Күні немесе уақыты қате']);
		$c = academy::conflicts($org_id, "$day $st:00", "$day $en:00", $venue, (int) $s['coach_id'], (int) $s['id']);
		if ($c) fun::json(['ok' => false, 'mess' => conflict_text($c)]);
		$id = db::tx(function () use ($s, $day, $st, $en, $venue, $org_id) {
			db::q("update sessions set status = 'cancelled', cancel_reason = 'Ауыстырылды' where id = ?", [(int) $s['id']]);
			return db::ins("insert into sessions (organization_id, group_id, type, starts_at, ends_at, venue_id, coach_id, moved_from_id) values (?, ?, ?, ?, ?, ?, ?, ?)",
				[$org_id, $s['group_id'] ? (int) $s['group_id'] : null, $s['type'] == 'match' ? 'match' : $s['type'], "$day $st:00", "$day $en:00", $venue ?: null, $s['coach_id'] ? (int) $s['coach_id'] : null, (int) $s['id']]);
		});
		if ($s['group_id']) academy::notify_group($org_id, $s['group_id'], 'schedule_changed', 'Жаттығу ауыстырылды', date('d.m H:i', strtotime($s['starts_at'])).' → '.date('d.m', strtotime($day))." $st", '/schedule/', 'sess_move_'.$id);
		fun::audit('move', 'sessions', $s['id'], ['starts_at' => $s['starts_at']], ['starts_at' => "$day $st", 'new_id' => $id]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// тұрақты кесте қосу
	if (isset($_GET['tpl_add'])) {
		core::need(['owner', 'admin']);
		$g = group_access((int) fun::post('group_id'));
		$wd = (int) fun::post('weekday'); $st = fun::post('start'); $en = fun::post('end');
		$venue = (int) fun::post('venue_id') ?: ($g ? (int) $g['venue_id'] : 0);
		$coach = (int) fun::post('coach_id') ?: ($g ? (int) $g['coach_id'] : 0);
		$from = fun::post('valid_from') ?: date('Y-m-d');
		if (!$g || $wd < 1 || $wd > 7 || !time_ok($st) || !time_ok($en) || $en <= $st || !fun::valid_date($from)) fun::json(['ok' => false, 'mess' => 'Өрістерді тексеріңіз']);
		// шаблондар арасындағы қақтығыс
		$c = db::all("select t.*, g.name group_name from schedule_templates t join `groups` g on g.id = t.group_id where t.organization_id = ? and t.weekday = ? and (t.valid_to is null or t.valid_to >= ?)
			and t.start_time < ? and t.end_time > ? and (".($venue ? "t.venue_id = ".(int) $venue : "0")." or ".($coach ? "t.coach_id = ".(int) $coach : "0").")", [$org_id, $wd, $from, "$en:00", "$st:00"]);
		if ($c) fun::json(['ok' => false, 'mess' => 'Қақтығыс: '.implode(', ', array_map(function ($x) { return $x['group_name'].' '.substr($x['start_time'], 0, 5); }, $c))]);
		$id = db::ins("insert into schedule_templates (organization_id, group_id, weekday, start_time, end_time, venue_id, coach_id, valid_from) values (?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, (int) $g['id'], $wd, "$st:00", "$en:00", $venue ?: null, $coach ?: null, $from]);
		academy::sessions_generate($org_id, $from, date('Y-m-d', strtotime('+14 day')));
		academy::notify_group($org_id, $g['id'], 'schedule_changed', 'Кесте өзгерді: '.$g['name'], fun::$weekdays[$wd]." $st–$en", '/schedule/', 'tpl_add_'.$id);
		fun::audit('create', 'schedule_templates', $id);
		fun::json(['ok' => true]);
	}


	// тұрақты кестені тоқтату: алдағы белгіленбеген жаттығулар болдырылмайды
	if (isset($_GET['tpl_stop'])) {
		core::need(['owner', 'admin']);
		$t = db::row("select * from schedule_templates where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		if (!$t) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		$y = date('Y-m-d', strtotime('-1 day'));
		db::tx(function () use ($t, $y) {
			db::q("update schedule_templates set valid_to = ? where id = ?", [$y, (int) $t['id']]);
			db::q("update sessions s set s.status = 'cancelled', s.cancel_reason = 'Кесте өзгерді' where s.template_id = ? and s.starts_at > now() and s.status = 'planned' and not exists (select 1 from attendance a where a.session_id = s.id)", [(int) $t['id']]);
		});
		academy::notify_group($org_id, $t['group_id'], 'schedule_changed', 'Кесте өзгерді', fun::$weekdays[$t['weekday']].' '.substr($t['start_time'], 0, 5).' жаттығуы тоқтатылды', '/schedule/', 'tpl_stop_'.$t['id']);
		fun::audit('stop', 'schedule_templates', $t['id']);
		fun::json(['ok' => true]);
	}

	http_response_code(400);
	echo 'none';
